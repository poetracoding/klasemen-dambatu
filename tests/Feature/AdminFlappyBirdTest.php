<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlappyBirdTest extends TestCase
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

    public function test_guest_cannot_access_admin_flappy_bird_and_public_navbar_hides_it(): void
    {
        Liga::current();

        $this->get('/admin/pengaturan/flappy-bird')->assertRedirect('/login');
        $this->get('/admin/flappy-bird')->assertRedirect('/login');

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertDontSee('/admin/pengaturan/flappy-bird');
        $home->assertDontSee('Game Flappy Bird');
    }

    public function test_admin_can_access_flappy_bird_via_pengaturan_and_it_is_excluded_from_analytics(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/flappy-bird')
            ->assertRedirect(route('admin.pengaturan.flappy_bird'));

        $res = $this->actingAs($admin)->get('/admin/pengaturan/flappy-bird');
        $res->assertStatus(200);
        $res->assertSee('Game Flappy Bird');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('flappyCanvas');
        $res->assertSee('flappy-bird-game.js');
        $res->assertSee('DISABLE_LIVE_UPDATE');
        $res->assertSee(route('admin.pengaturan.flappy_bird'));

        // Area admin tidak tercatat ke visitor_logs analitik publik
        $this->assertEquals(0, VisitorLog::where('path', 'like', '%flappy-bird%')->count());
    }
}
