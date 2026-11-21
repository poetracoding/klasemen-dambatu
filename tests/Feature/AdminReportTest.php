<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
    }

    private function seedReportFixture(): array
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026/2027',
            'status' => 'berjalan',
            'jumlah_meja' => 6,
            'hari_pertandingan' => '2,3,4',
        ]);

        $t1 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'SAYA AKAN LAWAN',
            'pemain1' => 'Nico',
            'pemain2' => 'Sem',
            'status' => 1,
        ]);
        $t2 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'KUDA BINAL',
            'pemain1' => 'Julfi Ramadhan',
            'pemain2' => 'Eko',
            'status' => 1,
        ]);
        $t3 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'BUKAN SRM',
            'pemain1' => 'Dicky',
            'pemain2' => 'Maman',
            'status' => 1,
        ]);
        $t4 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'CYBER CRIME',
            'pemain1' => 'Josep',
            'pemain2' => 'Brelly',
            'status' => 1,
        ]);

        // Match A: jadwal Januari, updated_at 2026-11-20 19:30:00 (Minggu ke-47, Nov 2026)
        $mA = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 101,
            'tanggal' => '2026-01-05',
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'skor_home' => 31,
            'skor_away' => 27,
            'pemenang_id' => $t1->id,
            'status' => 'selesai',
            'catatan' => 'Duel sengit',
        ]);
        DB::table('pertandingans')->where('id', $mA->id)->update(['updated_at' => '2026-11-20 19:30:00']);

        // Match B: jadwal Januari, updated_at 2026-11-18 18:15:00 (Minggu ke-47 yang sama, Nov 2026)
        $mB = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 2,
            'nomor_pertandingan' => 102,
            'tanggal' => '2026-01-06',
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 2',
            'tim_home' => $t3->id,
            'tim_away' => $t4->id,
            'skor_home' => 30,
            'skor_away' => 22,
            'pemenang_id' => $t3->id,
            'status' => 'selesai',
        ]);
        DB::table('pertandingans')->where('id', $mB->id)->update(['updated_at' => '2026-11-18 18:15:00']);

        // Match C: jadwal 2026-11-20 (!), tetapi updated_at 2026-10-10 20:00:00 (Oktober 2026)
        $mC = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 3,
            'nomor_pertandingan' => 103,
            'tanggal' => '2026-11-20',
            'waktu' => '20:00:00',
            'lokasi' => 'Meja 3',
            'tim_home' => $t1->id,
            'tim_away' => $t3->id,
            'skor_home' => 29,
            'skor_away' => 31,
            'pemenang_id' => $t3->id,
            'status' => 'selesai',
        ]);
        DB::table('pertandingans')->where('id', $mC->id)->update(['updated_at' => '2026-10-10 20:00:00']);

        // Match D: belum_main walau updated_at 2026-11-20 -> tidak boleh masuk laporan hasil
        $mD = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 4,
            'nomor_pertandingan' => 104,
            'tanggal' => '2026-11-20',
            'waktu' => '21:00:00',
            'lokasi' => 'Meja 4',
            'tim_home' => $t2->id,
            'tim_away' => $t4->id,
            'skor_home' => 0,
            'skor_away' => 0,
            'pemenang_id' => null,
            'status' => 'belum_main',
        ]);
        DB::table('pertandingans')->where('id', $mD->id)->update(['updated_at' => '2026-11-20 19:45:00']);

        return compact('liga', 't1', 't2', 't3', 't4', 'mA', 'mB', 'mC', 'mD');
    }

    public function test_guest_cannot_access_admin_report_routes(): void
    {
        $this->get('/admin/pengaturan/report')->assertRedirect('/login');
        $this->get('/admin/pengaturan/report/pdf')->assertRedirect('/login');
    }

    public function test_report_filters_harian_mingguan_bulanan_strictly_by_updated_at_with_team_and_players(): void
    {
        $admin = $this->createAdmin();
        $fx = $this->seedReportFixture();

        // 1. Per Hari (2026-11-20) -> hanya Match A, bukan Match C (jadwal Nov 20 tp updated_at Okt) atau Match D (belum_main)
        $harian = $this->actingAs($admin)->get('/admin/pengaturan/report?periode=harian&tanggal=2026-11-20');
        $harian->assertStatus(200);
        $harian->assertSee('Laporan Hasil');
        $harian->assertSee('SAYA AKAN LAWAN');
        $harian->assertSee('Nico &amp; Sem', false);
        $harian->assertSee('KUDA BINAL');
        $harian->assertSee('Julfi Ramadhan &amp; Eko', false);
        $harian->assertSee('31 - 27');
        $harian->assertDontSee('30 - 22');
        $harian->assertDontSee('29 - 31');
        $this->assertEquals([$fx['mA']->id], $harian->viewData('matches')->pluck('id')->all());

        // 2. Per Minggu (2026-W47) -> Match A dan Match B (urut updated_at terbaru lebih dulu)
        $mingguan = $this->actingAs($admin)->get('/admin/pengaturan/report?periode=mingguan&minggu=2026-W47');
        $mingguan->assertStatus(200);
        $mingguan->assertSee('31 - 27');
        $mingguan->assertSee('30 - 22');
        $mingguan->assertSee('Dicky &amp; Maman', false);
        $mingguan->assertSee('Josep &amp; Brelly', false);
        $mingguan->assertDontSee('29 - 31');
        $this->assertEquals([$fx['mA']->id, $fx['mB']->id], $mingguan->viewData('matches')->pluck('id')->all());

        // 3. Per Bulan (2026-11 vs 2026-10)
        $bulananNov = $this->actingAs($admin)->get('/admin/pengaturan/report?periode=bulanan&bulan=2026-11');
        $bulananNov->assertStatus(200);
        $this->assertEquals([$fx['mA']->id, $fx['mB']->id], $bulananNov->viewData('matches')->pluck('id')->all());

        $bulananOkt = $this->actingAs($admin)->get('/admin/pengaturan/report?periode=bulanan&bulan=2026-10');
        $bulananOkt->assertStatus(200);
        $bulananOkt->assertSee('29 - 31');
        $bulananOkt->assertDontSee('31 - 27');
        $this->assertEquals([$fx['mC']->id], $bulananOkt->viewData('matches')->pluck('id')->all());

        // 4. Filter tim opsional di periode bulanan 2026-11 (BUKAN SRM -> hanya Match B)
        $timFiltered = $this->actingAs($admin)->get('/admin/pengaturan/report?periode=bulanan&bulan=2026-11&tim=' . $fx['t3']->id);
        $timFiltered->assertStatus(200);
        $this->assertEquals([$fx['mB']->id], $timFiltered->viewData('matches')->pluck('id')->all());
    }

    public function test_admin_can_export_report_pdf_inline_and_download_including_empty_period(): void
    {
        $admin = $this->createAdmin();
        $this->seedReportFixture();

        // Inline stream PDF Harian
        $inlineRes = $this->actingAs($admin)->get('/admin/pengaturan/report/pdf?periode=harian&tanggal=2026-11-20');
        $inlineRes->assertStatus(200);
        $this->assertEquals('application/pdf', $inlineRes->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $inlineRes->getContent());

        // Download PDF Mingguan
        $downloadRes = $this->actingAs($admin)->get('/admin/pengaturan/report/pdf?periode=mingguan&minggu=2026-W47&download=1');
        $downloadRes->assertStatus(200);
        $this->assertEquals('application/pdf', $downloadRes->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $downloadRes->headers->get('content-disposition'));
        $this->assertStringContainsString('laporan-hasil-mingguan-2026-w47', (string) $downloadRes->headers->get('content-disposition'));

        // Empty period Bulanan tetap menghasilkan PDF valid 200 OK
        $emptyRes = $this->actingAs($admin)->get('/admin/pengaturan/report/pdf?periode=bulanan&bulan=2026-05');
        $emptyRes->assertStatus(200);
        $this->assertEquals('application/pdf', $emptyRes->headers->get('content-type'));
    }
}
