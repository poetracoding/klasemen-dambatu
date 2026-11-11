<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Klasemen {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    <style>
        @page {
            margin: 24px 28px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        .doc-badge {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 9px;
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
            padding: 8px 12px;
        }
        .meta-table td {
            font-size: 10px;
            color: #475569;
        }
        .meta-table strong {
            color: #0f172a;
        }
        .champion-box {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #78350f;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 12px;
            font-size: 11px;
        }
        .champion-box strong {
            color: #92400e;
            font-size: 12px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 7px 6px;
            text-align: center;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 6px 6px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .rank-1 { background-color: #fef9c3 !important; font-weight: bold; }
        .rank-2 { background-color: #f1f5f9 !important; }
        .rank-3 { background-color: #ffedd5 !important; }
        .pts-col {
            background-color: #eef2ff !important;
            font-weight: bold;
            font-size: 11px;
            color: #312e81;
        }
        .player-names {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 2px;
        }
        .rules-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 8.5px;
            color: #64748b;
            margin-bottom: 18px;
        }
        .rules-box strong { color: #334155; }
        .signature-table {
            width: 100%;
            margin-top: 20px;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            font-size: 10px;
        }
        .signature-line {
            margin-top: 50px;
            border-top: 1px solid #94a3b8;
            width: 180px;
            display: inline-block;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
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
                    Musim {{ $liga->musim ?? date('Y') }} &middot; Turnamen Single Round-Robin
                </div>
            </td>
            <td class="text-right">
                <span class="doc-badge">Klasemen Resmi</span>
                <div style="font-size: 8.5px; color: #64748b; margin-top: 4px;">
                    Dicetak: {{ date('d/m/Y H:i') }} WIB
                </div>
            </td>
        </tr>
    </table>

    <!-- Ringkasan Info Liga -->
    <table class="meta-table">
        <tr>
            <td width="25%">Status: <strong>{{ \App\Services\TournamentService::labelStatusLiga($liga->status) }}</strong></td>
            <td width="25%">Total Tim: <strong>{{ $totalTim }} Tim</strong></td>
            <td width="25%">Pertandingan Selesai: <strong>{{ $totalSelesai }} / {{ $totalMatch }}</strong></td>
            <td width="25%" class="text-right">Progres: <strong>{{ $totalMatch > 0 ? round(($totalSelesai / $totalMatch) * 100) : 0 }}%</strong></td>
        </tr>
    </table>

    @if ($liga->status === 'selesai' && !empty($klasemen))
        <div class="champion-box">
            &#127942; <strong>JUARA LIGA: {{ strtoupper($klasemen[0]['nama_tim']) }}</strong>
            ({{ $klasemen[0]['pemain1'] }} &amp; {{ $klasemen[0]['pemain2'] }}) &mdash; Total {{ $klasemen[0]['pts'] }} Poin, Selisih Batu {{ $klasemen[0]['sb'] > 0 ? '+' : '' }}{{ $klasemen[0]['sb'] }}.
        </div>
    @endif

    <!-- Tabel Klasemen -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">Pos</th>
                <th width="35%" class="text-left">Tim &amp; Pemain</th>
                <th width="8%" title="Matches Played">Main</th>
                <th width="8%" title="Won">Menang</th>
                <th width="8%" title="Lost">Kalah</th>
                <th width="9%" title="Poin Masuk (+)">BM (+)</th>
                <th width="9%" title="Poin Keluar (-)">BK (-)</th>
                <th width="9%" title="Selisih Skor">Δ Selisih</th>
                <th width="9%" class="pts-col">PTS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($klasemen as $idx => $k)
                @php
                    $pos = $k['pos'] ?? ($idx + 1);
                    $rowClass = '';
                    if ($pos === 1) $rowClass = 'rank-1';
                    elseif ($pos === 2) $rowClass = 'rank-2';
                    elseif ($pos === 3) $rowClass = 'rank-3';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center font-bold">{{ $pos }}</td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">{{ $k['nama_tim'] }}</div>
                        <div class="player-names">{{ $k['pemain1'] }} &middot; {{ $k['pemain2'] }}</div>
                    </td>
                    <td class="text-center">{{ $k['mp'] }}</td>
                    <td class="text-center font-bold" style="color: #059669;">{{ $k['w'] }}</td>
                    <td class="text-center" style="color: #dc2626;">{{ $k['l'] }}</td>
                    <td class="text-center font-bold" style="color: #047857;">{{ $k['bm'] }}</td>
                    <td class="text-center" style="color: #b91c1c;">{{ $k['bk'] }}</td>
                    <td class="text-center font-bold" style="color: {{ $k['sb'] > 0 ? '#047857' : ($k['sb'] < 0 ? '#b91c1c' : '#64748b') }};">
                        {{ $k['sb'] > 0 ? '+' : '' }}{{ $k['sb'] }}
                    </td>
                    <td class="text-center pts-col">{{ $k['pts'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #94a3b8;">
                        Belum ada data tim atau pertandingan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Keterangan Aturan -->
    <div class="rules-box">
        <strong>Ketentuan Poin &amp; Urutan Peringkat:</strong>
        Menang = 3 Poin, Kalah = 0 Poin. Peringkat diurutkan berdasarkan:
        (1) Total Poin Tertinggi &rarr;
        (2) Jumlah Kemenangan Terbanyak &rarr;
        (3) Selisih Batu Tertinggi (&Delta;) &rarr;
        (4) Total Batu Masuk Terbanyak (BM) &rarr;
        (5) Hasil Pertemuan Langsung (Head-to-Head).
    </div>

    <!-- Tanda Tangan -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Panitia Pelaksana</strong>
                <div class="signature-line"></div>
                <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">Koordinator Pertandingan</div>
            </td>
            <td class="text-right">
                {{ date('d F Y') }}<br>
                <strong>Administrator Turnamen</strong>
                <div class="signature-line"></div>
                <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak secara otomatis dari Sistem Manajemen Turnamen {{ $liga->nama_liga ?? 'Liga Dam Batu' }} &middot; Hal. 1
    </div>

</body>
</html>
