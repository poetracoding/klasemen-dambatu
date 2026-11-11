<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$pdo = db();
$liga = liga();
$idLiga = (int) $liga['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $skorHome = max(0, (int) ($_POST['skor_home'] ?? 0));
    $skorAway = max(0, (int) ($_POST['skor_away'] ?? 0));
    $pemenang = (int) ($_POST['pemenang_id'] ?? 0);
    $catatan = trim($_POST['catatan'] ?? '');

    $st = $pdo->prepare('SELECT * FROM pertandingan WHERE id=? AND id_liga=?');
    $st->execute([$id, $idLiga]);
    $m = $st->fetch();

    if ($liga['status'] === 'draft') {
        flash('danger', 'Publikasikan jadwal terlebih dahulu sebelum menginput hasil.');
    } elseif (!$m || !isset(STATUS_MATCH[$status])) {
        flash('danger', 'Data pertandingan tidak valid.');
    } else {
        // Otomatis tentukan pemenang jika skor berbeda dan belum dipilih manual
        if ($status === 'selesai' && !$pemenang) {
            if ($skorHome > $skorAway) {
                $pemenang = (int) $m['tim_home'];
            } elseif ($skorAway > $skorHome) {
                $pemenang = (int) $m['tim_away'];
            }
        }

        if ($status === 'selesai' && !in_array($pemenang, [(int) $m['tim_home'], (int) $m['tim_away']], true)) {
            flash('danger', 'Pilih pemenang pertandingan (harus ada pemenang, tidak boleh seri).');
        } else {
            $pdo->prepare('UPDATE pertandingan SET status=?, skor_home=?, skor_away=?, pemenang_id=?, catatan=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')
                ->execute([$status, $skorHome, $skorAway, $status === 'selesai' ? $pemenang : null, $catatan ?: null, $id]);
            sinkron_status_liga();
            flash('success', 'Hasil & poin pertandingan #' . $m['nomor_pertandingan'] . " ($skorHome - $skorAway) berhasil disimpan. Klasemen terupdate.");
            if (liga()['status'] === 'selesai') {
                flash('info', 'Seluruh pertandingan telah selesai! Juara liga sudah ditentukan.');
            }
        }
    }
    redirect('admin/pertandingan.php?' . http_build_query(array_filter(['f' => $_POST['f'] ?? '', 'tanggal' => $_POST['ft'] ?? ''])) . '#m' . $id);
}

$f = $_GET['f'] ?? '';
$ft = $_GET['tanggal'] ?? '';
$w = '1=1';
$p = [];
if ($f === 'belum') { $w .= " AND p.status <> 'selesai'"; }
if ($f === 'selesai') { $w .= " AND p.status = 'selesai'"; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ft)) { $w .= ' AND p.tanggal = ?'; $p[] = $ft; } else { $ft = ''; }
$matches = ambil_pertandingan($w, $p, 'p.tanggal, p.waktu, p.nomor_pertandingan');

$judul = 'Input Hasil';
$menu = 'hasil';
$area = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Input Hasil &amp; Poin Pertandingan</h1>
        <p class="text-sm text-slate-500 mt-1">Masukkan skor/poin pertandingan seperti gol sepak bola dan tentukan status laga.</p>
    </div>
</div>

<?php if ($liga['status'] === 'draft'): ?>
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
        <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg flex-shrink-0"></i>
        <div>Jadwal liga masih draft. Publikasikan terlebih dahulu di menu <a href="jadwal.php" class="font-bold underline hover:text-amber-800">Jadwal</a> agar hasil dapat diinput.</div>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get">
        <div class="sm:col-span-5">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Filter Status</label>
            <select name="f" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Pertandingan</option>
                <option value="belum" <?= $f === 'belum' ? 'selected' : '' ?>>Belum Selesai</option>
                <option value="selesai" <?= $f === 'selesai' ? 'selected' : '' ?>>Sudah Selesai</option>
            </select>
        </div>
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Pilih Tanggal</label>
            <input type="date" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" value="<?= h($ft) ?>">
        </div>
        <div class="sm:col-span-3 flex items-end">
            <button class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </form>
</div>

<?php if (!$matches): ?>
    <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-x text-4xl text-slate-400 mb-3 block"></i>
        Tidak ada data pertandingan yang sesuai.
    </div>
