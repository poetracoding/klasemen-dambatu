@extends('layouts.app')

@section('title', 'Game Dam Batu')

@section('content')
<script>window.DISABLE_LIVE_UPDATE = true;</script>

<style>
    @keyframes dominoShuffleLeft {
        0%, 100% { transform: translate(0, 0) rotate(-6deg); }
        25% { transform: translate(-26px, -10px) rotate(-18deg); }
        50% { transform: translate(18px, 6px) rotate(10deg); }
        75% { transform: translate(-14px, 8px) rotate(-12deg); }
    }
    @keyframes dominoShuffleCenter {
        0%, 100% { transform: translate(0, 0) rotate(0deg) scale(1); }
        30% { transform: translate(0, -14px) rotate(8deg) scale(1.08); }
        60% { transform: translate(0, 10px) rotate(-8deg) scale(0.96); }
    }
    @keyframes dominoShuffleRight {
        0%, 100% { transform: translate(0, 0) rotate(6deg); }
        25% { transform: translate(26px, 8px) rotate(18deg); }
        50% { transform: translate(-18px, -8px) rotate(-10deg); }
        75% { transform: translate(14px, -10px) rotate(12deg); }
    }
    @keyframes dominoDealIn {
        0% { opacity: 0; transform: translateY(-22px) scale(0.72) rotate(-6deg); }
        70% { opacity: 1; transform: translateY(3px) scale(1.04) rotate(1deg); }
        100% { opacity: 1; transform: translateY(0) scale(1) rotate(0deg); }
    }
    @keyframes dominoPlaceCenter {
        0% { opacity: 0; transform: scale(1.45) translateY(-18px); box-shadow: 0 18px 30px rgba(0,0,0,0.45); }
        70% { opacity: 1; transform: scale(0.96) translateY(2px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes dominoPlaceLeft {
        0% { opacity: 0; transform: translateX(-28px) scale(1.28); box-shadow: 0 14px 24px rgba(0,0,0,0.4); }
        70% { opacity: 1; transform: translateX(3px) scale(0.98); }
        100% { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes dominoPlaceRight {
        0% { opacity: 0; transform: translateX(28px) scale(1.28); box-shadow: 0 14px 24px rgba(0,0,0,0.4); }
        70% { opacity: 1; transform: translateX(-3px) scale(0.98); }
        100% { opacity: 1; transform: translateX(0) scale(1); }
    }

    .anim-shuffle-1 { animation: dominoShuffleLeft 0.55s ease-in-out infinite; }
    .anim-shuffle-2 { animation: dominoShuffleCenter 0.55s ease-in-out infinite; }
    .anim-shuffle-3 { animation: dominoShuffleRight 0.55s ease-in-out infinite; }
    .anim-deal-in { animation: dominoDealIn 0.36s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .anim-place-center { animation: dominoPlaceCenter 0.38s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .anim-place-left { animation: dominoPlaceLeft 0.36s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .anim-place-right { animation: dominoPlaceRight 0.36s cubic-bezier(0.22, 1, 0.36, 1) both; }

    /* Mode Fullscreen: memperluas area meja agar maksimal mengisi layar */
    #gameFullscreenWrapper:fullscreen,
    #gameFullscreenWrapper.is-pseudo-fullscreen {
        display: flex;
        flex-direction: column;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        padding: 0.75rem;
        gap: 0.5rem;
        background-color: #f1f5f9;
        overflow: hidden;
    }
    #gameFullscreenWrapper.is-pseudo-fullscreen {
        position: fixed;
        inset: 0;
        z-index: 60;
    }
    html.dark #gameFullscreenWrapper:fullscreen,
    html.dark #gameFullscreenWrapper.is-pseudo-fullscreen {
        background-color: #020617;
    }
    #gameFullscreenWrapper:fullscreen .fs-hide,
    #gameFullscreenWrapper.is-pseudo-fullscreen .fs-hide {
        display: none !important;
    }
    #gameFullscreenWrapper:fullscreen .fs-compact-mb,
    #gameFullscreenWrapper.is-pseudo-fullscreen .fs-compact-mb {
        margin-bottom: 0 !important;
    }
    #gameFullscreenWrapper:fullscreen #gameTableCard,
    #gameFullscreenWrapper.is-pseudo-fullscreen #gameTableCard {
        flex: 1 1 0%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        margin-bottom: 0 !important;
    }
    #gameFullscreenWrapper:fullscreen #boardViewport,
    #gameFullscreenWrapper.is-pseudo-fullscreen #boardViewport {
        flex: 1 1 0%;
        min-height: 0;
        overflow-y: auto;
    }
    #gameFullscreenWrapper:fullscreen #playerHandCard,
    #gameFullscreenWrapper.is-pseudo-fullscreen #playerHandCard {
        padding: 0.75rem 1rem !important;
        flex-shrink: 0;
    }
