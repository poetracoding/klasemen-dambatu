@extends('layouts.app')

@section('title', 'Game Catur 3D')

@section('content')
<script>window.DISABLE_LIVE_UPDATE = true;</script>

<style>
    #chessFullscreenWrapper:fullscreen,
    #chessFullscreenWrapper.is-pseudo-fullscreen {
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        padding: 0.75rem;
        background-color: #f1f5f9;
        overflow: auto;
    }
    #chessFullscreenWrapper.is-pseudo-fullscreen {
        position: fixed;
        inset: 0;
        z-index: 60;
    }
    html.dark #chessFullscreenWrapper:fullscreen,
    html.dark #chessFullscreenWrapper.is-pseudo-fullscreen {
        background-color: #020617;
    }

    /* 3D Tabletop Stage & Camera */
    #chessBoardStage {
        position: relative;
        overflow: visible;
        padding: 1.25rem 0.75rem 2rem;
        border-radius: 1.25rem;
        transition: background 0.35s ease;
        background: radial-gradient(circle at 50% 35%, #451a03 0%, #1c1008 68%, #090502 100%);
    }
    #chessBoardStage[data-theme="royal"] {
        background: radial-gradient(circle at 50% 35%, #1e1b4b 0%, #0f172a 68%, #020617 100%);
    }
    #chessBoardStage[data-theme="emerald"] {
        background: radial-gradient(circle at 50% 35%, #064e3b 0%, #022c22 68%, #011712 100%);
    }

    #chessBoard3dSurface {
        position: relative;
        width: 100%;
        max-width: 580px;
        margin-left: auto;
        margin-right: auto;
        border-radius: 1.1rem;
        padding: 8px;
        border: 3px solid #d97706;
        background: linear-gradient(145deg, #92400e 0%, #78350f 45%, #451a03 100%);
        transition: transform 0.45s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.45s ease;
        transform-origin: center center;
    }
    #chessBoardCard[data-camera="3d"] #chessBoard3dSurface {
        transform: perspective(1350px) rotateX(14deg) scale(var(--chess-zoom, 1));
        box-shadow: 0 13px 0 #451a03, 0 17px 0 #290f02, 0 28px 46px rgba(2, 6, 23, 0.78);
    }
    #chessBoardCard[data-camera="cinematic"] #chessBoard3dSurface {
        transform: perspective(1220px) rotateX(21deg) rotateZ(-2deg) scale(var(--chess-zoom, 1));
        box-shadow: 5px 16px 0 #451a03, 7px 21px 0 #290f02, 0 34px 58px rgba(2, 6, 23, 0.85);
    }
    #chessBoardCard[data-camera="2d"] #chessBoard3dSurface {
        transform: perspective(1400px) rotateX(0deg) scale(var(--chess-zoom, 1));
        box-shadow: 0 5px 0 #451a03, 0 16px 32px rgba(2, 6, 23, 0.55);
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
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0 text-lg">
                    ♔
                </span>
                <span>Game Catur 3D</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Duel catur meja 3D melawan AI 3 level (Santai, Normal, Sulit) lengkap dengan aturan FIDE, Rokade, En Passant, Promosi Pion, dan Jam Catur.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="btnChessOpenRules"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-book text-indigo-500"></i>
                <span>Aturan Main</span>
            </button>
            <button type="button" id="btnChessMute"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-volume-up-fill text-emerald-500" id="chessMuteIcon"></i>
                <span id="chessMuteLabel">Suara Aktif</span>
            </button>
            <button type="button" id="btnChessFullscreen"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-arrows-fullscreen text-indigo-500"></i>
                <span>Perluas Meja</span>
            </button>
        </div>
    </div>

    <!-- Panel Konfigurasi Setup -->
    <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3.5 items-end">
            <!-- Level Kecerdasan AI -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                    <i class="bi bi-cpu-fill text-amber-500 mr-1"></i>Level Kesulitan AI
                </label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" data-chess-diff="easy" class="chess-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">Santai</button>
                    <button type="button" data-chess-diff="normal" class="chess-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">Normal</button>
                    <button type="button" data-chess-diff="hard" class="chess-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer">Sulit</button>
                </div>
            </div>

            <!-- Warna & Jam Catur -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="chessColorSelect" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        <i class="bi bi-circle-half text-indigo-500 mr-1"></i>Bidak Anda
                    </label>
                    <select id="chessColorSelect"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="w">♔ Putih (Duluan)</option>
                        <option value="b">♚ Hitam (Kedua)</option>
                    </select>
                </div>
                <div>
                    <label for="chessTimeSelect" class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        <i class="bi bi-stopwatch-fill text-emerald-500 mr-1"></i>Jam Catur
                    </label>
                    <select id="chessTimeSelect"
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="0">Tanpa Waktu</option>
                        <option value="600">Cepat (10 Menit)</option>
                        <option value="300">Blitz (5 Menit)</option>
                        <option value="180">Blitz (3 Menit)</option>
                    </select>
                </div>
            </div>

            <!-- Aksi Undo & Saran -->
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="btnChessUndo"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Undo Langkah</span>
                </button>
                <button type="button" id="btnChessHint"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl text-xs font-bold bg-amber-50 dark:bg-amber-950/50 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/70 transition-colors cursor-pointer">
                    <i class="bi bi-lightbulb-fill"></i>
                    <span>Saran</span>
                </button>
            </div>

            <!-- Mulai Baru -->
            <div>
                <button type="button" id="btnChessRestart"
                    class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-colors cursor-pointer">
                    <i class="bi bi-arrow-repeat text-sm"></i>
                    <span>Mulai Pertandingan Baru</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Arena Permainan -->
    <div id="chessFullscreenWrapper" class="space-y-4 game-touch-arena">
        @include('partials.game_fs_exit', ['btnId' => 'btnChessFullscreen'])
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <!-- Papan Catur 3D -->
            <div class="lg:col-span-7 xl:col-span-8">
                <div id="chessBoardCard" data-camera="3d" class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-xs space-y-3">
                    <!-- Bilah Kendali 3D Kamera, Putar Papan, Tema & Zoom -->
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" id="btnChessCycleCamera"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/70 font-bold hover:bg-indigo-100 dark:hover:bg-indigo-900/70 transition-colors cursor-pointer">
                                <i class="bi bi-badge-3d-fill text-sm"></i>
                                <span id="chessCameraLabel">3D Meja</span>
                            </button>
                            <button type="button" id="btnChessFlipBoard"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-semibold hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                                <i class="bi bi-arrow-down-up"></i>
                                <span>Putar Papan</span>
                            </button>
                            <div class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 px-1">Papan:</span>
                                <button type="button" data-chess-theme="wood" class="w-5 h-5 rounded-md bg-amber-800 border border-white/60 cursor-pointer" title="Kayu Jati"></button>
                                <button type="button" data-chess-theme="royal" class="w-5 h-5 rounded-md bg-indigo-900 border border-white/60 cursor-pointer" title="Marmer Royal"></button>
                                <button type="button" data-chess-theme="emerald" class="w-5 h-5 rounded-md bg-emerald-800 border border-white/60 cursor-pointer" title="Zamrud Turnamen"></button>
                            </div>
                        </div>

                        <div class="inline-flex items-center gap-1 rounded-lg bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                            <button type="button" id="btnChessZoomOut" class="px-2 py-0.5 rounded font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 cursor-pointer" title="Perkecil">−</button>
                            <button type="button" id="btnChessZoomReset" class="px-2 py-0.5 rounded text-[11px] font-mono font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 cursor-pointer" title="Reset Zoom">100%</button>
                            <button type="button" id="btnChessZoomIn" class="px-2 py-0.5 rounded font-bold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 cursor-pointer" title="Perbesar">+</button>
                        </div>
                    </div>

                    <!-- Bilah Pemain Atas (AI) -->
                    <div id="chessTopPlayerBar" class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 px-3 py-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span id="chessTopAvatar" class="w-7 h-7 rounded-lg bg-slate-800 text-white flex items-center justify-center text-sm font-black shrink-0">♚</span>
                            <div class="min-w-0">
                                <div id="chessTopName" class="text-xs font-extrabold text-slate-800 dark:text-slate-100 truncate">AI Lawan (Normal)</div>
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                    <span id="chessTopCaptured" class="text-base sm:text-lg leading-none tracking-tight"></span>
                                    <span id="chessTopAdvantage" class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400"></span>
                                </div>
                            </div>
                        </div>
                        <div id="chessTopClock" class="px-2.5 py-1 rounded-lg bg-slate-200/80 dark:bg-slate-900 font-mono text-xs font-extrabold text-slate-800 dark:text-slate-200">
                            10:00
                        </div>
                    </div>

                    <!-- Panggung Meja Catur 3D -->
                    <div id="chessBoardStage" data-theme="wood" class="border border-slate-700/80 shadow-inner">
                        <div id="chessBoard3dSurface">
                            <canvas id="chessCanvas" width="640" height="640"
                                class="w-full block rounded-lg shadow-xl cursor-pointer touch-manipulation select-none"></canvas>

                            <!-- Overlay Pemilihan Promosi Pion -->
                            <div id="chessPromoOverlay" class="hidden absolute inset-0 z-30 items-center justify-center bg-slate-950/75 rounded-lg p-4">
                                <div class="bg-white dark:bg-slate-900 border border-amber-400/70 rounded-2xl p-4 shadow-2xl text-center space-y-3 max-w-xs w-full">
                                    <div class="text-sm font-extrabold text-slate-900 dark:text-white">Promosi Pion!</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">Pilih bidak pengganti pion Anda:</div>
                                    <div id="chessPromoOptions" class="grid grid-cols-4 gap-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bilah Pemain Bawah (Anda) -->
                    <div id="chessBottomPlayerBar" class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 px-3 py-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span id="chessBottomAvatar" class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm font-black shrink-0">♔</span>
                            <div class="min-w-0">
                                <div id="chessBottomName" class="text-xs font-extrabold text-slate-800 dark:text-slate-100 truncate">Anda (Putih)</div>
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                                    <span id="chessBottomCaptured" class="text-base sm:text-lg leading-none tracking-tight"></span>
                                    <span id="chessBottomAdvantage" class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400"></span>
                                </div>
                            </div>
                        </div>
                        <div id="chessBottomClock" class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/70 font-mono text-xs font-extrabold text-indigo-800 dark:text-indigo-200">
                            10:00
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel Kanan: Status, Evaluasi, Rekor & Notasi -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                <!-- Status Giliran & Bar Evaluasi -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-activity text-indigo-500"></i>
                            <span>Status Pertandingan</span>
                        </h2>
                        <span id="chessTurnBadge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                            Giliran Putih
                        </span>
                    </div>

                    <div id="chessStatusBanner" class="rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                        Klik bidak Anda untuk melihat langkah legal.
                    </div>

                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            <span>Evaluasi Posisi (Putih vs Hitam)</span>
                            <span id="chessEvalLabel" class="font-mono font-bold text-slate-800 dark:text-slate-100">Seimbang (0.0)</span>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-slate-800 overflow-hidden border border-slate-300 dark:border-slate-700">
                            <div id="chessEvalFill" class="h-full bg-gradient-to-r from-white to-slate-200 transition-all duration-300" style="width: 50%"></div>
                        </div>
                    </div>
                </div>

                <!-- Rekor Kemenangan Lokal -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-bar-chart-fill text-emerald-500"></i>
                            <span>Rekor Catur Anda</span>
                        </h2>
                        <button type="button" id="btnChessResetStats" class="text-[11px] font-semibold text-rose-500 hover:underline cursor-pointer">
                            Reset Rekor
                        </button>
                    </div>
                    <div id="chessStatsPanel" class="grid grid-cols-3 gap-2 text-center"></div>
                </div>

                <!-- Riwayat Notasi Langkah -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-2.5">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-journal-code text-amber-500"></i>
                        <span>Notasi Langkah (Aljabar)</span>
                    </h2>
                    <div id="chessMoveHistory" class="max-h-60 overflow-y-auto space-y-1 text-xs font-mono text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 rounded-xl p-2.5 border border-slate-200/70 dark:border-slate-700/60"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hasil Pertandingan -->
