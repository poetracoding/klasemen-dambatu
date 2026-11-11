@extends('layouts.app')

@section('title', 'Jadwal Liga')

@section('content')
@php
    $jadwalGrup = $grup ?? $jadwalByTanggal ?? [];
    $teams = $daftarTim ?? $tims ?? [];
    $selectedTim = (int) ($fTim ?? $timId ?? 0);
    $selectedStatus = (string) ($fStatus ?? '');
    $selectedTanggal = (string) ($tanggal ?? '');
    $tampilJadwal = $tampil ?? ($liga->status !== 'draft');
    $totalCount = 0;
    foreach ($jadwalGrup as $mList) {
        $totalCount += count($mList);
    }
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Seluruh Liga</h1>
        <p class="text-sm text-slate-500 mt-1">Daftar pertandingan lengkap single round-robin (Total: {{ $totalCount }} laga).</p>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs print:hidden cursor-pointer">
            <i class="bi bi-printer"></i> Cetak Jadwal
        </button>
        <a href="{{ route('jadwal.hari') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs print:hidden">
            <i class="bi bi-calendar-day"></i> Jadwal Harian
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8 print:hidden">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get" action="{{ route('jadwal') }}">
        <div class="sm:col-span-5">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Filter Tim</label>
            <select name="tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="0">Semua Tim</option>
                @foreach ($teams as $t)
                    <option value="{{ $t->id }}" {{ $selectedTim === (int) $t->id ? 'selected' : '' }}>
                        {{ $t->nama_tim }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Status Pertandingan</label>
            <select name="status" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Status</option>
                @foreach (\App\Services\TournamentService::STATUS_MATCH as $k => $v)
                    <option value="{{ $k }}" {{ $selectedStatus === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-3 flex items-end">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                <i class="bi bi-funnel"></i> Terapkan Filter
            </button>
        </div>
    </form>
</div>

@if (!$tampilJadwal)
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-lock text-4xl text-slate-400 mb-3 block"></i>
        Jadwal liga belum dipublikasikan oleh administrator.
    </div>
@elseif (empty($jadwalGrup))
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-x text-4xl text-slate-400 mb-3 block"></i>
        Tidak ada pertandingan yang sesuai dengan kriteria filter.
    </div>
@else
    <div class="space-y-8">
        @foreach ($jadwalGrup as $tgl => $matchList)
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">
                            @if ($tgl)
                                {{ \App\Services\TournamentService::hariId($tgl) }}, {{ \App\Services\TournamentService::tglId($tgl) }}
                            @else
                                Tanggal Belum Ditentukan
                            @endif
                        </h2>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                        {{ count($matchList) }} Pertandingan
                    </span>
                </div>

                @include('partials.match_table', ['matches' => $matchList, 'tampilTanggal' => false])
            </div>
        @endforeach
    </div>
@endif
@endsection
