<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\TournamentService;

class DashboardController extends Controller
{
    public function index()
    {
        $liga = Liga::current();
        $jumlahTim = Tim::where('liga_id', $liga->id)->count();

        $agg = Pertandingan::where('liga_id', $liga->id)
            ->selectRaw('COUNT(*) as total,
                         SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai,
                         SUM(CASE WHEN status = "berlangsung" THEN 1 ELSE 0 END) as main,
                         SUM(CASE WHEN status = "belum_main" THEN 1 ELSE 0 END) as belum')
            ->first();

        $total = (int) ($agg->total ?? 0);
        $selesai = (int) ($agg->selesai ?? 0);
        $main = (int) ($agg->main ?? 0);
        $belum = (int) ($agg->belum ?? 0);

        $kl = TournamentService::hitungKlasemen($liga);
        $hariIni = Pertandingan::where('liga_id', $liga->id)
            ->where('tanggal', date('Y-m-d'))
            ->with(['homeTeam', 'awayTeam', 'pemenang'])
            ->orderBy('waktu')
            ->orderBy('nomor_pertandingan')
            ->get();

        return view('admin.dashboard', compact('liga', 'jumlahTim', 'total', 'selesai', 'main', 'belum', 'kl', 'hariIni'));
    }
}

