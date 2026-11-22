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

    public function test_public_can_access_monopoli_and_navbar_dropdown_lists_all_games(): void
    {
        Liga::current();

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('publicGameDropdownMenu');
        $home->assertSee('Game Dam Batu');
        $home->assertSee('Monopoli Nusantara');
        $home->assertSee(route('game'));
        $home->assertSee(route('game.monopoli'));

        $res = $this->get('/game/monopoli');
        $res->assertStatus(200);
        $res->assertSee('Monopoli Nusantara');
        $res->assertSee('Arena Hiburan Publik');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('Juragan');
        $res->assertSee('10% Kekayaan');
        $res->assertSee('DISABLE_LIVE_UPDATE');
        $res->assertSee('monopoli-3d.css');
        $res->assertSee('monopoli-game.js');

        // Halaman publik tercatat ke visitor_logs analitik
        $this->assertEquals(1, VisitorLog::where('path', '/game/monopoli')->count());
    }

    public function test_old_admin_monopoli_routes_redirect_to_public_and_admin_dropdown_excludes_games(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/pengaturan/monopoli')
            ->assertRedirect(route('game.monopoli'));

        $this->actingAs($admin)
            ->get('/admin/monopoli')
            ->assertRedirect(route('game.monopoli'));

        $adminDash = $this->actingAs($admin)->get('/admin');
        $adminDash->assertStatus(200);
        $adminDash->assertDontSee('Mini Games Admin');
    }
}
