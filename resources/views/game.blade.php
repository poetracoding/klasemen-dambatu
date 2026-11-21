@extends('layouts.app')

@section('title', 'Game Dam Batu')

@section('content')
<script>window.DISABLE_LIVE_UPDATE = true;</script>
<link href="{{ asset('assets/css/dam-batu-3d.css') }}?v={{ filemtime(public_path('assets/css/dam-batu-3d.css')) }}" rel="stylesheet">

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
        0% { opacity: 0; transform: translateY(-24px) rotateX(22deg) scale(0.76); }
        70% { opacity: 1; transform: translateY(2px) rotateX(-3deg) scale(1.03); }
        100% { opacity: 1; transform: translateY(0) rotateX(0deg) scale(1); }
    }
    @keyframes dominoPlaceCenter {
        0% { opacity: 0; transform: translate3d(0, -28px, 42px) scale(1.36); }
        70% { opacity: 1; transform: translate3d(0, 2px, 0) scale(0.96); }
        100% { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
    }
    @keyframes dominoPlaceLeft {
        0% { opacity: 0; transform: translate3d(-28px, -18px, 32px) scale(1.26); }
        70% { opacity: 1; transform: translate3d(3px, 1px, 0) scale(0.98); }
        100% { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
    }
    @keyframes dominoPlaceRight {
        0% { opacity: 0; transform: translate3d(28px, -18px, 32px) scale(1.26); }
        70% { opacity: 1; transform: translate3d(-3px, 1px, 0) scale(0.98); }
        100% { opacity: 1; transform: translate3d(0, 0, 0) scale(1); }
    }

    .anim-shuffle-1 { animation: dominoShuffleLeft 0.55s ease-in-out infinite; }
    .anim-shuffle-2 { animation: dominoShuffleCenter 0.55s ease-in-out infinite; }
    .anim-shuffle-3 { animation: dominoShuffleRight 0.55s ease-in-out infinite; }
    .anim-deal-in { animation: dominoDealIn 0.38s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .anim-place-center { animation: dominoPlaceCenter 0.42s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .anim-place-left { animation: dominoPlaceLeft 0.38s cubic-bezier(0.22, 1, 0.36, 1) both; }
    .anim-place-right { animation: dominoPlaceRight 0.38s cubic-bezier(0.22, 1, 0.36, 1) both; }

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
    <div id="victoryConfetti" aria-hidden="true"></div>

    <!-- Header -->
    <div class="fs-compact-mb mb-4 sm:mb-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2.5 sm:gap-4 shrink-0">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center shadow-sm shadow-indigo-600/25">
                        <i class="bi bi-dice-6-fill text-base"></i>
                    </span>
                    <span>Game Dam Batu</span>
                </h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                    vs AI &middot; 3D
                </span>
            </div>
            <p class="fs-hide text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Meja 3D domino Dam Batu 3–4 pemain dengan 4 kartu sisa tertutup dan 3 tingkat kecerdasan AI.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <button type="button" id="btnCycleCamera"
                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors shadow-xs cursor-pointer touch-manipulation"
                title="Ganti Sudut Kamera Meja (3D / Sinematik / 2D)">
                <i class="bi bi-badge-3d-fill"></i> <span id="cameraLabel">3D Standar</span>
            </button>
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

    <!-- Bar Pengaturan, Tema Meja & Statistik Lokal -->
    <div class="fs-hide bg-white dark:bg-slate-900 rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 dark:border-slate-800 shadow-xs mb-4 sm:mb-5">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3.5">
            <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3 sm:gap-5">
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

                <!-- Tema Meja 3D -->
                <div>
                    <div class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Tema Meja</div>
                    <div class="grid grid-cols-3 sm:flex sm:flex-wrap items-center gap-1.5">
                        <button type="button" data-felt-theme="emerald"
                            class="inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 ring-1 ring-white/60"></span>
                            <span>Hijau</span>
                        </button>
                        <button type="button" data-felt-theme="royal"
                            class="inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 ring-1 ring-white/60"></span>
                            <span>Biru</span>
                        </button>
                        <button type="button" data-felt-theme="classic"
                            class="inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-700 ring-1 ring-white/60"></span>
                            <span>Kayu</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistik Lokal -->
            <div class="grid grid-cols-3 sm:flex items-center gap-2 sm:gap-3 pt-2.5 xl:pt-0 border-t xl:border-t-0 xl:border-l border-slate-100 dark:border-slate-800 xl:pl-5">
                <div class="text-center px-2">
                    <div class="text-[10px] uppercase font-semibold text-slate-400">Menang</div>
                    <div id="statWin" class="text-sm sm:text-base font-black text-emerald-600 font-mono">0</div>
                </div>
                <div class="text-center px-2 border-l border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] uppercase font-semibold text-slate-400">Kalah</div>
                    <div id="statLose" class="text-sm sm:text-base font-black text-rose-500 font-mono">0</div>
                </div>
                <div class="text-center px-2 border-l border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] uppercase font-semibold text-slate-400">Win Rate</div>
                    <div id="statRate" class="text-sm sm:text-base font-black text-indigo-600 font-mono">0%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barisan Lawan AI -->
    <div id="opponentsRow" class="fs-compact-mb grid grid-cols-3 gap-2 sm:gap-3 mb-4 shrink-0"></div>

    <!-- Meja Permainan 3D -->
    <div id="gameTableCard" data-felt="emerald" data-camera="3d" class="rounded-2xl sm:rounded-3xl overflow-hidden mb-4 sm:mb-5">
        <!-- Info Strip Atas Meja -->
        <div class="px-3 sm:px-5 py-2 sm:py-2.5 bg-black/35 backdrop-blur-xs border-b border-white/10 flex flex-wrap items-center justify-between gap-2 text-[11px] sm:text-xs text-white shrink-0 relative z-20">
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                <span id="turnIndicator" class="font-bold tracking-wide truncate">Siap Bermain — Klik Start Game</span>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 font-mono shrink-0">
                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-white/10 border border-white/15">
                    Kiri: <strong id="boardLeftEnd" class="text-amber-300">-</strong>
                </span>
                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg bg-white/10 border border-white/15">
                    Kanan: <strong id="boardRightEnd" class="text-amber-300">-</strong>
                </span>

                <!-- Kontrol Zoom Meja -->
                <div class="hidden sm:inline-flex items-center bg-black/35 rounded-lg p-0.5 border border-white/15">
                    <button type="button" id="btnZoomOut" class="px-1.5 py-0.5 text-slate-200 hover:text-white hover:bg-white/10 rounded cursor-pointer" title="Perkecil Meja">
                        <i class="bi bi-zoom-out"></i>
                    </button>
                    <button type="button" id="btnZoomReset" class="px-1.5 py-0.5 text-[10px] font-bold text-amber-200 hover:text-white cursor-pointer" title="Reset Zoom (100%)">
                        100%
                    </button>
                    <button type="button" id="btnZoomIn" class="px-1.5 py-0.5 text-slate-200 hover:text-white hover:bg-white/10 rounded cursor-pointer" title="Perbesar Meja">
                        <i class="bi bi-zoom-in"></i>
                    </button>
                </div>

                <!-- Tombol Toggle Riwayat Langkah -->
                <button type="button" id="btnToggleHistory"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-white/10 hover:bg-white/20 border border-white/15 text-[11px] font-sans font-semibold text-emerald-100 transition-colors cursor-pointer"
                    title="Lihat Riwayat Langkah">
                    <i class="bi bi-list-ul"></i> <span class="hidden sm:inline">Riwayat</span>
                </button>
            </div>
        </div>

        <!-- Stage & Area Rangkaian Batu Domino 3D -->
        <div id="boardViewport" class="relative min-h-[240px] sm:min-h-[310px] flex items-center justify-center px-2 sm:px-4 py-4">
            <div class="table-felt-watermark">LIGA DAM BATU</div>

            <!-- Tumpukan 4 Kartu Sisa Tertutup 3D -->
            <div id="hiddenDeckVisual" class="hidden sm:flex items-center gap-2.5 absolute bottom-3 right-4 z-10 px-2.5 py-2 rounded-xl bg-black/35 border border-white/10 backdrop-blur-xs pointer-events-none"
                title="4 batu rahasia tidak dibagikan dan tidak diambil">
                <div class="hidden-deck-3d" aria-hidden="true">
                    <div class="hidden-stone-layer"></div>
                    <div class="hidden-stone-layer"></div>
                    <div class="hidden-stone-layer"></div>
                    <div class="hidden-stone-layer"><i class="bi bi-lock-fill"></i></div>
                </div>
                <div class="text-[10px] font-semibold text-emerald-100/90 leading-tight">
                    <div class="font-bold text-amber-300">4 Tertutup</div>
                    <div class="text-[9px] text-white/60">Batu Rahasia</div>
                </div>
            </div>

            <!-- Panggung Perspektif 3D -->
            <div id="table3dStage">
                <div id="table3dSurface">
                    <div id="dominoBoard" class="domino-snake-canvas"></div>
                </div>
            </div>

            <!-- Overlay Start Game (sebelum permainan dimulai) -->
            <div id="startOverlay" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/50 backdrop-blur-[2px] p-4 sm:p-6 text-center">
                <div class="flex items-center justify-center gap-2 mb-3" aria-hidden="true">
                    <div class="domino-3d domino-3d--board w-14 h-7 flex-row -rotate-6 p-0.5 text-[10px] font-black text-slate-800 items-center justify-around">
                        <span>6</span><span class="w-px h-full bg-slate-300"></span><span>6</span>
                    </div>
                    <div class="domino-3d domino-3d--board w-14 h-7 flex-row rotate-6 p-0.5 text-[10px] font-black text-indigo-700 items-center justify-around">
                        <span>5</span><span class="w-px h-full bg-slate-300"></span><span>3</span>
                    </div>
                </div>
                <h2 class="text-base sm:text-xl font-black text-white tracking-tight">Siap Bermain Dam Batu 3D?</h2>
                <p id="startSummaryText" class="text-[11px] sm:text-sm text-emerald-100/90 mt-1 mb-4 max-w-md">
                    Mode 4 Orang (3 AI &middot; 6 Kartu) &middot; Level Normal &middot; 4 Kartu Sisa Tertutup
                </p>
                <button type="button" id="btnStartOverlay"
                    class="inline-flex items-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 rounded-2xl text-xs sm:text-sm font-extrabold text-slate-900 bg-amber-400 hover:bg-amber-300 shadow-lg shadow-black/35 transition-all hover:scale-105 cursor-pointer touch-manipulation">
                    <i class="bi bi-play-fill text-base sm:text-lg"></i> Mulai Permainan (Start)
                </button>
            </div>

            <!-- Overlay Animasi Kocok Kartu -->
            <div id="shuffleOverlay" class="hidden absolute inset-0 z-20 flex-col items-center justify-center bg-black/75 backdrop-blur-xs p-4 sm:p-6 text-center">
                <div class="relative flex items-center justify-center gap-3 h-20 sm:h-24 mb-2.5">
                    <div class="anim-shuffle-1 w-10 h-16 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-800 border-2 border-amber-300/70 shadow-2xl flex items-center justify-center text-amber-300">
                        <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                    </div>
                    <div class="anim-shuffle-2 w-10 h-16 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-800 border-2 border-white/80 shadow-2xl flex items-center justify-center text-white">
                        <i class="bi bi-dice-6-fill text-lg"></i>
                    </div>
                    <div class="anim-shuffle-3 w-10 h-16 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-800 border-2 border-amber-300/70 shadow-2xl flex items-center justify-center text-amber-300">
                        <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                    </div>
                </div>
                <div class="text-xs sm:text-sm font-bold text-white tracking-wide">Mengocok &amp; Membagikan 28 Batu 3D...</div>
                <div class="text-[11px] sm:text-xs text-emerald-200/80 mt-0.5">Menyisakan 4 batu tertutup di sudut meja</div>
            </div>
        </div>

        <!-- Panel Riwayat Langkah (collapsible) -->
        <div id="moveHistoryPanel" class="hidden px-3 sm:px-5 py-2.5 bg-black/50 border-t border-white/10 text-[11px] text-slate-200 max-h-32 overflow-y-auto relative z-20">
            <div class="text-[10px] font-bold uppercase tracking-wider text-amber-300 mb-1">Riwayat Langkah Terbaru</div>
            <ul id="moveHistoryList" class="space-y-0.5 font-mono">
                <li class="text-slate-400">Belum ada langkah dimainkan.</li>
            </ul>
        </div>

        <!-- Bar Log Langkah Terakhir -->
        <div class="px-3 sm:px-5 py-1.5 sm:py-2 bg-black/35 border-t border-white/10 text-[11px] sm:text-xs text-emerald-100/90 flex items-center justify-between gap-2 shrink-0 relative z-20">
            <div class="flex items-center gap-2 min-w-0">
                <i class="bi bi-clock-history text-emerald-300 shrink-0"></i>
                <span id="lastMoveLog" class="truncate">Pilih jumlah pemain &amp; tingkat kesulitan, lalu klik Start Game.</span>
            </div>
            <span class="sm:hidden text-[10px] font-mono text-amber-300 shrink-0">4 Tertutup</span>
        </div>
    </div>

    <!-- Area Tangan Pemain (Rak Kayu 3D) -->
    <div id="playerHandCard" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-sm">
                    <i class="bi bi-person-fill"></i>
                </span>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">Batu Anda</h2>
                        <span id="playableHintBadge" class="hidden px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"></span>
                    </div>
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
        <div id="sidePickerBar" class="hidden flex-col sm:flex-row items-stretch sm:items-center justify-center gap-2 p-2.5 sm:p-3 mb-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800">
            <span class="text-xs font-semibold text-indigo-900 dark:text-indigo-200 text-center sm:mr-1">Pilih posisi penempatan batu (atau klik langsung di ujung meja):</span>
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
                class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer touch-manipulation">
                Batal
            </button>
        </div>

        <!-- Daftar Batu di Rak Kayu Pemain -->
        <div class="domino-rack-tray">
            <div id="playerHand" class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-3.5 py-1.5 min-h-[112px] sm:min-h-[128px]">
                <div class="text-xs text-slate-500 dark:text-slate-400 py-4 text-center">Klik <strong>Start Game</strong> untuk mengocok dan membagikan batu.</div>
            </div>
        </div>
    </div>

    <!-- Modal Hasil Akhir Ronde -->
    <div id="roundEndModal" class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs hidden items-center justify-center p-3 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-8 max-w-lg w-full shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
            <div class="text-center mb-4 sm:mb-5">
                <div id="roundResultIcon" class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl bg-emerald-100 text-emerald-600">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <h3 id="roundResultTitle" class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">Ronde Selesai</h3>
                <p id="roundResultSubtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1"></p>
            </div>

            <div id="roundPlayersSummary" class="space-y-2.5 mb-5"></div>

            <!-- 4 Kartu Sisa Tertutup yang Dibuka -->
            <div class="p-3.5 rounded-2xl bg-slate-100/80 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 mb-5">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-300 mb-2.5 flex items-center gap-1.5">
                    <i class="bi bi-unlock-fill text-indigo-500"></i> 4 Kartu Sisa Tertutup (Rahasia)
                </div>
                <div id="roundHiddenTiles" class="flex flex-wrap items-center gap-2"></div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" id="btnCloseRoundModal"
                    class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer touch-manipulation">
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
    <div id="rulesModal" class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-xs hidden items-center justify-center p-3 sm:p-4">
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 sm:p-8 max-w-lg w-full shadow-2xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="bi bi-book text-indigo-500"></i> Aturan Game Dam Batu
                </h3>
                <button type="button" id="btnCloseRules" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
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
