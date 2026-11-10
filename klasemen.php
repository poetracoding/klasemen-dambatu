<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$liga = liga();
$kl = klasemen();
$judul = 'Klasemen';
$menu = 'klasemen';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Klasemen Liga</h1>
        <p class="text-sm text-slate-500 mt-1">Perolehan poin, rasio batu masuk/kebobolan, dan selisih skor seluruh tim.</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors print:hidden">
            <i class="bi bi-printer"></i> Cetak Klasemen
        </button>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-xs">
            <i class="bi bi-info-circle text-indigo-500"></i>
            <span>3 Poin Menang &middot; 0 Kalah</span>
        </div>
    </div>
</div>

<?php if ($liga['status'] === 'selesai' && $kl): ?>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 p-6 mb-6 shadow-md shadow-amber-500/20 text-slate-950 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-white/40 flex items-center justify-center text-amber-950 shadow-inner flex-shrink-0">
            <i class="bi bi-trophy-fill text-3xl"></i>
        </div>
        <div>
            <div class="text-xs uppercase tracking-widest font-extrabold text-amber-900/80">Juara Liga Dam Batu</div>
            <div class="text-2xl font-black tracking-tight"><?= h($kl[0]['nama_tim']) ?></div>
            <div class="text-xs font-semibold text-amber-950/80 mt-0.5">
                <?= h($kl[0]['pemain1']) ?> &amp; <?= h($kl[0]['pemain2']) ?> &middot; Total <?= (int) $kl[0]['pts'] ?> Poin (Selisih: <?= $kl[0]['sb'] > 0 ? '+' : '' ?><?= (int) $kl[0]['sb'] ?>)
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!$kl): ?>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-people text-4xl text-slate-400 mb-3 block"></i>
        Belum ada tim terdaftar.
    </div>
<?php else: ?>
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs mb-4">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-4 px-3 sm:px-4 text-center w-12 sm:w-14">Pos</th>
                        <th scope="col" class="py-4 px-4 min-w-44">Tim &amp; Pemain</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Matches Played (Main)">MP</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Won (Menang)">W</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Lost (Kalah)">L</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center text-emerald-700 font-bold" title="Poin Masuk (+)">+</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center text-rose-600 font-bold" title="Poin Keluar (-)">-</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center font-bold" title="Selisih Poin (&Delta;)">Δ</th>
                        <th scope="col" class="py-4 px-3 sm:px-4 text-center font-bold text-slate-900 bg-slate-100/50" title="Total Poin Liga">PTS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($kl as $k):
                        $isLeader = $k['pos'] === 1 && $k['mp'] > 0;
                    ?>
                        <tr class="hover:bg-slate-50/70 transition-colors <?= $isLeader ? 'bg-amber-50/40' : '' ?>">
                            <td class="py-4 px-3 sm:px-4 text-center">
                                <?php if ($k['pos'] === 1): ?>
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-800 font-extrabold text-xs shadow-xs">
                                        <i class="bi bi-trophy-fill text-xs mr-0.5"></i> 1
                                    </span>
                                <?php elseif ($k['pos'] === 2): ?>
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                        2
                                    </span>
                                <?php elseif ($k['pos'] === 3): ?>
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-orange-100 text-orange-800 font-bold text-xs">
                                        3
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs font-semibold text-slate-400"><?= $k['pos'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <a class="font-bold text-slate-900 hover:text-indigo-600 transition-colors block text-base" href="<?= h(url('tim.php?id=' . $k['id'])) ?>">
                                    <?= h($k['nama_tim']) ?>
                                </a>
                                <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5">
                                    <i class="bi bi-person text-slate-400"></i>
                                    <span><?= h($k['pemain1']) ?> &amp; <?= h($k['pemain2']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono text-slate-600 font-medium"><?= $k['mp'] ?></td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono text-emerald-600 font-bold"><?= $k['w'] ?></td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono text-rose-500 font-medium"><?= $k['l'] ?></td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono text-emerald-700 font-semibold"><?= (int) $k['bm'] ?></td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono text-rose-600 font-semibold"><?= (int) $k['bk'] ?></td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold <?= $k['sb'] > 0 ? 'text-emerald-700' : ($k['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') ?>">
                                <?= $k['sb'] > 0 ? '+' : '' ?><?= (int) $k['sb'] ?>
                            </td>
                            <td class="py-4 px-3 sm:px-4 text-center font-mono text-lg font-black text-slate-900 bg-slate-50/50"><?= $k['pts'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 text-xs text-slate-500 flex items-start gap-2.5">
        <i class="bi bi-info-circle-fill text-indigo-500 text-sm mt-0.5 flex-shrink-0"></i>
        <div>
            <strong>Keterangan Kolom:</strong> <strong>MP</strong> = Main (Matches Played), <strong>W</strong> = Menang, <strong>L</strong> = Kalah, <strong>+</strong> = Poin Masuk, <strong>-</strong> = Poin Keluar, <strong>&Delta;</strong> = Selisih Poin (+ dikurangi -), <strong>PTS</strong> = Poin Liga (Menang = 3 poin).<br>
            <strong>Urutan Peringkat:</strong> Poin (PTS) &rarr; Jumlah Menang (W) &rarr; Selisih Poin (&Delta;) &rarr; Poin Masuk (+) &rarr; Pertemuan Langsung (H2H) &rarr; Nama Tim.
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
