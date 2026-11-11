<!doctype html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Panel') — {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
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
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
    <script>window.BASE_URL = '{{ url('/') }}';</script>
</head>
<body class="min-h-full flex flex-col text-slate-800 antialiased font-sans" data-area="admin">

<!-- Admin Navigation -->
<nav class="sticky top-0 z-50 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 text-slate-100 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <div class="flex items-center gap-3">
                <a class="flex items-center gap-2.5 font-bold text-lg text-white hover:text-indigo-300 transition-colors" href="{{ route('admin.dashboard') }}">
                    <span class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                        <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                    </span>
                    <span class="tracking-tight">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</span>
                </a>
                <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-indigo-950/80 text-indigo-300 border border-indigo-700/50">Admin</span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1">
                @php
                    $adminNav = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                        ['route' => 'admin.tim.index', 'label' => 'Tim & Pemain', 'icon' => 'bi-people'],
                        ['route' => 'admin.jadwal.index', 'label' => 'Jadwal', 'icon' => 'bi-calendar3'],
                        ['route' => 'admin.pertandingan.index', 'label' => 'Input Hasil', 'icon' => 'bi-trophy'],
                        ['route' => 'admin.pengaturan.index', 'label' => 'Pengaturan', 'icon' => 'bi-gear'],
                    ];
                @endphp
                @foreach ($adminNav as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-slate-800 text-indigo-400 font-semibold shadow-xs' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}" href="{{ route($item['route']) }}">
                        <i class="bi {{ $item['icon'] }} text-base"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <!-- Right Actions -->
            <div class="hidden md:flex items-center space-x-2">
                <a class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 hover:text-white hover:bg-slate-800 transition-colors" href="{{ route('home') }}">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Lihat Situs</span>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-300 hover:text-white hover:bg-rose-900/40 border border-rose-800/40 transition-colors cursor-pointer">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
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
        @foreach ($adminNav as $item)
            @php $active = request()->routeIs($item['route']); @endphp
            <a class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $active ? 'bg-slate-800 text-indigo-400 font-semibold' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}" href="{{ route($item['route']) }}">
                <i class="bi {{ $item['icon'] }} text-lg"></i>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
        <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
            <a class="text-xs text-slate-300 hover:text-white flex items-center gap-1" href="{{ route('home') }}">
                <i class="bi bi-box-arrow-up-right"></i> Lihat Situs
            </a>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-xs text-rose-300 hover:text-rose-200 flex items-center gap-1 cursor-pointer">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</nav>

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
            Panel Administrator &middot; Laravel 12
        </div>
    </div>
</footer>

<script src="{{ asset('assets/js/app.js') }}"></script>
@yield('scripts')
</body>
</html>

