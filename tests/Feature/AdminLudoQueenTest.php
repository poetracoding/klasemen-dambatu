<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLudoQueenTest extends TestCase
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

    public function test_public_can_access_ludo_queen_and_navbar_dropdown_includes_it(): void
    {
        Liga::current();

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('Ludo Queen 3D');
        $home->assertSee(route('game.ludo_queen'));

        $res = $this->get('/game/ludo-queen');
        $res->assertStatus(200);
        $res->assertSee('Game Ludo Queen');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('ludoCanvas');
        $res->assertSee('ludoBoard3dSurface');
        $res->assertSee('ludoCenterDiceOverlay');
        $res->assertSee('btnLudoCycleCamera');
        $res->assertSee('3D Meja');
        $res->assertSee('ludo-queen-game.js');
        $res->assertSee('DISABLE_LIVE_UPDATE');

        $this->assertEquals(1, VisitorLog::where('path', '/game/ludo-queen')->count());
    }

    public function test_old_admin_ludo_queen_routes_redirect_to_public(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/pengaturan/ludo-queen')
            ->assertRedirect(route('game.ludo_queen'));

        $this->actingAs($admin)
            ->get('/admin/ludo-queen')
            ->assertRedirect(route('game.ludo_queen'));
    }
}
