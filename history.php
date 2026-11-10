<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$fTanggal = $_GET['tanggal'] ?? '';
$fTim = (int) ($_GET['tim'] ?? 0);
$cari = trim($_GET['q'] ?? '');

$w = "p.status = 'selesai'";
$p = [];
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fTanggal)) { $w .= ' AND p.tanggal = ?'; $p[] = $fTanggal; } else { $fTanggal = ''; }
if ($fTim) { $w .= ' AND (p.tim_home = ? OR p.tim_away = ?)'; array_push($p, $fTim, $fTim); }
if ($cari !== '') {
    $w .= ' AND (h.nama_tim LIKE ? OR a.nama_tim LIKE ? OR h.pemain1 LIKE ? OR h.pemain2 LIKE ? OR a.pemain1 LIKE ? OR a.pemain2 LIKE ?)';
    for ($i = 0; $i < 6; $i++) { $p[] = '%' . $cari . '%'; }
}
$rows = ambil_pertandingan($w, $p, 'p.tanggal DESC, p.waktu DESC, p.nomor_pertandingan DESC');
$daftarTim = db()->query('SELECT id, nama_tim FROM tim WHERE id_liga=' . (int) liga()['id'] . ' ORDER BY nama_tim')->fetchAll();

$judul = 'History Pertandingan';
$menu = 'history';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">History Pertandingan</h1>
    <p class="text-sm text-slate-500 mt-1">Daftar seluruh pertandingan yang telah selesai beserta rincian pemenang.</p>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get">
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Tanggal</label>
            <input type="date" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" value="<?= h($fTanggal) ?>">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Tim</label>
            <select name="tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="0">Semua Tim</option>
                <?php foreach ($daftarTim as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $fTim === (int) $t['id'] ? 'selected' : '' ?>><?= h($t['nama_tim']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Pencarian</label>
            <div class="relative">
                <i class="bi bi-search absolute left-3.5 top-2.5 text-xs text-slate-400"></i>
                <input type="search" name="q" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-slate-800 form-input-focus" placeholder="Cari tim atau pemain..." value="<?= h($cari) ?>">
            </div>
        </div>
        <div class="sm:col-span-2 flex items-end">
            <button class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                <i class="bi bi-funnel"></i> Cari
            </button>
        </div>
    </form>
</div>

<?php if (!$rows): ?>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-clock-history text-4xl text-slate-400 mb-3 block"></i>
        Belum ada pertandingan yang selesai atau tidak ada data yang cocok.
    </div>
<?php else: ?>
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-4 px-4">Tanggal</th>
                        <th scope="col" class="py-4 px-3 text-center w-12">#</th>
                        <th scope="col" class="py-4 px-4">Pertandingan</th>
                        <th scope="col" class="py-4 px-4">Susunan Pemain</th>
                        <th scope="col" class="py-4 px-4">Hasil / Pemenang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($rows as $m): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-4 px-4 font-medium text-slate-700 whitespace-nowrap">
                                <div><?= h(tgl($m['tanggal'])) ?></div>
                                <div class="text-xs text-slate-400 font-mono"><?= h(jam($m['waktu'])) ?></div>
                            </td>
                            <td class="py-4 px-3 text-center font-mono text-xs text-slate-400">
                                #<?= (int) $m['nomor_pertandingan'] ?>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <a class="group inline-flex items-center gap-2 font-bold text-slate-900 hover:text-indigo-600 transition-colors" href="<?= h(url('pertandingan.php?id=' . $m['id'])) ?>">
                                    <span class="<?= $m['pemenang_id'] == $m['tim_home'] ? 'text-indigo-600 font-extrabold' : '' ?>"><?= h($m['nama_home']) ?></span>
                                    <span class="inline-flex items-center justify-center font-mono font-black text-xs px-2.5 py-0.5 rounded-md bg-slate-900 text-white tracking-wider shadow-xs">
                                        <?= (int) ($m['skor_home'] ?? 0) ?> - <?= (int) ($m['skor_away'] ?? 0) ?>
                                    </span>
                                    <span class="<?= $m['pemenang_id'] == $m['tim_away'] ? 'text-indigo-600 font-extrabold' : '' ?>"><?= h($m['nama_away']) ?></span>
                                    <i class="bi bi-chevron-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"></i>
                                </a>
                            </td>
                            <td class="py-4 px-4 text-xs text-slate-500">
                                <div class="truncate max-w-xs">
                                    <span class="font-medium text-slate-700"><?= h($m['nama_home']) ?>:</span> <?= h($m['home_p1']) ?> &amp; <?= h($m['home_p2']) ?>
                                </div>
                                <div class="truncate max-w-xs mt-0.5">
                                    <span class="font-medium text-slate-700"><?= h($m['nama_away']) ?>:</span> <?= h($m['away_p1']) ?> &amp; <?= h($m['away_p2']) ?>
                                </div>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="bi bi-trophy-fill text-emerald-500 text-xs"></i>
                                    <span><?= h($m['nama_pemenang']) ?></span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
