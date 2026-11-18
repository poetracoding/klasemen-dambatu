<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pertandingan {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    <style>
        @page {
            margin: 18px 24px 24px 24px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .subtitle {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-badge {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #c7d2fe;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
        }
        .meta-table td {
            font-size: 9px;
            color: #475569;
        }
        .meta-table strong {
            color: #0f172a;
        }
        .page-header-mini {
            width: 100%;
            border-bottom: 1.5px solid #4f46e5;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .page-header-mini td {
            vertical-align: middle;
            font-size: 9px;
        }
        .day-block {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .day-header {
            background-color: #312e81;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 3px;
            margin-bottom: 4px;
        }
        .day-header-table {
            width: 100%;
            border: none;
            margin: 0;
            padding: 0;
        }
        .day-header-table td {
            padding: 0;
            color: #ffffff;
            vertical-align: middle;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            text-align: center;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 3px 4px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: middle;
        }
        .player-sub {
            font-size: 7.5px;
            color: #64748b;
            font-weight: normal;
            margin-top: 1px;
            line-height: 1.15;
        }
        .data-table tr {
            page-break-inside: avoid;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .score-box {
            font-weight: bold;
            font-size: 9.5px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 2px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-status {
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 3px;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-selesai { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-berlangsung { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-belum_main { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
        .winner {
            color: #15803d;
            font-weight: bold;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Dicetak otomatis dari Sistem Turnamen {{ addslashes($liga->nama_liga ?? 'Liga Dam Batu') }} | Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = $fontMetrics->get_font("Helvetica", "normal");
            $size = 7.5;
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $pdf->page_text((842 - $width) / 2, 576, $text, $font, $size, [0.58, 0.64, 0.72]);
        }
    </script>

    @forelse ($pages as $pageIndex => $daysOnPage)
        <div class="page-container">
            {{-- Header Halaman --}}
            @if ($loop->first)
                <!-- Header Halaman 1: Lengkap dengan Info Turnamen -->
                <table class="header-table">
                    <tr>
                        <td>
                            <div class="title">{{ $liga->nama_liga ?? 'LIGA DAM BATU' }}</div>
                            <div class="subtitle">
                                Musim {{ $liga->musim ?? date('Y') }} &middot; Jadwal Pertandingan Resmi (Total: {{ $matches->count() }} Laga)
                            </div>
                        </td>
                        <td class="text-right">
                            <span class="doc-badge">Jadwal Turnamen</span>
                            <div style="font-size: 8px; color: #64748b; margin-top: 3px;">
                                Dicetak: {{ date('d/m/Y H:i') }} WIB &middot; Hal. {{ $loop->iteration }} dari {{ $pages->count() }}
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Ringkasan Info Liga -->
                <table class="meta-table">
                    <tr>
                        <td width="25%">Status: <strong>{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</strong></td>
                        <td width="25%">Jumlah Meja: <strong>{{ $liga->jumlah_meja ?? 2 }} Meja</strong></td>
                        <td width="25%">Periode: <strong>{{ \App\Services\TournamentService::tglId($liga->tanggal_mulai) }} s/d {{ \App\Services\TournamentService::tglId($liga->tanggal_selesai) }}</strong></td>
                        <td width="25%" class="text-right">Total Pertandingan: <strong>{{ $matches->count() }} Match</strong></td>
                    </tr>
                </table>
            @else
                <!-- Header Halaman Lanjutan (Kompak) -->
                <table class="page-header-mini">
                    <tr>
                        <td>
                            <strong style="color: #0f172a; text-transform: uppercase;">{{ $liga->nama_liga ?? 'LIGA DAM BATU' }}</strong>
                            <span style="color: #64748b; margin-left: 6px;">&mdash; Jadwal Pertandingan (Musim {{ $liga->musim ?? date('Y') }})</span>
                        </td>
                        <td class="text-right" style="color: #64748b; font-size: 8.5px;">
                            Hal. {{ $loop->iteration }} dari {{ $pages->count() }} &middot; Dicetak: {{ date('d/m/Y H:i') }} WIB
                        </td>
                    </tr>
                </table>
            @endif

            {{-- Render Maksimal 2 Hari pada Halaman Ini --}}
            @foreach ($daysOnPage as $tglKey => $dayMatches)
                <div class="day-block">
                    <div class="day-header">
                        <table class="day-header-table">
                            <tr>
                                <td style="font-weight: bold; font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.3px;">
                                    @if ($tglKey === 'TBD')
                                        TANGGAL BELUM DITENTUKAN
                                    @else
                                        {{ \App\Services\TournamentService::hariId($tglKey) }}, {{ \App\Services\TournamentService::tglId($tglKey) }}
                                    @endif
                                </td>
                                <td class="text-right" style="font-size: 8.5px; opacity: 0.9;">
                                    {{ $dayMatches->count() }} Pertandingan
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="4%">No</th>
                                <th width="8%">Waktu</th>
                                <th width="8%">Ronde</th>
                                <th width="12%">Lokasi / Meja</th>
                                <th width="26%" class="text-right">Tim Home</th>
                                <th width="9%">Skor</th>
                                <th width="25%" class="text-left">Tim Away</th>
                                <th width="8%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dayMatches as $m)
                                @php
                                    $isHomeWin = $m->status === 'selesai' && $m->pemenang_id === $m->tim_home;
                                    $isAwayWin = $m->status === 'selesai' && $m->pemenang_id === $m->tim_away;
                                    $homePlayers = \App\Services\TournamentService::pemainLabel($m->homeTeam->pemain1 ?? '', $m->homeTeam->pemain2 ?? '', true, 7);
                                    $awayPlayers = \App\Services\TournamentService::pemainLabel($m->awayTeam->pemain1 ?? '', $m->awayTeam->pemain2 ?? '', true, 7);
                                @endphp
                                <tr>
                                    <td class="text-center font-bold">{{ $m->nomor_pertandingan }}</td>
                                    <td class="text-center font-bold">{{ \App\Services\TournamentService::jamId($m->waktu) }}</td>
                                    <td class="text-center">Rnd {{ $m->ronde }}</td>
                                    <td class="text-center">{{ $m->lokasi ?? '-' }}</td>
                                    <td class="text-right {{ $isHomeWin ? 'winner' : '' }}">
                                        <div>
                                            {{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}
                                            @if ($isHomeWin) &#10004; @endif
                                        </div>
                                        @if ($homePlayers)
                                            <div class="player-sub">{{ $homePlayers }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($m->status === 'selesai')
                                            <span class="score-box">{{ (int)$m->skor_home }} - {{ (int)$m->skor_away }}</span>
                                        @elseif ($m->status === 'berlangsung')
                                            <span class="score-box" style="background: #fef3c7; color: #b45309;">LIVE</span>
                                        @else
                                            <span style="color: #94a3b8; font-weight: bold;">vs</span>
                                        @endif
                                    </td>
                                    <td class="text-left {{ $isAwayWin ? 'winner' : '' }}">
                                        <div>
                                            @if ($isAwayWin) &#10004; @endif
                                            {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}
                                        </div>
                                        @if ($awayPlayers)
                                            <div class="player-sub">{{ $awayPlayers }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($m->status === 'selesai')
                                            <span class="badge-status status-selesai">Selesai</span>
                                        @elseif ($m->status === 'berlangsung')
                                            <span class="badge-status status-berlangsung">Main</span>
                                        @else
                                            <span class="badge-status status-belum_main">Belum</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @empty
        <div style="text-align: center; padding: 50px 20px; color: #94a3b8; font-size: 11px;">
            Belum ada jadwal pertandingan yang dibuat.
        </div>
    @endforelse

</body>
</html>
