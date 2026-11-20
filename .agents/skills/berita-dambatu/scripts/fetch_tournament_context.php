<?php

declare(strict_types=1);

use App\Models\Berita;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\TournamentService;
use Illuminate\Contracts\Console\Kernel;

/**
 * Strictly read-only CLI helper to extract live Liga Dam Batu tournament context
 * for drafting news articles under /admin/berita.
 */
$rootDir = dirname(__DIR__, 4);
if (!is_file($rootDir . '/vendor/autoload.php')) {
    $cwd = getcwd();
    if ($cwd !== false && is_file($cwd . '/vendor/autoload.php')) {
        $rootDir = $cwd;
    } else {
        fwrite(STDERR, "Gagal menemukan vendor/autoload.php dari root proyek.\n");
        exit(1);
    }
}

require $rootDir . '/vendor/autoload.php';
$app = require $rootDir . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

/**
 * Parse supported CLI options (--teams, --recent, --upcoming).
 *
 * @param array<int, string> $argv
 * @return array{teams: array<int, string>, recent: int, upcoming: int}
 */
function parseCliArgs(array $argv): array
{
    $teamsRaw = '';
    $recent = 8;
    $upcoming = 5;

    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        $arg = $argv[$i];
        if (str_starts_with($arg, '--teams=')) {
            $teamsRaw = substr($arg, 8);
        } elseif ($arg === '--teams' && isset($argv[$i + 1])) {
            $teamsRaw = $argv[++$i];
        } elseif (str_starts_with($arg, '--recent=')) {
            $recent = (int) substr($arg, 9);
        } elseif ($arg === '--recent' && isset($argv[$i + 1])) {
            $recent = (int) $argv[++$i];
        } elseif (str_starts_with($arg, '--upcoming=')) {
            $upcoming = (int) substr($arg, 11);
        } elseif ($arg === '--upcoming' && isset($argv[$i + 1])) {
            $upcoming = (int) $argv[++$i];
        }
    }

    $teamFilters = [];
    if (trim($teamsRaw) !== '') {
        foreach (explode(',', $teamsRaw) as $part) {
            $cleaned = trim($part, " \t\n\r\0\x0B\"'");
            if ($cleaned !== '') {
                $teamFilters[] = $cleaned;
            }
        }
    }

    return [
        'teams' => array_values(array_unique($teamFilters)),
        'recent' => max(1, min(50, $recent)),
        'upcoming' => max(0, min(30, $upcoming)),
    ];
}

function decodeHariPertandingan(?string $hariRaw): string
{
    if ($hariRaw === null || trim($hariRaw) === '') {
        return '-';
    }

    $dayNames = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    $labels = [];
    foreach (explode(',', $hariRaw) as $num) {
        $n = (int) trim($num);
        if (isset($dayNames[$n])) {
            $labels[] = $dayNames[$n];
        }
    }

    return $labels !== [] ? implode(', ', $labels) : $hariRaw;
}

function formatDateValue(mixed $value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }
    if (is_string($value) && trim($value) !== '') {
        return substr($value, 0, 10);
    }

    return '-';
}

function formatTimestampValue(mixed $value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    return is_string($value) && $value !== '' ? $value : '-';
}

$options = parseCliArgs($argv);

// Strictly read-only: never call Liga::current() because firstOrCreate can insert a row.
$liga = Liga::first();
if (!$liga) {
    echo "Belum ada data Liga di database. Tidak ada konteks turnamen untuk ditampilkan.\n";
    exit(0);
}

$klasemen = TournamentService::hitungKlasemen($liga);
$posByTeamId = [];
$teamById = [];
foreach ($klasemen as $row) {
    $posByTeamId[$row['id']] = $row['pos'];
    $teamById[$row['id']] = $row;
}

// Also include any team names from Tim table in case inactive teams appear on historical matches.
$allTeams = Tim::where('liga_id', $liga->id)->get(['id', 'nama_tim', 'pemain1', 'pemain2', 'status']);
$timNameById = [];
foreach ($allTeams as $t) {
    $timNameById[$t->id] = $t->nama_tim;
}

