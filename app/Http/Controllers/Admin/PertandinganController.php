<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class PertandinganController extends Controller
{
    public function index(Request $request)
    {
        $liga = Liga::current();
        $f = $request->get('f', '');
        $ft = $request->get('tanggal', '');

        $query = Pertandingan::where('liga_id', $liga->id)
            ->with(['homeTeam', 'awayTeam', 'pemenang']);

        if ($f === 'belum') {
            $query->where('status', '<>', 'selesai');
        } elseif ($f === 'selesai') {
            $query->where('status', 'selesai');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $ft)) {
            $query->where('tanggal', $ft);
        } else {
            $ft = '';
        }

        $matches = $query->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')->get();

        return view('admin.pertandingan', compact('liga', 'f', 'ft', 'matches'));
    }

    public function simpanHasil(Request $request, $id)
    {
        $liga = Liga::current();
        if ($liga->status === 'draft') {
            return back()->with('danger', 'Publikasikan jadwal terlebih dahulu sebelum menginput hasil.');
        }

        $match = Pertandingan::where('liga_id', $liga->id)->findOrFail($id);

        $status = $request->input('status', 'belum_main');
        $skorHome = max(0, (int) $request->input('skor_home', 0));
        $skorAway = max(0, (int) $request->input('skor_away', 0));
        $pemenang = (int) $request->input('pemenang_id', 0);
        $catatan = trim($request->input('catatan', ''));

        // Otomatis tentukan pemenang jika skor berbeda dan belum dipilih manual
        if ($status === 'selesai' && !$pemenang) {
            if ($skorHome > $skorAway) {
                $pemenang = $match->tim_home;
            } elseif ($skorAway > $skorHome) {
                $pemenang = $match->tim_away;
            }
        }

        if ($status === 'selesai' && !in_array($pemenang, [$match->tim_home, $match->tim_away], true)) {
            return back()->with('danger', 'Pilih pemenang pertandingan (harus ada pemenang, tidak boleh seri).');
        }

        $match->update([
            'status' => $status,
            'skor_home' => $skorHome,
            'skor_away' => $skorAway,
            'pemenang_id' => $status === 'selesai' ? $pemenang : null,
            'catatan' => $catatan ?: null,
        ]);

        TournamentService::sinkronStatusLiga($liga);

        $msg = 'Hasil & poin pertandingan #' . $match->nomor_pertandingan . " ($skorHome - $skorAway) berhasil disimpan. Klasemen terupdate.";
        if ($liga->fresh()->status === 'selesai') {
            $msg .= ' Seluruh pertandingan telah selesai! Juara liga sudah ditentukan.';
        }

        return redirect()->route('admin.pertandingan.index', array_filter(['f' => $request->f, 'tanggal' => $request->ft]))
            ->with('success', $msg);
    }
}

