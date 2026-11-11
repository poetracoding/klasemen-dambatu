<!doctype html>
<html lang="id" class="h-full bg-slate-100 dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
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
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <script>
        // Init theme immediately & define global toggleTheme
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

            var initial = getPreferredTheme();
            if (initial === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

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
    <!-- Custom Style -->
    <link href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}" rel="stylesheet">
</head>
<body class="h-full flex items-center justify-center p-4 bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased font-sans transition-colors duration-200">

<!-- Floating Theme Toggle -->
<div class="fixed top-4 right-4 z-50">
    <button type="button" onclick="toggleTheme()" class="theme-toggle-btn p-2.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors cursor-pointer" title="Beralih Mode Gelap/Terang" aria-label="Toggle Theme">
        <i class="theme-toggle-icon bi bi-moon-stars-fill text-indigo-500 dark:text-amber-400 text-base"></i>
    </button>
</div>

<div class="w-full max-w-md">
    <!-- Card Container -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 sm:p-10 border border-slate-200/80 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none transition-colors">
        <!-- Logo / Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 mb-4">
                <i class="bi bi-shield-lock-fill text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Login Administrator</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $liga->nama_liga ?? 'Liga Dam Batu' }} &middot; Panel Kontrol</p>
        </div>

        @if (session('error') || $errors->any())
            <div class="mb-5 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 text-sm"></i>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="username">Username atau Email</label>
                <div class="relative">
                    <i class="bi bi-person absolute left-3.5 top-3 text-sm text-slate-400"></i>
                    <input type="text" id="username" name="username" value="{{ old('username', 'admin') }}" required autofocus
                           class="w-full text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/80 py-2.5 pl-10 pr-3 text-slate-800 dark:text-slate-100 form-input-focus"
                           placeholder="admin">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5" for="password">Password</label>
                <div class="relative">
                    <i class="bi bi-key absolute left-3.5 top-3 text-sm text-slate-400"></i>
                    <input type="password" id="password" name="password" required
                           class="w-full text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/80 py-2.5 pl-10 pr-3 text-slate-800 dark:text-slate-100 form-input-focus"
                           placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-slate-600 dark:text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-md shadow-indigo-600/20 cursor-pointer">
                Masuk ke Panel Admin
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition-colors">
                <i class="bi bi-arrow-left"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/app.js') }}?v={{ filemtime(public_path('assets/js/app.js')) }}"></script>
</body>
</html>

</body>
</html>

