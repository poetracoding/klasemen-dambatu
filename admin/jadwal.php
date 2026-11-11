<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
wajib_admin();

$pdo = db();
$liga = liga();
$idLiga = (int) $liga['id'];
$jumlahTim = (int) $pdo->query('SELECT COUNT(*) FROM tim WHERE id_liga=' . $idLiga)->fetchColumn();

function tanggal_valid(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}
function jam_valid(string $s): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s);
}

// Cek apakah sudah ada pertandingan yang sudah dimainkan / diubah statusnya
$stCek = $pdo->prepare("SELECT COUNT(*) FROM pertandingan WHERE id_liga=? AND status<>'belum_main'");
$stCek->execute([$idLiga]);
$sudahBertanding = (int) $stCek->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';
    $draft = $liga['status'] === 'draft';

    if ($aksi === 'generate') {
        $hariDipilih = $_POST['hari'] ?? [];
        if (empty($hariDipilih)) {
            flash('danger', 'Pilih minimal satu hari pertandingan.');
        } else {
            $o = [
                'mulai'       => $_POST['mulai'] ?? '',
                'per_hari'    => (int) ($_POST['per_hari'] ?? 0),
                'jumlah_meja' => max(1, (int) ($_POST['jumlah_meja'] ?? 1)),
                'jam'         => $_POST['jam'] ?? '',
                'durasi'      => (int) ($_POST['durasi'] ?? 0),
                'lokasi'      => trim($_POST['lokasi'] ?? ''),
                'hari'        => $hariDipilih,
                'libur'       => $_POST['libur'] ?? [],
            ];
            if (!$draft) {
                flash('danger', 'Jadwal hanya dapat dibuat ulang saat liga masih berstatus draft.');
            } elseif ($jumlahTim < 2) {
                flash('danger', 'Minimal 2 tim untuk membuat jadwal.');
            } elseif (!tanggal_valid($o['mulai']) || !jam_valid($o['jam']) || $o['per_hari'] < 1 || $o['per_hari'] > 50 || $o['durasi'] < 1 || $o['durasi'] > 600) {
                flash('danger', 'Isian pengaturan jadwal tidak valid.');
            } else {
                $n = buat_jadwal($o);
                flash('success', "Jadwal dibuat: $n pertandingan dengan {$o['jumlah_meja']} meja Dam Batu. Pertandingan dijadwalkan pada hari yang ditentukan secara berurutan tanpa bentrok tim.");
            }
        }
    } elseif ($aksi === 'batal_jadwal') {
        // Syarat #1: tidak bisa membatalkan jadwal jika sudah ada yang bertanding
        if ($sudahBertanding > 0) {
            flash('danger', "Jadwal TIDAK DAPAT dibatalkan karena sudah ada $sudahBertanding pertandingan yang sedang berjalan atau telah selesai.");
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM pertandingan WHERE id_liga=?')->execute([$idLiga]);
            $pdo->prepare("UPDATE liga SET status='draft', tanggal_mulai=NULL, tanggal_selesai=NULL WHERE id=?")->execute([$idLiga]);
            $pdo->commit();
            flash('success', 'Seluruh jadwal pertandingan berhasil dibatalkan dan dihapus. Anda dapat menambah/mengurangi tim atau generate jadwal ulang.');
        }
    } elseif ($aksi === 'publikasi') {
        $n = (int) $pdo->query('SELECT COUNT(*) FROM pertandingan WHERE id_liga=' . $idLiga)->fetchColumn();
        if (!$draft) {
            flash('info', 'Jadwal sudah dipublikasikan.');
        } elseif (!$n) {
            flash('danger', 'Belum ada jadwal untuk dipublikasikan.');
        } else {
            $r = $pdo->query('SELECT MIN(tanggal) a, MAX(tanggal) b FROM pertandingan WHERE id_liga=' . $idLiga)->fetch();
            $pdo->prepare("UPDATE liga SET status='berjalan', tanggal_mulai=?, tanggal_selesai=? WHERE id=?")
                ->execute([$r['a'], $r['b'], $idLiga]);
            flash('success', 'Jadwal dipublikasikan. Liga sekarang berstatus berjalan.');
        }
    } elseif ($aksi === 'batal_publikasi') {
        if ($draft || $sudahBertanding > 0) {
            flash('danger', 'Publikasi hanya dapat dibatalkan jika belum ada pertandingan yang dimainkan/diubah statusnya.');
        } else {
            $pdo->prepare("UPDATE liga SET status='draft' WHERE id=?")->execute([$idLiga]);
            flash('success', 'Publikasi dibatalkan. Liga kembali ke draft.');
        }
    } elseif ($aksi === 'geser_jadwal') {
        // Syarat #4: percepat / perlambat jadwal
        $id = (int) ($_POST['id'] ?? 0);
        $menit = (int) ($_POST['menit'] ?? 0);
        $cakupan = $_POST['cakupan'] ?? 'semua_setelahnya';

        $st = $pdo->prepare('SELECT * FROM pertandingan WHERE id=? AND id_liga=?');
        $st->execute([$id, $idLiga]);
        $target = $st->fetch();

        if (!$target || $menit === 0) {
            flash('danger', 'Pilih pertandingan dan durasi menit pergeseran yang valid.');
        } else {
            if ($cakupan === 'hanya_satu') {
                $targetList = [$target];
            } else {
                // Semua pertandingan di hari yang sama dengan jam >= target atau nomor >= target
                $stGroup = $pdo->prepare('SELECT * FROM pertandingan WHERE id_liga=? AND tanggal=? AND nomor_pertandingan >= ? ORDER BY nomor_pertandingan');
                $stGroup->execute([$idLiga, $target['tanggal'], $target['nomor_pertandingan']]);
                $targetList = $stGroup->fetchAll();
            }

            $pdo->beginTransaction();
            $countShift = 0;
            foreach ($targetList as $m) {
                if ($m['waktu']) {
                    $ts = strtotime($m['waktu']) + ($menit * 60);
                    $waktuBaru = date('H:i', $ts);
                    $pdo->prepare('UPDATE pertandingan SET waktu=? WHERE id=? AND id_liga=?')
                        ->execute([$waktuBaru, $m['id'], $idLiga]);
                    $countShift++;
                }
            }
            $pdo->commit();

            $aksiTeks = $menit < 0 ? 'dipercepat ' . abs($menit) . ' menit' : 'diperlambat ' . $menit . ' menit';
            flash('success', "Jadwal $countShift pertandingan berhasil $aksiTeks.");
        }
    } elseif ($aksi === 'atur') {
        $id = (int) ($_POST['id'] ?? 0);
        $t = trim($_POST['tanggal'] ?? '');
        $w = trim($_POST['waktu'] ?? '');
        if (($t !== '' && !tanggal_valid($t)) || ($w !== '' && !jam_valid($w))) {
            flash('danger', 'Format tanggal/waktu tidak valid.');
        } else {
            $pdo->prepare('UPDATE pertandingan SET tanggal=?, waktu=?, lokasi=? WHERE id=? AND id_liga=?')
                ->execute([$t ?: null, $w ?: null, trim($_POST['lokasi'] ?? ''), $id, $idLiga]);
            flash('success', 'Jadwal pertandingan #' . $id . ' diperbarui.');
        }
    }
    redirect('admin/jadwal.php');
}

