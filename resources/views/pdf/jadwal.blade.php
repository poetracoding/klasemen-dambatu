<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pertandingan {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    <style>
        @page {
            margin: 22px 26px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .title {
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .subtitle {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-badge {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #c7d2fe;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
        }
        .meta-table td {
            font-size: 9.5px;
            color: #475569;
        }
        .meta-table strong {
            color: #0f172a;
        }
        .ronde-header {
            background-color: #312e81;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 5px 8px;
            border-radius: 3px;
            margin-top: 10px;
            margin-bottom: 4px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 5px;
            text-align: center;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 5px 5px;
            border: 1px solid #cbd5e1;
            font-size: 9.5px;
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
            font-size: 10.5px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-status {
            font-size: 8px;
            font-weight: bold;
            padding: 2px 6px;
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

    <!-- Header -->
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
                    Dicetak: {{ date('d/m/Y H:i') }} WIB
                </div>
            </td>
        </tr>
    </table>

    <!-- Ringkasan Info Liga -->
    <table class="meta-table">
        <tr>
            <td width="25%">Status Liga: <strong>{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</strong></td>
            <td width="25%">Jumlah Meja: <strong>{{ $liga->jumlah_meja ?? 2 }} Meja</strong></td>
            <td width="25%">Periode: <strong>{{ \App\Services\TournamentService::tglId($liga->tanggal_mulai) }} s/d {{ \App\Services\TournamentService::tglId($liga->tanggal_selesai) }}</strong></td>
            <td width="25%" class="text-right">Total Pertandingan: <strong>{{ $matches->count() }} Match</strong></td>
        </tr>
    </table>

    <!-- Tabel Jadwal Berdasarkan Ronde -->
    @forelse ($jadwalPerRonde as $ronde => $rMatches)
        <div class="ronde-header">
            RONDE {{ $ronde }} &mdash; ({{ $rMatches->count() }} Pertandingan)
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="14%">Hari &amp; Tanggal</th>
                    <th width="8%">Waktu</th>
                    <th width="10%">Lokasi / Meja</th>
                    <th width="24%" class="text-right">Tim Home</th>
                    <th width="9%">Skor</th>
                    <th width="24%" class="text-left">Tim Away</th>
                    <th width="7%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rMatches as $m)
                    @php
                        $isHomeWin = $m->status === 'selesai' && $m->pemenang_id === $m->tim_home;
                        $isAwayWin = $m->status === 'selesai' && $m->pemenang_id === $m->tim_away;
                        $tglStr = $m->tanggal ? \App\Services\TournamentService::hariId($m->tanggal) . ', ' . \App\Services\TournamentService::tglId($m->tanggal) : '-';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $m->nomor_pertandingan }}</td>
                        <td class="text-center">{{ $tglStr }}</td>
                        <td class="text-center font-bold">{{ \App\Services\TournamentService::jamId($m->waktu) }}</td>
                        <td class="text-center">{{ $m->lokasi ?? '-' }}</td>
                        <td class="text-right {{ $isHomeWin ? 'winner' : '' }}">
                            {{ $m->homeTeam->nama_tim ?? ('Tim #' . $m->tim_home) }}
                            @if ($isHomeWin) &#10004; @endif
                        </td>
                        <td class="text-center">
                            @if ($m->status === 'selesai')
                                <span class="score-box">{{ (int)$m->skor_home }} - {{ (int)$m->skor_away }}</span>
                            @elseif ($m->status === 'berlangsung')
                                <span class="score-box" style="background: #fef3c7; color: #b45309;">LIVE</span>
                            @else
                                <span style="color: #94a3b8;">vs</span>
                            @endif
                        </td>
                        <td class="text-left {{ $isAwayWin ? 'winner' : '' }}">
                            @if ($isAwayWin) &#10004; @endif
                            {{ $m->awayTeam->nama_tim ?? ('Tim #' . $m->tim_away) }}
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
    @empty
        <div style="text-align: center; padding: 40px; color: #94a3b8;">
            Belum ada jadwal pertandingan yang dibuat.
        </div>
    @endforelse

    <div class="footer">
        Dicetak secara otomatis dari Sistem Turnamen {{ $liga->nama_liga ?? 'Liga Dam Batu' }} &middot; Hal. 1
    </div>

</body>
</html>

