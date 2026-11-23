@extends('layouts.app')

@section('title', 'Klasemen Liga')

@section('content')
@php
    $klasemen = $kl ?? $klasemen ?? [];
@endphp
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Klasemen Liga</h1>
        <p class="text-sm text-slate-500 mt-1">Perolehan poin, rasio batu masuk/kebobolan, dan selisih skor seluruh tim.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('perbandingan') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-700/70 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 shadow-xs transition-colors print:hidden" title="Bandingkan statistik 2 tim secara langsung">
            <i class="bi bi-arrows-angle-contract"></i> Bandingkan Tim (H2H)
        </a>
        <a href="{{ route('klasemen.pdf') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white shadow-xs transition-colors print:hidden" title="Buka PDF untuk dicetak">
            <i class="bi bi-printer"></i> Cetak PDF
        </a>
        <a href="{{ route('klasemen.pdf', ['download' => 1]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors print:hidden" title="Unduh berkas PDF">
            <i class="bi bi-file-earmark-arrow-down"></i> Unduh PDF
        </a>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 shadow-xs">
            <i class="bi bi-info-circle text-indigo-500"></i>
            <span>3 Poin Menang &middot; 0 Kalah</span>
        </div>
    </div>
</div>

@include('partials.fcm_nudge_card')

@if ($liga->status === 'selesai' && !empty($klasemen))
    @include('partials.champion_confetti')
    @php $winner = $klasemen[0]; @endphp
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 p-6 mb-6 shadow-md shadow-amber-500/20 text-slate-950 flex items-center gap-5">
        <div class="w-14 h-14 rounded-2xl bg-white/40 flex items-center justify-center text-amber-950 shadow-inner flex-shrink-0">
            <i class="bi bi-trophy-fill text-3xl"></i>
        </div>
        <div>
            <div class="text-xs uppercase tracking-widest font-extrabold text-amber-900/80">Juara Liga Dam Batu</div>
            <div class="text-2xl font-black tracking-tight">{{ $winner['nama_tim'] }}</div>
            <div class="text-xs font-semibold text-amber-950/80 mt-0.5">
                {{ $winner['pemain1'] }} &amp; {{ $winner['pemain2'] }} &middot; Total {{ (int) $winner['pts'] }} Poin (Selisih: {{ $winner['sb'] > 0 ? '+' : '' }}{{ (int) $winner['sb'] }})
            </div>
        </div>
    </div>
@endif

@if (empty($klasemen))
    <div class="ldb-fade-in rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-12 text-center shadow-xs">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center mb-3">
            <i class="bi bi-trophy text-2xl"></i>
        </div>
        <h2 class="text-base font-bold text-slate-800 dark:text-white">Klasemen Belum Tersedia</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
            Belum ada tim terdaftar pada musim ini. Peringkat akan muncul otomatis setelah tim didaftarkan oleh panitia.
        </p>
    </div>