$draft = $liga['status'] === 'draft';
$defaultMeja = max(1, (int) ($liga['jumlah_meja'] ?? 2));
$defaultHari = array_map('intval', explode(',', $liga['hari_pertandingan'] ?? '1,2,3,4,5,6,7'));
$defaultLibur = array_values(array_filter(explode(',', $liga['tanggal_libur'] ?? '')));
$matches = ambil_pertandingan('1=1', [], 'p.tanggal, p.waktu, p.nomor_pertandingan');

$judul = 'Jadwal';
$menu = 'jadwal';
$area = 'admin';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Jadwal Liga</h1>
        <p class="text-sm text-slate-500 mt-1">Multi-meja Dam Batu, jadwal simultan, penyesuaian waktu, & publikasi.</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold <?= $draft ? 'bg-slate-100 text-slate-700 border border-slate-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
            <span class="w-2 h-2 rounded-full <?= $draft ? 'bg-slate-400' : 'bg-emerald-500' ?>"></span>
            Status: <?= h(label_status_liga($liga['status'])) ?>
        </span>
    </div>
</div>

<!-- Info Banner -->
<div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 text-xs text-indigo-900 mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        <i class="bi bi-info-circle-fill text-indigo-500 text-base flex-shrink-0"></i>
        <span>
            <strong><?= $jumlahTim ?> tim</strong> terdaftar &rarr; Total pertandingan = <strong><?= $jumlahTim > 1 ? total_pertandingan_rumus($jumlahTim) : 0 ?> match</strong>.
            <?= $jumlahTim % 2 ? '<span class="text-amber-700 font-semibold">(Ganjil: ada tim istirahat/bye tiap ronde)</span>' : '' ?>
        </span>
    </div>
    <?php if ($sudahBertanding > 0): ?>
        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-100/60 px-2.5 py-1 rounded-lg">
            <i class="bi bi-play-circle-fill"></i> <?= $sudahBertanding ?> pertandingan sudah berjalan/selesai
        </span>
    <?php endif; ?>
