@extends('layouts.admin')

@section('title', 'Laporan Hasil Pertandingan')

@section('content')
@php
    $pdfParams = array_filter([
        'periode' => $periode,
        'tanggal' => $periode === 'harian' ? $selectedTanggal : null,
        'minggu' => $periode === 'mingguan' ? $selectedMinggu : null,
        'bulan' => $periode === 'bulanan' ? $selectedBulan : null,
        'tim' => $timId > 0 ? $timId : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2.5">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="bi bi-file-earmark-bar-graph text-indigo-600"></i>
                <span>Laporan Hasil Pertandingan</span>
            </h1>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ $periodeJudul }}: {{ $periodeLabel }}
            </span>
        </div>
        <p class="text-sm text-slate-500 mt-1">
            Rekap hasil pertandingan selesai berdasarkan waktu update (<span class="font-mono text-xs">updated_at</span>) per hari, minggu, dan bulan.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <a href="{{ route('admin.pengaturan.report.pdf', $pdfParams) }}" target="_blank"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs">
            <i class="bi bi-printer"></i>
            <span>Pratinjau PDF</span>
        </a>
        <a href="{{ route('admin.pengaturan.report.pdf', array_merge($pdfParams, ['download' => 1])) }}"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
            <i class="bi bi-file-earmark-arrow-down-fill"></i>
            <span>Ekspor PDF</span>
        </a>
    </div>
</div>

