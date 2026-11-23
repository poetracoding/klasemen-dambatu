<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UxGameAndPdfEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private function seedLigaWithTwoTeams(): array
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026/2027',
            'status' => 'berlangsung',
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
            'pemain1' => 'Julfi',
            'pemain2' => 'Eko',
            'status' => 1,
        ]);

        $match = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 1,
            'tanggal' => now()->toDateString(),
            'waktu' => '17:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'skor_home' => 50,
            'skor_away' => 35,
            'pemenang_id' => $t1->id,
            'status' => 'selesai',
        ]);

        return [$liga, $t1, $t2, $match];
    }

    public function test_pdf_klasemen_pts_header_has_high_contrast_and_generates_pdf(): void
    {
        $this->seedLigaWithTwoTeams();

        $html = view('pdf.klasemen', [
            'liga' => Liga::current(),
            'klasemen' => \App\Services\TournamentService::hitungKlasemen(Liga::current()),
            'totalTim' => 2,
            'totalMatch' => 1,
            'totalSelesai' => 1,
        ])->render();

        $this->assertStringContainsString('.data-table th.pts-col', $html);
        $this->assertStringContainsString('background-color: #312e81 !important;', $html);
        $this->assertStringContainsString('color: #ffffff !important;', $html);

        $res = $this->get('/cetak/klasemen');
        $res->assertStatus(200);
        $res->assertHeader('content-type', 'application/pdf');
    }

    public function test_klasemen_dark_mode_leader_row_and_mobile_bottom_nav_render_cleanly(): void
    {
        $this->seedLigaWithTwoTeams();

        $res = $this->get('/klasemen');
        $res->assertStatus(200);
        $res->assertSee('dark:bg-amber-500/15', false);
        $res->assertSee('dark:text-amber-300', false);
        $res->assertSee('mobile-bottom-nav', false);
        $res->assertSee(route('perbandingan'), false);
    }

    public function test_head_to_head_perbandingan_works_safely_and_validates_inputs(): void
    {
        [$liga, $t1, $t2] = $this->seedLigaWithTwoTeams();

        // Valid comparison
        $res = $this->get('/perbandingan?tim1=' . $t1->id . '&tim2=' . $t2->id);
        $res->assertStatus(200);
        $res->assertSee('Perbandingan Tim');
        $res->assertSee('SAYA AKAN LAWAN');
        $res->assertSee('KUDA BINAL');
        $res->assertSee('Perkiraan Peluang Menang');
        $res->assertSee('Riwayat Pertemuan Langsung');

        // Same team shows warning gracefully
        $sameRes = $this->get('/perbandingan?tim1=' . $t1->id . '&tim2=' . $t1->id);
        $sameRes->assertStatus(200);
        $sameRes->assertSee('Pilih dua tim yang berbeda');

        // Invalid / non-existent IDs do not cause 500 or leak data
        $invalidRes = $this->get('/perbandingan?tim1=999999&tim2=invalid');
        $invalidRes->assertStatus(200);
    }

    public function test_games_hub_personal_stats_and_all_five_games_fullscreen_exit_controls(): void
    {
        $this->seedLigaWithTwoTeams();

        $hub = $this->get('/games');
        $hub->assertStatus(200);
        $hub->assertSee('Statistik Personal &amp; Lencana Mini-Game', false);
        $hub->assertSee('id="statBoardWins"', false);
        $hub->assertSee('id="arcadeBadgesContainer"', false);
        $hub->assertSee('rel="noopener noreferrer"', false);

        foreach (['/game', '/game/monopoli', '/game/flappy-bird', '/game/ludo-queen', '/game/catur'] as $url) {
            $res = $this->get($url);
            $res->assertStatus(200);
            $res->assertSee('game-fs-exit-btn', false);
            $res->assertSee('game-touch-arena', false);
        }
    }

    public function test_freshly_updated_match_shows_baru_indicator(): void
    {
        $this->seedLigaWithTwoTeams();

        $res = $this->get('/history');
        $res->assertStatus(200);
        $res->assertSee('Skor diperbarui dalam 24 jam terakhir', false);
    }
}
