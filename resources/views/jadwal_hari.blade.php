@extends('layouts.app')

@section('title', 'Jadwal Hari Ini')

@section('content')
@php
    $tanggalAktif = $tanggal ?? date('Y-m-d');
    $prevDate = \Carbon\Carbon::parse($tanggalAktif)->subDay()->format('Y-m-d');
    $nextDate = \Carbon\Carbon::parse($tanggalAktif)->addDay()->format('Y-m-d');
    $today = date('Y-m-d');
    $selectedTim = (int) ($fTim ?? 0);
    $selectedStatus = (string) ($fStatus ?? '');
    $tampilJadwal = $tampil ?? ($liga->status !== 'draft');
    $teams = $daftarTim ?? [];
    $matches = $rows ?? collect();
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Jadwal Harian</h1>
        <p class="text-sm text-slate-500 mt-1">Pantau pertandingan berdasarkan tanggal pelaksanaan.</p>
    </div>
    <!-- Quick Date Navigation -->
    <div class="flex items-center gap-1.5 bg-white p-1 rounded-2xl border border-slate-200/80 shadow-xs">
        <a class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors" href="{{ route('jadwal.hari', array_filter(['tanggal' => $prevDate, 'tim' => $selectedTim ?: null, 'status' => $selectedStatus ?: null])) }}" title="Hari Sebelumnya">
            <i class="bi bi-chevron-left text-sm"></i>
        </a>
        <div class="px-3 py-1 text-xs font-bold text-slate-800 flex items-center gap-1.5">
            <i class="bi bi-calendar-event text-indigo-500"></i>
            <span>{{ \App\Services\TournamentService::hariId($tanggalAktif) }}, {{ \App\Services\TournamentService::tglId($tanggalAktif) }}</span>
        </div>
        <a class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition-colors" href="{{ route('jadwal.hari', array_filter(['tanggal' => $nextDate, 'tim' => $selectedTim ?: null, 'status' => $selectedStatus ?: null])) }}" title="Hari Berikutnya">
            <i class="bi bi-chevron-right text-sm"></i>
        </a>
        @if ($tanggalAktif !== $today)
            <a class="px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-colors ml-1" href="{{ route('jadwal.hari', array_filter(['tanggal' => $today, 'tim' => $selectedTim ?: null, 'status' => $selectedStatus ?: null])) }}">
                Hari Ini
            </a>
        @endif
    </div>
</div>

<!-- Date & Filters Form -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get" action="{{ route('jadwal.hari') }}">
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Pilih Tanggal</label>
            <input type="date" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" value="{{ $tanggalAktif }}">
        </div>
        <div class="sm:col-span-3">
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
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Status Pertandingan</label>
            <select name="status" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Status</option>
                @foreach (\App\Services\TournamentService::STATUS_MATCH as $k => $v)
                    <option value="{{ $k }}" {{ $selectedStatus === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2 flex items-end">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </form>
</div>

@if (!$tampilJadwal)
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-lock text-4xl text-slate-400 mb-3 block"></i>
        Jadwal liga belum dipublikasikan oleh administrator.
    </div>
@elseif ($matches->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-x text-4xl text-slate-400 mb-3 block"></i>
        Tidak ada jadwal pertandingan pada tanggal <strong>{{ \App\Services\TournamentService::hariId($tanggalAktif) }}, {{ \App\Services\TournamentService::tglId($tanggalAktif) }}</strong>.
    </div>
@else
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <span>Pertandingan Terjadwal</span>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $matches->count() }} Match
                </span>
            </div>
        </div>
        @include('partials.match_table', ['matches' => $matches, 'tampilTanggal' => false])
    </div>
@endif
@endsection
