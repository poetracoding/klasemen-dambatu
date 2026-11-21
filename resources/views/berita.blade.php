@extends('layouts.app')

@section('title', 'Berita & Liputan Turnamen')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
            <i class="bi bi-newspaper text-indigo-600"></i>
            <span>Berita &amp; Liputan</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Kabar terbaru, rekap pertandingan, dan pengumuman resmi {{ $liga->nama_liga }}.</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-8">
    <form class="grid grid-cols-1 sm:grid-cols-12 gap-3" method="get" action="{{ route('berita.index') }}">
        <div class="sm:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Kategori</label>
            <select name="kategori" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 px-3 text-slate-800 form-input-focus">
                <option value="">Semua Kategori</option>
                @foreach (\App\Models\Berita::KATEGORI_LIST as $kat)
                    <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-6">
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Cari Berita</label>
            <div class="relative">
                <i class="bi bi-search absolute left-3.5 top-2.5 text-xs text-slate-400"></i>
                <input type="search" name="q" class="w-full text-sm rounded-xl border border-slate-200 bg-slate-50/50 py-2 pl-9 pr-3 text-slate-800 form-input-focus" placeholder="Ketik judul atau kata kunci..." value="{{ $qSearch }}">
            </div>
        </div>
        <div class="sm:col-span-2 flex items-end gap-1.5">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-4 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-xs shadow-indigo-600/20 cursor-pointer">
                <i class="bi bi-funnel"></i> Cari
            </button>
            @if ($kategori !== '' || $qSearch !== '')
                <a href="{{ route('berita.index') }}" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            @endif
        </div>
    </form>
</div>

@if ($beritas->isEmpty())
    <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-12 text-center text-sm text-slate-500 shadow-xs">
        <i class="bi bi-newspaper text-4xl text-slate-400 mb-3 block"></i>
        Belum ada berita yang diterbitkan.
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach ($beritas as $b)
            @php
                $bCounts = $reactionCountsMap[$b->id] ?? \App\Models\BeritaLike::emptyCounts();
                $bUserReaction = $visitorReactions[$b->id] ?? null;
            @endphp
            <article class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col hover:border-indigo-300 transition-all">
                <a href="{{ route('berita.show', $b->slug) }}" class="block h-44 bg-slate-100 overflow-hidden relative group">
                    @if ($b->gambar_url)
                        <img src="{{ $b->gambar_url }}" alt="{{ $b->judul }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-indigo-500/15 to-violet-500/15 flex items-center justify-center text-indigo-400">
                            <i class="bi bi-newspaper text-4xl"></i>
                        </div>
                    @endif
                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-900/80 text-white backdrop-blur-xs">
                        {{ $b->kategori }}
                    </span>
                </a>

                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-[11px] text-slate-400 mb-2">
                            <span><i class="bi bi-calendar3 mr-1"></i>{{ ($b->published_at ?? $b->created_at)->format('d M Y') }}</span>
                            <span>&middot;</span>
                            <span><i class="bi bi-eye mr-1"></i>{{ number_format($b->views_count) }} dibaca</span>
                        </div>
                        <h2 class="text-base font-bold text-slate-900 leading-snug mb-2 line-clamp-2">
                            <a href="{{ route('berita.show', $b->slug) }}" class="hover:text-indigo-600 transition-colors">
                                {{ $b->judul }}
                            </a>
                        </h2>
                        <p class="text-xs text-slate-500 leading-relaxed line-clamp-3">{{ $b->excerpt }}</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-4 mt-4 border-t border-slate-100">
                        @include('partials.berita_reactions', [
                            'berita' => $b,
                            'counts' => $bCounts,
                            'userReaction' => $bUserReaction,
                            'compact' => true,
                        ])

                        <a href="{{ route('berita.show', $b->slug) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 inline-flex items-center gap-1 ml-auto">
                            Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $beritas->links() }}
    </div>
@endif
@endsection

@section('scripts')
    @include('partials.berita_like_script')
@endsection
