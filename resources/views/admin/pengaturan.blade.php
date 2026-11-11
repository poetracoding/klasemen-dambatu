@extends('layouts.admin')

@section('title', 'Pengaturan Liga & Sistem')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Pengaturan Liga &amp; Sistem</h1>
    <p class="text-sm text-slate-500 mt-1">Kelola identitas liga, keamanan akun admin, dan backup basis data turnamen.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Kolom Kiri: Profil Liga & Ganti Password -->
    <div class="lg:col-span-7 space-y-8">
        <!-- Card 1: Informasi Liga -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="bi bi-sliders"></i>
                </span>
                <h2 class="text-base font-bold text-slate-900">Informasi Liga</h2>
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

                <div class="pt-2">
                    <button type="submit" class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Keamanan Akun Admin -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100">
                <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-2xs">
                    <i class="bi bi-shield-lock-fill"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Ganti Password Admin</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Perbarui kata sandi untuk mengamankan akses panel manajemen turnamen.</p>
                </div>
            </div>

            <form action="{{ route('admin.pengaturan.ganti_password') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Saat Ini</label>
                    <input type="password" name="lama" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required placeholder="••••••••">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
                        <input type="password" name="baru" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required minlength="8" placeholder="Minimal 8 karakter">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ulangi Password Baru</label>
                        <input type="password" name="ulang" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required minlength="8" placeholder="Ulangi password baru">
                    </div>
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit" class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-colors shadow-xs cursor-pointer">
                        Perbarui Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kolom Kanan: Backup & Restore Data Database -->
    <div class="lg:col-span-5 space-y-8">
        <!-- Card 3: Download Backup Database -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
                <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-2xs">
                    <i class="bi bi-cloud-arrow-down-fill"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Backup Data Turnamen</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Unduh seluruh berkas data liga, tim, dan skor pertandingan.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed mb-5">
                Simpan cadangan data turnamen sewaktu-waktu sebagai arsip atau untuk dipindahkan ke server lain dengan aman.
            </p>

            <form action="{{ route('admin.pengaturan.backup') }}" method="POST">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs cursor-pointer">
                    <i class="bi bi-download"></i> Unduh File Backup (JSON)
                </button>
            </form>
        </div>

        <!-- Card 4: Upload / Restore Database -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
                <span class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shadow-2xs">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Restore / Unggah Database</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Pulihkan turnamen dari backup SQLite (.sqlite) atau JSON.</p>
                </div>
            </div>

            <div class="p-3.5 mb-5 rounded-2xl bg-amber-50/80 border border-amber-200/80 text-amber-900 text-xs flex items-start gap-2.5">
                <i class="bi bi-exclamation-triangle-fill text-amber-600 text-base flex-shrink-0 mt-0.5"></i>
                <div>
                    <strong>Peringatan Restore:</strong> Mengunggah berkas database akan menimpa seluruh data liga, jadwal, tim, dan skor saat ini.
                </div>
            </div>

            <form action="{{ route('admin.pengaturan.upload_db') }}" method="POST" enctype="multipart/form-data" class="space-y-4 form-delete" data-confirm="PERINGATAN: Seluruh data saat ini akan ditimpa dengan database yang diunggah. Apakah Anda yakin ingin melanjutkan?" data-confirm-title="Pulihkan Database?" data-confirm-btn="Ya, Pulihkan Sekarang">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Berkas (.sqlite atau .json)</label>
                    <input type="file" name="db_file" accept=".sqlite,.db,.sqlite3,.json" required
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl bg-slate-50/50 cursor-pointer">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                        <i class="bi bi-upload"></i> Unggah &amp; Pulihkan Turnamen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

