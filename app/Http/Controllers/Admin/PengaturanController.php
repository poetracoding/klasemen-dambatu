<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PDO;
use Throwable;

class PengaturanController extends Controller
{
    public function index()
    {
        $liga = Liga::current();
        return view('admin.pengaturan', compact('liga'));
    }

    public function updateLiga(Request $request)
    {
        $liga = Liga::current();
        $request->validate([
            'nama_liga' => 'required|string|max:80',
            'musim' => 'nullable|string|max:40',
        ]);

        $liga->update([
            'nama_liga' => trim($request->nama_liga),
            'musim' => trim($request->musim ?? ''),
        ]);

        return back()->with('success', 'Pengaturan liga berhasil disimpan.');
    }

    public function gantiPassword(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'lama' => 'required',
            'baru' => 'required|min:8|same:ulang',
            'ulang' => 'required',
        ]);

        if (!Hash::check($request->lama, $user->password)) {
            return back()->with('danger', 'Password lama tidak sesuai.');
        }

        $user->update([
            'password' => Hash::make($request->baru),
        ]);

        return back()->with('success', 'Password admin berhasil diperbarui.');
    }

    public function backup()
    {
        $liga = Liga::current();
        $tims = Tim::where('liga_id', $liga->id)->get()->toArray();
        $pertandingans = Pertandingan::where('liga_id', $liga->id)->get()->toArray();

        $data = [
            'app' => 'LIGA DAM BATU - LARAVEL 12',
            'exported_at' => now()->toIso8601String(),
            'liga' => $liga->toArray(),
            'tims' => $tims,
            'pertandingans' => $pertandingans,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'backup-dambatu-' . date('Ymd-His') . '.json';

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function uploadDb(Request $request)
    {
        $request->validate([
            'db_file' => 'required|file|max:51200', // max 50MB
        ]);

        $file = $request->file('db_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        // Cek header magic bytes untuk mendeteksi apakah berkas SQLite
        $fp = @fopen($realPath, 'rb');
        $header = $fp ? fread($fp, 16) : '';
        if ($fp) fclose($fp);
        $isSqlite = strpos($header, 'SQLite format 3') === 0 || in_array($ext, ['sqlite', 'sqlite3', 'db']);

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::beginTransaction();

            $liga = Liga::current();
            $now = now();

            if ($ext === 'json' && !$isSqlite) {
                $content = file_get_contents($realPath);
                $data = json_decode($content, true);

                if (!isset($data['liga']) || !isset($data['tims'])) {
                    throw new \Exception('Struktur file JSON backup tidak valid.');
                }

                $liga->update([
                    'nama_liga' => $data['liga']['nama_liga'] ?? $liga->nama_liga,
                    'musim' => $data['liga']['musim'] ?? $liga->musim,
                    'tanggal_mulai' => $this->normalizeDate($data['liga']['tanggal_mulai'] ?? null),
                    'tanggal_selesai' => $this->normalizeDate($data['liga']['tanggal_selesai'] ?? null),
                    'status' => $data['liga']['status'] ?? 'draft',
                    'jumlah_meja' => $data['liga']['jumlah_meja'] ?? 2,
                    'hari_pertandingan' => $data['liga']['hari_pertandingan'] ?? '1,2,3,4,5,6,7',
                    'tanggal_libur' => isset($data['liga']['tanggal_libur']) && is_array($data['liga']['tanggal_libur'])
                        ? implode(',', $data['liga']['tanggal_libur'])
                        : ($data['liga']['tanggal_libur'] ?? null),
                ]);

                DB::table('pertandingans')->where('liga_id', $liga->id)->delete();
                DB::table('tims')->where('liga_id', $liga->id)->delete();

                $validTeamIds = [];
                $timRecords = [];
                foreach ($data['tims'] as $t) {
                    $timId = (int) $t['id'];
                    $validTeamIds[] = $timId;
                    $timRecords[] = [
                        'id' => $timId,
                        'liga_id' => $liga->id,
                        'nama_tim' => $t['nama_tim'],
                        'pemain1' => $t['pemain1'] ?? '',
                        'pemain2' => $t['pemain2'] ?? '',
                        'status' => $t['status'] ?? 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($timRecords)) {
                    DB::table('tims')->insert($timRecords);
                }

                if (!empty($data['pertandingans'])) {
                    $matchRecords = [];
                    foreach ($data['pertandingans'] as $p) {
                        $pemenangId = (!empty($p['pemenang_id']) && in_array((int)$p['pemenang_id'], $validTeamIds, true))
                            ? (int) $p['pemenang_id']
                            : null;

                        $matchRecords[] = [
                            'id' => (int) $p['id'],
                            'liga_id' => $liga->id,
                            'ronde' => $p['ronde'] ?? 1,
                            'nomor_pertandingan' => $p['nomor_pertandingan'] ?? 1,
                            'tanggal' => $this->normalizeDate($p['tanggal'] ?? null),
                            'waktu' => $this->normalizeTime($p['waktu'] ?? null),
                            'lokasi' => $p['lokasi'] ?: null,
                            'tim_home' => (int) $p['tim_home'],
                            'tim_away' => (int) $p['tim_away'],
                            'skor_home' => (int) ($p['skor_home'] ?? 0),
                            'skor_away' => (int) ($p['skor_away'] ?? 0),
                            'pemenang_id' => $pemenangId,
                            'status' => $p['status'] ?? 'belum_main',
                            'catatan' => $p['catatan'] ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    foreach (array_chunk($matchRecords, 50) as $chunk) {
                        DB::table('pertandingans')->insert($chunk);
                    }
                }
            } elseif ($isSqlite) {
                // Import from SQLite
                $sqlite = new PDO('sqlite:' . $realPath);
                $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $ligaRow = $sqlite->query('SELECT * FROM liga LIMIT 1')->fetch();
                if (!$ligaRow) {
                    throw new \Exception('Berkas SQLite tidak memiliki tabel liga.');
                }

                $liga->update([
                    'nama_liga' => $ligaRow['nama_liga'] ?? $liga->nama_liga,
                    'musim' => $ligaRow['musim'] ?? $liga->musim,
                    'tanggal_mulai' => $this->normalizeDate($ligaRow['tanggal_mulai'] ?? null),
                    'tanggal_selesai' => $this->normalizeDate($ligaRow['tanggal_selesai'] ?? null),
                    'status' => $ligaRow['status'] ?? 'draft',
                    'jumlah_meja' => $ligaRow['jumlah_meja'] ?? 2,
                    'hari_pertandingan' => $ligaRow['hari_pertandingan'] ?? '1,2,3,4,5,6,7',
                    'tanggal_libur' => $ligaRow['tanggal_libur'] ?? null,
                ]);

                DB::table('pertandingans')->where('liga_id', $liga->id)->delete();
                DB::table('tims')->where('liga_id', $liga->id)->delete();

                $tims = $sqlite->query('SELECT * FROM tim WHERE id_liga=' . (int)$ligaRow['id'])->fetchAll();
                if (empty($tims)) {
                    $tims = $sqlite->query('SELECT * FROM tim')->fetchAll();
                }

                $validTeamIds = [];
                $timRecords = [];
                foreach ($tims as $t) {
                    $timId = (int) $t['id'];
                    $validTeamIds[] = $timId;
                    $timRecords[] = [
                        'id' => $timId,
                        'liga_id' => $liga->id,
                        'nama_tim' => $t['nama_tim'],
                        'pemain1' => $t['pemain1'] ?? '',
                        'pemain2' => $t['pemain2'] ?? '',
                        'status' => $t['status'] ?? 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($timRecords)) {
                    DB::table('tims')->insert($timRecords);
                }

                $pertandingans = $sqlite->query('SELECT * FROM pertandingan WHERE id_liga=' . (int)$ligaRow['id'])->fetchAll();
                if (empty($pertandingans)) {
                    $pertandingans = $sqlite->query('SELECT * FROM pertandingan')->fetchAll();
                }

                $matchRecords = [];
                foreach ($pertandingans as $p) {
                    $pemenangId = (!empty($p['pemenang_id']) && in_array((int)$p['pemenang_id'], $validTeamIds, true))
                        ? (int) $p['pemenang_id']
                        : null;

                    $matchRecords[] = [
                        'id' => (int) $p['id'],
                        'liga_id' => $liga->id,
                        'ronde' => $p['ronde'] ?? 1,
                        'nomor_pertandingan' => $p['nomor_pertandingan'] ?? 1,
                        'tanggal' => $this->normalizeDate($p['tanggal'] ?? null),
                        'waktu' => $this->normalizeTime($p['waktu'] ?? null),
                        'lokasi' => $p['lokasi'] ?: null,
                        'tim_home' => (int) $p['tim_home'],
                        'tim_away' => (int) $p['tim_away'],
                        'skor_home' => (int) ($p['skor_home'] ?? 0),
                        'skor_away' => (int) ($p['skor_away'] ?? 0),
                        'pemenang_id' => $pemenangId,
                        'status' => $p['status'] ?? 'belum_main',
                        'catatan' => $p['catatan'] ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($matchRecords, 50) as $chunk) {
                    DB::table('pertandingans')->insert($chunk);
                }
            } else {
                throw new \Exception('Format berkas harus .json, .sqlite, atau .db.');
            }

            \App\Services\TournamentService::sinkronStatusLiga($liga);

            DB::commit();

            // Atur auto-increment setelah transaksi selesai
            $maxTim = DB::table('tims')->max('id') ?? 0;
            if ($maxTim > 0) {
                DB::statement('ALTER TABLE tims AUTO_INCREMENT = ' . ($maxTim + 1));
            }
            $maxMatch = DB::table('pertandingans')->max('id') ?? 0;
            if ($maxMatch > 0) {
                DB::statement('ALTER TABLE pertandingans AUTO_INCREMENT = ' . ($maxMatch + 1));
            }

            return back()->with('success', 'Database berhasil dipulihkan dari berkas yang diunggah!');
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return back()->with('danger', 'Gagal memulihkan database: ' . $e->getMessage());
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function normalizeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $val = trim((string) $value);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $val, $matches)) {
            return $matches[1];
        }

        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    private function normalizeTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $val = trim((string) $value);
        if (preg_match('/^(\d{2}:\d{2}(?::\d{2})?)/', $val, $matches)) {
            return strlen($matches[1]) === 5 ? $matches[1] . ':00' : $matches[1];
        }

        try {
            return \Carbon\Carbon::parse($val)->format('H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }
}

