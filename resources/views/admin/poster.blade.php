@extends('layouts.admin')

@section('title', 'Kelola Poster Informasi')

@section('content')
@php
    $aktifCount = $posters->where('is_active', true)->count();
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Poster Informasi</h1>
        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            {{ $posters->count() }} Poster
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            {{ $aktifCount }} Aktif
        </span>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('home') }}#informasiSection" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-colors shadow-xs">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Informasi
        </a>
    </div>
</div>

@if ($aktifCount > 1)
    <div class="mb-6 p-4 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-900 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
        <i class="bi bi-collection-play-fill text-indigo-600 text-lg flex-shrink-0"></i>
        <div>Mode <strong>Slide Otomatis Aktif</strong>: terdapat {{ $aktifCount }} poster aktif, sehingga gambar pada bagian <strong>Informasi</strong> akan berganti otomatis di halaman depan.</div>
    </div>
@elseif ($aktifCount === 0)
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
        <i class="bi bi-eye-slash-fill text-amber-600 text-lg flex-shrink-0"></i>
        <div>Tidak ada poster yang aktif saat ini. Card <strong>Informasi</strong> disembunyikan di halaman utama sampai ada minimal 1 poster aktif.</div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Form Upload Poster -->
    <div class="lg:col-span-4">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs sticky top-24">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-cloud-arrow-up text-indigo-500"></i> Upload Poster Baru
                </h2>
            </div>

            <form action="{{ route('admin.poster.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Poster <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="judul" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="120" placeholder="contoh: Peraturan & Tata Tertib" value="{{ old('judul') }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan Singkat <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="deskripsi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="255" placeholder="contoh: Pedoman resmi pelaksanaan liga" value="{{ old('deskripsi') }}">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">File Gambar Poster <span class="text-rose-500">*</span></label>
                    <input type="file" id="posterUploadInput" name="posters[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required
                        class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl bg-slate-50/50 p-1.5 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1.5">
                        Bisa pilih lebih dari 1 file sekaligus (JPG, PNG, WEBP, GIF &middot; maks 10MB/file).
                    </p>
                    <div id="uploadPreviewGrid" class="hidden grid grid-cols-3 gap-2 mt-3"></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Tampil</label>
                    <select name="is_active" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
                        <option value="1" selected>Aktif (Tampilkan di Informasi)</option>
                        <option value="0">Nonaktif (Simpan sebagai arsip)</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer flex items-center justify-center gap-2">
                        <i class="bi bi-upload"></i>
                        <span>Upload Poster</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Poster -->
    <div class="lg:col-span-8">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Poster Informasi</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Kelola gambar yang tampil di card Informasi halaman utama.</p>
                </div>
                <span class="text-xs text-slate-400">Total: {{ $posters->count() }} poster</span>
            </div>

            @if ($posters->isEmpty())
                <div class="p-12 text-center text-slate-400 text-sm">
                    <i class="bi bi-images text-4xl text-slate-300 mb-2 block"></i>
                    Belum ada poster yang diunggah. Silakan upload melalui formulir di samping.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="py-3 px-4 w-14 text-center">Urut</th>
                                <th scope="col" class="py-3 px-4 w-28">Preview</th>
                                <th scope="col" class="py-3 px-4">Judul &amp; Keterangan</th>
                                <th scope="col" class="py-3 px-4 text-center w-36">Status</th>
                                <th scope="col" class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($posters as $p)
                                <tr class="hover:bg-slate-50/60 transition-colors {{ !$p->is_active ? 'opacity-70' : '' }}">
                                    <td class="py-3.5 px-4 text-center font-mono text-xs font-bold text-slate-500">
                                        #{{ $p->urutan }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <a href="{{ $p->url }}" target="_blank" class="block w-20 h-14 rounded-xl overflow-hidden border border-slate-200 bg-slate-100 group relative" title="Buka gambar penuh">
                                            <img src="{{ $p->url }}" alt="{{ $p->judul ?? 'Poster Informasi' }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                        </a>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900">{{ $p->judul ?: 'Poster Informasi #' . $p->id }}</div>
                                        @if ($p->deskripsi)
                                            <div class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ $p->deskripsi }}</div>
                                        @endif
                                        <div class="text-[11px] text-slate-400 font-mono mt-1 truncate max-w-[220px]">{{ $p->file_path }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <form action="{{ route('admin.poster.toggle', $p->id) }}" method="POST" class="inline">
                                            @csrf
                                            @if ($p->is_active)
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer" title="Klik untuk menonaktifkan">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Aktif
                                                </button>
                                            @else
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200 transition-colors cursor-pointer" title="Klik untuk mengaktifkan">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    Nonaktif
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                        <button type="button"
                                                onclick="openEditPosterModal(this)"
                                                data-id="{{ $p->id }}"
                                                data-judul="{{ $p->judul ?? '' }}"
                                                data-deskripsi="{{ $p->deskripsi ?? '' }}"
                                                data-urutan="{{ $p->urutan }}"
                                                data-active="{{ $p->is_active ? '1' : '0' }}"
                                                data-url="{{ $p->url }}"
                                                class="inline-flex items-center p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 transition-colors cursor-pointer" title="Edit Poster">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <form action="{{ route('admin.poster.destroy', $p->id) }}" method="POST" class="inline form-delete" data-confirm="Yakin ingin menghapus poster {{ addslashes($p->judul ?: ('#' . $p->id)) }}? Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus Poster?" data-confirm-btn="Ya, Hapus">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Poster">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Edit Poster -->
<div id="editPosterModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-pencil-square text-indigo-500"></i> Edit Poster Informasi
            </h3>
            <button type="button" onclick="closeEditPosterModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="editPosterForm" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-2 text-center">
                <img id="editPosterPreview" src="" alt="Preview Poster" class="max-h-40 mx-auto rounded-xl object-contain">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Poster <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input id="editPosterJudul" name="judul" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="120" placeholder="Judul poster">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan Singkat <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input id="editPosterDeskripsi" name="deskripsi" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" maxlength="255" placeholder="Keterangan singkat">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Urutan Tampil</label>
                    <input type="number" id="editPosterUrutan" name="urutan" min="1" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                    <select id="editPosterActive" name="is_active" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ganti Gambar <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input type="file" name="poster" accept="image/jpeg,image/png,image/webp,image/gif"
                    class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl bg-slate-50/50 p-1.5 cursor-pointer">
            </div>

            <div class="pt-3 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditPosterModal()" class="py-2.5 px-4 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="py-2.5 px-5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadInput = document.getElementById('posterUploadInput');
    const previewGrid = document.getElementById('uploadPreviewGrid');
    if (uploadInput && previewGrid) {
        uploadInput.addEventListener('change', function () {
            previewGrid.innerHTML = '';
            const files = Array.from(this.files || []);
            if (files.length === 0) {
                previewGrid.classList.add('hidden');
                return;
            }
            previewGrid.classList.remove('hidden');
            files.forEach(function (file) {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function (e) {
                    const wrap = document.createElement('div');
                    wrap.className = 'rounded-xl overflow-hidden border border-slate-200 bg-slate-100 h-20';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-full h-full object-cover';
                    wrap.appendChild(img);
                    previewGrid.appendChild(wrap);
                };
                reader.readAsDataURL(file);
            });
        });
    }
});

function openEditPosterModal(btn) {
    const form = document.getElementById('editPosterForm');
    form.action = "{{ url('admin/poster') }}/" + btn.dataset.id;
    document.getElementById('editPosterJudul').value = btn.dataset.judul || '';
    document.getElementById('editPosterDeskripsi').value = btn.dataset.deskripsi || '';
    document.getElementById('editPosterUrutan').value = btn.dataset.urutan || 1;
    document.getElementById('editPosterActive').value = btn.dataset.active || '1';
    document.getElementById('editPosterPreview').src = btn.dataset.url || '';

    const modal = document.getElementById('editPosterModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditPosterModal() {
    const modal = document.getElementById('editPosterModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection
