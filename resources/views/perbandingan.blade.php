@extends('layouts.app')

@section('title', 'Perbandingan Tim (Head-to-Head)')

@section('content')
<div class="space-y-6 ldb-fade-in">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/70 mb-2">
                <i class="bi bi-arrows-angle-contract"></i>
                <span>Head-to-Head Explorer</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">Perbandingan Tim</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pilih dua tim untuk membandingkan klasemen, rasio menang, selisih batu, tren 5 laga terakhir, dan rekor pertemuan langsung.
            </p>
        </div>
        <a href="{{ route('klasemen') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-xs transition-colors w-fit shrink-0">
            <i class="bi bi-arrow-left"></i>
            <span>Kembali ke Klasemen</span>
        </a>
    </div>

    @if ($tims->count() < 2)
        <div class="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-12 text-center shadow-xs">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center mb-3">
                <i class="bi bi-people text-2xl"></i>
            </div>
            <h2 class="text-base font-bold text-slate-800 dark:text-white">Minimal 2 Tim Diperlukan</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                Fitur perbandingan Head-to-Head akan aktif secara otomatis setelah minimal dua tim terdaftar di liga ini.
            </p>
        </div>
    @else
        <!-- Form Pemilih 2 Tim -->
        <form method="GET" action="{{ route('perbandingan') }}" id="h2hForm"
              class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr_auto] gap-3 items-end">
                <div>
                    <label for="tim1" class="block text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-1.5">
                        Tim Pertama
                    </label>
                    <select id="tim1" name="tim1" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-semibold px-3.5 py-2.5 form-input-focus">
                        @foreach ($tims as $t)
                            <option value="{{ $t->id }}" {{ (int) $tim1Id === (int) $t->id ? 'selected' : '' }}>
                                {{ $t->nama_tim }} ({{ $t->pemain1 }} &amp; {{ $t->pemain2 }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-center pb-0.5">
                    <button type="button" id="btnSwapTeams"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors cursor-pointer"
                            title="Tukar posisi kedua tim" aria-label="Tukar posisi kedua tim">
                        <i class="bi bi-arrow-left-right text-base"></i>
                    </button>
                </div>

                <div>
                    <label for="tim2" class="block text-xs font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-1.5">
                        Tim Kedua
                    </label>
                    <select id="tim2" name="tim2" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-semibold px-3.5 py-2.5 form-input-focus">
                        @foreach ($tims as $t)
                            <option value="{{ $t->id }}" {{ (int) $tim2Id === (int) $t->id ? 'selected' : '' }}>
                                {{ $t->nama_tim }} ({{ $t->pemain1 }} &amp; {{ $t->pemain2 }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors cursor-pointer">
                    <i class="bi bi-bar-chart-steps"></i>
                    <span>Bandingkan</span>
                </button>
            </div>
        </form>

        @if ($sameTeamWarning)
            <div class="rounded-2xl border border-amber-200 dark:border-amber-700/60 bg-amber-50 dark:bg-amber-500/15 p-4 text-sm text-amber-800 dark:text-amber-200 flex items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill text-lg shrink-0"></i>
                <span>Pilih dua tim yang berbeda untuk melihat perbandingan Head-to-Head.</span>
            </div>
        @elseif ($comparison)
            @php
                $t1 = $comparison['tim1'];
                $t2 = $comparison['tim2'];
                $k1 = $comparison['k1'];
                $k2 = $comparison['k2'];
                $tr1 = $comparison['tr1'];
                $tr2 = $comparison['tr2'];
                $pred = $comparison['prediksi'];
                $pHome = (int) ($pred['home_prob'] ?? 50);
                $pAway = (int) ($pred['away_prob'] ?? 50);
            @endphp

            <!-- Kartu Duel Utama -->
            <div class="rounded-3xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_1fr] gap-5 items-center">
                    <!-- Tim 1 -->
                    <div class="rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-200/70 dark:border-indigo-800/60 p-4 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-between gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-lg text-[11px] font-extrabold bg-indigo-600 text-white">
                                Peringkat #{{ $k1['pos'] }}
                            </span>
                            <span class="text-xs font-mono font-bold text-indigo-700 dark:text-indigo-300">
                                {{ $k1['pts'] }} PTS
                            </span>
                        </div>
                        <a href="{{ route('tim.detail', $t1->id) }}" class="block text-lg font-black text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-300 transition-colors">
                            {{ $t1->nama_tim }}
                        </a>
                        <div class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                            {{ $t1->pemain1 }} &amp; {{ $t1->pemain2 }}
                        </div>
                        <div class="mt-3 flex items-center justify-center md:justify-start gap-1 flex-wrap">
                            @forelse (($tr1['recent_form'] ?? []) as $rf)
                                <span class="w-6 h-6 rounded-md text-[10px] font-black inline-flex items-center justify-center {{ $rf === 'W' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                                    {{ $rf === 'W' ? 'M' : 'K' }}
                                </span>
                            @empty
                                <span class="text-[11px] text-slate-400">Belum ada laga</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- Skor H2H Tengah -->
                    <div class="text-center px-3">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">
                            Rekor H2H
                        </div>
                        <div class="inline-flex items-center gap-3 px-4 py-2 rounded-2xl bg-slate-900 dark:bg-slate-800 text-white font-mono text-2xl font-black shadow-sm">
                            <span class="text-indigo-300">{{ $comparison['h2h_w1'] }}</span>
                            <span class="text-slate-500 text-base">-</span>
                            <span class="text-rose-300">{{ $comparison['h2h_w2'] }}</span>
                        </div>
                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                            Total Batu H2H: <strong class="text-slate-700 dark:text-slate-200">{{ $comparison['h2h_pts1'] }}</strong> vs <strong class="text-slate-700 dark:text-slate-200">{{ $comparison['h2h_pts2'] }}</strong>
                        </div>
                    </div>

                    <!-- Tim 2 -->
                    <div class="rounded-2xl bg-rose-50/60 dark:bg-rose-950/35 border border-rose-200/70 dark:border-rose-800/60 p-4 text-center md:text-right">
                        <div class="flex items-center justify-center md:justify-between gap-2 mb-1 md:flex-row-reverse">
                            <span class="px-2 py-0.5 rounded-lg text-[11px] font-extrabold bg-rose-600 text-white">
                                Peringkat #{{ $k2['pos'] }}
                            </span>
                            <span class="text-xs font-mono font-bold text-rose-700 dark:text-rose-300">
                                {{ $k2['pts'] }} PTS
                            </span>
                        </div>
                        <a href="{{ route('tim.detail', $t2->id) }}" class="block text-lg font-black text-slate-900 dark:text-white hover:text-rose-600 dark:hover:text-rose-300 transition-colors">
                            {{ $t2->nama_tim }}
                        </a>
                        <div class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                            {{ $t2->pemain1 }} &amp; {{ $t2->pemain2 }}
                        </div>
                        <div class="mt-3 flex items-center justify-center md:justify-end gap-1 flex-wrap">
                            @forelse (($tr2['recent_form'] ?? []) as $rf)
                                <span class="w-6 h-6 rounded-md text-[10px] font-black inline-flex items-center justify-center {{ $rf === 'W' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' }}">
                                    {{ $rf === 'W' ? 'M' : 'K' }}
                                </span>
                            @empty
                                <span class="text-[11px] text-slate-400">Belum ada laga</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Perkiraan Peluang Menang -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between text-xs font-bold">
                        <span class="text-indigo-600 dark:text-indigo-400">{{ $t1->nama_tim }} {{ $pHome }}%</span>
                        <span class="text-[11px] uppercase tracking-wider text-slate-400">Perkiraan Peluang Menang</span>
                        <span class="text-rose-600 dark:text-rose-400">{{ $pAway }}% {{ $t2->nama_tim }}</span>
                    </div>
                    <div class="h-3 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex">
                        <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500" style="width: {{ $pHome }}%"></div>
                        <div class="bg-gradient-to-r from-rose-500 to-rose-600 transition-all duration-500" style="width: {{ $pAway }}%"></div>
                    </div>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center">
                        Estimasi berdasarkan klasemen, selisih batu, dan tren performa terbaru (hanya perkiraan statistik).
                    </p>
                </div>

                <!-- Baris Perbandingan Statistik -->
                @php
                    $avgBm1 = ($k1['mp'] ?? 0) > 0 ? round($k1['bm'] / $k1['mp'], 1) : 0;
                    $avgBm2 = ($k2['mp'] ?? 0) > 0 ? round($k2['bm'] / $k2['mp'], 1) : 0;
                    $statRows = [
                        ['label' => 'Rasio Menang (Win Rate)', 'v1' => $comparison['wr1'] . '%', 'v2' => $comparison['wr2'] . '%', 'n1' => $comparison['wr1'], 'n2' => $comparison['wr2']],
                        ['label' => 'Poin Klasemen (PTS)', 'v1' => (string) $k1['pts'], 'v2' => (string) $k2['pts'], 'n1' => (float) $k1['pts'], 'n2' => (float) $k2['pts']],
                        ['label' => 'Menang / Main', 'v1' => $k1['w'] . ' / ' . $k1['mp'], 'v2' => $k2['w'] . ' / ' . $k2['mp'], 'n1' => (float) $k1['w'], 'n2' => (float) $k2['w']],
                        ['label' => 'Selisih Batu (Δ)', 'v1' => ($k1['sb'] > 0 ? '+' : '') . $k1['sb'], 'v2' => ($k2['sb'] > 0 ? '+' : '') . $k2['sb'], 'n1' => (float) $k1['sb'], 'n2' => (float) $k2['sb']],
                        ['label' => 'Rata-rata Batu Masuk', 'v1' => (string) $avgBm1, 'v2' => (string) $avgBm2, 'n1' => (float) $avgBm1, 'n2' => (float) $avgBm2],
                    ];
                @endphp
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    @foreach ($statRows as $sr)
                        @php
                            $win1 = $sr['n1'] > $sr['n2'];
                            $win2 = $sr['n2'] > $sr['n1'];
                        @endphp
                        <div class="grid grid-cols-3 items-center gap-2 py-2 px-3 rounded-xl bg-slate-50/70 dark:bg-slate-800/60 text-xs sm:text-sm">
                            <div class="font-mono font-extrabold {{ $win1 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $sr['v1'] }}
                            </div>
                            <div class="text-center text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400">
                                {{ $sr['label'] }}
                            </div>
                            <div class="text-right font-mono font-extrabold {{ $win2 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $sr['v2'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Riwayat Pertemuan Langsung -->
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-clock-history text-indigo-500"></i>
                        <span>Riwayat Pertemuan Langsung</span>
                    </h2>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                        {{ $comparison['h2h_matches']->count() }} Laga
                    </span>
                </div>

                @if ($comparison['h2h_matches']->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-slate-700 p-8 text-center">
                        <i class="bi bi-calendar-event text-2xl text-slate-400 mb-2 block"></i>
                        <div class="text-sm font-bold text-slate-700 dark:text-slate-200">Belum Ada Pertemuan Terjadwal</div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Kedua tim belum memiliki jadwal atau catatan pertandingan pada musim ini.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($comparison['h2h_matches'] as $m)
                            @php
                                $tgl = $m->tanggal ? \App\Services\TournamentService::tglId(\Carbon\Carbon::parse($m->tanggal)->format('Y-m-d')) : '-';
                                $skorAda = in_array($m->status, ['selesai', 'berlangsung'], true);
                            @endphp
                            <a href="{{ route('pertandingan.detail', $m->id) }}"
                               class="py-3.5 px-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/70 dark:hover:bg-slate-800/70 rounded-xl transition-colors">
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    <span class="font-bold text-slate-700 dark:text-slate-200">#{{ $m->nomor_pertandingan }}</span>
                                    &middot; {{ $tgl }}
                                    @if ($m->lokasi)
                                        &middot; {{ $m->lokasi }}
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-sm font-bold">
                                    <span class="{{ $m->status === 'selesai' && $m->pemenang_id == $m->tim_home ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-800 dark:text-slate-200' }}">
                                        {{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}
                                    </span>
                                    @if ($skorAda)
                                        <span class="px-2.5 py-0.5 rounded-md bg-slate-900 dark:bg-slate-700 text-white font-mono text-xs font-black">
                                            {{ (int) ($m->skor_home ?? 0) }} - {{ (int) ($m->skor_away ?? 0) }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs">vs</span>
                                    @endif
                                    <span class="{{ $m->status === 'selesai' && $m->pemenang_id == $m->tim_away ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-800 dark:text-slate-200' }}">
                                        {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}
                                    </span>
                                </div>
                                <div>{!! \App\Services\TournamentService::badgeStatus($m->status) !!}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btnSwap = document.getElementById('btnSwapTeams');
    var s1 = document.getElementById('tim1');
    var s2 = document.getElementById('tim2');
    var form = document.getElementById('h2hForm');
    if (btnSwap && s1 && s2 && form) {
        btnSwap.addEventListener('click', function () {
            var tmp = s1.value;
            s1.value = s2.value;
            s2.value = tmp;
            form.submit();
        });
    }
});
</script>
@endsection
