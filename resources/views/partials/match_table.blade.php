@props([
    'matches' => [],
    'tampilTanggal' => true,
])

@if (empty($matches) || count($matches) === 0)
    <div class="ldb-fade-in rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-10 text-center shadow-xs">
        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center mb-3">
            <i class="bi bi-calendar-x text-2xl"></i>
        </div>
        <div class="text-sm font-bold text-slate-700 dark:text-slate-200">Belum Ada Pertandingan</div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
            Tidak ada data laga pada kategori atau filter yang dipilih saat ini.
        </p>
    </div>
@else
    <div class="ldb-fade-in overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm text-slate-600 dark:text-slate-300 divide-y divide-slate-100 dark:divide-slate-800">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <tr>
                        <th scope="col" class="py-3.5 px-4 text-center w-12">#</th>
                        @if ($tampilTanggal)
                            <th scope="col" class="py-3.5 px-4">Tanggal</th>
                        @endif
                        <th scope="col" class="py-3.5 px-4">Jam</th>
                        <th scope="col" colspan="3" class="py-3.5 px-4 text-center">Pertandingan &amp; Skor</th>
                        <th scope="col" class="py-3.5 px-4">Lokasi</th>
                        <th scope="col" class="py-3.5 px-4">Status</th>
                        <th scope="col" class="py-3.5 px-4">Hasil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($matches as $m)
                        @php
                            $skorAda = ($m->status === 'selesai' || $m->status === 'berlangsung');
                            $homeName = $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home);
                            $awayName = $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away);
                            $pemenangName = $m->pemenang->nama_tim ?? null;
                            $tglStr = $m->tanggal ? \Carbon\Carbon::parse($m->tanggal)->format('Y-m-d') : null;
                            $isFreshUpdate = ($m->status === 'selesai' && !empty($m->updated_at) && \Carbon\Carbon::parse($m->updated_at)->greaterThanOrEqualTo(now()->subDay()));
                        @endphp
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/80 transition-colors">
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $m->nomor_pertandingan }}
                            </td>
                            @if ($tampilTanggal)
                                <td class="py-3.5 px-4 font-medium text-slate-700 whitespace-nowrap">
                                    {{ $tglStr ? \App\Services\TournamentService::hariId($tglStr) . ', ' . \App\Services\TournamentService::tglId($tglStr) : '-' }}
                                </td>
                            @endif
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap font-mono text-xs">
                                {{ \App\Services\TournamentService::jamId($m->waktu) }}
                            </td>
                            <!-- Home Team -->
                            <td class="py-3.5 pl-4 pr-2 text-right">
                                <a class="inline-flex flex-col items-end font-semibold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" href="{{ route('pertandingan.detail', $m->id) }}">
                                    <span class="whitespace-nowrap {{ $m->status === 'selesai' && $m->pemenang_id == $m->tim_home ? 'text-indigo-600 font-extrabold' : '' }}">
                                        {{ $homeName }}
                                    </span>
                                    @include('partials.team_players', [
                                        'p1' => $m->homeTeam->pemain1 ?? null,
                                        'p2' => $m->homeTeam->pemain2 ?? null,
                                        'short' => true,
                                        'maxLen' => 7,
                                        'align' => 'right',
                                    ])
                                </a>
                            </td>

                            <!-- Score Box / vs -->
                            <td class="py-3.5 px-1 text-center w-14">
                                <a class="inline-flex items-center justify-center" href="{{ route('pertandingan.detail', $m->id) }}">
                                    @if ($skorAda)
                                        <span class="inline-flex items-center justify-center font-mono font-black text-xs px-2.5 py-0.5 rounded-md bg-slate-900 text-white tracking-wider shadow-xs whitespace-nowrap">
                                            {{ (int) ($m->skor_home ?? 0) }} - {{ (int) ($m->skor_away ?? 0) }}
                                        </span>
                                    @else
                                        <span class="text-xs font-normal text-slate-400 px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800">
                                            vs
                                        </span>
                                    @endif
                                </a>
                            </td>

                            <!-- Away Team -->
                            <td class="py-3.5 pl-2 pr-4 text-left">
                                <a class="inline-flex flex-col items-start font-semibold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" href="{{ route('pertandingan.detail', $m->id) }}">
                                    <span class="whitespace-nowrap {{ $m->status === 'selesai' && $m->pemenang_id == $m->tim_away ? 'text-indigo-600 font-extrabold' : '' }}">
                                        {{ $awayName }}
                                    </span>
                                    @include('partials.team_players', [
                                        'p1' => $m->awayTeam->pemain1 ?? null,
                                        'p2' => $m->awayTeam->pemain2 ?? null,
                                        'short' => true,
                                        'maxLen' => 7,
                                        'align' => 'left',
                                    ])
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                {{ $m->lokasi ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    {!! \App\Services\TournamentService::badgeStatus($m->status) !!}
                                    @if ($isFreshUpdate)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/40" title="Skor diperbarui dalam 24 jam terakhir">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                            Baru
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if ($m->status === 'selesai' && $pemenangName)
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-500/15 px-2 py-0.5 rounded-md border border-emerald-200/60 dark:border-emerald-500/35">
                                        <i class="bi bi-trophy text-xs"></i> {{ $pemenangName }} Menang
                                    </span>
                                    @include('partials.team_players', [
                                        'p1' => $m->pemenang->pemain1 ?? null,
                                        'p2' => $m->pemenang->pemain2 ?? null,
                                        'short' => true,
                                        'maxLen' => 7,
                                    ])
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