</div>

<?php if ($draft): ?>
    <!-- Generator Jadwal Card (Dukungan Multi-Meja) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs mb-8">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-magic text-indigo-500"></i> Generate Jadwal Otomatis (Multi-Meja Dam Batu)
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Pertandingan dapat berlangsung simultan di beberapa meja tanpa antri, dengan jaminan tim yang sama tidak akan bentrok di jam yang sama.</p>
            </div>
            <?php if ($matches): ?>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200/60 hidden sm:inline-block">
                    <i class="bi bi-exclamation-triangle mr-1"></i> Jadwal lama akan digantikan
                </span>
            <?php endif; ?>
        </div>

        <form method="post" class="space-y-4" data-confirm="<?= $matches ? 'Jadwal yang ada saat ini akan diganti seluruhnya. Lanjutkan?' : 'Generate jadwal sekarang?' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="generate">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="mulai" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" required value="<?= h(date('Y-m-d')) ?>">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Meja Dam Batu</label>
                    <input type="number" name="jumlah_meja" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" min="1" max="10" required value="<?= $defaultMeja ?>">
                    <span class="text-3xs text-slate-400 mt-1 block">Tersedia: Meja 1 s/d Meja <?= $defaultMeja ?></span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Maks. Match / Hari</label>
                    <input type="number" name="per_hari" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" min="1" max="50" required value="4">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jam Mulai (WIB)</label>
                    <input type="time" name="jam" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" required value="19:00">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Durasi (Menit)</label>
                    <input type="number" name="durasi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" min="1" max="600" required value="45">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Meja / Lokasi</label>
                    <input name="lokasi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" maxlength="60" placeholder="Meja">
                </div>
            </div>

            <!-- Penentuan Hari Pertandingan -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-800">
                            <i class="bi bi-calendar-week text-indigo-500 mr-1"></i> Penentuan Hari Pertandingan
                        </label>
                        <p class="text-3xs text-slate-500">Pilih hari apa saja pertandingan boleh dilaksanakan. Hari yang tidak dicentang akan dilewati otomatis saat generate jadwal.</p>
                    </div>
                    <!-- Preset Shortcut Buttons -->
                    <div class="flex flex-wrap items-center gap-1.5 text-3xs font-semibold">
                        <button type="button" onclick="pilihHariPreset([1,2,3,4,5,6,7])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Semua Hari</button>
                        <button type="button" onclick="pilihHariPreset([1,2,3,4,5])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Senin - Jumat</button>
                        <button type="button" onclick="pilihHariPreset([5,6,7])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Jumat - Minggu</button>
                        <button type="button" onclick="pilihHariPreset([6,7])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Sabtu &amp; Minggu</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                    <?php
                    $listHariNama = [
                        1 => 'Senin',
                        2 => 'Selasa',
                        3 => 'Rabu',
                        4 => 'Kamis',
                        5 => 'Jumat',
                        6 => 'Sabtu',
                        7 => 'Minggu',
                    ];
                    foreach ($listHariNama as $num => $nama):
                        $checked = in_array($num, $defaultHari, true);
                        $isWeekend = $num >= 6;
                    ?>
                        <label class="hari-pill flex items-center justify-between p-2.5 rounded-xl border cursor-pointer select-none transition-all <?= $checked ? 'bg-indigo-50 border-indigo-200 text-indigo-900 font-bold shadow-2xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="hari[]" value="<?= $num ?>" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300" <?= $checked ? 'checked' : '' ?> onchange="updateHariPill(this)">
                                <span class="text-xs"><?= h($nama) ?></span>
                            </div>
                            <?php if ($isWeekend): ?>
                                <span class="text-4xs px-1 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Weekend</span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Skenario 1: Tanggal Libur Khusus (Dilewati) -->
            <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-rose-950 flex items-center gap-1.5">
                            <i class="bi bi-calendar-x text-rose-500 text-sm"></i>
                            <span>Tanggal Libur Khusus (Dilewati / Blackout Dates)</span>
                            <span class="text-4xs uppercase bg-rose-200 text-rose-800 px-1.5 py-0.5 rounded font-bold">Skenario 1</span>
                        </label>
                        <p class="text-3xs text-rose-700/80 mt-0.5">Pertandingan tidak akan dijadwalkan pada tanggal-tanggal ini (misal hari besar, pemilu, venue tutup) dan otomatis dialihkan ke jadwal berikutnya.</p>
                    </div>
                </div>

                <!-- Input Picker & Button Tambah -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <input type="date" id="inputTanggalLibur" class="text-xs rounded-xl border border-rose-200 bg-white py-1.5 px-3 text-slate-800 form-input-focus shadow-2xs">
                    <button type="button" onclick="tambahTanggalLibur()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-100 hover:bg-rose-200 border border-rose-200 transition-colors shadow-2xs">
                        <i class="bi bi-plus-circle-fill"></i> Tambah Hari Libur
                    </button>
                    <span class="text-3xs text-slate-400 italic">Pilih tanggal lalu klik Tambah</span>
                </div>

                <!-- List Tanggal Libur Yang Terpilih -->
                <div id="containerLibur" class="flex flex-wrap items-center gap-2 min-h-8">
                    <?php if (empty($defaultLibur)): ?>
                        <span id="emptyLiburNotice" class="text-3xs text-slate-400 italic">Belum ada tanggal libur khusus yang ditambahkan (opsional).</span>
                    <?php else: ?>
                        <?php foreach ($defaultLibur as $dl): ?>
                            <span class="libur-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white text-rose-700 text-xs font-semibold border border-rose-200 shadow-2xs">
                                <i class="bi bi-calendar-x text-rose-500 text-xs"></i>
                                <span><?= h(hari_id($dl) . ', ' . tgl($dl)) ?></span>
                                <input type="hidden" name="libur[]" value="<?= h($dl) ?>">
                                <button type="button" onclick="hapusTanggalLibur(this)" class="text-slate-400 hover:text-rose-600 transition-colors ml-1 font-bold text-sm" title="Hapus tanggal libur">&times;</button>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-100 text-xs text-indigo-900 flex items-start gap-2">
                <i class="bi bi-info-circle-fill text-indigo-500 mt-0.5 flex-shrink-0"></i>
                <div>
                    <strong>Sistem Prioritas Meja Kosong:</strong> Pertandingan akan selalu menggunakan meja yang sudah kosong (mulai dari Meja 1). Meja 2, 3, dst hanya digunakan secara paralel jika meja sebelumnya belum selesai bertanding pada jam yang sama. Dengan <strong><?= $jumlahTim ?> tim</strong>, kapasitas simultan adalah maksimal <strong><?= max(1, intdiv($jumlahTim, 2)) ?> meja</strong> bersamaan.
                </div>
            </div>

            <div class="pt-2">
                <button class="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 disabled:opacity-50 disabled:cursor-not-allowed" <?= $jumlahTim < 2 ? 'disabled' : '' ?>>
                    <i class="bi bi-play-circle"></i> Generate Jadwal
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($matches): ?>
    <!-- Action Bar: Publikasi, Batal Jadwal, dan Geser Jadwal -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6">
        <!-- Status & Publikasi / Batal Jadwal Actions -->
        <div class="lg:col-span-12 flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="text-xs text-slate-600 font-medium">
                    Total <strong><?= count($matches) ?></strong> pertandingan tersusun.
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <?php if ($draft): ?>
                    <!-- Tombol Publikasikan -->
                    <form method="post" data-confirm="Publikasikan jadwal? Tim tidak dapat ditambah/dihapus setelah ini.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="publikasi">
                        <button class="inline-flex items-center gap-1.5 py-2 px-4 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-xs shadow-emerald-600/20">
                            <i class="bi bi-megaphone-fill"></i> Publikasikan Jadwal
                        </button>
                    </form>

                    <!-- Syarat #1: Menu membatalkan jadwal yang digenerate (Hanya jika belum ada yang bertanding) -->
                    <form method="post" data-confirm="Apakah Anda yakin ingin MEMBATALKAN &amp; MENGHAPUS SELURUH JADWAL yang digenerate? Jadwal akan dikosongkan kembali.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="batal_jadwal">
                        <button class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                            <i class="bi bi-trash3-fill"></i> Batalkan &amp; Hapus Jadwal
                        </button>
                    </form>
                <?php else: ?>
                    <?php if ($sudahBertanding === 0): ?>
                        <form method="post" data-confirm="Batalkan publikasi dan kembali ke mode draft?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="batal_publikasi">
                            <button class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition-colors">
                                <i class="bi bi-arrow-counterclockwise"></i> Batalkan Publikasi
                            </button>
                        </form>
                        <form method="post" data-confirm="Apakah Anda yakin ingin MENGHAPUS SEMUA JADWAL? Status liga akan kembali ke draft dan jadwal dikosongkan.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="batal_jadwal">
                            <button class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors">
                                <i class="bi bi-trash3-fill"></i> Batalkan &amp; Hapus Jadwal
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="text-xs text-slate-400 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <i class="bi bi-lock-fill text-slate-400 mr-1"></i> Jadwal terkunci (pertandingan sudah berjalan)
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Syarat #4: Fitur Percepat / Perlambat Jadwal -->
        <div class="lg:col-span-12 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-2 pb-3 mb-3 border-b border-slate-100">
                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="bi bi-clock-history"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Percepat / Perlambat Jadwal Pertandingan</h2>
                    <p class="text-3xs text-slate-400">Gunakan bila pertandingan selesai lebih cepat atau molor dari jadwal rencana.</p>
                </div>
            </div>

            <form method="post" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end" data-confirm="Terapkan pergeseran waktu untuk pertandingan yang dipilih?">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="geser_jadwal">

                <div class="sm:col-span-5">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mulai Dari Pertandingan</label>
                    <select name="id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" required>
                        <?php foreach ($matches as $m): ?>
                            <option value="<?= $m['id'] ?>">
                                #<?= $m['nomor_pertandingan'] ?> &middot; <?= h(tgl($m['tanggal'])) ?> <?= h(jam($m['waktu'])) ?> (<?= h($m['nama_home']) ?> vs <?= h($m['nama_away']) ?> - <?= h($m['lokasi']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Geser Waktu</label>
                    <select name="menit" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" required>
                        <option value="-15">⚡ Percepat 15 Menit (-15m)</option>
                        <option value="-30">⚡ Percepat 30 Menit (-30m)</option>
                        <option value="-45">⚡ Percepat 45 Menit (-45m)</option>
                        <option value="15">⏳ Perlambat 15 Menit (+15m)</option>
                        <option value="30" selected>⏳ Perlambat 30 Menit (+30m)</option>
                        <option value="45">⏳ Perlambat 45 Menit (+45m)</option>
                        <option value="60">⏳ Perlambat 60 Menit (+60m)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cakupan</label>
                    <select name="cakupan" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                        <option value="semua_setelahnya">Match ini &amp; setelahnya (di hari tsb)</option>
                        <option value="hanya_satu">Hanya match ini saja</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <button class="w-full py-2 px-3 rounded-xl text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-xs">
                        Terapkan Geser Waktu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Editable Match Table -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-4 px-4 text-center w-12">#</th>
                        <th scope="col" class="py-4 px-3 text-center w-14">Ronde</th>
                        <th scope="col" class="py-4 px-4">Pertandingan</th>
                        <th scope="col" class="py-4 px-4 w-44">Tanggal</th>
                        <th scope="col" class="py-4 px-4 w-32">Jam</th>
                        <th scope="col" class="py-4 px-4 w-44">Lokasi / Meja</th>
                        <th scope="col" class="py-4 px-4">Status</th>
                        <th scope="col" class="py-4 px-4 text-center w-16">Simpan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($matches as $m): $fid = 'f' . $m['id']; ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 text-center font-mono text-xs text-slate-400"><?= (int) $m['nomor_pertandingan'] ?></td>
                            <td class="py-3 px-3 text-center font-mono text-xs font-bold text-indigo-600">R<?= (int) $m['ronde'] ?></td>
                            <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                                <?= h($m['nama_home']) ?> 
                                <?php if ($m['status'] === 'selesai' || $m['status'] === 'berlangsung'): ?>
                                    <span class="font-mono text-xs font-black bg-slate-900 text-white px-2 py-0.5 rounded mx-1"><?= (int) ($m['skor_home'] ?? 0) ?> - <?= (int) ($m['skor_away'] ?? 0) ?></span>
                                <?php else: ?>
                                    <span class="font-normal text-xs text-slate-400 px-1 py-0.5 rounded bg-slate-100 mx-1">vs</span>
                                <?php endif; ?>
                                <?= h($m['nama_away']) ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php if ($m['tanggal']): ?>
                                    <div class="text-3xs text-indigo-600 font-bold mb-1 flex items-center gap-1">
                                        <i class="bi bi-calendar2-day"></i> <?= h(hari_id($m['tanggal'])) ?>
                                    </div>
                                <?php endif; ?>
                                <input form="<?= $fid ?>" type="date" name="tanggal" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-slate-800 form-input-focus" value="<?= h($m['tanggal']) ?>">
                            </td>
                            <td class="py-3 px-4">
                                <input form="<?= $fid ?>" type="time" name="waktu" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-slate-800 form-input-focus" value="<?= h(jam($m['waktu']) === '-' ? '' : jam($m['waktu'])) ?>">
                            </td>
                            <td class="py-3 px-4">
                                <input form="<?= $fid ?>" name="lokasi" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-slate-800 form-input-focus" maxlength="60" value="<?= h($m['lokasi']) ?>">
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <?= badge_status($m['status']) ?>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <form id="<?= $fid ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="atur">
                                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                    <button class="p-2 rounded-xl text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 transition-colors" title="Simpan Slot Pertandingan">
                                        <i class="bi bi-floppy"></i>
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

