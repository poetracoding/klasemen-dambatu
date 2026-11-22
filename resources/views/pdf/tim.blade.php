<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Tim Peserta {{ $liga->nama_liga ?? 'Liga Dam Batu' }}</title>
    <style>
        @page {
            margin: 24px 28px;
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
            margin-bottom: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
        }
        .meta-table td {
            font-size: 9.5px;
            color: #475569;
        }
        .meta-table strong {
            color: #0f172a;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 6px;
            text-align: center;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 5.5px 6px;
            border: 1px solid #cbd5e1;
            font-size: 9.5px;
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
        .badge-status {
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-aktif {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .status-nonaktif {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .rules-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 8.5px;
            color: #64748b;
            margin-bottom: 16px;
            page-break-inside: avoid;
        }
        .rules-box strong {
            color: #334155;
        }
        .signature-table {
            width: 100%;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            font-size: 9.5px;
        }
        .signature-line {
            margin-top: 45px;
            border-top: 1px solid #94a3b8;
            width: 170px;
            display: inline-block;
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
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <table class="header-table">
        <tr>
            <td>
                <div class="title">{{ $liga->nama_liga ?? 'LIGA DAM BATU' }}</div>
                <div class="subtitle">
                    Musim {{ $liga->musim ?? date('Y') }} &middot; Turnamen Dam Batu Pasangan / Ganda (2 Pemain per Tim)
                </div>
            </td>
            <td class="text-right">
                <span class="doc-badge">Daftar Tim Resmi</span>
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
            <td width="25%">Total Tim: <strong>{{ $totalTim }} Tim Peserta</strong></td>
            <td width="25%">Total Atlet: <strong>{{ $totalPemain }} Pemain</strong></td>
            <td width="25%" class="text-right">Jumlah Meja: <strong>{{ $liga->jumlah_meja ?? 2 }} Meja</strong></td>
        </tr>
    </table>

    <!-- Tabel Daftar Tim -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="32%" class="text-left">Nama Tim</th>
                <th width="24%" class="text-left">Pemain 1</th>
                <th width="24%" class="text-left">Pemain 2</th>
                <th width="8%">Status</th>
                <th width="7%">Paraf</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tims as $i => $t)
                <tr>
                    <td class="text-center font-bold">{{ $i + 1 }}</td>
                    <td class="font-bold" style="color: #0f172a;">{{ $t->nama_tim }}</td>
                    <td class="text-left" style="color: #334155;">{{ $t->pemain1 }}</td>
                    <td class="text-left" style="color: #334155;">{{ $t->pemain2 }}</td>
                    <td class="text-center">
                        @if ($t->status == 1)
                            <span class="badge-status status-aktif">Aktif</span>
                        @else
                            <span class="badge-status status-nonaktif">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center" style="color: #cbd5e1;">&hellip;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 30px; color: #94a3b8;">
                        Belum ada tim yang terdaftar pada turnamen ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Keterangan & Ketentuan Peserta -->
    <div class="rules-box">
        <strong>Ketentuan Peserta:</strong>
        (1) Setiap tim terdiri dari tepat 2 (dua) pemain resmi terdaftar.
        (2) Seluruh pemain wajib menjunjung tinggi sportivitas dan mematuhi Peraturan &amp; Tata Tertib Pertandingan yang berlaku.
        (3) Pergantian susunan nama pemain hanya dapat diajukan kepada Panitia Pelaksana sebelum jadwal laga resmi dipublikasikan.
    </div>

    <!-- Tanda Tangan Pengesahan -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Panitia Pelaksana Turnamen</strong>
                <div class="signature-line"></div>
                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Koordinator Pertandingan</div>
            </td>
            <td class="text-right">
                {{ date('d F Y') }}<br>
                <strong>Administrator Turnamen</strong>
                <div class="signature-line"></div>
                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">{{ $liga->nama_liga ?? 'Liga Dam Batu' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak secara otomatis dari Sistem Manajemen Turnamen {{ $liga->nama_liga ?? 'Liga Dam Batu' }}
    </div>

</body>
</html>

