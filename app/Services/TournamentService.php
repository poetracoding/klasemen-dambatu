<?php

namespace App\Services;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use Illuminate\Support\Facades\DB;

class TournamentService
{
    public const STATUS_MATCH = [
        'belum_main'  => 'Belum Main',
        'berlangsung' => 'Sedang Main',
        'selesai'     => 'Selesai',
    ];

    public static function roundRobin(array $teams): array
    {
        $n = count($teams);
        if ($n < 2) {
            return [];
        }

        $hasBye = false;
        if ($n % 2 !== 0) {
            $teams[] = null;
            $n++;
            $hasBye = true;
        }

        $rounds = [];
        $totalRounds = $n - 1;
        $half = $n / 2;

        for ($r = 0; $r < $totalRounds; $r++) {
            $pairs = [];
            for ($i = 0; $i < $half; $i++) {
                $t1 = $teams[$i];
                $t2 = $teams[$n - 1 - $i];

                if ($t1 !== null && $t2 !== null) {
                    if ($r % 2 === 1 && $i === 0) {
                        $pairs[] = [$t2, $t1];
                    } else {
                        $pairs[] = [$t1, $t2];
                    }
                }
            }

            $rounds[] = $pairs;

            // Rotate array keeping index 0 fixed
            $fixed = $teams[0];
            $rotated = array_slice($teams, 1);
            $last = array_pop($rotated);
            array_unshift($rotated, $last);
            $teams = array_merge([$fixed], $rotated);
        }

        return $rounds;
    }

    public static function buatJadwal(Liga $liga, array $o): int
    {
        $teamIds = Tim::where('liga_id', $liga->id)
            ->where('status', 1)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $rounds = self::roundRobin($teamIds);

        $jumlahMeja = max(1, (int) ($o['jumlah_meja'] ?? 1));
        $prefixLokasi = trim($o['lokasi'] ?? '') ?: 'Meja';
        $perHari = max(1, (int) ($o['per_hari'] ?? 4));
        $durasi = max(10, (int) ($o['durasi'] ?? 45));
        $jamMulai = ($o['jam'] ?? '') ?: '19:00';
        $tglMulai = ($o['mulai'] ?? '') ?: date('Y-m-d');

        // Penentuan Hari Pertandingan (1=Senin .. 7=Minggu)
        $hariDipilih = !empty($o['hari']) && is_array($o['hari'])
            ? array_values(array_filter(array_map('intval', $o['hari']), fn($x) => $x >= 1 && $x <= 7))
            : (isset($o['hari']) && is_string($o['hari']) && trim($o['hari']) !== ''
                ? array_values(array_filter(array_map('intval', explode(',', $o['hari'])), fn($x) => $x >= 1 && $x <= 7))
                : [1, 2, 3, 4, 5, 6, 7]);

        if (empty($hariDipilih)) {
            $hariDipilih = [1, 2, 3, 4, 5, 6, 7];
        }
        sort($hariDipilih);
        $hariPertandinganStr = implode(',', $hariDipilih);

        // Tanggal Libur Khusus (Skenario 1: Blackout Dates)
        $tanggalLibur = [];
        if (!empty($o['libur'])) {
            $rawLibur = is_array($o['libur']) ? $o['libur'] : explode(',', (string) $o['libur']);
            foreach ($rawLibur as $d) {
                $d = trim($d);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $tanggalLibur[] = $d;
                }
            }
        }
        $tanggalLibur = array_values(array_unique($tanggalLibur));
        sort($tanggalLibur);
        $tanggalLiburStr = implode(',', $tanggalLibur);

        // Kumpulkan seluruh pasangan pertandingan sesuai ronde
        $matchList = [];
        foreach ($rounds as $rIndex => $pairs) {
            foreach ($pairs as [$a, $b]) {
                $matchList[] = [
                    'ronde' => $rIndex + 1,
                    'a' => $a,
                    'b' => $b,
                ];
            }
        }

        $totalTim = count($teamIds);
        $maxMatchesPerSlot = min($jumlahMeja, max(1, intdiv($totalTim, 2)));

