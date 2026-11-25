@extends('layouts.app')

@section('title', 'Tentang Aplikasi')

@section('content')
@php
    $appIconUrl = $liga->appIconUrl();
    $tagline = $liga->app_tagline ?: 'Portal manajemen turnamen, klasemen otomatis, jadwal, berita, dan arena hiburan digital.';
    $defaultAbout = "Aplikasi ini dikembangkan sebagai pusat informasi resmi untuk memantau seluruh rangkaian kompetisi " . ($liga->nama_liga ?? 'Liga Dam Batu') . " Musim " . ($liga->musim ?? date('Y')) . ".\n\nSeluruh data pertandingan, klasemen, selisih batu, tren performa tim, hingga berita turnamen diperbarui secara terpusat oleh panitia sehingga peserta dan penonton dapat mengikuti perkembangan turnamen dengan transparan dan real-time.";
    $aboutText = trim((string) ($liga->tentang_aplikasi ?? '')) !== '' ? $liga->tentang_aplikasi : $defaultAbout;
@endphp

<div class="space-y-6 ldb-fade-in">
    <!-- Hero Tentang Aplikasi -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 border border-indigo-500/30 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center shadow-lg shrink-0 overflow-hidden">
                    @if ($appIconUrl)
                        <img src="{{ $appIconUrl }}" alt="Icon {{ $liga->nama_liga ?? 'Liga Dam Batu' }}" class="w-full h-full object-contain p-1.5">
                    @else
                        <i class="bi bi-grid-3x3-gap-fill text-3xl text-indigo-300"></i>
                    @endif
                </div>
                <div class="space-y-1.5">
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-indigo-200 text-xs font-semibold">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Tentang Aplikasi &middot; Musim {{ $liga->musim ?? date('Y') }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</h1>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">{{ $tagline }}</p>
                </div>
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-bold text-white transition-colors w-fit shrink-0">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Musim -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-300 flex items-center justify-center text-lg shrink-0">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Tim Aktif</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">{{ $totalTim }} Pasangan</div>
            </div>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-lg shrink-0">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Progres Pertandingan</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">{{ $totalSelesai }} / {{ $totalMatch }} Laga</div>
            </div>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 flex items-center justify-center text-lg shrink-0">
                <i class="bi bi-trophy-fill"></i>
            </div>
            <div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Status Turnamen</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</div>
            </div>
        </div>
    </div>

    <!-- Deskripsi & Fitur -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-3">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-journal-text text-indigo-500"></i>
                    <span>Profil &amp; Tujuan Aplikasi</span>
                </h2>
                <div class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                    {!! nl2br(e($aboutText)) !!}
                </div>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-stars text-indigo-500"></i>
                    <span>Fitur Utama Portal</span>
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/70">
                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 mb-1">
                            <i class="bi bi-list-ol text-indigo-500"></i> Klasemen &amp; Tiebreaker Otomatis
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">Perhitungan poin, rasio menang, selisih batu (&Delta;), dan head-to-head secara otomatis.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/70">
                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 mb-1">
                            <i class="bi bi-arrows-angle-contract text-indigo-500"></i> Perbandingan Tim (H2H)
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">Bandingkan statistik 2 tim, tren 5 laga terakhir, riwayat pertemuan, dan perkiraan peluang.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/70">
                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 mb-1">
                            <i class="bi bi-controller text-indigo-500"></i> Arena 5 Game 3D Publik
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">Dam Batu 3D, Monopoli Nusantara, Ludo Queen 3D, Catur 3D, dan Flappy Bird lengkap dengan lencana lokal.</p>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/70">
                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 mb-1">
                            <i class="bi bi-newspaper text-indigo-500"></i> Berita, Polling &amp; Ekspor PDF
                        </div>
                        <p class="text-slate-500 dark:text-slate-400">Liputan turnamen dengan reaksi emoji, polling dukungan laga, notifikasi push, dan cetak PDF resmi.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Info Sistem & Kontak -->
        <div class="space-y-6">
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-3">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-shield-check text-emerald-500"></i>
                    <span>Aturan Kompetisi</span>
                </h2>
                <ul class="text-xs text-slate-600 dark:text-slate-300 space-y-2">
                    <li class="flex items-start gap-2"><i class="bi bi-dot text-indigo-500 text-base leading-none"></i><span>Format Single Round-Robin (setiap tim bertemu satu kali).</span></li>
                    <li class="flex items-start gap-2"><i class="bi bi-dot text-indigo-500 text-base leading-none"></i><span>Menang = 3 poin, Kalah = 0 poin (tanpa hasil seri).</span></li>
                    <li class="flex items-start gap-2"><i class="bi bi-dot text-indigo-500 text-base leading-none"></i><span>Tie-breaker: Poin &rarr; Menang &rarr; Selisih Batu (&Delta;) &rarr; Batu Masuk &rarr; Head-to-Head.</span></li>
                </ul>
            </div>

            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-3">
                <h2 class="text-sm font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-cpu text-indigo-500"></i>
                    <span>Informasi Sistem</span>
                </h2>
                <dl class="text-xs space-y-2">
                    <div class="flex items-center justify-between gap-2 py-1 border-b border-slate-100 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">Platform</dt>
                        <dd class="font-bold text-slate-800 dark:text-slate-200">Laravel 12 &middot; PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2 py-1 border-b border-slate-100 dark:border-slate-800">
                        <dt class="text-slate-500 dark:text-slate-400">Mode Tampilan</dt>
                        <dd class="font-bold text-slate-800 dark:text-slate-200">Terang &amp; Gelap (Responsif)</dd>
                    </div>
                    @if (!empty($liga->kontak_panitia))
                        <div class="flex items-center justify-between gap-2 py-1">
                            <dt class="text-slate-500 dark:text-slate-400">Kontak Panitia</dt>
                            <dd class="font-bold text-indigo-600 dark:text-indigo-400 text-right">{{ $liga->kontak_panitia }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
