<!doctype html>
<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
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
                    var saved = localStorage.getItem('theme');
                    if (saved === 'dark' || saved === 'light') return saved;
                } catch (e) {}
                return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
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
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                } catch (e) {}
                updateThemeUI(isDark);
            }

            window.toggleTheme = function () {
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
<body class="min-h-full flex flex-col text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-950 antialiased font-sans transition-colors duration-200" data-area="public">

<!-- Public Navigation -->
<nav class="sticky top-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 shadow-xs transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <a class="flex items-center gap-2.5 font-bold text-lg text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" href="{{ route('home') }}">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                    <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                </span>
                <span class="tracking-tight">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1">
                @php
                    $navItems = [
                        ['route' => 'home', 'label' => 'Dashboard', 'icon' => 'bi-house'],
                        ['route' => 'jadwal.hari', 'label' => 'Jadwal Hari Ini', 'icon' => 'bi-calendar-day'],
                        ['route' => 'jadwal', 'label' => 'Jadwal Liga', 'icon' => 'bi-calendar3'],
                        ['route' => 'klasemen', 'label' => 'Klasemen', 'icon' => 'bi-list-ol'],
                        ['route' => 'history', 'label' => 'History', 'icon' => 'bi-clock-history'],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $active ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/70 dark:hover:bg-slate-800/60' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }} text-base"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <!-- Right Actions -->
            <div class="hidden md:flex items-center space-x-2">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/80 mr-1" title="Data akan refresh otomatis bila ada skor baru">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Live Otomatis</span>
                </div>

                <!-- Theme Toggle Button -->
                <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer" title="Beralih Mode Gelap/Terang" aria-label="Toggle Theme">
                    <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-500 dark:text-amber-400 text-base"></i>
                </button>

                @auth
                    <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 dark:bg-indigo-600 text-white hover:bg-slate-800 dark:hover:bg-indigo-700 shadow-xs transition-colors" href="{{ route('admin.dashboard') }}">
                        <i class="bi bi-shield-lock"></i>
                        <span>Panel Admin</span>
                    </a>
                @else
                    <a class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" href="{{ route('login') }}">
                        <i class="bi bi-person-lock"></i>
                        <span>Admin</span>
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex md:hidden items-center gap-1.5">
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
    <div id="mobileMenu" class="hidden md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 pt-2 pb-4 space-y-1 shadow-lg">
        @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }} text-lg"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
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
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-800 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-check-circle-fill text-emerald-500 text-lg flex-shrink-0"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if (session('danger') || session('error'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-rose-50 border-rose-200 text-rose-800 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-x-circle-fill text-rose-500 text-lg flex-shrink-0"></i>
                <div>{{ session('danger') ?? session('error') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-amber-50 border-amber-200 text-amber-800 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-exclamation-triangle-fill text-amber-500 text-lg flex-shrink-0"></i>
                <div>{{ session('warning') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if (session('info'))
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-indigo-50 border-indigo-200 text-indigo-800 shadow-xs transition-all" role="alert">
            <div class="flex items-center gap-3 text-sm font-medium">
                <i class="bi bi-info-circle-fill text-indigo-500 text-lg flex-shrink-0"></i>
                <div>{{ session('info') }}</div>
            </div>
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert-box flex items-start justify-between p-4 mb-5 rounded-xl border bg-rose-50 border-rose-200 text-rose-800 shadow-xs transition-all" role="alert">
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
            <button type="button" class="alert-close-btn -mr-1 -mt-1 p-1.5 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 transition-opacity" aria-label="Close">
                <i class="bi bi-x-lg text-xs"></i>
            </button>
        </div>
    @endif

    @yield('content')
</main>

<footer class="border-t border-slate-200/80 bg-white/60 py-6 text-center text-xs text-slate-500 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="font-medium text-slate-700">
            {{ $liga->nama_liga ?? 'Liga Dam Batu' }} <span class="text-slate-400 font-normal">&middot; Musim {{ $liga->musim ?? date('Y') }}</span>
        </div>
        <div class="text-slate-400">
            Sistem Manajemen Turnamen Dam Batu &middot; Laravel 12
        </div>
    </div>
</footer>

<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
@yield('scripts')
</body>
</html>

