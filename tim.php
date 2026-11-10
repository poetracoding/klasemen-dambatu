<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM tim WHERE id=? AND id_liga=?');
$st->execute([$id, liga()['id']]);
$tim = $st->fetch();

if (!$tim) {
    http_response_code(404);
    $judul = 'Tim tidak ditemukan';
    $menu = 'klasemen';
    require __DIR__ . '/includes/header.php';
    echo '<div class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center text-sm font-semibold text-rose-700">Tim tidak ditemukan.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stat = null;
foreach (klasemen() as $k) {
    if ((int) $k['id'] === $id) {
        $stat = $k;
    }
}
$rows = jadwal_tampil() ? ambil_pertandingan('p.tim_home = ? OR p.tim_away = ?', [$id, $id]) : [];

$judul = $tim['nama_tim'];
$menu = 'klasemen';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
    <a href="<?= h(url('klasemen.php')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
        <i class="bi bi-arrow-left"></i> Kembali ke Klasemen
    </a>
</div>

<!-- Team Profile Card -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-8 mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white text-2xl font-black shadow-md shadow-indigo-500/20">
                <?= strtoupper(substr($tim['nama_tim'], 0, 2)) ?>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"><?= h($tim['nama_tim']) ?></h1>
                <div class="text-xs text-slate-500 flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                        <i class="bi bi-person-fill text-indigo-500"></i> <?= h($tim['pemain1']) ?>
                    </span>
                    <span>&amp;</span>
                    <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                        <i class="bi bi-person-fill text-indigo-500"></i> <?= h($tim['pemain2']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php if ($stat): ?>
            <div class="flex items-center gap-2">
                <span class="px-3.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold shadow-xs">
                    Peringkat #<?= (int) $stat['pos'] ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stat Metrics -->
    <?php if ($stat): ?>
        <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 pt-6">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 text-center">
                <div class="text-xs uppercase font-semibold text-slate-400">Posisi</div>
                <div class="text-2xl font-black text-slate-800 mt-1"><?= (int) $stat['pos'] ?></div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 text-center">
                <div class="text-xs uppercase font-semibold text-slate-400">Main (MP)</div>
                <div class="text-2xl font-black text-slate-800 mt-1"><?= (int) $stat['mp'] ?></div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 text-center">
                <div class="text-xs uppercase font-semibold text-emerald-600">Menang (W)</div>
                <div class="text-2xl font-black text-emerald-600 mt-1"><?= (int) $stat['w'] ?></div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 text-center">
                <div class="text-xs uppercase font-semibold text-rose-500">Kalah (L)</div>
                <div class="text-2xl font-black text-rose-500 mt-1"><?= (int) $stat['l'] ?></div>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/60 text-center">
                <div class="text-xs uppercase font-semibold text-slate-500" title="Poin Masuk (+) - Keluar (-)">Selisih (&Delta;)</div>
                <div class="text-2xl font-black <?= $stat['sb'] > 0 ? 'text-emerald-700' : ($stat['sb'] < 0 ? 'text-rose-600' : 'text-slate-800') ?> mt-1">
                    <?= $stat['sb'] > 0 ? '+' : '' ?><?= (int) $stat['sb'] ?>
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-indigo-50 border border-indigo-100 text-center col-span-2 sm:col-span-1">
                <div class="text-xs uppercase font-semibold text-indigo-700">Poin (PTS)</div>
                <div class="text-2xl font-black text-indigo-700 mt-1"><?= (int) $stat['pts'] ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Team Matches Section -->
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Jadwal & Hasil Pertandingan Tim</h2>
        <span class="text-xs text-slate-400"><?= count($rows) ?> Pertandingan</span>
    </div>
    <?php tabel_pertandingan($rows); ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
