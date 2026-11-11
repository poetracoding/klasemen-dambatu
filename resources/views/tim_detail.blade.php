@extends('layouts.app')

@section('title', $tim->nama_tim)

@section('content')
@php
    $stats = $stat ?? $stats ?? null;
    $teamMatches = $matches ?? $riwayat ?? collect();
@endphp

<div class="mb-6">
    <a href="{{ route('klasemen') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
        <i class="bi bi-arrow-left"></i> Kembali ke Klasemen
    </a>
</div>

<!-- Team Profile Card -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-8 mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-600 flex items-center justify-center text-white text-2xl font-black shadow-md shadow-indigo-500/20">
                {{ strtoupper(substr($tim->nama_tim, 0, 2)) }}
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ $tim->nama_tim }}</h1>
                <div class="text-xs text-slate-500 flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                        <i class="bi bi-person-fill text-indigo-500"></i> {{ $tim->pemain1 }}
                    </span>
                    <span>&amp;</span>
                    <span class="inline-flex items-center gap-1 font-medium text-slate-700">
                        <i class="bi bi-person-fill text-indigo-500"></i> {{ $tim->pemain2 }}
                    </span>
                </div>
            </div>
        </div>
        @if ($stats)
            <div class="flex items-center gap-2">
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                    Peringkat #{{ $stats['pos'] }} Klasemen
                </span>
            </div>
        @endif
    </div>

    @if ($stats)
        <!-- Team Stats Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6">
            <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-100 text-center">
                <div class="text-xs font-semibold uppercase text-slate-400">Total Main</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $stats['mp'] }}</div>
                <div class="text-2xs text-slate-400 mt-0.5">Pertandingan</div>
            </div>
            <div class="bg-emerald-50/50 rounded-2xl p-4 border border-emerald-100 text-center">
                <div class="text-xs font-semibold uppercase text-emerald-600">Menang - Kalah</div>
                <div class="text-2xl font-extrabold text-emerald-700 mt-1 font-mono">{{ $stats['w'] }} - {{ $stats['l'] }}</div>
                <div class="text-xs text-emerald-600 font-medium mt-0.5">
                    {{ $stats['mp'] > 0 ? round(($stats['w'] / $stats['mp']) * 100) : 0 }}% Win Rate
                </div>
            </div>
            <div class="bg-slate-50/70 rounded-2xl p-4 border border-slate-100 text-center">
                <div class="text-xs font-semibold uppercase text-slate-400">Poin (+ / -)</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 font-mono">{{ $stats['bm'] }} / {{ $stats['bk'] }}</div>
                <div class="text-xs font-bold mt-0.5 {{ $stats['sb'] > 0 ? 'text-emerald-700' : ($stats['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                    Selisih (&Delta;): {{ $stats['sb'] > 0 ? '+' : '' }}{{ (int) $stats['sb'] }}
                </div>
            </div>
            <div class="bg-indigo-50/50 rounded-2xl p-4 border border-indigo-100 text-center">
                <div class="text-xs font-semibold uppercase text-indigo-600">Poin Liga</div>
                <div class="text-2xl font-black text-indigo-700 mt-1 font-mono">{{ $stats['pts'] }}</div>
                <div class="text-xs text-indigo-600 font-medium mt-0.5">PTS Klasemen</div>
            </div>
        </div>
    @endif
</div>

<!-- Team Match Record -->
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900 tracking-tight">Daftar Pertandingan Tim</h2>
        <span class="text-xs text-slate-500">{{ $teamMatches->count() }} Pertandingan</span>
    </div>
    @include('partials.match_table', ['matches' => $teamMatches, 'tampilTanggal' => true])
</div>
@endsection
