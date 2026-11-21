<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Liga;
use App\Models\Poster;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BeritaTest extends TestCase
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

    protected function tearDown(): void
    {
        $dir = public_path('uploads/berita');
        if (is_dir($dir)) {
            foreach (glob($dir . '/berita_*') as $file) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    public function test_guest_cannot_access_admin_berita_routes(): void
    {
        $this->get('/admin/berita')->assertRedirect('/login');
        $this->post('/admin/berita', [])->assertRedirect('/login');
        $this->put('/admin/berita/1', [])->assertRedirect('/login');
        $this->post('/admin/berita/1/toggle')->assertRedirect('/login');
        $this->post('/admin/berita/1/publish-now')->assertRedirect('/login');
        $this->delete('/admin/berita/1')->assertRedirect('/login');
    }

    public function test_admin_can_crud_berita_with_unique_slug_and_safe_image_cleanup(): void
    {
        $admin = $this->createAdmin();
        Liga::current();

        $file = UploadedFile::fake()->image('cover.png', 800, 500);
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Hasil Pertandingan Pekan Pertama',
            'kategori' => 'Rekap Laga',
            'ringkasan' => 'Ringkasan singkat laga pembuka.',
            'isi' => "Baris pertama berita.\nBaris kedua berita.",
            'status' => 'published',
            'gambar' => $file,
        ])->assertSessionHas('success');

        $first = Berita::first();
        $this->assertNotNull($first);
        $this->assertEquals('hasil-pertandingan-pekan-pertama', $first->slug);
        $this->assertFileExists(public_path($first->gambar));

        // Judul kembar menghasilkan slug unik -2
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Hasil Pertandingan Pekan Pertama',
            'kategori' => 'Berita',
            'isi' => 'Artikel kedua dengan judul sama.',
            'status' => 'published',
        ])->assertSessionHas('success');

        $second = Berita::orderByDesc('id')->first();
        $this->assertEquals('hasil-pertandingan-pekan-pertama-2', $second->slug);

        // Update berita & toggle status
        $this->actingAs($admin)->put('/admin/berita/' . $second->id, [
            'judul' => 'Judul Berita Diperbarui',
            'kategori' => 'Liputan',
            'ringkasan' => 'Ringkasan baru.',
            'isi' => 'Konten baru.',
            'status' => 'published',
        ])->assertSessionHas('success');
        $this->assertEquals('judul-berita-diperbarui', $second->fresh()->slug);

        $this->actingAs($admin)->post('/admin/berita/' . $second->id . '/toggle')->assertSessionHas('success');
        $this->assertEquals('draft', $second->fresh()->status);

        // Hapus berita pertama -> file fisik ikut terhapus
        $imgPath = $first->gambar;
        $this->actingAs($admin)->delete('/admin/berita/' . $first->id)->assertSessionHas('success');
        $this->assertNull(Berita::find($first->id));
        $this->assertFileDoesNotExist(public_path($imgPath));
    }

    public function test_upload_rejects_php_and_svg_files_and_public_output_escapes_xss(): void
    {
        $admin = $this->createAdmin();
        Liga::current();

        // Tolak file .php
        $phpFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Tes Upload PHP',
            'kategori' => 'Berita',
            'isi' => 'Isi berita.',
            'status' => 'published',
            'gambar' => $phpFile,
        ])->assertSessionHasErrors('gambar');

        // Tolak file .svg (rawan XSS inline SVG)
        $svgFile = UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml');
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Tes Upload SVG',
            'kategori' => 'Berita',
            'isi' => 'Isi berita.',
            'status' => 'published',
            'gambar' => $svgFile,
        ])->assertSessionHasErrors('gambar');

        // Uji sanitasi XSS pada judul & isi berita
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Judul Aman <script>alert(1)</script>',
            'kategori' => 'Berita',
            'isi' => "Paragraf aman.\n<script>alert('xss')</script>",
            'status' => 'published',
        ])->assertSessionHas('success');

        $berita = Berita::orderByDesc('id')->first();
        $res = $this->get('/berita/' . $berita->slug);
        $res->assertStatus(200);
        $res->assertDontSee('<script>alert(1)</script>', false);
        $res->assertDontSee("<script>alert('xss')</script>", false);
        $res->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_draft_berita_returns_404_and_like_toggle_is_deduplicated_without_dislike(): void
    {
        $liga = Liga::current();
        Poster::query()->delete();
        Poster::create([
            'liga_id' => $liga->id,
            'judul' => 'Info Liga',
            'file_path' => 'peraturan4.png',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $published = Berita::create([
            'liga_id' => $liga->id,
            'judul' => 'Berita Publik Utama',
            'slug' => 'berita-publik-utama',
            'kategori' => 'Berita',
            'isi' => 'Konten berita publik yang aman.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $draft = Berita::create([
            'liga_id' => $liga->id,
            'judul' => 'Berita Draft Rahasia',
            'slug' => 'berita-draft-rahasia',
            'kategori' => 'Berita',
            'isi' => 'Belum boleh tampil.',
            'status' => 'draft',
        ]);

        // Dashboard menampilkan Card Informasi dan Card Berita berdampingan, tanpa berita draft
        $homeRes = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0)'])->get('/');
        $homeRes->assertStatus(200);
        $homeRes->assertSee('id="informasiSection"', false);
        $homeRes->assertSee('id="beritaSection"', false);
        $homeRes->assertSee('Berita Publik Utama');
        $homeRes->assertDontSee('Berita Draft Rahasia');

        // Halaman /berita & detail berita hanya menampilkan yang published dan tidak memiliki tombol dislike
        $listRes = $this->get('/berita');
        $listRes->assertStatus(200);
        $listRes->assertSee('Berita Publik Utama');
        $listRes->assertDontSee('Berita Draft Rahasia');

        $detailRes = $this->get('/berita/' . $published->slug);
        $detailRes->assertStatus(200);
        $detailRes->assertSee('Suka');
        $detailRes->assertDontSee('Dislike');
        $this->assertEquals(1, $published->fresh()->views_count);

        // Draft -> 404 di show maupun like
        $this->get('/berita/' . $draft->slug)->assertStatus(404);
        $this->postJson('/berita/' . $draft->slug . '/like')->assertStatus(404);

        // Toggle Like via AJAX JSON (0 -> 1 -> 0, tidak bisa lebih dari 1 per pengunjung)
        $likeRes1 = $this->postJson('/berita/' . $published->slug . '/like');
        $likeRes1->assertStatus(200)->assertJson([
            'success' => true,
            'liked' => true,
            'likes_count' => 1,
        ]);
        $this->assertEquals(1, $published->fresh()->likes_count);

        $likeRes2 = $this->postJson('/berita/' . $published->slug . '/like');
        $likeRes2->assertStatus(200)->assertJson([
            'success' => true,
            'liked' => false,
            'likes_count' => 0,
        ]);
        $this->assertEquals(0, $published->fresh()->likes_count);

        // Analytics mencatat /berita dan /berita/{slug}
        $this->assertEquals(1, VisitorLog::where('path', '/berita')->where('page_title', 'Berita Turnamen')->count());
        $this->assertEquals(1, VisitorLog::where('path', '/berita/' . $published->slug)->where('page_title', 'Baca Berita')->count());
    }

    public function test_scheduled_berita_stays_hidden_until_published_at_and_supports_publish_now(): void
    {
        $admin = $this->createAdmin();
        Liga::current();

        // Tolak status scheduled tanpa tanggal atau tanggal masa lalu
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Berita Terjadwal Tanpa Waktu',
            'kategori' => 'Berita',
            'isi' => 'Isi berita terjadwal.',
            'status' => 'scheduled',
        ])->assertSessionHasErrors('published_at');

        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Berita Terjadwal Masa Lalu',
            'kategori' => 'Berita',
            'isi' => 'Isi berita terjadwal.',
            'status' => 'scheduled',
            'published_at' => now()->subMinutes(10)->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('published_at');

        // Buat berita terjadwal +2 jam ke depan
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Berita Terjadwal Otomatis',
            'kategori' => 'Pengumuman',
            'ringkasan' => 'Ringkasan pengumuman terjadwal.',
            'isi' => 'Berita ini baru boleh tampil setelah jadwal tercapai.',
            'status' => 'scheduled',
            'published_at' => now()->addHours(2)->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');

        $scheduled = Berita::orderByDesc('id')->first();
        $this->assertNotNull($scheduled);
        $this->assertEquals('published', $scheduled->status);
        $this->assertTrue($scheduled->isScheduled());
        $this->assertFalse($scheduled->isLive());

        // Halaman admin menampilkan badge Terjadwal
        $this->actingAs($admin)->get('/admin/berita')
            ->assertStatus(200)
            ->assertSee('Terjadwal');

        $hashBefore = $this->getJson('/api/check-update')->json('hash');

        // Sebelum waktu terbit tiba -> tersembunyi di publik & 404 pada detail/like
        $this->get('/')->assertStatus(200)->assertDontSee('Berita Terjadwal Otomatis');
        $this->get('/berita')->assertStatus(200)->assertDontSee('Berita Terjadwal Otomatis');
        $this->get('/berita/' . $scheduled->slug)->assertStatus(404);
        $this->postJson('/berita/' . $scheduled->slug . '/like')->assertStatus(404);

        // Maju waktu 3 jam -> otomatis tampil ke publik, bisa di-like, dan hash check-update berubah
        $this->travel(3)->hours();
        $this->get('/')->assertStatus(200)->assertSee('Berita Terjadwal Otomatis');
        $this->get('/berita')->assertStatus(200)->assertSee('Berita Terjadwal Otomatis');
        $this->get('/berita/' . $scheduled->slug)->assertStatus(200)->assertSee('Berita Terjadwal Otomatis');
        $this->postJson('/berita/' . $scheduled->slug . '/like')->assertStatus(200)->assertJson([
            'success' => true,
            'liked' => true,
            'likes_count' => 1,
        ]);
        $hashAfter = $this->getJson('/api/check-update')->json('hash');
        $this->assertNotEquals($hashBefore, $hashAfter);
        $this->travelBack();

        // Terbitkan sekarang (publish-now) untuk berita terjadwal kedua + edit kembali ke terjadwal
        $this->actingAs($admin)->post('/admin/berita', [
            'judul' => 'Berita Terbit Instan',
            'kategori' => 'Liputan',
            'isi' => 'Siap diterbitkan kapan saja.',
            'status' => 'scheduled',
            'published_at' => now()->addHours(3)->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');

        $second = Berita::orderByDesc('id')->first();
        $this->get('/berita/' . $second->slug)->assertStatus(404);

        $this->actingAs($admin)->post('/admin/berita/' . $second->id . '/publish-now')
            ->assertSessionHas('success');
        $this->assertTrue($second->fresh()->isLive());
        $this->get('/berita/' . $second->slug)->assertStatus(200);

        // Kembalikan lagi ke status terjadwal via edit -> publik kembali 404
        $this->actingAs($admin)->put('/admin/berita/' . $second->id, [
            'judul' => 'Berita Terbit Instan',
            'kategori' => 'Liputan',
            'isi' => 'Dijadwalkan ulang 5 jam lagi.',
            'status' => 'scheduled',
            'published_at' => now()->addHours(5)->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');
        $this->get('/berita/' . $second->fresh()->slug)->assertStatus(404);
    }

    public function test_multi_emoji_reactions_switch_toggle_and_reject_invalid_reaction(): void
    {
        $admin = $this->createAdmin();
        $liga = Liga::current();

        // Pastikan zona paste/drop screenshot tersedia di halaman admin berita
        $this->actingAs($admin)->get('/admin/berita')
            ->assertStatus(200)
            ->assertSee('id="createBeritaPasteZone"', false)
            ->assertSee('id="editBeritaPasteZone"', false);

        $berita = Berita::create([
            'liga_id' => $liga->id,
            'judul' => 'Laga Seru Penuh Kejutan',
            'slug' => 'laga-seru-penuh-kejutan',
            'kategori' => 'Liputan',
            'isi' => 'Artikel dengan reaksi multi emoji.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Halaman detail menampilkan seluruh emoji positif & tanpa Dislike
        $detail = $this->get('/berita/' . $berita->slug);
        $detail->assertStatus(200);
        $detail->assertSee('❤️');
        $detail->assertSee('😊');
        $detail->assertSee('😂');
        $detail->assertSee('🔥');
        $detail->assertSee('👏');
        $detail->assertDontSee('Dislike');

        // Kirim reaksi tertawa (haha)
        $res1 = $this->postJson('/berita/' . $berita->slug . '/like', ['reaction' => 'haha']);
        $res1->assertStatus(200)->assertJson([
            'success' => true,
            'liked' => true,
            'user_reaction' => 'haha',
            'likes_count' => 1,
            'reaction_counts' => [
                'love' => 0,
                'smile' => 0,
                'haha' => 1,
                'fire' => 0,
                'clap' => 0,
            ],
        ]);

        // Pindah reaksi dari haha ke senyum (smile) -> total tetap 1
        $res2 = $this->postJson('/berita/' . $berita->slug . '/like', ['reaction' => 'smile']);
        $res2->assertStatus(200)->assertJson([
            'success' => true,
            'liked' => true,
            'user_reaction' => 'smile',
            'likes_count' => 1,
            'reaction_counts' => [
                'love' => 0,
                'smile' => 1,
                'haha' => 0,
                'fire' => 0,
                'clap' => 0,
            ],
        ]);

        // Klik reaksi smile lagi -> batal (toggle off)
        $res3 = $this->postJson('/berita/' . $berita->slug . '/like', ['reaction' => 'smile']);
        $res3->assertStatus(200)->assertJson([
            'success' => true,
            'liked' => false,
            'user_reaction' => null,
            'likes_count' => 0,
            'reaction_counts' => [
                'love' => 0,
                'smile' => 0,
                'haha' => 0,
                'fire' => 0,
                'clap' => 0,
            ],
        ]);
        $this->assertEquals(0, $berita->fresh()->likes_count);

        // Reaksi tidak valid (termasuk dislike) ditolak dengan 422
        $this->postJson('/berita/' . $berita->slug . '/like', ['reaction' => 'dislike'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
