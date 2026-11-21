<!-- Modal Permintaan Izin Notifikasi Terupdate -->
<div id="fcmPromptModal"
    class="fixed inset-0 z-[95] bg-slate-950/60 backdrop-blur-xs hidden items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-labelledby="fcmPromptTitle">
    <div class="relative bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-7 max-w-md w-full shadow-2xl border border-slate-200 dark:border-slate-800">
        <button type="button" id="fcmPromptCloseBtn"
            class="absolute top-4 right-4 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            aria-label="Tutup">
            <i class="bi bi-x-lg text-sm"></i>
        </button>

        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/80 flex items-center justify-center mb-4">
            <i class="bi bi-bell-fill text-xl"></i>
        </div>

        <h3 id="fcmPromptTitle" class="text-lg font-extrabold text-slate-900 dark:text-white tracking-tight leading-snug">
            Aktifkan Notifikasi Terupdate?
        </h3>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-2 leading-relaxed">
            Dapatkan pemberitahuan langsung di perangkat Anda setiap ada update skor pertandingan, pergeseran klasemen, dan berita terbaru {{ $liga->nama_liga ?? 'Liga Dam Batu' }}.
        </p>

        <div class="mt-6 space-y-2.5">
            <button type="button" id="fcmPromptYesBtn"
                class="w-full py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 flex items-center justify-center gap-2 cursor-pointer">
                <i class="bi bi-bell-fill"></i>
                <span>Ya, Aktifkan</span>
            </button>

            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="fcmPromptLaterBtn"
                    class="py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Tidak Sekarang
                </button>
                <button type="button" id="fcmPromptNoBtn"
                    class="py-2.5 px-3 rounded-xl text-xs sm:text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700/80 transition-colors cursor-pointer">
                    Tidak
                </button>
            </div>
        </div>
    </div>
</div>
