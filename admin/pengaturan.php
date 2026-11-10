<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$pdo = db();
$liga = liga();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'backup') {
        $tmp = sys_get_temp_dir() . '/liga-backup-' . bin2hex(random_bytes(4)) . '.sqlite';
        try {
            $pdo->exec('VACUUM INTO ' . $pdo->quote($tmp));
        } catch (Throwable $e) {
            copy(DB_FILE, $tmp);
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="liga-dam-batu-' . date('Ymd-His') . '.sqlite"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    if ($aksi === 'liga') {
        $nama = trim($_POST['nama_liga'] ?? '');
        $musim = trim($_POST['musim'] ?? '');
        if ($nama === '' || mb_strlen($nama) > 80) {
            flash('danger', 'Nama liga wajib diisi (maks. 80 karakter).');
        } else {
            $pdo->prepare('UPDATE liga SET nama_liga=?, musim=? WHERE id=?')->execute([$nama, $musim, $liga['id']]);
            flash('success', 'Pengaturan liga disimpan.');
        }
    } elseif ($aksi === 'password') {
        $st = $pdo->prepare('SELECT password FROM admin WHERE id=?');
        $st->execute([$_SESSION['admin_id']]);
        $lama = $_POST['lama'] ?? '';
        $baru = $_POST['baru'] ?? '';
        if (!password_verify($lama, (string) $st->fetchColumn())) {
            flash('danger', 'Password lama salah.');
        } elseif (strlen($baru) < 8) {
            flash('danger', 'Password baru minimal 8 karakter.');
        } elseif ($baru !== ($_POST['ulang'] ?? '')) {
            flash('danger', 'Konfirmasi password tidak sama.');
        } else {
            $pdo->prepare('UPDATE admin SET password=? WHERE id=?')
                ->execute([password_hash($baru, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
            flash('success', 'Password berhasil diganti.');
        }
    }
    redirect('admin/pengaturan.php');
}

$judul = 'Pengaturan';
$menu = 'pengaturan';
$area = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Pengaturan Sistem</h1>
    <p class="text-sm text-slate-500 mt-1">Konfigurasi nama liga, keamanan akun admin, dan backup data.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Pengaturan Liga -->
    <div class="lg:col-span-6">
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs h-full flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                    <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i class="bi bi-sliders"></i>
                    </span>
                    <h2 class="text-base font-bold text-slate-900">Informasi Liga</h2>
                </div>

                <form method="post" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="liga">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Liga</label>
                        <input name="nama_liga" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="80" required value="<?= h($liga['nama_liga']) ?>">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Musim / Edisi</label>
                        <input name="musim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="40" value="<?= h($liga['musim']) ?>">
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-2">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                            <div class="text-xs text-slate-400">Status</div>
                            <div class="text-xs font-bold text-slate-800 mt-0.5"><?= h(label_status_liga($liga['status'])) ?></div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                            <div class="text-xs text-slate-400">Mulai</div>
                            <div class="text-xs font-bold text-slate-800 mt-0.5"><?= h(tgl($liga['tanggal_mulai'])) ?></div>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                            <div class="text-xs text-slate-400">Selesai</div>
                            <div class="text-xs font-bold text-slate-800 mt-0.5"><?= h(tgl($liga['tanggal_selesai'])) ?></div>
                        </div>
                    </div>

                    <div class="text-xs text-slate-400 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                        Aturan sistem: Single Round Robin, 2 pemain per tim, menang 3 poin, kalah 0 poin (tanpa draw). Tanggal mulai dan selesai ditentukan saat publikasi jadwal.
                    </div>

                    <div class="pt-2">
                        <button class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password & Backup -->
    <div class="lg:col-span-6 space-y-6">
        <!-- Ganti Password -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="bi bi-shield-lock"></i>
                </span>
                <h2 class="text-base font-bold text-slate-900">Ganti Password Admin</h2>
            </div>

            <form method="post" autocomplete="off" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="password">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Lama</label>
                    <input type="password" name="lama" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" placeholder="••••••••" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
                    <input type="password" name="baru" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" placeholder="Minimal 8 karakter" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ulangi Password Baru</label>
                    <input type="password" name="ulang" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" placeholder="Ulangi password baru" required>
                </div>

                <div class="pt-2">
                    <button class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-xs">
                        Perbarui Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Backup Database -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="bi bi-database-down"></i>
                </span>
                <h2 class="text-base font-bold text-slate-900">Cadangkan Database (Backup)</h2>
            </div>

            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                Unduh salinan berkas database SQLite yang konsisten (menggunakan SQLite VACUUM snapshot). Aman diunduh kapan saja tanpa mengganggu aplikasi yang sedang berjalan.
            </p>

            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="backup">
                <button class="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl text-sm font-semibold text-slate-800 bg-slate-100 hover:bg-slate-200 transition-colors border border-slate-200">
                    <i class="bi bi-download"></i> Unduh File Backup (.sqlite)
                </button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
