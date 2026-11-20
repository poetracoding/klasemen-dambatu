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

    public static function namaSingkat(?string $nama, int $maxLen = 7): string
    {
        $nama = trim((string) $nama);
        if ($nama === '') {
            return '';
        }
        if (mb_strlen($nama) <= $maxLen) {
            return $nama;
        }
        $parts = preg_split('/\s+/', $nama);
        $first = $parts[0] ?? $nama;
        if (mb_strlen($first) > $maxLen) {
            $first = mb_substr($first, 0, max(1, $maxLen));
        }
        return rtrim($first, '.') . '...';
    }

    public static function pemainLabel(?string $p1, ?string $p2, bool $short = true, int $maxLen = 7): string
    {
        $n1 = $short ? self::namaSingkat($p1, $maxLen) : trim((string) $p1);
        $n2 = $short ? self::namaSingkat($p2, $maxLen) : trim((string) $p2);
        return trim($n1 . ($n1 !== '' && $n2 !== '' ? ' & ' : '') . $n2);
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

    /**
     * Hitung skor kekuatan & riwayat performa per tim untuk keperluan prediksi & analisis tren.
     */
    public static function hitungKekuatanTim(Liga $liga): array
    {
        $klasemen = self::hitungKlasemen($liga);
        $totalTeams = count($klasemen);

        $finishedMatches = Pertandingan::where('liga_id', $liga->id)
            ->where('status', 'selesai')
            ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan, id')
            ->get();

        $historyByTeam = [];
        foreach ($finishedMatches as $m) {
            $homeWon = (int) $m->pemenang_id === (int) $m->tim_home;
            $awayWon = (int) $m->pemenang_id === (int) $m->tim_away;
            $diffHome = (int) $m->skor_home - (int) $m->skor_away;

            $historyByTeam[$m->tim_home][] = [
                'won' => $homeWon,
                'diff' => $diffHome,
                'opp_id' => $m->tim_away,
            ];
            $historyByTeam[$m->tim_away][] = [
                'won' => $awayWon,
                'diff' => -$diffHome,
                'opp_id' => $m->tim_home,
            ];
        }

        $map = [];
        foreach ($klasemen as $row) {
            $id = (int) $row['id'];
            $mp = (int) $row['mp'];
            $w = (int) $row['w'];
            $l = (int) $row['l'];
            $sb = (int) $row['sb'];
            $pos = (int) $row['pos'];

            $teamHistory = $historyByTeam[$id] ?? [];
            $winRate = $mp > 0 ? ($w / $mp) * 100.0 : 50.0;
            $avgDiff = $mp > 0 ? ($sb / $mp) : 0.0;

            // Tren 5 pertandingan terakhir (bobot lebih besar ke laga paling baru)
            $recentMatches = array_slice($teamHistory, -5);
            $recentForm = array_map(fn ($h) => $h['won'] ? 'W' : 'L', $recentMatches);

            if (count($recentMatches) > 0) {
                $weights = [1.0, 1.2, 1.4, 1.6, 1.8];
                $offset = count($weights) - count($recentMatches);
                $weightedWins = 0.0;
                $weightSum = 0.0;
                foreach ($recentMatches as $idx => $h) {
                    $wt = $weights[$offset + $idx];
                    $weightSum += $wt;
                    if ($h['won']) {
                        $weightedWins += $wt;
                    }
                }
                $recentRate = $weightSum > 0 ? ($weightedWins / $weightSum) * 100.0 : 50.0;
                $recentRawRate = (count(array_filter($recentMatches, fn ($h) => $h['won'])) / count($recentMatches)) * 100.0;
            } else {
                $recentRate = 50.0;
                $recentRawRate = 0.0;
            }

            // Streak terkini (menang/kalah beruntun dari laga terakhir)
            $streakType = 'N';
            $streakCount = 0;
            if (!empty($teamHistory)) {
                $lastWon = $teamHistory[count($teamHistory) - 1]['won'];
                $streakType = $lastWon ? 'W' : 'L';
                for ($i = count($teamHistory) - 1; $i >= 0; $i--) {
                    if ($teamHistory[$i]['won'] === $lastWon) {
                        $streakCount++;
                    } else {
                        break;
                    }
                }
            }

            // Skor selisih poin (dinormalisasi ke 0..100, rata-rata 0 -> 50)
            $diffScore = 50.0 + max(-50.0, min(50.0, $avgDiff * 2.5));
            // Skor peringkat klasemen (0..100)
            $rankScore = $totalTeams > 1 ? (($totalTeams - $pos) / ($totalTeams - 1)) * 100.0 : 50.0;

            $strength = $mp > 0
                ? ($winRate * 0.40) + ($recentRate * 0.30) + ($diffScore * 0.20) + ($rankScore * 0.10)
                : 50.0;

            $map[$id] = [
                'id' => $id,
                'nama_tim' => $row['nama_tim'],
                'pemain1' => $row['pemain1'],
                'pemain2' => $row['pemain2'],
                'pos' => $pos,
                'mp' => $mp,
                'w' => $w,
                'l' => $l,
                'sb' => $sb,
                'pts' => (int) $row['pts'],
                'win_rate' => $mp > 0 ? round($winRate, 1) : 0.0,
                'recent_rate' => $mp > 0 ? round($recentRawRate, 1) : 0.0,
                'avg_diff' => round($avgDiff, 1),
                'recent_form' => $recentForm,
                'streak_type' => $streakType,
                'streak_count' => $streakCount,
                'strength' => round($strength, 2),
            ];
        }

        return $map;
    }

    /**
     * Perkirakan persentase peluang kemenangan untuk pertandingan yang belum selesai.
     */
    public static function prediksiPertandingan(Liga $liga, Pertandingan $match, ?array $strengthMap = null): array
    {
        $map = $strengthMap ?? self::hitungKekuatanTim($liga);

        $defaultTeam = [
            'id' => 0,
            'nama_tim' => '-',
            'pos' => 0,
            'mp' => 0,
            'w' => 0,
            'l' => 0,
            'sb' => 0,
            'pts' => 0,
            'win_rate' => 0.0,
            'recent_rate' => 0.0,
            'avg_diff' => 0.0,
            'recent_form' => [],
            'streak_type' => 'N',
            'streak_count' => 0,
            'strength' => 50.0,
        ];

        $home = $map[(int) $match->tim_home] ?? $defaultTeam;
        $away = $map[(int) $match->tim_away] ?? $defaultTeam;

        $hasData = ($home['mp'] > 0 || $away['mp'] > 0);

        if (!$hasData) {
            $homePct = 50;
            $awayPct = 50;
        } else {
            $diff = $home['strength'] - $away['strength'];
            $prob = 1.0 / (1.0 + exp(-$diff / 18.0));
            $homePct = max(10, min(90, (int) round($prob * 100)));
            $awayPct = 100 - $homePct;
        }

        $favored = $homePct > $awayPct ? 'home' : ($awayPct > $homePct ? 'away' : 'even');

        return [
            'home_pct' => $homePct,
            'away_pct' => $awayPct,
            'favored' => $favored,
            'has_data' => $hasData,
            'home' => $home,
            'away' => $away,
        ];
    }

    /**
     * Analisis tren performa tiap tim (cenderung menang / kalah) & proyeksi sisa pertandingan.
     */
    public static function analisisTrenTim(Liga $liga): array
    {
        $strengthMap = self::hitungKekuatanTim($liga);

        $remainingMatches = Pertandingan::where('liga_id', $liga->id)
            ->where('status', '<>', 'selesai')
            ->get();

        $remainingByTeam = [];
        foreach ($remainingMatches as $m) {
            $remainingByTeam[$m->tim_home][] = $m;
            $remainingByTeam[$m->tim_away][] = $m;
        }

        $analysis = [];
        foreach ($strengthMap as $id => $team) {
            $mp = $team['mp'];
            $winRate = $team['win_rate'];
            $recentRate = $team['recent_rate'];
            $avgDiff = $team['avg_diff'];
            $streakType = $team['streak_type'];
            $streakCount = $team['streak_count'];

            if ($mp === 0) {
                $trendKey = 'no_data';
                $trendLabel = 'Belum Bertanding';
                $trendColor = 'slate';
                $trendDesc = 'Belum ada riwayat pertandingan untuk membaca tren tim.';
            } elseif (($winRate >= 70 && ($streakType === 'W' || $avgDiff > 0)) || ($streakType === 'W' && $streakCount >= 3)) {
                $trendKey = 'dominant';
                $trendLabel = 'Tren Menang Kuat';
                $trendColor = 'emerald';
                $trendDesc = 'Performa sangat konsisten dan berpeluang tinggi terus menang.';
            } elseif (($recentRate >= 60 && $recentRate > $winRate) || ($streakType === 'W' && $streakCount >= 2)) {
                $trendKey = 'rising';
                $trendLabel = 'Tren Membaik';
                $trendColor = 'sky';
                $trendDesc = 'Momentum positif meningkat pada laga-laga terakhir.';
            } elseif (($winRate <= 30 && ($streakType === 'L' || $avgDiff < 0)) || ($streakType === 'L' && $streakCount >= 3)) {
                $trendKey = 'vulnerable';
                $trendLabel = 'Tren Kalah (Rentan)';
                $trendColor = 'rose';
                $trendDesc = 'Sering menelan kekalahan & selisih poin negatif; berisiko terus kalah jika tidak membaik.';
            } elseif (($recentRate <= 40 && $recentRate < $winRate) || ($streakType === 'L' && $streakCount >= 2)) {
                $trendKey = 'slump';
                $trendLabel = 'Tren Menurun';
                $trendColor = 'amber';
                $trendDesc = 'Performa menurun pada pertandingan terakhir.';
            } else {
                $trendKey = 'steady';
                $trendLabel = 'Seimbang / Stabil';
                $trendColor = 'indigo';
                $trendDesc = 'Hasil menang dan kalah relatif berimbang.';
            }

            // Proyeksi menghadapi sisa lawan
            $teamRemaining = $remainingByTeam[$id] ?? [];
            $projWins = 0;
            $projLosses = 0;
            $projEven = 0;
            $pctSum = 0;

            foreach ($teamRemaining as $rm) {
                $pred = self::prediksiPertandingan($liga, $rm, $strengthMap);
                $myPct = ((int) $rm->tim_home === $id) ? $pred['home_pct'] : $pred['away_pct'];
                $pctSum += $myPct;
                if ($myPct > 50) {
                    $projWins++;
                } elseif ($myPct < 50) {
                    $projLosses++;
                } else {
                    $projEven++;
                }
            }

            $remCount = count($teamRemaining);
            $avgProjPct = $remCount > 0 ? (int) round($pctSum / $remCount) : ($mp > 0 ? (int) round($winRate) : 50);

            $analysis[] = array_merge($team, [
                'trend_key' => $trendKey,
                'trend_label' => $trendLabel,
                'trend_color' => $trendColor,
                'trend_desc' => $trendDesc,
                'remaining_count' => $remCount,
                'projected_wins' => $projWins,
                'projected_losses' => $projLosses,
                'projected_even' => $projEven,
                'projected_win_pct' => $avgProjPct,
            ]);
        }

        usort($analysis, function ($a, $b) {
            return $b['strength'] <=> $a['strength']
                ?: $b['win_rate'] <=> $a['win_rate']
                ?: $a['pos'] <=> $b['pos'];
        });

        return $analysis;
    }
}
