<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\FcmToken;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use App\Models\VisitorLog;
use App\Services\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmPreparationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $tempCredentialsFile = null;

    protected function tearDown(): void
    {
        if ($this->tempCredentialsFile !== null && is_file($this->tempCredentialsFile)) {
            @unlink($this->tempCredentialsFile);
        }
        parent::tearDown();
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
    }

    private function createTemporaryServiceAccountFile(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);

        $privateKeyPem = '';
        $exported = openssl_pkey_export($key, $privateKeyPem);
        $this->assertTrue($exported);

        $path = storage_path('app/private/test_fcm_sa_' . uniqid() . '.json');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'project_id' => 'dambatu-test',
            'client_email' => 'fcm-test@dambatu-test.iam.gserviceaccount.com',
            'private_key' => $privateKeyPem,
        ]));

        $this->tempCredentialsFile = $path;

        return $path;
    }

    public function test_manifest_and_service_worker_work_safely_and_are_excluded_from_analytics(): void
    {
        config()->set('services.fcm.enabled', false);

        $manifestRes = $this->get('/manifest.json');
        $manifestRes->assertStatus(200)
            ->assertJsonStructure(['name', 'short_name', 'start_url', 'display', 'icons']);

        $swDisabled = $this->get('/firebase-messaging-sw.js');
        $swDisabled->assertStatus(200)
            ->assertHeader('Service-Worker-Allowed', '/');
        $this->assertStringContainsString('application/javascript', (string) $swDisabled->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $swDisabled->headers->get('Cache-Control'));

        // Pastikan manifest dan sw tidak tercatat di visitor_logs
        $this->assertEquals(0, VisitorLog::whereIn('path', ['/manifest.json', '/firebase-messaging-sw.js'])->count());

        // VAPID placeholder harus dianggap belum siap
        config()->set('services.fcm.enabled', true);
        config()->set('services.fcm.project_id', 'dambatu-test');
        config()->set('services.fcm.api_key', 'AIzaFakePublicKey123');
        config()->set('services.fcm.messaging_sender_id', '123456789');
        config()->set('services.fcm.app_id', '1:123456789:web:abcdef');
        config()->set('services.fcm.vapid_key', 'TEMPEL_VAPID_KEY_DI_SINI');
        $this->assertFalse(app(FcmService::class)->isClientReady());

        // Saat client config terisi penuh dengan VAPID valid, SW menyertakan guard anti-duplikat & tag
        config()->set('services.fcm.vapid_key', 'B' . str_repeat('AbCdEfGh1234', 7));
        $this->assertTrue(app(FcmService::class)->isClientReady());

        $swEnabled = $this->get('/firebase-messaging-sw.js');
        $swEnabled->assertStatus(200);
        $this->assertStringContainsString('firebase.initializeApp', $swEnabled->getContent());
        $this->assertStringContainsString('onBackgroundMessage', $swEnabled->getContent());
        $this->assertStringContainsString('payload.notification', $swEnabled->getContent());
        $this->assertStringContainsString('self.location.origin', $swEnabled->getContent());
        $this->assertStringContainsString('tag:', $swEnabled->getContent());
        $this->assertStringContainsString('dambatu-test', $swEnabled->getContent());
        $this->assertStringNotContainsString('private_key', $swEnabled->getContent());

        // Halaman Dashboard, Klasemen, dan Berita merender modal opt-in & card kecil ajakan notifikasi
        foreach (['/', '/klasemen', '/berita'] as $publicUrl) {
            $page = $this->get($publicUrl);
            $page->assertStatus(200);
            $page->assertSee('fcm-toggle-btn');
            $page->assertSee('id="fcmPromptModal"', false);
            $page->assertSee('id="fcmPromptYesBtn"', false);
            $page->assertSee('id="fcmPromptLaterBtn"', false);
            $page->assertSee('id="fcmPromptNoBtn"', false);
            $page->assertSee('fcm-nudge-card');
            $page->assertSee('fcm-nudge-enable-btn');
            $page->assertSee('data-cfasync="false"', false);
        }
    }

    public function test_visitor_can_subscribe_and_unsubscribe_fcm_token(): void
    {
        // Token pendek ditolak
        $this->postJson('/api/fcm/subscribe', ['token' => 'short'])
            ->assertStatus(422);

        $sampleToken = str_repeat('a1b2c3d4e5', 4);
        $this->postJson('/api/fcm/subscribe', [
            'token' => $sampleToken,
            'notify_skor' => true,
            'notify_berita' => true,
        ])->assertStatus(200)->assertJson([
            'success' => true,
            'subscribed' => true,
        ]);

        // Subscribe ulang token yang sama tidak membuat duplikat
        $this->postJson('/api/fcm/subscribe', [
            'token' => $sampleToken,
            'notify_skor' => true,
            'notify_berita' => false,
        ])->assertStatus(200);

        $this->assertEquals(1, FcmToken::count());
        $this->assertFalse(FcmToken::first()->notify_berita);

        // Unsubscribe menghapus token
        $this->postJson('/api/fcm/unsubscribe', ['token' => $sampleToken])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'subscribed' => false,
            ]);
        $this->assertEquals(0, FcmToken::count());
    }

    public function test_fcm_service_skips_gracefully_when_unconfigured(): void
    {
        config()->set('services.fcm.enabled', false);
        FcmToken::create([
            'token' => str_repeat('z', 40),
            'token_hash' => FcmToken::hashToken(str_repeat('z', 40)),
            'notify_skor' => true,
            'notify_berita' => true,
        ]);

        $liga = Liga::current();
        $berita = Berita::create([
            'liga_id' => $liga->id,
            'judul' => 'Berita Tanpa FCM',
            'slug' => 'berita-tanpa-fcm',
            'kategori' => 'Berita',
            'isi' => 'Tidak mengirim push karena FCM belum aktif.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $res = app(FcmService::class)->notifyBeritaPublished($berita);
        $this->assertTrue($res['skipped']);
        $this->assertNull($berita->fresh()->notified_at);
    }

    public function test_fcm_sends_score_and_news_notifications_prunes_stale_tokens_and_runs_scheduler(): void
    {
        Cache::flush();
        $saPath = $this->createTemporaryServiceAccountFile();

        config()->set('services.fcm.enabled', true);
        config()->set('services.fcm.project_id', 'dambatu-test');
        config()->set('services.fcm.credentials_path', $saPath);

        $validToken = str_repeat('valid_tok_', 4);
        $staleToken = str_repeat('stale_tok_', 4);

        FcmToken::create([
            'token' => $validToken,
            'token_hash' => FcmToken::hashToken($validToken),
            'notify_skor' => true,
            'notify_berita' => true,
        ]);
        FcmToken::create([
            'token' => $staleToken,
            'token_hash' => FcmToken::hashToken($staleToken),
            'notify_skor' => true,
            'notify_berita' => true,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-oauth-access-token',
                'expires_in' => 3600,
            ], 200),
            'https://fcm.googleapis.com/v1/projects/dambatu-test/messages:send' => Http::sequence()
                // Panggilan 1 (skor, validToken) -> sukses
                ->push(['name' => 'projects/dambatu-test/messages/1'], 200)
                // Panggilan 2 (skor, staleToken) -> 404 UNREGISTERED -> token harus terhapus
                ->push([
                    'error' => [
                        'code' => 404,
                        'message' => 'Requested entity was not found.',
                        'status' => 'NOT_FOUND',
                        'details' => [['errorCode' => 'UNREGISTERED']],
                    ],
                ], 404)
                // Panggilan 3 (berita langsung, validToken) -> sukses
                ->push(['name' => 'projects/dambatu-test/messages/2'], 200)
                // Panggilan 4 (berita terjadwal via scheduler, validToken) -> sukses
                ->push(['name' => 'projects/dambatu-test/messages/3'], 200),
        ]);

        $admin = $this->createAdmin();
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026/2027',
            'status' => 'berjalan',
            'jumlah_meja' => 4,
            'hari_pertandingan' => '2,3,4',
        ]);

        $tim1 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'SAYA AKAN LAWAN',
            'pemain1' => 'Nico',
            'pemain2' => 'Sem',
            'status' => 1,
        ]);
        $tim2 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'KUDA BINAL',
            'pemain1' => 'Julfi',
            'pemain2' => 'Eko',
            'status' => 1,
        ]);
        $match = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 10,
            'tanggal' => now()->toDateString(),
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $tim1->id,
            'tim_away' => $tim2->id,
            'status' => 'belum_main',
        ]);

        // Simpan skor lewat AdminController -> memicu notifikasi FCM skor & hapus token UNREGISTERED
        $this->actingAs($admin)->post('/admin/pertandingan/' . $match->id, [
            'status' => 'selesai',
            'skor_home' => 31,
            'skor_away' => 28,
        ])->assertRedirect();

        // Stale token sudah terhapus, tersisa 1 token valid
        $this->assertEquals(1, FcmToken::count());
        $this->assertEquals($validToken, FcmToken::first()->token);

        // Publish berita langsung -> memicu push 1x & set notified_at
        $berita = Berita::create([
            'liga_id' => $liga->id,
            'judul' => 'Kemenangan Dramatis Meja 1',
            'slug' => 'kemenangan-dramatis-meja-1',
            'kategori' => 'Rekap Laga',
            'isi' => 'Duel sengit berakhir 31-28.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $firstNewsPush = app(FcmService::class)->notifyBeritaPublished($berita);
        $this->assertFalse($firstNewsPush['skipped']);
        $this->assertEquals(1, $firstNewsPush['sent']);
        $this->assertNotNull($berita->fresh()->notified_at);

        // Panggil ulang tidak mengirim duplikat
        $dupPush = app(FcmService::class)->notifyBeritaPublished($berita->fresh());
        $this->assertTrue($dupPush['skipped']);

        // Berita terjadwal yang sudah jatuh tempo diproses oleh command berita:notify-scheduled
        $dueScheduled = Berita::create([
            'liga_id' => $liga->id,
            'judul' => 'Jadwal Malam Ini Resmi Rilis',
            'slug' => 'jadwal-malam-ini-resmi-rilis',
            'kategori' => 'Pengumuman',
            'isi' => 'Berita terjadwal yang waktunya baru saja tiba.',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'notified_at' => null,
        ]);

        $this->artisan('berita:notify-scheduled')->assertExitCode(0);
        $this->assertNotNull($dueScheduled->fresh()->notified_at);

        // Jalankan command kedua kali -> 0 artikel lagi yang dikirim
        $this->artisan('berita:notify-scheduled')
            ->expectsOutputToContain('0 artikel')
            ->assertExitCode(0);

        // Total HTTP requests ke FCM send endpoint harus tepat 4 (2 untuk skor + 1 berita langsung + 1 berita terjadwal)
        // Memastikan payload data-only agar tidak muncul notifikasi ganda di Android/Chrome
        Http::assertSent(function (HttpClientRequest $req) {
            return str_contains($req->url(), 'fcm.googleapis.com/v1/projects/dambatu-test/messages:send')
                && !isset($req['message']['notification'])
                && !isset($req['message']['webpush']['notification'])
                && ($req['message']['webpush']['headers']['Urgency'] ?? '') === 'high'
                && ($req['message']['data']['type'] ?? '') === 'skor'
                && !empty($req['message']['data']['title'])
                && !empty($req['message']['data']['tag'])
                && str_contains((string) ($req['message']['data']['body'] ?? ''), '31 - 28');
        });
        Http::assertSent(function (HttpClientRequest $req) {
            return str_contains($req->url(), 'fcm.googleapis.com/v1/projects/dambatu-test/messages:send')
                && !isset($req['message']['notification'])
                && ($req['message']['data']['slug'] ?? '') === 'jadwal-malam-ini-resmi-rilis';
        });
        Http::assertSentCount(5); // 1 OAuth + 4 FCM messages:send
    }

    public function test_admin_can_view_fcm_status_and_send_test_fcm_notification(): void
    {
        // Guest tidak boleh mengakses route test FCM
        $this->post('/admin/pengaturan/test-fcm', [])->assertRedirect('/login');

        $admin = $this->createAdmin();
        Liga::current();

        // Halaman pengaturan menampilkan panel Notifikasi Push & tombol test
        $this->actingAs($admin)->get('/admin/pengaturan')
            ->assertStatus(200)
            ->assertSee('Notifikasi Push Browser (FCM)')
            ->assertSee('Kirim Test Notifikasi FCM');

        // Jika FCM nonaktif -> tampil peringatan
        config()->set('services.fcm.enabled', false);
        $this->actingAs($admin)->post('/admin/pengaturan/test-fcm', [])
            ->assertRedirect()
            ->assertSessionHas('warning');

        // Jika aktif & server siap, tetapi belum ada browser terdaftar -> tampil peringatan
        Cache::flush();
        $saPath = $this->createTemporaryServiceAccountFile();
        config()->set('services.fcm.enabled', true);
        config()->set('services.fcm.project_id', 'dambatu-test');
        config()->set('services.fcm.credentials_path', $saPath);

        $this->actingAs($admin)->post('/admin/pengaturan/test-fcm', [])
            ->assertRedirect()
            ->assertSessionHas('warning');

        // Daftarkan 1 token lalu kirim test notifikasi FCM
        $token = str_repeat('test_device_', 3);
        FcmToken::create([
            'token' => $token,
            'token_hash' => FcmToken::hashToken($token),
            'notify_skor' => false,
            'notify_berita' => false,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-oauth-access-token',
                'expires_in' => 3600,
            ], 200),
            'https://fcm.googleapis.com/v1/projects/dambatu-test/messages:send' => Http::response([
                'name' => 'projects/dambatu-test/messages/test-1',
            ], 200),
        ]);

        $res = $this->actingAs($admin)->post('/admin/pengaturan/test-fcm', [
            'judul' => 'Uji Coba Push Liga',
            'pesan' => 'Notifikasi dari admin berhasil mendarat.',
        ]);

        $res->assertRedirect()->assertSessionHas('success');
        $this->assertStringContainsString('berhasil dikirim ke 1 perangkat', (string) session('success'));

        Http::assertSent(function (HttpClientRequest $req) use ($token) {
            return str_contains($req->url(), 'fcm.googleapis.com/v1/projects/dambatu-test/messages:send')
                && !isset($req['message']['notification'])
                && !isset($req['message']['webpush']['notification'])
                && ($req['message']['webpush']['headers']['Urgency'] ?? '') === 'high'
                && ($req['message']['token'] ?? '') === $token
                && ($req['message']['data']['title'] ?? '') === 'Uji Coba Push Liga'
                && ($req['message']['data']['body'] ?? '') === 'Notifikasi dari admin berhasil mendarat.'
                && ($req['message']['data']['url'] ?? '') === '/'
                && ($req['message']['data']['type'] ?? '') === 'test'
                && !empty($req['message']['data']['tag']);
        });
    }
}
