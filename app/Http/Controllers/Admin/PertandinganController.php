<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Tim;
use App\Services\FcmService;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PertandinganController extends Controller
{
    public function index(Request $request)
    {
        $liga = Liga::current();
        $f = $request->has('f') ? (string) $request->get('f') : 'belum';
        $ft = (string) $request->get('tanggal', '');
        $timId = (int) $request->get('tim', 0);
        $qSearch = trim((string) $request->get('q', ''));

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

        if ($timId > 0) {
            $query->where(function ($q) use ($timId) {
                $q->where('tim_home', $timId)->orWhere('tim_away', $timId);
            });
        }

        if ($qSearch !== '') {
            $like = '%' . $qSearch . '%';
            $query->where(function ($q) use ($like) {
                $q->whereHas('homeTeam', function ($sub) use ($like) {
                    $sub->where('nama_tim', 'like', $like)
                        ->orWhere('pemain1', 'like', $like)
                        ->orWhere('pemain2', 'like', $like);
                })->orWhereHas('awayTeam', function ($sub) use ($like) {
                    $sub->where('nama_tim', 'like', $like)
                        ->orWhere('pemain1', 'like', $like)
                        ->orWhere('pemain2', 'like', $like);
                });
            });
        }

        $matches = $query->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')->get();
        $tims = Tim::where('liga_id', $liga->id)->orderBy('nama_tim')->get();

        return view('admin.pertandingan', compact('liga', 'f', 'ft', 'timId', 'qSearch', 'matches', 'tims'));
    }

    public function simpanHasil(Request $request, $id)
    {
        $liga = Liga::current();
        if ($liga->status === 'draft') {
            return back()->with('danger', 'Publikasikan jadwal terlebih dahulu sebelum menginput hasil.');
        }

        $match = Pertandingan::where('liga_id', $liga->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(TournamentService::STATUS_MATCH))],
            'skor_home' => 'nullable|integer|min:0|max:9999',
            'skor_away' => 'nullable|integer|min:0|max:9999',
            'pemenang_id' => 'nullable|integer',
            'catatan' => 'nullable|string|max:500',
        ]);

        $status = $validated['status'];
        $skorHome = max(0, (int) ($validated['skor_home'] ?? 0));
        $skorAway = max(0, (int) ($validated['skor_away'] ?? 0));
        $pemenang = (int) ($validated['pemenang_id'] ?? 0);
        $catatan = trim((string) ($validated['catatan'] ?? ''));

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

        try {
            app(FcmService::class)->notifyScoreUpdate($match);
        } catch (\Throwable $e) {
            // Pastikan gagal kirim notifikasi tidak menggagalkan penyimpanan skor
        }

        $msg = 'Hasil & poin pertandingan #' . $match->nomor_pertandingan . " ($skorHome - $skorAway) berhasil disimpan. Klasemen terupdate.";
        if ($liga->fresh()->status === 'selesai') {
            $msg .= ' Seluruh pertandingan telah selesai! Juara liga sudah ditentukan.';
        }

        return redirect()->route('admin.pertandingan.index', array_filter([
            'f' => $request->input('f'),
            'tanggal' => $request->input('ft'),
            'tim' => $request->input('tim'),
            'q' => $request->input('q'),
        ]))->with('success', $msg);
    }
}

