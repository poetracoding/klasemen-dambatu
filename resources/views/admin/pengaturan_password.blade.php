@extends('layouts.admin')

@section('title', 'Ganti Password Admin')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Ganti Password Admin</h1>
    <p class="text-sm text-slate-500 mt-1">Perbarui kata sandi untuk mengamankan akses panel manajemen turnamen.</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100">
            <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-2xs">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Keamanan Akun Administrator</h2>
                <p class="text-xs text-slate-400 mt-0.5">Gunakan minimal 8 karakter yang sulit ditebak.</p>
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
@endsection
