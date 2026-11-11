@if (isset($tickerMatches) && $tickerMatches->isNotEmpty())
    <div class="relative w-full bg-white/95 dark:bg-slate-900/95 border-b border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden z-30 transition-colors">
        <div class="flex items-center">
            <!-- Fixed Left Label / Badge (Compact on mobile, full on desktop) -->
            <div class="flex items-center gap-1.5 sm:gap-2 px-2 sm:px-4 py-1 sm:py-2 bg-gradient-to-r from-indigo-600 via-indigo-700 to-violet-700 text-white font-extrabold text-3xs sm:text-xs uppercase tracking-wider shadow-sm flex-shrink-0 z-20 select-none">
                <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="hidden sm:inline">{{ $tickerLabel ?? 'Jadwal & Hasil Pekan Ini' }}</span>
                <span class="sm:hidden font-black tracking-tight text-3xs">PEKAN INI</span>
                @if (!empty($tickerDateRange))
                    <span class="hidden lg:inline text-indigo-200 font-normal lowercase text-3xs">({{ $tickerDateRange }})</span>
                @endif
            </div>

            <!-- Ticker Scrolling Track -->
            <div class="relative flex-1 overflow-hidden py-1 sm:py-1.5">
                <!-- Fade Gradients -->
                <div class="pointer-events-none absolute left-0 top-0 bottom-0 w-3 sm:w-6 bg-gradient-to-r from-white dark:from-slate-900 to-transparent z-10"></div>
                <div class="pointer-events-none absolute right-0 top-0 bottom-0 w-4 sm:w-8 bg-gradient-to-l from-white dark:from-slate-900 to-transparent z-10"></div>

                <!-- Scrolling Marquee Track (Repeated twice for seamless loop) -->
                <div class="animate-marquee-scroll flex items-center gap-2 sm:gap-4 group" style="animation-duration: {{ max(28, $tickerMatches->count() * 3.5) }}s;">
                    @for ($loopCount = 0; $loopCount < 2; $loopCount++)
                        <div class="flex items-center gap-2 sm:gap-3">
                            @foreach ($tickerMatches as $m)
                                @if ($m->status === 'selesai')
                                    <a href="{{ route('pertandingan.detail', $m->id) }}" class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full bg-emerald-50/90 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-2xs sm:text-xs font-medium hover:border-emerald-400 dark:hover:border-emerald-600 transition-colors whitespace-nowrap shadow-2xs" title="Lihat detail hasil pertandingan">
                                        <span class="px-1 sm:px-1.5 py-0.2 sm:py-0.5 rounded text-3xs font-black bg-emerald-600 text-white uppercase tracking-wider">FT</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-2xs sm:text-xs">{{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}</span>
                                        <span class="font-mono font-black text-emerald-700 dark:text-emerald-300 bg-emerald-100/70 dark:bg-emerald-900/60 px-1.5 py-0.2 rounded text-2xs sm:text-xs">{{ (int) $m->skor_home }} - {{ (int) $m->skor_away }}</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-2xs sm:text-xs">{{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}</span>
                                    </a>
                                @elseif ($m->status === 'berlangsung')
                                    <a href="{{ route('pertandingan.detail', $m->id) }}" class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full bg-amber-50/90 dark:bg-amber-950/70 border border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200 text-2xs sm:text-xs font-bold hover:border-amber-400 transition-colors whitespace-nowrap shadow-2xs" title="Pertandingan sedang berlangsung">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        <span class="px-1 sm:px-1.5 py-0.2 sm:py-0.5 rounded text-3xs font-black bg-amber-500 text-white uppercase tracking-wider animate-pulse">LIVE</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-2xs sm:text-xs">{{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}</span>
                                        <span class="font-mono font-black text-amber-900 dark:text-amber-100 bg-amber-100/80 dark:bg-amber-900/60 px-1.5 py-0.2 rounded text-2xs sm:text-xs">{{ (int) $m->skor_home }} - {{ (int) $m->skor_away }}</span>
                                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-2xs sm:text-xs">{{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}</span>
                                        <span class="hidden sm:inline text-3xs text-amber-700 dark:text-amber-400">({{ $m->lokasi ?: 'Meja' }})</span>
                                    </a>
                                @else
                                    <a href="{{ route('pertandingan.detail', $m->id) }}" class="inline-flex items-center gap-1 sm:gap-1.5 px-2 sm:px-3 py-0.5 sm:py-1 rounded-full bg-slate-50 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-2xs sm:text-xs hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors whitespace-nowrap shadow-2xs" title="Jadwal pertandingan mendatang">
                                        <span class="px-1 sm:px-1.5 py-0.2 sm:py-0.5 rounded text-3xs font-black bg-indigo-600 text-white uppercase tracking-wider">
                                            <span class="sm:hidden">VS</span>
                                            <span class="hidden sm:inline">JADWAL</span>
                                        </span>
                                        <span class="font-medium text-slate-800 dark:text-slate-200 text-2xs sm:text-xs">{{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}</span>
                                        <span class="text-slate-400 dark:text-slate-500 font-bold text-3xs sm:text-2xs">vs</span>
                                        <span class="font-medium text-slate-800 dark:text-slate-200 text-2xs sm:text-xs">{{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}</span>
                                        <span class="hidden sm:inline text-slate-400 dark:text-slate-500 text-2xs font-mono">&middot; {{ \App\Services\TournamentService::hariId($m->tanggal) }}, {{ date('d/m', strtotime($m->tanggal)) }} {{ \App\Services\TournamentService::jamId($m->waktu) }}</span>
                                        <span class="sm:hidden text-indigo-500 dark:text-indigo-400 text-3xs font-mono font-medium">({{ date('d/m', strtotime($m->tanggal)) }})</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
@endif

