@props([
    'matches' => [],
    'tampilTanggal' => true,
])

@if (empty($matches) || count($matches) === 0)
    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-calendar-x text-2xl text-slate-400 mb-2 block"></i>
        Tidak ada pertandingan.
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-3.5 px-4 text-center w-12">#</th>
                        @if ($tampilTanggal)
                            <th scope="col" class="py-3.5 px-4">Tanggal</th>
                        @endif
                        <th scope="col" class="py-3.5 px-4">Jam</th>
                        <th scope="col" class="py-3.5 px-4">Pertandingan &amp; Skor</th>
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
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <a class="group inline-flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" href="{{ route('pertandingan.detail', $m->id) }}">
                                    <!-- Home Team -->
                                    <span class="{{ $m->status === 'selesai' && $m->pemenang_id == $m->tim_home ? 'text-indigo-600 font-extrabold' : '' }}">
                                        {{ $homeName }}
                                    </span>

                                    <!-- Score Box / vs -->
                                    @if ($skorAda)
                                        <span class="inline-flex items-center justify-center font-mono font-black text-xs px-2.5 py-0.5 rounded-md bg-slate-900 text-white tracking-wider shadow-xs">
                                            {{ (int) ($m->skor_home ?? 0) }} - {{ (int) ($m->skor_away ?? 0) }}
                                        </span>
                                    @else
                                        <span class="text-xs font-normal text-slate-400 px-1.5 py-0.5 rounded bg-slate-100">
                                            vs
                                        </span>
                                    @endif

                                    <!-- Away Team -->
                                    <span class="{{ $m->status === 'selesai' && $m->pemenang_id == $m->tim_away ? 'text-indigo-600 font-extrabold' : '' }}">
                                        {{ $awayName }}
                                    </span>
                                    <i class="bi bi-chevron-right opacity-0 -ml-1 group-hover:opacity-100 group-hover:ml-0 transition-all text-xs"></i>
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 whitespace-nowrap">
                                {{ $m->lokasi ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                {!! \App\Services\TournamentService::badgeStatus($m->status) !!}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if ($m->status === 'selesai' && $pemenangName)
                                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
                                        <i class="bi bi-trophy text-xs"></i> {{ $pemenangName }} Menang
                                    </span>
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

