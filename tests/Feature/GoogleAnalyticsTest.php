<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAnalyticsTest extends TestCase
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

    public function test_admin_can_configure_google_analytics_and_script_renders_only_on_public_pages(): void
    {
        $admin = $this->createAdmin();
        $liga = Liga::current();

        // Awalnya kosong -> script GA tidak boleh tampil di halaman publik
        $this->get('/')->assertStatus(200)->assertDontSee('googletagmanager.com/gtag/js', false);

        // Simpan GA4 ID melalui Pengaturan Admin (huruf kecil otomatis jadi huruf besar)
        $resSave = $this->actingAs($admin)->post('/admin/pengaturan/liga', [
            'nama_liga' => $liga->nama_liga,
            'musim' => $liga->musim,
            'google_analytics_id' => 'g-test123abc',
        ]);
        $resSave->assertRedirect();
        $resSave->assertSessionHas('success');

        $this->assertEquals('G-TEST123ABC', $liga->fresh()->google_analytics_id);

        // Halaman publik sekarang merender script GA4
        $resPublic = $this->get('/');
        $resPublic->assertStatus(200);
        $resPublic->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123ABC', false);
        $resPublic->assertSee('gtag(\'config\', "G-TEST123ABC")', false);

        // Panel admin TIDAK boleh merender script GA4
        $resAdmin = $this->actingAs($admin)->get('/admin');
        $resAdmin->assertStatus(200);
        $resAdmin->assertDontSee('googletagmanager.com/gtag/js', false);

        // Kosongkan kembali -> script GA nonaktif
        $this->actingAs($admin)->post('/admin/pengaturan/liga', [
            'nama_liga' => $liga->nama_liga,
            'musim' => $liga->musim,
            'google_analytics_id' => '',
        ])->assertSessionHas('success');

        $this->assertNull($liga->fresh()->google_analytics_id);
        $this->get('/')->assertStatus(200)->assertDontSee('googletagmanager.com/gtag/js', false);
    }

    public function test_invalid_google_analytics_id_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $liga = Liga::current();

        $res = $this->actingAs($admin)->post('/admin/pengaturan/liga', [
            'nama_liga' => $liga->nama_liga,
            'musim' => $liga->musim,
            'google_analytics_id' => 'INVALID_CODE',
        ]);

        $res->assertSessionHasErrors('google_analytics_id');
    }
}
