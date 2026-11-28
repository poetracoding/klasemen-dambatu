@extends('layouts.app')

@section('title', 'Game Flappy Bird')

@section('content')
<script>window.DISABLE_LIVE_UPDATE = true;</script>

<style>
    #flappyFullscreenWrapper:fullscreen,
    #flappyFullscreenWrapper.is-pseudo-fullscreen {
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        padding: 0.75rem;
        background-color: #f1f5f9;
        overflow: auto;
    }
    #flappyFullscreenWrapper.is-pseudo-fullscreen {
        position: fixed;
        inset: 0;
        z-index: 60;
    }
    html.dark #flappyFullscreenWrapper:fullscreen,
    html.dark #flappyFullscreenWrapper.is-pseudo-fullscreen {
        background-color: #020617;
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
                <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-sm shrink-0">
                    <i class="bi bi-feather text-lg"></i>
                </span>
                <span>Game Flappy Bird</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Mini-game arkade ringan dengan 3 level kesulitan, 3 tema langit, efek suara Web Audio, dan papan medali lokal.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="btnFlappyOpenRules"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-question-circle text-indigo-500"></i>
                <span>Aturan &amp; Kontrol</span>
            </button>
            <button type="button" id="btnFlappyMute"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-volume-up-fill text-emerald-500" id="flappyMuteIcon"></i>
                <span id="flappyMuteLabel">Suara Aktif</span>
            </button>
            <button type="button" id="btnFlappyFullscreen"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                <i class="bi bi-arrows-fullscreen text-indigo-500"></i>
                <span>Perluas Layar</span>
            </button>
        </div>
    </div>

    <!-- Arena Utama -->
    <div id="flappyFullscreenWrapper" class="space-y-4 game-touch-arena">
        @include('partials.game_fs_exit', ['btnId' => 'btnFlappyFullscreen'])
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
            <!-- Kolom Kiri/Utama: Toolbar + Canvas Game -->
            <div class="lg:col-span-7 xl:col-span-8">
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-xs space-y-3">
                    <!-- Toolbar Pengaturan Permainan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2.5 pb-3 border-b border-slate-200/70 dark:border-slate-800 items-end">
                        <!-- Level Kesulitan -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Level Kecepatan</label>
                            <div class="grid grid-cols-3 gap-1">
                                <button type="button" data-flappy-diff="easy" class="flappy-diff-btn px-2 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer">Santai</button>
                                <button type="button" data-flappy-diff="normal" class="flappy-diff-btn px-2 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer">Normal</button>
                                <button type="button" data-flappy-diff="hard" class="flappy-diff-btn px-2 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer">Sulit</button>
                            </div>
                        </div>

                        <!-- Tema Langit -->
                        <div>
                            <label for="flappyThemeSelect" class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Tema Langit</label>
                            <select id="flappyThemeSelect"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="day">☀️ Siang Cerah</option>
                                <option value="sunset">🌇 Senja Nusantara</option>
                                <option value="night">🌙 Malam Neon</option>
                            </select>
                        </div>

                        <!-- Karakter Burung -->
                        <div>
                            <label for="flappySkinSelect" class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Karakter Burung</label>
                            <select id="flappySkinSelect"
                                class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-xs font-semibold px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="classic">🐤 Flappy Kuning</option>
                                <option value="garuda">🦅 Garuda Emas</option>
                                <option value="electric">🐦 Biru Elektrik</option>
                                <option value="fire">🔥 Merah Api</option>
                            </select>
                        </div>

                        <!-- Kontrol Mulai / Jeda -->
                        <div class="flex items-center gap-1.5">
                            <button type="button" id="btnFlappyStart"
                                class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors cursor-pointer">
                                <i class="bi bi-play-fill"></i>
                                <span>Mulai Ulang</span>
                            </button>
                            <button type="button" id="btnFlappyPause"
                                class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition-colors cursor-pointer disabled:opacity-40">
                                <i class="bi bi-pause-fill" id="flappyPauseIcon"></i>
                                <span id="flappyPauseLabel">Jeda</span>
                            </button>
                        </div>
                    </div>

                    <!-- HUD Live -->
                    <div class="flex flex-wrap items-center justify-between gap-2 px-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/70 text-indigo-700 dark:text-indigo-300 text-xs font-bold" id="flappyDifficultyBadge">
                                Level: Normal
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/70 text-amber-700 dark:text-amber-300 text-xs font-bold" id="flappyBestBadge">
                                Rekor: 0
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <span>Skor Saat Ini:</span>
                            <span id="flappyLiveScore" class="px-3 py-0.5 rounded-full bg-emerald-600 text-white font-mono text-sm font-black shadow-xs">0</span>
                        </div>
                    </div>

                    <!-- Canvas Permainan -->
                    <div class="rounded-2xl bg-slate-900/95 p-2 sm:p-3 border border-slate-700/80 shadow-inner">
                        <canvas id="flappyCanvas" width="480" height="640"
                            class="w-full max-w-[440px] mx-auto block rounded-xl shadow-lg cursor-pointer touch-manipulation select-none bg-sky-400"></canvas>
                    </div>

                    <!-- Tombol Sentuh Bawah (Nyaman untuk Mobile / Mouse) -->
                    <button type="button" id="btnFlappyTapFlap"
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 active:scale-[0.99] text-white text-sm font-extrabold shadow-sm transition-all cursor-pointer touch-manipulation select-none flex items-center justify-center gap-2">
                        <span>🪽</span>
                        <span>Kepak Sayap (Klik / Tap / Tekan Spasi)</span>
                    </button>
                </div>
            </div>

            <!-- Sidebar Kanan: Rekor, Medali, Riwayat & Kontrol -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-4">
                <!-- Papan Skor & Medali -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="bi bi-trophy-fill text-amber-500"></i>
                            <span>Rekor Terbaik &amp; Medali</span>
                        </h2>
                        <button type="button" id="btnFlappyResetStats" class="text-[11px] font-semibold text-rose-500 hover:underline cursor-pointer">
                            Reset Rekor
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200/70 dark:border-emerald-800/50 p-2.5">
                            <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300">Santai</div>
                            <div id="flappyBestEasy" class="text-lg font-mono font-black text-emerald-600 dark:text-emerald-400">0</div>
                        </div>
                        <div class="rounded-xl bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200/70 dark:border-indigo-800/50 p-2.5">
                            <div class="text-[10px] font-bold text-indigo-700 dark:text-indigo-300">Normal</div>
                            <div id="flappyBestNormal" class="text-lg font-mono font-black text-indigo-600 dark:text-indigo-400">0</div>
                        </div>
                        <div class="rounded-xl bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200/70 dark:border-rose-800/50 p-2.5">
                            <div class="text-[10px] font-bold text-rose-700 dark:text-rose-300">Sulit</div>
                            <div id="flappyBestHard" class="text-lg font-mono font-black text-rose-600 dark:text-rose-400">0</div>
                        </div>
                    </div>

                    <div id="flappyBestMedal"
                        class="rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700/70 px-3 py-2.5 text-xs font-semibold text-slate-600 dark:text-slate-300 flex items-center justify-between">
                        <span>Medali Tertinggi:</span>
                        <span class="font-bold text-slate-800 dark:text-white">Belum ada (Min. skor 10)</span>
                    </div>
                </div>

                <!-- Riwayat 5 Percobaan Terakhir -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-3">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-clock-history text-indigo-500"></i>
                        <span>5 Percobaan Terakhir</span>
                    </h2>
                    <div id="flappyRecentRuns" class="space-y-1.5 text-xs text-slate-600 dark:text-slate-300">
                        <div class="text-center text-slate-400 py-3">Belum ada riwayat permainan.</div>
                    </div>
                </div>

                <!-- Panduan Singkat -->
                <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs space-y-2 text-xs text-slate-600 dark:text-slate-300">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="bi bi-controller text-emerald-500"></i>
                        <span>Kontrol &amp; Pencapaian Medali</span>
                    </h2>
                    <ul class="space-y-1">
                        <li>&bull; <strong>Kepak Sayap:</strong> Klik kiri di area kanvas, tap tombol oranye, tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono text-[10px]">Spasi</kbd>, atau <kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono text-[10px]">&uarr;</kbd>.</li>
                        <li>&bull; <strong>Jeda / Lanjut:</strong> Tekan tombol <kbd class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 font-mono text-[10px]">P</kbd> atau klik tombol Jeda.</li>
                        <li>&bull; <strong>Level Sulit:</strong> Setelah skor &ge; 10, sebagian pipa mulai bergerak naik-turun!</li>
                        <li>&bull; <strong>Medali:</strong> 🥉 Perunggu (10+), 🥈 Perak (20+), 🥇 Emas (40+), 💎 Berlian (70+).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aturan & Kontrol -->
