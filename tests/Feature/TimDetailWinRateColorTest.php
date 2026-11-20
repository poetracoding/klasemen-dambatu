<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimDetailWinRateColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_win_rate_card_color_reflects_percentage_thresholds(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026',
            'status' => 'berjalan',
            'jumlah_meja' => 2,
        ]);

        $timMerah = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM MERAH', 'pemain1' => 'A1', 'pemain2' => 'A2', 'status' => 1]);
        $timOrange = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM ORANGE', 'pemain1' => 'B1', 'pemain2' => 'B2', 'status' => 1]);
        $timHijau = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM HIJAU', 'pemain1' => 'C1', 'pemain2' => 'C2', 'status' => 1]);
        $timLawan = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'TIM LAWAN', 'pemain1' => 'D1', 'pemain2' => 'D2', 'status' => 1]);

        // TIM MERAH: 0 menang, 1 kalah (0% < 50%)
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 1, 'ronde' => 1,
            'tim_home' => $timMerah->id, 'tim_away' => $timLawan->id,
            'status' => 'selesai', 'skor_home' => 26, 'skor_away' => 34, 'pemenang_id' => $timLawan->id,
        ]);

        // TIM ORANGE: 1 menang, 1 kalah (50%)
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 2, 'ronde' => 1,
            'tim_home' => $timOrange->id, 'tim_away' => $timLawan->id,
            'status' => 'selesai', 'skor_home' => 40, 'skor_away' => 20, 'pemenang_id' => $timOrange->id,
        ]);
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 3, 'ronde' => 2,
            'tim_home' => $timOrange->id, 'tim_away' => $timHijau->id,
            'status' => 'selesai', 'skor_home' => 15, 'skor_away' => 30, 'pemenang_id' => $timHijau->id,
        ]);

        // TIM HIJAU: 2 menang, 0 kalah (100% > 50%)
        Pertandingan::create([
            'liga_id' => $liga->id, 'nomor_pertandingan' => 4, 'ronde' => 2,
            'tim_home' => $timHijau->id, 'tim_away' => $timLawan->id,
            'status' => 'selesai', 'skor_home' => 35, 'skor_away' => 10, 'pemenang_id' => $timHijau->id,
        ]);

        // 0% -> Merah (rose)
        $resMerah = $this->get('/tim/' . $timMerah->id);
        $resMerah->assertStatus(200);
        $resMerah->assertSee('0% Win Rate');
        $resMerah->assertSee('bg-rose-50/60', false);
        $resMerah->assertSee('text-rose-700', false);

        // 50% -> Orange
        $resOrange = $this->get('/tim/' . $timOrange->id);
        $resOrange->assertStatus(200);
        $resOrange->assertSee('50% Win Rate');
        $resOrange->assertSee('bg-orange-50/60', false);
        $resOrange->assertSee('text-orange-700', false);

        // 100% -> Hijau (emerald)
        $resHijau = $this->get('/tim/' . $timHijau->id);
        $resHijau->assertStatus(200);
        $resHijau->assertSee('100% Win Rate');
        $resHijau->assertSee('bg-emerald-50/50', false);
        $resHijau->assertSee('text-emerald-700', false);
    }
}
