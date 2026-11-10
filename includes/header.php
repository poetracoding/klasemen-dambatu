<?php
// Variabel yang dibutuhkan: $judul, $menu (kunci menu aktif), $area ('public' | 'admin')
$area = $area ?? 'public';
$menu = $menu ?? '';
$ligaH = liga();
$nav = $area === 'admin'
    ? [
        'dashboard'  => ['admin/index.php', 'bi-speedometer2', 'Dashboard'],
        'tim'        => ['admin/tim.php', 'bi-people', 'Tim & Pemain'],
        'jadwal'     => ['admin/jadwal.php', 'bi-calendar3', 'Jadwal'],
        'hasil'      => ['admin/pertandingan.php', 'bi-trophy', 'Input Hasil'],
        'pengaturan' => ['admin/pengaturan.php', 'bi-gear', 'Pengaturan'],
    ]
    : [
        'dashboard' => ['index.php', 'bi-house', 'Dashboard'],
        'hari_ini'  => ['jadwal_hari.php', 'bi-calendar-day', 'Jadwal Hari Ini'],
        'jadwal'    => ['jadwal.php', 'bi-calendar3', 'Jadwal Liga'],
        'klasemen'  => ['klasemen.php', 'bi-list-ol', 'Klasemen'],
        'history'   => ['history.php', 'bi-clock-history', 'History'],
    ];
?><!doctype html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($judul ?? 'Liga') ?> — <?= h($ligaH['nama_liga']) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="<?= h(url('assets/css/style.css')) ?>" rel="stylesheet">
    <script>window.BASE_URL = '<?= h(rtrim(BASE_URL, '/')) ?>';</script>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased font-sans" data-area="<?= h($area) ?>">

<?php if ($area === 'admin'): ?>
<!-- Admin Navigation -->
<nav class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 text-slate-100 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <a class="flex items-center gap-2.5 font-bold text-lg text-white hover:text-indigo-300 transition-colors" href="<?= h(url('admin/index.php')) ?>">
                    <span class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                        <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                    </span>
                    <span class="tracking-tight"><?= h($ligaH['nama_liga']) ?></span>
                </a>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-indigo-950/80 text-indigo-300 border border-indigo-700/50">Admin</span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1">
                <?php foreach ($nav as $k => [$href, $ico, $label]): ?>
                    <a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $menu === $k ? 'bg-slate-800 text-indigo-400 font-semibold shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' ?>" href="<?= h(url($href)) ?>">
                        <i class="bi <?= $ico ?> text-base"></i>
                        <span><?= h($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Right Actions -->
            <div class="hidden md:flex items-center space-x-2">
                <a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors" href="<?= h(url('index.php')) ?>">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Lihat Situs</span>
                </a>
                <a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-300 hover:text-white hover:bg-rose-900/40 border border-rose-800/40 transition-colors" href="<?= h(url('logout.php')) ?>">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar</span>
                </a>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex md:hidden">
                <button type="button" id="navToggle" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none" aria-label="Toggle Navigation">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobileMenu" class="hidden md:hidden border-t border-slate-800 bg-slate-900 px-4 pt-2 pb-4 space-y-1">
        <?php foreach ($nav as $k => [$href, $ico, $label]): ?>
            <a class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $menu === $k ? 'bg-slate-800 text-indigo-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' ?>" href="<?= h(url($href)) ?>">
                <i class="bi <?= $ico ?> text-lg"></i>
                <span><?= h($label) ?></span>
            </a>
        <?php endforeach; ?>
        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
            <a class="text-xs text-slate-300 hover:text-white flex items-center gap-1" href="<?= h(url('index.php')) ?>">
                <i class="bi bi-box-arrow-up-right"></i> Lihat Situs
            </a>
            <a class="text-xs text-rose-300 hover:text-rose-200 flex items-center gap-1" href="<?= h(url('logout.php')) ?>">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </div>
</nav>

<?php else: ?>
<!-- Public Navigation -->
<nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <a class="flex items-center gap-2.5 font-bold text-lg text-slate-900 hover:text-indigo-600 transition-colors" href="<?= h(url('index.php')) ?>">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                    <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                </span>
                <span class="tracking-tight"><?= h($ligaH['nama_liga']) ?></span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1">
                <?php foreach ($nav as $k => [$href, $ico, $label]): ?>
                    <a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all <?= $menu === $k ? 'bg-indigo-50 text-indigo-600 font-semibold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/70' ?>" href="<?= h(url($href)) ?>">
                        <i class="bi <?= $ico ?> text-base"></i>
                        <span><?= h($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Right Actions -->
            <div class="hidden md:flex items-center space-x-2">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 mr-1" title="Data akan refresh otomatis bila ada skor baru">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Otomatis</span>
                </div>
                <?php if (!empty($_SESSION['admin_id'])): ?>
                    <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 shadow-xs transition-colors" href="<?= h(url('admin/index.php')) ?>">
                        <i class="bi bi-shield-lock"></i>
                        <span>Panel Admin</span>
                    </a>
                <?php else: ?>
                    <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors" href="<?= h(url('login.php')) ?>">
                        <i class="bi bi-person-lock"></i>
                        <span>Admin</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex md:hidden">
                <button type="button" id="navToggle" class="p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none" aria-label="Toggle Navigation">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-1 shadow-lg">
        <?php foreach ($nav as $k => [$href, $ico, $label]): ?>
            <a class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $menu === $k ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>" href="<?= h(url($href)) ?>">
                <i class="bi <?= $ico ?> text-lg"></i>
                <span><?= h($label) ?></span>
            </a>
        <?php endforeach; ?>
        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
            <?php if (!empty($_SESSION['admin_id'])): ?>
                <a class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1" href="<?= h(url('admin/index.php')) ?>">
                    <i class="bi bi-shield-lock"></i> Masuk Panel Admin
                </a>
            <?php else: ?>
                <a class="text-xs text-slate-600 hover:text-slate-900 flex items-center gap-1" href="<?= h(url('login.php')) ?>">
                    <i class="bi bi-person-lock"></i> Login Admin
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Main Wrapper -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <!-- Flash Messages -->
    <?php foreach (flash_ambil() as $f):
        $tipe = $f['t'] ?? 'info';
        $alertStyles = [
            'success' => ['bg' => 'bg-emerald-50 border-emerald-200 text-emerald-800', 'icon' => 'bi-check-circle-fill text-emerald-500'],
            'danger'  => ['bg' => 'bg-rose-50 border-rose-200 text-rose-800', 'icon' => 'bi-x-circle-fill text-rose-500'],
            'warning' => ['bg' => 'bg-amber-50 border-amber-200 text-amber-800', 'icon' => 'bi-exclamation-triangle-fill text-amber-500'],
            'info'    => ['bg' => 'bg-indigo-50 border-indigo-200 text-indigo-800', 'icon' => 'bi-info-circle-fill text-indigo-500'],
        ];
        $st = $alertStyles[$tipe] ?? $alertStyles['info'];
    ?>
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border <?= $st['bg'] ?> shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi <?= $st['icon'] ?> text-lg flex-shrink-0"></i>
                <div><?= h($f['m']) ?></div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    <?php endforeach; ?>