<div id="flappyRulesModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/75 backdrop-blur-xs p-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <i class="bi bi-feather text-amber-500"></i>
                <span>Cara Bermain Flappy Bird</span>
            </h3>
            <button type="button" id="btnCloseFlappyRules" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        <div class="p-4 sm:p-5 space-y-3 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
            <p>Terbangkan burung melewati celah antar pipa sebanyak mungkin tanpa menabrak pipa atau jatuh ke tanah.</p>
            <ul class="list-disc list-inside space-y-1.5">
                <li><strong>Santai:</strong> Celah pipa lebar &amp; kecepatan ramah untuk pemanasan.</li>
                <li><strong>Normal:</strong> Kecepatan dan jarak pipa standar klasik.</li>
                <li><strong>Sulit:</strong> Celah lebih sempit, kecepatan tinggi, dan pipa mulai bergerak vertikal saat skor &ge; 10.</li>
            </ul>
            <div class="rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 p-3 text-xs text-amber-900 dark:text-amber-200">
                Seluruh skor tertinggi dan riwayat percobaan tersimpan lokal di browser Anda per tingkat kesulitan.
            </div>
        </div>
    </div>
</div>
@include('partials.game_ux_enhancements')
@endsection

@section('scripts')
<script src="{{ asset('assets/js/flappy-bird-game.js') }}?v={{ filemtime(public_path('assets/js/flappy-bird-game.js')) }}"></script>
@endsection
