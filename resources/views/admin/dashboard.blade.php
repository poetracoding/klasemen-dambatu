@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
@php
    $totalTim = $jumlahTim ?? $totalTim ?? 0;
    $totalMatch = $total ?? $totalMatch ?? 0;
    $totalSelesai = $selesai ?? $totalSelesai ?? 0;
    $persenSelesai = $totalMatch > 0 ? round(($totalSelesai / $totalMatch) * 100) : 0;
    $belumMulai = $belum ?? $belumMulai ?? 0;
    $sedangMain = $main ?? $sedangMain ?? 0;
    $jadwalHariIni = $hariIni ?? $jadwalHariIni ?? collect();
    $klasemen = $kl ?? $klasemen ?? [];
@endphp

<div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Dashboard Admin</h1>
        <p class="text-sm text-slate-500 mt-1">Ringkasan status, jadwal, dan progres jalannya liga.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.jadwal.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors shadow-xs">
            <i class="bi bi-calendar3 text-indigo-500"></i> Kelola Jadwal
        </a>
        <a href="{{ route('admin.pertandingan.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20">
            <i class="bi bi-trophy"></i> Input Hasil
        </a>
    </div>
</div>

@if (auth()->check() && \Illuminate\Support\Facades\Hash::check('admin123', auth()->user()->password))
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <i class="bi bi-shield-exclamation text-xl text-amber-600"></i>
            <div class="text-xs sm:text-sm">
                <span class="font-bold">Peringatan Keamanan:</span> Anda masih memakai password bawaan (<code class="bg-amber-100 px-1 py-0.5 rounded text-amber-800 font-mono">admin123</code>). Segera ganti di menu <a href="{{ route('admin.pengaturan.index') }}" class="font-bold underline hover:text-amber-700">Pengaturan</a>.
            </div>
        </div>
    </div>
@endif

<!-- Metric Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Status Liga</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</div>
        <div class="text-xs text-slate-400 mt-1 capitalize">Musim {{ $liga->musim }}</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Jumlah Tim</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ (int) $totalTim }}</div>
        <div class="text-xs text-indigo-600 font-medium mt-1">
            <a href="{{ route('admin.tim.index') }}" class="hover:underline">Kelola tim &rarr;</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Total Pertandingan</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">
            {{ (int) $totalMatch }}
            @if ($totalTim > 1)
                <span class="text-base text-slate-400 font-normal"> / {{ (int) (($totalTim * ($totalTim - 1)) / 2) }}</span>
            @endif
        </div>
        <div class="text-xs text-slate-400 mt-1">Round Robin</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Progres Selesai</div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ (int) $totalSelesai }} <span class="text-base font-normal text-slate-400">({{ $persenSelesai }}%)</span></div>
        <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
            <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $persenSelesai }}%"></div>
        </div>
    </div>
</div>

<!-- Match Breakdown -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600">
                <i class="bi bi-clock text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-400">Belum Main</div>
                <div class="text-xl font-bold text-slate-800">{{ $belumMulai }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                <i class="bi bi-play-circle text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-amber-600">Sedang Berlangsung</div>
                <div class="text-xl font-bold text-slate-800">{{ $sedangMain }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                <i class="bi bi-check-circle text-lg"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-emerald-600">Selesai</div>
                <div class="text-xl font-bold text-slate-800">{{ $totalSelesai }}</div>
            </div>
        </div>
    </div>
</div>

<!-- 2 Columns: Today's Matches & Standings Preview -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-7 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="bi bi-calendar-event text-indigo-500"></i> Pertandingan Hari Ini
            </h2>
            <span class="text-xs text-slate-400">{{ \App\Services\TournamentService::tglId(date('Y-m-d')) }}</span>
        </div>

        @if ($jadwalHariIni->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                Tidak ada pertandingan dijadwalkan untuk hari ini.
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="py-3 px-3">Jam &amp; Meja</th>
                            <th scope="col" class="py-3 px-3">Pertandingan</th>
                            <th scope="col" class="py-3 px-3 text-center">Status</th>
                            <th scope="col" class="py-3 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($jadwalHariIni as $m)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-3 font-mono text-xs whitespace-nowrap">
                                    <span class="font-bold text-slate-800">{{ \App\Services\TournamentService::jamId($m->waktu) }}</span>
                                    <div class="text-slate-400 font-sans text-2xs">{{ $m->lokasi ?: 'Meja' }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-slate-800 line-clamp-1">
                                        {{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}
                                        <span class="text-xs text-slate-400 font-normal">vs</span>
                                        {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}
                                    </div>
                                    @if ($m->status === 'selesai' || $m->status === 'berlangsung')
                                        <div class="text-xs font-mono font-bold text-indigo-600">
                                            Skor: {{ $m->skor_home }} - {{ $m->skor_away }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    {!! \App\Services\TournamentService::badgeStatus($m->status) !!}
                                </td>
                                <td class="py-3 px-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.pertandingan.index', ['tanggal' => $m->tanggal]) }}#m{{ $m->id }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        Input <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Standings Preview -->
    <div class="lg:col-span-5 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <i class="bi bi-list-ol text-indigo-500"></i> Top 5 Klasemen
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('klasemen.pdf') }}" target="_blank" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 flex items-center gap-1" title="Cetak PDF Klasemen">
                    <i class="bi bi-printer"></i> PDF
                </a>
                <a href="{{ route('klasemen') }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:underline">
                    Buka Publik &rarr;
                </a>
            </div>
        </div>

        @if (empty($klasemen))
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
                Belum ada tim terdaftar.
            </div>
        @else
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th scope="col" class="py-3 px-3 text-center w-8">#</th>
                            <th scope="col" class="py-3 px-3">Tim</th>
                            <th scope="col" class="py-3 px-2 text-center">MP</th>
                            <th scope="col" class="py-3 px-2 text-center">Δ</th>
                            <th scope="col" class="py-3 px-3 text-center font-bold text-slate-800">PTS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach (array_slice($klasemen, 0, 5) as $k)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-3 text-center text-xs font-bold text-slate-400">
                                    {{ $k['pos'] }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-slate-800 line-clamp-1">
                                    {{ $k['nama_tim'] }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono text-xs text-slate-500">{{ $k['mp'] }}</td>
                                <td class="py-3 px-2 text-center font-mono text-xs font-bold {{ $k['sb'] > 0 ? 'text-emerald-700' : ($k['sb'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                                    {{ $k['sb'] > 0 ? '+' : '' }}{{ (int) $k['sb'] }}
                                </td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-900">{{ $k['pts'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
