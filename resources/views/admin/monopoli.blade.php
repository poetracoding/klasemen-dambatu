@extends('layouts.app')

@section('title', 'Game Monopoli Nusantara')

@section('content')
<script>window.DISABLE_LIVE_UPDATE = true;</script>
<link href="{{ asset('assets/css/monopoli-3d.css') }}?v={{ filemtime(public_path('assets/css/monopoli-3d.css')) }}" rel="stylesheet">

<div class="space-y-4">
    <!-- Header & Quick Info -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/70 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-1.5">
                <i class="bi bi-controller"></i>
                <span>Arena Hiburan Publik &middot; Lawan AI</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white flex items-center justify-center shadow-sm shrink-0">
                    <i class="bi bi-buildings-fill text-lg"></i>
                </span>
                <span>Monopoli Nusantara</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Jelajahi 22 kota, 4 transportasi, &amp; BUMN Indonesia dengan mata uang Rupiah dan 3 level kecerdasan AI.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="btnMonoOpenRules"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-book text-indigo-500"></i>
                <span>Aturan Main</span>
            </button>
            <button type="button" id="btnMonoToggleMute"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-volume-up-fill text-emerald-500" id="monoMuteIcon"></i>
                <span id="monoMuteLabel">Suara Aktif</span>
            </button>
            <button type="button" id="btnMonoFullscreen"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-arrows-fullscreen text-indigo-500"></i>
                <span>Perluas Meja</span>
            </button>
        </div>
    </div>

    <!-- Setup & Lobby Configuration Panel -->
    <div id="monoSetupPanel" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- Jumlah Pemain -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                    <i class="bi bi-people-fill text-indigo-500 mr-1"></i>Jumlah Pemain Meja
                </label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" id="btnMonoPlayers2" data-mono-players="2"
                        class="mono-opt-players px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                        2 Orang<br><span class="text-[10px] opacity-80">1 AI</span>
                    </button>
                    <button type="button" id="btnMonoPlayers3" data-mono-players="3"
                        class="mono-opt-players px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                        3 Orang<br><span class="text-[10px] opacity-80">2 AI</span>
                    </button>
                    <button type="button" id="btnMonoPlayers4" data-mono-players="4"
                        class="mono-opt-players px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                        4 Orang<br><span class="text-[10px] opacity-80">3 AI</span>
                    </button>
                </div>
            </div>

            <!-- Level Pertandingan -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                    <i class="bi bi-cpu-fill text-amber-500 mr-1"></i>Level Pertandingan AI
                </label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" id="btnMonoDiffEasy" data-mono-diff="easy"
                        class="mono-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                        Santai<br><span class="text-[10px] opacity-80">Pemula</span>
                    </button>
                    <button type="button" id="btnMonoDiffNormal" data-mono-diff="normal"
                        class="mono-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                        Normal<br><span class="text-[10px] opacity-80">Kompetitif</span>
                    </button>
                    <button type="button" id="btnMonoDiffHard" data-mono-diff="hard"
                        class="mono-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer">
                        Sulit<br><span class="text-[10px] opacity-80">Juragan</span>
                    </button>
                </div>
            </div>

            <!-- Mode Menang & Pion -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="monoWinModeSelect" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        <i class="bi bi-trophy-fill text-emerald-500 mr-1"></i>Mode Menang
                    </label>
                    <select id="monoWinModeSelect"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="classic">Klasik (Eliminasi)</option>
                        <option value="rounds_20">20 Putaran (Cepat)</option>
                        <option value="rounds_30">30 Putaran (Sedang)</option>
                        <option value="rounds_40">40 Putaran (Panjang)</option>
                    </select>
                </div>
                <div>
                    <label for="monoTokenSelect" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        <i class="bi bi-person-badge-fill text-rose-500 mr-1"></i>Pion Anda
                    </label>
                    <select id="monoTokenSelect"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="🏯">🏯 Monas</option>
                        <option value="🦅">🦅 Garuda</option>
                        <option value="🎭">🎭 Wayang</option>
                        <option value="⛵">⛵ Pinisi</option>
                        <option value="☕">☕ Kopi Nusantara</option>
                        <option value="🛺">🛺 Becak</option>
                    </select>
                </div>
            </div>

            <!-- Tombol Mulai & Opsi Lelang -->
            <div class="flex flex-col justify-between gap-2">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer select-none">
                    <input type="checkbox" id="monoAuctionToggle" checked class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Aktifkan Lelang Cepat properti ditolak</span>
                </label>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btnMonoStartGame"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors cursor-pointer">
                        <i class="bi bi-play-fill text-sm"></i>
                        <span>Mulai Pertandingan</span>
                    </button>
                    <button type="button" id="btnMonoResumeSaved"
                        class="hidden inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition-colors cursor-pointer"
                        title="Lanjutkan pertandingan tersimpan di browser">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Lanjutkan</span>
                    </button>
                    <button type="button" id="btnMonoRestartGame"
                        class="hidden inline-flex items-center justify-center gap-1 px-3 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm transition-colors cursor-pointer"
                        title="Mulai ulang pertandingan">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Restart</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Arena Utama (Papan + Panel Samping) -->
    <div id="monopoliFullscreenWrapper" class="space-y-4 game-touch-arena">
        @include('partials.game_fs_exit', ['btnId' => 'btnMonoFullscreen'])
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <!-- Papan 11x11 (7/12 - 8/12 Desktop) -->
            <div class="lg:col-span-7 xl:col-span-8">
                <div id="monopoliBoardCard" data-camera="3d" data-felt="emerald"
                    class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-xs space-y-3">
                    <!-- Toolbar Meja (Kamera, Felt, Zoom) -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200/70 dark:border-slate-800">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" id="btnMonoCycleCamera"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                                <i class="bi bi-camera-video-fill text-indigo-500"></i>
                                <span id="monoCameraLabel">3D Meja</span>
                            </button>
                            <div class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-800 rounded-lg p-0.5">
                                <button type="button" data-mono-felt="emerald" class="mono-felt-btn px-2 py-1 rounded-md text-[11px] font-semibold cursor-pointer">Hijau</button>
                                <button type="button" data-mono-felt="royal" class="mono-felt-btn px-2 py-1 rounded-md text-[11px] font-semibold cursor-pointer">Biru</button>
                                <button type="button" data-mono-felt="nusantara" class="mono-felt-btn px-2 py-1 rounded-md text-[11px] font-semibold cursor-pointer">Merah</button>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <div class="inline-flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-0.5">
                                <button type="button" id="btnMonoZoomOut" class="px-2 py-1 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 rounded-md cursor-pointer" title="Perkecil Papan">-</button>
                                <button type="button" id="btnMonoZoomReset" class="px-2 py-1 text-[11px] font-mono font-bold text-slate-700 dark:text-slate-200 cursor-pointer" title="Reset Zoom">100%</button>
                                <button type="button" id="btnMonoZoomIn" class="px-2 py-1 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 rounded-md cursor-pointer" title="Perbesar Papan">+</button>
                            </div>
                            <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-200 text-[11px] font-semibold">
                                <i class="bi bi-info-circle"></i> PPh Petak 4: 10% Kekayaan
                            </span>
                        </div>
                    </div>

                    <!-- Panel Kendali Cepat 2D (Selalu Siap Klik) -->
                    <div id="monoQuickActionBar"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 px-3 py-2.5">
                    </div>

                    <!-- Viewport Papan 11x11 -->
                    <div id="monopoliBoardViewport">
                        <div id="monopoliBoardStage">
                            <div id="monopoliBoardSurface">
                                <div id="monopoliBoardGrid" class="monopoli-board-grid"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Kanan: Pemain, Portofolio SHM, Barter & Statistik -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                <!-- Daftar Pemain & Saldo -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-wallet2 text-emerald-500"></i>
                            <span>Pemain &amp; Kekayaan (Rp)</span>
                        </h2>
                        <span id="monoRoundBadge" class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                            Siap Bermain
                        </span>
                    </div>
                    <div id="monoPlayersList" class="space-y-2"></div>
                </div>

                <!-- Manajemen Portofolio & Barter -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-house-door-fill text-amber-500"></i>
                            <span>Portofolio Properti (SHM)</span>
                        </h2>
                        <button type="button" id="btnMonoOpenTrade"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-[11px] font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Barter AI</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/70 rounded-xl px-3 py-1.5">
                        <span>Stok Bank:</span>
                        <div class="flex items-center gap-3 font-mono font-bold text-slate-700 dark:text-slate-200">
                            <span id="monoBankHouses">🏠 32 Rumah</span>
                            <span id="monoBankHotels">🏨 12 Hotel</span>
                        </div>
                    </div>

                    <div id="monoPortfolioTabs" class="flex flex-wrap gap-1"></div>
                    <div id="monoPortfolioList" class="max-h-64 overflow-y-auto space-y-1.5 pr-1 text-xs"></div>
                </div>

                <!-- Log Aktivitas & Statistik Lokal -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-journal-text text-indigo-500"></i>
                            <span>Log Pertandingan &amp; Rekor</span>
                        </h2>
                        <button type="button" id="btnMonoResetStats" class="text-[11px] font-semibold text-rose-500 hover:underline cursor-pointer">
                            Reset Rekor
                        </button>
                    </div>
                    <div id="monoStatsSummary" class="grid grid-cols-3 gap-2 text-center"></div>
                    <div id="monoActivityLog" class="max-h-44 overflow-y-auto space-y-1.5 text-[11px] text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-200/70 dark:border-slate-700/60"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: Detail Sertifikat Hak Milik (SHM) -->
