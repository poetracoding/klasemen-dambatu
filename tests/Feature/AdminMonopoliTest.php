<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMonopoliTest extends TestCase
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

    public function test_guest_cannot_access_admin_monopoli_and_public_navbar_hides_it(): void
    {
        Liga::current();

        $this->get('/admin/pengaturan/monopoli')->assertRedirect('/login');
        $this->get('/admin/monopoli')->assertRedirect('/login');

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertDontSee('/admin/pengaturan/monopoli');
        $home->assertDontSee('Game Monopoli');
    }

    public function test_admin_can_access_monopoli_via_pengaturan_and_it_is_excluded_from_analytics(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/monopoli')
            ->assertRedirect(route('admin.pengaturan.monopoli'));

        $res = $this->actingAs($admin)->get('/admin/pengaturan/monopoli');
        $res->assertStatus(200);
        $res->assertSee('Game Monopoli');
        $res->assertSee('Monopoli Nusantara');
        $res->assertSee('Khusus Admin');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('Juragan');
        $res->assertSee('10% Kekayaan');
        $res->assertSee('DISABLE_LIVE_UPDATE');
        $res->assertSee('monopoli-3d.css');
        $res->assertSee('monopoli-game.js');
        $res->assertSee(route('admin.pengaturan.monopoli'));

        // Area admin tidak tercatat ke visitor_logs analitik publik
        $this->assertEquals(0, VisitorLog::where('path', 'like', '%monopoli%')->count());
    }
}
