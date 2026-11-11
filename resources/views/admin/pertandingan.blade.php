@extends('layouts.admin')

@section('title', 'Input Hasil Pertandingan')

@section('content')
@php
    $pertandingans = $matches ?? $pertandingans ?? collect();
    $filterStatus = $f ?? $filterStatus ?? '';
    $filterTanggal = $ft ?? $filterTanggal ?? '';
    $teams = $tims ?? \App\Models\Tim::where('liga_id', $liga->id)->orderBy('nama_tim')->get();
    $filterTim = (int) request('tim', 0);
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Input Hasil &amp; Poin Pertandingan</h1>
        <p class="text-sm text-slate-500 mt-1">Masukkan skor/poin pertandingan seperti gol sepak bola dan tentukan status laga.</p>
    </div>
</div>

@if ($liga->status === 'draft')
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
        <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg flex-shrink-0"></i>
        <div>Jadwal liga masih draft. Publikasikan terlebih dahulu di menu <a href="{{ route('admin.jadwal.index') }}" class="font-bold underline hover:text-amber-800">Jadwal</a> agar hasil dapat diinput.</div>
    </div>
@endif

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get" action="{{ route('admin.pertandingan.index') }}">
        <div class="sm:col-span-5">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Filter Status</label>
            <select name="f" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Pertandingan</option>
                <option value="belum" {{ $filterStatus === 'belum' ? 'selected' : '' }}>Belum Selesai</option>
                <option value="selesai" {{ $filterStatus === 'selesai' ? 'selected' : '' }}>Sudah Selesai</option>
            </select>
        </div>
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Pilih Tanggal</label>
            <input type="date" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" value="{{ $filterTanggal }}">
        </div>
        <div class="sm:col-span-3 flex items-end">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </form>
</div>

@if ($pertandingans->isEmpty())
    <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-x text-4xl text-slate-400 mb-3 block"></i>
        Tidak ada data pertandingan yang sesuai.
    </div>
