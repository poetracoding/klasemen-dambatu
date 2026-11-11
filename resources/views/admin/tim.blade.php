@extends('layouts.admin')

@section('title', 'Kelola Tim & Pemain')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tim &amp; Pemain</h1>
        <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
            {{ $tims->count() }} Tim
        </span>
    </div>
    <div class="text-xs text-slate-500">
        Format Tim: Tepat 2 Pemain per Tim
    </div>
</div>

@if (!$draft)
    <div class="mb-6 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-900 text-xs sm:text-sm flex items-center gap-3 shadow-xs">
        <i class="bi bi-info-circle-fill text-sky-600 text-lg flex-shrink-0"></i>
        <div>Jadwal liga sudah dipublikasikan: tim tidak dapat ditambah atau dihapus. Anda tetap dapat memperbarui nama tim dan nama pemain.</div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Form Tambah Tim (jika draft) -->
    <div class="lg:col-span-4">
        @if ($draft)
            <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs sticky top-24">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-person-plus text-indigo-500"></i> Tambah Tim Baru
                    </h2>
                </div>

                <form action="{{ route('admin.tim.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Tim</label>
                        <input name="nama_tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="60" placeholder="contoh: Garuda Muda" required value="{{ old('nama_tim') }}">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemain 1</label>
                        <input name="pemain1" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="60" placeholder="Nama pemain 1" required value="{{ old('pemain1') }}">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemain 2</label>
                        <input name="pemain2" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus placeholder-slate-400" maxlength="60" placeholder="Nama pemain 2" required value="{{ old('pemain2') }}">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                            Tambah Tim
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-6 text-center text-xs text-slate-500 shadow-xs">
                <i class="bi bi-lock text-3xl text-slate-400 mb-2 block"></i>
                Jadwal telah dipublikasikan.<br>Penambahan tim dinonaktifkan.
            </div>
        @endif
    </div>

    <!-- Tabel Daftar Tim -->
    <div class="{{ $draft ? 'lg:col-span-8' : 'lg:col-span-12' }}">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900">Daftar Tim Peserta</h2>
                <span class="text-xs text-slate-400">Total: {{ $tims->count() }} tim</span>
            </div>

            @if ($tims->isEmpty())
                <div class="p-12 text-center text-slate-400 text-sm">
                    Belum ada tim yang ditambahkan.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th scope="col" class="py-3 px-4 w-12 text-center">#</th>
                                <th scope="col" class="py-3 px-4">Nama Tim</th>
                                <th scope="col" class="py-3 px-4">Pemain 1</th>
                                <th scope="col" class="py-3 px-4">Pemain 2</th>
                                <th scope="col" class="py-3 px-4 text-right w-28">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($tims as $i => $t)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3.5 px-4 text-center font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                                    <td class="py-3.5 px-4 font-bold text-slate-900">{{ $t->nama_tim }}</td>
                                    <td class="py-3.5 px-4 text-slate-700">{{ $t->pemain1 }}</td>
                                    <td class="py-3.5 px-4 text-slate-700">{{ $t->pemain2 }}</td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1">
                                        <button type="button"
                                                onclick="openEditModal({{ $t->id }}, '{{ addslashes($t->nama_tim) }}', '{{ addslashes($t->pemain1) }}', '{{ addslashes($t->pemain2) }}')"
                                                class="inline-flex items-center p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 transition-colors" title="Edit Tim">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        @if ($draft)
                                            <form action="{{ route('admin.tim.destroy', $t->id) }}" method="POST" class="inline form-delete" data-confirm="Yakin ingin menghapus tim {{ addslashes($t->nama_tim) }}? Data tim dan riwayat pertandingan terkait akan dihapus permanen." data-confirm-title="Hapus Tim?" data-confirm-btn="Ya, Hapus">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer" title="Hapus Tim">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
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

<!-- Modal Edit Tim -->
<div id="editModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-pencil-square text-indigo-500"></i> Edit Data Tim
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Tim</label>
                <input id="editNamaTim" name="nama_tim" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required maxlength="60">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemain 1</label>
                <input id="editPemain1" name="pemain1" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required maxlength="60">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Pemain 2</label>
                <input id="editPemain2" name="pemain2" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-slate-800 form-input-focus" required maxlength="60">
            </div>

            <div class="pt-3 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="py-2.5 px-4 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition-colors">
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
function openEditModal(id, nama, p1, p2) {
    const form = document.getElementById('editForm');
    form.action = "{{ url('admin/tim') }}/" + id;
    document.getElementById('editNamaTim').value = nama;
    document.getElementById('editPemain1').value = p1;
    document.getElementById('editPemain2').value = p2;
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>
@endsection