<div id="monoShmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Sertifikat Hak Milik (SHM)</h3>
            <button type="button" id="btnCloseShmModal" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div id="monoShmBody" class="p-4 space-y-3"></div>
    </div>
</div>

<!-- MODAL 2: Lelang Cepat (Auction) -->
<div id="monoAuctionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-xs p-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white">
            <h3 class="text-sm font-extrabold flex items-center gap-2">
                <i class="bi bi-hammer"></i>
                <span>Lelang Cepat Properti</span>
            </h3>
            <span class="text-[11px] font-bold bg-black/20 px-2 py-0.5 rounded-full">Standar Monopoli</span>
        </div>
        <div id="monoAuctionBody" class="p-4 space-y-3"></div>
    </div>
</div>

<!-- MODAL 3: Barter / Perdagangan (Trade) -->
<div id="monoTradeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-xs p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-arrow-left-right text-amber-500"></i>
                <span>Barter Properti &amp; Kas dengan AI</span>
            </h3>
            <button type="button" id="btnCloseTradeModal" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div id="monoTradeBody" class="p-4 overflow-y-auto space-y-4"></div>
    </div>
</div>

<!-- MODAL 4: Kartu Dana Umum / Kesempatan -->
<div id="monoCardModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4">
    <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden text-center">
        <div id="monoCardHeader" class="px-4 py-3 text-white font-extrabold text-sm">KARTU</div>
        <div class="p-5 space-y-4">
            <div id="monoCardIcon" class="text-4xl">🎁</div>
            <h4 id="monoCardTitle" class="text-base font-extrabold text-slate-900 dark:text-white"></h4>
            <p id="monoCardDesc" class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed"></p>
            <button type="button" id="btnConfirmCardModal"
                class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-colors cursor-pointer">
                Jalankan &amp; Lanjut
            </button>
        </div>
    </div>
