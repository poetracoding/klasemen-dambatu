<?php

namespace Tests\Feature;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfJadwalTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_pdf_route_accessible_and_chunked_per_two_days()
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Test',
            'musim' => '2026',
            'status' => 'berjalan',
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-20',
            'jumlah_meja' => 2,
        ]);

        $t1 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim A', 'pemain1' => 'P1', 'pemain2' => 'P2', 'status' => 1]);
        $t2 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim B', 'pemain1' => 'P3', 'pemain2' => 'P4', 'status' => 1]);
        $t3 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim C', 'pemain1' => 'P5', 'pemain2' => 'P6', 'status' => 1]);
        $t4 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim D', 'pemain1' => 'P7', 'pemain2' => 'P8', 'status' => 1]);
        $t5 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim E', 'pemain1' => 'P9', 'pemain2' => 'P10', 'status' => 1]);
        $t6 = Tim::create(['liga_id' => $liga->id, 'nama_tim' => 'Tim F', 'pemain1' => 'P11', 'pemain2' => 'P12', 'status' => 1]);

        // 5 tanggal berbeda -> harus menjadi 3 halaman (2 + 2 + 1)
        $matchups = [
            ['date' => '2026-11-11', 'home' => $t1->id, 'away' => $t2->id],
            ['date' => '2026-11-12', 'home' => $t3->id, 'away' => $t4->id],
            ['date' => '2026-11-13', 'home' => $t5->id, 'away' => $t6->id],
            ['date' => '2026-11-14', 'home' => $t1->id, 'away' => $t3->id],
            ['date' => '2026-11-15', 'home' => $t2->id, 'away' => $t4->id],
        ];

        foreach ($matchups as $idx => $m) {
            Pertandingan::create([
                'liga_id' => $liga->id,
                'nomor_pertandingan' => $idx + 1,
                'ronde' => floor($idx / 2) + 1,
                'tim_home' => $m['home'],
                'tim_away' => $m['away'],
                'tanggal' => $m['date'],
                'waktu' => '19:00:00',
                'lokasi' => 'Meja 1',
                'status' => 'belum_main',
            ]);
        }

        $response = $this->get('/cetak/jadwal');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));

        // Test download parameter
        $downloadResponse = $this->get('/cetak/jadwal?download=1');
        $downloadResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $downloadResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', $downloadResponse->headers->get('content-disposition'));

        // Test chunking calculation: 5 dates => 3 chunks/pages
        $matches = Pertandingan::where('liga_id', $liga->id)
            ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')
            ->get();

        $matchesByDate = $matches->groupBy(function ($item) {
            return $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('Y-m-d') : 'TBD';
        });

        $pages = $matchesByDate->chunk(2);
        $this->assertCount(3, $pages);
        $this->assertCount(2, $pages->get(0));
        $this->assertCount(2, $pages->get(1));
        $this->assertCount(1, $pages->get(2));
    }

    public function test_jadwal_pdf_empty_matches()
    {
        $liga = Liga::create([
            'nama_liga' => 'Liga Kosong',
            'musim' => '2026',
            'status' => 'draft',
        ]);

        $response = $this->get('/cetak/jadwal');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
