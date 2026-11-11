<?php

namespace App\Http\Controllers;

use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\TournamentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PdfController extends Controller
{
    public function klasemen(Request $request)
    {
        $liga = Liga::current();
        $klasemen = TournamentService::hitungKlasemen($liga);
        $totalTim = count($klasemen);
        $totalMatch = Pertandingan::where('liga_id', $liga->id)->count();
        $totalSelesai = Pertandingan::where('liga_id', $liga->id)->where('status', 'selesai')->count();

        $pdf = Pdf::loadView('pdf.klasemen', compact('liga', 'klasemen', 'totalTim', 'totalMatch', 'totalSelesai'))
            ->setPaper('a4', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Helvetica',
            ]);

        $filename = 'klasemen-' . Str::slug($liga->nama_liga ?: 'liga') . '-' . date('Ymd-His') . '.pdf';

        if ($request->has('download') || $request->get('action') === 'download') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function jadwal(Request $request)
    {
        $liga = Liga::current();
        $query = Pertandingan::where('liga_id', $liga->id)
            ->with(['homeTeam', 'awayTeam']);

        if ($request->filled('ronde')) {
            $query->where('ronde', (int) $request->ronde);
        }

        if ($request->filled('tim') && (int) $request->tim > 0) {
            $timId = (int) $request->tim;
            $query->where(function ($q) use ($timId) {
                $q->where('tim_home', $timId)->orWhere('tim_away', $timId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

        $matches = $query->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')->get();
        $jadwalPerRonde = $matches->groupBy('ronde');

        $pdf = Pdf::loadView('pdf.jadwal', compact('liga', 'matches', 'jadwalPerRonde', 'request'))
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Helvetica',
            ]);

        $filename = 'jadwal-' . Str::slug($liga->nama_liga ?: 'liga') . '-' . date('Ymd-His') . '.pdf';

        if ($request->has('download') || $request->get('action') === 'download') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }
}

