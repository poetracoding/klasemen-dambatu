<!doctype html>
<html lang="id" class="h-full bg-slate-100 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ route('fcm.manifest', [], false) }}">
    <title>@yield('title', 'Dashboard') — {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    @php
        $gaId = $liga->google_analytics_id ?? (\App\Models\Liga::current()->google_analytics_id ?? null);
    @endphp
    @if (!empty($gaId))
        <!-- Google Analytics (GA4) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($gaId));
        </script>
    @endif
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
                        icon.className = 'theme-toggle-icon bi bi-moon-stars-fill text-indigo-500 text-base';
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
<body class="min-h-full flex flex-col text-slate-800 dark:text-slate-100 bg-slate-100 dark:bg-slate-950 antialiased font-sans transition-colors duration-200" data-area="public">
@php
    $fcmService = app(\App\Services\FcmService::class);
    $fcmReady = $fcmService->isClientReady();
@endphp

<!-- Public Navigation -->
<nav class="sticky top-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 shadow-xs transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4 h-16">
            <!-- Brand -->
            <a class="flex items-center gap-2.5 font-bold text-base xl:text-lg text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors whitespace-nowrap shrink-0" href="{{ route('home') }}">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 shrink-0">
                    <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                </span>
                <span class="tracking-tight">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center gap-1">
                @php
                    $navItems = [
                        ['route' => 'home', 'label' => 'Dashboard', 'icon' => 'bi-house'],
                        ['route' => 'jadwal.hari', 'label' => 'Jadwal Hari Ini', 'icon' => 'bi-calendar-day'],
                        ['route' => 'jadwal', 'label' => 'Jadwal Liga', 'icon' => 'bi-calendar3'],
                        ['route' => 'klasemen', 'label' => 'Klasemen', 'icon' => 'bi-list-ol'],
                        ['route' => 'history', 'label' => 'History', 'icon' => 'bi-clock-history'],
                        ['route' => 'berita.index', 'label' => 'Berita', 'icon' => 'bi-newspaper', 'pattern' => 'berita.*'],
                    ];
                    $isGameActive = request()->routeIs('game') || request()->routeIs('game.*') || request()->routeIs('games.*');
                    $gameLinks = [
                        ['url' => route('games.hub'), 'label' => 'Semua Game (Arena Hub)', 'icon' => 'bi-grid-fill', 'active' => request()->routeIs('games.hub')],
                        ['url' => route('game'), 'label' => 'Game Dam Batu', 'icon' => 'bi-dice-6-fill', 'active' => request()->routeIs('game')],
                        ['url' => route('game.monopoli'), 'label' => 'Monopoli Nusantara', 'icon' => 'bi-buildings-fill', 'active' => request()->routeIs('game.monopoli')],
                        ['url' => route('game.flappy_bird'), 'label' => 'Flappy Bird', 'icon' => 'bi-feather', 'active' => request()->routeIs('game.flappy_bird')],
                        ['url' => route('game.ludo_queen'), 'label' => 'Ludo Queen 3D', 'icon' => 'bi-dice-5-fill', 'active' => request()->routeIs('game.ludo_queen')],
                        ['url' => route('game.catur'), 'label' => 'Catur 3D', 'icon' => 'bi-grid-3x3-gap-fill', 'active' => request()->routeIs('game.catur')],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['pattern'] ?? $item['route']); @endphp
                    <a class="flex items-center gap-1.5 px-2.5 xl:px-3 py-2 rounded-lg text-[13px] xl:text-sm font-medium whitespace-nowrap shrink-0 transition-all {{ $active ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }} text-base shrink-0"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <!-- Dropdown Game Publik -->
                <div class="relative shrink-0" id="publicGameDropdownWrap">
                    <button type="button" id="publicGameDropdownBtn"
                        class="flex items-center gap-1.5 px-2.5 xl:px-3 py-2 rounded-lg text-[13px] xl:text-sm font-medium whitespace-nowrap shrink-0 transition-all cursor-pointer {{ $isGameActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60' }}"
                        aria-expanded="false" aria-haspopup="true">
                        <i class="bi bi-controller text-base shrink-0"></i>
                        <span>Game</span>
                        <i class="bi bi-chevron-down text-xs transition-transform duration-150" id="publicGameDropdownChevron"></i>
                    </button>

                    <div id="publicGameDropdownMenu"
                        class="hidden absolute right-0 mt-2 w-56 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700/90 shadow-xl py-2 z-50">
                        <div class="px-4 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Arena Hiburan
                        </div>
                        @foreach ($gameLinks as $g)
                            <a href="{{ $g['url'] }}"
                                onclick="closePublicGameDropdown()"
                                class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium transition-colors {{ $g['active'] ? 'bg-indigo-50 dark:bg-slate-800/90 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/70' }}">
                                <i class="bi {{ $g['icon'] }} text-sm shrink-0"></i>
                                <span>{{ $g['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="hidden lg:flex items-center gap-2 shrink-0">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/80 whitespace-nowrap shrink-0" title="Data akan refresh otomatis bila ada skor baru">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                    <span class="hidden xl:inline">Live Otomatis</span>
                    <span class="xl:hidden">Live</span>
                </div>

                @if ($fcmReady)
                    <!-- FCM Notification Toggle Button -->
                    <button type="button"
                        class="fcm-toggle-btn relative p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shrink-0"
                        title="Aktifkan Notifikasi Skor & Berita" aria-label="Toggle Notifikasi Push">
                        <i class="fcm-bell-icon bi bi-bell text-base"></i>
                        <span class="fcm-bell-dot hidden absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-indigo-500 ring-2 ring-white dark:ring-slate-900"></span>
                    </button>
                @endif

                <!-- Theme Toggle Button -->
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer shrink-0" title="Beralih Mode Gelap/Terang" aria-label="Toggle Theme">
                    <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-500 dark:text-amber-400 text-base"></i>
                </button>

                @auth
                    <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 dark:bg-indigo-600 text-white hover:bg-slate-800 dark:hover:bg-indigo-700 shadow-xs transition-colors whitespace-nowrap shrink-0" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-shield-lock shrink-0"></i>
                        <span>Panel Admin</span>
                    </a>
                @else
                    <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors whitespace-nowrap shrink-0" href="{{ route('login') }}">
                        <i class="bi bi-person-lock shrink-0"></i>
                        <span>Admin</span>
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex lg:hidden items-center gap-1.5 shrink-0">
                @if ($fcmReady)
                    <button type="button"
                        class="fcm-toggle-btn relative p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none cursor-pointer"
                        title="Aktifkan Notifikasi Skor & Berita" aria-label="Toggle Notifikasi Push">
                        <i class="fcm-bell-icon bi bi-bell text-base"></i>
                        <span class="fcm-bell-dot hidden absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-indigo-500 ring-2 ring-white dark:ring-slate-900"></span>
                    </button>
                @endif
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none" title="Beralih Mode Gelap/Terang" aria-label="Toggle Theme">
                    <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-500 dark:text-amber-400 text-base"></i>
                </button>
                <button type="button" id="navToggle" class="p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none" aria-label="Toggle Navigation">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu dropdown -->
    <div id="mobileMenu" class="hidden lg:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 pt-2 pb-4 space-y-1 shadow-lg">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['pattern'] ?? $item['route']); @endphp
            <a class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }} text-lg"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        <!-- Mobile Dropdown Game -->
        <div class="pt-1">
            <button type="button" id="mobileGameBtn"
                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors cursor-pointer {{ $isGameActive ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white' }}">
                <span class="flex items-center gap-2.5">
                    <i class="bi bi-controller text-lg"></i>
                    <span>Game</span>
                </span>
                <i class="bi bi-chevron-down text-xs transition-transform duration-150 {{ $isGameActive ? 'rotate-180' : '' }}" id="mobileGameChevron"></i>
            </button>
            <div id="mobileGameMenu" class="{{ $isGameActive ? '' : 'hidden' }} pl-8 pr-2 py-1 space-y-1">
                @foreach ($gameLinks as $g)
                    <a href="{{ $g['url'] }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ $g['active'] ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/70 dark:bg-slate-800/70 font-semibold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50' }}">
                        <i class="bi {{ $g['icon'] }} text-sm"></i>
                        <span>{{ $g['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <button type="button" onclick="toggleTheme()" class="theme-toggle-btn flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-500 dark:text-amber-400"></i>
                <span class="theme-toggle-text">Mode Gelap</span>
            </button>
            @auth
                <a class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 flex items-center gap-1" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-shield-lock"></i> Masuk Panel Admin
                </a>
            @else
                <a class="text-xs text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white flex items-center gap-1" href="{{ route('login') }}">
                    <i class="bi bi-person-lock"></i> Login Admin
                </a>
            @endauth
        </div>
    </div>
</nav>

<!-- Running Text Ticker Pertandingan -->
@include('partials.running_text')

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
            Sistem Manajemen Turnamen Dam Batu &middot; Laravel 12
        </div>
    </div>
</footer>

<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
<script>
    (function () {
        const btn = document.getElementById('publicGameDropdownBtn');
        const menu = document.getElementById('publicGameDropdownMenu');
        const chevron = document.getElementById('publicGameDropdownChevron');
        const wrap = document.getElementById('publicGameDropdownWrap');

        window.closePublicGameDropdown = function () {
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
                    window.closePublicGameDropdown();
                }
            });

            document.addEventListener('click', function (e) {
                if (wrap && !wrap.contains(e.target)) {
                    window.closePublicGameDropdown();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    window.closePublicGameDropdown();
                }
            });
        }

        const mobBtn = document.getElementById('mobileGameBtn');
        const mobMenu = document.getElementById('mobileGameMenu');
        const mobChevron = document.getElementById('mobileGameChevron');
        if (mobBtn && mobMenu) {
            mobBtn.addEventListener('click', function () {
                mobMenu.classList.toggle('hidden');
                if (mobChevron) mobChevron.classList.toggle('rotate-180');
            });
        }
    })();
</script>
@if ($fcmReady)
    @include('partials.fcm_prompt_modal')
    @include('partials.fcm_script', ['fcmConfig' => $fcmService->webConfig()])
@endif
@yield('scripts')
</body>
</html>

