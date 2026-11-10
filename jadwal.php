<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$fTim = (int) ($_GET['tim'] ?? 0);
$fStatus = $_GET['status'] ?? '';
$tampil = jadwal_tampil();
$grup = [];
if ($tampil) {
    $w = '1=1';
    $p = [];
    if ($fTim) { $w .= ' AND (p.tim_home = ? OR p.tim_away = ?)'; array_push($p, $fTim, $fTim); }
    if (isset(STATUS_MATCH[$fStatus])) { $w .= ' AND p.status = ?'; $p[] = $fStatus; }
    foreach (ambil_pertandingan($w, $p, 'p.tanggal IS NULL, p.tanggal, p.waktu, p.nomor_pertandingan') as $m) {
        $grup[$m['tanggal'] ?: ''][] = $m;
    }
}
$daftarTim = db()->query('SELECT id, nama_tim FROM tim WHERE id_liga=' . (int) liga()['id'] . ' ORDER BY nama_tim')->fetchAll();

$judul = 'Jadwal Liga';
$menu = 'jadwal';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Seluruh Liga</h1>
        <p class="text-sm text-slate-500 mt-1">Daftar pertandingan lengkap single round-robin.</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs print:hidden">
            <i class="bi bi-printer"></i> Cetak Jadwal
        </button>
        <a href="<?= h(url('jadwal_hari.php')) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs print:hidden">
            <i class="bi bi-calendar-day"></i> Jadwal Harian
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8 print:hidden">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get">
        <div class="sm:col-span-5">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Filter Tim</label>
            <select name="tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="0">Semua Tim</option>
                <?php foreach ($daftarTim as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $fTim === (int) $t['id'] ? 'selected' : '' ?>><?= h($t['nama_tim']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Status Pertandingan</label>
            <select name="status" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Status</option>
                <?php foreach (STATUS_MATCH as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= h($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-3 flex items-end">
            <button class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                <i class="bi bi-funnel"></i> Terapkan Filter
            </button>
        </div>
    </form>
</div>

<?php if (!$tampil): ?>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-lock text-4xl text-slate-400 mb-3 block"></i>
        Jadwal liga belum dipublikasikan oleh administrator.
    </div>
<?php elseif (!$grup): ?>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-search text-4xl text-slate-400 mb-3 block"></i>
        Tidak ada pertandingan yang sesuai dengan filter.
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($grup as $tanggal => $rows): ?>
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    <h2 class="text-xs uppercase font-extrabold tracking-wider text-slate-600">
                        <?= $tanggal ? h(hari_id($tanggal) . ', ' . tgl($tanggal)) : 'Belum Dijadwalkan' ?>
                    </h2>
                    <span class="text-xs text-slate-400 font-medium">(<?= count($rows) ?> match)</span>
                </div>
                <?php tabel_pertandingan($rows, false); ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
