<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Poster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PosterInformasiTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admin@test.local',
            'password' => bcrypt('password'),
        ]);
    }

    private function cleanupUploadedPosters(): void
    {
        $dir = public_path('uploads/posters');
        if (is_dir($dir)) {
            foreach (glob($dir . '/poster_*') as $file) {
                @unlink($file);
            }
        }
    }

    protected function tearDown(): void
    {
        $this->cleanupUploadedPosters();
        parent::tearDown();
    }

    public function test_admin_can_access_poster_page_and_upload_poster(): void
    {
        $admin = $this->createAdmin();
        Liga::current();

        $res = $this->actingAs($admin)->get('/admin/poster');
        $res->assertStatus(200);
        $res->assertSee('Poster Informasi');

        $file = UploadedFile::fake()->image('jadwal-resmi.png', 800, 600);
        $uploadRes = $this->actingAs($admin)->post('/admin/poster', [
            'judul' => 'Jadwal Opening Match',
            'deskripsi' => 'Poster pembukaan turnamen',
            'poster' => $file,
            'is_active' => 1,
        ]);

        $uploadRes->assertRedirect();
        $uploadRes->assertSessionHas('success');

        $poster = Poster::where('judul', 'Jadwal Opening Match')->first();
        $this->assertNotNull($poster);
        $this->assertTrue($poster->is_active);
        $this->assertFileExists(public_path($poster->file_path));
    }

    public function test_admin_can_upload_multiple_posters_toggle_status_edit_and_delete(): void
    {
        $admin = $this->createAdmin();
        Liga::current();

        // Hapus seed awal jika ada agar perhitungan jelas
        Poster::query()->delete();

        $f1 = UploadedFile::fake()->image('info1.png', 600, 400);
        $f2 = UploadedFile::fake()->image('info2.jpg', 600, 400);

        $this->actingAs($admin)->post('/admin/poster', [
            'judul' => 'Poster Slide',
            'posters' => [$f1, $f2],
            'is_active' => 1,
        ])->assertSessionHas('success');

        $this->assertEquals(2, Poster::count());
        $posters = Poster::orderBy('id')->get();

        // Edit poster pertama
        $replacementFile = UploadedFile::fake()->image('info1-updated.png', 600, 400);
        $oldPath = $posters[0]->file_path;
        $this->actingAs($admin)->put('/admin/poster/' . $posters[0]->id, [
            'judul' => 'Poster Utama Terbaru',
            'deskripsi' => 'Keterangan diperbarui',
            'urutan' => 1,
            'is_active' => 1,
            'poster' => $replacementFile,
        ])->assertSessionHas('success');

        $posters[0]->refresh();
        $this->assertEquals('Poster Utama Terbaru', $posters[0]->judul);
        $this->assertFileDoesNotExist(public_path($oldPath));
        $this->assertFileExists(public_path($posters[0]->file_path));

        // Toggle status poster kedua menjadi nonaktif
        $this->actingAs($admin)->post('/admin/poster/' . $posters[1]->id . '/toggle')
            ->assertSessionHas('success');
        $this->assertFalse($posters[1]->fresh()->is_active);

        // Hapus poster kedua
        $deletedPath = $posters[1]->file_path;
        $this->actingAs($admin)->delete('/admin/poster/' . $posters[1]->id)
            ->assertSessionHas('success');
        $this->assertNull(Poster::find($posters[1]->id));
        $this->assertFileDoesNotExist(public_path($deletedPath));
    }

    public function test_home_displays_informasi_only_active_posters_and_auto_slide_when_multiple(): void
    {
        $liga = Liga::current();
        Poster::query()->delete();

        // Buat 2 poster aktif dan 1 poster nonaktif
        $aktif1 = Poster::create([
            'liga_id' => $liga->id,
            'judul' => 'Info Aktif Pertama',
            'deskripsi' => 'Detail pertama',
            'file_path' => 'peraturan4.png',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $aktif2 = Poster::create([
            'liga_id' => $liga->id,
            'judul' => 'Info Aktif Kedua',
            'deskripsi' => 'Detail kedua',
            'file_path' => 'peraturan4.png',
            'urutan' => 2,
            'is_active' => true,
        ]);

        $nonaktif = Poster::create([
            'liga_id' => $liga->id,
            'judul' => 'Info Rahasia Nonaktif',
            'deskripsi' => 'Tidak boleh muncul',
            'file_path' => 'peraturan4.png',
            'urutan' => 3,
            'is_active' => false,
        ]);

        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee('Informasi');
        $res->assertDontSee('Peraturan &amp; Tata Tertib Pertandingan', false);
        $res->assertSee($aktif1->judul);
        $res->assertSee($aktif2->judul);
        $res->assertDontSee($nonaktif->judul);

        // Kontrol auto-slide, zoom, dan download harus tampil karena ada 2 poster aktif
        $res->assertSee('prevPosterSlide');
        $res->assertSee('nextPosterSlide');
        $res->assertSee('poster-dot');
        $res->assertSee('zoomPeraturan');
        $res->assertSee('cardDownloadPosterBtn');
        $res->assertSee('modalDownloadPosterBtn');

        // Nonaktifkan salah satu hingga tersisa 1 poster aktif -> kontrol slide disembunyikan, zoom & download tetap ada
        $aktif2->update(['is_active' => false]);
        $resSingle = $this->get('/');
        $resSingle->assertStatus(200);
        $resSingle->assertSee($aktif1->judul);
        $resSingle->assertDontSee($aktif2->judul);
        $resSingle->assertDontSee('Auto-slide');
        $resSingle->assertSee('zoomPeraturan');
        $resSingle->assertSee('cardDownloadPosterBtn');

        // Nonaktifkan semua poster -> section Informasi disembunyikan
        $aktif1->update(['is_active' => false]);
        $resNone = $this->get('/');
        $resNone->assertStatus(200);
        $resNone->assertDontSee('id="informasiSection"', false);
    }
}