<script>
function updateHariPill(cb) {
    var label = cb.closest('.hari-pill');
    if (!label) return;
    if (cb.checked) {
        label.classList.add('bg-indigo-50', 'border-indigo-200', 'text-indigo-900', 'font-bold', 'shadow-2xs');
        label.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
    } else {
        label.classList.remove('bg-indigo-50', 'border-indigo-200', 'text-indigo-900', 'font-bold', 'shadow-2xs');
        label.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
    }
}
function pilihHariPreset(days) {
    document.querySelectorAll('input[name="hari[]"]').forEach(function (cb) {
        cb.checked = days.indexOf(parseInt(cb.value, 10)) !== -1;
        updateHariPill(cb);
    });
}
function tambahTanggalLibur() {
    var input = document.getElementById('inputTanggalLibur');
    var val = input.value;
    if (!val) {
        alert('Silakan pilih tanggal terlebih dahulu.');
        return;
    }
    var existing = document.querySelectorAll('input[name="libur[]"]');
    for (var i = 0; i < existing.length; i++) {
        if (existing[i].value === val) {
            alert('Tanggal tersebut sudah ada di daftar libur.');
            return;
        }
    }
    var container = document.getElementById('containerLibur');
    var emptyNotice = document.getElementById('emptyLiburNotice');
    if (emptyNotice) emptyNotice.remove();

    var dateObj = new Date(val + 'T00:00:00');
    var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var dayName = days[dateObj.getDay()];
    var parts = val.split('-');
    var tglFmt = parts[2] + '-' + parts[1] + '-' + parts[0];

    var badge = document.createElement('span');
    badge.className = 'libur-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white text-rose-700 text-xs font-semibold border border-rose-200 shadow-2xs';
    badge.innerHTML = '<i class="bi bi-calendar-x text-rose-500 text-xs"></i><span>' + dayName + ', ' + tglFmt + '</span><input type="hidden" name="libur[]" value="' + val + '"><button type="button" onclick="hapusTanggalLibur(this)" class="text-slate-400 hover:text-rose-600 transition-colors ml-1 font-bold text-sm" title="Hapus tanggal libur">&times;</button>';
    container.appendChild(badge);
    input.value = '';
}
function hapusTanggalLibur(btn) {
    var badge = btn.closest('.libur-badge');
    if (badge) badge.remove();
    var container = document.getElementById('containerLibur');
    if (container.querySelectorAll('.libur-badge').length === 0) {
        container.innerHTML = '<span id="emptyLiburNotice" class="text-3xs text-slate-400 italic">Belum ada tanggal libur khusus yang ditambahkan (opsional).</span>';
    }
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
