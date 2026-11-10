<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !strtotime($tanggal)) {
    $tanggal = date('Y-m-d');
}
$fTim = (int) ($_GET['tim'] ?? 0);
$fStatus = $_GET['status'] ?? '';

$tampil = jadwal_tampil();
$rows = [];
if ($tampil) {
    $w = 'p.tanggal = ?';
    $p = [$tanggal];
    if ($fTim) { $w .= ' AND (p.tim_home = ? OR p.tim_away = ?)'; array_push($p, $fTim, $fTim); }
    if (isset(STATUS_MATCH[$fStatus])) { $w .= ' AND p.status = ?'; $p[] = $fStatus; }
    $rows = ambil_pertandingan($w, $p);
}
$daftarTim = db()->query('SELECT id, nama_tim FROM tim WHERE id_liga=' . (int) liga()['id'] . ' ORDER BY nama_tim')->fetchAll();
$q = fn($t) => http_build_query(array_filter(['tanggal' => $t, 'tim' => $fTim ?: null, 'status' => $fStatus ?: null]));

$judul = 'Jadwal Hari Ini';
$menu = 'hari_ini';
require __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Harian</h1>
        <p class="text-sm text-slate-500 mt-1">Pantau pertandingan berdasarkan tanggal pelaksanaan.</p>
    </div>
    <!-- Quick Date Navigation -->
    <div class="flex items-center gap-1.5 bg-white p-1 rounded-2xl border border-slate-200/80 shadow-xs">
        <a class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors" href="?<?= h($q(date('Y-m-d', strtotime($tanggal . ' -1 day')))) ?>" title="Hari Sebelumnya">
            <i class="bi bi-chevron-left text-sm"></i>
        </a>
        <div class="px-3 py-1 text-xs font-bold text-slate-800 flex items-center gap-1.5">
            <i class="bi bi-calendar-event text-indigo-500"></i>
            <span><?= h(hari_id($tanggal) . ', ' . tgl($tanggal)) ?></span>
        </div>
        <a class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors" href="?<?= h($q(date('Y-m-d', strtotime($tanggal . ' +1 day')))) ?>" title="Hari Berikutnya">
            <i class="bi bi-chevron-right text-sm"></i>
        </a>
        <?php if ($tanggal !== date('Y-m-d')): ?>
            <a class="px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors ml-1" href="?<?= h($q(date('Y-m-d'))) ?>">
                Hari Ini
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Date & Filters Form -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get">
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Pilih Tanggal</label>
            <input type="date" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" value="<?= h($tanggal) ?>">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Filter Tim</label>
            <select name="tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="0">Semua Tim</option>
                <?php foreach ($daftarTim as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $fTim === (int) $t['id'] ? 'selected' : '' ?>><?= h($t['nama_tim']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Status</label>
            <select name="status" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Status</option>
                <?php foreach (STATUS_MATCH as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= h($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2 flex items-end">
            <button class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                <i class="bi bi-funnel"></i> Terapkan
            </button>
        </div>
    </form>
</div>

<?php if (!$tampil): ?>
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-lock text-4xl text-slate-400 mb-3 block"></i>
        Jadwal belum dipublikasikan.
    </div>
<?php else: ?>
    <div class="space-y-4">
        <div class="flex items-center justify-between text-xs text-slate-500 px-1">
            <span>Ditemukan <strong><?= count($rows) ?></strong> pertandingan pada tanggal ini</span>
        </div>
        <?php tabel_pertandingan($rows, false); ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