</div>

<!-- MODAL 5: Akhir Pertandingan -->
<div id="monoEndModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-xs p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 bg-gradient-to-r from-indigo-600 to-emerald-600 text-white flex items-center justify-between">
            <h3 class="text-base font-extrabold flex items-center gap-2">
                <i class="bi bi-trophy-fill text-amber-300"></i>
                <span>Pertandingan Selesai!</span>
            </h3>
            <button type="button" id="btnCloseEndModal" class="text-white/80 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="monoEndBody" class="p-5 space-y-4"></div>
    </div>
</div>

<!-- MODAL 6: Aturan Monopoli Nusantara -->
<div id="monoRulesModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-xs p-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[88vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-book-fill text-indigo-500"></i>
                <span>Aturan Resmi Monopoli Nusantara</span>
            </h3>
            <button type="button" id="btnCloseRulesModal" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div class="p-4 sm:p-5 overflow-y-auto space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
            <div class="rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/60 p-3 text-indigo-900 dark:text-indigo-200">
                <strong>Ekonomi Rupiah Standar:</strong> Modal awal <strong>Rp 15.000.000</strong>, gaji melewati <strong>START Rp 2.000.000</strong>, tebusan penjara <strong>Rp 500.000</strong>, Pajak Barang Mewah (Petak 38) <strong>Rp 1.000.000</strong>, dan Pajak Penghasilan / PPh (Petak 4) sebesar <strong>10% dari Total Kekayaan Bersih</strong>.
            </div>
            <ul class="list-disc list-inside space-y-1.5">
                <li><strong>Dadu Kembar (Doubles):</strong> Angka dadu kembar memberi bonus lempar ulang. Namun <strong>3 kali kembar berturut-turut</strong> otomatis masuk ke <strong>Penjara (LP Nusakambangan)</strong> tanpa melewati START.</li>
                <li><strong>Beli atau Lelang Cepat:</strong> Mendarat di properti kosong dapat langsung dibeli atau ditolak untuk masuk <strong>Lelang Cepat</strong> antar seluruh pemain (jika lelang diaktifkan).</li>
                <li><strong>Monopoli Warna &amp; Bangun Merata:</strong> Menguasai semua kota sewarna menggandakan sewa tanah kosong (<strong>2×</strong>) dan membuka izin bangun 1–4 Rumah hingga Hotel. Pembangunan &amp; penjualan wajib merata (selisih tingkat antar kota sewarna maks 1 tingkat), dengan stok Bank maksimal <strong>32 Rumah &amp; 12 Hotel</strong>.</li>
                <li><strong>Hipotek &amp; Tebus:</strong> Properti tanpa bangunan di kelompok warnanya dapat dihipotekkan untuk mendapat kas <strong>50% harga beli</strong>, dan ditebus kembali dengan biaya <strong>55% harga beli</strong> (+10% bunga).</li>
                <li><strong>Penjara:</strong> Tetap dapat menerima sewa selama di Penjara. Keluar dengan membayar <strong>Rp 500.000</strong>, memakai <strong>Kartu Bebas Penjara</strong>, atau melempar dadu kembar maksimal 3 giliran (gagal ke-3 wajib bayar Rp 500.000).</li>
                <li><strong>Level AI (Santai / Normal / Sulit):</strong> Mengatur agresivitas AI saat lelang (*Color Blocking* pada level Sulit), manajemen kas, pembangunan 3–4 rumah, evaluasi barter, hingga taktik bertahan di penjara saat papan padat hotel.</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('assets/js/monopoli-game.js') }}?v={{ filemtime(public_path('assets/js/monopoli-game.js')) }}"></script>
@endsection
