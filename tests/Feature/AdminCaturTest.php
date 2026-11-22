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

    public function test_guest_cannot_access_admin_catur_and_public_navbar_hides_it(): void
    {
        Liga::current();

        $this->get('/admin/pengaturan/catur')->assertRedirect('/login');
        $this->get('/admin/catur')->assertRedirect('/login');

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertDontSee('/admin/pengaturan/catur');
        $home->assertDontSee('Game Catur 3D');
    }

    public function test_admin_can_access_catur_via_pengaturan_and_it_is_excluded_from_analytics(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/catur')
            ->assertRedirect(route('admin.pengaturan.catur'));

        $res = $this->actingAs($admin)->get('/admin/pengaturan/catur');
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
        $res->assertSee(route('admin.pengaturan.catur'));

        // Area admin tidak tercatat ke visitor_logs analitik publik
        $this->assertEquals(0, VisitorLog::where('path', 'like', '%catur%')->count());
    }
}
