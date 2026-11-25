<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppConfigAndTentangTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_public_tentang_page_renders_and_footer_links_to_it(): void
    {
        Liga::current();

        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee(route('tentang'), false);

        $res = $this->get('/tentang');
        $res->assertStatus(200);
        $res->assertSee('Tentang Aplikasi');
        $res->assertSee('Profil &amp; Tujuan Aplikasi', false);
        $res->assertSee('Aturan Kompetisi');
    }

    public function test_admin_can_upload_app_icon_and_update_app_config_and_remove_icon(): void
    {
        $admin = $this->createAdmin();
        $liga = Liga::current();

        // 1x1 valid PNG binary
        $pngBinary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Z0WQAAAAASUVORK5CYII=');
        $iconFile = UploadedFile::fake()->createWithContent('icon.png', $pngBinary);

        $res = $this->actingAs($admin)->post('/admin/pengaturan/liga', [
            'nama_liga' => 'Liga Dam Batu Pro',
            'musim' => '2026/2027',
            'app_icon' => $iconFile,
            'app_tagline' => 'Portal Turnamen Resmi',
            'theme_color' => '#10b981',
            'footer_text' => 'Footer Kustom Liga',
            'kontak_panitia' => 'WA 0812-0000-0000',
            'tentang_aplikasi' => 'Deskripsi resmi <script>alert(1)</script> liga.',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $liga->refresh();
        $this->assertNotNull($liga->app_icon);
        $this->assertStringStartsWith('uploads/app/app-icon-', $liga->app_icon);
        $uploadedFullPath = public_path($liga->app_icon);
        $this->assertFileExists($uploadedFullPath);

        try {
            // Public page shows custom icon in <head> & custom footer
            $publicRes = $this->get('/');
            $publicRes->assertStatus(200);
            $publicRes->assertSee('<link rel="icon" href="' . $liga->appIconUrl() . '"', false);
            $publicRes->assertSee('<meta name="theme-color" content="#10b981">', false);
            $publicRes->assertSee('Footer Kustom Liga');

            // Manifest reflects custom theme_color & icon
            $manifestRes = $this->get(route('fcm.manifest'));
            $manifestRes->assertStatus(200);
            $manifestRes->assertJsonPath('theme_color', '#10b981');
            $manifestRes->assertJsonPath('icons.0.src', $liga->appIconUrl());

            // Tentang page renders escaped content (anti-XSS) and kontak panitia
            $tentangRes = $this->get('/tentang');
            $tentangRes->assertStatus(200);
            $tentangRes->assertSee('Portal Turnamen Resmi');
            $tentangRes->assertSee('WA 0812-0000-0000');
            $tentangRes->assertDontSee('<script>alert(1)</script>', false);
            $tentangRes->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);

            // Remove custom icon safely
            $removeRes = $this->actingAs($admin)->post('/admin/pengaturan/liga', [
                'nama_liga' => 'Liga Dam Batu Pro',
                'musim' => '2026/2027',
                'hapus_app_icon' => '1',
            ]);
            $removeRes->assertRedirect();
            $liga->refresh();
            $this->assertNull($liga->app_icon);
            $this->assertFileDoesNotExist($uploadedFullPath);
        } finally {
            if (is_file($uploadedFullPath)) {
                @unlink($uploadedFullPath);
            }
        }
    }

    public function test_app_icon_rejects_svg_and_invalid_theme_color(): void
    {
        $admin = $this->createAdmin();
        Liga::current();

        $svg = UploadedFile::fake()->createWithContent('evil.svg', '<svg onload="alert(1)"></svg>');
        $res = $this->actingAs($admin)->post('/admin/pengaturan/liga', [
            'nama_liga' => 'Liga Dam Batu',
            'app_icon' => $svg,
            'theme_color' => 'red;background:url(javascript:alert(1))',
        ]);

        $res->assertSessionHasErrors(['app_icon', 'theme_color']);
    }
}