$allMatches = Pertandingan::where('liga_id', $liga->id)->get();
$totalMatches = $allMatches->count();
$finishedMatches = Pertandingan::where('liga_id', $liga->id)
    ->where('status', 'selesai')
    ->orderByDesc('updated_at')
    ->orderByDesc('id')
    ->get();
$finishedCount = $finishedMatches->count();

$remainingCountByTeam = [];
foreach ($allMatches as $m) {
    if ($m->status !== 'selesai') {
        $remainingCountByTeam[$m->tim_home] = ($remainingCountByTeam[$m->tim_home] ?? 0) + 1;
        $remainingCountByTeam[$m->tim_away] = ($remainingCountByTeam[$m->tim_away] ?? 0) + 1;
    }
}

// 1. === INFO LIGA & FORMAT BERITA ===
echo "=== INFO LIGA & FORMAT BERITA ===\n";
echo sprintf("Nama Liga          : %s\n", $liga->nama_liga);
echo sprintf("Musim              : %s\n", $liga->musim ?? '-');
echo sprintf("Status Liga        : %s\n", $liga->status ?? '-');
echo sprintf("Periode            : %s s/d %s\n", formatDateValue($liga->tanggal_mulai), formatDateValue($liga->tanggal_selesai));
echo sprintf("Jumlah Meja        : %d\n", (int) $liga->jumlah_meja);
echo sprintf("Hari Pertandingan  : %s\n", decodeHariPertandingan($liga->hari_pertandingan));
echo sprintf("Tanggal Libur      : %s\n", trim((string) $liga->tanggal_libur) !== '' ? $liga->tanggal_libur : '-');
echo sprintf("Total Tim Aktif    : %d\n", count($klasemen));
echo sprintf("Progres Laga       : %d / %d selesai\n", $finishedCount, $totalMatches);
echo sprintf("Kategori Berita    : %s\n", implode(', ', Berita::KATEGORI_LIST));
echo "Batas Form Admin   : judul <= 180 karakter | ringkasan <= 300 karakter | isi <= 20000 karakter (plain-text, dirender via nl2br(e()))\n\n";

// 2. === KLASEMEN LENGKAP ===
echo "=== KLASEMEN LENGKAP ===\n";
if ($klasemen === []) {
    echo "(Belum ada tim aktif di klasemen)\n\n";
} else {
    foreach ($klasemen as $k) {
        $sisa = $remainingCountByTeam[$k['id']] ?? 0;
        $wr = $k['mp'] > 0 ? round(($k['w'] / $k['mp']) * 100, 1) : 0.0;
        echo sprintf(
            "#%2d %-24s (%s & %s) | MP=%d SISA=%d W=%d L=%d WR=%.1f%% BM=%d BK=%d SB=%+d PTS=%d\n",
            $k['pos'],
            $k['nama_tim'],
            $k['pemain1'],
            $k['pemain2'],
            $k['mp'],
            $sisa,
            $k['w'],
            $k['l'],
            $wr,
            $k['bm'],
            $k['bk'],
            $k['sb'],
            $k['pts']
        );
    }
    echo "\n";
}

// 3. === TIM BELUM TERKALAHKAN & BELUM MAIN ===
echo "=== TIM BELUM TERKALAHKAN & BELUM MAIN ===\n";
$unbeatenTeams = array_values(array_filter($klasemen, fn(array $k): bool => $k['mp'] > 0 && $k['l'] === 0));
$notPlayedTeams = array_values(array_filter($klasemen, fn(array $k): bool => $k['mp'] === 0));

