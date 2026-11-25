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

        <form action="{{ route('admin.pengaturan.update_liga') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Liga / Aplikasi</label>
                    <input type="text" name="nama_liga" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required maxlength="80" value="{{ old('nama_liga', $liga->nama_liga) }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Musim / Edisi</label>
                    <input type="text" name="musim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="40" value="{{ old('musim', $liga->musim) }}">
                </div>
            </div>

            <!-- Icon Aplikasi (Favicon pada Title & Brand) -->
            @php $currentIconUrl = $liga->appIconUrl(); @endphp
            <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-800">Icon Aplikasi (Favicon Title &amp; Logo Navbar)</label>
                        <p class="text-[11px] text-slate-500 mt-0.5">Tampil pada tab browser (title), bookmark, manifest PWA, dan logo navbar.</p>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-white border border-slate-200 flex items-center justify-center shadow-2xs shrink-0 overflow-hidden">
                        @if ($currentIconUrl)
                            <img src="{{ $currentIconUrl }}" alt="Icon Aplikasi" class="w-full h-full object-contain p-1">
                        @else
                            <i class="bi bi-grid-3x3-gap-fill text-indigo-500 text-lg"></i>
                        @endif
                    </div>
                </div>

                <input type="file" name="app_icon" accept=".png,.jpg,.jpeg,.webp,.ico"
                    class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl bg-white p-1.5 cursor-pointer">
                <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                    <span>Format: PNG, JPG, WEBP, atau ICO (disarankan rasio 1:1, maks. 1 MB).</span>
                    @if ($currentIconUrl)
                        <label class="inline-flex items-center gap-1.5 font-semibold text-rose-600 cursor-pointer">
                            <input type="checkbox" name="hapus_app_icon" value="1" class="rounded border-slate-300 text-rose-600">
                            <span>Hapus icon kustom</span>
                        </label>
                    @endif
                </div>
            </div>

            <!-- Konfigurasi Aplikasi Lainnya -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tagline / Deskripsi Singkat (SEO Meta)</label>
                    <input type="text" name="app_tagline" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="160" placeholder="Portal resmi klasemen & jadwal turnamen" value="{{ old('app_tagline', $liga->app_tagline) }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Warna Tema Browser / PWA</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="themeColorPicker" value="{{ old('theme_color', $liga->theme_color ?: '#4f46e5') }}" class="h-10 w-12 rounded-xl border border-slate-200 bg-white p-1 cursor-pointer shrink-0">
                        <input type="text" id="themeColorInput" name="theme_color" class="w-full text-sm font-mono rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="20" placeholder="#4f46e5" value="{{ old('theme_color', $liga->theme_color ?: '#4f46e5') }}">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Teks Footer Kustom</label>
                    <input type="text" name="footer_text" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="160" placeholder="Sistem Manajemen Turnamen Dam Batu · Laravel 12" value="{{ old('footer_text', $liga->footer_text) }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kontak Panitia / Penyelenggara</label>
                    <input type="text" name="kontak_panitia" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="120" placeholder="contoh: WhatsApp Panitia / Sekretariat" value="{{ old('kontak_panitia', $liga->kontak_panitia) }}">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-semibold text-slate-700">Konten Halaman "Tentang Aplikasi"</label>
                    <a href="{{ route('tentang') }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:underline">
                        <span>Lihat Halaman</span>
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
                <textarea name="tentang_aplikasi" rows="4" maxlength="5000"
                    class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus"
                    placeholder="Tulis profil liga, tujuan aplikasi, atau informasi penyelenggara (kosongkan untuk memakai deskripsi bawaan)...">{{ old('tentang_aplikasi', $liga->tentang_aplikasi) }}</textarea>
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

        @if ($fcmEnabled && !$fcmVapidValid)
            <div class="mb-4 p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-xs text-amber-800 leading-relaxed">
                <span class="font-bold">FCM_VAPID_KEY</span> di <span class="font-mono">.env</span> masih kosong atau masih berisi placeholder. Ambil Key Pair asli (~87 karakter) dari <strong>Firebase Console &rarr; Cloud Messaging &rarr; Web Push certificates</strong>, lalu jalankan <span class="font-mono">php artisan config:clear</span>.
            </div>
        @endif

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

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var picker = document.getElementById('themeColorPicker');
    var input = document.getElementById('themeColorInput');
    if (picker && input) {
        picker.addEventListener('input', function () {
            input.value = picker.value;
        });
        input.addEventListener('input', function () {
            if (/^#[0-9A-Fa-f]{6}$/.test(input.value.trim())) {
                picker.value = input.value.trim();
            }
        });
    }
});
</script>
@endsection
