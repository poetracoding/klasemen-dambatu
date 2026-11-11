<?php

namespace Database\Seeders;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PDO;
use Throwable;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@dambatu.test',
                'password' => Hash::make('admin123'),
            ]
        );

        // 2. Cek apakah ada database/liga.sqlite di root proyek lama
        $sqlitePath = base_path('../database/liga.sqlite');
        if (file_exists($sqlitePath)) {
            try {
                $sqlite = new PDO('sqlite:' . $sqlitePath);
                $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Import Liga
                $ligaRow = $sqlite->query('SELECT * FROM liga LIMIT 1')->fetch();
                if ($ligaRow) {
                    $liga = Liga::updateOrCreate(
                        ['id' => $ligaRow['id']],
                        [
                            'nama_liga' => $ligaRow['nama_liga'] ?? 'LIGA DAM BATU',
                            'musim' => $ligaRow['musim'] ?? date('Y'),
                            'tanggal_mulai' => $ligaRow['tanggal_mulai'] ?: null,
                            'tanggal_selesai' => $ligaRow['tanggal_selesai'] ?: null,
                            'status' => $ligaRow['status'] ?? 'draft',
                            'jumlah_meja' => $ligaRow['jumlah_meja'] ?? 2,
                            'hari_pertandingan' => $ligaRow['hari_pertandingan'] ?? '1,2,3,4,5,6,7',
                            'tanggal_libur' => $ligaRow['tanggal_libur'] ?? null,
                        ]
                    );

                    // Import Tim
                    $tims = $sqlite->query('SELECT * FROM tim WHERE id_liga=' . (int)$ligaRow['id'])->fetchAll();
                    foreach ($tims as $t) {
                        Tim::updateOrCreate(
                            ['id' => $t['id']],
                            [
                                'liga_id' => $liga->id,
                                'nama_tim' => $t['nama_tim'],
                                'pemain1' => $t['pemain1'],
                                'pemain2' => $t['pemain2'],
                                'status' => $t['status'] ?? 1,
                            ]
                        );
                    }

                    // Import Pertandingan
                    $pertandingans = $sqlite->query('SELECT * FROM pertandingan WHERE id_liga=' . (int)$ligaRow['id'])->fetchAll();
                    foreach ($pertandingans as $p) {
                        Pertandingan::updateOrCreate(
                            ['id' => $p['id']],
                            [
                                'liga_id' => $liga->id,
                                'ronde' => $p['ronde'] ?? 1,
                                'nomor_pertandingan' => $p['nomor_pertandingan'] ?? 1,
                                'tanggal' => $p['tanggal'] ?: null,
                                'waktu' => $p['waktu'] ?: null,
                                'lokasi' => $p['lokasi'] ?: null,
                                'tim_home' => $p['tim_home'],
                                'tim_away' => $p['tim_away'],
                                'skor_home' => $p['skor_home'] ?? 0,
                                'skor_away' => $p['skor_away'] ?? 0,
                                'pemenang_id' => $p['pemenang_id'] ?: null,
                                'status' => $p['status'] ?? 'belum_main',
                                'catatan' => $p['catatan'] ?: null,
                            ]
                        );
                    }
                }
            } catch (Throwable $e) {
                // Ignore SQLite import error and create fallback default liga
            }
        }

        // Pastikan minimal ada 1 liga default
        Liga::current();
    }
}
