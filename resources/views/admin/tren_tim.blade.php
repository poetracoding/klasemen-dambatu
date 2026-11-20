@extends('layouts.admin')

@section('title', 'Analisis & Tren Tim')

@section('content')
@php
    $trenList = collect($trenTims ?? []);
    $cntPositif = $trenList->whereIn('trend_key', ['dominant', 'rising'])->count();
    $cntStabil = $trenList->where('trend_key', 'steady')->count();
    $cntNegatif = $trenList->whereIn('trend_key', ['slump', 'vulnerable'])->count();
    $cntNoData = $trenList->where('trend_key', 'no_data')->count();

    $badgeMap = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
            <i class="bi bi-activity text-indigo-600"></i>
            <span>Analisis &amp; Tren Tim</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Pemetaan tren kemenangan/kekalahan, momentum 5 laga terakhir, dan proyeksi sisa pertandingan.</p>
    </div>
    <div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

<!-- Disclaimer Wajib -->
<div class="mb-6 p-4 rounded-2xl bg-amber-50/90 border border-amber-200 text-amber-900 text-xs sm:text-sm flex items-start gap-3 shadow-xs">
    <i class="bi bi-info-circle-fill text-amber-600 text-lg flex-shrink-0 mt-0.5"></i>
    <div>
        <strong>Catatan Penting:</strong> Seluruh analisis tren, peluang kemenangan, dan proyeksi sisa laga di halaman ini <strong>hanyalah perkiraan statistik</strong> berdasarkan riwayat pertandingan sebelumnya. Kemenangan sesungguhnya tetap ditentukan di meja pertandingan.
    </div>
</div>

<!-- 4 Ringkasan Kategori Tren -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-emerald-200/70 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">Tren Menang / Membaik</div>
        <div class="text-3xl font-extrabold text-slate-900 font-mono">{{ $cntPositif }}</div>
        <div class="text-xs text-slate-400 mt-1">Tim berpeluang dominan</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-indigo-200/70 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">Seimbang / Stabil</div>
        <div class="text-3xl font-extrabold text-slate-900 font-mono">{{ $cntStabil }}</div>
        <div class="text-xs text-slate-400 mt-1">Performa berimbang</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-rose-200/70 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-rose-600 mb-1">Tren Menurun / Rentan</div>
        <div class="text-3xl font-extrabold text-slate-900 font-mono">{{ $cntNegatif }}</div>
        <div class="text-xs text-slate-400 mt-1">Berisiko terus kalah</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Belum Bertanding</div>
        <div class="text-3xl font-extrabold text-slate-900 font-mono">{{ $cntNoData }}</div>
        <div class="text-xs text-slate-400 mt-1">Menunggu laga pertama</div>
    </div>
</div>

@if ($trenList->isEmpty())
    <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-people text-4xl text-slate-400 mb-3 block"></i>
        Belum ada tim terdaftar pada liga ini.
    </div>
@else
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900">Peringkat Kekuatan &amp; Tren Performa Tim</h2>
                <p class="text-xs text-slate-400 mt-0.5">Diurutkan dari indeks kekuatan &amp; tren kemenangan tertinggi ke terendah.</p>
            </div>
            <span class="text-xs text-slate-400">Total: {{ $trenList->count() }} tim</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">#</th>
                        <th class="py-3.5 px-4">Tim &amp; Pemain</th>
                        <th class="py-3.5 px-3 text-center">M - K (Win Rate)</th>
                        <th class="py-3.5 px-3 text-center">5 Laga Terakhir</th>
                        <th class="py-3.5 px-4">Status Tren</th>
                        <th class="py-3.5 px-4 text-center">Proyeksi Sisa Laga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($trenList as $idx => $t)
                        @php
                            $bCls = $badgeMap[$t['trend_color']] ?? $badgeMap['slate'];
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 text-center font-mono text-xs font-bold text-slate-400">
                                {{ $idx + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <a href="{{ route('tim.detail', $t['id']) }}" target="_blank" class="font-bold text-slate-900 hover:text-indigo-600 transition-colors">
                                    {{ $t['nama_tim'] }}
                                </a>
                                @include('partials.team_players', [
                                    'p1' => $t['pemain1'] ?? null,
                                    'p2' => $t['pemain2'] ?? null,
                                    'short' => true,
                                    'maxLen' => 7,
                                ])
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-800">{{ $t['w'] }}W - {{ $t['l'] }}L</div>
                                <div class="text-xs font-mono {{ $t['win_rate'] > 50 ? 'text-emerald-600' : ($t['win_rate'] == 50 && $t['mp'] > 0 ? 'text-orange-600' : 'text-rose-600') }}">
                                    {{ (int) round($t['win_rate']) }}% &middot; &Delta; {{ $t['avg_diff'] > 0 ? '+' : '' }}{{ $t['avg_diff'] }}/laga
                                </div>
                            </td>
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if (empty($t['recent_form']))
                                    <span class="text-xs text-slate-400">-</span>
                                @else
                                    <div class="inline-flex items-center gap-1">
                                        @foreach ($t['recent_form'] as $rf)
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-md text-[10px] font-black {{ $rf === 'W' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $rf }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if ($t['streak_count'] >= 2)
                                        <div class="text-[10px] font-bold mt-1 {{ $t['streak_type'] === 'W' ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $t['streak_count'] }}{{ $t['streak_type'] }} beruntun
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $bCls }}">
                                    {{ $t['trend_label'] }}
                                </span>
                                <div class="text-[11px] text-slate-400 mt-1 max-w-xs">{{ $t['trend_desc'] }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if ($t['remaining_count'] > 0)
                                    <div class="text-xs font-bold text-slate-800 font-mono">
                                        Est. {{ $t['projected_wins'] }}M / {{ $t['projected_losses'] }}K
                                        <span class="text-slate-400 font-normal">({{ $t['remaining_count'] }} sisa)</span>
                                    </div>
                                    <div class="text-[11px] font-semibold mt-0.5 {{ $t['projected_win_pct'] > 50 ? 'text-emerald-600' : ($t['projected_win_pct'] == 50 ? 'text-orange-600' : 'text-rose-600') }}">
                                        Rata-rata peluang: {{ $t['projected_win_pct'] }}%
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Semua laga selesai</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
