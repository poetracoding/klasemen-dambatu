@if (app(\App\Services\FcmService::class)->isClientReady())
    <!-- Card Kecil Ajakan Aktivasi Notifikasi -->
    <div class="fcm-nudge-card hidden mb-6 rounded-2xl border border-indigo-200/80 dark:border-indigo-800/70 bg-gradient-to-r from-indigo-50 via-white to-violet-50 dark:from-indigo-950/50 dark:via-slate-900 dark:to-violet-950/40 p-3.5 sm:p-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start sm:items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs shadow-indigo-600/20">
                    <i class="bi bi-bell-fill text-sm"></i>
                </div>
                <div>
                    <div class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-snug">
                        Selalu dapatkan informasi terupdate!
                    </div>
                    <p class="text-[11px] sm:text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-snug">
                        Aktifkan notifikasi browser untuk menerima update skor pertandingan, klasemen, dan berita terbaru langsung di perangkat Anda.
                    </p>
                </div>
            </div>
            <button type="button"
                class="fcm-nudge-enable-btn inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 whitespace-nowrap shrink-0 cursor-pointer">
                <i class="bi bi-bell"></i>
                <span>Aktifkan Notifikasi</span>
            </button>
        </div>
    </div>
@endif
