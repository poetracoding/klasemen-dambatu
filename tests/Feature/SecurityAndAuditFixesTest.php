<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Poster;
use App\Models\Tim;
use App\Models\User;
use App\Models\VisitorLog;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecurityAndAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Audit',
            'username' => 'adminaudit',
            'email' => 'audit@test.local',
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_security_headers_are_present_on_responses(): void
    {
        Liga::current();

        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_login_is_rate_limited_and_logout_requires_post(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        // Logout via GET harus ditolak (405 Method Not Allowed)
        $this->actingAs($admin)->get('/logout')->assertStatus(405);
        $this->actingAs($admin)->post('/logout')->assertRedirect('/');

        // 5 kali gagal login masih diproses, ke-6 harus kena throttle 429
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'username' => 'adminaudit',
                'password' => 'salah-' . $i,
            ])->assertRedirect();
        }

        $this->post('/login', [
            'username' => 'adminaudit',
            'password' => 'salah-6',
        ])->assertStatus(429);
    }

    public function test_poster_upload_rejects_non_image_payloads(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $fakeScript = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');

        $this->actingAs($admin)->post('/admin/poster', [
            'judul' => 'Uji Keamanan',
            'poster' => $fakeScript,
        ])->assertSessionHasErrors('poster');
    }

    public function test_public_pdf_jadwal_does_not_leak_draft_schedule(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Rahasia Draft',
            'musim' => '2026',
            'status' => 'draft',
        ]);
        $t1 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Alpha Draft', 'pemain1' => 'A', 'pemain2' => 'B', 'status' => 1]);
        $t2 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Beta Draft', 'pemain1' => 'C', 'pemain2' => 'D', 'status' => 1]);

        Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 1,
            'tanggal' => '2026-12-01',
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'status' => 'belum_main',
        ]);

        // Publik tidak boleh melihat jumlah laga draft di PDF jadwal
        $publicRes = $this->get('/cetak/jadwal');
        $publicRes->assertStatus(200);
        $this->assertEquals('application/pdf', $publicRes->headers->get('content-type'));

        // Cek langsung data controller/view saat guest vs admin
        $admin = $this->createAdmin();
        $adminRes = $this->actingAs($admin)->get('/cetak/jadwal');
        $adminRes->assertStatus(200);
        $this->assertGreaterThan(strlen($publicRes->getContent()), strlen($adminRes->getContent()));
    }

    public function test_admin_match_and_slot_validation_prevents_invalid_inputs(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Berjalan',
            'musim' => '2026',
            'status' => 'berjalan',
        ]);
        $t1 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim Satu', 'pemain1' => 'A', 'pemain2' => 'B', 'status' => 1]);
        $t2 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim Dua', 'pemain1' => 'C', 'pemain2' => 'D', 'status' => 1]);
        $match = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 1,
            'tanggal' => '2026-12-01',
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'status' => 'belum_main',
        ]);

        $admin = $this->createAdmin();

        $this->actingAs($admin)->post('/admin/pertandingan/' . $match->id, [
            'status' => 'invalid_status',
            'skor_home' => -5,
        ])->assertSessionHasErrors(['status', 'skor_home']);

        $this->actingAs($admin)->post('/admin/jadwal/slot/' . $match->id, [
            'tanggal' => 'bukan-tanggal',
            'waktu' => '99:invalid',
        ])->assertSessionHasErrors(['tanggal', 'waktu']);
    }

    public function test_schedule_generator_rolls_date_over_when_passing_midnight(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Malam',
            'musim' => '2026',
            'status' => 'draft',
        ]);

        for ($i = 1; $i <= 6; $i++) {
            Tim::create([
                'liga_id' => $liga->id,
                'nama_tim' => 'Tim ' . $i,
                'pemain1' => 'P' . $i . 'a',
                'pemain2' => 'P' . $i . 'b',
                'status' => 1,
            ]);
        }

        // Mulai pukul 23:00, durasi 45 menit, per_hari=10, 1 meja => slot ke-2 jam 23:45, slot ke-3 harus pindah ke hari berikutnya (23:00), bukan 00:30 di tanggal yang sama
        TournamentService::buatJadwal($liga, [
            'mulai' => '2026-12-01',
            'per_hari' => 10,
            'jumlah_meja' => 1,
            'jam' => '23:00',
            'durasi' => 45,
            'hari' => [1, 2, 3, 4, 5, 6, 7],
        ]);

        $matches = Pertandingan::where('liga_id', $liga->id)->orderBy('nomor_pertandingan')->get();
        $this->assertGreaterThan(2, $matches->count());
        $this->assertEquals('2026-12-01', $matches[0]->tanggal->format('Y-m-d'));
        $this->assertEquals('23:00', substr((string) $matches[0]->waktu, 0, 5));
        $this->assertEquals('2026-12-01', $matches[1]->tanggal->format('Y-m-d'));
        $this->assertEquals('23:45', substr((string) $matches[1]->waktu, 0, 5));
        $this->assertEquals('2026-12-02', $matches[2]->tanggal->format('Y-m-d'));
        $this->assertEquals('23:00', substr((string) $matches[2]->waktu, 0, 5));
    }

    public function test_backup_includes_posters_and_beritas_and_prunes_only_old_visitor_logs(): void
    {
        $admin = $this->createAdmin();
        $liga = Liga::current();

        Poster::create([
            'liga_id' => $liga->id,
            'judul' => 'Poster Eksisting',
            'file_path' => 'peraturan4.png',
            'urutan' => 1,
            'is_active' => true,
        ]);

        Berita::create([
            'liga_id' => $liga->id,
            'user_id' => $admin->id,
            'judul' => 'Berita Eksisting',
            'slug' => 'berita-eksisting',
            'kategori' => 'Berita',
            'isi' => 'Konten berita aman.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $backupRes = $this->actingAs($admin)->post('/admin/pengaturan/backup');
        $backupRes->assertStatus(200);
        $json = json_decode($backupRes->getContent(), true);
        $this->assertArrayHasKey('posters', $json);
        $this->assertArrayHasKey('beritas', $json);
        $this->assertCount(1, $json['posters']);
        $this->assertCount(1, $json['beritas']);

        // Uji command visitor:prune hanya menghapus data lama (>90 hari)
        VisitorLog::create([
            'visit_date' => now()->subDays(120)->toDateString(),
            'session_id' => 'old-sess',
            'ip_address' => '127.0.0.1',
            'path' => '/',
            'device_type' => 'desktop',
        ]);
        VisitorLog::create([
            'visit_date' => now()->subDays(5)->toDateString(),
            'session_id' => 'recent-sess',
            'ip_address' => '127.0.0.1',
            'path' => '/klasemen',
            'device_type' => 'desktop',
        ]);

        $this->artisan('visitor:prune --days=90')->assertExitCode(0);
        $this->assertEquals(1, VisitorLog::count());
        $this->assertEquals('recent-sess', VisitorLog::first()->session_id);
    }

    public function test_json_restore_normalizes_iso8601_berita_datetimes_for_mysql(): void
    {
        $admin = $this->createAdmin();

        $payload = json_encode([
            'liga' => [
                'nama_liga' => 'Liga Dam Batu',
                'musim' => '2026/2027',
            ],
            'tims' => [
                ['id' => 1, 'nama_tim' => 'Tim A', 'pemain1' => 'P1', 'pemain2' => 'P2', 'status' => 1],
                ['id' => 2, 'nama_tim' => 'Tim B', 'pemain1' => 'P3', 'pemain2' => 'P4', 'status' => 1],
            ],
            'pertandingans' => [
                [
                    'id' => 10,
                    'ronde' => 1,
                    'nomor_pertandingan' => 1,
                    'tanggal' => '2026-11-20',
                    'waktu' => '17:00:00',
                    'lokasi' => 'Meja 1',
                    'tim_home' => 1,
                    'tim_away' => 2,
                    'skor_home' => 50,
                    'skor_away' => 40,
                    'pemenang_id' => 1,
                    'status' => 'selesai',
                    'catatan' => null,
                    'created_at' => '2026-11-20T10:00:00.000000Z',
                    'updated_at' => '2026-11-20T10:30:00.000000Z',
                ],
            ],
            'posters' => [],
            'beritas' => [
                [
                    'judul' => 'Berita ISO Backup',
                    'slug' => 'berita-iso-backup',
                    'kategori' => 'Berita',
                    'ringkasan' => 'Ringkasan singkat',
                    'isi' => 'Isi berita dari backup JSON.',
                    'status' => 'published',
                    'published_at' => '2026-11-20T11:58:00.000000Z',
                    'notified_at' => '2026-11-20T12:00:00.000000Z',
                    'views_count' => 12,
                    'likes_count' => 3,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('backup.json', $payload);

        $response = $this->actingAs($admin)->post('/admin/pengaturan/upload-db', [
            'db_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $row = \Illuminate\Support\Facades\DB::table('beritas')
            ->where('slug', 'berita-iso-backup')
            ->first();

        $this->assertNotNull($row);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $row->published_at);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $row->notified_at);
        $this->assertStringNotContainsString('T', (string) $row->published_at);
        $this->assertStringNotContainsString('Z', (string) $row->published_at);

        $matchRow = \Illuminate\Support\Facades\DB::table('pertandingans')->where('id', 10)->first();
        $this->assertNotNull($matchRow);
        $expectedMatchUpdatedAt = \Carbon\Carbon::parse('2026-11-20T10:30:00.000000Z')
            ->setTimezone(config('app.timezone', 'UTC'))
            ->format('Y-m-d H:i:s');
        $this->assertEquals($expectedMatchUpdatedAt, (string) $matchRow->updated_at);
    }
}
