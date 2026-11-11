<!doctype html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
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
    <!-- Custom Style -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-50 via-slate-100 to-indigo-50/30 text-slate-800 antialiased font-sans">

<div class="w-full max-w-md">
    <!-- Card Container -->
    <div class="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200/80 shadow-xl shadow-slate-200/50">
        <!-- Logo / Icon -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 mb-4">
                <i class="bi bi-shield-lock-fill text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Login Administrator</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $liga->nama_liga ?? 'Liga Dam Batu' }} &middot; Panel Kontrol</p>
        </div>

        @if (session('error') || $errors->any())
            <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-rose-500 text-sm"></i>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="username">Username atau Email</label>
                <div class="relative">
                    <i class="bi bi-person absolute left-3.5 top-3 text-sm text-slate-400"></i>
                    <input type="text" id="username" name="username" value="{{ old('username', 'admin') }}" required autofocus
                           class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-3 text-slate-800 form-input-focus"
                           placeholder="admin">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="password">Password</label>
                <div class="relative">
                    <i class="bi bi-key absolute left-3.5 top-3 text-sm text-slate-400"></i>
                    <input type="password" id="password" name="password" required
                           class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-3 text-slate-800 form-input-focus"
                           placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Ingat saya di perangkat ini</span>
                </label>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-md shadow-indigo-600/20 cursor-pointer">
                Masuk ke Panel Admin
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition-colors">
                <i class="bi bi-arrow-left"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</div>

</body>
</html>

