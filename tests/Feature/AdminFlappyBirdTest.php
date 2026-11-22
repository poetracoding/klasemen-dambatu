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

    public function test_public_can_access_flappy_bird_and_navbar_dropdown_includes_it(): void
    {
        Liga::current();

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('Flappy Bird');
        $home->assertSee(route('game.flappy_bird'));

        $res = $this->get('/game/flappy-bird');
        $res->assertStatus(200);
        $res->assertSee('Game Flappy Bird');
        $res->assertSee('Santai');
        $res->assertSee('Normal');
        $res->assertSee('Sulit');
        $res->assertSee('flappyCanvas');
        $res->assertSee('flappy-bird-game.js');
        $res->assertSee('DISABLE_LIVE_UPDATE');

        $this->assertEquals(1, VisitorLog::where('path', '/game/flappy-bird')->count());
    }

    public function test_old_admin_flappy_bird_routes_redirect_to_public(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/pengaturan/flappy-bird')
            ->assertRedirect(route('game.flappy_bird'));

        $this->actingAs($admin)
            ->get('/admin/flappy-bird')
            ->assertRedirect(route('game.flappy_bird'));
    }
}
