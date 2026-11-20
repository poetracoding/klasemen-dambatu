<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrediksiDanTrenTimTest extends TestCase
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

    public function test_match_win_probability_prediction_and_admin_team_trend_analysis(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026',
            'status' => 'berjalan',
            'jumlah_meja' => 2,
        ]);

        $timDominan = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM DOMINAN', 'pemain1' => 'A1', 'pemain2' => 'A2', 'status' => 1]);
        $timRentan = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM RENTAN', 'pemain1' => 'B1', 'pemain2' => 'B2', 'status' => 1]);
        $timC = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM C', 'pemain1' => 'C1', 'pemain2' => 'C2', 'status' => 1]);
        $timD = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM D', 'pemain1' => 'D1', 'pemain2' => 'D2', 'status' => 1]);

        // Laga belum dimulai sebelum ada histori -> 50% vs 50%
        $upcoming = Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 10, 'ronde' => 4,
            'tim_home' => $timDominan->id, 'tim_away' => $timRentan->id,
            'status' => 'belum_main',
        ]);

        $initialPred = TournamentService::prediksiPertandingan($liga, $upcoming);
        $this->assertEquals(50, $initialPred['home_pct']);
        $this->assertEquals(50, $initialPred['away_pct']);
        $this->assertFalse($initialPred['has_data']);

        // Beri 3 kemenangan telak untuk TIM DOMINAN dan 3 kekalahan untuk TIM RENTAN
        $selesai1 = Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 1, 'ronde' => 1,
            'tim_home' => $timDominan->id, 'tim_away' => $timC->id,
            'status' => 'selesai', 'skor_home' => 45, 'skor_away' => 15, 'pemenang_id' => $timDominan->id,
        ]);
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 2, 'ronde' => 2,
            'tim_home' => $timDominan->id, 'tim_away' => $timD->id,
            'status' => 'selesai', 'skor_home' => 40, 'skor_away' => 20, 'pemenang_id' => $timDominan->id,
        ]);
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 3, 'ronde' => 3,
            'tim_home' => $timC->id, 'tim_away' => $timDominan->id,
            'status' => 'selesai', 'skor_home' => 10, 'skor_away' => 38, 'pemenang_id' => $timDominan->id,
        ]);

        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 4, 'ronde' => 1,
            'tim_home' => $timRentan->id, 'tim_away' => $timC->id,
            'status' => 'selesai', 'skor_home' => 12, 'skor_away' => 35, 'pemenang_id' => $timC->id,
        ]);
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 5, 'ronde' => 2,
            'tim_home' => $timRentan->id, 'tim_away' => $timD->id,
            'status' => 'selesai', 'skor_home' => 18, 'skor_away' => 32, 'pemenang_id' => $timD->id,
        ]);
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 6, 'ronde' => 3,
            'tim_home' => $timD->id, 'tim_away' => $timRentan->id,
            'status' => 'selesai', 'skor_home' => 34, 'skor_away' => 15, 'pemenang_id' => $timD->id,
        ]);

        $pred = TournamentService::prediksiPertandingan($liga, $upcoming);
        $this->assertTrue($pred['has_data']);
        $this->assertEquals('home', $pred['favored']);
        $this->assertGreaterThan(60, $pred['home_pct']);
        $this->assertEquals(100, $pred['home_pct'] + $pred['away_pct']);

        // Detail pertandingan belum_main menampilkan perkiraan peluang & disclaimer
        $resUpcoming = $this->get('/pertandingan/' . $upcoming->id);
        $resUpcoming->assertStatus(200);
        $resUpcoming->assertSee('Perkiraan Peluang Kemenangan');
        $resUpcoming->assertSee($pred['home_pct'] . '%');
        $resUpcoming->assertSee('Kemenangan sesungguhnya tetap ditentukan di meja pertandingan');

        // Detail pertandingan selesai TIDAK menampilkan perkiraan peluang
        $resFinished = $this->get('/pertandingan/' . $selesai1->id);
        $resFinished->assertStatus(200);
        $resFinished->assertDontSee('Perkiraan Peluang Kemenangan');

        // Analisis tren tim di Admin
        $tren = collect(TournamentService::analisisTrenTim($liga))->keyBy('id');
        $this->assertEquals('dominant', $tren[$timDominan->id]['trend_key']);
        $this->assertEquals('vulnerable', $tren[$timRentan->id]['trend_key']);
        $this->assertEquals(1, $tren[$timDominan->id]['projected_wins']);
        $this->assertEquals(1, $tren[$timRentan->id]['projected_losses']);

        $admin = $this->createAdmin();
        $resTrenPage = $this->actingAs($admin)->get('/admin/tren-tim');
        $resTrenPage->assertStatus(200);
        $resTrenPage->assertSee('Analisis &amp; Tren Tim', false);
        $resTrenPage->assertSee('Tren Menang Kuat');
        $resTrenPage->assertSee('Tren Kalah (Rentan)');
        $resTrenPage->assertSee('Kemenangan sesungguhnya tetap ditentukan di meja pertandingan');

        $resAdminDash = $this->actingAs($admin)->get('/admin');
        $resAdminDash->assertStatus(200);
        $resAdminDash->assertSee('Ringkasan Tren &amp; Proyeksi Tim', false);
    }

    public function test_recent_form_and_streak_are_ordered_by_updated_at_instead_of_scheduled_date(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Lompat Jadwal',
            'musim' => '2026',
            'status' => 'berjalan',
            'jumlah_meja' => 2,
        ]);

        $timA = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM A', 'pemain1' => 'P1', 'pemain2' => 'P2', 'status' => 1]);
        $timB = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM B', 'pemain1' => 'P3', 'pemain2' => 'P4', 'status' => 1]);
        $timC = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM C', 'pemain1' => 'P5', 'pemain2' => 'P6', 'status' => 1]);

        // Laga jadwal lebih akhir (20 Nov), tapi dimainkan & diupdate LEBIH DULU (kalah)
        $m1 = Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 1, 'ronde' => 1,
            'tim_home' => $timA->id, 'tim_away' => $timB->id,
            'tanggal' => '2026-11-20',
            'status' => 'selesai', 'skor_home' => 10, 'skor_away' => 30, 'pemenang_id' => $timB->id,
        ]);
        Pertandingan::where('id', $m1->id)->update(['updated_at' => '2026-11-18 10:00:00']);

        // Laga jadwal lebih awal (10 Nov), tapi baru dimainkan & diupdate BELAKANGAN (menang)
        $m2 = Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 2, 'ronde' => 2,
            'tim_home' => $timA->id, 'tim_away' => $timC->id,
            'tanggal' => '2026-11-10',
            'status' => 'selesai', 'skor_home' => 35, 'skor_away' => 15, 'pemenang_id' => $timA->id,
        ]);
        Pertandingan::where('id', $m2->id)->update(['updated_at' => '2026-11-20 15:00:00']);

        $strength = TournamentService::hitungKekuatanTim($liga);

        // Urutan form harus ['L', 'W'] (terbaru W), dan streak harus 1W
        $this->assertEquals(['L', 'W'], $strength[$timA->id]['recent_form']);
        $this->assertEquals('W', $strength[$timA->id]['streak_type']);
        $this->assertEquals(1, $strength[$timA->id]['streak_count']);
    }
}
