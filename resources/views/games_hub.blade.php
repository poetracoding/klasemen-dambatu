@extends('layouts.app')

@section('title', 'Arena Game Publik')

@section('content')
<div class="space-y-6">
    <!-- Hero Arena Game -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 border border-indigo-500/30 shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-indigo-200 text-xs font-semibold">
                    <i class="bi bi-controller"></i>
                    <span>Arena Hiburan Publik &middot; {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Arena Game &amp; Hiburan Meja 3D</h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Nikmati 5 permainan interaktif gratis langsung di browser Anda sambil menanti jadwal pertandingan berikutnya. Seluruh progres dan rekor tersimpan otomatis di perangkat Anda.
                </p>
            </div>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-bold text-white transition-colors w-fit shrink-0">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Grid 5 Game -->
    @php
        $games = [
            [
                'key' => 'dambatu',
                'title' => 'Game Dam Batu 3D',
                'desc' => 'Permainan domino Dam Batu khas turnamen melawan AI dengan papan kartu terhubung, mode 3D meja, dan efek suara.',
                'route' => route('game'),
                'icon' => 'bi-dice-6-fill',
                'gradient' => 'from-indigo-600 to-violet-700',
                'badge' => 'Utama &middot; 3D',
                'meta' => 'Domino Klasik &bull; Lawan AI',
            ],
            [
                'key' => 'monopoli',
                'title' => 'Monopoli Nusantara 3D',
                'desc' => 'Jelajahi 22 kota, 4 transportasi, & BUMN Indonesia dengan mata uang Rupiah, dadu 3D, barter, dan fitur hipotek.',
                'route' => route('game.monopoli'),
                'icon' => 'bi-buildings-fill',
                'gradient' => 'from-emerald-600 to-teal-700',
                'badge' => '2–4 Pemain &bull; 3 Level AI',
                'meta' => 'Ekonomi &bull; Rupiah',
            ],
            [
                'key' => 'ludo',
                'title' => 'Ludo Queen 3D',
                'desc' => 'Papan klasik 15×15 bergaya meja 3D, pion berdiri 3D, lemparan kubus dadu 3D ke tengah papan, dan zona bintang aman.',
                'route' => route('game.ludo_queen'),
                'icon' => 'bi-dice-5-fill',
                'gradient' => 'from-fuchsia-600 to-indigo-600',
                'badge' => '2–4 Pemain &bull; 3 Level AI',
                'meta' => 'Papan 3D &bull; Dadu Tengah',
            ],
            [
                'key' => 'catur',
                'title' => 'Game Catur 3D',
                'desc' => 'Duel strategi aturan FIDE lengkap (Rokade, En Passant, Promosi), jam catur, notasi aljabar, dan 3 level AI Alpha-Beta.',
                'route' => route('game.catur'),
                'icon' => 'bi-grid-3x3-gap-fill',
                'gradient' => 'from-amber-500 to-indigo-600',
                'badge' => 'Strategi &bull; 3 Level AI',
                'meta' => 'FIDE Lengkap &bull; Jam Catur',
            ],
            [
                'key' => 'flappy',
                'title' => 'Game Flappy Bird',
                'desc' => 'Mini-game arkade refleks cepat dengan 3 level kesulitan, 3 tema langit, efek suara Web Audio, dan papan medali lokal.',
                'route' => route('game.flappy_bird'),
                'icon' => 'bi-feather',
                'gradient' => 'from-amber-500 to-orange-600',
                'badge' => 'Arkade &bull; 3 Level',
                'meta' => 'Skor Tertinggi Lokal',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach ($games as $g)
            <div class="group rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition-all flex flex-col justify-between gap-4">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $g['gradient'] }} text-white flex items-center justify-center text-xl shadow-sm shrink-0">
                            <i class="bi {{ $g['icon'] }}"></i>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                            {!! $g['badge'] !!}
                        </span>
                    </div>

                    <div>
                        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                            {{ $g['title'] }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            {{ $g['desc'] }}
                        </p>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400" id="hubStat-{{ $g['key'] }}">
                        {!! $g['meta'] !!}
                    </div>
                    <a href="{{ $g['route'] }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors shrink-0">
                        <span>Main Sekarang</span>
                        <i class="bi bi-play-fill"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function readJson(key) {
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    var flappy = readJson('flappy_dambatu_stats_v1');
    if (flappy) {
        var best = Math.max(flappy.easy || 0, flappy.normal || 0, flappy.hard || 0);
        if (best > 0) {
            var elF = document.getElementById('hubStat-flappy');
            if (elF) elF.innerHTML = '<span class="text-amber-600 dark:text-amber-400 font-bold">🏆 Rekor Terbaik: ' + best + '</span>';
        }
    }

    var ludo = readJson('ludo_queen_dambatu_stats_v1');
    if (ludo) {
        var lw = (ludo.easy?.wins || 0) + (ludo.normal?.wins || 0) + (ludo.hard?.wins || 0);
        if (lw > 0) {
            var elL = document.getElementById('hubStat-ludo');
            if (elL) elL.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400 font-bold">👑 ' + lw + ' Kemenangan</span>';
        }
    }

    var chess = readJson('catur_3d_dambatu_stats_v1');
    if (chess) {
        var cw = (chess.easy?.wins || 0) + (chess.normal?.wins || 0) + (chess.hard?.wins || 0);
        if (cw > 0) {
            var elC = document.getElementById('hubStat-catur');
            if (elC) elC.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400 font-bold">♔ ' + cw + ' Kemenangan</span>';
        }
    }

    var mono = readJson('monopoli_nusantara_stats_v1');
    if (mono && mono.wins > 0) {
        var elM = document.getElementById('hubStat-monopoli');
        if (elM) elM.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400 font-bold">🏢 ' + mono.wins + ' Kemenangan</span>';
    }
});
</script>
@endsection
