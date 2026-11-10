<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$liga = liga();
$jumlahTim = (int) $pdo->query('SELECT COUNT(*) FROM tim WHERE id_liga=' . (int) $liga['id'])->fetchColumn();
$st = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='selesai'),0) sel FROM pertandingan WHERE id_liga=?");
$st->execute([$liga['id']]);
$r = $st->fetch();
$total = (int) $r['total'];
$selesai = (int) $r['sel'];
$tampil = jadwal_tampil();
$hariIni = $tampil ? ambil_pertandingan('p.tanggal = ?', [date('Y-m-d')]) : [];
$kl = klasemen();

$judul = 'Dashboard';
$menu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<!-- Header / Hero Section -->
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-slate-200/80">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight"><?= h($liga['nama_liga']) ?></h1>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold <?= $liga['status'] === 'berjalan' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($liga['status'] === 'selesai' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200') ?>">
                <span class="w-2 h-2 rounded-full <?= $liga['status'] === 'berjalan' ? 'bg-indigo-500 animate-pulse' : ($liga['status'] === 'selesai' ? 'bg-emerald-500' : 'bg-slate-400') ?>"></span>
                <?= h(label_status_liga($liga['status'])) ?>
            </span>
        </div>
        <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
            <span>Musim <?= h($liga['musim']) ?></span>
            <span>&middot;</span>
            <span>Format: Single Round Robin</span>
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= h(url('jadwal.php')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
            <i class="bi bi-calendar3"></i> Jadwal Lengkap
        </a>
        <a href="<?= h(url('klasemen.php')) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
            <i class="bi bi-trophy"></i> Klasemen
        </a>
    </div>
</div>

<?php if ($liga['status'] === 'selesai' && $kl): ?>
    <!-- Juara Liga Showcase Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500 p-8 text-slate-900 mb-8 shadow-lg shadow-amber-500/20">
        <div class="absolute -right-6 -bottom-6 opacity-15 pointer-events-none text-9xl">
            <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="relative z-10 text-center max-w-xl mx-auto">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/30 backdrop-blur-md mb-3 text-white shadow-inner">
                <i class="bi bi-trophy-fill text-3xl text-amber-900"></i>
            </div>
            <div class="text-xs uppercase tracking-widest font-extrabold text-amber-950/80 mb-1">Juara Liga Dam Batu</div>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight mb-2"><?= h($kl[0]['nama_tim']) ?></h2>
            <div class="text-sm font-semibold text-amber-950/90 flex items-center justify-center gap-2">
                <span><i class="bi bi-people-fill mr-1"></i><?= h($kl[0]['pemain1']) ?> &amp; <?= h($kl[0]['pemain2']) ?></span>
                <span>&bull;</span>
                <span class="px-2.5 py-0.5 rounded-full bg-amber-900/10 text-xs font-bold"><?= (int) $kl[0]['pts'] ?> Poin</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 4 Key Metric Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Jumlah Tim</span>
            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">
                <i class="bi bi-people"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-slate-900"><?= (int) $jumlahTim ?></div>
        <div class="text-xs text-slate-400 mt-1">Tim terdaftar</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Total Match</span>
            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-base">
                <i class="bi bi-grid-3x3"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-slate-900"><?= (int) $total ?></div>
        <div class="text-xs text-slate-400 mt-1">Pertandingan liga</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Selesai</span>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                <i class="bi bi-check-circle"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-slate-900"><?= (int) $selesai ?></div>
        <div class="text-xs text-emerald-600 font-medium mt-1">
            <?= $total > 0 ? round(($selesai / $total) * 100) : 0 ?>% dari total
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
        <div class="flex items-center justify-between text-slate-500 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider">Tersisa</span>
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-slate-900"><?= max(0, $total - $selesai) ?></div>
        <div class="text-xs text-slate-400 mt-1">Belum dimainkan</div>
    </div>
</div>

<!-- 2 Columns: Today's Matches & Top Standings -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Today's Matches -->
    <div class="lg:col-span-7 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Pertandingan Hari Ini</h2>
            </div>
            <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">
                <?= h(hari_id(date('Y-m-d')) . ', ' . tgl(date('Y-m-d'))) ?>
            </span>
        </div>

        <?php if (!$tampil): ?>
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                <i class="bi bi-calendar-lock text-3xl text-slate-400 mb-2 block"></i>
                Jadwal liga belum dipublikasikan oleh administrator.
            </div>
        <?php else: ?>
            <?php tabel_pertandingan($hariIni, false); ?>
            <div class="text-right pt-1">
                <a href="<?= h(url('jadwal_hari.php')) ?>" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Lihat jadwal harian lainnya <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Leaderboard Top 5 -->
    <div class="lg:col-span-5 space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Klasemen Sementara</h2>
            </div>
            <a href="<?= h(url('klasemen.php')) ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Selengkapnya &rarr;
            </a>
        </div>

        <?php if (!$kl): ?>
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                <i class="bi bi-trophy text-3xl text-slate-400 mb-2 block"></i>
                Belum ada tim terdaftar.
            </div>
        <?php else: ?>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                        <tr>
                            <th scope="col" class="py-3 px-3 text-center w-10">Pos</th>
                            <th scope="col" class="py-3 px-3">Tim</th>
                            <th scope="col" class="py-3 px-2 text-center">MP</th>
                            <th scope="col" class="py-3 px-2 text-center">W</th>
                            <th scope="col" class="py-3 px-2 text-center font-bold" title="Selisih Poin (&Delta;)">Δ</th>
                            <th scope="col" class="py-3 px-3 text-center font-bold text-slate-800">PTS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach (array_slice($kl, 0, 5) as $k):
                            $isLeader = $k['pos'] === 1 && $k['mp'] > 0;
                        ?>
                            <tr class="hover:bg-slate-50/60 transition-colors <?= $isLeader ? 'bg-amber-50/40' : '' ?>">
                                <td class="py-3 px-3 text-center">
                                    <?php if ($k['pos'] === 1): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-bold text-xs">
                                            1
                                        </span>
                                    <?php elseif ($k['pos'] === 2): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                            2
                                        </span>
                                    <?php elseif ($k['pos'] === 3): ?>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 font-bold text-xs">
                                            3
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-slate-400"><?= $k['pos'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-3">
                                    <a class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors line-clamp-1" href="<?= h(url('tim.php?id=' . $k['id'])) ?>">
                                        <?= h($k['nama_tim']) ?>
                                    </a>
                                </td>
                                <td class="py-3 px-2 text-center text-xs text-slate-500 font-mono"><?= $k['mp'] ?></td>
                                <td class="py-3 px-2 text-center text-xs text-emerald-600 font-semibold font-mono"><?= $k['w'] ?></td>
                                <td class="py-3 px-2 text-center text-xs font-mono font-bold <?= $k['sb'] > 0 ? 'text-emerald-700' : ($k['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') ?>">
                                    <?= $k['sb'] > 0 ? '+' : '' ?><?= (int) $k['sb'] ?>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-slate-900 font-mono"><?= $k['pts'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center pt-1">
                <a href="<?= h(url('klasemen.php')) ?>" class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                    Lihat Klasemen Lengkap
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
