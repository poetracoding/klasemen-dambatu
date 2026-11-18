@extends('layouts.admin')

@section('title', 'Analytics Pengunjung')

@section('content')
@php
    $totalDevice = max(1, $mobileViews + $desktopViews);
    $mobilePct = ($mobileViews + $desktopViews) > 0 ? round(($mobileViews / $totalDevice) * 100) : 0;
    $desktopPct = ($mobileViews + $desktopViews) > 0 ? (100 - $mobilePct) : 0;
@endphp

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
            <i class="bi bi-graph-up-arrow text-indigo-600"></i>
            <span>Analytics Pengunjung</span>
        </h1>
        <p class="text-sm text-slate-500 mt-1">Statistik kunjungan halaman publik secara langsung dari sistem web.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.pengaturan.analytics') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-xs">
            <i class="bi bi-arrow-clockwise"></i> Muat Ulang
        </a>
        <form action="{{ route('admin.pengaturan.analytics.reset') }}" method="POST" class="inline form-delete" data-confirm="Yakin ingin menghapus seluruh riwayat statistik kunjungan?" data-confirm-title="Reset Statistik?" data-confirm-btn="Ya, Reset">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors shadow-xs cursor-pointer">
                <i class="bi bi-trash3"></i> Reset Data
            </button>
        </form>
    </div>
</div>

<!-- 4 Ringkasan Metrik Kunjungan -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">Kunjungan Hari Ini</span>
            <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><i class="bi bi-eye-fill"></i></span>
        </div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($todayViews) }}</div>
        <div class="text-xs text-slate-400 mt-1">{{ number_format($todayUnique) }} pengunjung unik</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">Pengunjung Unik Hari Ini</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="bi bi-person-check-fill"></i></span>
        </div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($todayUnique) }}</div>
        <div class="text-xs text-emerald-600 font-medium mt-1">Berdasarkan sesi hari ini</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">7 Hari Terakhir</span>
            <span class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center"><i class="bi bi-calendar-week-fill"></i></span>
        </div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($weekViews) }}</div>
        <div class="text-xs text-slate-400 mt-1">{{ number_format($weekUnique) }} pengunjung unik</div>
    </div>

    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider">Total Kunjungan</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center"><i class="bi bi-bar-chart-fill"></i></span>
        </div>
        <div class="text-2xl sm:text-3xl font-extrabold text-slate-900">{{ number_format($totalViews) }}</div>
        <div class="text-xs text-slate-400 mt-1">{{ number_format($totalUnique) }} total pengunjung unik</div>
    </div>
</div>