<!-- Filter Periode & Tim -->
<div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-xs mb-6">
    <!-- Tab Mode Periode -->
    <div class="flex flex-wrap items-center gap-2 pb-4 mb-4 border-b border-slate-100">
        @foreach (['harian' => ['label' => 'Per Hari', 'icon' => 'bi-calendar-event'], 'mingguan' => ['label' => 'Per Minggu', 'icon' => 'bi-calendar-week'], 'bulanan' => ['label' => 'Per Bulan', 'icon' => 'bi-calendar-month']] as $modeKey => $modeMeta)
            @php
                $isTabActive = $periode === $modeKey;
                $tabUrl = route('admin.pengaturan.report', array_filter([
                    'periode' => $modeKey,
                    'tanggal' => $selectedTanggal,
                    'minggu' => $selectedMinggu,
                    'bulan' => $selectedBulan,
                    'tim' => $timId > 0 ? $timId : null,
                ]));
            @endphp
            <a href="{{ $tabUrl }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all {{ $isTabActive ? 'bg-indigo-600 text-white shadow-xs shadow-indigo-600/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' }}">
                <i class="bi {{ $modeMeta['icon'] }}"></i>
                <span>{{ $modeMeta['label'] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.pengaturan.report') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <input type="hidden" name="periode" value="{{ $periode }}">

        <div class="md:col-span-5">
            @if ($periode === 'mingguan')
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Minggu (Senin - Minggu)</label>
                <select name="minggu" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
                    @foreach ($availableWeeks as $wk)
                        <option value="{{ $wk['value'] }}" {{ $selectedMinggu === $wk['value'] ? 'selected' : '' }}>
                            {{ $wk['label'] }}
                        </option>
                    @endforeach
                </select>
            @elseif ($periode === 'bulanan')
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Bulan</label>
                <select name="bulan" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
                    @foreach ($availableMonths as $bl)
                        <option value="{{ $bl['value'] }}" {{ $selectedBulan === $bl['value'] ? 'selected' : '' }}>
                            {{ $bl['label'] }}
                        </option>
                    @endforeach
                </select>
            @else
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Tanggal Update</label>
                <input type="date" name="tanggal" value="{{ $selectedTanggal }}"
                    class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
            @endif
        </div>

        <div class="md:col-span-5">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Filter Tim &amp; Pemain <span class="text-slate-400 font-normal">(opsional)</span></label>
            <select name="tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
                <option value="0">Semua Tim</option>
                @foreach ($tims as $t)
                    <option value="{{ $t->id }}" {{ (int) $timId === (int) $t->id ? 'selected' : '' }}>
                        {{ $t->nama_tim }} ({{ \App\Services\TournamentService::pemainLabel($t->pemain1, $t->pemain2, false) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2 flex gap-2">
            <button type="submit"
                class="w-full inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                <i class="bi bi-funnel-fill text-xs"></i>
                <span>Tampilkan</span>
            </button>
        </div>
    </form>

    @if ($periode === 'harian' && !empty($recentDates))
        <div class="mt-4 pt-3.5 border-t border-slate-100 flex flex-wrap items-center gap-1.5">
            <span class="text-[11px] font-semibold text-slate-400 mr-1">Tanggal Update Aktif:</span>
            @foreach ($recentDates as $rd)
                @php
                    $isActiveDate = $selectedTanggal === $rd['value'];
                    $chipUrl = route('admin.pengaturan.report', array_filter([
                        'periode' => 'harian',
                        'tanggal' => $rd['value'],
                        'tim' => $timId > 0 ? $timId : null,
                    ]));
                @endphp
                <a href="{{ $chipUrl }}"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition-colors {{ $isActiveDate ? 'bg-indigo-50 text-indigo-700 border-indigo-300' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                    <span>{{ $rd['label'] }}</span>
                    <span class="px-1.5 py-0.2 rounded-full bg-white/80 text-[10px] font-mono">{{ $rd['count'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>

<!-- 4 KPI Ringkasan -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold text-slate-400">Laga Selesai</div>
        <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $totalLaga }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Berdasarkan <span class="font-mono">updated_at</span></div>
    </div>
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold text-slate-400">Tim Bertanding</div>
        <div class="text-2xl font-extrabold text-indigo-600 mt-1 font-mono">{{ $uniqueTeamCount }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">{{ $uniqueTeamCount * 2 }} pemain terlibat</div>
    </div>
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold text-slate-400">Total Skor Tercipta</div>
        <div class="text-2xl font-extrabold text-emerald-600 mt-1 font-mono">{{ number_format($totalPoin) }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Akumulasi poin kedua tim</div>
    </div>
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold text-slate-400">Rata-rata Selisih</div>
        <div class="text-2xl font-extrabold text-amber-600 mt-1 font-mono">&plusmn;{{ $avgSelisih }}</div>
        <div class="text-[11px] text-slate-400 mt-0.5">Poin per pertandingan</div>
    </div>
</div>

<!-- Detail Pertandingan Selesai (dikelompokkan per tanggal updated_at) -->
@if ($matches->isEmpty())
    <div class="bg-white rounded-3xl border border-dashed border-slate-200 p-12 text-center text-slate-400 text-sm mb-6">
        <i class="bi bi-inbox text-4xl text-slate-300 mb-2 block"></i>
        Tidak ada pertandingan berstatus <strong>Selesai</strong> dengan waktu update pada periode <strong>{{ $periodeLabel }}</strong>{{ $selectedTim ? ' untuk tim ' . $selectedTim->nama_tim : '' }}.
    </div>
@else
    <div class="space-y-6 mb-8">
        @foreach ($matchesByUpdateDate as $ymd => $dayMatches)
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-5 py-3.5 bg-slate-50/90 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                        <i class="bi bi-calendar-check text-indigo-600"></i>
                        <span>{{ \App\Http\Controllers\Admin\ReportController::formatTanggalId($ymd) }}</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $dayMatches->count() }} Pertandingan
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[820px] text-left text-sm divide-y divide-slate-100">
                        <thead class="bg-slate-50/50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-4 w-24">Match</th>
                                <th class="py-3 px-3 w-32">Update (WIB)</th>
                                <th class="py-3 px-4">Tim Home &amp; Pemain</th>
                                <th class="py-3 px-3 text-center w-24">Skor</th>
                                <th class="py-3 px-4">Tim Away &amp; Pemain</th>
                                <th class="py-3 px-4">Pemenang</th>
                                <th class="py-3 px-4">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($dayMatches as $m)
                                @php
                                    $homeName = $m->homeTeam?->nama_tim ?? ('Tim #' . $m->tim_home);
                                    $awayName = $m->awayTeam?->nama_tim ?? ('Tim #' . $m->tim_away);
                                    $homePlayers = \App\Services\TournamentService::pemainLabel($m->homeTeam?->pemain1, $m->homeTeam?->pemain2, false);
                                    $awayPlayers = \App\Services\TournamentService::pemainLabel($m->awayTeam?->pemain1, $m->awayTeam?->pemain2, false);
                                    $isHomeWinner = (int) $m->pemenang_id === (int) $m->tim_home;
                                    $isAwayWinner = (int) $m->pemenang_id === (int) $m->tim_away;
                                    $winPlayers = $isHomeWinner ? $homePlayers : ($isAwayWinner ? $awayPlayers : '');
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="font-mono text-xs font-bold text-slate-800">#{{ $m->nomor_pertandingan }}</div>
                                        <div class="text-[11px] text-slate-400">Ronde {{ $m->ronde }} &middot; {{ $m->lokasi ?: '-' }}</div>
                                    </td>
                                    <td class="py-3.5 px-3 align-top">
                                        <div class="font-mono text-xs font-semibold text-slate-800">
                                            {{ $m->updated_at ? $m->updated_at->format('H:i') : '-' }} WIB
                                        </div>
                                        <div class="font-mono text-[11px] text-slate-400">
                                            {{ $m->updated_at ? $m->updated_at->format('d/m/Y') : '-' }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="font-bold text-sm {{ $isHomeWinner ? 'text-emerald-700' : 'text-slate-900' }}">
                                            {{ $homeName }}
                                            @if ($isHomeWinner)
                                                <i class="bi bi-trophy-fill text-amber-500 text-xs ml-1" title="Pemenang"></i>
                                            @endif
                                        </div>
                                        @if ($homePlayers !== '')
                                            <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                                <i class="bi bi-people text-[11px] text-slate-400"></i>
                                                <span>{{ $homePlayers }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-center align-top">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-900 font-mono text-sm font-extrabold border border-slate-200">
                                            {{ $m->skor_home }} - {{ $m->skor_away }}
                                        </span>
                                        <div class="text-[10px] text-slate-400 font-mono mt-1">
                                            &Delta; {{ abs((int) $m->skor_home - (int) $m->skor_away) }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 align-top">
                                        <div class="font-bold text-sm {{ $isAwayWinner ? 'text-emerald-700' : 'text-slate-900' }}">
                                            {{ $awayName }}
                                            @if ($isAwayWinner)
                                                <i class="bi bi-trophy-fill text-amber-500 text-xs ml-1" title="Pemenang"></i>
                                            @endif
                                        </div>
                                        @if ($awayPlayers !== '')
                                            <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                                <i class="bi bi-people text-[11px] text-slate-400"></i>
                                                <span>{{ $awayPlayers }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 align-top">
                                        @if ($m->pemenang)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <i class="bi bi-check-circle-fill text-[10px]"></i>
                                                {{ $m->pemenang->nama_tim }}
                                            </span>
                                            @if ($winPlayers !== '')
                                                <div class="text-[11px] text-slate-500 mt-1">{{ $winPlayers }}</div>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 align-top text-xs text-slate-500">
                                        {{ $m->catatan ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Rekap Performa Tim Periode Ini -->
    @if (!empty($rekapTim))
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-trophy text-amber-500"></i>
                        <span>Rekap Performa Tim pada Periode Ini</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Akumulasi statistik khusus pertandingan selesai pada {{ $periodeLabel }}.</p>
                </div>
                <span class="text-xs text-slate-400 font-semibold">{{ count($rekapTim) }} Tim</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm divide-y divide-slate-100">
                    <thead class="bg-slate-50/70 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-3.5 px-4 text-center w-14">No</th>
                            <th class="py-3.5 px-4">Tim &amp; Pemain</th>
                            <th class="py-3.5 px-3 text-center">Main</th>
                            <th class="py-3.5 px-3 text-center">Menang</th>
                            <th class="py-3.5 px-3 text-center">Kalah</th>
                            <th class="py-3.5 px-3 text-center text-emerald-700">+</th>
                            <th class="py-3.5 px-3 text-center text-rose-600">-</th>
                            <th class="py-3.5 px-3 text-center">&Delta;</th>
                            <th class="py-3.5 px-4 text-center font-bold text-slate-900 bg-slate-100/50">PTS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach ($rekapTim as $row)
                            @php
                                $rowPlayers = \App\Services\TournamentService::pemainLabel($row['pemain1'], $row['pemain2'], false);
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 text-center font-mono text-xs font-bold text-slate-500">{{ $row['pos'] }}</td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $row['nama_tim'] }}</div>
                                    @if ($rowPlayers !== '')
                                        <div class="text-xs text-slate-500 mt-0.5">{{ $rowPlayers }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center font-mono text-xs">{{ $row['mp'] }}</td>
                                <td class="py-3 px-3 text-center font-mono text-xs font-bold text-emerald-700">{{ $row['w'] }}</td>
                                <td class="py-3 px-3 text-center font-mono text-xs text-rose-600">{{ $row['l'] }}</td>
                                <td class="py-3 px-3 text-center font-mono text-xs">{{ $row['bm'] }}</td>
                                <td class="py-3 px-3 text-center font-mono text-xs">{{ $row['bk'] }}</td>
                                <td class="py-3 px-3 text-center font-mono text-xs font-bold {{ $row['sb'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                    {{ $row['sb'] > 0 ? '+' : '' }}{{ $row['sb'] }}
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-sm font-extrabold text-slate-900 bg-slate-50/70">{{ $row['pts'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif
@endsection
