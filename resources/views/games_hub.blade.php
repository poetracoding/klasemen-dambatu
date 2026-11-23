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

    <!-- Statistik Personal & Lencana Mini-Game -->
    <div class="ldb-fade-in rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/20 text-amber-600 dark:text-amber-300 flex items-center justify-center text-lg shrink-0">
                    <i class="bi bi-award-fill"></i>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white">Statistik Personal &amp; Lencana Mini-Game</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Tersimpan otomatis di perangkat Anda. Raih semua lencana dan pamerkan rekornya!</p>
                </div>
            </div>
            <a href="#" id="btnShareArcadeWa" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs transition-colors w-fit shrink-0">
                <i class="bi bi-whatsapp text-sm"></i>
                <span>Bagikan Rekor ke WhatsApp</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/70 p-3">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Menang Game Papan</div>
                <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-0.5" id="statBoardWins">0</div>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/70 p-3">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Rekor Flappy Bird</div>
                <div class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono mt-0.5" id="statFlappyBest">0</div>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/70 p-3">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Game Dimainkan</div>
                <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5" id="statGamesPlayed">0/5</div>
            </div>
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/70 p-3">
                <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Lencana Diraih</div>
                <div class="text-xl font-black text-fuchsia-600 dark:text-fuchsia-400 font-mono mt-0.5" id="statBadgeCount">0/5</div>
            </div>
        </div>

        <div id="arcadeBadgesContainer" class="flex flex-wrap items-center gap-2 pt-1"></div>
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

    function setCardStat(elId, text, colorClass) {
        var el = document.getElementById(elId);
        if (!el) return;
        el.textContent = '';
        var span = document.createElement('span');
        span.className = colorClass + ' font-bold';
        span.textContent = text;
        el.appendChild(span);
    }

    var dam = readJson('dam_batu_stats_v1');
    var damWins = 0;
    var damPlayed = false;
    if (dam) {
        damWins = (dam.easy?.win || 0) + (dam.normal?.win || 0) + (dam.hard?.win || 0);
        var damTotal = damWins
            + (dam.easy?.lose || 0) + (dam.normal?.lose || 0) + (dam.hard?.lose || 0)
            + (dam.easy?.draw || 0) + (dam.normal?.draw || 0) + (dam.hard?.draw || 0);
        damPlayed = damTotal > 0;
        if (damWins > 0) {
            setCardStat('hubStat-dambatu', '🎲 ' + damWins + ' Kemenangan', 'text-emerald-600 dark:text-emerald-400');
        }
    }

    var flappy = readJson('flappy_dambatu_stats_v1');
    var flappyBest = 0;
    var flappyPlayed = false;
    if (flappy) {
        flappyBest = Math.max(flappy.easy || 0, flappy.normal || 0, flappy.hard || 0);
        flappyPlayed = flappyBest > 0 || (flappy.played || 0) > 0;
        if (flappyBest > 0) {
            setCardStat('hubStat-flappy', '🏆 Rekor Terbaik: ' + flappyBest, 'text-amber-600 dark:text-amber-400');
        }
    }

    var ludo = readJson('ludo_queen_dambatu_stats_v1');
    var ludoWins = 0;
    var ludoPlayed = false;
    if (ludo) {
        ludoWins = (ludo.easy?.wins || 0) + (ludo.normal?.wins || 0) + (ludo.hard?.wins || 0);
        var ludoLosses = (ludo.easy?.losses || 0) + (ludo.normal?.losses || 0) + (ludo.hard?.losses || 0);
        ludoPlayed = (ludoWins + ludoLosses) > 0;
        if (ludoWins > 0) {
            setCardStat('hubStat-ludo', '👑 ' + ludoWins + ' Kemenangan', 'text-emerald-600 dark:text-emerald-400');
        }
    }

    var chess = readJson('catur_3d_dambatu_stats_v1');
    var chessWins = 0;
    var chessPlayed = false;
    if (chess) {
        chessWins = (chess.easy?.wins || 0) + (chess.normal?.wins || 0) + (chess.hard?.wins || 0);
        var chessTotal = chessWins
            + (chess.easy?.losses || 0) + (chess.normal?.losses || 0) + (chess.hard?.losses || 0)
            + (chess.easy?.draws || 0) + (chess.normal?.draws || 0) + (chess.hard?.draws || 0);
        chessPlayed = chessTotal > 0;
        if (chessWins > 0) {
            setCardStat('hubStat-catur', '♔ ' + chessWins + ' Kemenangan', 'text-emerald-600 dark:text-emerald-400');
        }
    }

    var mono = readJson('monopoli_nusantara_stats_v1');
    var monoWins = 0;
    var monoPlayed = false;
    if (mono) {
        monoWins = mono.wins || 0;
        monoPlayed = monoWins > 0 || (mono.games || 0) > 0 || (mono.played || 0) > 0;
        if (monoWins > 0) {
            setCardStat('hubStat-monopoli', '🏢 ' + monoWins + ' Kemenangan', 'text-emerald-600 dark:text-emerald-400');
        }
    }

    var totalBoardWins = damWins + ludoWins + chessWins + monoWins;
    var gamesPlayedCount = [damPlayed, monoPlayed, ludoPlayed, chessPlayed, flappyPlayed].filter(Boolean).length;

    var badges = [
        { label: 'Pemula Arena', icon: 'bi-controller', unlocked: gamesPlayedCount >= 1, hint: 'Mainkan minimal 1 game' },
        { label: 'Master Strategi', icon: 'bi-trophy-fill', unlocked: (damWins + chessWins) >= 1, hint: 'Menang di Dam Batu atau Catur' },
        { label: 'Pilot Ulung', icon: 'bi-feather', unlocked: flappyBest >= 10, hint: 'Raih skor Flappy Bird minimal 10' },
        { label: 'Raja Papan', icon: 'bi-award-fill', unlocked: totalBoardWins >= 3, hint: 'Raih total 3 kemenangan game papan' },
        { label: 'Kolektor 5 Game', icon: 'bi-stars', unlocked: gamesPlayedCount >= 5, hint: 'Coba seluruh 5 game di Arena' },
    ];
    var unlockedCount = badges.filter(function (b) { return b.unlocked; }).length;

    var elBoard = document.getElementById('statBoardWins');
    var elFlappy = document.getElementById('statFlappyBest');
    var elPlayed = document.getElementById('statGamesPlayed');
    var elBadgeCnt = document.getElementById('statBadgeCount');
    if (elBoard) elBoard.textContent = String(totalBoardWins);
    if (elFlappy) elFlappy.textContent = String(flappyBest);
    if (elPlayed) elPlayed.textContent = gamesPlayedCount + '/5';
    if (elBadgeCnt) elBadgeCnt.textContent = unlockedCount + '/5';

    var badgeWrap = document.getElementById('arcadeBadgesContainer');
    if (badgeWrap) {
        badgeWrap.textContent = '';
        badges.forEach(function (b) {
            var pill = document.createElement('span');
            pill.title = b.hint;
            pill.className = b.unlocked
                ? 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 dark:bg-amber-500/20 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-400/40'
                : 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200/70 dark:border-slate-700/70 opacity-75';
            var ic = document.createElement('i');
            ic.className = 'bi ' + b.icon;
            pill.appendChild(ic);
            var txt = document.createElement('span');
            txt.textContent = b.label;
            pill.appendChild(txt);
            badgeWrap.appendChild(pill);
        });
    }

    var shareBtn = document.getElementById('btnShareArcadeWa');
    if (shareBtn) {
        var shareMsg = [
            '🎮 *Rekor Arena Game {{ $liga->nama_liga ?? "Liga Dam Batu" }}*',
            '🏆 Kemenangan Game Papan: ' + totalBoardWins,
            '🪶 Rekor Flappy Bird: ' + flappyBest,
            '🏅 Lencana Diraih: ' + unlockedCount + '/5',
            'Ayo tantang rekor saya di: {{ route("games.hub") }}'
        ].join('\n');
        shareBtn.href = 'https://wa.me/?text=' + encodeURIComponent(shareMsg);
    }
});
</script>
@endsection
