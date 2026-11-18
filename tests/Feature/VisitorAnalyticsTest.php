<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorAnalyticsTest extends TestCase
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

    public function test_public_pages_are_tracked_while_admin_and_api_are_excluded(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        // Kunjungan desktop ke halaman publik
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
            ->get('/')
            ->assertStatus(200);

        // Kunjungan mobile ke klasemen
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Linux; Android 13; Mobile)'])
            ->get('/klasemen')
            ->assertStatus(200);

        // Request API polling & admin tidak boleh tercatat
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0)'])
            ->get('/api/check-update')
            ->assertStatus(200);

        $this->actingAs($admin)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0)'])
            ->get('/admin/pengaturan/analytics')
            ->assertStatus(200);

        $this->assertEquals(2, VisitorLog::count());
        $this->assertEquals(1, VisitorLog::where('path', '/')->where('device_type', 'desktop')->count());
        $this->assertEquals(1, VisitorLog::where('path', '/klasemen')->where('device_type', 'mobile')->count());
    }

    public function test_admin_analytics_page_and_pengaturan_dropdown_and_reset(): void
    {
        Liga::current();
        $admin = $this->createAdmin();

        // Simulasikan kunjungan
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0)'])->get('/')->assertStatus(200);
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)'])->get('/jadwal')->assertStatus(200);

        $res = $this->actingAs($admin)->get('/admin/pengaturan/analytics');
        $res->assertStatus(200);
        $res->assertSee('Analytics Pengunjung');
        $res->assertSee('Dashboard Publik');
        $res->assertSee('Jadwal Liga');

        // Verifikasi dropdown Pengaturan pada topbar admin memiliki semua submenu
        $res->assertSee('pengaturanDropdownMenu');
        $res->assertSee('Informasi Liga');
        $res->assertSee('Poster Informasi');
        $res->assertSee('Ganti Password');
        $res->assertSee('Backup &amp; Restore', false);
        $res->assertSee('Analytics');

        // Verifikasi halaman Pengaturan terpisah sesuai menunya
        $this->actingAs($admin)->get('/admin/pengaturan')->assertStatus(200)->assertSee('Informasi Liga')->assertDontSee('Backup Data Turnamen');
        $this->actingAs($admin)->get('/admin/pengaturan/password')->assertStatus(200)->assertSee('Ganti Password Admin')->assertDontSee('Backup Data Turnamen');
        $this->actingAs($admin)->get('/admin/pengaturan/backup-restore')->assertStatus(200)->assertSee('Backup &amp; Restore Database', false)->assertDontSee('Ganti Password Admin');

        // Reset analytics
        $this->actingAs($admin)->post('/admin/pengaturan/analytics/reset')
            ->assertSessionHas('success');
        $this->assertEquals(0, VisitorLog::count());
    }
}
