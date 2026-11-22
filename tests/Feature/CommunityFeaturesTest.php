<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\PertandinganCheer;
use App\Models\PertandinganVote;
use App\Models\Tim;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_games_hub_page_renders_all_five_games_and_is_tracked(): void
    {
        Liga::current();

        $res = $this->get('/games');
        $res->assertStatus(200);
        $res->assertSee('Arena Game &amp; Hiburan Meja 3D', false);
        $res->assertSee('Game Dam Batu 3D');
        $res->assertSee('Monopoli Nusantara 3D');
        $res->assertSee('Ludo Queen 3D');
        $res->assertSee('Game Catur 3D');
        $res->assertSee('Game Flappy Bird');

        $this->assertEquals(1, VisitorLog::where('path', '/games')->count());
    }

    public function test_visitor_can_vote_and_cheer_on_match_detail(): void
    {
        $liga = Liga::create(['nama_liga' => 'Liga Komunitas', 'musim' => '2026', 'status' => 'berjalan']);
        $t1 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Elang Batu', 'pemain1' => 'Adi', 'pemain2' => 'Budi', 'status' => 1]);
        $t2 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Macan Meja', 'pemain1' => 'Cici', 'pemain2' => 'Dodi', 'status' => 1]);

        $match = Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 1,
            'tanggal' => now()->toDateString(),
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'status' => 'belum_main',
        ]);

        // Halaman detail menampilkan polling & sorakan
        $detail = $this->get('/pertandingan/' . $match->id);
        $detail->assertStatus(200);
        $detail->assertSee('Polling Tebak Pemenang');
        $detail->assertSee('Sorakan Penonton');

        // Vote home
        $this->postJson('/pertandingan/' . $match->id . '/vote', ['choice' => 'home'])
            ->assertStatus(200)
            ->assertJsonPath('summary.home', 1)
            ->assertJsonPath('summary.away', 0)
            ->assertJsonPath('summary.user_choice', 'home');

        // Switch vote ke away (deduplicated, tetap 1 row)
        $this->postJson('/pertandingan/' . $match->id . '/vote', ['choice' => 'away'])
            ->assertStatus(200)
            ->assertJsonPath('summary.home', 0)
            ->assertJsonPath('summary.away', 1)
            ->assertJsonPath('summary.user_choice', 'away');
        $this->assertEquals(1, PertandinganVote::count());

        // Cheer fire -> switch trophy -> invalid rejected
        $this->postJson('/pertandingan/' . $match->id . '/cheer', ['reaction' => 'fire'])
            ->assertStatus(200)
            ->assertJsonPath('summary.counts.fire', 1)
            ->assertJsonPath('summary.user_reaction', 'fire');

        $this->postJson('/pertandingan/' . $match->id . '/cheer', ['reaction' => 'trophy'])
            ->assertStatus(200)
            ->assertJsonPath('summary.counts.fire', 0)
            ->assertJsonPath('summary.counts.trophy', 1);
        $this->assertEquals(1, PertandinganCheer::count());

        $this->postJson('/pertandingan/' . $match->id . '/cheer', ['reaction' => 'invalid'])
            ->assertStatus(422);

        // Setelah laga selesai, voting baru ditolak
        $match->update(['status' => 'selesai', 'skor_home' => 30, 'skor_away' => 20, 'pemenang_id' => $t1->id]);
        $this->postJson('/pertandingan/' . $match->id . '/vote', ['choice' => 'home'])
            ->assertStatus(422);
    }

    public function test_dashboard_mvp_share_whatsapp_and_klasemen_streak_and_champion_confetti(): void
    {
        $liga = Liga::create(['nama_liga' => 'Liga Juara', 'musim' => '2026', 'status' => 'selesai']);
        $t1 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Garuda Jaya', 'pemain1' => 'Eko', 'pemain2' => 'Fajar', 'status' => 1]);
        $t2 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Singa Barat', 'pemain1' => 'Gilang', 'pemain2' => 'Hadi', 'status' => 1]);
        $t3 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Rajawali Tim', 'pemain1' => 'Irfan', 'pemain2' => 'Joko', 'status' => 1]);

        Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 1,
            'nomor_pertandingan' => 1,
            'tanggal' => now()->toDateString(),
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'skor_home' => 31,
            'skor_away' => 20,
            'pemenang_id' => $t1->id,
            'status' => 'selesai',
        ]);
        Pertandingan::create([
            'liga_id' => $liga->id,
            'ronde' => 2,
            'nomor_pertandingan' => 2,
            'tanggal' => now()->toDateString(),
            'waktu' => '19:30:00',
            'lokasi' => 'Meja 2',
            'tim_home' => $t1->id,
            'tim_away' => $t3->id,
            'skor_home' => 30,
            'skor_away' => 22,
            'pemenang_id' => $t1->id,
            'status' => 'selesai',
        ]);

        $dash = $this->get('/');
        $dash->assertStatus(200);
        $dash->assertSee('Sorotan MVP &amp; Rekor Turnamen', false);
        $dash->assertSee('Bagikan ke WhatsApp');
        $dash->assertSee('championConfettiCanvas');

        $kl = $this->get('/klasemen');
        $kl->assertStatus(200);
        $kl->assertSee('2M');
        $kl->assertSee('championConfettiCanvas');
    }
}
