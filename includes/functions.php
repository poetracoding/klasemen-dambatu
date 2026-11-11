<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const STATUS_MATCH = [
    'belum_main'  => 'Belum Main',
    'berlangsung' => 'Berlangsung',
    'selesai'     => 'Selesai',
    'ditunda'     => 'Ditunda',
    'dibatalkan'  => 'Dibatalkan',
];

/* ---------- Helper umum ---------- */
function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $p = ''): string
{
    return BASE_URL . '/' . ltrim($p, '/');
}
function redirect(string $p)
{
    header('Location: ' . url($p));
    exit;
}
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['t' => $type, 'm' => $msg];
}
function flash_ambil(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
function csrf_cek(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function tgl(?string $d): string
{
    $t = $d ? strtotime($d) : false;
    return $t ? date('d-m-Y', $t) : '-';
}
function hari_id(string $d): string
{
    $n = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $n[(int) date('w', strtotime($d))];
}
function jam(?string $w): string
{
    return $w ? substr($w, 0, 5) : '-';
}

function badge_status(string $s): string
{
    $badges = [
        'belum_main'  => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">Belum Main</span>',
        'berlangsung' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse"><span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>Berlangsung</span>',
        'selesai'     => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Selesai</span>',
        'ditunda'     => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">Ditunda</span>',
        'dibatalkan'  => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">Dibatalkan</span>',
    ];
    return $badges[$s] ?? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">' . h(STATUS_MATCH[$s] ?? $s) . '</span>';
}

/* ---------- Liga ---------- */
function liga(): array
{
    return db()->query('SELECT * FROM liga ORDER BY id LIMIT 1')->fetch();
}
function jadwal_tampil(): bool
{
    return liga()['status'] !== 'draft' || !empty($_SESSION['admin_id']);
}
function label_status_liga(string $s): string
{
    return ['draft' => 'Draft', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'][$s] ?? $s;
}
function total_pertandingan_rumus(int $n): int
{
    return intdiv($n * ($n - 1), 2);
}
/** Liga otomatis "selesai" bila semua pertandingan selesai, kembali "berjalan" bila ada yang diubah. */
function sinkron_status_liga(): void
{
    $l = liga();
    if ($l['status'] === 'draft') {
        return;
    }
    $pdo = db();
    $st = $pdo->prepare("SELECT COUNT(*) total, SUM(status='selesai') sel FROM pertandingan WHERE id_liga=?");
    $st->execute([$l['id']]);
    $r = $st->fetch();
    $baru = ((int) $r['total'] > 0 && (int) $r['total'] === (int) $r['sel']) ? 'selesai' : 'berjalan';
    if ($baru !== $l['status']) {
        $pdo->prepare('UPDATE liga SET status=? WHERE id=?')->execute([$baru, $l['id']]);
    }
}

/* ---------- Round Robin (circle method, mendukung ganjil/genap) ---------- */
function round_robin(array $ids): array
{
    $teams = array_values($ids);
    if (count($teams) % 2) {
        $teams[] = null; // bye
    }
    $n = count($teams);
    $rounds = [];
    for ($r = 0; $r < $n - 1; $r++) {
        $pairs = [];
        for ($i = 0; $i < $n / 2; $i++) {
            $a = $teams[$i];
            $b = $teams[$n - 1 - $i];
            if ($a !== null && $b !== null) {
                $pairs[] = [$a, $b];
            }
        }
        $rounds[] = $pairs;
        $last = array_pop($teams);
        array_splice($teams, 1, 0, [$last]);
    }
    return $rounds;
}

/** Buat ulang seluruh jadwal liga. $o: mulai (Y-m-d), per_hari, jumlah_meja, jam (H:i), durasi (menit), lokasi. */
function buat_jadwal(array $o): int
{
    $pdo = db();
    $l = liga();
    $st = $pdo->prepare('SELECT id FROM tim WHERE id_liga=? ORDER BY id');
    $st->execute([$l['id']]);
    $teamIds = $st->fetchAll(PDO::FETCH_COLUMN);
    $rounds = round_robin($teamIds);

    $jumlahMeja = max(1, (int) ($o['jumlah_meja'] ?? 1));
    $prefixLokasi = trim($o['lokasi'] ?? '') ?: 'Meja';
    $perHari = max(1, (int) ($o['per_hari'] ?? 4));
    $durasi = max(10, (int) ($o['durasi'] ?? 45));
    $jamMulai = ($o['jam'] ?? '') ?: '19:00';
    $tglMulai = ($o['mulai'] ?? '') ?: date('Y-m-d');

    // Penentuan Hari Pertandingan (1=Senin, 7=Minggu)
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

    // Pastikan tanggal mulai jatuh pada hari pertandingan yang dipilih
    $currentDateTs = strtotime($tglMulai);
    while (!in_array((int) date('N', $currentDateTs), $hariDipilih, true)) {
        $currentDateTs = strtotime('+1 day', $currentDateTs);
    }

    $matchesToday = 0;
    $slotIndex = 0;
    $no = 0;

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM pertandingan WHERE id_liga=?')->execute([$l['id']]);
        $pdo->prepare('UPDATE liga SET jumlah_meja=?, hari_pertandingan=? WHERE id=?')->execute([$jumlahMeja, $hariPertandinganStr, $l['id']]);
        $ins = $pdo->prepare('INSERT INTO pertandingan
            (id_liga, ronde, nomor_pertandingan, tanggal, waktu, lokasi, tim_home, tim_away, skor_home, skor_away, status)
            VALUES (?,?,?,?,?,?,?,?,0,0,\'belum_main\')');

        while (!empty($matchList)) {
            $matchesInThisSlot = 0;
            $teamsInSlot = [];

            while ($matchesInThisSlot < $maxMatchesPerSlot && $matchesToday < $perHari && !empty($matchList)) {
                // Cari pertandingan pertama yang timnya belum bermain di slot waktu yang sama
                $foundKey = null;
                foreach ($matchList as $k => $m) {
                    if (!in_array($m['a'], $teamsInSlot, true) && !in_array($m['b'], $teamsInSlot, true)) {
                        $foundKey = $k;
                        break;
                    }
                }

                if ($foundKey === null) {
                    // Tidak ada lagi pasangan tim yang bebas di slot ini, lanjut ke slot berikutnya
                    break;
                }

                $m = $matchList[$foundKey];
                unset($matchList[$foundKey]);
                $matchList = array_values($matchList);

                $no++;
                $tanggal = date('Y-m-d', $currentDateTs);
                $waktu = date('H:i', strtotime($jamMulai) + $slotIndex * $durasi * 60);

                // Gunakan meja yang sudah kosong (mulai dari Meja 1).
                // Meja 2, 3, dst hanya dipakai jika meja sebelumnya sedang digunakan di slot waktu yang sama.
                $mejaIndex = $matchesInThisSlot + 1;
                $lokasi = $jumlahMeja > 1 ? "$prefixLokasi $mejaIndex" : $prefixLokasi;

                $ins->execute([$l['id'], $m['ronde'], $no, $tanggal, $waktu, $lokasi, $m['a'], $m['b']]);

                $teamsInSlot[] = $m['a'];
                $teamsInSlot[] = $m['b'];
                $matchesInThisSlot++;
                $matchesToday++;
            }

            // Maju ke slot waktu berikutnya
            $slotIndex++;
            if ($matchesToday >= $perHari) {
                // Lompat ke hari pertandingan berikutnya yang diperbolehkan
                do {
                    $currentDateTs = strtotime('+1 day', $currentDateTs);
                } while (!in_array((int) date('N', $currentDateTs), $hariDipilih, true));

                $matchesToday = 0;
                $slotIndex = 0;
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $no;
}

/* ---------- Pertandingan ---------- */
function ambil_pertandingan(string $where = '1=1', array $p = [], string $order = 'p.tanggal, p.waktu, p.nomor_pertandingan'): array
{
    $sql = "SELECT p.*, h.nama_tim AS nama_home, h.pemain1 AS home_p1, h.pemain2 AS home_p2,
                   a.nama_tim AS nama_away, a.pemain1 AS away_p1, a.pemain2 AS away_p2,
                   w.nama_tim AS nama_pemenang
            FROM pertandingan p
            JOIN tim h ON h.id = p.tim_home
            JOIN tim a ON a.id = p.tim_away
            LEFT JOIN tim w ON w.id = p.pemenang_id
            WHERE p.id_liga = ? AND ($where) ORDER BY $order";
    $st = db()->prepare($sql);
    $st->execute(array_merge([liga()['id']], $p));
    return $st->fetchAll();
}

function tabel_pertandingan(array $rows, bool $tampilTanggal = true): void
{
    if (!$rows) {
        echo '<div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
            <i class="bi bi-calendar-x text-2xl text-slate-400 mb-2 block"></i>
            Tidak ada pertandingan.
        </div>';
        return;
    }
    echo '<div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">';
    echo '<div class="overflow-x-auto">';
    echo '<table class="w-full min-w-[700px] text-left text-sm text-slate-600 divide-y divide-slate-100">';
    echo '<thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500"><tr>';
    echo '<th scope="col" class="py-3.5 px-4 text-center w-12">#</th>';
    if ($tampilTanggal) {
        echo '<th scope="col" class="py-3.5 px-4">Tanggal</th>';
    }
    echo '<th scope="col" class="py-3.5 px-4">Jam</th>';
    echo '<th scope="col" class="py-3.5 px-4">Pertandingan & Skor</th>';
    echo '<th scope="col" class="py-3.5 px-4">Lokasi</th>';
    echo '<th scope="col" class="py-3.5 px-4">Status</th>';
    echo '<th scope="col" class="py-3.5 px-4">Hasil</th>';
    echo '</tr></thead><tbody class="divide-y divide-slate-100">';
    foreach ($rows as $m) {
        $skorAda = ($m['status'] === 'selesai' || $m['status'] === 'berlangsung');
        $hasil = ($m['status'] === 'selesai' && $m['nama_pemenang'])
            ? '<span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60"><i class="bi bi-trophy text-xs"></i> ' . h($m['nama_pemenang']) . ' Menang</span>' : '<span class="text-slate-400">-</span>';
        
        echo '<tr class="hover:bg-slate-50/70 transition-colors">';
        echo '<td class="py-3.5 px-4 text-center font-medium text-slate-400">' . (int) $m['nomor_pertandingan'] . '</td>';
        if ($tampilTanggal) {
            echo '<td class="py-3.5 px-4 font-medium text-slate-700 whitespace-nowrap">' . ($m['tanggal'] ? h(hari_id($m['tanggal']) . ', ' . tgl($m['tanggal'])) : '-') . '</td>';
        }
        echo '<td class="py-3.5 px-4 text-slate-600 whitespace-nowrap font-mono text-xs">' . h(jam($m['waktu'])) . '</td>';
        echo '<td class="py-3.5 px-4 whitespace-nowrap"><a class="group inline-flex items-center gap-2 font-semibold text-slate-800 hover:text-indigo-600 transition-colors" href="' . h(url('pertandingan.php?id=' . $m['id'])) . '">';
        
        // Nama Home
        echo '<span class="' . ($m['status'] === 'selesai' && $m['pemenang_id'] == $m['tim_home'] ? 'text-indigo-600 font-extrabold' : '') . '">' . h($m['nama_home']) . '</span>';
        
        // Papan Skor / vs
        if ($skorAda) {
            echo '<span class="inline-flex items-center justify-center font-mono font-black text-xs px-2.5 py-0.5 rounded-md bg-slate-900 text-white tracking-wider shadow-xs">' 
                . (int) ($m['skor_home'] ?? 0) . ' - ' . (int) ($m['skor_away'] ?? 0) . '</span>';
        } else {
            echo '<span class="text-xs font-normal text-slate-400 px-1.5 py-0.5 rounded bg-slate-100">vs</span>';
        }
        
        // Nama Away
        echo '<span class="' . ($m['status'] === 'selesai' && $m['pemenang_id'] == $m['tim_away'] ? 'text-indigo-600 font-extrabold' : '') . '">' . h($m['nama_away']) . '</span>';
        echo '<i class="bi bi-chevron-right opacity-0 -ml-1 group-hover:opacity-100 group-hover:ml-0 transition-all text-xs"></i></a></td>';
        
        echo '<td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">' . h($m['lokasi'] ?: '-') . '</td>';
        echo '<td class="py-3.5 px-4 whitespace-nowrap">' . badge_status($m['status']) . '</td>';
        echo '<td class="py-3.5 px-4 whitespace-nowrap">' . $hasil . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';
}

/* ---------- Klasemen ---------- */
function klasemen(?int $idLiga = null): array
{
    $pdo = db();
    $idLiga = $idLiga ?? (int) liga()['id'];

    $st = $pdo->prepare('SELECT id, nama_tim, pemain1, pemain2 FROM tim WHERE id_liga=? AND status=1');
    $st->execute([$idLiga]);
    $rows = [];
    foreach ($st->fetchAll() as $t) {
        $rows[$t['id']] = $t + ['mp' => 0, 'w' => 0, 'l' => 0, 'bm' => 0, 'bk' => 0, 'sb' => 0, 'pts' => 0];
    }

    $st = $pdo->prepare("SELECT tim_home, tim_away, pemenang_id, COALESCE(skor_home,0) sh, COALESCE(skor_away,0) sa 
                         FROM pertandingan
                         WHERE id_liga=? AND status='selesai'");
    $st->execute([$idLiga]);
    $h2h = [];
    foreach ($st->fetchAll() as $m) {
        $h = (int) $m['tim_home'];
        $a = (int) $m['tim_away'];
        $sh = (int) $m['sh'];
        $sa = (int) $m['sa'];
        $menang = (int) ($m['pemenang_id'] ?? 0);

        if (!isset($rows[$h], $rows[$a])) {
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
    // Urut: poin (PTS), lalu menang (W), lalu selisih skor (SB), lalu skor memasukkan (BM), lalu nama tim
    usort($list, function ($a, $b) {
        if ($a['pts'] !== $b['pts']) return $b['pts'] <=> $a['pts'];
        if ($a['w'] !== $b['w']) return $b['w'] <=> $a['w'];
        if ($a['sb'] !== $b['sb']) return $b['sb'] <=> $a['sb'];
        if ($a['bm'] !== $b['bm']) return $b['bm'] <=> $a['bm'];
        return strcasecmp($a['nama_tim'], $b['nama_tim']);
    });

    $hasil = [];
    $i = 0;
    $n = count($list);
    while ($i < $n) {
        $j = $i;
        while ($j + 1 < $n && $list[$j + 1]['pts'] === $list[$i]['pts'] && $list[$j + 1]['w'] === $list[$i]['w'] && $list[$j + 1]['sb'] === $list[$i]['sb']) {
            $j++;
        }
        $grup = array_slice($list, $i, $j - $i + 1);
        if (count($grup) > 1) {
            $ids = array_column($grup, 'id');
            foreach ($grup as &$g) {
                $g['h2h'] = 0;
                foreach ($ids as $o) {
                    if ($o !== $g['id']) {
                        $g['h2h'] += $h2h[$g['id']][$o] ?? 0;
                    }
                }
            }
            unset($g);
            usort($grup, function ($a, $b) {
                return $a['h2h'] !== $b['h2h'] ? $b['h2h'] <=> $a['h2h'] : strcasecmp($a['nama_tim'], $b['nama_tim']);
            });
        }
        foreach ($grup as $g) {
            $hasil[] = $g;
        }
        $i = $j + 1;
    }
    foreach ($hasil as $k => &$r) {
        $r['pos'] = $k + 1;
    }
    return $hasil;
}