</style>

<div id="gameFullscreenWrapper" class="transition-all">
    <!-- Header -->
    <div class="fs-compact-mb mb-4 sm:mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 sm:gap-4 shrink-0">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="bi bi-dice-6-fill text-indigo-600"></i>
                    <span>Game Dam Batu</span>
                </h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    vs AI
                </span>
            </div>
            <p class="fs-hide text-xs sm:text-sm text-slate-500 mt-1">Hiburan domino Dam Batu 3–4 pemain dengan 4 kartu sisa tertutup dan 3 tingkat kecerdasan AI.</p>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <button type="button" id="btnToggleFullscreen"
                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs cursor-pointer touch-manipulation"
                title="Perluas Meja (Layar Penuh)">
                <i class="bi bi-arrows-fullscreen"></i> <span>Perluas Meja</span>
            </button>
            <button type="button" id="btnToggleMute"
                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs cursor-pointer touch-manipulation">
                <i class="bi bi-volume-up-fill"></i> <span class="hidden sm:inline">Suara</span>
            </button>
            <button type="button" id="btnOpenRules"
                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs cursor-pointer touch-manipulation">
                <i class="bi bi-book"></i> <span class="hidden sm:inline">Aturan</span>
            </button>
            <button type="button" id="btnStartGame"
                class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-xs shadow-emerald-600/20 cursor-pointer touch-manipulation">
                <i class="bi bi-play-fill text-sm"></i> Start Game
            </button>
            <button type="button" id="btnRestartGame"
                class="hidden items-center gap-1.5 px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer touch-manipulation">
                <i class="bi bi-arrow-clockwise"></i> Restart
            </button>
        </div>
    </div>

    <!-- Bar Pengaturan & Statistik Lokal (disembunyikan saat Fullscreen agar meja jauh lebih luas) -->
    <div class="fs-hide bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-xs mb-4 sm:mb-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3.5">
            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 sm:gap-4">
                <!-- Mode Meja -->
                <div>
                    <div class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Jumlah Pemain</div>
                    <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-1.5">
                        <button type="button" data-ai-count="2">3 Orang (2 AI)</button>
                        <button type="button" data-ai-count="3">4 Orang (3 AI)</button>
                    </div>
                </div>

                <!-- Level Kesulitan -->
                <div>
                    <div class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Tingkat Kesulitan AI</div>
                    <div class="grid grid-cols-3 sm:flex sm:flex-wrap items-center gap-1.5">
                        <button type="button" data-difficulty="easy">Santai</button>
                        <button type="button" data-difficulty="normal">Normal</button>
                        <button type="button" data-difficulty="hard">Sulit</button>
                    </div>
                </div>
            </div>

            <!-- Statistik Lokal -->
            <div class="grid grid-cols-3 sm:flex items-center gap-2 sm:gap-3 pt-2.5 lg:pt-0 border-t lg:border-t-0 lg:border-l border-slate-100 lg:pl-5">
                <div class="text-center px-2">
                    <div class="text-[10px] uppercase font-semibold text-slate-400">Menang</div>
                    <div id="statWin" class="text-sm sm:text-base font-black text-emerald-600 font-mono">0</div>
                </div>
                <div class="text-center px-2 border-l border-slate-100">
                    <div class="text-[10px] uppercase font-semibold text-slate-400">Kalah</div>
                    <div id="statLose" class="text-sm sm:text-base font-black text-rose-500 font-mono">0</div>
                </div>
                <div class="text-center px-2 border-l border-slate-100">
                    <div class="text-[10px] uppercase font-semibold text-slate-400">Win Rate</div>
                    <div id="statRate" class="text-sm sm:text-base font-black text-indigo-600 font-mono">0%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barisan Lawan AI (tetap 1 baris ringkas di mobile) -->
    <div id="opponentsRow" class="fs-compact-mb grid grid-cols-3 gap-2 sm:gap-3 mb-4 shrink-0"></div>

    <!-- Meja Permainan (Board) -->
    <div id="gameTableCard" class="rounded-2xl sm:rounded-3xl overflow-hidden border-2 border-emerald-900/30 shadow-md mb-4 sm:mb-5 relative" style="background: radial-gradient(circle at center, #065f46 0%, #064e3b 100%);">
        <!-- Info Strip Atas Meja -->
        <div class="px-3 sm:px-5 py-2 sm:py-2.5 bg-black/25 border-b border-white/10 flex flex-wrap items-center justify-between gap-2 text-[11px] sm:text-xs text-white shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                <span id="turnIndicator" class="font-bold tracking-wide truncate">Siap Bermain — Klik Start Game</span>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2 font-mono shrink-0">
                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-white/10 border border-white/15">
                    Kiri: <strong id="boardLeftEnd" class="text-amber-300">-</strong>
                </span>
                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-white/10 border border-white/15">
                    Kanan: <strong id="boardRightEnd" class="text-amber-300">-</strong>
                </span>
                <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-black/30 border border-white/10 text-emerald-200" title="4 kartu rahasia tidak dibagikan dan tidak diambil">
                    <i class="bi bi-lock-fill text-[10px]"></i> 4 Tertutup
                </span>
            </div>
        </div>

        <!-- Area Rangkaian Batu Domino -->
        <div id="boardViewport" class="relative min-h-[195px] sm:min-h-[250px] flex items-center justify-center">
            <div id="dominoBoard" class="p-3 sm:p-6 w-full flex flex-wrap items-center justify-center gap-1.5 sm:gap-2"></div>

            <!-- Overlay Start Game (sebelum permainan dimulai) -->
            <div id="startOverlay" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-emerald-950/60 backdrop-blur-[2px] p-4 sm:p-6 text-center">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/15 border border-white/20 text-amber-300 flex items-center justify-center text-2xl sm:text-3xl mb-2.5 shadow-lg">
                    <i class="bi bi-dice-6-fill"></i>
                </div>
                <h2 class="text-base sm:text-xl font-black text-white tracking-tight">Siap Bermain Dam Batu?</h2>
                <p id="startSummaryText" class="text-[11px] sm:text-sm text-emerald-100/90 mt-1 mb-4 max-w-md">
                    Mode 4 Orang (3 AI &middot; 6 Kartu) &middot; Level Normal &middot; 4 Kartu Sisa Tertutup
                </p>
                <button type="button" id="btnStartOverlay"
                    class="inline-flex items-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 rounded-2xl text-xs sm:text-sm font-extrabold text-slate-900 bg-amber-400 hover:bg-amber-300 shadow-lg shadow-black/25 transition-all hover:scale-105 cursor-pointer touch-manipulation">
                    <i class="bi bi-play-fill text-base sm:text-lg"></i> Mulai Permainan (Start)
                </button>
            </div>

            <!-- Overlay Animasi Kocok Kartu -->
            <div id="shuffleOverlay" class="hidden absolute inset-0 z-20 flex-col items-center justify-center bg-emerald-950/80 backdrop-blur-xs p-4 sm:p-6 text-center">
                <div class="relative flex items-center justify-center gap-3 h-20 sm:h-24 mb-2.5">
                    <div class="anim-shuffle-1 w-9 h-14 sm:w-10 sm:h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 border-2 border-indigo-200/80 shadow-xl flex items-center justify-center text-white/80">
                        <i class="bi bi-grid-3x3-gap-fill text-base sm:text-lg"></i>
                    </div>
                    <div class="anim-shuffle-2 w-9 h-14 sm:w-10 sm:h-16 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-700 border-2 border-white/80 shadow-xl flex items-center justify-center text-white">
                        <i class="bi bi-dice-6-fill text-base sm:text-lg"></i>
                    </div>
                    <div class="anim-shuffle-3 w-9 h-14 sm:w-10 sm:h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 border-2 border-indigo-200/80 shadow-xl flex items-center justify-center text-white/80">
                        <i class="bi bi-grid-3x3-gap-fill text-base sm:text-lg"></i>
                    </div>
                </div>
                <div class="text-xs sm:text-sm font-bold text-white tracking-wide">Mengocok &amp; Membagikan 28 Batu...</div>
                <div class="text-[11px] sm:text-xs text-emerald-200/80 mt-0.5">Menyisakan 4 batu tertutup di meja</div>
            </div>
        </div>

        <!-- Bar Log Langkah Terakhir -->
        <div class="px-3 sm:px-5 py-1.5 sm:py-2 bg-black/25 border-t border-white/10 text-[11px] sm:text-xs text-emerald-100/90 flex items-center gap-2 shrink-0">
            <i class="bi bi-clock-history text-emerald-300 shrink-0"></i>
            <span id="lastMoveLog" class="truncate">Pilih jumlah pemain &amp; tingkat kesulitan, lalu klik Start Game.</span>
        </div>
    </div>

    <!-- Area Tangan Pemain -->
    <div id="playerHandCard" class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="bi bi-person-fill"></i>
                </span>
                <div>
                    <h2 class="text-xs sm:text-sm font-bold text-slate-900">Batu Anda</h2>
                    <p id="playerCardCount" class="text-[11px] sm:text-xs text-slate-400 font-mono">Belum dimulai</p>
                </div>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" id="btnPlayerPass"
                    class="hidden w-full sm:w-auto items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 shadow-sm transition-colors cursor-pointer touch-manipulation animate-bounce">
                    <i class="bi bi-skip-forward-fill"></i> Tidak Ada Batu Cocok — Lewat (Pass)
                </button>
            </div>
        </div>

        <!-- Pemilih Sisi Kiri / Kanan (tampil jika batu cocok di kedua ujung) -->
        <div id="sidePickerBar" class="hidden flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 p-2.5 sm:p-3 mb-3 rounded-2xl bg-indigo-50 border border-indigo-200">
            <span class="text-xs font-semibold text-indigo-900 text-center sm:mr-1">Pilih posisi penempatan batu:</span>
            <div class="grid grid-cols-2 sm:flex items-center gap-2">
                <button type="button" id="btnPlaceLeft"
                    class="px-3.5 py-2 sm:py-1.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors cursor-pointer touch-manipulation">
                    Taruh Ujung Kiri
                </button>
                <button type="button" id="btnPlaceRight"
                    class="px-3.5 py-2 sm:py-1.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors cursor-pointer touch-manipulation">
                    Taruh Ujung Kanan
                </button>
            </div>
            <button type="button" id="btnCancelSide"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 transition-colors cursor-pointer touch-manipulation">
                Batal
            </button>
        </div>

        <!-- Daftar Batu di Tangan Pemain -->
        <div id="playerHand" class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 py-1.5 min-h-[100px] sm:min-h-[115px]">
            <div class="text-xs text-slate-400 py-4 text-center">Klik <strong>Start Game</strong> untuk mengocok dan membagikan batu.</div>
        </div>
    </div>

    <!-- Modal Hasil Akhir Ronde (di dalam wrapper agar tetap tampil saat Fullscreen) -->
    <div id="roundEndModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs hidden items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl p-5 sm:p-8 max-w-lg w-full shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
            <div class="text-center mb-4 sm:mb-5">
                <div id="roundResultIcon" class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl bg-emerald-100 text-emerald-600">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <h3 id="roundResultTitle" class="text-lg sm:text-xl font-black text-slate-900">Ronde Selesai</h3>
                <p id="roundResultSubtitle" class="text-xs text-slate-500 mt-1"></p>
            </div>

            <div id="roundPlayersSummary" class="space-y-2.5 mb-5"></div>

            <!-- 4 Kartu Sisa Tertutup yang Dibuka -->
            <div class="p-3.5 rounded-2xl bg-slate-100/80 border border-slate-200 mb-5">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                    <i class="bi bi-unlock-fill text-indigo-500"></i> 4 Kartu Sisa Tertutup (Rahasia)
                </div>
                <div id="roundHiddenTiles" class="flex flex-wrap items-center gap-1.5"></div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" id="btnCloseRoundModal"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer touch-manipulation">
                    Lihat Meja
                </button>
                <button type="button" id="btnPlayAgain"
                    class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer touch-manipulation flex items-center gap-1.5">
                    <i class="bi bi-play-fill"></i> Main Lagi
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Aturan Main -->
    <div id="rulesModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs hidden items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl p-5 sm:p-8 max-w-lg w-full shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-book text-indigo-500"></i> Aturan Game Dam Batu
                </h3>
                <button type="button" id="btnCloseRules" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="space-y-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                <p><strong>1. Pembagian Batu (28 Batu):</strong></p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li><strong>Mode 3 Orang (1 vs 2 AI):</strong> Masing-masing mendapat <strong>8 batu</strong>, tersisa <strong>4 batu tertutup</strong>.</li>
                    <li><strong>Mode 4 Orang (1 vs 3 AI):</strong> Masing-masing mendapat <strong>6 batu</strong>, tersisa <strong>4 batu tertutup</strong>.</li>
                </ul>
                <p><strong>2. Jalan Pertama:</strong> Dimulai oleh pemain yang memegang <strong>balak tertinggi</strong> (<code>6|6</code>, atau turun ke <code>5|5</code>, dst. jika tertutup).</p>
                <p><strong>3. Lewat (Pass):</strong> Jika tidak ada batu yang cocok dengan ujung kiri maupun kanan meja, giliran langsung <strong>Lewat</strong> (tidak mengambil dari 4 kartu sisa tertutup).</p>
                <p><strong>4. Pemenang Ronde:</strong> Pemain yang batunya habis duluan (<strong>Dam!</strong>), atau jika semua pemain lewat (<strong>Meja Buntu</strong>), pemenangnya adalah yang memiliki <strong>total titik sisa paling sedikit</strong>.</p>
                <p><strong>5. Tingkat Kesulitan AI:</strong></p>
                <ul class="list-disc list-inside space-y-1 pl-1">
                    <li><strong>Santai:</strong> AI memilih langkah secara acak.</li>
                    <li><strong>Normal:</strong> AI membuang balak/angka besar lebih awal dan mengarahkan ke angka yang banyak dimilikinya.</li>
                    <li><strong>Sulit (Pro):</strong> AI menghitung batu yang keluar, membaca pola <em>pass</em> lawan, dan berusaha mengunci langkah lawan.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/dam-batu-game.js') }}?v={{ filemtime(public_path('assets/js/dam-batu-game.js')) }}"></script>
@endsection
