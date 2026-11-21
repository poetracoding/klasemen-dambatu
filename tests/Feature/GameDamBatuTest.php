<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameDamBatuTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_dam_batu_page_renders_and_is_tracked_in_analytics(): void
    {
        Liga::current();

        // Navbar publik memuat tautan ke halaman /game
        $this->get('/')->assertStatus(200)->assertSee(route('game'));

        // Halaman /game tampil lengkap dengan 2 mode (2 AI & 3 AI) dan 3 level kesulitan
        $res = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)'])
            ->get('/game');

        $res->assertStatus(200);
        $res->assertSee('Game Dam Batu');
        $res->assertSee('3 Orang (2 AI)');
        $res->assertSee('4 Orang (3 AI)');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('dam-batu-game.js');
        $res->assertSee('DISABLE_LIVE_UPDATE');
        $res->assertSee('btnToggleFullscreen');
        $res->assertSee('Perluas Meja');
        $res->assertSee('gameTableCard');
        $res->assertSee('btnStartGame');
        $res->assertSee('btnRestartGame');
        $res->assertSee('startOverlay');
        $res->assertSee('shuffleOverlay');
        $res->assertSee('anim-place-left');

        // Cek elemen UI/UX 3D baru pada halaman /game
        $res->assertSee('dam-batu-3d.css');
        $res->assertSee('table3dStage');
        $res->assertSee('btnCycleCamera');
        $res->assertSee('data-felt-theme="emerald"', false);
        $res->assertSee('data-felt-theme="royal"', false);
        $res->assertSee('data-felt-theme="classic"', false);
        $res->assertSee('hiddenDeckVisual');
        $res->assertSee('btnZoomIn');
        $res->assertSee('btnZoomOut');
        $res->assertSee('btnToggleHistory');

        // Tercatat di visitor_logs sebagai 'Game Dam Batu' dengan perangkat mobile
        $this->assertEquals(1, VisitorLog::where('path', '/game')->where('page_title', 'Game Dam Batu')->where('device_type', 'mobile')->count());

        // Tampil di halaman Analytics Panel Admin
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
        $analyticsRes = $this->actingAs($admin)->get('/admin/pengaturan/analytics');
        $analyticsRes->assertStatus(200);
        $analyticsRes->assertSee('Game Dam Batu');
        $analyticsRes->assertSee('/game');
    }
}