@else
    <div class="ldb-fade-in overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs mb-8">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm text-slate-600 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-800">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th scope="col" class="py-4 px-3 sm:px-4 text-center w-12 sm:w-14">Pos</th>
                        <th scope="col" class="py-4 px-4 min-w-44">Tim &amp; Pemain</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Matches Played (Main)">MP</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Won (Menang)">W</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Lost (Kalah)">L</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center text-emerald-700 dark:text-emerald-400 font-bold" title="Poin Masuk (+)">+</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center text-rose-600 dark:text-rose-400 font-bold" title="Poin Keluar (-)">-</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center font-bold" title="Selisih Poin (&Delta;)">Δ</th>
                        <th scope="col" class="py-4 px-3 sm:px-4 text-center font-bold text-slate-900 dark:text-white bg-slate-100/50 dark:bg-slate-800" title="Total Poin Liga">PTS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($klasemen as $k)
                        @php $isLeader = ($k['pos'] === 1 && $k['mp'] > 0); @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/80 transition-colors {{ $isLeader ? 'bg-amber-50/60 dark:bg-amber-500/15' : '' }}">
                            <td class="py-4 px-3 sm:px-4 text-center">
                                @if ($k['pos'] === 1)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-amber-100 dark:bg-amber-500/25 text-amber-800 dark:text-amber-300 dark:ring-1 dark:ring-amber-400/40 font-black text-xs shadow-xs">
                                        1
                                    </span>
                                @elseif ($k['pos'] === 2)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-black text-xs">
                                        2
                                    </span>
                                @elseif ($k['pos'] === 3)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-orange-100 dark:bg-orange-500/25 text-orange-800 dark:text-orange-300 font-black text-xs">
                                        3
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ $k['pos'] }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $tr = $trendMap[$k['id']] ?? null;
                                    $stType = $tr['streak_type'] ?? 'N';
                                    $stCount = (int) ($tr['streak_count'] ?? 0);
                                    $showBadge = !empty($tr['badge_recent']) && $stCount >= 2;
                                @endphp
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors" href="{{ route('tim.detail', $k['id']) }}">
                                        {{ $k['nama_tim'] }}
                                    </a>
                                    @if ($isLeader)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-100 dark:bg-amber-500/25 text-amber-800 dark:text-amber-200 border border-amber-200/80 dark:border-amber-400/40" title="Pemuncak Klasemen">
                                            <i class="bi bi-trophy-fill text-[9px]"></i> #1
                                        </span>
                                    @endif
                                    @if ($showBadge && $stType === 'W')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/35" title="Menang {{ $stCount }} kali beruntun (update terbaru)">
                                            ▲ {{ $stCount }}M
                                        </span>
                                    @elseif ($showBadge && $stType === 'L')
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-extrabold bg-rose-50 dark:bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/35" title="Kalah {{ $stCount }} kali beruntun (update terbaru)">
                                            ▼ {{ $stCount }}K
                                        </span>
                                    @endif
                                </div>
                                @include('partials.team_players', [
                                    'p1' => $k['pemain1'] ?? null,
                                    'p2' => $k['pemain2'] ?? null,
                                    'short' => false,
                                    'size' => 'xs',
                                ])
                            </td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-medium text-slate-700 dark:text-slate-200">{{ $k['mp'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $k['w'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-medium text-rose-500 dark:text-rose-400">{{ $k['l'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold text-emerald-700 dark:text-emerald-300">{{ $k['bm'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold text-rose-600 dark:text-rose-400">{{ $k['bk'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold {{ $k['sb'] > 0 ? 'text-emerald-700 dark:text-emerald-300' : ($k['sb'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-400 dark:text-slate-500') }}">
                                {{ $k['sb'] > 0 ? '+' : '' }}{{ (int) $k['sb'] }}
                            </td>
                            <td class="py-4 px-3 sm:px-4 text-center font-mono font-extrabold text-base text-slate-900 dark:text-white {{ $isLeader ? 'bg-amber-100/50 dark:bg-amber-500/20' : 'bg-slate-50/60 dark:bg-slate-800/60' }}">
                                {{ $k['pts'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Explanations Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-slate-600 dark:text-slate-400">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5 text-sm">
                <i class="bi bi-info-circle text-indigo-500"></i> Sistem Penilaian
            </div>
            <ul class="space-y-1.5 text-slate-500 dark:text-slate-400">
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">Menang (W):</strong> Memperoleh 3 Poin Liga.</li>
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">Kalah (L):</strong> Memperoleh 0 Poin Liga.</li>
                <li>&bull; Pertandingan wajib ada pemenang (tidak ada hasil seri).</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5 text-sm">
                <i class="bi bi-calculator text-indigo-500"></i> Keterangan Kolom
            </div>
            <ul class="space-y-1.5 text-slate-500 dark:text-slate-400">
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">MP:</strong> Matches Played (Total laga dimainkan)</li>
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">+ :</strong> Poin Masuk (Total poin dikumpulkan tim)</li>
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">- :</strong> Poin Keluar (Total kebobolan oleh lawan)</li>
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">&Delta; :</strong> Selisih Skor (Poin Masuk dikurangi Keluar)</li>
                <li>&bull; <strong class="text-slate-700 dark:text-slate-200">PTS:</strong> Total Poin Liga klasemen</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-1.5 text-sm">
                <i class="bi bi-sort-numeric-down text-indigo-500"></i> Penentuan Peringkat (Tiebreaker)
            </div>
            <ol class="space-y-1.5 text-slate-500 dark:text-slate-400 list-decimal list-inside">
                <li>Poin Liga Tertinggi (PTS)</li>
                <li>Jumlah Kemenangan Terbanyak (W)</li>
                <li>Selisih Skor (&Delta;) Terbesar</li>
                <li>Total Poin Masuk (+) Terbanyak</li>
                <li>Hasil Pertemuan Langsung (Head-to-Head)</li>
            </ol>
        </div>
    </div>
@endif
@endsection
