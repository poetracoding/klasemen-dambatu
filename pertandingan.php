<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$rows = jadwal_tampil() ? ambil_pertandingan('p.id = ?', [$id]) : [];
$m = $rows[0] ?? null;

$judul = 'Detail Pertandingan';
$menu = 'jadwal';
require __DIR__ . '/includes/header.php';

if (!$m) {
    http_response_code(404);
    echo '<div class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center text-sm font-semibold text-rose-700">Pertandingan tidak ditemukan.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
?>

<div class="mb-6">
    <a href="<?= h(url('jadwal.php')) ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
        <i class="bi bi-arrow-left"></i> Kembali ke Jadwal Liga
    </a>
</div>

<!-- Match Arena Card -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
    <!-- Top Bar -->
    <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
            <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/60">Match #<?= (int) $m['nomor_pertandingan'] ?></span>
            <span>&middot;</span>
            <span>Ronde <?= (int) $m['ronde'] ?></span>
        </div>
        <div>
            <?= badge_status($m['status']) ?>
        </div>
    </div>

    <!-- Main VS Arena Showcase -->
    <div class="p-8 sm:p-12">
        <div class="grid grid-cols-1 md:grid-cols-12 items-center gap-6">
            <!-- Team Home -->
            <div class="md:col-span-4 text-center md:text-right space-y-2">
                <a class="text-2xl sm:text-3xl font-black text-slate-900 hover:text-indigo-600 transition-colors tracking-tight block" href="<?= h(url('tim.php?id=' . $m['tim_home'])) ?>">
                    <?= h($m['nama_home']) ?>
                </a>
                <div class="text-xs text-slate-500 flex items-center justify-center md:justify-end gap-1.5">
                    <i class="bi bi-people"></i>
                    <span><?= h($m['home_p1']) ?> &amp; <?= h($m['home_p2']) ?></span>
                </div>
                <?php if ($m['status'] === 'selesai' && $m['pemenang_id'] == $m['tim_home']): ?>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs">
                            <i class="bi bi-trophy-fill text-xs text-emerald-600"></i> Pemenang
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Scoreboard Center -->
            <div class="md:col-span-4 text-center my-2 md:my-0 flex flex-col items-center justify-center">
                <?php if ($m['status'] === 'selesai' || $m['status'] === 'berlangsung'): ?>
                    <div class="inline-flex items-center gap-3 px-6 py-2.5 rounded-2xl bg-slate-900 text-white font-mono font-black text-3xl sm:text-4xl shadow-md shadow-slate-900/20 tracking-wider">
                        <span class="<?= $m['pemenang_id'] == $m['tim_home'] ? 'text-emerald-400' : '' ?>"><?= (int) ($m['skor_home'] ?? 0) ?></span>
                        <span class="text-slate-500 text-2xl font-light">:</span>
                        <span class="<?= $m['pemenang_id'] == $m['tim_away'] ? 'text-emerald-400' : '' ?>"><?= (int) ($m['skor_away'] ?? 0) ?></span>
                    </div>
                    <div class="text-3xs uppercase tracking-widest text-slate-400 font-bold mt-2">
                        <?= $m['status'] === 'berlangsung' ? 'Sedang Bertanding' : 'Poin Skor Akhir' ?>
                    </div>
                <?php else: ?>
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center font-black text-base tracking-wider border border-slate-200 shadow-inner">
                        VS
                    </div>
                    <div class="text-3xs uppercase tracking-widest text-slate-400 font-bold mt-2">Belum Bertanding</div>
                <?php endif; ?>
            </div>

            <!-- Team Away -->
            <div class="md:col-span-4 text-center md:text-left space-y-2">
                <a class="text-2xl sm:text-3xl font-black text-slate-900 hover:text-indigo-600 transition-colors tracking-tight block" href="<?= h(url('tim.php?id=' . $m['tim_away'])) ?>">
                    <?= h($m['nama_away']) ?>
                </a>
                <div class="text-xs text-slate-500 flex items-center justify-center md:justify-start gap-1.5">
                    <i class="bi bi-people"></i>
                    <span><?= h($m['away_p1']) ?> &amp; <?= h($m['away_p2']) ?></span>
                </div>
                <?php if ($m['status'] === 'selesai' && $m['pemenang_id'] == $m['tim_away']): ?>
                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs">
                            <i class="bi bi-trophy-fill text-xs text-emerald-600"></i> Pemenang
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($m['status'] === 'selesai' && $m['nama_pemenang']): ?>
            <div class="mt-8 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-center max-w-md mx-auto">
                <div class="text-xs uppercase tracking-wider font-extrabold text-emerald-700 mb-0.5">Hasil Pertandingan</div>
                <div class="text-lg font-black"><?= h($m['nama_pemenang']) ?> Memenangkan Pertandingan!</div>
                <div class="text-xs text-emerald-700 mt-1">Skor Akhir: <?= (int) ($m['skor_home'] ?? 0) ?> - <?= (int) ($m['skor_away'] ?? 0) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Match Information Grid Footer -->
    <div class="bg-slate-50/70 border-t border-slate-200/80 px-6 py-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-3 bg-white rounded-xl border border-slate-200/60 shadow-2xs">
                <div class="text-xs text-slate-400 font-medium">Tanggal Pelaksanaan</div>
                <div class="text-sm font-bold text-slate-800 mt-0.5">
                    <?= $m['tanggal'] ? h(hari_id($m['tanggal']) . ', ' . tgl($m['tanggal'])) : '-' ?>
                </div>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-200/60 shadow-2xs">
                <div class="text-xs text-slate-400 font-medium">Waktu / Jam</div>
                <div class="text-sm font-bold text-slate-800 font-mono mt-0.5"><?= h(jam($m['waktu'])) ?> WIB</div>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-200/60 shadow-2xs">
                <div class="text-xs text-slate-400 font-medium">Lokasi / Meja</div>
                <div class="text-sm font-bold text-slate-800 mt-0.5"><?= h($m['lokasi'] ?: '-') ?></div>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-200/60 shadow-2xs">
                <div class="text-xs text-slate-400 font-medium">Status Match</div>
                <div class="mt-0.5"><?= badge_status($m['status']) ?></div>
            </div>
        </div>

        <?php if ($m['catatan']): ?>
            <div class="mt-4 p-3 bg-white rounded-xl border border-slate-200/60 text-xs text-slate-600">
                <span class="font-bold text-slate-700">Catatan Khusus:</span> <?= h($m['catatan']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
