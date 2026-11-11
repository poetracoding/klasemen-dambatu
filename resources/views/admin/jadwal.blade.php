@extends('layouts.admin')

@section('title', 'Kelola Jadwal Pertandingan')

@section('content')
@php
    $draft = $draft ?? ($liga->status === 'draft');
    $matches = $matches ?? $jadwal ?? collect();
    $sudahBertanding = $sudahBertanding ?? $matches->whereNotIn('status', ['belum_main'])->count();
    $totalTim = $jumlahTim ?? $totalTim ?? 0;
    $hariMainArr = $defaultHari ?? $hariMainArr ?? [1,2,3,4,5,6,7];
    $liburArr = $defaultLibur ?? $liburArr ?? [];
    $listHariNama = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kelola Jadwal Pertandingan</h1>
        <p class="text-sm text-slate-500 mt-1">Pembuatan jadwal round-robin, alokasi meja, dan penyesuaian waktu tanding.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('jadwal.pdf') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors shadow-xs" title="Buka PDF untuk dicetak">
            <i class="bi bi-printer"></i> Cetak PDF
        </a>
        <a href="{{ route('jadwal.pdf', ['download' => 1]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs" title="Unduh berkas PDF">
            <i class="bi bi-file-earmark-arrow-down"></i> Unduh PDF
        </a>
        <a href="{{ route('jadwal') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors shadow-xs">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Jadwal Publik
        </a>
    </div>
</div>

<!-- Info Status Bar -->
<div class="mb-6 p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 flex flex-wrap items-center justify-between gap-3 text-xs text-indigo-900 shadow-xs">
    <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full {{ $draft ? 'bg-amber-400' : 'bg-emerald-500' }}"></span>
        <span>
            Status Liga: <strong>{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</strong> &middot;
            <strong>{{ $totalTim }} tim</strong> terdaftar &rarr; Total pertandingan = <strong>{{ $totalTim > 1 ? (int)(($totalTim * ($totalTim - 1)) / 2) : 0 }} match</strong>.
            @if ($totalTim % 2)
                <span class="text-amber-700 font-semibold">(Ganjil: ada tim istirahat/bye tiap ronde)</span>
            @endif
        </span>
    </div>
    @if ($sudahBertanding > 0)
        <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-100/60 px-2.5 py-1 rounded-lg">
            <i class="bi bi-play-circle-fill"></i> {{ $sudahBertanding }} pertandingan sudah berjalan/selesai
        </span>
    @endif
</div>

@if ($draft)
    <!-- Generator Jadwal Card (Dukungan Multi-Meja) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs mb-8">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-magic text-indigo-500"></i> Generate Jadwal Otomatis (Multi-Meja Dam Batu)
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Pertandingan dapat berlangsung simultan di beberapa meja tanpa antri, dengan jaminan tim yang sama tidak akan bentrok di jam yang sama.</p>
            </div>
            @if ($matches->isNotEmpty())
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200/60 hidden sm:inline-block">
                    <i class="bi bi-exclamation-triangle mr-1"></i> Jadwal lama akan digantikan
                </span>
            @endif
        </div>

        <form action="{{ route('admin.jadwal.generate') }}" method="POST" class="space-y-4" data-confirm="{{ $matches->isNotEmpty() ? 'Jadwal yang ada saat ini akan diganti seluruhnya. Lanjutkan?' : 'Generate jadwal pertandingan sekarang?' }}" data-confirm-title="Generate Jadwal?" data-confirm-btn="Ya, Generate">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="mulai" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" required value="{{ date('Y-m-d') }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Meja Dam Batu</label>
                    <input type="number" name="jumlah_meja" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" min="1" max="10" required value="{{ $liga->jumlah_meja ?: 6 }}">
                    <span class="text-3xs text-slate-400 mt-1 block">Tersedia: Meja 1 s/d Meja {{ $liga->jumlah_meja ?: 6 }}</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Maks. Match / Hari</label>
                    <input type="number" name="per_hari" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" min="1" max="50" required value="4">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jam Mulai (WIB)</label>
                    <input type="time" name="jam" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" required value="19:00">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Durasi (Menit)</label>
                    <input type="number" name="durasi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" min="1" max="600" required value="45">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Meja / Lokasi</label>
                    <input name="lokasi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus" maxlength="60" placeholder="Meja">
                </div>
            </div>

            <!-- Penentuan Hari Pertandingan -->
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-800">
                            <i class="bi bi-calendar-week text-indigo-500 mr-1"></i> Penentuan Hari Pertandingan
                        </label>
                        <p class="text-3xs text-slate-500">Pilih hari apa saja pertandingan boleh dilaksanakan. Hari yang tidak dicentang akan dilewati otomatis saat generate jadwal.</p>
                    </div>
                    <!-- Preset Shortcut Buttons -->
                    <div class="flex flex-wrap items-center gap-1.5 text-3xs font-semibold">
                        <button type="button" onclick="pilihHariPreset([2,3,4])" class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 shadow-2xs transition-colors">Selasa, Rabu, Kamis</button>
                        <button type="button" onclick="pilihHariPreset([1,2,3,4,5,6,7])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Semua Hari</button>
                        <button type="button" onclick="pilihHariPreset([1,2,3,4,5])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Senin - Jumat</button>
                        <button type="button" onclick="pilihHariPreset([5,6,7])" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 hover:text-indigo-600 hover:border-indigo-300 shadow-2xs transition-colors">Jumat - Minggu</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                    @foreach ($listHariNama as $num => $nama)
                        @php
                            $checked = in_array((int) $num, array_map('intval', $hariMainArr), true);
                            $isWeekend = $num >= 6;
                        @endphp
                        <label class="hari-pill flex items-center justify-between p-2.5 rounded-xl border cursor-pointer select-none transition-all {{ $checked ? 'bg-indigo-50 border-indigo-200 text-indigo-900 font-bold shadow-2xs' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="hari[]" value="{{ $num }}" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300" {{ $checked ? 'checked' : '' }} onchange="updateHariPill(this)">
                                <span class="text-xs">{{ $nama }}</span>
                            </div>
                            @if ($isWeekend)
                                <span class="text-4xs px-1 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold">Weekend</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Skenario 1: Tanggal Libur Khusus (Dilewati) -->
            <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-rose-950 flex items-center gap-1.5">
                            <i class="bi bi-calendar-x text-rose-500 text-sm"></i>
                            <span>Tanggal Libur Khusus (Dilewati / Blackout Dates)</span>
                            <span class="text-4xs uppercase bg-rose-200 text-rose-800 px-1.5 py-0.5 rounded font-bold">Skenario 1</span>
                        </label>
                        <p class="text-3xs text-rose-700/80 mt-0.5">Pertandingan tidak akan dijadwalkan pada tanggal-tanggal ini (misal hari besar, pemilu, venue tutup) dan otomatis dialihkan ke jadwal berikutnya.</p>
                    </div>
                </div>

                <!-- Input Picker & Button Tambah -->
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <input type="date" id="inputTanggalLibur" class="text-xs rounded-xl border border-rose-200 bg-white py-1.5 px-3 text-slate-800 form-input-focus shadow-2xs">
                    <button type="button" onclick="tambahTanggalLibur()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-100 hover:bg-rose-200 border border-rose-200 transition-colors shadow-2xs">
                        <i class="bi bi-plus-circle-fill"></i> Tambah Hari Libur
                    </button>
                    <span class="text-3xs text-slate-400 italic">Pilih tanggal lalu klik Tambah</span>
                </div>

                <!-- List Tanggal Libur Yang Terpilih -->
                <div id="containerLibur" class="flex flex-wrap items-center gap-2 min-h-8">
                    @if (empty($liburArr))
                        <span id="emptyLiburNotice" class="text-3xs text-slate-400 italic">Belum ada tanggal libur khusus yang ditambahkan (opsional).</span>
                    @else
                        @foreach ($liburArr as $dl)
                            <span class="libur-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white text-rose-700 text-xs font-semibold border border-rose-200 shadow-2xs">
                                <i class="bi bi-calendar-x text-rose-500 text-xs"></i>
                                <span>{{ \App\Services\TournamentService::hariId($dl) }}, {{ \App\Services\TournamentService::tglId($dl) }}</span>
                                <input type="hidden" name="libur[]" value="{{ $dl }}">
                                <button type="button" onclick="hapusTanggalLibur(this)" class="text-slate-400 hover:text-rose-600 transition-colors ml-1 font-bold text-sm cursor-pointer" title="Hapus tanggal libur">&times;</button>
                            </span>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="p-3 bg-indigo-50/70 rounded-xl border border-indigo-100 text-xs text-indigo-900 flex items-start gap-2">
                <i class="bi bi-info-circle-fill text-indigo-500 mt-0.5 flex-shrink-0"></i>
                <div>
                    <strong>Sistem Prioritas Meja Kosong:</strong> Pertandingan akan selalu menggunakan meja yang sudah kosong (mulai dari Meja 1). Meja 2, 3, dst hanya digunakan secara paralel jika meja sebelumnya belum selesai bertanding pada jam yang sama. Dengan <strong>{{ $totalTim }} tim</strong>, kapasitas simultan adalah maksimal <strong>{{ max(1, intdiv($totalTim, 2)) }} meja</strong> bersamaan.
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer" {{ $totalTim < 2 ? 'disabled' : '' }}>
                    <i class="bi bi-play-circle"></i> Generate Jadwal
                </button>
            </div>
        </form>
    </div>
@endif

@if ($matches->isNotEmpty())
    <!-- Action Bar: Publikasi, Batal Jadwal, dan Geser Jadwal -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-6">
        <!-- Status & Publikasi / Batal Jadwal Actions -->
        <div class="lg:col-span-12 flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="text-xs text-slate-600 font-medium">
                    Total <strong>{{ $matches->count() }}</strong> pertandingan tersusun.
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($draft)
                    <!-- Tombol Publikasikan -->
                    <form action="{{ route('admin.jadwal.publikasi') }}" method="POST" data-confirm="Publikasikan jadwal? Tim tidak dapat ditambah/dihapus setelah ini." data-confirm-title="Publikasikan Jadwal?" data-confirm-btn="Ya, Publikasikan">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 py-2 px-4 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors shadow-xs shadow-emerald-600/20 cursor-pointer">
                            <i class="bi bi-megaphone-fill"></i> Publikasikan Jadwal
                        </button>
                    </form>

                    <!-- Batalkan & Hapus Jadwal -->
                    <form action="{{ route('admin.jadwal.batal') }}" method="POST" class="form-delete" data-confirm="Apakah Anda yakin ingin MEMBATALKAN & MENGHAPUS SELURUH JADWAL yang digenerate? Jadwal akan dikosongkan kembali." data-confirm-title="Hapus Semua Jadwal?" data-confirm-btn="Ya, Hapus Semua">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer">
                            <i class="bi bi-trash3-fill"></i> Batalkan &amp; Hapus Jadwal
                        </button>
                    </form>
                @else
                    @if ($sudahBertanding === 0)
                        <form action="{{ route('admin.jadwal.batal_publikasi') }}" method="POST" data-confirm="Batalkan publikasi dan kembali ke mode draft?" data-confirm-title="Batalkan Publikasi?" data-confirm-btn="Ya, Batalkan">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-100 border border-slate-200 transition-colors cursor-pointer">
                                <i class="bi bi-arrow-counterclockwise"></i> Batalkan Publikasi
                            </button>
                        </form>
                        <form action="{{ route('admin.jadwal.batal') }}" method="POST" class="form-delete" data-confirm="Apakah Anda yakin ingin MENGHAPUS SEMUA JADWAL? Status liga akan kembali ke draft dan jadwal dikosongkan." data-confirm-title="Hapus Semua Jadwal?" data-confirm-btn="Ya, Hapus Semua">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors cursor-pointer">
                                <i class="bi bi-trash3-fill"></i> Batalkan &amp; Hapus Jadwal
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-slate-400 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                            <i class="bi bi-lock-fill text-slate-400 mr-1"></i> Jadwal terkunci (pertandingan sudah berjalan)
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <!-- Fitur Percepat / Perlambat Jadwal -->
        <div class="lg:col-span-12 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-2 pb-3 mb-3 border-b border-slate-100">
                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="bi bi-clock-history"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Percepat / Perlambat Jadwal Pertandingan</h2>
                    <p class="text-3xs text-slate-400">Gunakan bila pertandingan selesai lebih cepat atau molor dari jadwal rencana.</p>
                </div>
            </div>

            <form action="{{ route('admin.jadwal.geser') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end" data-confirm="Terapkan pergeseran waktu untuk pertandingan yang dipilih?" data-confirm-title="Geser Waktu Pertandingan?" data-confirm-btn="Ya, Terapkan">
                @csrf
                <div class="sm:col-span-5">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mulai Dari Pertandingan</label>
                    <select name="id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" required>
                        @foreach ($matches as $m)
                            <option value="{{ $m->id }}">
                                #{{ $m->nomor_pertandingan }} &middot; {{ \App\Services\TournamentService::tglId($m->tanggal) }} {{ \App\Services\TournamentService::jamId($m->waktu) }} ({{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }} vs {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }} - {{ $m->lokasi }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Geser Waktu</label>
                    <select name="menit" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus" required>
                        <option value="-15">⚡ Percepat 15 Menit (-15m)</option>
                        <option value="-30">⚡ Percepat 30 Menit (-30m)</option>
                        <option value="-45">⚡ Percepat 45 Menit (-45m)</option>
                        <option value="15">⏳ Perlambat 15 Menit (+15m)</option>
                        <option value="30" selected>⏳ Perlambat 30 Menit (+30m)</option>
                        <option value="45">⏳ Perlambat 45 Menit (+45m)</option>
                        <option value="60">⏳ Perlambat 60 Menit (+60m)</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cakupan</label>
                    <select name="cakupan" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                        <option value="semua_setelahnya">Match ini &amp; setelahnya (di hari tsb)</option>
                        <option value="hanya_satu">Hanya match ini saja</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="w-full py-2 px-3 rounded-xl text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-xs cursor-pointer">
                        Terapkan Geser Waktu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Match Table -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="py-3 px-4 w-12 text-center">#</th>
                        <th scope="col" class="py-3 px-4">Hari &amp; Tanggal</th>
                        <th scope="col" class="py-3 px-4">Waktu</th>
                        <th scope="col" class="py-3 px-4">Pertandingan</th>
                        <th scope="col" class="py-3 px-4">Meja / Lokasi</th>
                        <th scope="col" class="py-3 px-4">Status</th>
                        <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($matches as $m)
                        @php
                            $tglStr = $m->tanggal ? \Carbon\Carbon::parse($m->tanggal)->format('Y-m-d') : null;
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-400">
                                {{ $m->nomor_pertandingan }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800">
                                    {{ $tglStr ? \App\Services\TournamentService::hariId($tglStr) : '-' }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ $tglStr ? \App\Services\TournamentService::tglId($tglStr) : 'Belum Ditentukan' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs font-semibold text-slate-700">
                                {{ \App\Services\TournamentService::jamId($m->waktu) }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900">
                                    {{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}
                                    <span class="text-xs text-slate-400 font-normal px-1">vs</span>
                                    {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}
                                </div>
                                @if ($m->status === 'selesai' || $m->status === 'berlangsung')
                                    <span class="inline-flex items-center font-mono text-2xs px-2 py-0.5 rounded bg-slate-900 text-white font-bold mt-0.5">
                                        Skor: {{ (int) $m->skor_home }} - {{ (int) $m->skor_away }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap font-medium text-slate-700">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                    <i class="bi bi-grid-fill text-indigo-500 text-3xs"></i> {{ $m->lokasi ?: 'Meja' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                {!! \App\Services\TournamentService::badgeStatus($m->status) !!}
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                <button type="button"
                                        onclick="openSlotModal({{ $m->id }}, '{{ $tglStr }}', '{{ substr($m->waktu ?? '', 0, 5) }}', '{{ addslashes($m->lokasi ?? '') }}', '{{ addslashes($m->homeTeam->nama_tim ?? '') }}', '{{ addslashes($m->awayTeam->nama_tim ?? '') }}')"
                                        class="inline-flex items-center p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 transition-colors" title="Edit Waktu / Meja">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="{{ route('admin.pertandingan.index', ['tanggal' => $tglStr]) }}#m{{ $m->id }}"
                                   class="inline-flex items-center p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 transition-colors" title="Input Skor">
                                    <i class="bi bi-trophy"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Modal Edit Slot Jadwal -->
<div id="slotModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-900">Ubah Slot Pertandingan</h3>
                <p id="slotModalSubtitle" class="text-xs text-slate-400 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeSlotModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="slotForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal</label>
                <input type="date" id="slotTanggal" name="tanggal" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Waktu / Jam</label>
                <input type="time" id="slotWaktu" name="waktu" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokasi / Meja</label>
                <input type="text" id="slotLokasi" name="lokasi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="60" required>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2">
                <button type="button" onclick="closeSlotModal()" class="py-2.5 px-4 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function pilihHariPreset(days) {
    document.querySelectorAll('input[name="hari[]"]').forEach(cb => {
        const val = parseInt(cb.value, 10);
        cb.checked = days.includes(val);
        updateHariPill(cb);
    });
}

function updateHariPill(cb) {
    const label = cb.closest('label.hari-pill');
    if (!label) return;
    if (cb.checked) {
        label.classList.add('bg-indigo-50', 'border-indigo-200', 'text-indigo-900', 'font-bold', 'shadow-2xs');
        label.classList.remove('bg-white', 'border-slate-200', 'text-slate-600');
    } else {
        label.classList.remove('bg-indigo-50', 'border-indigo-200', 'text-indigo-900', 'font-bold', 'shadow-2xs');
        label.classList.add('bg-white', 'border-slate-200', 'text-slate-600');
    }
}

function tambahTanggalLibur() {
    const input = document.getElementById('inputTanggalLibur');
    const val = input.value;
    if (!val) return;

    // Check duplicate
    const existing = document.querySelectorAll('#containerLibur input[name="libur[]"]');
    for (let el of existing) {
        if (el.value === val) {
            alert('Tanggal tersebut sudah ada di daftar libur khusus.');
            return;
        }
    }

    const notice = document.getElementById('emptyLiburNotice');
    if (notice) notice.remove();

    const span = document.createElement('span');
    span.className = 'libur-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white text-rose-700 text-xs font-semibold border border-rose-200 shadow-2xs';
    span.innerHTML = `
        <i class="bi bi-calendar-x text-rose-500 text-xs"></i>
        <span>${val}</span>
        <input type="hidden" name="libur[]" value="${val}">
        <button type="button" onclick="hapusTanggalLibur(this)" class="text-slate-400 hover:text-rose-600 transition-colors ml-1 font-bold text-sm cursor-pointer" title="Hapus">&times;</button>
    `;
    document.getElementById('containerLibur').appendChild(span);
    input.value = '';
}

function hapusTanggalLibur(btn) {
    btn.closest('.libur-badge').remove();
    const container = document.getElementById('containerLibur');
    if (container.querySelectorAll('.libur-badge').length === 0) {
        container.innerHTML = '<span id="emptyLiburNotice" class="text-3xs text-slate-400 italic">Belum ada tanggal libur khusus yang ditambahkan (opsional).</span>';
    }
}

function openSlotModal(id, tanggal, waktu, lokasi, home, away) {
    const form = document.getElementById('slotForm');
    form.action = "{{ url('admin/jadwal/slot') }}/" + id;
    document.getElementById('slotModalSubtitle').textContent = `#${id} · ${home} vs ${away}`;
    document.getElementById('slotTanggal').value = tanggal;
    document.getElementById('slotWaktu').value = waktu;
    document.getElementById('slotLokasi').value = lokasi;
    const modal = document.getElementById('slotModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeSlotModal() {
    const modal = document.getElementById('slotModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection
