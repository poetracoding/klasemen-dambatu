<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCaturTest extends TestCase
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

    public function test_public_can_access_catur_and_navbar_dropdown_includes_it(): void
    {
        Liga::current();

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('Catur 3D');
        $home->assertSee(route('game.catur'));

        $res = $this->get('/game/catur');
        $res->assertStatus(200);
        $res->assertSee('Game Catur 3D');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('chessCanvas');
        $res->assertSee('chessBoard3dSurface');
        $res->assertSee('3D Meja');
        $res->assertSee('catur-game.js');
        $res->assertSee('DISABLE_LIVE_UPDATE');

        $this->assertEquals(1, VisitorLog::where('path', '/game/catur')->count());
    }

    public function test_old_admin_catur_routes_redirect_to_public(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/pengaturan/catur')
            ->assertRedirect(route('game.catur'));

        $this->actingAs($admin)
            ->get('/admin/catur')
            ->assertRedirect(route('game.catur'));
    }
}
