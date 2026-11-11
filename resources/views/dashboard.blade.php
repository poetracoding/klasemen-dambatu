@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $totalTim = $jumlahTim ?? $totalTim ?? 0;
    $totalMatch = $total ?? $totalMatch ?? 0;
    $totalSelesai = $selesai ?? $totalSelesai ?? 0;
    $persenSelesai = $totalMatch > 0 ? round(($totalSelesai / $totalMatch) * 100) : 0;
    $klasemen = $kl ?? $klasemen ?? [];
    $juara = (!empty($klasemen) && $liga->status === 'selesai') ? $klasemen[0] : null;
    $tampilJadwal = $tampil ?? ($liga->status !== 'draft');
@endphp

<!-- Header / Hero Section -->
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-slate-200/80">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $liga->nama_liga }}</h1>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $liga->status === 'berjalan' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($liga->status === 'selesai' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                <span class="w-2 h-2 rounded-full {{ $liga->status === 'berjalan' ? 'bg-indigo-500 animate-pulse' : ($liga->status === 'selesai' ? 'bg-emerald-500' : 'bg-slate-400') }}"></span>
                {{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}
            </span>
        </div>
        <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
            <span>Musim {{ $liga->musim }}</span>
            <span>&middot;</span>
            <span>Format: Single Round Robin</span>
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('jadwal') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 dark:hover:border-slate-600 transition-colors shadow-xs">
            <i class="bi bi-calendar3"></i> Jadwal Lengkap
        </a>
        <a href="{{ route('klasemen') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
            <i class="bi bi-trophy"></i> Klasemen
        </a>
    </div>
</div>

@if ($liga->status === 'selesai' && $juara)
    <!-- Juara Liga Showcase Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500 p-8 text-slate-900 mb-8 shadow-lg shadow-amber-500/20">
        <div class="absolute -right-6 -bottom-6 opacity-15 pointer-events-none text-9xl">
            <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="relative z-10 text-center max-w-xl mx-auto">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/30 backdrop-blur-md mb-3 text-white shadow-inner">
                <i class="bi bi-trophy-fill text-3xl text-amber-900"></i>
            </div>
            <div class="text-xs uppercase tracking-widest font-extrabold text-amber-950/80 mb-1">Juara Liga Dam Batu</div>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight mb-2">{{ $juara['nama_tim'] }}</h2>
            <div class="text-sm font-semibold text-amber-950/90 flex items-center justify-center gap-2">
                <span><i class="bi bi-people-fill mr-1"></i>{{ $juara['pemain1'] }} &amp; {{ $juara['pemain2'] }}</span>
                <span>&bull;</span>
                <span class="px-2.5 py-0.5 rounded-full bg-amber-900/10 text-xs font-bold">{{ (int) $juara['pts'] }} Poin</span>
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
        <div class="text-xs text-slate-400 mt-1">Tim terdaftar</div>
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
                {{ \App\Services\TournamentService::hariId(date('Y-m-d')) }}, {{ \App\Services\TournamentService::tglId(date('Y-m-d')) }}
            </span>
        </div>

        @if (!$tampilJadwal)
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                <i class="bi bi-calendar-lock text-3xl text-slate-400 mb-2 block"></i>
                Jadwal liga belum dipublikasikan oleh administrator.
            </div>
        @else
            @include('partials.match_table', ['matches' => $hariIni, 'tampilTanggal' => false])
            <div class="text-right pt-1">
                <a href="{{ route('jadwal.hari') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
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
            <a href="{{ route('klasemen') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                Selengkapnya &rarr;
            </a>
        </div>

        @if (empty($klasemen))
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                <i class="bi bi-trophy text-3xl text-slate-400 mb-2 block"></i>
                Belum ada tim terdaftar.
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-200/80">
                        <tr>
                            <th scope="col" class="py-3 px-3 text-center w-10">Pos</th>
                            <th scope="col" class="py-3 px-3">Tim</th>
                            <th scope="col" class="py-3 px-2 text-center">MP</th>
                            <th scope="col" class="py-3 px-2 text-center">W</th>
                            <th scope="col" class="py-3 px-2 text-center font-bold" title="Selisih Poin (&Delta;)">Δ</th>
                            <th scope="col" class="py-3 px-3 text-center font-bold text-slate-800">PTS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (array_slice($klasemen, 0, 5) as $k)
                            @php $isLeader = ($k['pos'] === 1 && $k['mp'] > 0); @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors {{ $isLeader ? 'bg-amber-50/40' : '' }}">
                                <td class="py-3 px-3 text-center">
                                    @if ($k['pos'] === 1)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-100 text-amber-700 font-bold text-xs">
                                            1
                                        </span>
                                    @elseif ($k['pos'] === 2)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                            2
                                        </span>
                                    @elseif ($k['pos'] === 3)
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-100 text-orange-700 font-bold text-xs">
                                            3
                                        </span>
                                    @else
                                        <span class="text-xs font-medium text-slate-400">{{ $k['pos'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <a class="font-semibold text-slate-800 hover:text-indigo-600 transition-colors line-clamp-1" href="{{ route('tim.detail', $k['id']) }}">
                                        {{ $k['nama_tim'] }}
                                    </a>
                                </td>
                                <td class="py-3 px-2 text-center text-xs text-slate-500 font-mono">{{ $k['mp'] }}</td>
                                <td class="py-3 px-2 text-center text-xs text-emerald-600 font-semibold font-mono">{{ $k['w'] }}</td>
                                <td class="py-3 px-2 text-center text-xs font-mono font-bold {{ $k['sb'] > 0 ? 'text-emerald-700' : ($k['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                    {{ $k['sb'] > 0 ? '+' : '' }}{{ (int) $k['sb'] }}
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-slate-900 font-mono">{{ $k['pts'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="text-center pt-1">
                <a href="{{ route('klasemen') }}" class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/70 hover:bg-indigo-100 dark:hover:bg-indigo-900/80 transition-colors">
                    Lihat Klasemen Lengkap
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Section Peraturan Pertandingan -->
@if (file_exists(public_path('peraturan.png')))
    <div class="mt-8 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shadow-2xs">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Peraturan &amp; Tata Tertib Pertandingan</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Pedoman resmi pelaksanaan pertandingan Liga Dam Batu.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openPeraturanModal()" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs cursor-pointer">
                    <i class="bi bi-arrows-fullscreen"></i> Perbesar Gambar
                </button>
                <a href="{{ asset('peraturan.png') }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors shadow-2xs w-fit">
                    <i class="bi bi-box-arrow-up-right"></i> Tab Baru
                </a>
            </div>
        </div>

        <!-- Banner Peraturan -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50 group relative cursor-pointer" onclick="openPeraturanModal()">
            <div class="block w-full cursor-zoom-in relative">
                <img src="{{ asset('peraturan.png') }}" alt="Peraturan Liga Dam Batu" class="w-full max-h-[720px] object-contain mx-auto rounded-xl transition-transform duration-300 group-hover:scale-[1.008]" loading="lazy">
                <div class="absolute bottom-3 right-3 bg-slate-900/85 backdrop-blur-md text-white text-xs font-medium px-3.5 py-2 rounded-xl shadow-lg flex items-center gap-2 opacity-90 group-hover:opacity-100 transition-all">
                    <i class="bi bi-arrows-fullscreen text-sm"></i>
                    <span>Klik untuk Memperbesar</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Lightbox Perbesar Gambar Peraturan -->
    <div id="modalPeraturan" class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md hidden flex-col transition-all duration-300" role="dialog" aria-modal="true" aria-labelledby="modalPeraturanTitle">
        <!-- Toolbar Atas -->
        <div class="flex items-center justify-between px-4 sm:px-6 py-3 bg-slate-900/90 border-b border-slate-800 text-white z-10 flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-indigo-600/80 text-white flex items-center justify-center text-xs">
                    <i class="bi bi-file-earmark-image"></i>
                </span>
                <div>
                    <h3 id="modalPeraturanTitle" class="text-sm font-bold text-white tracking-tight">Peraturan &amp; Tata Tertib Pertandingan</h3>
                    <p class="text-3xs text-slate-400 hidden sm:block">Gunakan tombol zoom atau klik gambar untuk memperbesar/memperkecil (Tekan ESC untuk keluar)</p>
                </div>
            </div>

            <!-- Kontrol Zoom & Aksi -->
            <div class="flex items-center gap-1.5 sm:gap-2">
                <div class="inline-flex items-center bg-slate-800 rounded-xl p-0.5 border border-slate-700/80">
                    <button type="button" onclick="zoomPeraturan(-0.25)" class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-xs font-bold transition-colors cursor-pointer" title="Perkecil">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <span id="zoomLevelText" class="px-2 text-xs font-mono font-semibold text-indigo-300 min-w-[50px] text-center select-none">100%</span>
                    <button type="button" onclick="zoomPeraturan(0.25)" class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-xs font-bold transition-colors cursor-pointer" title="Perbesar">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                    <button type="button" onclick="resetZoomPeraturan()" class="p-1.5 sm:px-2 text-slate-300 hover:text-white hover:bg-slate-700 rounded-lg text-2xs font-semibold transition-colors cursor-pointer" title="Reset Zoom">
                        Reset
                    </button>
                </div>

                <a href="{{ asset('peraturan.png') }}" download="Peraturan-Liga-Dam-Batu.png" class="p-2 sm:px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-slate-700/80 transition-colors" title="Unduh Gambar Asli">
                    <i class="bi bi-download"></i>
                    <span class="hidden sm:inline">Unduh</span>
                </a>

                <a href="{{ asset('peraturan.png') }}" target="_blank" class="p-2 sm:px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold flex items-center gap-1.5 border border-slate-700/80 transition-colors" title="Buka di Tab Baru">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>

                <button type="button" onclick="closePeraturanModal()" class="p-2 sm:px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer" title="Tutup Modal (ESC)">
                    <i class="bi bi-x-lg"></i>
                    <span class="hidden sm:inline">Tutup</span>
                </button>
            </div>
        </div>

        <!-- Area Gambar (Scrollable & Zoomable) -->
        <div id="modalPeraturanBackdrop" class="flex-1 overflow-auto p-4 sm:p-8 flex items-center justify-center cursor-zoom-out select-none" onclick="handleBackdropClick(event)">
            <div class="relative transition-transform duration-200 ease-out origin-center inline-block" id="imageWrapper">
                <img id="modalPeraturanImg" src="{{ asset('peraturan.png') }}" alt="Peraturan & Tata Tertib Pertandingan Liga Dam Batu" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl cursor-zoom-in border border-slate-700/50" onclick="toggleZoomClick(event)">
            </div>
        </div>
    </div>

    <script>
        let currentZoom = 1.0;
        const minZoom = 0.5;
        const maxZoom = 3.0;

        function updateZoomTransform() {
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
        }

        function zoomPeraturan(delta) {
            currentZoom = Math.min(maxZoom, Math.max(minZoom, Math.round((currentZoom + delta) * 100) / 100));
            updateZoomTransform();
        }

        function resetZoomPeraturan() {
            currentZoom = 1.0;
            updateZoomTransform();
        }

        function toggleZoomClick(e) {
            e.stopPropagation();
            if (currentZoom === 1.0) {
                currentZoom = 1.75;
            } else {
                currentZoom = 1.0;
            }
            updateZoomTransform();
        }

        function openPeraturanModal() {
            const modal = document.getElementById('modalPeraturan');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
                resetZoomPeraturan();
            }
        }

        function closePeraturanModal() {
            const modal = document.getElementById('modalPeraturan');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
                resetZoomPeraturan();
            }
        }

        function handleBackdropClick(e) {
            if (e.target.id === 'modalPeraturanBackdrop') {
                closePeraturanModal();
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('modalPeraturan');
                if (modal && !modal.classList.contains('hidden')) {
                    closePeraturanModal();
                }
            }
        });
    </script>
@endif
@endsection
