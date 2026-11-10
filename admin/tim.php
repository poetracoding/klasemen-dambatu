<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$pdo = db();
$liga = liga();
$draft = $liga['status'] === 'draft';

function reset_jadwal_bila_ada(int $idLiga): bool
{
    $n = (int) db()->query('SELECT COUNT(*) FROM pertandingan WHERE id_liga=' . $idLiga)->fetchColumn();
    if ($n) {
        db()->prepare('DELETE FROM pertandingan WHERE id_liga=?')->execute([$idLiga]);
    }
    return $n > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        $nama = trim($_POST['nama_tim'] ?? '');
        $p1 = trim($_POST['pemain1'] ?? '');
        $p2 = trim($_POST['pemain2'] ?? '');
        $err = null;
        if ($nama === '' || $p1 === '' || $p2 === '') {
            $err = 'Nama tim dan kedua pemain wajib diisi (tepat dua pemain).';
        } elseif (mb_strlen($nama) > 60 || mb_strlen($p1) > 60 || mb_strlen($p2) > 60) {
            $err = 'Maksimal 60 karakter per kolom.';
        } elseif (mb_strtolower($p1) === mb_strtolower($p2)) {
            $err = 'Kedua pemain harus berbeda nama.';
        } else {
            $st = $pdo->prepare('SELECT COUNT(*) FROM tim WHERE id_liga=? AND LOWER(nama_tim)=LOWER(?) AND id<>?');
            $st->execute([$liga['id'], $nama, $aksi === 'ubah' ? $id : 0]);
            if ($st->fetchColumn()) {
                $err = 'Nama tim sudah dipakai.';
            }
        }
        if ($err) {
            flash('danger', $err);
        } elseif ($aksi === 'tambah') {
            if (!$draft) {
                flash('danger', 'Tim tidak dapat ditambah setelah jadwal dipublikasikan.');
            } else {
                $pdo->prepare('INSERT INTO tim (id_liga, nama_tim, pemain1, pemain2) VALUES (?,?,?,?)')
                    ->execute([$liga['id'], $nama, $p1, $p2]);
                flash('success', 'Tim "' . $nama . '" ditambahkan.');
                if (reset_jadwal_bila_ada((int) $liga['id'])) {
                    flash('warning', 'Jadwal lama dihapus karena jumlah tim berubah. Silakan generate ulang jadwal.');
                }
            }
        } else {
            $pdo->prepare('UPDATE tim SET nama_tim=?, pemain1=?, pemain2=? WHERE id=? AND id_liga=?')
                ->execute([$nama, $p1, $p2, $id, $liga['id']]);
            flash('success', 'Data tim diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        if (!$draft) {
            flash('danger', 'Tim tidak dapat dihapus setelah jadwal dipublikasikan.');
        } else {
            $pdo->beginTransaction();
            $ada = reset_jadwal_bila_ada((int) $liga['id']);
            $pdo->prepare('DELETE FROM tim WHERE id=? AND id_liga=?')->execute([$id, $liga['id']]);
            $pdo->commit();
            flash('success', 'Tim dihapus.');
            if ($ada) {
                flash('warning', 'Jadwal lama dihapus karena jumlah tim berubah. Silakan generate ulang jadwal.');
            }
        }
    }
    redirect('admin/tim.php');
}

$tims = $pdo->prepare('SELECT * FROM tim WHERE id_liga=? ORDER BY nama_tim');
$tims->execute([$liga['id']]);
$tims = $tims->fetchAll();
$edit = null;
if (isset($_GET['edit'])) {
    foreach ($tims as $t) {
        if ((int) $t['id'] === (int) $_GET['edit']) {
            $edit = $t;
        }
    }
}

$judul = 'Tim & Pemain';
$menu = 'tim';
$area = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tim &amp; Pemain</h1>
        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            <?= count($tims) ?> Tim
        </span>
    </div>
    <div class="text-xs text-slate-500">
        Format Tim: Tepat 2 Pemain per Tim
    </div>
</div>

<?php if (!$draft): ?>
    <div class="mb-6 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-900 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
        <i class="bi bi-info-circle-fill text-sky-600 text-lg"></i>
        <div>Jadwal liga sudah dipublikasikan: tim tidak dapat ditambah atau dihapus. Anda tetap dapat memperbarui nama tim dan pemain.</div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Form Tambah / Edit Tim -->
    <div class="lg:col-span-4">
        <?php if ($draft || $edit): ?>
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs sticky top-24">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi <?= $edit ? 'bi-pencil-square text-indigo-500' : 'bi-person-plus text-indigo-500' ?>"></i>
                        <?= $edit ? 'Edit Data Tim' : 'Tambah Tim Baru' ?>
                    </h2>
                    <?php if ($edit): ?>
                        <a href="tim.php" class="text-xs font-semibold text-slate-400 hover:text-slate-600">Batal</a>
                    <?php endif; ?>
                </div>

                <form method="post" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>">
                    <?php if ($edit): ?>
                        <input type="hidden" name="id" value="<?= (int) $edit['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Tim</label>
                        <input name="nama_tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="60" placeholder="contoh: Garuda Muda" required value="<?= h($edit['nama_tim'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemain 1</label>
                        <input name="pemain1" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="60" placeholder="Nama pemain 1" required value="<?= h($edit['pemain1'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemain 2</label>
                        <input name="pemain2" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="60" placeholder="Nama pemain 2" required value="<?= h($edit['pemain2'] ?? '') ?>">
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                            <?= $edit ? 'Simpan Perubahan' : 'Tambah Tim' ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="tim.php" class="px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 border border-slate-200 transition-colors">
                                Batal
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-6 text-center text-xs text-slate-500 shadow-xs">
                <i class="bi bi-lock text-3xl text-slate-400 mb-2 block"></i>
                Jadwal telah dipublikasikan. Tambah tim dinonaktifkan.
            </div>
        <?php endif; ?>
    </div>

    <!-- Daftar Tim Table -->
    <div class="lg:col-span-8">
        <?php if (!$tims): ?>
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
                <i class="bi bi-people text-4xl text-slate-400 mb-3 block"></i>
                Belum ada tim terdaftar. Tambahkan tim melalui form di samping.
            </div>
        <?php else: ?>
            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="py-4 px-4 text-center w-12">#</th>
                                <th scope="col" class="py-4 px-4">Nama Tim</th>
                                <th scope="col" class="py-4 px-4">Pemain 1</th>
                                <th scope="col" class="py-4 px-4">Pemain 2</th>
                                <th scope="col" class="py-4 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($tims as $i => $t): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-4 text-center font-mono text-xs text-slate-400"><?= $i + 1 ?></td>
                                    <td class="py-4 px-4 font-bold text-slate-900"><?= h($t['nama_tim']) ?></td>
                                    <td class="py-4 px-4 text-slate-600"><?= h($t['pemain1']) ?></td>
                                    <td class="py-4 px-4 text-slate-600"><?= h($t['pemain2']) ?></td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <a class="p-2 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors" href="?edit=<?= (int) $t['id'] ?>" title="Edit Tim">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($draft): ?>
                                                <form method="post" class="inline" data-confirm="Hapus tim '<?= h($t['nama_tim']) ?>'? Jadwal yang sudah dibuat akan ikut terhapus.">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                                    <button class="p-2 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors" title="Hapus Tim">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