if ($unbeatenTeams === []) {
    echo "Tim Belum Terkalahkan (MP > 0, L = 0): Tidak ada.\n";
} else {
    echo "Tim Belum Terkalahkan (MP > 0, L = 0):\n";
    foreach ($unbeatenTeams as $ut) {
        $sisa = $remainingCountByTeam[$ut['id']] ?? 0;
        echo sprintf(
            "  - #%d %s (%s & %s) | MP=%d SISA=%d SB=%+d PTS=%d\n",
            $ut['pos'],
            $ut['nama_tim'],
            $ut['pemain1'],
            $ut['pemain2'],
            $ut['mp'],
            $sisa,
            $ut['sb'],
            $ut['pts']
        );
        foreach ($finishedMatches as $m) {
            if ((int) $m->tim_home === $ut['id'] || (int) $m->tim_away === $ut['id']) {
                $isHome = (int) $m->tim_home === $ut['id'];
                $oppId = $isHome ? (int) $m->tim_away : (int) $m->tim_home;
                $myScore = $isHome ? $m->skor_home : $m->skor_away;
                $oppScore = $isHome ? $m->skor_away : $m->skor_home;
                $oppName = $timNameById[$oppId] ?? "Tim#{$oppId}";
                echo sprintf("      [M] vs %s (%d - %d) | upd=%s\n", $oppName, $myScore, $oppScore, formatTimestampValue($m->updated_at));
            }
        }
    }
}

if ($notPlayedTeams === []) {
    echo "Tim Belum Bermain (MP = 0): Tidak ada.\n\n";
} else {
    echo "Tim Belum Bermain (MP = 0):\n";
    foreach ($notPlayedTeams as $np) {
        $sisa = $remainingCountByTeam[$np['id']] ?? 0;
        echo sprintf("  - #%d %s (%s & %s) | SISA=%d laga\n", $np['pos'], $np['nama_tim'], $np['pemain1'], $np['pemain2'], $sisa);
    }
    echo "\n";
}

// 4. === PERTANDINGAN TERBARU SELESAI ===
echo "=== PERTANDINGAN TERBARU SELESAI (urut updated_at DESC, id DESC; bukan tanggal jadwal) ===\n";
$recentSlice = $finishedMatches->take($options['recent']);
if ($recentSlice->isEmpty()) {
    echo "(Belum ada pertandingan selesai)\n\n";
} else {
    foreach ($recentSlice as $m) {
        $homeName = $timNameById[(int) $m->tim_home] ?? "Tim#{$m->tim_home}";
        $awayName = $timNameById[(int) $m->tim_away] ?? "Tim#{$m->tim_away}";
        $diff = abs((int) $m->skor_home - (int) $m->skor_away);
        echo sprintf(
            "  - [No.%s | R%s | %s | tgl:%s] %s (%d) vs (%d) %s | selisih=%d | upd=%s\n",
            $m->nomor_pertandingan ?? '-',
            $m->ronde ?? '-',
            $m->lokasi ?? '-',
            formatDateValue($m->tanggal),
            $homeName,
            $m->skor_home,
            $m->skor_away,
            $awayName,
            $diff,
            formatTimestampValue($m->updated_at)
        );
    }
    echo "\n";
}

// 5. === KEJUTAN & LAGA TIPIS ===
echo "=== KEJUTAN & LAGA TIPIS (maks 15 laga terbaru) ===\n";
$highlights = [];
foreach ($finishedMatches as $m) {
    $homeId = (int) $m->tim_home;
    $awayId = (int) $m->tim_away;
    $diff = abs((int) $m->skor_home - (int) $m->skor_away);
    $winnerId = (int) $m->pemenang_id;
    $loserId = $winnerId === $homeId ? $awayId : ($winnerId === $awayId ? $homeId : 0);

    $winnerPos = $posByTeamId[$winnerId] ?? null;
    $loserPos = $posByTeamId[$loserId] ?? null;

    $tags = [];
    if ($winnerPos !== null && $loserPos !== null && $winnerPos > $loserPos) {
        $tags[] = 'UPSET';
    }
    if ($diff <= 4) {
        $tags[] = 'TIPIS';
    }

    if ($tags !== []) {
        $homeName = $timNameById[$homeId] ?? "Tim#{$homeId}";
        $awayName = $timNameById[$awayId] ?? "Tim#{$awayId}";
        $winnerName = $timNameById[$winnerId] ?? '-';
        $highlights[] = sprintf(
            "  - [%s] %s (%d) vs (%d) %s | Menang: %s (#%s vs #%s) | selisih=%d | upd=%s",
            implode('+', $tags),
            $homeName,
            $m->skor_home,
            $m->skor_away,
            $awayName,
            $winnerName,
            $winnerPos !== null ? (string) $winnerPos : '-',
            $loserPos !== null ? (string) $loserPos : '-',
            $diff,
            formatTimestampValue($m->updated_at)
        );
        if (count($highlights) >= 15) {
            break;
        }
    }
}
echo $highlights !== [] ? implode("\n", $highlights) . "\n\n" : "(Belum ada laga kejutan atau selisih tipis <= 4)\n\n";