<div id="chessResultModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-xs p-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-600 to-indigo-600 text-white flex items-center justify-between">
            <h3 class="text-base font-extrabold flex items-center gap-2">
                <i class="bi bi-trophy-fill text-amber-200"></i>
                <span>Hasil Pertandingan Catur</span>
            </h3>
            <button type="button" id="btnCloseChessResult" class="text-white/80 hover:text-white cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="chessResultBody" class="p-5 space-y-4"></div>
    </div>
</div>

<!-- Modal Aturan Catur -->
<div id="chessRulesModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-xs p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden max-h-[88vh] flex flex-col">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-book-fill text-indigo-500"></i>
                <span>Aturan &amp; Panduan Catur 3D</span>
            </h3>
            <button type="button" id="btnCloseChessRules" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div class="p-4 sm:p-5 overflow-y-auto space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
            <ul class="list-disc list-inside space-y-1.5">
                <li><strong>Aturan Legal FIDE:</strong> Langkah tidak diizinkan jika membuat Raja sendiri dalam posisi Skak.</li>
                <li><strong>Rokade (Castling):</strong> Jika Raja dan Benteng belum pernah bergerak serta jalur di antaranya kosong dan tidak terancam Skak, klik Raja lalu pilih petak 2 langkah menuju Benteng.</li>
                <li><strong>En Passant &amp; Promosi:</strong> Pion dapat menangkap secara <em>en passant</em> setelah pion lawan maju 2 langkah. Saat pion mencapai baris akhir, pilih promosi menjadi Menteri, Benteng, Gajah, atau Kuda.</li>
                <li><strong>3 Level Kecerdasan AI:</strong> <em>Santai</em> (pemula), <em>Normal</em> (taktis seimbang), dan <em>Sulit</em> (pencarian mendalam Alpha-Beta &amp; evaluasi posisi).</li>
                <li><strong>Kamera 3D:</strong> Ubah sudut pandang antara <strong>3D Meja</strong>, <strong>Sinematik</strong>, dan <strong>2D Atas</strong> kapan saja tanpa memengaruhi akurasi klik bidak.</li>
            </ul>
        </div>
    </div>
</div>
@include('partials.game_ux_enhancements')
@endsection

@section('scripts')
<script src="{{ asset('assets/js/catur-game.js') }}?v={{ filemtime(public_path('assets/js/catur-game.js')) }}"></script>
@endsection
