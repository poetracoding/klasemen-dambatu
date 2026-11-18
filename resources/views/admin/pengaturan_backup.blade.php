@extends('layouts.admin')

@section('title', 'Backup & Restore Database')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Backup &amp; Restore Database</h1>
    <p class="text-sm text-slate-500 mt-1">Cadangkan atau pulihkan seluruh data liga, tim, jadwal, dan hasil pertandingan.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Card 1: Download Backup Database -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
                <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shadow-2xs">
                    <i class="bi bi-cloud-arrow-down-fill"></i>
                </span>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Backup Data Turnamen</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Unduh seluruh berkas data liga, tim, dan skor pertandingan.</p>
                </div>
            </div>

            <p class="text-sm text-slate-600 leading-relaxed mb-6">
                Simpan cadangan data turnamen sewaktu-waktu sebagai arsip keamanan atau untuk dipindahkan ke server lain dengan aman. Berkas cadangan berformat JSON yang berisi konfigurasi liga, daftar tim, jadwal, serta skor pertandingan.
            </p>
        </div>

        <form action="{{ route('admin.pengaturan.backup') }}" method="POST">
            @csrf
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-semibold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs cursor-pointer">
                <i class="bi bi-download"></i> Unduh File Backup (JSON)
            </button>
        </form>
    </div>

    <!-- Card 2: Upload / Restore Database -->
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
@endsection
