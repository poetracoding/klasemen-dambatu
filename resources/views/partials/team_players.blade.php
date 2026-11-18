@php
    $p1Raw = trim((string) ($p1 ?? ''));
    $p2Raw = trim((string) ($p2 ?? ''));
    $useShort = $short ?? true;
    $limit = $maxLen ?? 7;
    $p1Disp = $p1Raw !== '' ? ($useShort ? \App\Services\TournamentService::namaSingkat($p1Raw, $limit) : $p1Raw) : '';
    $p2Disp = $p2Raw !== '' ? ($useShort ? \App\Services\TournamentService::namaSingkat($p2Raw, $limit) : $p2Raw) : '';
    $fullTitle = trim($p1Raw . ($p1Raw !== '' && $p2Raw !== '' ? ' & ' : '') . $p2Raw);
    $alignVal = $align ?? 'left';
    $alignClass = $alignVal === 'right' ? 'justify-end' : ($alignVal === 'center' ? 'justify-center' : 'justify-start');
    $sizeVal = $size ?? '2xs';
    $textClass = $sizeVal === 'xs' ? 'text-xs gap-x-1.5' : 'text-[11px] gap-x-1';
    $iconClass = $sizeVal === 'xs' ? 'text-xs' : 'text-[10px]';
    $wrapClass = $useShort ? 'flex-nowrap whitespace-nowrap' : 'flex-wrap';
    $extraClass = $class ?? '';
@endphp
@if ($p1Disp !== '' || $p2Disp !== '')
    <div class="{{ $textClass }} text-slate-500 dark:text-slate-400 font-normal flex {{ $wrapClass }} items-center {{ $alignClass }} gap-y-0.5 mt-0.5 leading-tight {{ $extraClass }}" title="{{ $fullTitle }}">
        @if ($p1Disp !== '')
            <span class="inline-flex items-center gap-0.5">
                <i class="bi bi-person-fill text-indigo-500 dark:text-indigo-400 {{ $iconClass }} shrink-0"></i>
                <span>{{ $p1Disp }}</span>
            </span>
        @endif
        @if ($p1Disp !== '' && $p2Disp !== '')
            <span class="text-slate-400">&amp;</span>
        @endif
        @if ($p2Disp !== '')
            <span class="inline-flex items-center gap-0.5">
                <i class="bi bi-person-fill text-indigo-500 dark:text-indigo-400 {{ $iconClass }} shrink-0"></i>
                <span>{{ $p2Disp }}</span>
            </span>
        @endif
    </div>
@endif
