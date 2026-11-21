@php
    $counts = $counts ?? \App\Models\BeritaLike::emptyCounts();
    $userReaction = $userReaction ?? null;
    $compact = $compact ?? false;
    $activeCls = 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border-rose-300 dark:border-rose-700 ring-1 ring-rose-200/70';
    $inactiveCls = 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-indigo-50 hover:border-indigo-200';
@endphp

<div class="berita-reaction-bar flex flex-wrap items-center {{ $compact ? 'gap-1' : 'gap-1.5' }}"
    data-url="{{ route('berita.like', $berita->slug) }}">
    @foreach (\App\Models\BeritaLike::REACTIONS as $key => $meta)
        @php
            $isActive = $userReaction === $key;
            $countVal = (int) ($counts[$key] ?? 0);
        @endphp
        <button type="button"
            class="btn-react-berita inline-flex items-center {{ $compact ? 'gap-1 px-2 py-1 rounded-lg text-[11px]' : 'gap-1.5 px-3 py-1.5 rounded-xl text-xs' }} font-semibold border transition-all cursor-pointer {{ $isActive ? $activeCls : $inactiveCls }}"
            data-reaction="{{ $key }}"
            data-active="{{ $isActive ? '1' : '0' }}"
            title="{{ $meta['label'] }}"
            aria-pressed="{{ $isActive ? 'true' : 'false' }}">
            <span class="leading-none">{{ $meta['emoji'] }}</span>
            @if (!$compact)
                <span class="hidden sm:inline">{{ $meta['label'] }}</span>
            @endif
            <span class="reaction-count font-mono {{ $compact ? 'text-[10px]' : 'text-[11px]' }}">{{ $countVal }}</span>
        </button>
    @endforeach
</div>
