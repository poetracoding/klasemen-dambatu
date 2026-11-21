@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $totalTim = $jumlahTim ?? ($totalTim ?? 0);
        $totalMatch = $total ?? ($totalMatch ?? 0);
        $totalSelesai = $selesai ?? ($totalSelesai ?? 0);
        $persenSelesai = $totalMatch > 0 ? round(($totalSelesai / $totalMatch) * 100) : 0;
        $klasemen = $kl ?? ($klasemen ?? []);
        $juara = !empty($klasemen) && $liga->status === 'selesai' ? $klasemen[0] : null;
        $tampilJadwal = $tampil ?? $liga->status !== 'draft';
    @endphp

    <!-- Header / Hero Section -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-slate-200/80">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $liga->nama_liga }}</h1>
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $liga->status === 'berjalan' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($liga->status === 'selesai' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                    <span
                        class="w-2 h-2 rounded-full {{ $liga->status === 'berjalan' ? 'bg-indigo-500 animate-pulse' : ($liga->status === 'selesai' ? 'bg-emerald-500' : 'bg-slate-400') }}"></span>
                    {{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                <span>Musim {{ $liga->musim }}</span>
                <span>&middot;</span>
                <span>Format: Single Round Robin</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('tim.pdf') }}" target="_blank"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-colors shadow-xs"
                title="Cetak / Unduh Daftar Tim Peserta">
                <i class="bi bi-people"></i> Daftar Tim
            </a>
            <a href="{{ route('jadwal') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-colors shadow-xs">
                <i class="bi bi-calendar3"></i> Jadwal Lengkap
            </a>
            <a href="{{ route('klasemen') }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
                <i class="bi bi-trophy"></i> Klasemen
            </a>
        </div>
    </div>

    @if ($liga->status === 'selesai' && $juara)
        <!-- Juara Liga Showcase Banner -->
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500 p-8 text-slate-900 mb-8 shadow-lg shadow-amber-500/20">
            <div class="absolute -right-6 -bottom-6 opacity-15 pointer-events-none text-9xl">
                <i class="bi bi-trophy-fill"></i>
            </div>
            <div class="relative z-10 text-center max-w-xl mx-auto">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/30 backdrop-blur-md mb-3 text-white shadow-inner">
                    <i class="bi bi-trophy-fill text-3xl text-amber-900"></i>
                </div>
                <div class="text-xs uppercase tracking-widest font-extrabold text-amber-950/80 mb-1">Juara Liga Dam Batu
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight mb-2">{{ $juara['nama_tim'] }}</h2>
                <div class="text-sm font-semibold text-amber-950/90 flex items-center justify-center gap-2">
                    <span><i class="bi bi-people-fill mr-1"></i>{{ $juara['pemain1'] }} &amp;
                        {{ $juara['pemain2'] }}</span>
                    <span>&bull;</span>
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-900/10 text-xs font-bold">{{ (int) $juara['pts'] }}
                        Poin</span>
                </div>
            </div>
        </div>
    @endif

    <!-- 4 Key Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider">Jumlah Tim</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">
                    <i class="bi bi-people"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900">{{ (int) $totalTim }}</div>
            <div class="flex items-center justify-between text-xs text-slate-400 mt-1">
                <span>Tim terdaftar</span>
                <a href="{{ route('tim.pdf') }}" target="_blank"
                    class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 font-semibold"
                    title="Buka PDF Daftar Tim">
                    <i class="bi bi-file-earmark-arrow-down"></i> PDF
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Match</span>
                <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-base">
                    <i class="bi bi-grid-3x3"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900">{{ (int) $totalMatch }}</div>
            <div class="text-xs text-slate-400 mt-1">Pertandingan liga</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider">Selesai</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900">{{ (int) $totalSelesai }}</div>
            <div class="text-xs text-emerald-600 font-medium mt-1">
                {{ $persenSelesai }}% dari total
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs hover:border-slate-300 transition-colors">
            <div class="flex items-center justify-between text-slate-500 mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider">Tersisa</span>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
            <div class="text-3xl font-extrabold text-slate-900">{{ max(0, $totalMatch - $totalSelesai) }}</div>
            <div class="text-xs text-slate-400 mt-1">Belum dimainkan</div>
        </div>
    </div>

    <!-- 2 Columns: Today's Matches & Top Standings -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Today's Matches -->
        <div class="lg:col-span-7 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-indigo-500"></div>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">Pertandingan Hari Ini</h2>
                </div>
                <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg">
                    {{ \App\Services\TournamentService::hariId(date('Y-m-d')) }},
                    {{ \App\Services\TournamentService::tglId(date('Y-m-d')) }}
                </span>
            </div>

            @if (!$tampilJadwal)
                <div
                    class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                    <i class="bi bi-calendar-lock text-3xl text-slate-400 mb-2 block"></i>
                    Jadwal liga belum dipublikasikan oleh administrator.
                </div>
            @else
                @include('partials.match_table', ['matches' => $hariIni, 'tampilTanggal' => false])
                <div class="text-right pt-1">
                    <a href="{{ route('jadwal.hari') }}"
                        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                        Lihat jadwal harian lainnya <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            @endif
        </div>

        <!-- Leaderboard Top 5 -->
        <div class="lg:col-span-5 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                    <h2 class="text-lg font-bold text-slate-900 tracking-tight">Klasemen Sementara</h2>
                </div>
                <a href="{{ route('klasemen') }}"
                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Selengkapnya &rarr;
                </a>
            </div>

            @if (empty($klasemen))
                <div
                    class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                    <i class="bi bi-trophy text-3xl text-slate-400 mb-2 block"></i>
                    Belum ada tim terdaftar.
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead
                            class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                            <tr>
                                <th scope="col" class="py-3 px-3 text-center w-10">Pos</th>
                                <th scope="col" class="py-3 px-3">Tim</th>
                                <th scope="col" class="py-3 px-2 text-center">MP</th>
                                <th scope="col" class="py-3 px-2 text-center">W</th>
                                <th scope="col" class="py-3 px-2 text-center font-bold" title="Selisih Poin (&Delta;)">Δ
                                </th>
                                <th scope="col" class="py-3 px-3 text-center font-bold text-slate-800">PTS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach (array_slice($klasemen, 0, 5) as $k)
                                @php $isLeader = ($k['pos'] === 1 && $k['mp'] > 0); @endphp
                                <tr
                                    class="hover:bg-slate-50/60 transition-colors {{ $isLeader ? 'bg-amber-50/40' : '' }}">
                                    <td class="py-3 px-3 text-center">
                                        @if ($k['pos'] === 1)
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-bold text-xs">
                                                1
                                            </span>
                                        @elseif ($k['pos'] === 2)
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                                2
                                            </span>
                                        @elseif ($k['pos'] === 3)
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 font-bold text-xs">
                                                3
                                            </span>
                                        @else
                                            <span class="text-xs font-medium text-slate-400">{{ $k['pos'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <a class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors line-clamp-1"
                                            href="{{ route('tim.detail', $k['id']) }}">
                                            {{ $k['nama_tim'] }}
                                        </a>
                                        @include('partials.team_players', [
                                            'p1' => $k['pemain1'] ?? null,
                                            'p2' => $k['pemain2'] ?? null,
                                            'short' => true,
                                            'maxLen' => 7,
                                        ])
                                    </td>
                                    <td class="py-3 px-2 text-center text-xs text-slate-500 font-mono">{{ $k['mp'] }}
                                    </td>
                                    <td class="py-3 px-2 text-center text-xs text-emerald-600 font-semibold font-mono">
                                        {{ $k['w'] }}</td>
                                    <td
                                        class="py-3 px-2 text-center text-xs font-mono font-bold {{ $k['sb'] > 0 ? 'text-emerald-700' : ($k['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                        {{ $k['sb'] > 0 ? '+' : '' }}{{ (int) $k['sb'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-900 font-mono">
                                        {{ $k['pts'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center pt-1">
                    <a href="{{ route('klasemen') }}"
                        class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/70 hover:bg-indigo-100 dark:hover:bg-indigo-900/80 transition-colors">
                        Lihat Klasemen Lengkap
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Card Ajakan Bermain Game Dam Batu Online -->
    <div class="mt-8 relative overflow-hidden rounded-3xl p-6 sm:p-8 text-white shadow-md border border-emerald-700/40"
        style="background: linear-gradient(135deg, #065f46 0%, #047857 50%, #0f766e 100%);">
        <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none text-[140px] leading-none select-none">
            <i class="bi bi-dice-6-fill"></i>
        </div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
            <div class="max-w-2xl">
                <div
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-white/15 text-emerald-100 border border-white/20 mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse"></span>
                    <span>Hiburan Gratis &middot; Langsung Main di Browser</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    Yuk, Main Game Dam Batu Online!
                </h2>
                <p class="text-xs sm:text-sm text-emerald-100/90 mt-1.5 leading-relaxed">
                    Sambil menunggu jadwal pertandingan berikutnya, asah strategi Anda melawan 2 atau 3 AI dengan aturan 4
                    kartu sisa tertutup dan 3 tingkat kesulitan.
                </p>
                <div class="flex flex-wrap items-center gap-2 mt-3.5 text-[11px] font-semibold text-emerald-100">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-black/20 border border-white/10">
                        <i class="bi bi-people-fill text-amber-300"></i> 3–4 Pemain
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-black/20 border border-white/10">
                        <i class="bi bi-cpu-fill text-amber-300"></i> 3 Level AI
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-black/20 border border-white/10">
                        <i class="bi bi-arrows-fullscreen text-amber-300"></i> Mode Layar Penuh
                    </span>
                </div>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('game') }}"
                    class="inline-flex items-center justify-center gap-2 w-full md:w-auto px-6 py-3.5 rounded-2xl text-sm font-extrabold text-slate-900 bg-amber-400 hover:bg-amber-300 shadow-lg shadow-black/20 transition-all hover:scale-[1.02]">
                    <i class="bi bi-play-circle-fill text-lg"></i>
                    <span>Main Sekarang</span>
                </a>
            </div>
        </div>
    </div>

    @php
        $posters = $posters ?? collect();
        $firstPoster = $posters->first();
        $latestBeritas = $beritas ?? collect();
        $likedBeritaIds = $likedBeritaIds ?? [];
    @endphp

    <!-- Section Informasi & Berita Terbaru (Berdampingan di Desktop) -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        @if ($posters->isNotEmpty())
            <div id="informasiSection"
                class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-2xs">
                            <i class="bi bi-info-circle-fill"></i>
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900 tracking-tight">Informasi</h2>
                                @if ($posters->count() > 1)
                                    <span id="posterHeaderBadge"
                                        class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        1 / {{ $posters->count() }}
                                    </span>
                                @endif
                            </div>
                            <p id="posterActiveSubtitle" class="text-xs text-slate-400 mt-0.5">
                                {{ $firstPoster->judul ?: 'Informasi & pengumuman resmi pelaksanaan Liga Dam Batu.' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="openPeraturanModal()"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs cursor-pointer">
                            <i class="bi bi-arrows-fullscreen"></i> Perbesar
                        </button>
                        <a id="cardDownloadPosterBtn" href="{{ $firstPoster->url }}"
                            download="{{ $firstPoster->download_name }}"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors shadow-2xs w-fit"
                            title="Unduh Poster Aktif">
                            <i class="bi bi-download"></i> Unduh
                        </a>
                        <a id="cardNewTabPosterBtn" href="{{ $firstPoster->url }}" target="_blank"
                            class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors shadow-2xs w-fit">
                            <i class="bi bi-box-arrow-up-right"></i> Tab Baru
                        </a>
                    </div>
                </div>

                <!-- Banner / Auto-Slide Container Poster Informasi -->
                <div id="posterCarouselContainer"
                    class="overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50 group relative select-none">

                    <div class="relative w-full cursor-zoom-in" onclick="openPeraturanModal()">
                        @foreach ($posters as $idx => $poster)
                            <div class="poster-slide {{ $idx === 0 ? 'block opacity-100' : 'hidden opacity-0' }} transition-opacity duration-500"
                                data-index="{{ $idx }}" data-url="{{ $poster->url }}"
                                data-title="{{ $poster->judul ?: 'Informasi #' . ($idx + 1) }}"
                                data-desc="{{ $poster->deskripsi ?? '' }}" data-download="{{ $poster->download_name }}">
                                <img src="{{ $poster->url }}" alt="{{ $poster->judul ?: 'Informasi Liga Dam Batu' }}"
                                    class="w-full max-h-[720px] object-contain mx-auto rounded-xl transition-transform duration-300 group-hover:scale-[1.005]"
                                    loading="lazy">
                            </div>
                        @endforeach

                        <!-- Overlay Caption jika ada deskripsi -->
                        <div id="posterCaptionBar"
                            class="{{ $firstPoster->judul || $firstPoster->deskripsi ? 'flex' : 'hidden' }} items-center justify-between gap-3 px-4 py-2.5 bg-slate-900/75 backdrop-blur-md text-white text-xs border-t border-white/10">
                            <div class="truncate">
                                <span id="posterCaptionTitle" class="font-bold">{{ $firstPoster->judul ?? '' }}</span>
                                <span id="posterCaptionDesc"
                                    class="text-slate-300 ml-1.5">{{ $firstPoster->deskripsi ? '— ' . $firstPoster->deskripsi : '' }}</span>
                            </div>
                        </div>

                        <!-- Badge Klik Zoom -->
                        <div
                            class="absolute bottom-12 sm:bottom-3 right-3 bg-slate-900/85 backdrop-blur-md text-white text-xs font-medium px-3.5 py-2 rounded-xl shadow-lg flex items-center gap-2 opacity-90 group-hover:opacity-100 transition-all pointer-events-none">
                            <i class="bi bi-arrows-fullscreen text-sm"></i>
                            <span>Klik untuk Memperbesar</span>
                        </div>
                    </div>

                    @if ($posters->count() > 1)
                        <!-- Tombol Navigasi Prev / Next -->
                        <button type="button" onclick="prevPosterSlide(event)"
                            class="absolute left-3 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-slate-900/70 hover:bg-indigo-600 text-white flex items-center justify-center shadow-lg backdrop-blur-xs transition-all cursor-pointer opacity-80 group-hover:opacity-100"
                            title="Poster Sebelumnya" aria-label="Poster Sebelumnya">
                            <i class="bi bi-chevron-left text-lg"></i>
                        </button>
                        <button type="button" onclick="nextPosterSlide(event)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-slate-900/70 hover:bg-indigo-600 text-white flex items-center justify-center shadow-lg backdrop-blur-xs transition-all cursor-pointer opacity-80 group-hover:opacity-100"
                            title="Poster Berikutnya" aria-label="Poster Berikutnya">
                            <i class="bi bi-chevron-right text-lg"></i>
                        </button>

                        <!-- Indikator Slide (Dots) & Auto-Slide Status -->
                        <div
                            class="flex items-center justify-center gap-2 py-2.5 bg-slate-100/90 dark:bg-slate-800/90 border-t border-slate-200/80 dark:border-slate-700/80">
                            <div class="flex items-center gap-1.5" id="posterDotsContainer">
                                @foreach ($posters as $idx => $poster)
                                    <button type="button" onclick="goToPosterSlide({{ $idx }}, event)"
                                        class="poster-dot h-2 rounded-full transition-all cursor-pointer {{ $idx === 0 ? 'w-6 bg-indigo-600' : 'w-2 bg-slate-300 dark:bg-slate-600 hover:bg-indigo-400' }}"
                                        data-index="{{ $idx }}"
                                        aria-label="Slide {{ $idx + 1 }}"></button>
                                @endforeach
                            </div>
                            <span class="text-[11px] text-slate-400 ml-2 flex items-center gap-1">
                                <i class="bi bi-play-circle text-indigo-500"></i> Auto-slide
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Card Berita Terbaru (Di sebelah Card Informasi) -->
        <div id="beritaSection"
            class="{{ $posters->isNotEmpty() ? 'lg:col-span-5' : 'lg:col-span-12' }} bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span
                        class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shadow-2xs">
                        <i class="bi bi-newspaper"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Berita &amp; Liputan</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Kabar terbaru turnamen.</p>
                    </div>
                </div>
                <a href="{{ route('berita.index') }}"
                    class="text-xs font-semibold text-indigo-600 hover:underline whitespace-nowrap">
                    Semua &rarr;
                </a>
            </div>

            @if ($latestBeritas->isEmpty())
                <div
                    class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-8 text-center text-xs sm:text-sm text-slate-400">
                    <i class="bi bi-newspaper text-3xl text-slate-300 mb-2 block"></i>
                    Belum ada berita yang diterbitkan.
                </div>
            @else
                <div class="{{ $posters->isNotEmpty() ? 'space-y-4' : 'grid grid-cols-1 md:grid-cols-2 gap-4' }}">
                    @foreach ($latestBeritas as $b)
                        @php
                            $bCounts = ($beritaReactionCounts[$b->id] ?? null) ?: \App\Models\BeritaLike::emptyCounts();
                            $bUserReaction = $beritaVisitorReactions[$b->id] ?? null;
                        @endphp
                        <article
                            class="group p-3.5 rounded-2xl border border-slate-200/70 hover:border-indigo-300 bg-slate-50/40 transition-all">
                            <div class="flex items-start gap-3">
                                <a href="{{ route('berita.show', $b->slug) }}"
                                    class="w-20 h-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 block">
                                    @if ($b->gambar_url)
                                        <img src="{{ $b->gambar_url }}" alt="{{ $b->judul }}"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            loading="lazy">
                                    @else
                                        <div
                                            class="w-full h-full bg-indigo-50 text-indigo-400 flex items-center justify-center">
                                            <i class="bi bi-newspaper text-lg"></i>
                                        </div>
                                    @endif
                                </a>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-400 mb-1">
                                        <span class="font-bold text-indigo-600 uppercase">{{ $b->kategori }}</span>
                                        <span>&middot;</span>
                                        <span>{{ ($b->published_at ?? $b->created_at)->format('d/m/Y') }}</span>
                                    </div>
                                    <h3 class="text-xs sm:text-sm font-bold text-slate-900 leading-snug line-clamp-2">
                                        <a href="{{ route('berita.show', $b->slug) }}"
                                            class="hover:text-indigo-600 transition-colors">
                                            {{ $b->judul }}
                                        </a>
                                    </h3>
                                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $b->excerpt }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mt-2.5 pt-2 border-t border-slate-200/60">
                                @include('partials.berita_reactions', [
                                    'berita' => $b,
                                    'counts' => $bCounts,
                                    'userReaction' => $bUserReaction,
                                    'compact' => true,
                                ])
                                <div class="flex items-center gap-2.5 ml-auto">
                                    <span class="text-[11px] text-slate-400 font-mono">
                                        <i class="bi bi-eye"></i> {{ number_format($b->views_count) }}
                                    </span>
                                    <a href="{{ route('berita.show', $b->slug) }}"
                                        class="text-[11px] font-semibold text-indigo-600 hover:underline whitespace-nowrap">
                                        Baca &rarr;
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($posters->isNotEmpty())
        <!-- Modal Lightbox Perbesar Gambar Informasi -->
        <div id="modalPeraturan"
            class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md hidden flex-col transition-all duration-300"
            role="dialog" aria-modal="true" aria-labelledby="modalPeraturanTitle">
            <!-- Toolbar Atas -->
            <div
                class="flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 py-3 bg-slate-900/90 border-b border-slate-800 text-white z-10 flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-indigo-600/80 text-white flex items-center justify-center text-xs">
                        <i class="bi bi-info-circle-fill"></i>
                    </span>
                    <div>
                        <h3 id="modalPeraturanTitle" class="text-sm font-bold text-white tracking-tight">Informasi</h3>
                        <p id="modalPosterSubtitle" class="text-3xs text-slate-400 hidden sm:block">
                            {{ $firstPoster->judul ?: 'Gunakan tombol zoom atau klik gambar untuk memperbesar/memperkecil (ESC untuk keluar)' }}
                        </p>
                    </div>
                </div>

                <!-- Kontrol Slide, Zoom & Aksi -->
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    @if ($posters->count() > 1)
                        <div class="inline-flex items-center bg-slate-800 rounded-xl p-0.5 border border-slate-700/80">
                            <button type="button" onclick="prevPosterSlide(event)"
                                class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                title="Poster Sebelumnya">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span id="modalSlideCounter"
                                class="px-2 text-xs font-mono font-semibold text-slate-200 select-none">
                                1 / {{ $posters->count() }}
                            </span>
                            <button type="button" onclick="nextPosterSlide(event)"
                                class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                title="Poster Berikutnya">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    @endif

                    <div class="inline-flex items-center bg-slate-800 rounded-xl p-0.5 border border-slate-700/80">
                        <button type="button" onclick="zoomPeraturan(-0.25)"
                            class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                            title="Perkecil">
                            <i class="bi bi-zoom-out"></i>
                        </button>
                        <span id="zoomLevelText"
                            class="px-2 text-xs font-mono font-semibold text-indigo-300 min-w-[50px] text-center select-none">100%</span>
                        <button type="button" onclick="zoomPeraturan(0.25)"
                            class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-xs font-bold transition-colors cursor-pointer"
                            title="Perbesar">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                        <button type="button" onclick="resetZoomPeraturan()"
                            class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-2xs font-semibold transition-colors cursor-pointer"
                            title="Reset Zoom">
                            Reset
                        </button>
                    </div>

                    <a id="modalDownloadPosterBtn" href="{{ $firstPoster->url }}"
                        download="{{ $firstPoster->download_name }}"
                        class="p-2 sm:px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-slate-700/80 transition-colors"
                        title="Unduh Gambar Asli">
                        <i class="bi bi-download"></i>
                        <span class="hidden sm:inline">Unduh</span>
                    </a>

                    <a id="modalNewTabPosterBtn" href="{{ $firstPoster->url }}" target="_blank"
                        class="p-2 sm:px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-slate-700/80 transition-colors"
                        title="Buka di Tab Baru">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>

                    <button type="button" onclick="closePeraturanModal()"
                        class="p-2 sm:px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                        title="Tutup Modal (ESC)">
                        <i class="bi bi-x-lg"></i>
                        <span class="hidden sm:inline">Tutup</span>
                    </button>
                </div>
            </div>

            <!-- Area Gambar (Scrollable & Zoomable) -->
            <div id="modalPeraturanBackdrop"
                class="flex-1 overflow-auto p-4 sm:p-8 flex items-center justify-center cursor-zoom-out select-none relative"
                onclick="handleBackdropClick(event)">
                @if ($posters->count() > 1)
                    <button type="button" onclick="prevPosterSlide(event)"
                        class="fixed left-3 sm:left-6 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-slate-900/80 hover:bg-indigo-600 text-white flex items-center justify-center shadow-xl border border-slate-700 transition-all cursor-pointer"
                        title="Poster Sebelumnya" aria-label="Poster Sebelumnya">
                        <i class="bi bi-chevron-left text-xl"></i>
                    </button>
                    <button type="button" onclick="nextPosterSlide(event)"
                        class="fixed right-3 sm:right-6 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-slate-900/80 hover:bg-indigo-600 text-white flex items-center justify-center shadow-xl border border-slate-700 transition-all cursor-pointer"
                        title="Poster Berikutnya" aria-label="Poster Berikutnya">
                        <i class="bi bi-chevron-right text-xl"></i>
                    </button>
                @endif

                <div class="relative transition-transform duration-200 ease-out origin-center inline-block"
                    id="imageWrapper">
                    <img id="modalPeraturanImg" src="{{ $firstPoster->url }}"
                        alt="{{ $firstPoster->judul ?: 'Informasi Liga Dam Batu' }}"
                        class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl cursor-zoom-in border border-slate-700/50"
                        onclick="toggleZoomClick(event)">
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    @include('partials.berita_like_script')
    <script>
        (function() {
            let currentZoom = 1.0;
            const minZoom = 0.5;
            const maxZoom = 3.0;
            let currentSlideIndex = 0;
            let autoSlideTimer = null;
            let isHovered = false;

            function getSlides() {
                return Array.from(document.querySelectorAll('.poster-slide'));
            }

            function syncSlideUI() {
                const slides = getSlides();
                if (slides.length === 0) return;

                if (currentSlideIndex >= slides.length) currentSlideIndex = 0;
                if (currentSlideIndex < 0) currentSlideIndex = slides.length - 1;

                const activeSlide = slides[currentSlideIndex];
                const url = activeSlide.dataset.url || '';
                const title = activeSlide.dataset.title || 'Informasi';
                const desc = activeSlide.dataset.desc || '';
                const downloadName = activeSlide.dataset.download || 'informasi.png';

                slides.forEach(function(slide, idx) {
                    if (idx === currentSlideIndex) {
                        slide.classList.remove('hidden', 'opacity-0');
                        slide.classList.add('block', 'opacity-100');
                    } else {
                        slide.classList.add('hidden', 'opacity-0');
                        slide.classList.remove('block', 'opacity-100');
                    }
                });

                document.querySelectorAll('.poster-dot').forEach(function(dot, idx) {
                    if (idx === currentSlideIndex) {
                        dot.classList.add('w-6', 'bg-indigo-600');
                        dot.classList.remove('w-2', 'bg-slate-300', 'dark:bg-slate-600');
                    } else {
                        dot.classList.remove('w-6', 'bg-indigo-600');
                        dot.classList.add('w-2', 'bg-slate-300', 'dark:bg-slate-600');
                    }
                });

                const headerBadge = document.getElementById('posterHeaderBadge');
                if (headerBadge) headerBadge.innerText = (currentSlideIndex + 1) + ' / ' + slides.length;

                const modalCounter = document.getElementById('modalSlideCounter');
                if (modalCounter) modalCounter.innerText = (currentSlideIndex + 1) + ' / ' + slides.length;

                const subtitleEl = document.getElementById('posterActiveSubtitle');
                if (subtitleEl) {
                    subtitleEl.innerText = title || 'Informasi & pengumuman resmi pelaksanaan Liga Dam Batu.';
                }

                const captionBar = document.getElementById('posterCaptionBar');
                const captionTitle = document.getElementById('posterCaptionTitle');
                const captionDesc = document.getElementById('posterCaptionDesc');
                if (captionBar && captionTitle && captionDesc) {
                    if (title || desc) {
                        captionTitle.innerText = title;
                        captionDesc.innerText = desc ? ('— ' + desc) : '';
                        captionBar.classList.remove('hidden');
                        captionBar.classList.add('flex');
                    } else {
                        captionBar.classList.add('hidden');
                        captionBar.classList.remove('flex');
                    }
                }

                const cardDl = document.getElementById('cardDownloadPosterBtn');
                if (cardDl) {
                    cardDl.href = url;
                    cardDl.setAttribute('download', downloadName);
                }

                const cardTab = document.getElementById('cardNewTabPosterBtn');
                if (cardTab) cardTab.href = url;

                const modalImg = document.getElementById('modalPeraturanImg');
                if (modalImg) {
                    modalImg.src = url;
                    modalImg.alt = title;
                }

                const modalSub = document.getElementById('modalPosterSubtitle');
                if (modalSub) {
                    modalSub.innerText = title + (desc ? ' — ' + desc : '');
                }

                const modalDl = document.getElementById('modalDownloadPosterBtn');
                if (modalDl) {
                    modalDl.href = url;
                    modalDl.setAttribute('download', downloadName);
                }

                const modalTab = document.getElementById('modalNewTabPosterBtn');
                if (modalTab) modalTab.href = url;
            }

            function startAutoSlide() {
                stopAutoSlide();
                const slides = getSlides();
                if (slides.length <= 1) return;

                autoSlideTimer = setInterval(function() {
                    const modal = document.getElementById('modalPeraturan');
                    const modalOpen = modal && !modal.classList.contains('hidden');
                    if (!isHovered && !modalOpen) {
                        currentSlideIndex = (currentSlideIndex + 1) % getSlides().length;
                        syncSlideUI();
                    }
                }, 4500);
            }

            function stopAutoSlide() {
                if (autoSlideTimer) {
                    clearInterval(autoSlideTimer);
                    autoSlideTimer = null;
                }
            }

            window.goToPosterSlide = function(index, e) {
                if (e && e.stopPropagation) e.stopPropagation();
                const slides = getSlides();
                if (slides.length === 0) return;
                currentSlideIndex = ((index % slides.length) + slides.length) % slides.length;
                resetZoomPeraturan();
                syncSlideUI();
                startAutoSlide();
            };

            window.nextPosterSlide = function(e) {
                if (e && e.stopPropagation) e.stopPropagation();
                const slides = getSlides();
                if (slides.length === 0) return;
                currentSlideIndex = (currentSlideIndex + 1) % slides.length;
                resetZoomPeraturan();
                syncSlideUI();
                startAutoSlide();
            };

            window.prevPosterSlide = function(e) {
                if (e && e.stopPropagation) e.stopPropagation();
                const slides = getSlides();
                if (slides.length === 0) return;
                currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
                resetZoomPeraturan();
                syncSlideUI();
                startAutoSlide();
            };

            window.updateZoomTransform = function() {
                const wrapper = document.getElementById('imageWrapper');
                const img = document.getElementById('modalPeraturanImg');
                const text = document.getElementById('zoomLevelText');
                if (wrapper) {
                    wrapper.style.transform = `scale(${currentZoom})`;
                }
                if (text) {
                    text.innerText = Math.round(currentZoom * 100) + '%';
                }
                if (img) {
                    if (currentZoom > 1.0) {
                        img.classList.remove('cursor-zoom-in');
                        img.classList.add('cursor-zoom-out');
                    } else {
                        img.classList.remove('cursor-zoom-out');
                        img.classList.add('cursor-zoom-in');
                    }
                }
            };

            window.zoomPeraturan = function(delta) {
                currentZoom = Math.min(maxZoom, Math.max(minZoom, Math.round((currentZoom + delta) * 100) / 100));
                updateZoomTransform();
            };

            window.resetZoomPeraturan = function() {
                currentZoom = 1.0;
                updateZoomTransform();
            };

            window.toggleZoomClick = function(e) {
                if (e && e.stopPropagation) e.stopPropagation();
                currentZoom = (currentZoom === 1.0) ? 1.75 : 1.0;
                updateZoomTransform();
            };

            window.openPeraturanModal = function() {
                syncSlideUI();
                const modal = document.getElementById('modalPeraturan');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    resetZoomPeraturan();
                }
            };

            window.closePeraturanModal = function() {
                const modal = document.getElementById('modalPeraturan');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                    resetZoomPeraturan();
                }
            };

            window.handleBackdropClick = function(e) {
                if (e.target && e.target.id === 'modalPeraturanBackdrop') {
                    closePeraturanModal();
                }
            };

            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('modalPeraturan');
                if (!modal || modal.classList.contains('hidden')) return;

                if (e.key === 'Escape') {
                    closePeraturanModal();
                } else if (e.key === 'ArrowRight') {
                    nextPosterSlide(e);
                } else if (e.key === 'ArrowLeft') {
                    prevPosterSlide(e);
                }
            });

            function bindCarouselHover() {
                const container = document.getElementById('posterCarouselContainer');
                if (container && !container.dataset.hoverBound) {
                    isHovered = false;
                    container.dataset.hoverBound = '1';
                    container.addEventListener('mouseenter', function() {
                        isHovered = true;
                    });
                    container.addEventListener('mouseleave', function() {
                        isHovered = false;
                    });
                }
            }

            function initPosterCarousel() {
                bindCarouselHover();
                syncSlideUI();
                startAutoSlide();
            }

            document.addEventListener('DOMContentLoaded', function() {
                initPosterCarousel();

                const mainEl = document.querySelector('main');
                if (mainEl && window.MutationObserver) {
                    const observer = new MutationObserver(function() {
                        initPosterCarousel();
                    });
                    observer.observe(mainEl, {
                        childList: true
                    });
                }
            });
        })();
    </script>
@endsection
