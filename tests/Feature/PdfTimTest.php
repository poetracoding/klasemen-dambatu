<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Tim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfTimTest extends TestCase
{
    use RefreshDatabase;

    public function test_tim_pdf_route_accessible_and_generates_pdf()
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Dam Batu Seri 1',
            'musim' => '2026/2027',
            'status' => 'berjalan',
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-25',
            'jumlah_meja' => 2,
        ]);

        $t1 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'Garuda Perkasa',
            'pemain1' => 'Budi Santoso',
            'pemain2' => 'Agus Wijaya',
            'status' => 1,
        ]);

        $t2 = Tim::create([
            'liga_id' => $liga->id,
            'nama_tim' => 'Elang Hitam',
            'pemain1' => 'Joko Widodo',
            'pemain2' => 'Prabowo Subianto',
            'status' => 1,
        ]);

        // Stream view
        $response = $this->get('/cetak/tim');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        // Download view
        $downloadResponse = $this->get('/cetak/tim?download=1');
        $downloadResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $downloadResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', $downloadResponse->headers->get('content-disposition'));
        $this->assertStringContainsString('daftar-tim-', $downloadResponse->headers->get('content-disposition'));
    }

    public function test_tim_pdf_with_empty_teams()
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Baru',
            'musim' => '2026/2027',
            'status' => 'draft',
        ]);

        $response = $this->get('/cetak/tim');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