<?php else: ?>
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-3.5 px-3 text-center w-10">#</th>
                        <th scope="col" class="py-3.5 px-3 w-32">Jadwal &amp; Meja</th>
                        <th scope="col" class="py-3.5 px-3">Pertandingan</th>
                        <th scope="col" class="py-3.5 px-3 w-36 text-center">Poin / Skor</th>
                        <th scope="col" class="py-3.5 px-3 w-32">Status</th>
                        <th scope="col" class="py-3.5 px-3 w-40">Pemenang</th>
                        <th scope="col" class="py-3.5 px-3">Catatan</th>
                        <th scope="col" class="py-3.5 px-3 text-center w-14">Simpan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($matches as $m): $fid = 'h' . $m['id']; ?>
                        <tr id="m<?= (int) $m['id'] ?>" class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-3 text-center font-mono text-xs text-slate-400">
                                #<?= (int) $m['nomor_pertandingan'] ?>
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap text-xs">
                                <div class="font-semibold text-slate-800"><?= $m['tanggal'] ? h(hari_id($m['tanggal']) . ', ' . tgl($m['tanggal'])) : '-' ?></div>
                                <div class="text-slate-400 font-mono text-3xs"><?= h(jam($m['waktu'])) ?> &middot; <span class="text-indigo-600 font-bold"><?= h($m['lokasi'] ?: '-') ?></span></div>
                            </td>
                            <!-- Kolom Pertandingan -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 text-xs leading-snug"><?= h($m['nama_home']) ?></div>
                                <div class="text-3xs text-slate-400 leading-snug">vs <span class="font-bold text-slate-800"><?= h($m['nama_away']) ?></span></div>
                            </td>
                            <!-- Kolom Poin / Skor dengan Font Kecil Rapi -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-1.5 bg-slate-100/90 py-1 px-2 rounded-xl border border-slate-200/80 shadow-2xs">
                                    <div class="text-center">
                                        <div class="text-[9px] font-semibold text-slate-500 truncate max-w-16 leading-tight" title="<?= h($m['nama_home']) ?>">
                                            <?= h($m['nama_home']) ?>
                                        </div>
                                        <input form="<?= $fid ?>" type="number" min="0" max="99" name="skor_home" 
                                            class="w-10 h-7 text-center text-xs font-black font-mono rounded-lg border border-slate-300 bg-white text-slate-900 form-input-focus score-input-home shadow-2xs" 
                                            value="<?= (int) ($m['skor_home'] ?? 0) ?>" 
                                            title="Poin <?= h($m['nama_home']) ?>">
                                    </div>
                                    <span class="font-extrabold text-slate-300 text-xs mt-3">:</span>
                                    <div class="text-center">
                                        <div class="text-[9px] font-semibold text-slate-500 truncate max-w-16 leading-tight" title="<?= h($m['nama_away']) ?>">
                                            <?= h($m['nama_away']) ?>
                                        </div>
                                        <input form="<?= $fid ?>" type="number" min="0" max="99" name="skor_away" 
                                            class="w-10 h-7 text-center text-xs font-black font-mono rounded-lg border border-slate-300 bg-white text-slate-900 form-input-focus score-input-away shadow-2xs" 
                                            value="<?= (int) ($m['skor_away'] ?? 0) ?>" 
                                            title="Poin <?= h($m['nama_away']) ?>">
                                    </div>
                                </div>
                            </td>
                            <!-- Status -->
                            <td class="py-3 px-3">
                                <select form="<?= $fid ?>" name="status" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2 text-slate-800 form-input-focus">
                                    <?php foreach (STATUS_MATCH as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $m['status'] === $k ? 'selected' : '' ?>><?= h($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <!-- Pemenang -->
                            <td class="py-3 px-3">
                                <select form="<?= $fid ?>" name="pemenang_id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2 text-slate-800 form-input-focus">
                                    <option value="">— Belum / Pilih —</option>
                                    <option value="<?= (int) $m['tim_home'] ?>" <?= (int) $m['pemenang_id'] === (int) $m['tim_home'] ? 'selected' : '' ?>><?= h($m['nama_home']) ?></option>
                                    <option value="<?= (int) $m['tim_away'] ?>" <?= (int) $m['pemenang_id'] === (int) $m['tim_away'] ? 'selected' : '' ?>><?= h($m['nama_away']) ?></option>
                                </select>
                            </td>
                            <!-- Catatan -->
                            <td class="py-3 px-3">
                                <input form="<?= $fid ?>" name="catatan" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-slate-800 form-input-focus" maxlength="200" placeholder="Catatan opsional..." value="<?= h($m['catatan']) ?>">
                            </td>
                            <!-- Tombol Simpan -->
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <form id="<?= $fid ?>" method="post" class="form-hasil">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <input type="hidden" name="f" value="<?= h($f) ?>">
                                    <input type="hidden" name="ft" value="<?= h($ft) ?>">
                                    <button class="inline-flex items-center justify-center p-2 rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 disabled:opacity-50 disabled:cursor-not-allowed" <?= $liga['status'] === 'draft' ? 'disabled' : '' ?> title="Simpan Skor &amp; Hasil">
                                        <i class="bi bi-floppy text-sm"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
