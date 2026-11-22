<!doctype html>
<html lang="id" class="h-full bg-slate-100 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel') — {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
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
                    fontSize: {
                        '3xs': ['0.625rem', { lineHeight: '0.85rem' }],
                        '2xs': ['0.7rem', { lineHeight: '1rem' }],
                    },
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        // Init theme immediately & define global toggleTheme to support instant dark/light switching
        (function () {
            function getPreferredTheme() {
                try {
                    var saved = localStorage.getItem('theme_v2');
                    if (saved === 'dark' || saved === 'light') return saved;
                } catch (e) {}
                return 'light'; // Default ke Light Mode
            }

            function updateThemeUI(isDark) {
                document.querySelectorAll('.theme-toggle-icon').forEach(function (icon) {
                    if (isDark) {
                        icon.className = 'theme-toggle-icon bi bi-sun-fill text-amber-400 text-base';
                    } else {
                        icon.className = 'theme-toggle-icon bi bi-moon-stars-fill text-indigo-400 text-base';
                    }
                });
                document.querySelectorAll('.theme-toggle-text').forEach(function (el) {
                    el.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
                });
            }

            function applyTheme(theme) {
                var isDark = (theme === 'dark');
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                try {
                    localStorage.setItem('theme_v2', isDark ? 'dark' : 'light');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                } catch (e) {}
                updateThemeUI(isDark);
            }

            var _themeToggleLocked = false;
            window.toggleTheme = function (e) {
                if (e && e.preventDefault) e.preventDefault();
                if (_themeToggleLocked) return;
                _themeToggleLocked = true;
                setTimeout(function () {
                    _themeToggleLocked = false;
                }, 200);

                var isDark = document.documentElement.classList.contains('dark');
                applyTheme(isDark ? 'light' : 'dark');
            };

            window.applyTheme = applyTheme;
            window.updateThemeUI = updateThemeUI;

            // Apply immediately to <html> root before page renders
            var initial = getPreferredTheme();
            if (initial === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Sync icon & bind toggle buttons on load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    updateThemeUI(document.documentElement.classList.contains('dark'));
                });
            } else {
                updateThemeUI(document.documentElement.classList.contains('dark'));
            }
        })();
    </script>
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom Styles -->
    <link href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}" rel="stylesheet">
    <script>window.BASE_URL = '{{ url('/') }}';</script>
</head>
<body class="min-h-full flex flex-col text-slate-800 dark:text-slate-100 bg-slate-100 dark:bg-slate-950 antialiased font-sans transition-colors duration-200" data-area="admin">