// 6. === DETAIL TIM, TREN & HEAD-TO-HEAD ===
$trendsList = TournamentService::analisisTrenTim($liga);
$trendsById = [];
foreach ($trendsList as $t) {
    if (is_array($t) && isset($t['id'])) {
        $trendsById[$t['id']] = $t;
    }
}

echo "=== DETAIL TIM, TREN & HEAD-TO-HEAD ===\n";
if ($options['teams'] !== []) {
    $matchedTeams = [];
    foreach ($klasemen as $k) {
        foreach ($options['teams'] as $needle) {
            if (mb_stripos($k['nama_tim'], $needle) !== false) {
                $matchedTeams[$k['id']] = $k;
                break;
            }
        }
    }

    if ($matchedTeams === []) {
        echo "Tidak ditemukan tim aktif yang cocok dengan filter: " . implode(', ', $options['teams']) . "\n\n";
    } else {
        foreach ($matchedTeams as $teamId => $teamRow) {
            $t = $trendsById[$teamId] ?? null;
            $sisa = $remainingCountByTeam[$teamId] ?? 0;
            echo sprintf(
                ">>> #%d %s (%s & %s) | MP=%d SISA=%d W=%d L=%d SB=%+d PTS=%d\n",
                $teamRow['pos'],
                $teamRow['nama_tim'],
                $teamRow['pemain1'],
                $teamRow['pemain2'],
                $teamRow['mp'],
                $sisa,
                $teamRow['w'],
                $teamRow['l'],
                $teamRow['sb'],
                $teamRow['pts']
            );
            if ($t !== null) {
                $formStr = $t['recent_form'] !== [] ? implode('-', $t['recent_form']) : '-';
                echo sprintf(
                    "    Tren: %s | Strength=%.2f | Form5=[%s] (recent=%.0f%%) | Streak=%s%d | AvgDiff=%+.1f | Proyeksi Sisa %d laga: %dM-%dK (avg %d%%)\n",
                    $t['trend_label'],
                    $t['strength'],
                    $formStr,
                    $t['recent_rate'],
                    $t['streak_type'] ?? '-',
                    $t['streak_count'],
                    $t['avg_diff'],
                    $t['remaining_count'],
                    $t['projected_wins'],
                    $t['projected_losses'],
                    $t['projected_win_pct']
                );
            }

            echo "    Riwayat Laga Selesai (terbaru lebih dulu via updated_at):\n";
            $teamFinishedCount = 0;
            foreach ($finishedMatches as $m) {
                if ((int) $m->tim_home === $teamId || (int) $m->tim_away === $teamId) {
                    $isHome = (int) $m->tim_home === $teamId;
                    $oppId = $isHome ? (int) $m->tim_away : (int) $m->tim_home;
                    $myScore = $isHome ? $m->skor_home : $m->skor_away;
                    $oppScore = $isHome ? $m->skor_away : $m->skor_home;
                    $res = (int) $m->pemenang_id === $teamId ? 'M' : 'K';
                    $oppName = $timNameById[$oppId] ?? "Tim#{$oppId}";
                    echo sprintf(
                        "      [%s] vs %-24s (%d - %d) | R%s No.%s | upd=%s\n",
                        $res,
                        $oppName,
                        $myScore,
                        $oppScore,
                        $m->ronde ?? '-',
                        $m->nomor_pertandingan ?? '-',
                        formatTimestampValue($m->updated_at)
                    );
                    $teamFinishedCount++;
                }
            }
            if ($teamFinishedCount === 0) {
                echo "      (Belum ada pertandingan selesai)\n";
            }
        }

        // Print pairwise Head-to-Head fixtures when 2+ matched teams are selected.
        $matchedIds = array_keys($matchedTeams);
        if (count($matchedIds) >= 2) {
            echo "--- Head-to-Head Antar Tim Terfilter ---\n";
            $h2hMatches = Pertandingan::where('liga_id', $liga->id)
                ->whereIn('tim_home', $matchedIds)
                ->whereIn('tim_away', $matchedIds)
                ->orderBy('ronde')
                ->orderBy('nomor_pertandingan')
                ->get();

            if ($h2hMatches->isEmpty()) {
                echo "  (Belum ada jadwal head-to-head antar tim terfilter)\n";
            } else {
                foreach ($h2hMatches as $hm) {
                    $hName = $timNameById[(int) $hm->tim_home] ?? "Tim#{$hm->tim_home}";
                    $aName = $timNameById[(int) $hm->tim_away] ?? "Tim#{$hm->tim_away}";
                    if ($hm->status === 'selesai') {
                        $wName = $timNameById[(int) $hm->pemenang_id] ?? '-';
                        echo sprintf(
                            "  - [SELESAI] R%s No.%s (%s, %s): %s (%d) vs (%d) %s | Menang: %s | upd=%s\n",
                            $hm->ronde ?? '-',
                            $hm->nomor_pertandingan ?? '-',
                            formatDateValue($hm->tanggal),
                            $hm->lokasi ?? '-',
                            $hName,
                            $hm->skor_home,
                            $hm->skor_away,
                            $aName,
                            $wName,
                            formatTimestampValue($hm->updated_at)
                        );
                    } else {
                        echo sprintf(
                            "  - [%s] R%s No.%s (%s %s, %s): %s vs %s\n",
                            strtoupper((string) $hm->status),
                            $hm->ronde ?? '-',
                            $hm->nomor_pertandingan ?? '-',
                            formatDateValue($hm->tanggal),
                            $hm->waktu ?? '-',
                            $hm->lokasi ?? '-',
                            $hName,
                            $aName
                        );
                    }
                }
            }
        }
        echo "\n";
    }
} else {
    echo "Ringkasan Tren Top 5 (berdasarkan strength dari TournamentService::analisisTrenTim):\n";
    foreach (array_slice($trendsList, 0, 5) as $t) {
        $formStr = $t['recent_form'] !== [] ? implode('-', $t['recent_form']) : '-';
        echo sprintf(
            "  - #%d %-24s | %s | Strength=%.2f | Form5=[%s] | Streak=%s%d | Proyeksi %d sisa: %dM-%dK (%d%%)\n",
            $t['pos'],
            $t['nama_tim'],
            $t['trend_label'],
            $t['strength'],
            $formStr,
            $t['streak_type'] ?? '-',
            $t['streak_count'],
            $t['remaining_count'],
            $t['projected_wins'],
            $t['projected_losses'],
            $t['projected_win_pct']
        );
    }
    echo "\n";
}

// 7. === JADWAL MENDATANG ===
if ($options['upcoming'] > 0) {
    echo "=== JADWAL MENDATANG ===\n";
    $upcomingMatches = Pertandingan::where('liga_id', $liga->id)
        ->where('status', '<>', 'selesai')
        ->orderBy('tanggal')
        ->orderBy('waktu')
        ->orderBy('id')
        ->take($options['upcoming'])
        ->get();

    if ($upcomingMatches->isEmpty()) {
        echo "(Semua pertandingan telah selesai)\n";
    } else {
        foreach ($upcomingMatches as $um) {
            $hName = $timNameById[(int) $um->tim_home] ?? "Tim#{$um->tim_home}";
            $aName = $timNameById[(int) $um->tim_away] ?? "Tim#{$um->tim_away}";
            echo sprintf(
                "  - [%s] R%s No.%s (%s %s, %s): %s vs %s\n",
                $um->status,
                $um->ronde ?? '-',
                $um->nomor_pertandingan ?? '-',
                formatDateValue($um->tanggal),
                $um->waktu ?? '-',
                $um->lokasi ?? '-',
                $hName,
                $aName
            );
        }
    }
}
