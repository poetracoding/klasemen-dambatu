@extends('layouts.app')

@section('title', 'Game Ludo Queen')

@section('content')
<script>window.DISABLE_LIVE_UPDATE = true;</script>

<style>
    #ludoFullscreenWrapper:fullscreen,
    #ludoFullscreenWrapper.is-pseudo-fullscreen {
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        padding: 0.75rem;
        background-color: #f1f5f9;
        overflow: auto;
    }
    #ludoFullscreenWrapper.is-pseudo-fullscreen {
        position: fixed;
        inset: 0;
        z-index: 60;
    }
    html.dark #ludoFullscreenWrapper:fullscreen,
    html.dark #ludoFullscreenWrapper.is-pseudo-fullscreen {
        background-color: #020617;
    }

    @keyframes ludoDieTumble {
        0%   { transform: rotate(0deg) scale(1); }
        25%  { transform: translateY(-4px) rotate(-95deg) scale(1.12); }
        50%  { transform: translateY(2px) rotate(-185deg) scale(0.95); }
        75%  { transform: translateY(-4px) rotate(-275deg) scale(1.1); }
        100% { transform: rotate(-360deg) scale(1); }
    }
    .ludo-die-rolling {
        animation: ludoDieTumble 0.36s linear infinite;
    }

    /* 3D Tabletop Stage & Camera */
    #ludoBoardStage {
        position: relative;
        overflow: visible;
        padding: 1.35rem 0.75rem 2.1rem;
        border-radius: 1.25rem;
        transition: background 0.35s ease;
        background: radial-gradient(circle at 50% 35%, #1e1b4b 0%, #0f172a 68%, #020617 100%);
    }
    #ludoBoardStage[data-felt="emerald"] {
        background: radial-gradient(circle at 50% 35%, #064e3b 0%, #022c22 68%, #011712 100%);
    }
    #ludoBoardStage[data-felt="crimson"] {
        background: radial-gradient(circle at 50% 35%, #581c3a 0%, #270b28 65%, #090d16 100%);
    }

    #ludoBoard3dSurface {
        position: relative;
        width: 100%;
        max-width: 590px;
        margin-left: auto;
        margin-right: auto;
        border-radius: 1.15rem;
        padding: 7px;
        border: 3px solid #d97706;
        background: linear-gradient(145deg, #92400e 0%, #78350f 45%, #451a03 100%);
        transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.45s ease;
        transform-origin: center center;
    }
    #ludoBoardCard[data-camera="3d"] #ludoBoard3dSurface {
        transform: perspective(1350px) rotateX(14deg) scale(var(--ludo-zoom, 1));
        box-shadow: 0 13px 0 #451a03, 0 17px 0 #290f02, 0 28px 46px rgba(2, 6, 23, 0.75);
    }
    #ludoBoardCard[data-camera="cinematic"] #ludoBoard3dSurface {
        transform: perspective(1220px) rotateX(21deg) rotateZ(-2deg) scale(var(--ludo-zoom, 1));
        box-shadow: 5px 16px 0 #451a03, 7px 21px 0 #290f02, 0 34px 58px rgba(2, 6, 23, 0.82);
    }
    #ludoBoardCard[data-camera="2d"] #ludoBoard3dSurface {
        transform: perspective(1400px) rotateX(0deg) scale(var(--ludo-zoom, 1));
        box-shadow: 0 5px 0 #451a03, 0 16px 32px rgba(2, 6, 23, 0.55);
    }

    /* Center-of-Board 3D Dice Overlay */
    .ludo-center-dice-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 25;
        transition: opacity 0.24s ease, transform 0.24s ease;
    }
    .ludo-center-dice-overlay.is-hidden {
        opacity: 0;
        transform: scale(0.84);
        pointer-events: none !important;
    }
    .ludo-center-dice-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        padding: 1rem 1.25rem;
        border-radius: 1.5rem;
        background: radial-gradient(circle at 50% 35%, rgba(15, 23, 42, 0.88), rgba(2, 6, 23, 0.94));
        border: 2px solid rgba(250, 204, 21, 0.55);
        box-shadow: 0 18px 45px rgba(2, 6, 23, 0.75), 0 0 28px var(--ludo-player-glow, rgba(99, 102, 241, 0.45));
        pointer-events: none;
        transition: transform 0.2s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
    .ludo-center-dice-card.is-clickable {
        pointer-events: auto;
        cursor: pointer;
    }
    .ludo-center-dice-card.is-clickable:hover {
        transform: translateY(-2px) scale(1.04);
        border-color: rgba(253, 224, 71, 0.95);
    }
    .ludo-center-dice-card.is-clickable:active {
        transform: scale(0.97);
    }

    .ludo-3d-die-stage {
        position: relative;
        width: 96px;
        height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
        perspective: 680px;
    }
    .ludo-3d-die-shadow {
        position: absolute;
        bottom: 2px;
        width: 64px;
        height: 16px;
        border-radius: 9999px;
        background: radial-gradient(ellipse at center, rgba(2, 6, 23, 0.78) 0%, rgba(2, 6, 23, 0) 72%);
        transform-origin: center center;
        transition: transform 0.25s ease, opacity 0.25s ease;
    }
    .ludo-3d-die-mover {
        width: 72px;
        height: 72px;
        transform-style: preserve-3d;
        will-change: transform;
    }
    .ludo-3d-iso-tilt {
        width: 100%;
        height: 100%;
        transform-style: preserve-3d;
        transform: rotateX(-11deg) rotateY(14deg);
        transition: transform 0.28s ease;
    }
    .ludo-3d-die {
        width: 72px;
        height: 72px;
        position: relative;
        transform-style: preserve-3d;
        transition: transform 0.38s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .ludo-cube-face {
        position: absolute;
        inset: 0;
        border-radius: 15px;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(3, 1fr);
        padding: 10px;
        gap: 3px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 58%, #cbd5e1 100%);
        border: 1.5px solid rgba(255, 255, 255, 0.95);
        box-shadow: inset 0 2px 5px rgba(255, 255, 255, 0.9), inset 0 -4px 8px rgba(100, 116, 139, 0.38);
        backface-visibility: hidden;
    }
    .ludo-cube-face--1 { transform: rotateY(0deg) translateZ(36px); }
    .ludo-cube-face--6 { transform: rotateY(180deg) translateZ(36px); }
    .ludo-cube-face--3 { transform: rotateY(90deg) translateZ(36px); }
    .ludo-cube-face--4 { transform: rotateY(-90deg) translateZ(36px); }
    .ludo-cube-face--2 { transform: rotateX(90deg) translateZ(36px); }
    .ludo-cube-face--5 { transform: rotateX(-90deg) translateZ(36px); }

    .ludo-3d-pip {
        width: 12px;
        height: 12px;
        border-radius: 9999px;
        margin: auto;
        opacity: 0;
        background: radial-gradient(circle at 35% 30%, #475569, #0f172a 75%);
        box-shadow: inset 0 1.5px 2px rgba(0, 0, 0, 0.7), 0 1px 1px rgba(255, 255, 255, 0.85);
    }
    .ludo-3d-pip.is-on {
        opacity: 1;
    }
    .ludo-3d-pip.is-red {
        background: radial-gradient(circle at 35% 30%, #f87171, #b91c1c 75%);
    }
    .ludo-3d-pip.is-big {
        width: 17px;
        height: 17px;
    }

    @keyframes ludoDieFloatIdle {
        0%, 100% { transform: translateY(-2px); }
        50%      { transform: translateY(-8px); }
    }
    @keyframes ludoDieThrowArc {
        0%   { transform: translateY(42px) scale(0.72); }
        28%  { transform: translateY(-38px) scale(1.24); }
        56%  { transform: translateY(4px) scale(1.04); }
        76%  { transform: translateY(-16px) scale(1.12); }
        92%  { transform: translateY(2px) scale(1.0); }
        100% { transform: translateY(-2px) scale(1.04); }
    }
    @keyframes ludoDieShadowBounce {
        0%   { transform: scale(0.75); opacity: 0.45; }
        28%  { transform: scale(0.42); opacity: 0.25; }
        56%  { transform: scale(1.12); opacity: 0.85; }
        76%  { transform: scale(0.65); opacity: 0.45; }
        100% { transform: scale(1.0); opacity: 0.75; }
    }
    @keyframes ludoDieLandPop {
        0%   { transform: scale(1.26) translateY(-10px); }
        45%  { transform: scale(0.95) translateY(3px); }
        100% { transform: scale(1.06) translateY(0); }
    }
    .ludo-3d-die-mover.is-idle {
        animation: ludoDieFloatIdle 2s ease-in-out infinite;
    }
    .ludo-3d-die-mover.is-rolling {
        animation: ludoDieThrowArc 1.2s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
    }
    .ludo-3d-die-shadow.is-rolling {
        animation: ludoDieShadowBounce 1.2s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
    }
    .ludo-3d-die-mover.is-landed {
        animation: ludoDieLandPop 0.38s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
</style>

<div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/70 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-1.5">
                <i class="bi bi-controller"></i>
                <span>Arena Hiburan Publik &middot; Liga Dam Batu</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-fuchsia-600 to-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0">
                    <i class="bi bi-dice-5-fill text-lg"></i>
                </span>
                <span>Game Ludo Queen</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Duel papan klasik 15&times;15 melawan 1–3 AI dengan zona bintang aman, tangkap pion lawan, 3 level kecerdasan, dan efek suara.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="btnLudoOpenRules"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-book text-indigo-500"></i>
                <span>Aturan Main</span>
            </button>
            <button type="button" id="btnLudoMute"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-volume-up-fill text-emerald-500" id="ludoMuteIcon"></i>
                <span id="ludoMuteLabel">Suara Aktif</span>
            </button>
            <button type="button" id="btnLudoFullscreen"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-arrows-fullscreen text-indigo-500"></i>
                <span>Perluas Meja</span>
            </button>
        </div>
    </div>

    <!-- Panel Konfigurasi Setup -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3.5 items-end">
            <!-- Jumlah Pemain -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                    <i class="bi bi-people-fill text-indigo-500 mr-1"></i>Jumlah Pemain
                </label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" data-ludo-players="2" class="ludo-opt-players px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">2 Orang<br><span class="text-[10px] opacity-80">1 AI</span></button>
                    <button type="button" data-ludo-players="3" class="ludo-opt-players px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">3 Orang<br><span class="text-[10px] opacity-80">2 AI</span></button>
                    <button type="button" data-ludo-players="4" class="ludo-opt-players px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">4 Orang<br><span class="text-[10px] opacity-80">3 AI</span></button>
                </div>
            </div>

            <!-- Level Kecerdasan AI -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                    <i class="bi bi-cpu-fill text-amber-500 mr-1"></i>Level Pertandingan
                </label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" data-ludo-diff="easy" class="ludo-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">Santai</button>
                    <button type="button" data-ludo-diff="normal" class="ludo-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">Normal</button>
                    <button type="button" data-ludo-diff="hard" class="ludo-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">Sulit</button>
                </div>
            </div>

            <!-- Pilihan Warna & Mode Menang -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="ludoColorSelect" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        <i class="bi bi-palette-fill text-rose-500 mr-1"></i>Warna Anda
                    </label>
                    <select id="ludoColorSelect"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="blue">🔵 Biru</option>
                        <option value="red">🔴 Merah</option>
                        <option value="green">🟢 Hijau</option>
                        <option value="yellow">🟡 Kuning</option>
                    </select>
                </div>
                <div>
                    <label for="ludoWinModeSelect" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        <i class="bi bi-trophy-fill text-emerald-500 mr-1"></i>Target Menang
                    </label>
                    <select id="ludoWinModeSelect"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="classic">Klasik (4 Pion)</option>
                        <option value="quick2">Cepat (2 Pion)</option>
                    </select>
                </div>
            </div>

            <!-- Tombol Mulai / Restart -->
            <div class="flex items-center gap-2">
                <button type="button" id="btnLudoRestart"
                    class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors cursor-pointer">
                    <i class="bi bi-arrow-repeat text-sm"></i>
                    <span>Mulai Baru / Reset</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Arena Permainan -->
    <div id="ludoFullscreenWrapper" class="space-y-4 game-touch-arena">
        @include('partials.game_fs_exit', ['btnId' => 'btnLudoFullscreen'])
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <!-- Papan Ludo 15x15 (3D Arena) -->
            <div class="lg:col-span-7 xl:col-span-8">
                <div id="ludoBoardCard" data-camera="3d" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-xs space-y-3">
                    <!-- Bilah Kendali 3D Kamera, Alas & Zoom -->
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" id="btnLudoCycleCamera"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/70 font-bold hover:bg-indigo-100 dark:hover:bg-indigo-900/70 transition-colors cursor-pointer">
                                <i class="bi bi-badge-3d-fill text-sm"></i>
                                <span id="ludoCameraLabel">3D Meja</span>
                            </button>
                            <div class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 px-1">Alas:</span>
                                <button type="button" data-ludo-felt="royal" class="ludo-felt-btn w-5 h-5 rounded-md bg-indigo-900 border border-white/60 cursor-pointer" title="Royal Navy"></button>
                                <button type="button" data-ludo-felt="emerald" class="ludo-felt-btn w-5 h-5 rounded-md bg-emerald-800 border border-white/60 cursor-pointer" title="Zamrud"></button>
                                <button type="button" data-ludo-felt="crimson" class="ludo-felt-btn w-5 h-5 rounded-md bg-rose-900 border border-white/60 cursor-pointer" title="Krimson"></button>
                            </div>
                        </div>

                        <div class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                            <button type="button" id="btnLudoZoomOut" class="px-2 py-0.5 rounded font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 cursor-pointer" title="Perkecil">−</button>
                            <button type="button" id="btnLudoZoomReset" class="px-2 py-0.5 rounded text-[11px] font-mono font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 cursor-pointer" title="Reset Zoom">100%</button>
                            <button type="button" id="btnLudoZoomIn" class="px-2 py-0.5 rounded font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 cursor-pointer" title="Perbesar">+</button>
                        </div>
                    </div>

                    <!-- Bilah Kendali Giliran & Dadu -->
                    <div id="ludoQuickBar"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 px-3 py-2.5">
                    </div>

                    <!-- Panggung Meja 3D & Kanvas Papan 15x15 -->
                    <div id="ludoBoardStage" data-felt="royal" class="border border-slate-700/80 shadow-inner">
                        <div id="ludoBoard3dSurface">
                            <canvas id="ludoCanvas" width="660" height="660"
                                class="w-full block rounded-lg shadow-xl cursor-pointer touch-manipulation select-none"></canvas>

                            <!-- Dadu 3D Tengah Papan Permainan -->
                            <div id="ludoCenterDiceOverlay" class="ludo-center-dice-overlay">
                                <div id="ludoCenterDiceCard" class="ludo-center-dice-card is-clickable" title="Klik untuk melempar dadu 3D">
                                    <div id="ludoCenterDiceTurnChip" class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold text-white bg-indigo-600 shadow-xs">
                                        Giliran Anda
                                    </div>

                                    <div class="ludo-3d-die-stage">
                                        <div id="ludoCenter3dShadow" class="ludo-3d-die-shadow"></div>
                                        <div id="ludoCenter3dMover" class="ludo-3d-die-mover is-idle">
                                            <div class="ludo-3d-iso-tilt">
                                                <div id="ludoCenter3dDie" class="ludo-3d-die">
                                                    @php
                                                        $cubeFaces = [
                                                            1 => [4],
                                                            2 => [0, 8],
                                                            3 => [0, 4, 8],
                                                            4 => [0, 2, 6, 8],
                                                            5 => [0, 2, 4, 6, 8],
                                                            6 => [0, 2, 3, 5, 6, 8],
                                                        ];
                                                    @endphp
                                                    @foreach($cubeFaces as $faceNum => $pips)
                                                        <div class="ludo-cube-face ludo-cube-face--{{ $faceNum }}">
                                                            @for($idx = 0; $idx < 9; $idx++)
                                                                @php
                                                                    $isOn = in_array($idx, $pips, true);
                                                                    $isRed = $faceNum === 1 || $faceNum === 6;
                                                                    $isBig = $faceNum === 1 && $idx === 4;
                                                                @endphp
                                                                <span class="ludo-3d-pip {{ $isOn ? 'is-on' : '' }} {{ ($isOn && $isRed) ? 'is-red' : '' }} {{ ($isOn && $isBig) ? 'is-big' : '' }}"></span>
                                                            @endfor
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="ludoCenterDiceBadge" class="px-3 py-1 rounded-full bg-amber-400 text-slate-950 text-[11px] font-black tracking-wide shadow-sm">
                                        🎲 KLIK / SPASI LEMPAR DADU
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Kanan: Pemain, Statistik & Log -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                <!-- Status Pemain & Pion -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-crown-fill text-amber-500"></i>
                            <span>Status Kerajaan &amp; Pion</span>
                        </h2>
                        <span id="ludoTurnBadge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                            Siap Bermain
                        </span>
                    </div>
                    <div id="ludoPlayersPanel" class="space-y-2"></div>
                </div>

                <!-- Rekor Kemenangan Lokal -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-bar-chart-fill text-emerald-500"></i>
                            <span>Rekor Kemenangan Anda</span>
                        </h2>
                        <button type="button" id="btnLudoResetStats" class="text-[11px] font-semibold text-rose-500 hover:underline cursor-pointer">
                            Reset Rekor
                        </button>
                    </div>
                    <div id="ludoStatsPanel" class="grid grid-cols-3 gap-2 text-center"></div>
                </div>

                <!-- Log Aktivitas -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-2.5">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-journal-text text-indigo-500"></i>
                        <span>Log Langkah Terbaru</span>
                    </h2>
                    <div id="ludoActivityLog" class="max-h-52 overflow-y-auto space-y-1.5 text-[11px] text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-200/70 dark:border-slate-700/60"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pemenang -->
<div id="ludoWinModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-xs p-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 bg-gradient-to-r from-fuchsia-600 to-indigo-600 text-white flex items-center justify-between">
            <h3 class="text-base font-extrabold flex items-center gap-2">
                <i class="bi bi-trophy-fill text-amber-300"></i>
                <span>Ratu Ludo Terpilih!</span>
            </h3>
            <button type="button" id="btnCloseLudoWin" class="text-white/80 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="ludoWinBody" class="p-5 space-y-4"></div>
    </div>
</div>

<!-- Modal Aturan Main -->
<div id="ludoRulesModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-xs p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[88vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-book-fill text-indigo-500"></i>
                <span>Aturan Bermain Ludo Queen</span>
            </h3>
            <button type="button" id="btnCloseLudoRules" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div class="p-4 sm:p-5 overflow-y-auto space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
            <ul class="list-disc list-inside space-y-1.5">
                <li><strong>Keluar Kandang:</strong> Lempar dadu angka <strong>6</strong> untuk mengeluarkan pion dari kandang awal ke titik START masing-masing.</li>
                <li><strong>Bonus Giliran:</strong> Mendapat angka <strong>6</strong>, berhasil <strong>menangkap pion lawan</strong>, atau memasukkan pion ke <strong>Mahkota Tengah (👑)</strong> memberi hadiah 1 kali lempar dadu ulang.</li>
                <li><strong>Aturan 3× Angka 6:</strong> Jika melempar angka 6 tiga kali berturut-turut, giliran ke-3 langsung hangus.</li>
                <li><strong>Zona Aman (★):</strong> Terdapat 8 petak bintang aman di papan (4 titik START &amp; 4 bintang tengah sisi). Pion di atas bintang aman tidak dapat ditangkap lawan.</li>
                <li><strong>Menangkap Lawan:</strong> Mendarat tepat di petak biasa yang ditempati pion lawan akan mengembalikan pion lawan tersebut ke kandang awalnya!</li>
                <li><strong>Masuk Mahkota Tengah:</strong> Pion membutuhkan angka dadu yang <strong>pas</strong> untuk mendarat tepat di segitiga Mahkota Tengah (langkah ke-56).</li>
            </ul>
        </div>
    </div>
</div>
@include('partials.game_ux_enhancements')
@endsection

@section('scripts')
<script src="{{ asset('assets/js/ludo-queen-game.js') }}?v={{ filemtime(public_path('assets/js/ludo-queen-game.js')) }}"></script>
@endsection
