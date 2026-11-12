<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KlasemenTiebreakerTest extends TestCase
{
    use RefreshDatabase;

    public function test_klasemen_tiebreaker_prioritizes_selisih_skor_over_head_to_head(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Uji Coba',
            'musim' => '2026',
            'status' => 'berjalan',
            'jumlah_meja' => 2,
        ]);

        // Tim A dan Tim B
        $timA = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'Tim A',
            'pemain1' => 'Pemain A1',
            'pemain2' => 'Pemain A2',
            'status' => 1,
        ]);

        $timB = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'Tim B',
            'pemain1' => 'Pemain B1',
            'pemain2' => 'Pemain B2',
            'status' => 1,
        ]);

        $timC = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'Tim C',
            'pemain1' => 'Pemain C1',
            'pemain2' => 'Pemain C2',
            'status' => 1,
        ]);

        // Pertandingan 1: Tim B mengalahkan Tim A (B menang Head-to-Head atas A)
        // Skor: Tim B (1) vs Tim A (0)
        Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 1,
            'ronde' => 1,
            'tim_home' => $timB->id,
            'tim_away' => $timA->id,
            'skor_home' => 1,
            'skor_away' => 0,
            'pemenang_id' => $timB->id,
            'status' => 'selesai',
            'tanggal' => '2026-11-10',
            'waktu' => '19:00:00',
        ]);

        // Pertandingan 2: Tim A mengalahkan Tim C dengan skor telak (3 - 0)
        // Tim A: 1 Menang, 1 Kalah, Poin 3, BM 3, BK 1 -> Selisih Skor +2
        Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 2,
            'ronde' => 2,
            'tim_home' => $timA->id,
            'tim_away' => $timC->id,
            'skor_home' => 3,
            'skor_away' => 0,
            'pemenang_id' => $timA->id,
            'status' => 'selesai',
            'tanggal' => '2026-11-11',
            'waktu' => '19:00:00',
        ]);

        // Pertandingan 3: Tim C mengalahkan Tim B (1 - 0)
        // Tim B: 1 Menang, 1 Kalah, Poin 3, BM 1, BK 1 -> Selisih Skor 0
        Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 3,
            'ronde' => 3,
            'tim_home' => $timC->id,
            'tim_away' => $timB->id,
            'skor_home' => 1,
            'skor_away' => 0,
            'pemenang_id' => $timC->id,
            'status' => 'selesai',
            'tanggal' => '2026-11-12',
            'waktu' => '19:00:00',
        ]);

        // Hitung klasemen
        $klasemen = TournamentService::hitungKlasemen($liga);

        // Kedua tim (Tim A & Tim B) memiliki Poin sama (3) dan Menang sama (1).
        // Tim B menang H2H atas Tim A (1 - 0).
        // Tapi Tim A memiliki Selisih Skor lebih besar (+2 vs 0).
        // Dalam OPSI A (Selisih Skor Dahulu):
        // Tim A HARUS berada di peringkat 1, Tim B di peringkat 2.
        $this->assertEquals($timA->id, $klasemen[0]['id'], 'Tim A harus di peringkat 1 karena selisih skor lebih unggul');
        $this->assertEquals(1, $klasemen[0]['pos']);
        $this->assertEquals(2, $klasemen[0]['sb']);

        $this->assertEquals($timB->id, $klasemen[1]['id'], 'Tim B harus di peringkat 2');
        $this->assertEquals(2, $klasemen[1]['pos']);
        $this->assertEquals(0, $klasemen[1]['sb']);
    }
}
