<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPertandinganFilterTest extends TestCase
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

    public function test_default_shows_uncompleted_matches_and_supports_team_and_search_filters(): void
    {
        $admin = $this->createAdmin();
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026',
            'status' => 'berjalan',
            'jumlah_meja' => 2,
        ]);

        $t1 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'GARUDA ALPHA', 'pemain1' => 'Budi Santoso', 'pemain2' => 'Agus Salim', 'status' => 1]);
        $t2 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'ELANG BRAVO', 'pemain1' => 'Dodi Pratama', 'pemain2' => 'Eko Saputra', 'status' => 1]);
        $t3 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'HARIMAU CHARLIE', 'pemain1' => 'Fajar Nugroho', 'pemain2' => 'Gilang Ramadhan', 'status' => 1]);
        $t4 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'RAJAWALI DELTA', 'pemain1' => 'Hendra Wijaya', 'pemain2' => 'Irfan Hakim', 'status' => 1]);

        // Match 1: Sudah selesai (tidak boleh tampil saat default)
        $mSelesai = Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 1,
            'ronde' => 1,
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'tanggal' => '2026-11-19',
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'status' => 'selesai',
            'skor_home' => 3,
            'skor_away' => 1,
            'pemenang_id' => $t1->id,
        ]);

        // Match 2: Belum main (harus tampil saat default)
        $mBelum1 = Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 2,
            'ronde' => 1,
            'tim_home' => $t3->id,
            'tim_away' => $t4->id,
            'tanggal' => '2026-11-19',
            'waktu' => '19:45:00',
            'lokasi' => 'Meja 2',
            'status' => 'belum_main',
        ]);

        // Match 3: Belum main melibatkan t1
        $mBelum2 = Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 3,
            'ronde' => 2,
            'tim_home' => $t1->id,
            'tim_away' => $t3->id,
            'tanggal' => '2026-11-20',
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'status' => 'belum_main',
        ]);

        // 1. Default view -> hanya menampilkan yang belum selesai (#2 dan #3), bukan #1
        $resDefault = $this->actingAs($admin)->get('/admin/pertandingan');
        $resDefault->assertStatus(200);
        $resDefault->assertDontSee('id="m' . $mSelesai->id . '"', false);
        $resDefault->assertSee('id="m' . $mBelum1->id . '"', false);
        $resDefault->assertSee('id="m' . $mBelum2->id . '"', false);

        // 2. Filter by tim = t4 -> hanya tampil #2
        $resTim = $this->actingAs($admin)->get('/admin/pertandingan?tim=' . $t4->id);
        $resTim->assertStatus(200);
        $resTim->assertSee('id="m' . $mBelum1->id . '"', false);
        $resTim->assertDontSee('id="m' . $mBelum2->id . '"', false);
        $resTim->assertDontSee('id="m' . $mSelesai->id . '"', false);

        // 3. Search by nama pemain / tim (q=Fajar) -> tampil #2 dan #3
        $resSearch = $this->actingAs($admin)->get('/admin/pertandingan?q=Fajar');
        $resSearch->assertStatus(200);
        $resSearch->assertSee('id="m' . $mBelum1->id . '"', false);
        $resSearch->assertSee('id="m' . $mBelum2->id . '"', false);

        // 4. Filter f=semua -> tampil semua termasuk yang selesai (#1)
        $resAll = $this->actingAs($admin)->get('/admin/pertandingan?f=semua');
        $resAll->assertStatus(200);
        $resAll->assertSee('id="m' . $mSelesai->id . '"', false);
        $resAll->assertSee('id="m' . $mBelum1->id . '"', false);

        // 5. Simpan hasil tetap mempertahankan filter tim & status
        $resSave = $this->actingAs($admin)->post('/admin/pertandingan/' . $mBelum1->id, [
            'status' => 'selesai',
            'skor_home' => 2,
            'skor_away' => 0,
            'pemenang_id' => $t3->id,
            'f' => 'belum',
            'tim' => $t3->id,
        ]);
        $resSave->assertRedirect('/admin/pertandingan?f=belum&tim=' . $t3->id);
    }
}
