@extends('layouts.app')

@section('title', 'History Pertandingan')

@section('content')
@php
    $completedMatches = $matches ?? $riwayat ?? collect();
    $teams = $daftarTim ?? $tims ?? [];
    $selectedTim = (int) ($fTim ?? $timId ?? 0);
    $fTanggal = request('tanggal', '');
    $cari = request('q', '');
    $totalCount = $totalSelesai ?? $completedMatches->count();
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">History Pertandingan</h1>
        <p class="text-sm text-slate-500 mt-1">Daftar seluruh pertandingan yang telah selesai beserta rincian pemenang (Total: {{ $totalCount }} laga).</p>
    </div>
    <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs print:hidden cursor-pointer">
        <i class="bi bi-printer"></i> Cetak History
    </button>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8 print:hidden">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get" action="{{ route('history') }}">
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Tanggal</label>
            <input type="date" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" value="{{ $fTanggal }}">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Tim</label>
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
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Pencarian</label>
            <div class="relative">
                <i class="bi bi-search absolute left-3.5 top-2.5 text-xs text-slate-400"></i>
                <input type="search" name="q" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-slate-800 form-input-focus" placeholder="Cari tim atau pemain..." value="{{ $cari }}">
            </div>
        </div>
        <div class="sm:col-span-2 flex items-end">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                <i class="bi bi-funnel"></i> Cari
            </button>
        </div>
    </form>
</div>

@if ($completedMatches->isEmpty())
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-clock-history text-4xl text-slate-400 mb-3 block"></i>
        Belum ada riwayat pertandingan selesai yang cocok dengan filter.
    </div>
@else
    @include('partials.match_table', ['matches' => $completedMatches, 'tampilTanggal' => true])
@endif
@endsection
