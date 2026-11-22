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
                @include('partials.team_players', [
                    'p1' => $m->homeTeam->pemain1 ?? null,
                    'p2' => $m->homeTeam->pemain2 ?? null,
                    'short' => false,
                    'size' => 'xs',
                    'class' => 'justify-center md:justify-end',
                ])
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
                @include('partials.team_players', [
                    'p1' => $m->awayTeam->pemain1 ?? null,
                    'p2' => $m->awayTeam->pemain2 ?? null,
                    'short' => false,
                    'size' => 'xs',
                    'class' => 'justify-center md:justify-start',
                ])
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

<!-- Polling Tebak Pemenang & Sorakan Penonton -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
    <!-- Polling / Tebak Pemenang -->
    <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 space-y-4">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-bar-chart-steps text-indigo-500"></i>
                    <span>Polling Tebak Pemenang</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $m->status === 'selesai' ? 'Polling ditutup karena pertandingan telah selesai.' : 'Pilih tim jagoan Anda dan lihat suara penonton!' }}
                </p>
            </div>
            <span id="voteTotalBadge" class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/70">
                {{ (int) ($voteSummary['total'] ?? 0) }} Suara
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <button type="button" data-vote-choice="home" @disabled($m->status === 'selesai')
                class="match-vote-btn p-3.5 rounded-2xl border text-left transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed {{ ($voteSummary['user_choice'] ?? null) === 'home' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-800/70 text-slate-800 dark:text-slate-100 border-slate-200 dark:border-slate-700 hover:border-indigo-400' }}">
                <div class="text-[10px] uppercase tracking-wider font-bold opacity-80">Tim Home</div>
                <div class="text-sm font-extrabold truncate mt-0.5">{{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}</div>
                <div class="mt-2 flex items-center justify-between text-xs font-mono font-bold">
                    <span id="voteHomePct">{{ (int) ($voteSummary['home_pct'] ?? 50) }}%</span>
                    <span id="voteHomeCount" class="opacity-80">{{ (int) ($voteSummary['home'] ?? 0) }} suara</span>
                </div>
            </button>

            <button type="button" data-vote-choice="away" @disabled($m->status === 'selesai')
                class="match-vote-btn p-3.5 rounded-2xl border text-left transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed {{ ($voteSummary['user_choice'] ?? null) === 'away' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-800/70 text-slate-800 dark:text-slate-100 border-slate-200 dark:border-slate-700 hover:border-emerald-400' }}">
                <div class="text-[10px] uppercase tracking-wider font-bold opacity-80">Tim Away</div>
                <div class="text-sm font-extrabold truncate mt-0.5">{{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}</div>
                <div class="mt-2 flex items-center justify-between text-xs font-mono font-bold">
                    <span id="voteAwayPct">{{ (int) ($voteSummary['away_pct'] ?? 50) }}%</span>
                    <span id="voteAwayCount" class="opacity-80">{{ (int) ($voteSummary['away'] ?? 0) }} suara</span>
                </div>
            </button>
        </div>

        <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden flex">
            <div id="voteHomeBar" class="h-full bg-indigo-600 transition-all duration-300" style="width: {{ (int) ($voteSummary['home_pct'] ?? 50) }}%"></div>
            <div id="voteAwayBar" class="h-full bg-emerald-500 transition-all duration-300" style="width: {{ (int) ($voteSummary['away_pct'] ?? 50) }}%"></div>
        </div>
    </div>

    <!-- Sorakan Penonton (Live Cheer Presets) -->
    <div class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xs p-6 space-y-4 relative overflow-hidden">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-megaphone-fill text-amber-500"></i>
                    <span>Sorakan Penonton</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Kirim dukungan cepat ke meja pertandingan!</p>
            </div>
            <span id="cheerTotalBadge" class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/70">
                {{ (int) ($cheerSummary['total'] ?? 0) }} Sorakan
            </span>
        </div>

        <div class="grid grid-cols-2 gap-2.5">
            @foreach (\App\Models\PertandinganCheer::REACTIONS as $rKey => $rInfo)
                @php
                    $isChosen = ($cheerSummary['user_reaction'] ?? null) === $rKey;
                    $cnt = (int) ($cheerSummary['counts'][$rKey] ?? 0);
                @endphp
                <button type="button" data-cheer-key="{{ $rKey }}"
                    class="match-cheer-btn flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-2xl border text-xs font-bold transition-all cursor-pointer {{ $isChosen ? 'bg-amber-500 text-slate-950 border-amber-500 shadow-sm' : 'bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:border-amber-400' }}">
                    <span class="flex items-center gap-1.5">
                        <span class="text-base leading-none">{{ $rInfo['emoji'] }}</span>
                        <span>{{ $rInfo['label'] }}</span>
                    </span>
                    <span class="font-mono font-extrabold cheer-count">{{ $cnt }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>

@if (!empty($prediksi))
    @php
        $homePct = $prediksi['home_pct'];
        $awayPct = $prediksi['away_pct'];
        $favored = $prediksi['favored'];
        $hStat = $prediksi['home'];
        $aStat = $prediksi['away'];
    @endphp
    <!-- Perkiraan Peluang Kemenangan (Hanya untuk laga belum selesai) -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-8 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 mb-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">
                    <i class="bi bi-graph-up-arrow"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Perkiraan Peluang Kemenangan</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Dihitung dari Win Rate, tren 5 laga terakhir, selisih skor rata-rata, dan peringkat.</p>
                </div>
            </div>
            @if (!$prediksi['has_data'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 w-fit">
                    Belum Ada Data Laga
                </span>
            @elseif ($favored === 'even')
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 w-fit">
                    Peluang Seimbang
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 w-fit">
                    <i class="bi bi-stars"></i>
                    Diunggulkan: {{ $favored === 'home' ? ($m->homeTeam->nama_tim ?? 'Tim Home') : ($m->awayTeam->nama_tim ?? 'Tim Away') }}
                </span>
            @endif
        </div>

        <!-- Bar Persentase -->
        <div class="mb-5">
            <div class="flex items-end justify-between gap-4 mb-2">
                <div class="text-left">
                    <div class="text-xs font-bold text-slate-700 truncate max-w-[160px] sm:max-w-xs">{{ $m->homeTeam->nama_tim ?? 'Tim Home' }}</div>
                    <div class="text-2xl sm:text-3xl font-black font-mono {{ $favored === 'home' ? 'text-indigo-600' : 'text-slate-700' }}">{{ $homePct }}%</div>
                </div>
                <div class="text-xs font-bold uppercase tracking-widest text-slate-400 pb-1">Peluang</div>
                <div class="text-right">
                    <div class="text-xs font-bold text-slate-700 truncate max-w-[160px] sm:max-w-xs">{{ $m->awayTeam->nama_tim ?? 'Tim Away' }}</div>
                    <div class="text-2xl sm:text-3xl font-black font-mono {{ $favored === 'away' ? 'text-emerald-600' : 'text-slate-700' }}">{{ $awayPct }}%</div>
                </div>
            </div>

            <div class="w-full h-4 rounded-full bg-slate-100 overflow-hidden flex shadow-inner">
                <div class="h-full bg-indigo-600 transition-all duration-500" style="width: {{ $homePct }}%"></div>
                <div class="h-full bg-emerald-500 transition-all duration-500" style="width: {{ $awayPct }}%"></div>
            </div>
        </div>

        <!-- Perbandingan Indikator -->
        <div class="grid grid-cols-3 gap-2 sm:gap-4 text-center mb-5">
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                <div class="text-[11px] font-semibold uppercase text-slate-400">Win Rate</div>
                <div class="text-xs sm:text-sm font-bold font-mono text-slate-800 mt-1">
                    <span class="text-indigo-600">{{ (int) round($hStat['win_rate']) }}%</span>
                    <span class="text-slate-300 mx-1">vs</span>
                    <span class="text-emerald-600">{{ (int) round($aStat['win_rate']) }}%</span>
                </div>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                <div class="text-[11px] font-semibold uppercase text-slate-400">Tren Terakhir</div>
                <div class="text-xs sm:text-sm font-bold font-mono text-slate-800 mt-1">
                    <span class="text-indigo-600">{{ !empty($hStat['recent_form']) ? implode('', $hStat['recent_form']) : '-' }}</span>
                    <span class="text-slate-300 mx-1">vs</span>
                    <span class="text-emerald-600">{{ !empty($aStat['recent_form']) ? implode('', $aStat['recent_form']) : '-' }}</span>
                </div>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                <div class="text-[11px] font-semibold uppercase text-slate-400">Rata2 Selisih</div>
                <div class="text-xs sm:text-sm font-bold font-mono text-slate-800 mt-1">
                    <span class="text-indigo-600">{{ $hStat['avg_diff'] > 0 ? '+' : '' }}{{ $hStat['avg_diff'] }}</span>
                    <span class="text-slate-300 mx-1">vs</span>
                    <span class="text-emerald-600">{{ $aStat['avg_diff'] > 0 ? '+' : '' }}{{ $aStat['avg_diff'] }}</span>
                </div>
            </div>
        </div>

        <!-- Disclaimer Wajib -->
        <div class="p-3.5 rounded-2xl bg-amber-50/80 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-2.5">
            <i class="bi bi-info-circle-fill text-amber-600 text-base flex-shrink-0 mt-0.5"></i>
            <div>
                <strong>Catatan:</strong> Persentase ini hanya perkiraan statistik berdasarkan riwayat pertandingan sebelumnya. Kemenangan sesungguhnya tetap ditentukan di meja pertandingan.
            </div>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var voteUrl = @json(route('pertandingan.vote', $m->id));
    var cheerUrl = @json(route('pertandingan.cheer', $m->id));

    document.querySelectorAll('.match-vote-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) return;
            var choice = btn.getAttribute('data-vote-choice');
            fetch(voteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ choice: choice }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success || !data.summary) return;
                var s = data.summary;
                document.getElementById('voteTotalBadge').textContent = s.total + ' Suara';
                document.getElementById('voteHomePct').textContent = s.home_pct + '%';
                document.getElementById('voteAwayPct').textContent = s.away_pct + '%';
                document.getElementById('voteHomeCount').textContent = s.home + ' suara';
                document.getElementById('voteAwayCount').textContent = s.away + ' suara';
                document.getElementById('voteHomeBar').style.width = s.home_pct + '%';
                document.getElementById('voteAwayBar').style.width = s.away_pct + '%';

                document.querySelectorAll('.match-vote-btn').forEach(function (b) {
                    var c = b.getAttribute('data-vote-choice');
                    var isSel = s.user_choice === c;
                    b.className = 'match-vote-btn p-3.5 rounded-2xl border text-left transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed ' +
                        (isSel
                            ? (c === 'home' ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-emerald-600 text-white border-emerald-600 shadow-sm')
                            : 'bg-slate-50 dark:bg-slate-800/70 text-slate-800 dark:text-slate-100 border-slate-200 dark:border-slate-700 hover:border-indigo-400');
                });
            })
            .catch(function () {});
        });
    });

    document.querySelectorAll('.match-cheer-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var reaction = btn.getAttribute('data-cheer-key');
            fetch(cheerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ reaction: reaction }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success || !data.summary) return;
                var s = data.summary;
                document.getElementById('cheerTotalBadge').textContent = s.total + ' Sorakan';
                document.querySelectorAll('.match-cheer-btn').forEach(function (b) {
                    var k = b.getAttribute('data-cheer-key');
                    var cntEl = b.querySelector('.cheer-count');
                    if (cntEl) cntEl.textContent = String((s.counts && s.counts[k]) || 0);
                    var isSel = s.user_reaction === k;
                    b.className = 'match-cheer-btn flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-2xl border text-xs font-bold transition-all cursor-pointer ' +
                        (isSel
                            ? 'bg-amber-500 text-slate-950 border-amber-500 shadow-sm'
                            : 'bg-slate-50 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:border-amber-400');
                });
            })
            .catch(function () {});
        });
    });
});
</script>
@endsection