<!-- Grafik Tren Kunjungan 14 Hari Terakhir -->
<div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 mb-6 border-b border-slate-100">
        <div>
            <h2 class="text-base font-bold text-slate-900">Tren Kunjungan 14 Hari Terakhir</h2>
            <p class="text-xs text-slate-400 mt-0.5">Perbandingan total halaman dibuka setiap hari.</p>
        </div>
        <div class="text-xs text-slate-500 flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span> Total Kunjungan</span>
        </div>
    </div>

    <div class="grid grid-cols-14 gap-1.5 sm:gap-2 items-end h-44 pt-4" style="grid-template-columns: repeat(14, minmax(0, 1fr));">
        @foreach ($dailyStats as $day)
            @php
                $heightPct = $day['views'] > 0 ? max(8, round(($day['views'] / $maxDailyViews) * 100)) : 2;
                $isToday = $day['date'] === now()->toDateString();
            @endphp
            <div class="flex flex-col items-center h-full justify-end group" title="{{ $day['day'] }}, {{ $day['date'] }}: {{ $day['views'] }} kunjungan ({{ $day['uniques'] }} unik)">
                <div class="text-[10px] font-mono font-bold text-slate-500 mb-1 opacity-80 group-hover:opacity-100 group-hover:text-indigo-600">
                    {{ $day['views'] > 0 ? $day['views'] : '' }}
                </div>
                <div class="w-full max-w-[28px] rounded-t-lg transition-all duration-300 {{ $day['views'] > 0 ? ($isToday ? 'bg-indigo-600' : 'bg-indigo-400 group-hover:bg-indigo-600') : 'bg-slate-200 dark:bg-slate-800' }}"
                     style="height: {{ $heightPct }}%;"></div>
                <div class="text-[10px] font-mono {{ $isToday ? 'font-bold text-indigo-600' : 'text-slate-400' }} mt-2 truncate w-full text-center">
                    {{ $day['label'] }}
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
    <!-- Halaman Terpopuler -->
    <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-file-earmark-bar-graph text-indigo-500"></i> Halaman Paling Sering Dibuka
            </h2>
        </div>

        @if ($topPages->isEmpty())
            <div class="py-10 text-center text-sm text-slate-400">
                Belum ada data kunjungan tercatat.
            </div>
        @else
            @php $maxPageViews = max(1, $topPages->max('views')); @endphp
            <div class="space-y-4">
                @foreach ($topPages as $page)
                    @php $pct = round(($page->views / $maxPageViews) * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <div>
                                <span class="font-bold text-slate-800">{{ $page->page_title }}</span>
                                <span class="text-slate-400 font-mono ml-1.5">{{ $page->path }}</span>
                            </div>
                            <div class="font-mono font-bold text-slate-700 whitespace-nowrap">
                                {{ number_format($page->views) }} <span class="text-slate-400 font-normal">({{ number_format($page->uniques) }} unik)</span>
                            </div>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Perangkat Pengunjung -->
    <div class="lg:col-span-5 bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="bi bi-phone text-indigo-500"></i> Perangkat Pengunjung
            </h2>
        </div>

        @if (($mobileViews + $desktopViews) === 0)
            <div class="py-10 text-center text-sm text-slate-400">
                Belum ada data perangkat tercatat.
            </div>
        @else
            <div class="space-y-5 pt-2">
                <div class="w-full h-4 rounded-full bg-slate-100 overflow-hidden flex">
                    <div class="bg-indigo-600 h-full" style="width: {{ $mobilePct }}%" title="Mobile {{ $mobilePct }}%"></div>
                    <div class="bg-emerald-500 h-full" style="width: {{ $desktopPct }}%" title="Desktop {{ $desktopPct }}%"></div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100">
                        <div class="flex items-center gap-2 text-xs font-semibold text-indigo-700 mb-1">
                            <i class="bi bi-phone-fill"></i> HP / Mobile
                        </div>
                        <div class="text-2xl font-black text-slate-900">{{ $mobilePct }}%</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ number_format($mobileViews) }} kunjungan</div>
                    </div>

                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100">
                        <div class="flex items-center gap-2 text-xs font-semibold text-emerald-700 mb-1">
                            <i class="bi bi-laptop-fill"></i> Komputer / Desktop
                        </div>
                        <div class="text-2xl font-black text-slate-900">{{ $desktopPct }}%</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ number_format($desktopViews) }} kunjungan</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Riwayat Kunjungan Terbaru -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-900">Aktivitas Kunjungan Terbaru</h2>
            <p class="text-xs text-slate-400 mt-0.5">15 akses halaman publik terakhir.</p>
        </div>
    </div>

    @if ($recentVisits->isEmpty())
        <div class="p-10 text-center text-sm text-slate-400">
            Belum ada riwayat kunjungan yang tercatat.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 divide-y divide-slate-100">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Halaman</th>
                        <th class="py-3 px-4">URL Path</th>
                        <th class="py-3 px-4 text-center">Perangkat</th>
                        <th class="py-3 px-4 text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($recentVisits as $v)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap font-mono text-xs text-slate-500">
                                {{ $v->created_at ? $v->created_at->format('d/m/Y H:i:s') : '-' }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $v->page_title ?: $v->path }}</td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $v->path }}</td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if ($v->device_type === 'mobile')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i class="bi bi-phone"></i> Mobile
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="bi bi-laptop"></i> Desktop
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-xs text-slate-400">{{ $v->ip_address ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
