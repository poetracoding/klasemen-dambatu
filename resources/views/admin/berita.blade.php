@extends('layouts.admin')

@section('title', 'Kelola Berita Turnamen')

@section('content')
@php
    $pubCount = $beritas->where('status', 'published')->count();
    $draftCount = $beritas->where('status', 'draft')->count();
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
            <i class="bi bi-newspaper text-indigo-600"></i>
            <span>Kelola Berita</span>
        </h1>
        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            {{ $beritas->count() }} Artikel
        </span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            {{ $pubCount }} Terbit
        </span>
        @if ($draftCount > 0)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                {{ $draftCount }} Draft
            </span>
        @endif
    </div>
    <div>
        <a href="{{ route('berita.index') }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Berita
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Form Tambah Berita -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs sticky top-24">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-pencil-square text-indigo-500"></i> Tulis Berita Baru
                </h2>
            </div>

            <form action="{{ route('admin.berita.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Berita <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" required maxlength="180" value="{{ old('judul') }}"
                        class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400"
                        placeholder="contoh: Rekap Laga Sengit Pekan Pertama">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori <span class="text-rose-500">*</span></label>
                        <select name="kategori" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus">
                            @foreach (\App\Models\Berita::KATEGORI_LIST as $kat)
                                <option value="{{ $kat }}" {{ old('kategori') === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status <span class="text-rose-500">*</span></label>
                        <select name="status" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus">
                            <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Publikasikan</option>
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Simpan Draft</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ringkasan Singkat <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="text" name="ringkasan" maxlength="300" value="{{ old('ringkasan') }}"
                        class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400"
                        placeholder="Cuplikan singkat untuk kartu berita">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Isi Berita <span class="text-rose-500">*</span></label>
                    <textarea name="isi" rows="6" required maxlength="20000"
                        class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400"
                        placeholder="Tulis berita atau liputan pertandingan di sini...">{{ old('isi') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gambar Sampul <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <input type="file" id="beritaUploadInput" name="gambar" accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl bg-slate-50/50 p-1.5 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Format JPG, PNG, WEBP &middot; Maksimal 5MB.</p>
                    <div id="beritaUploadPreviewWrap" class="hidden mt-2.5 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 h-32">
                        <img id="beritaUploadPreviewImg" src="" alt="Preview" class="w-full h-full object-cover">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer flex items-center justify-center gap-2">
                        <i class="bi bi-send-fill"></i>
                        <span>Simpan Berita</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Berita -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Berita &amp; Liputan</h2>
                    <p class="text-xs text-slate-400 mt-0.5">URL artikel otomatis dibuat dengan slug yang rapi dan unik.</p>
                </div>
                <span class="text-xs text-slate-400">Total: {{ $beritas->count() }}</span>
            </div>

            @if ($beritas->isEmpty())
                <div class="p-12 text-center text-slate-400 text-sm">
                    <i class="bi bi-newspaper text-4xl text-slate-300 mb-2 block"></i>
                    Belum ada berita yang ditulis. Silakan buat melalui form di samping.
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($beritas as $b)
                        <div class="p-5 hover:bg-slate-50/60 transition-colors {{ $b->status === 'draft' ? 'opacity-75' : '' }}">
                            <div class="flex items-start gap-4">
                                @if ($b->gambar_url)
                                    <img src="{{ $b->gambar_url }}" alt="{{ $b->judul }}" class="w-20 h-16 rounded-xl object-cover border border-slate-200 shrink-0">
                                @else
                                    <div class="w-20 h-16 rounded-xl bg-indigo-50 text-indigo-400 border border-indigo-100 flex items-center justify-center shrink-0">
                                        <i class="bi bi-newspaper text-xl"></i>
                                    </div>
                                @endif

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/70">
                                            {{ $b->kategori }}
                                        </span>
                                        <form action="{{ route('admin.berita.toggle', $b->id) }}" method="POST" class="inline">
                                            @csrf
                                            @if ($b->status === 'published')
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors cursor-pointer" title="Klik untuk ubah ke Draft">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Terbit
                                                </button>
                                            @else
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition-colors cursor-pointer" title="Klik untuk Publikasikan">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Draft
                                                </button>
                                            @endif
                                        </form>
                                        <span class="text-[11px] text-slate-400 font-mono">
                                            {{ ($b->published_at ?? $b->created_at)->format('d/m/Y H:i') }}
                                        </span>
                                    </div>

                                    <h3 class="font-bold text-slate-900 text-sm leading-snug">
                                        @if ($b->status === 'published')
                                            <a href="{{ route('berita.show', $b->slug) }}" target="_blank" class="hover:text-indigo-600 transition-colors">
                                                {{ $b->judul }}
                                            </a>
                                        @else
                                            {{ $b->judul }}
                                        @endif
                                    </h3>

                                    <div class="text-[11px] text-slate-400 font-mono truncate mt-0.5">
                                        /berita/{{ $b->slug }}
                                    </div>

                                    <p class="text-xs text-slate-500 mt-1.5 line-clamp-2">{{ $b->excerpt }}</p>

                                    <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-2.5 border-t border-slate-100">
                                        <div class="flex items-center gap-3 text-xs text-slate-500 font-mono">
                                            <span title="Jumlah dibaca"><i class="bi bi-eye text-indigo-500 mr-1"></i>{{ number_format($b->views_count) }}</span>
                                            <span title="Jumlah disukai"><i class="bi bi-heart-fill text-rose-500 mr-1"></i>{{ number_format($b->likes_count) }}</span>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            @if ($b->status === 'published')
                                                <a href="{{ route('berita.show', $b->slug) }}" target="_blank" class="inline-flex items-center p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition-colors" title="Buka Berita">
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            @endif

                                            <button type="button"
                                                onclick="openEditBeritaModal(this)"
                                                data-id="{{ $b->id }}"
                                                data-judul="{{ $b->judul }}"
                                                data-kategori="{{ $b->kategori }}"
                                                data-ringkasan="{{ $b->ringkasan ?? '' }}"
                                                data-isi="{{ $b->isi }}"
                                                data-status="{{ $b->status }}"
                                                data-gambar="{{ $b->gambar_url ?? '' }}"
                                                class="inline-flex items-center p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 transition-colors cursor-pointer" title="Edit Berita">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <form action="{{ route('admin.berita.destroy', $b->id) }}" method="POST" class="inline form-delete" data-confirm="Yakin ingin menghapus berita ini secara permanen?" data-confirm-title="Hapus Berita?" data-confirm-btn="Ya, Hapus">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Berita">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal Edit Berita -->
<div id="editBeritaModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-xl w-full shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-pencil-square text-indigo-500"></i> Edit Berita
            </h3>
            <button type="button" onclick="closeEditBeritaModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="editBeritaForm" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div id="editBeritaGambarWrap" class="hidden rounded-2xl border border-slate-200 bg-slate-50 p-2 text-center">
                <img id="editBeritaGambarPreview" src="" alt="Preview Sampul" class="max-h-36 mx-auto rounded-xl object-contain">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul Berita <span class="text-rose-500">*</span></label>
                <input id="editBeritaJudul" name="judul" required maxlength="180" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori</label>
                    <select id="editBeritaKategori" name="kategori" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus">
                        @foreach (\App\Models\Berita::KATEGORI_LIST as $kat)
                            <option value="{{ $kat }}">{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status</label>
                    <select id="editBeritaStatus" name="status" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3 text-slate-800 form-input-focus">
                        <option value="published">Terbit</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ringkasan Singkat</label>
                <input id="editBeritaRingkasan" name="ringkasan" maxlength="300" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Isi Berita <span class="text-rose-500">*</span></label>
                <textarea id="editBeritaIsi" name="isi" rows="6" required maxlength="20000" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ganti Gambar Sampul <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl bg-slate-50/50 p-1.5 cursor-pointer">
            </div>

            <div class="pt-3 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditBeritaModal()" class="py-2.5 px-4 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
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
    const input = document.getElementById('beritaUploadInput');
    const wrap = document.getElementById('beritaUploadPreviewWrap');
    const img = document.getElementById('beritaUploadPreviewImg');
    if (input && wrap && img) {
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) {
                wrap.classList.add('hidden');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
                wrap.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    }
});

function openEditBeritaModal(btn) {
    const form = document.getElementById('editBeritaForm');
    form.action = "{{ url('admin/berita') }}/" + btn.dataset.id;
    document.getElementById('editBeritaJudul').value = btn.dataset.judul || '';
    document.getElementById('editBeritaKategori').value = btn.dataset.kategori || 'Berita';
    document.getElementById('editBeritaStatus').value = btn.dataset.status || 'published';
    document.getElementById('editBeritaRingkasan').value = btn.dataset.ringkasan || '';
    document.getElementById('editBeritaIsi').value = btn.dataset.isi || '';

    const gWrap = document.getElementById('editBeritaGambarWrap');
    const gImg = document.getElementById('editBeritaGambarPreview');
    if (btn.dataset.gambar) {
        gImg.src = btn.dataset.gambar;
        gWrap.classList.remove('hidden');
    } else {
        gWrap.classList.add('hidden');
    }

    const modal = document.getElementById('editBeritaModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditBeritaModal() {
    const modal = document.getElementById('editBeritaModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection
