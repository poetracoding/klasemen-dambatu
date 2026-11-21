@extends('layouts.admin')

@section('title', 'Informasi Liga')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Informasi Liga</h1>
    <p class="text-sm text-slate-500 mt-1">Kelola identitas liga, musim pertandingan, dan pengaturan pelacakan publik.</p>
</div>

<div class="max-w-2xl space-y-6">
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex items-center gap-2.5 pb-4 mb-5 border-b border-slate-100">
            <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <i class="bi bi-sliders"></i>
            </span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Identitas &amp; Status Turnamen</h2>
                <p class="text-xs text-slate-400 mt-0.5">Perubahan nama liga akan tampil di seluruh halaman publik maupun dokumen PDF.</p>
            </div>
        </div>

        <form action="{{ route('admin.pengaturan.update_liga') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Liga</label>
                <input type="text" name="nama_liga" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required maxlength="80" value="{{ old('nama_liga', $liga->nama_liga) }}">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Musim / Edisi</label>
                <input type="text" name="musim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="40" value="{{ old('musim', $liga->musim) }}">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-slate-700">Google Analytics Measurement ID (GA4)</label>
                    @if (!empty($liga->google_analytics_id))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                            Nonaktif
                        </span>
                    @endif
                </div>
                <input type="text" name="google_analytics_id" class="w-full text-sm font-mono rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="40" placeholder="contoh: G-XXXXXXXXXX" value="{{ old('google_analytics_id', $liga->google_analytics_id) }}">
                <p class="text-[11px] text-slate-400 mt-1.5">
                    Opsional. Kosongkan jika hanya menggunakan Analytics Pengunjung bawaan sistem.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-2 pt-2">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                    <div class="text-xs text-slate-400">Status</div>
                    <div class="text-xs font-bold text-slate-800 mt-0.5">{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                    <div class="text-xs text-slate-400">Mulai</div>
                    <div class="text-xs font-bold text-slate-800 mt-0.5">{{ \App\Services\TournamentService::tglId($liga->tanggal_mulai) }}</div>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 text-center">
                    <div class="text-xs text-slate-400">Selesai</div>
                    <div class="text-xs font-bold text-slate-800 mt-0.5">{{ \App\Services\TournamentService::tglId($liga->tanggal_selesai) }}</div>
                </div>
            </div>

            <div class="text-xs text-slate-400 bg-slate-50/50 p-3 rounded-xl border border-slate-100">
                Aturan sistem: Single Round Robin, 2 pemain per tim, menang 3 poin, kalah 0 poin (tanpa draw). Tanggal mulai dan selesai ditentukan saat publikasi jadwal.
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Card Uji Coba & Status FCM Web Push -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-bell-fill"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Notifikasi Push Browser (FCM)</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Kirim notifikasi percobaan ke seluruh browser yang berlangganan.</p>
                </div>
            </div>
            @if ($fcmClientReady && $fcmServerReady)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Siap Digunakan
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Perlu Konfigurasi
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-5">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[11px] text-slate-400">Konfigurasi Web (.env)</div>
                <div class="text-xs font-bold mt-0.5 {{ $fcmClientReady ? 'text-emerald-700' : 'text-amber-700' }}">
                    {{ $fcmClientReady ? 'Siap' : ($fcmEnabled ? 'Belum Lengkap' : 'Nonaktif') }}
                </div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[11px] text-slate-400">Service Account JSON</div>
                <div class="text-xs font-bold mt-0.5 {{ $fcmServerReady ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $fcmServerReady ? 'Terdeteksi' : 'Tidak Ditemukan' }}
                </div>
            </div>
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                <div class="text-[11px] text-slate-400">Browser Terdaftar</div>
                <div class="text-xs font-bold text-slate-800 mt-0.5">
                    {{ $fcmTokenCount }} Perangkat
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5">{{ $fcmSkorCount }} Skor &middot; {{ $fcmBeritaCount }} Berita</div>
            </div>
        </div>

        @if (!$fcmServerReady)
            <div class="mb-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-800 leading-relaxed">
                Pastikan file Service Account JSON berada di <span class="font-mono font-semibold">{{ $fcmCredentialsPath ?: 'storage/app/private/firebase-service-account.json' }}</span> (perhatikan agar nama file tidak berakhiran ganda <span class="font-mono">.json.json</span>).
            </div>
        @endif

        <form action="{{ route('admin.pengaturan.test_fcm') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Notifikasi Tes</label>
                <input type="text" name="judul" maxlength="120"
                    class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus"
                    value="{{ old('judul', 'Tes Notifikasi Liga Dam Batu') }}">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Isi Pesan Tes</label>
                <input type="text" name="pesan" maxlength="255"
                    class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus"
                    value="{{ old('pesan', 'Jika notifikasi ini muncul, konfigurasi FCM Web Push Anda sudah aktif dan berhasil!') }}">
            </div>

            <div class="pt-1 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                    <i class="bi bi-bell-fill"></i>
                    <span>Kirim Test Notifikasi FCM</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
