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
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 shadow-xs transition-colors print:hidden">
            <i class="bi bi-printer"></i> Cetak Klasemen
        </button>
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-xs">
            <i class="bi bi-info-circle text-indigo-500"></i>
            <span>3 Poin Menang &middot; 0 Kalah</span>
        </div>
    </div>
</div>

@if ($liga->status === 'selesai' && !empty($klasemen))
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
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-people text-4xl text-slate-400 mb-3 block"></i>
        Belum ada tim terdaftar.
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs mb-8">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-4 px-3 sm:px-4 text-center w-12 sm:w-14">Pos</th>
                        <th scope="col" class="py-4 px-4 min-w-44">Tim &amp; Pemain</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Matches Played (Main)">MP</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Won (Menang)">W</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center" title="Lost (Kalah)">L</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center text-emerald-700 font-bold" title="Poin Masuk (+)">+</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center text-rose-600 font-bold" title="Poin Keluar (-)">-</th>
                        <th scope="col" class="py-4 px-2 sm:px-3 text-center font-bold" title="Selisih Poin (&Delta;)">Δ</th>
                        <th scope="col" class="py-4 px-3 sm:px-4 text-center font-bold text-slate-900 bg-slate-100/50" title="Total Poin Liga">PTS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($klasemen as $k)
                        @php $isLeader = ($k['pos'] === 1 && $k['mp'] > 0); @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors {{ $isLeader ? 'bg-amber-50/40' : '' }}">
                            <td class="py-4 px-3 sm:px-4 text-center">
                                @if ($k['pos'] === 1)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-amber-100 text-amber-800 font-black text-xs shadow-xs">
                                        1
                                    </span>
                                @elseif ($k['pos'] === 2)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-slate-200/80 text-slate-700 font-black text-xs">
                                        2
                                    </span>
                                @elseif ($k['pos'] === 3)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-orange-100 text-orange-800 font-black text-xs">
                                        3
                                    </span>
                                @else
                                    <span class="text-xs font-semibold text-slate-400">{{ $k['pos'] }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <a class="font-bold text-slate-900 hover:text-indigo-600 transition-colors block" href="{{ route('tim.detail', $k['id']) }}">
                                    {{ $k['nama_tim'] }}
                                </a>
                                <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                                    <span>{{ $k['pemain1'] }}</span>
                                    <span>&amp;</span>
                                    <span>{{ $k['pemain2'] }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-medium text-slate-700">{{ $k['mp'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold text-emerald-600">{{ $k['w'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-medium text-rose-500">{{ $k['l'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold text-emerald-700">{{ $k['bm'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold text-rose-600">{{ $k['bk'] }}</td>
                            <td class="py-4 px-2 sm:px-3 text-center font-mono font-bold {{ $k['sb'] > 0 ? 'text-emerald-700' : ($k['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                {{ $k['sb'] > 0 ? '+' : '' }}{{ (int) $k['sb'] }}
                            </td>
                            <td class="py-4 px-3 sm:px-4 text-center font-mono font-extrabold text-base text-slate-900 bg-slate-50/60">
                                {{ $k['pts'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Explanations Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-slate-600">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="font-bold text-slate-900 mb-2 flex items-center gap-1.5 text-sm">
                <i class="bi bi-info-circle text-indigo-500"></i> Sistem Penilaian
            </div>
            <ul class="space-y-1.5 text-slate-500">
                <li>&bull; <strong class="text-slate-700">Menang (W):</strong> Memperoleh 3 Poin Liga.</li>
                <li>&bull; <strong class="text-slate-700">Kalah (L):</strong> Memperoleh 0 Poin Liga.</li>
                <li>&bull; Pertandingan wajib ada pemenang (tidak ada hasil seri).</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="font-bold text-slate-900 mb-2 flex items-center gap-1.5 text-sm">
                <i class="bi bi-calculator text-indigo-500"></i> Keterangan Kolom
            </div>
            <ul class="space-y-1.5 text-slate-500">
                <li>&bull; <strong class="text-slate-700">MP:</strong> Matches Played (Total laga dimainkan)</li>
                <li>&bull; <strong class="text-slate-700">+ :</strong> Poin Masuk (Total poin dikumpulkan tim)</li>
                <li>&bull; <strong class="text-slate-700">- :</strong> Poin Keluar (Total kebobolan oleh lawan)</li>
                <li>&bull; <strong class="text-slate-700">Δ :</strong> Selisih Skor (Poin Masuk dikurangi Keluar)</li>
                <li>&bull; <strong class="text-slate-700">PTS:</strong> Total Poin Liga klasemen</li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="font-bold text-slate-900 mb-2 flex items-center gap-1.5 text-sm">
                <i class="bi bi-sort-numeric-down text-indigo-500"></i> Penentuan Peringkat (Tiebreaker)
            </div>
            <ol class="space-y-1.5 text-slate-500 list-decimal list-inside">
                <li>Poin Liga Tertinggi (PTS)</li>
                <li>Head-to-head (H2H) antar tim bersangkutan</li>
                <li>Selisih Skor (Δ) terbesar</li>
                <li>Total Poin Masuk (+) terbanyak</li>
            </ol>
        </div>
    </div>
@endif
@endsection
