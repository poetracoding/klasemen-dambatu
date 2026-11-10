<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$pdo = db();
$liga = liga();
$jumlahTim = (int) $pdo->query('SELECT COUNT(*) FROM tim WHERE id_liga=' . (int) $liga['id'])->fetchColumn();
$st = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='selesai'),0) sel FROM pertandingan WHERE id_liga=?");
$st->execute([$liga['id']]);
$r = $st->fetch();
$total = (int) $r['total'];
$selesai = (int) $r['sel'];
$persen = $total ? round($selesai / $total * 100) : 0;
$hariIni = ambil_pertandingan('p.tanggal = ?', [date('Y-m-d')]);

$judul = 'Dashboard Admin';
$menu = 'dashboard';
$area = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Dashboard Admin</h1>
        <p class="text-sm text-slate-500 mt-1">Ringkasan status, jadwal, dan progres jalannya liga.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= h(url('admin/jadwal.php')) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs">
            <i class="bi bi-calendar3 text-indigo-500"></i> Kelola Jadwal
        </a>
        <a href="<?= h(url('admin/pertandingan.php')) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
            <i class="bi bi-trophy"></i> Input Hasil
        </a>
    </div>
</div>

<?php if (password_verify('admin123', $pdo->query('SELECT password FROM admin WHERE id=' . (int) $_SESSION['admin_id'])->fetchColumn())): ?>
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <i class="bi bi-shield-exclamation text-xl text-amber-600"></i>
            <div class="text-xs sm:text-sm">
                <span class="font-bold">Peringatan Keamanan:</span> Anda masih memakai password bawaan (<code class="bg-amber-100 px-1 py-0.5 rounded text-amber-800 font-mono">admin123</code>). Segera ganti di menu <a href="pengaturan.php" class="font-bold underline hover:text-amber-700">Pengaturan</a>.
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Metric Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Status Liga</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= h(label_status_liga($liga['status'])) ?></div>
        <div class="text-xs text-slate-400 mt-1 capitalize">Musim <?= h($liga['musim']) ?></div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Jumlah Tim</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900"><?= (int) $jumlahTim ?></div>
        <div class="text-xs text-indigo-600 font-medium mt-1">
            <a href="tim.php" class="hover:underline">Kelola tim &rarr;</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Total Pertandingan</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
            <?= (int) $total ?><?= $jumlahTim > 1 ? '<span class="text-base text-slate-400 font-normal"> / ' . total_pertandingan_rumus($jumlahTim) . '</span>' : '' ?>
        </div>
        <div class="text-xs text-slate-400 mt-1">Round Robin</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Selesai</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-emerald-600"><?= (int) $selesai ?></div>
        <div class="text-xs text-slate-400 mt-1"><?= max(0, $total - $selesai) ?> match tersisa</div>
    </div>
</div>

<!-- Progress Bar Card -->
<div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs mb-6">
    <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-2">
        <span class="flex items-center gap-1.5">
            <i class="bi bi-activity text-indigo-500"></i> Progres Penyelesaian Liga
        </span>
        <span class="font-mono text-indigo-600 text-sm"><?= $persen ?>%</span>
    </div>
    <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-violet-600 h-2.5 rounded-full transition-all duration-500" style="width: <?= $persen ?>%"></div>
    </div>
</div>

<!-- Next Steps Card -->
<div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-2xl p-6 shadow-sm mb-8 border border-slate-700">
    <div class="flex items-center gap-2 mb-2 text-indigo-400 text-xs font-bold uppercase tracking-wider">
        <i class="bi bi-lightbulb"></i> Panduan Langkah Selanjutnya
    </div>
    <div class="text-sm font-medium text-slate-200">
        <?php if ($jumlahTim < 2): ?>
            Tambahkan minimal 2 tim di menu <a href="tim.php" class="text-indigo-400 font-bold underline hover:text-indigo-300">Tim &amp; Pemain</a> untuk dapat menyusun jadwal pertandingan.
        <?php elseif ($liga['status'] === 'draft' && !$total): ?>
            Tim sudah siap (<?= $jumlahTim ?> tim). Sekarang buat jadwal otomatis di menu <a href="jadwal.php" class="text-indigo-400 font-bold underline hover:text-indigo-300">Jadwal Pertandingan</a>.
        <?php elseif ($liga['status'] === 'draft'): ?>
            Jadwal sudah digenerate. Periksa tanggal/jam, lalu <a href="jadwal.php" class="text-indigo-400 font-bold underline hover:text-indigo-300">publikasikan jadwal</a> agar liga dapat dimulai.
        <?php elseif ($liga['status'] === 'berjalan'): ?>
            Liga sedang aktif berjalan! Masukkan skor &amp; pemenang di menu <a href="pertandingan.php" class="text-indigo-400 font-bold underline hover:text-indigo-300">Input Hasil</a> setiap kali pertandingan selesai.
        <?php else: ?>
            Seluruh pertandingan liga telah selesai. Anda dapat melihat hasil akhir dan sang juara di halaman <a href="../klasemen.php" class="text-indigo-400 font-bold underline hover:text-indigo-300">Klasemen Akhir</a>.
        <?php endif; ?>
    </div>
</div>

<!-- Today's Matches -->
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Pertandingan Hari Ini</h2>
        <span class="text-xs text-slate-400 font-medium"><?= h(hari_id(date('Y-m-d')) . ', ' . tgl(date('Y-m-d'))) ?></span>
    </div>
    <?php tabel_pertandingan($hariIni, false); ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