@else
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-3.5 px-3 text-center w-10">#</th>
                        <th scope="col" class="py-3.5 px-3 w-32">Jadwal &amp; Meja</th>
                        <th scope="col" class="py-3.5 px-3">Pertandingan</th>
                        <th scope="col" class="py-3.5 px-3 w-36 text-center">Poin / Skor</th>
                        <th scope="col" class="py-3.5 px-3 w-32">Status</th>
                        <th scope="col" class="py-3.5 px-3 w-40">Pemenang</th>
                        <th scope="col" class="py-3.5 px-3">Catatan</th>
                        <th scope="col" class="py-3.5 px-3 text-center w-14">Simpan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($pertandingans as $m)
                        @php
                            $fid = 'h' . $m->id;
                            $tglStr = $m->tanggal ? \Carbon\Carbon::parse($m->tanggal)->format('Y-m-d') : null;
                            $homeName = $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home);
                            $awayName = $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away);
                        @endphp
                        <tr id="m{{ $m->id }}" class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-3 text-center font-mono text-xs text-slate-400">
                                #{{ $m->nomor_pertandingan }}
                            </td>
                            <td class="py-3 px-3 whitespace-nowrap text-xs">
                                <div class="font-semibold text-slate-800">
                                    {{ $tglStr ? \App\Services\TournamentService::hariId($tglStr) . ', ' . \App\Services\TournamentService::tglId($tglStr) : '-' }}
                                </div>
                                <div class="text-slate-400 font-mono text-3xs">
                                    {{ \App\Services\TournamentService::jamId($m->waktu) }} &middot; <span class="text-indigo-600 font-bold">{{ $m->lokasi ?: '-' }}</span>
                                </div>
                            </td>
                            <!-- Kolom Pertandingan -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="font-bold text-slate-900 text-xs leading-snug">{{ $homeName }}</div>
                                <div class="text-3xs text-slate-400 leading-snug">vs <span class="font-bold text-slate-800">{{ $awayName }}</span></div>
                            </td>
                            <!-- Kolom Poin / Skor dengan Font Kecil Rapi -->
                            <td class="py-3 px-3 whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-1.5 bg-slate-100/90 py-1 px-2 rounded-xl border border-slate-200/80 shadow-2xs">
                                    <div class="text-center">
                                        <div class="text-[9px] font-semibold text-slate-500 truncate max-w-16 leading-tight" title="{{ $homeName }}">
                                            {{ $homeName }}
                                        </div>
                                        <input form="{{ $fid }}" type="number" min="0" max="99" name="skor_home"
                                            class="w-10 h-7 text-center text-xs font-black font-mono rounded-lg border border-slate-300 bg-white text-slate-900 form-input-focus score-input-home shadow-2xs"
                                            value="{{ (int) ($m->skor_home ?? 0) }}"
                                            title="Poin {{ $homeName }}"
                                            onchange="autoPilihPemenang({{ $m->id }}, {{ $m->tim_home }}, {{ $m->tim_away }})">
                                    </div>
                                    <span class="font-extrabold text-slate-300 text-xs mt-3">:</span>
                                    <div class="text-center">
                                        <div class="text-[9px] font-semibold text-slate-500 truncate max-w-16 leading-tight" title="{{ $awayName }}">
                                            {{ $awayName }}
                                        </div>
                                        <input form="{{ $fid }}" type="number" min="0" max="99" name="skor_away"
                                            class="w-10 h-7 text-center text-xs font-black font-mono rounded-lg border border-slate-300 bg-white text-slate-900 form-input-focus score-input-away shadow-2xs"
                                            value="{{ (int) ($m->skor_away ?? 0) }}"
                                            title="Poin {{ $awayName }}"
                                            onchange="autoPilihPemenang({{ $m->id }}, {{ $m->tim_home }}, {{ $m->tim_away }})">
                                    </div>
                                </div>
                            </td>
                            <!-- Status -->
                            <td class="py-3 px-3">
                                <select form="{{ $fid }}" id="status-{{ $m->id }}" name="status" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2 text-slate-800 form-input-focus">
                                    @foreach (\App\Services\TournamentService::STATUS_MATCH as $k => $v)
                                        <option value="{{ $k }}" {{ $m->status === $k ? 'selected' : '' }}>{{ $v }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <!-- Pemenang -->
                            <td class="py-3 px-3">
                                <select form="{{ $fid }}" id="pemenang-{{ $m->id }}" name="pemenang_id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2 text-slate-800 form-input-focus">
                                    <option value="">— Belum / Pilih —</option>
                                    <option value="{{ $m->tim_home }}" {{ (int) $m->pemenang_id === (int) $m->tim_home ? 'selected' : '' }}>{{ $homeName }}</option>
                                    <option value="{{ $m->tim_away }}" {{ (int) $m->pemenang_id === (int) $m->tim_away ? 'selected' : '' }}>{{ $awayName }}</option>
                                </select>
                            </td>
                            <!-- Catatan -->
                            <td class="py-3 px-3">
                                <input form="{{ $fid }}" name="catatan" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-1.5 px-2.5 text-slate-800 form-input-focus" maxlength="200" placeholder="Catatan opsional..." value="{{ $m->catatan }}">
                            </td>
                            <!-- Tombol Simpan -->
                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                <form id="{{ $fid }}" action="{{ route('admin.pertandingan.simpan', $m->id) }}" method="POST" class="form-hasil">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center p-2 rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" {{ $liga->status === 'draft' ? 'disabled' : '' }} title="Simpan Skor &amp; Hasil">
                                        <i class="bi bi-floppy text-sm"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
function autoPilihPemenang(matchId, homeId, awayId) {
    const row = document.getElementById('m' + matchId);
    if (!row) return;
    const skorHome = parseInt(row.querySelector('input[name="skor_home"]').value, 10) || 0;
    const skorAway = parseInt(row.querySelector('input[name="skor_away"]').value, 10) || 0;
    const selectPemenang = document.getElementById('pemenang-' + matchId);
    const selectStatus = document.getElementById('status-' + matchId);

    if (skorHome > 0 || skorAway > 0) {
        if (selectStatus.value === 'belum_main') {
            selectStatus.value = 'selesai';
        }
        if (skorHome > skorAway) {
            selectPemenang.value = homeId;
        } else if (skorAway > skorHome) {
            selectPemenang.value = awayId;
        }
    }
}
</script>
@endsection
