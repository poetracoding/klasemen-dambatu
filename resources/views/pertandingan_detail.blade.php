@extends('layouts.app')

@php
    $m = $m ?? $pertandingan;
    $skorAda = ($m->status === 'selesai' || $m->status === 'berlangsung');
    $homePemenang = ($m->status === 'selesai' && $m->pemenang_id == $m->tim_home);
    $awayPemenang = ($m->status === 'selesai' && $m->pemenang_id == $m->tim_away);
    $tglStr = $m->tanggal ? \Carbon\Carbon::parse($m->tanggal)->format('Y-m-d') : null;
@endphp

@section('title', 'Detail Pertandingan #' . $m->nomor_pertandingan)

@section('content')
<div class="mb-6">
    <a href="{{ route('jadwal') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
        <i class="bi bi-arrow-left"></i> Kembali ke Jadwal Liga
    </a>
</div>

<!-- Match Arena Card -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
    <!-- Top Bar -->
    <div class="bg-slate-50/80 px-6 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
            <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/60">Match #{{ (int) $m->nomor_pertandingan }}</span>
            <span>&middot;</span>
            <span>Ronde {{ (int) $m->ronde }}</span>
        </div>
        <div>
            {!! \App\Services\TournamentService::badgeStatus($m->status) !!}
        </div>
    </div>

    <!-- Main VS Arena Showcase -->
    <div class="p-8 sm:p-12">
        <div class="grid grid-cols-1 md:grid-cols-12 items-center gap-6">
            <!-- Team Home -->
            <div class="md:col-span-4 text-center md:text-right space-y-2">
                <a class="text-2xl sm:text-3xl font-black text-slate-900 hover:text-indigo-600 transition-colors tracking-tight block {{ $homePemenang ? 'text-indigo-600' : '' }}" href="{{ route('tim.detail', $m->tim_home) }}">
                    {{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}
                </a>
                <div class="text-xs text-slate-500 flex items-center justify-center md:justify-end gap-1.5">
                    <i class="bi bi-people"></i>
                    <span>{{ $m->homeTeam->pemain1 ?? '-' }} &amp; {{ $m->homeTeam->pemain2 ?? '-' }}</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                    Tim Home
                </div>
                @if ($homePemenang)
                    <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                        <i class="bi bi-trophy-fill text-emerald-600"></i> Pemenang
                    </div>
                @endif
            </div>

            <!-- Scoreboard / VS Center -->
            <div class="md:col-span-4 flex flex-col items-center justify-center text-center py-4">
                @if ($skorAda)
                    <div class="inline-flex items-center gap-3 bg-slate-900 text-white px-6 py-3 rounded-2xl shadow-lg shadow-slate-900/10 font-mono font-black text-3xl sm:text-4xl tracking-widest">
                        <span>{{ (int) ($m->skor_home ?? 0) }}</span>
                        <span class="text-slate-500 font-light text-2xl">:</span>
                        <span>{{ (int) ($m->skor_away ?? 0) }}</span>
                    </div>
                    <div class="text-xs font-semibold text-slate-400 mt-2 uppercase tracking-widest">
                        Skor Akhir
                    </div>
                @else
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-black text-lg border border-slate-200">
                        VS
                    </div>
                    <div class="text-xs text-slate-400 mt-2">
                        Belum Dimainkan
                    </div>
                @endif
            </div>

            <!-- Team Away -->
            <div class="md:col-span-4 text-center md:text-left space-y-2">
                <a class="text-2xl sm:text-3xl font-black text-slate-900 hover:text-indigo-600 transition-colors tracking-tight block {{ $awayPemenang ? 'text-indigo-600' : '' }}" href="{{ route('tim.detail', $m->tim_away) }}">
                    {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}
                </a>
                <div class="text-xs text-slate-500 flex items-center justify-center md:justify-start gap-1.5">
                    <i class="bi bi-people"></i>
                    <span>{{ $m->awayTeam->pemain1 ?? '-' }} &amp; {{ $m->awayTeam->pemain2 ?? '-' }}</span>
                </div>
                <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                    Tim Away
                </div>
                @if ($awayPemenang)
                    <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                        <i class="bi bi-trophy-fill text-emerald-600"></i> Pemenang
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Match Information Details Footer -->
    <div class="bg-slate-50/60 px-6 sm:px-8 py-5 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-center sm:text-left">
        <div>
            <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Jadwal Tanggal &amp; Hari</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5">
                {{ $tglStr ? \App\Services\TournamentService::hariId($tglStr) . ', ' . \App\Services\TournamentService::tglId($tglStr) : 'Belum Ditentukan' }}
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Waktu &amp; Lokasi</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5 font-mono">
                {{ \App\Services\TournamentService::jamId($m->waktu) }} &middot; <span class="font-sans font-semibold text-slate-600">{{ $m->lokasi ?: 'Meja Pertandingan' }}</span>
            </div>
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider font-semibold text-slate-400">Catatan Wasit / Panitia</div>
            <div class="text-sm text-slate-600 mt-0.5 italic">
                {{ $m->catatan ?: '-' }}
            </div>
        </div>
    </div>
</div>
@endsection
