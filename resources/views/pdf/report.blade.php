<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $periodeJudul }} - {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    <style>
        @page {
            margin: 18px 24px 26px 24px;
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

        .day-block {
            margin-bottom: 11px;
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
            margin-bottom: 8px;
        }

        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 5px;
            text-align: center;
            border: 1px solid #0f172a;
        }

        .data-table td {
            padding: 4px 5px;
            border: 1px solid #cbd5e1;
            font-size: 8.8px;
            vertical-align: top;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .player-sub {
            font-size: 7.5px;
            color: #64748b;
            font-weight: normal;
            margin-top: 1px;
            line-height: 1.15;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .score-box {
            font-weight: bold;
            font-size: 9.5px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            border: 1px solid #cbd5e1;
        }

        .winner {
            color: #15803d;
            font-weight: bold;
        }

        .winner-badge {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .section-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #0f172a;
            margin: 10px 0 5px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #cbd5e1;
            text-transform: uppercase;
        }

        .empty-box {
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            padding: 24px;
            text-align: center;
            color: #64748b;
            font-size: 10px;
            border-radius: 4px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 3px;
        }
    </style>
</head>

<body>

    <div class="footer">
        Laporan Hasil {{ $liga->nama_liga ?? 'Liga Dam Batu' }} &middot; {{ $periodeJudul }}
    </div>

    <!-- Header Laporan -->
    <table class="header-table">
        <tr>
            <td>
                <div class="title">{{ $liga->nama_liga ?? 'LIGA DAM BATU' }} &mdash; {{ strtoupper($periodeJudul) }}
                </div>
                <div class="subtitle">
                    Musim {{ $liga->musim ?? date('Y') }} &middot; Periode: <strong>{{ $periodeLabel }}</strong>
                    @if ($selectedTim)
                        &middot; Filter Tim: <strong>{{ $selectedTim->nama_tim }}</strong>
                    @endif
                    &middot; Sumber Tanggal: Waktu Update Pertandingan (updated_at)
                </div>
            </td>
            <td class="text-right">
                <span class="doc-badge">{{ $periodeJudul }}</span>
                <div style="font-size: 8px; color: #64748b; margin-top: 3px;">
                    Dicetak: {{ date('d/m/Y H:i') }} WIB
                </div>
            </td>
        </tr>
    </table>

    <!-- Ringkasan Periode -->
    <table class="meta-table">
        <tr>
            <td width="25%">Total Laga Selesai: <strong>{{ $totalLaga }} Match</strong></td>
            <td width="25%">Tim Bertanding: <strong>{{ $uniqueTeamCount }} Tim ({{ $uniqueTeamCount * 2 }}
                    Pemain)</strong></td>
            <td width="25%">Total Skor Tercipta: <strong>{{ number_format($totalPoin) }} Poin</strong></td>
            <td width="25%" class="text-right">Rata-rata Selisih: <strong>+/- {{ $avgSelisih }} Poin</strong></td>
        </tr>
    </table>

    @if ($matches->isEmpty())
        <div class="empty-box">
            Tidak ada pertandingan berstatus Selesai dengan waktu update (updated_at) pada periode
            {{ $periodeLabel }}{{ $selectedTim ? ' untuk tim ' . $selectedTim->nama_tim : '' }}.
        </div>
    @else
        @foreach ($matchesByUpdateDate as $ymd => $dayMatches)
            <div class="day-block">
                <div class="day-header">
                    <table class="day-header-table">
                        <tr>
                            <td style="font-weight: bold; font-size: 9px; text-transform: uppercase;">
                                {{ \App\Http\Controllers\Admin\ReportController::formatTanggalId($ymd) }}
                            </td>
                            <td class="text-right" style="font-size: 8.5px;">
                                {{ $dayMatches->count() }} Pertandingan
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="8%">Match / Meja</th>
                            <th width="10%">Waktu Update</th>
                            <th width="23%" class="text-left">Tim Home &amp; Pemain</th>
                            <th width="9%">Skor</th>
                            <th width="23%" class="text-left">Tim Away &amp; Pemain</th>
                            <th width="17%" class="text-left">Pemenang</th>
                            <th width="10%" class="text-left">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayMatches as $m)
                            @php
                                $homeName = $m->homeTeam?->nama_tim ?? 'Tim #' . $m->tim_home;
                                $awayName = $m->awayTeam?->nama_tim ?? 'Tim #' . $m->tim_away;
                                $homePlayers = \App\Services\TournamentService::pemainLabel(
                                    $m->homeTeam?->pemain1,
                                    $m->homeTeam?->pemain2,
                                    false,
                                );
                                $awayPlayers = \App\Services\TournamentService::pemainLabel(
                                    $m->awayTeam?->pemain1,
                                    $m->awayTeam?->pemain2,
                                    false,
                                );
                                $isHomeWinner = (int) $m->pemenang_id === (int) $m->tim_home;
                                $isAwayWinner = (int) $m->pemenang_id === (int) $m->tim_away;
                                $winPlayers = $isHomeWinner ? $homePlayers : ($isAwayWinner ? $awayPlayers : '');
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <div class="font-bold">#{{ $m->nomor_pertandingan }}</div>
                                    <div class="player-sub">R{{ $m->ronde }} &middot; {{ $m->lokasi ?: '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="font-bold">{{ $m->updated_at ? $m->updated_at->format('H:i') : '-' }}
                                        WIB</div>
                                    <div class="player-sub">
                                        {{ $m->updated_at ? $m->updated_at->format('d/m/Y') : '-' }}</div>
                                </td>
                                <td>
                                    <div class="{{ $isHomeWinner ? 'winner' : 'font-bold' }}">{{ $homeName }}
                                    </div>
                                    @if ($homePlayers !== '')
                                        <div class="player-sub">{{ $homePlayers }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="score-box">{{ $m->skor_home }} - {{ $m->skor_away }}</span>
                                    <div class="player-sub">Selisih:
                                        {{ abs((int) $m->skor_home - (int) $m->skor_away) }}</div>
                                </td>
                                <td>
                                    <div class="{{ $isAwayWinner ? 'winner' : 'font-bold' }}">{{ $awayName }}
                                    </div>
                                    @if ($awayPlayers !== '')
                                        <div class="player-sub">{{ $awayPlayers }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($m->pemenang)
                                        <span class="winner-badge">{{ $m->pemenang->nama_tim }}</span>
                                        @if ($winPlayers !== '')
                                            <div class="player-sub">{{ $winPlayers }}</div>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $m->catatan ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    @if (!empty($klasemen))
        <div class="section-title">
            Update Klasemen Keseluruhan
            <span style="font-size: 8px; font-weight: normal; color: #64748b; text-transform: none; margin-left: 6px;">
                ({{ count($klasemen) }} Tim &middot; Kondisi terkini per {{ date('d/m/Y H:i') }} WIB &middot; 3 Poin
                Menang / 0 Kalah)
            </span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="6%">Pos</th>
                    <th width="38%" class="text-left">Tim &amp; Pemain</th>
                    <th width="8%">Main</th>
                    <th width="8%">Menang</th>
                    <th width="8%">Kalah</th>
                    <th width="8%">+</th>
                    <th width="8%">-</th>
                    <th width="8%">Selisih</th>
                    <th width="8%">PTS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($klasemen as $row)
                    @php
                        $rowPlayers = \App\Services\TournamentService::pemainLabel(
                            $row['pemain1'],
                            $row['pemain2'],
                            false,
                        );
                    @endphp
                    <tr>
                        <td class="text-center font-bold">#{{ $row['pos'] }}</td>
                        <td>
                            <div class="font-bold">{{ $row['nama_tim'] }}</div>
                            @if ($rowPlayers !== '')
                                <div class="player-sub">{{ $rowPlayers }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $row['mp'] }}</td>
                        <td class="text-center winner">{{ $row['w'] }}</td>
                        <td class="text-center">{{ $row['l'] }}</td>
                        <td class="text-center">{{ $row['bm'] }}</td>
                        <td class="text-center">{{ $row['bk'] }}</td>
                        <td class="text-center font-bold">{{ $row['sb'] > 0 ? '+' : '' }}{{ $row['sb'] }}</td>
                        <td class="text-center font-bold">{{ $row['pts'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>

</html>