        $isDateAllowed = function (int $ts) use ($hariDipilih, $tanggalLibur): bool {
            $dow = (int) date('N', $ts);
            $ymd = date('Y-m-d', $ts);
            return in_array($dow, $hariDipilih, true) && !in_array($ymd, $tanggalLibur, true);
        };

        $currentDateTs = strtotime($tglMulai);
        while (!$isDateAllowed($currentDateTs)) {
            $currentDateTs = strtotime('+1 day', $currentDateTs);
        }

        $matchesToday = 0;
        $slotIndex = 0;
        $no = 0;

        return DB::transaction(function () use (
            $liga, $jumlahMeja, $hariPertandinganStr, $tanggalLiburStr, $matchList,
            $maxMatchesPerSlot, $perHari, $jamMulai, $durasi, $prefixLokasi, $isDateAllowed,
            $currentDateTs, $no
        ) {
            Pertandingan::where('liga_id', $liga->id)->delete();
            $liga->update([
                'jumlah_meja' => $jumlahMeja,
                'hari_pertandingan' => $hariPertandinganStr,
                'tanggal_libur' => $tanggalLiburStr,
            ]);

            $matchesToday = 0;
            $slotIndex = 0;

            while (!empty($matchList)) {
                $matchesInThisSlot = 0;
                $teamsInSlot = [];

                while ($matchesInThisSlot < $maxMatchesPerSlot && $matchesToday < $perHari && !empty($matchList)) {
                    $foundKey = null;
                    foreach ($matchList as $k => $m) {
                        if (!in_array($m['a'], $teamsInSlot, true) && !in_array($m['b'], $teamsInSlot, true)) {
                            $foundKey = $k;
                            break;
                        }
                    }

                    if ($foundKey === null) {
                        break;
                    }

                    $m = $matchList[$foundKey];
                    unset($matchList[$foundKey]);
                    $matchList = array_values($matchList);

                    $no++;
                    $tanggal = date('Y-m-d', $currentDateTs);
                    $waktu = date('H:i', strtotime($jamMulai) + $slotIndex * $durasi * 60);

                    $mejaIndex = $matchesInThisSlot + 1;
                    $lokasi = $jumlahMeja > 1 ? "$prefixLokasi $mejaIndex" : $prefixLokasi;

                    Pertandingan::create([
                        'liga_id' => $liga->id,
                        'ronde' => $m['ronde'],
                        'nomor_pertandingan' => $no,
                        'tanggal' => $tanggal,
                        'waktu' => $waktu,
                        'lokasi' => $lokasi,
                        'tim_home' => $m['a'],
                        'tim_away' => $m['b'],
                        'skor_home' => 0,
                        'skor_away' => 0,
                        'status' => 'belum_main',
                    ]);

                    $teamsInSlot[] = $m['a'];
                    $teamsInSlot[] = $m['b'];
                    $matchesInThisSlot++;
                    $matchesToday++;
                }

                $slotIndex++;
                if ($matchesToday >= $perHari) {
                    do {
                        $currentDateTs = strtotime('+1 day', $currentDateTs);
                    } while (!$isDateAllowed($currentDateTs));

                    $matchesToday = 0;
                    $slotIndex = 0;
                }
            }

            return $no;
        });
    }

    public static function hitungKlasemen(Liga $liga): array
    {
        $teams = Tim::where('liga_id', $liga->id)
            ->where('status', 1)
            ->get();

        if ($teams->isEmpty()) {
            return [];
        }

        $rows = [];
        $h2h = [];

        foreach ($teams as $t) {
            $rows[$t->id] = [
                'id'       => $t->id,
                'nama_tim' => $t->nama_tim,
                'pemain1'  => $t->pemain1,
                'pemain2'  => $t->pemain2,
                'mp'       => 0,
                'w'        => 0,
                'l'        => 0,
                'bm'       => 0, // + Poin Masuk
                'bk'       => 0, // - Poin Keluar
                'sb'       => 0, // Δ Selisih Poin
                'pts'      => 0,
            ];
            $h2h[$t->id] = [];
        }

        $matches = Pertandingan::where('liga_id', $liga->id)
            ->where('status', 'selesai')
            ->get();

        foreach ($matches as $m) {
            $h = $m->tim_home;
            $a = $m->tim_away;
            $sh = (int) ($m->skor_home ?? 0);
            $sa = (int) ($m->skor_away ?? 0);
            $menang = (int) $m->pemenang_id;

            if (!isset($rows[$h]) || !isset($rows[$a])) {
                continue;
            }

            $rows[$h]['mp']++;
            $rows[$a]['mp']++;

            $rows[$h]['bm'] += $sh;
            $rows[$h]['bk'] += $sa;

            $rows[$a]['bm'] += $sa;
            $rows[$a]['bk'] += $sh;

            if ($menang === $h) {
                $rows[$h]['w']++;
                $rows[$h]['pts'] += 3;
                $rows[$a]['l']++;
                $h2h[$h][$a] = ($h2h[$h][$a] ?? 0) + 1;
            } elseif ($menang === $a) {
                $rows[$a]['w']++;
                $rows[$a]['pts'] += 3;
                $rows[$h]['l']++;
                $h2h[$a][$h] = ($h2h[$a][$h] ?? 0) + 1;
            }
        }

        foreach ($rows as &$r) {
            $r['sb'] = $r['bm'] - $r['bk'];
        }
        unset($r);

        $list = array_values($rows);

        usort($list, function ($a, $b) use ($h2h) {
            // 1. Poin Liga Tertinggi (PTS)
            if ($a['pts'] !== $b['pts']) {
                return $b['pts'] <=> $a['pts'];
            }
            // 2. Jumlah Kemenangan Terbanyak (W)
            if ($a['w'] !== $b['w']) {
                return $b['w'] <=> $a['w'];
            }
            // 3. Selisih Skor / Batu Terbesar (Δ / SB)
            if ($a['sb'] !== $b['sb']) {
                return $b['sb'] <=> $a['sb'];
            }
            // 4. Total Poin Masuk (+) / BM Terbanyak
            if ($a['bm'] !== $b['bm']) {
                return $b['bm'] <=> $a['bm'];
            }
            // 5. Head-to-Head (H2H) antar tim bersangkutan
            $h2hA = $h2h[$a['id']][$b['id']] ?? 0;
            $h2hB = $h2h[$b['id']][$a['id']] ?? 0;
            if ($h2hA !== $h2hB) {
                return $h2hB <=> $h2hA;
            }
            // 6. Nama Tim (Alfabetis)
            return strcasecmp($a['nama_tim'], $b['nama_tim']);
        });

        foreach ($list as $k => &$r) {
            $r['pos'] = $k + 1;
        }

        return $list;
    }

    public static function geserJadwal(Liga $liga, int $targetId, int $menit, string $cakupan): int
    {
        $target = Pertandingan::where('liga_id', $liga->id)->find($targetId);
        if (!$target || $menit === 0) {
            return 0;
        }

        if ($cakupan === 'hanya_satu') {
            $targetList = [$target];
        } else {
            $targetList = Pertandingan::where('liga_id', $liga->id)
                ->where('tanggal', $target->tanggal)
                ->where('nomor_pertandingan', '>=', $target->nomor_pertandingan)
                ->orderBy('nomor_pertandingan')
                ->get();
        }

        $count = 0;
        DB::transaction(function () use ($targetList, $menit, &$count) {
            foreach ($targetList as $m) {
                if ($m->waktu) {
                    $ts = strtotime($m->waktu) + ($menit * 60);
                    $m->waktu = date('H:i', $ts);
                    $m->save();
                    $count++;
                }
            }
        });

        return $count;
    }

    public static function sinkronStatusLiga(Liga $liga): void
    {
        if ($liga->status === 'draft') {
            return;
        }

        $total = Pertandingan::where('liga_id', $liga->id)->count();
        if ($total === 0) {
            return;
        }

        $selesai = Pertandingan::where('liga_id', $liga->id)->where('status', 'selesai')->count();
        $statusBaru = ($selesai === $total) ? 'selesai' : 'berjalan';

        if ($liga->status !== $statusBaru) {
            $liga->update(['status' => $statusBaru]);
        }
    }

    public static function tgl(?string $d): string
    {
        if (!$d) return '-';
        $t = strtotime($d);
        return $t ? date('d-m-Y', $t) : '-';
    }

    public static function tglId(?string $d): string
    {
        return self::tgl($d);
    }

    public static function hariId(?string $d): string
    {
        if (!$d) return '-';
        $n = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $ts = strtotime($d);
        return $ts ? $n[(int) date('w', $ts)] : '-';
    }

    public static function jam(?string $w): string
    {
        return $w ? substr($w, 0, 5) : '-';
    }

    public static function jamId(?string $w): string
    {
        return self::jam($w);
    }

    public static function badgeStatus(string $s): string
    {
        $badges = [
            'belum_main'  => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Belum Main</span>',
            'berlangsung' => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>Sedang Main</span>',
            'selesai'     => '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Selesai</span>',
        ];
        return $badges[$s] ?? '<span class="text-xs text-slate-500">-</span>';
    }

    public static function labelStatusLiga(string $s): string
    {
        $map = [
            'draft'    => 'Draft (Penyusunan)',
            'berjalan' => 'Sedang Berjalan',
            'selesai'  => 'Selesai',
        ];
        return $map[$s] ?? $s;
    }

    public static function getTickerMatches(?Liga $liga = null): array
    {
        $liga = $liga ?? Liga::current();
        if (!$liga || $liga->status === 'draft') {
            return [
                'tickerMatches' => collect(),
                'tickerLabel' => 'Jadwal & Hasil Minggu Ini',
                'tickerDateRange' => '',
            ];
        }

        $now = now();
        $startOfWeek = $now->copy()->startOfWeek()->toDateString();
        $endOfWeek = $now->copy()->endOfWeek()->toDateString();

        // 1. Coba ambil pertandingan dalam minggu aktif kalender ini
        $matches = Pertandingan::where('liga_id', $liga->id)
            ->whereBetween('tanggal', [$startOfWeek, $endOfWeek])
            ->with(['homeTeam', 'awayTeam'])
            ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')
            ->get();

        $label = 'Jadwal & Hasil Pekan Ini';
        $dateRangeStr = self::tgl($startOfWeek) . ' - ' . self::tgl($endOfWeek);

        // 2. Jika tidak ada pertandingan di minggu kalender ini, cari pekan terdekat yang ada jadwal
        if ($matches->isEmpty()) {
            $nearestDate = Pertandingan::where('liga_id', $liga->id)
                ->whereNotNull('tanggal')
                ->where('tanggal', '>=', $now->toDateString())
                ->min('tanggal');

            if (!$nearestDate) {
                $nearestDate = Pertandingan::where('liga_id', $liga->id)
                    ->whereNotNull('tanggal')
                    ->max('tanggal');
            }

            if ($nearestDate) {
                $refCarbon = \Carbon\Carbon::parse($nearestDate);
                $startWeekRef = $refCarbon->copy()->startOfWeek()->toDateString();
                $endWeekRef = $refCarbon->copy()->endOfWeek()->toDateString();

                $matches = Pertandingan::where('liga_id', $liga->id)
                    ->whereBetween('tanggal', [$startWeekRef, $endWeekRef])
                    ->with(['homeTeam', 'awayTeam'])
                    ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')
                    ->get();

                $dateRangeStr = self::tgl($startWeekRef) . ' - ' . self::tgl($endWeekRef);
            }
        }

        // 3. Fallback jika tetap kosong
        if ($matches->isEmpty()) {
            $matches = Pertandingan::where('liga_id', $liga->id)
                ->with(['homeTeam', 'awayTeam'])
                ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')
                ->take(15)
                ->get();
        }

        return [
            'tickerMatches' => $matches,
            'tickerLabel' => $label,
            'tickerDateRange' => $dateRangeStr,
        ];
    }
}