<!-- Admin Navigation -->
<nav class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 text-slate-100 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 h-16">
            <!-- Brand -->
            <div class="flex items-center gap-2.5 shrink-0">
                <a class="flex items-center gap-2.5 font-bold text-base xl:text-lg text-white hover:text-indigo-300 transition-colors whitespace-nowrap shrink-0" href="{{ route('admin.dashboard') }}">
                    <span class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/30 shrink-0">
                        <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                    </span>
                    <span class="tracking-tight">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</span>
                </a>
                <span class="hidden xl:inline-flex px-2 py-0.5 text-xs font-semibold rounded-md bg-indigo-950/80 text-indigo-300 border border-indigo-700/50 shrink-0 whitespace-nowrap">Admin</span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-1">
                @php
                    $adminNav = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                        ['route' => 'admin.tim.index', 'label' => 'Tim & Pemain', 'icon' => 'bi-people'],
                        ['route' => 'admin.jadwal.index', 'label' => 'Jadwal', 'icon' => 'bi-calendar3'],
                        ['route' => 'admin.pertandingan.index', 'label' => 'Input Hasil', 'icon' => 'bi-trophy'],
                        ['route' => 'admin.tren_tim.index', 'label' => 'Tren Tim', 'icon' => 'bi-activity'],
                    ];
                    $isPengaturanActive = request()->routeIs('admin.pengaturan.*') || request()->routeIs('admin.poster.*') || request()->routeIs('admin.berita.*');
                    $pengaturanLinks = [
                        ['header' => 'Sistem & Turnamen'],
                        ['url' => route('admin.pengaturan.index'), 'label' => 'Informasi Liga', 'icon' => 'bi-sliders', 'active' => request()->routeIs('admin.pengaturan.index')],
                        ['url' => route('admin.poster.index'), 'label' => 'Poster Informasi', 'icon' => 'bi-images', 'active' => request()->routeIs('admin.poster.*')],
                        ['url' => route('admin.berita.index'), 'label' => 'Kelola Berita', 'icon' => 'bi-newspaper', 'active' => request()->routeIs('admin.berita.*')],
                        ['url' => route('admin.pengaturan.password'), 'label' => 'Ganti Password', 'icon' => 'bi-shield-lock', 'active' => request()->routeIs('admin.pengaturan.password')],
                        ['url' => route('admin.pengaturan.backup_restore'), 'label' => 'Backup & Restore', 'icon' => 'bi-cloud-arrow-down', 'active' => request()->routeIs('admin.pengaturan.backup_restore')],
                        ['divider' => true],
                        ['header' => 'Laporan & Analitik'],
                        ['url' => route('admin.pengaturan.report'), 'label' => 'Laporan Hasil', 'icon' => 'bi-file-earmark-bar-graph', 'active' => request()->routeIs('admin.pengaturan.report*')],
                        ['url' => route('admin.pengaturan.analytics'), 'label' => 'Analytics', 'icon' => 'bi-graph-up-arrow', 'active' => request()->routeIs('admin.pengaturan.analytics')],
                        ['divider' => true],
                        ['header' => 'Mini Games Admin'],
                        ['url' => route('admin.pengaturan.monopoli'), 'label' => 'Game Monopoli', 'icon' => 'bi-buildings', 'active' => request()->routeIs('admin.pengaturan.monopoli')],
                        ['url' => route('admin.pengaturan.flappy_bird'), 'label' => 'Game Flappy Bird', 'icon' => 'bi-feather', 'active' => request()->routeIs('admin.pengaturan.flappy_bird')],
                        ['url' => route('admin.pengaturan.ludo_queen'), 'label' => 'Game Ludo Queen', 'icon' => 'bi-dice-5', 'active' => request()->routeIs('admin.pengaturan.ludo_queen')],
                        ['url' => route('admin.pengaturan.catur'), 'label' => 'Game Catur 3D', 'icon' => 'bi-grid-3x3-gap-fill', 'active' => request()->routeIs('admin.pengaturan.catur')],
                    ];
                @endphp
                @foreach ($adminNav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a class="flex items-center gap-1.5 px-2.5 xl:px-3 py-2 rounded-lg text-[13px] xl:text-sm font-medium whitespace-nowrap shrink-0 transition-colors {{ $active ? 'bg-slate-800 text-indigo-400 font-semibold shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }} text-base shrink-0"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <!-- Dropdown Pengaturan -->
                <div class="relative shrink-0" id="pengaturanDropdownWrap">
                    <button type="button" id="pengaturanDropdownBtn"
                        class="flex items-center gap-1.5 px-2.5 xl:px-3 py-2 rounded-lg text-[13px] xl:text-sm font-medium whitespace-nowrap shrink-0 transition-colors cursor-pointer {{ $isPengaturanActive ? 'bg-slate-800 text-indigo-400 font-semibold shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}"
                        aria-expanded="false" aria-haspopup="true">
                        <i class="bi bi-gear text-base shrink-0"></i>
                        <span>Pengaturan</span>
                        <i class="bi bi-chevron-down text-xs transition-transform duration-150" id="pengaturanDropdownChevron"></i>
                    </button>

                    <div id="pengaturanDropdownMenu"
                        class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-slate-900 border border-slate-700/90 shadow-2xl py-2 z-50">
                        @foreach ($pengaturanLinks as $sub)
                            @if (!empty($sub['divider']))
                                <div class="my-1.5 border-t border-slate-800"></div>
                            @elseif (!empty($sub['header']))
                                <div class="px-4 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    {{ $sub['header'] }}
                                </div>
                            @else
                                <a href="{{ $sub['url'] }}"
                                    onclick="closePengaturanDropdown()"
                                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium transition-colors {{ !empty($sub['active']) ? 'bg-slate-800/90 text-indigo-400 font-semibold' : 'text-slate-300 hover:text-white hover:bg-slate-800/70' }}">
                                    <i class="bi {{ $sub['icon'] }} text-sm shrink-0"></i>
                                    <span>{{ $sub['label'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="hidden lg:flex items-center gap-2 shrink-0">
                <!-- Theme Toggle Button -->
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer shrink-0" title="Beralih Mode Gelap/Terang" aria-label="Toggle Theme">
                    <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-400 text-base"></i>
                </button>

                <a class="flex items-center gap-1.5 px-2.5 xl:px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors whitespace-nowrap shrink-0" href="{{ route('home') }}" title="Lihat Situs Publik">
                    <i class="bi bi-box-arrow-up-right shrink-0"></i>
                    <span class="hidden xl:inline">Lihat Situs</span>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline shrink-0 form-logout" data-confirm="Apakah Anda yakin ingin keluar dari panel admin?" data-confirm-title="Konfirmasi Keluar" data-confirm-btn="Ya, Keluar">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-300 hover:text-white hover:bg-rose-900/40 border border-rose-800/40 transition-colors cursor-pointer whitespace-nowrap shrink-0">
                        <i class="bi bi-box-arrow-right shrink-0"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex lg:hidden items-center gap-1.5 shrink-0">
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none" title="Beralih Mode Gelap/Terang" aria-label="Toggle Theme">
                    <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-400 text-base"></i>
                </button>
                <button type="button" id="navToggle" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none" aria-label="Toggle Navigation">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobileMenu" class="hidden lg:hidden border-t border-slate-800 bg-slate-900 px-4 pt-2 pb-4 space-y-1">
        @foreach ($adminNav as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-slate-800 text-indigo-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }} text-lg"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <!-- Mobile Dropdown Pengaturan -->
        <div class="pt-1">
            <button type="button" id="mobilePengaturanBtn"
                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors cursor-pointer {{ $isPengaturanActive ? 'bg-slate-800 text-indigo-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                <span class="flex items-center gap-2.5">
                    <i class="bi bi-gear text-lg"></i>
                    <span>Pengaturan</span>
                </span>
                <i class="bi bi-chevron-down text-xs transition-transform duration-150 {{ $isPengaturanActive ? 'rotate-180' : '' }}" id="mobilePengaturanChevron"></i>
            </button>
            <div id="mobilePengaturanMenu" class="{{ $isPengaturanActive ? '' : 'hidden' }} pl-8 pr-2 py-1 space-y-1">
                @foreach ($pengaturanLinks as $sub)
                    @if (!empty($sub['header']))
                        <div class="px-3 pt-2 pb-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            {{ $sub['header'] }}
                        </div>
                    @elseif (empty($sub['divider']))
                        <a href="{{ $sub['url'] }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ !empty($sub['active']) ? 'text-indigo-400 bg-slate-800/70 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            <i class="bi {{ $sub['icon'] }} text-sm"></i>
                            <span>{{ $sub['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
            <button type="button" onclick="toggleTheme()" class="theme-toggle-btn flex items-center gap-2 text-xs font-medium text-slate-300 hover:text-white cursor-pointer">
                <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-400"></i>
                <span class="theme-toggle-text">Mode Gelap</span>
            </button>
            <div class="flex items-center gap-3">
                <a class="text-xs text-slate-300 hover:text-white flex items-center gap-1" href="{{ route('home') }}">
                    <i class="bi bi-box-arrow-up-right"></i> Lihat Situs
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline form-logout" data-confirm="Apakah Anda yakin ingin keluar dari panel admin?" data-confirm-title="Konfirmasi Keluar" data-confirm-btn="Ya, Keluar">
                    @csrf
                    <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 flex items-center gap-1 cursor-pointer">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Main Wrapper -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-800/70 text-emerald-800 dark:text-emerald-200 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-check-circle-fill text-emerald-500 text-lg flex-shrink-0"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if (session('danger') || session('error'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-rose-50 dark:bg-rose-950/60 border-rose-200 dark:border-rose-800/70 text-rose-800 dark:text-rose-200 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-x-circle-fill text-rose-500 text-lg flex-shrink-0"></i>
                <div>{{ session('danger') ?? session('error') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-amber-50 dark:bg-amber-950/60 border-amber-200 dark:border-amber-800/70 text-amber-800 dark:text-amber-200 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-lg flex-shrink-0"></i>
                <div>{{ session('warning') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200 dark:border-indigo-800/70 text-indigo-800 dark:text-indigo-200 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-info-circle-fill text-indigo-500 text-lg flex-shrink-0"></i>
                <div>{{ session('info') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-rose-50 dark:bg-rose-950/60 border-rose-200 dark:border-rose-800/70 text-rose-800 dark:text-rose-200 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-x-circle-fill text-rose-500 text-lg flex-shrink-0"></i>
                <div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @yield('content')
</main>

<footer class="border-t border-slate-200/80 dark:border-slate-800 bg-white/60 dark:bg-slate-900/80 py-6 text-center text-xs text-slate-500 dark:text-slate-400 mt-auto transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="font-medium text-slate-700 dark:text-slate-200">
            {{ $liga->nama_liga ?? 'Liga Dam Batu' }} <span class="text-slate-400 dark:text-slate-500 font-normal">&middot; Musim {{ $liga->musim ?? date('Y') }}</span>
        </div>
        <div class="text-slate-400 dark:text-slate-500">
            Panel Administrator &middot; Laravel 12
        </div>
    </div>
</footer>

<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
<script>
    (function () {
        const btn = document.getElementById('pengaturanDropdownBtn');
        const menu = document.getElementById('pengaturanDropdownMenu');
        const chevron = document.getElementById('pengaturanDropdownChevron');
        const wrap = document.getElementById('pengaturanDropdownWrap');

        window.closePengaturanDropdown = function () {
            if (menu) menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        };

        if (btn && menu) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isHidden = menu.classList.contains('hidden');
                if (isHidden) {
                    menu.classList.remove('hidden');
                    if (chevron) chevron.classList.add('rotate-180');
                    btn.setAttribute('aria-expanded', 'true');
                } else {
                    window.closePengaturanDropdown();
                }
            });

            document.addEventListener('click', function (e) {
                if (wrap && !wrap.contains(e.target)) {
                    window.closePengaturanDropdown();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    window.closePengaturanDropdown();
                }
            });
        }

        const mobBtn = document.getElementById('mobilePengaturanBtn');
        const mobMenu = document.getElementById('mobilePengaturanMenu');
        const mobChevron = document.getElementById('mobilePengaturanChevron');
        if (mobBtn && mobMenu) {
            mobBtn.addEventListener('click', function () {
                mobMenu.classList.toggle('hidden');
                if (mobChevron) mobChevron.classList.toggle('rotate-180');
            });
        }
    })();
</script>
@yield('scripts')
</body>
</html>

