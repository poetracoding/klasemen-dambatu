@extends('layouts.app')

@section('title', $berita->judul)

@section('content')
<div class="mb-6 flex items-center justify-between gap-3">
    <a href="{{ route('berita.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
        <i class="bi bi-arrow-left"></i> Kembali ke Daftar Berita
    </a>
    <a href="{{ route('home') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800 transition-colors">
        Dashboard
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Artikel Utama -->
    <article class="lg:col-span-8 bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if ($berita->gambar_url)
            <div class="bg-slate-100 border-b border-slate-100">
                <img src="{{ $berita->gambar_url }}" alt="{{ $berita->judul }}" class="w-full max-h-[460px] object-cover">
            </div>
        @endif

        <div class="p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 mb-3">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    {{ $berita->kategori }}
                </span>
                <span>&middot;</span>
                <span><i class="bi bi-calendar3 mr-1"></i>{{ ($berita->published_at ?? $berita->created_at)->format('d M Y, H:i') }} WIB</span>
                @if ($berita->author)
                    <span>&middot;</span>
                    <span><i class="bi bi-person mr-1"></i>{{ $berita->author->name }}</span>
                @endif
                <span>&middot;</span>
                <span><i class="bi bi-eye mr-1"></i>{{ number_format($berita->views_count) }} dibaca</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight mb-5">
                {{ $berita->judul }}
            </h1>

            @if (!empty($berita->ringkasan))
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 text-sm text-slate-600 italic mb-6">
                    {{ $berita->ringkasan }}
                </div>
            @endif

            <!-- Konten aman terhadap XSS: di-escape via e() dan konversi baris baru -->
            <div class="text-sm sm:text-base text-slate-700 leading-relaxed space-y-4">
                {!! nl2br(e($berita->isi)) !!}
            </div>

            <!-- Bar Aksi: Reaksi Emoji & Bagikan -->
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                @include('partials.berita_reactions', [
                    'berita' => $berita,
                    'counts' => $reactionCounts ?? \App\Models\BeritaLike::emptyCounts(),
                    'userReaction' => $userReaction ?? null,
                    'compact' => false,
                ])

                <div class="flex flex-wrap items-center gap-2">
                    <a href="https://wa.me/?text={{ urlencode($berita->judul . ' - ' . route('berita.show', $berita->slug)) }}"
                        target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition-colors">
                        <i class="bi bi-whatsapp"></i> Bagikan WA
                    </a>
                    <button type="button" onclick="copyBeritaUrl(this)" data-copy-url="{{ route('berita.show', $berita->slug) }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                        <i class="bi bi-link-45deg"></i> <span>Salin Link</span>
                    </button>
                </div>
            </div>
        </div>
    </article>

    <!-- Sidebar Berita Lainnya -->
    <aside class="lg:col-span-4 space-y-4">
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-newspaper text-indigo-500"></i> Berita Lainnya
                </h2>
                <a href="{{ route('berita.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Semua &rarr;</a>
            </div>

            @if ($beritaTerkait->isEmpty())
                <div class="text-xs text-slate-400 py-4 text-center">Belum ada berita lainnya.</div>
            @else
                <div class="space-y-4">
                    @foreach ($beritaTerkait as $item)
                        <a href="{{ route('berita.show', $item->slug) }}" class="flex items-start gap-3 group">
                            @if ($item->gambar_url)
                                <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}" class="w-16 h-14 rounded-xl object-cover border border-slate-200 shrink-0">
                            @else
                                <div class="w-16 h-14 rounded-xl bg-indigo-50 text-indigo-400 border border-indigo-100 flex items-center justify-center shrink-0">
                                    <i class="bi bi-newspaper"></i>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold text-indigo-600">{{ $item->kategori }}</div>
                                <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                    {{ $item->judul }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5 font-mono">
                                    {{ ($item->published_at ?? $item->created_at)->format('d/m/Y') }} &middot; {{ $item->likes_count }} suka
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </aside>
</div>
@endsection

@section('scripts')
@include('partials.berita_like_script')
<script>
function copyBeritaUrl(btn) {
    const url = btn.getAttribute('data-copy-url');
    if (!url || !navigator.clipboard) return;
    navigator.clipboard.writeText(url).then(function () {
        const span = btn.querySelector('span');
        if (span) {
            span.textContent = 'Tersalin!';
            setTimeout(function () { span.textContent = 'Salin Link'; }, 2000);
        }
    });
}
</script>
@endsection
