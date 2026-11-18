<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPlayersDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_nama_singkat_helper_formats_names_concisely(): void
    {
        $this->assertEquals('TOGU...', TournamentService::namaSingkat('TOGU MANURUNG'));
        $this->assertEquals('NUR...', TournamentService::namaSingkat("NUR 'AINUN"));
        $this->assertEquals('ERDIANS...', TournamentService::namaSingkat('ERDIANSYAH HARAHAP'));
        $this->assertEquals('WANDASY...', TournamentService::namaSingkat('WANDASYAH PUTRA SAMOSIR'));
        $this->assertEquals('BUDI', TournamentService::namaSingkat('BUDI'));
        $this->assertEquals('SANTOSO', TournamentService::namaSingkat('SANTOSO'));
        $this->assertEquals('M...', TournamentService::namaSingkat('M. Fauzi Harahap'));
        $this->assertEquals('', TournamentService::namaSingkat(''));
    }

    public function test_public_and_admin_views_display_player_names_under_team_names(): void
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu',
            'musim' => '2026',
            'status' => 'berjalan',
            'jumlah_meja' => 2,
        ]);

        $t1 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'MIE SADAP PRAPRORO',
            'pemain1' => 'ERDIANSYAH HARAHAP',
            'pemain2' => 'WANDASYAH PUTRA SAMOSIR',
            'status' => 1,
        ]);

        $t2 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'GARUDA JAYA',
            'pemain1' => 'Budi Santoso',
            'pemain2' => 'Agus Salim',
            'status' => 1,
        ]);

        $match = Pertandingan::create([
            'liga_id' => $liga->id,
            'nomor_pertandingan' => 1,
            'ronde' => 1,
            'tim_home' => $t1->id,
            'tim_away' => $t2->id,
            'tanggal' => date('Y-m-d'),
            'waktu' => '19:00:00',
            'lokasi' => 'Meja 1',
            'status' => 'selesai',
            'skor_home' => 3,
            'skor_away' => 1,
            'pemenang_id' => $t1->id,
        ]);

        // Public Dashboard (Top 5 klasemen + match hari ini)
        $resHome = $this->get('/');
        $resHome->assertStatus(200);
        $resHome->assertSee('MIE SADAP PRAPRORO');
        $resHome->assertSee('ERDIANS...');
        $resHome->assertSee('WANDASY...');
        $resHome->assertSee('bi-person-fill');

        // Public Jadwal, Klasemen, History, Detail Pertandingan
        $this->get('/jadwal')->assertStatus(200)->assertSee('ERDIANS...')->assertSee('WANDASY...');
        $this->get('/klasemen')->assertStatus(200)->assertSee('ERDIANSYAH HARAHAP')->assertSee('WANDASYAH PUTRA SAMOSIR');
        $this->get('/history')->assertStatus(200)->assertSee('ERDIANS...')->assertSee('WANDASY...');
        $this->get('/pertandingan/' . $match->id)->assertStatus(200)->assertSee('ERDIANSYAH HARAHAP')->assertSee('WANDASYAH PUTRA SAMOSIR');

        // Admin views
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@local.test',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin)->get('/admin')->assertStatus(200)->assertSee('ERDIANS...')->assertSee('WANDASY...');
        $this->actingAs($admin)->get('/admin/jadwal')->assertStatus(200)->assertSee('ERDIANS...')->assertSee('WANDASY...');
        $this->actingAs($admin)->get('/admin/pertandingan')->assertStatus(200)->assertSee('ERDIANS...')->assertSee('WANDASY...');

        // PDFs
        $this->get('/cetak/jadwal')->assertStatus(200);
        $this->get('/cetak/klasemen')->assertStatus(200);
    }
}
