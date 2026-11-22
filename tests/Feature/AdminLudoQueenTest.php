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

    public function test_guest_cannot_access_admin_ludo_queen_and_public_navbar_hides_it(): void
    {
        Liga::current();

        $this->get('/admin/pengaturan/ludo-queen')->assertRedirect('/login');
        $this->get('/admin/ludo-queen')->assertRedirect('/login');

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertDontSee('/admin/pengaturan/ludo-queen');
        $home->assertDontSee('Game Ludo Queen');
    }

    public function test_admin_can_access_ludo_queen_via_pengaturan_and_it_is_excluded_from_analytics(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/ludo-queen')
            ->assertRedirect(route('admin.pengaturan.ludo_queen'));

        $res = $this->actingAs($admin)->get('/admin/pengaturan/ludo-queen');
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
        $res->assertSee(route('admin.pengaturan.ludo_queen'));

        // Area admin tidak tercatat ke visitor_logs analitik publik
        $this->assertEquals(0, VisitorLog::where('path', 'like', '%ludo-queen%')->count());
    }
}
