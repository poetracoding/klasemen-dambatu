<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\BeritaLike;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\Poster;
use App\Models\Tim;
use App\Services\TournamentService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $liga = Liga::current();
        $jumlahTim = Tim::where('liga_id', $liga->id)->count();
        $total = Pertandingan::where('liga_id', $liga->id)->count();
        $selesai = Pertandingan::where('liga_id', $liga->id)->where('status', 'selesai')->count();
        $tampil = $liga->status !== 'draft';

        $hariIni = $tampil ? Pertandingan::where('liga_id', $liga->id)
            ->where('tanggal', date('Y-m-d'))
            ->with(['homeTeam', 'awayTeam', 'pemenang'])
            ->orderBy('tanggal')
            ->orderBy('waktu')
            ->orderBy('nomor_pertandingan')
            ->get() : collect();

        $kl = TournamentService::hitungKlasemen($liga);

        $posters = Poster::where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $beritas = Berita::published()
            ->with('author')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $visitorHash = BeritaController::resolveVisitorHash($request);
        $beritaIds = $beritas->pluck('id')->all();
        $beritaReactionCounts = BeritaLike::countsForBeritas($beritaIds);
        $beritaVisitorReactions = BeritaLike::visitorReactionsForBeritas($visitorHash, $beritaIds);
        $likedBeritaIds = array_keys($beritaVisitorReactions);

        return view('dashboard', compact(
            'liga',
            'jumlahTim',
            'total',
            'selesai',
            'tampil',
            'hariIni',
            'kl',
            'posters',
            'beritas',
            'likedBeritaIds',
            'beritaReactionCounts',
            'beritaVisitorReactions'
        ));
    }

    public function jadwal(Request $request)
    {
        $liga = Liga::current();
        $tampil = $liga->status !== 'draft';
        $daftarTim = Tim::where('liga_id', $liga->id)->orderBy('nama_tim')->get();

        $fTim = (int) $request->get('tim', 0);
        $fStatus = $request->get('status', '');

        $grup = [];
        if ($tampil) {
            $query = Pertandingan::where('liga_id', $liga->id)
                ->with(['homeTeam', 'awayTeam', 'pemenang']);

            if ($fTim) {
                $query->where(function ($q) use ($fTim) {
                    $q->where('tim_home', $fTim)->orWhere('tim_away', $fTim);
                });
            }

            if ($fStatus && isset(TournamentService::STATUS_MATCH[$fStatus])) {
                $query->where('status', $fStatus);
            }

            $matches = $query->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')->get();

            foreach ($matches as $m) {
                $tglKey = $m->tanggal ? $m->tanggal->format('Y-m-d') : '';
                $grup[$tglKey][] = $m;
            }
        }

        return view('jadwal', compact('liga', 'tampil', 'daftarTim', 'fTim', 'fStatus', 'grup'));
    }

    public function jadwalHari(Request $request)
    {
        $liga = Liga::current();
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !strtotime($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $fTim = (int) $request->get('tim', 0);
        $fStatus = $request->get('status', '');
        $tampil = $liga->status !== 'draft';
        $daftarTim = Tim::where('liga_id', $liga->id)->orderBy('nama_tim')->get();

        $rows = collect();
        if ($tampil) {
            $query = Pertandingan::where('liga_id', $liga->id)
                ->where('tanggal', $tanggal)
                ->with(['homeTeam', 'awayTeam', 'pemenang']);

            if ($fTim) {
                $query->where(function ($q) use ($fTim) {
                    $q->where('tim_home', $fTim)->orWhere('tim_away', $fTim);
                });
            }

            if ($fStatus && isset(TournamentService::STATUS_MATCH[$fStatus])) {
                $query->where('status', $fStatus);
            }

            $rows = $query->orderBy('waktu')->orderBy('nomor_pertandingan')->get();
        }

        return view('jadwal_hari', compact('liga', 'tanggal', 'fTim', 'fStatus', 'tampil', 'daftarTim', 'rows'));
    }

    public function klasemen()
    {
        $liga = Liga::current();
        $kl = TournamentService::hitungKlasemen($liga);
        return view('klasemen', compact('liga', 'kl'));
    }

    public function history(Request $request)
    {
        $liga = Liga::current();
        $daftarTim = Tim::where('liga_id', $liga->id)->orderBy('nama_tim')->get();
        $fTim = (int) $request->get('tim', 0);

        $query = Pertandingan::where('liga_id', $liga->id)
            ->where('status', 'selesai')
            ->with(['homeTeam', 'awayTeam', 'pemenang']);

        if ($fTim) {
            $query->where(function ($q) use ($fTim) {
                $q->where('tim_home', $fTim)->orWhere('tim_away', $fTim);
            });
        }

        $matches = $query->orderByDesc('tanggal')->orderByDesc('waktu')->orderByDesc('nomor_pertandingan')->get();

        return view('history', compact('liga', 'daftarTim', 'fTim', 'matches'));
    }

    public function timDetail($id)
    {
        $liga = Liga::current();
        $tim = Tim::where('liga_id', $liga->id)->findOrFail($id);
        $kl = TournamentService::hitungKlasemen($liga);

        $stat = null;
        foreach ($kl as $k) {
            if ($k['id'] === $tim->id) {
                $stat = $k;
                break;
            }
        }

        $matches = Pertandingan::where('liga_id', $liga->id)
            ->where(function ($q) use ($tim) {
                $q->where('tim_home', $tim->id)->orWhere('tim_away', $tim->id);
            })
            ->with(['homeTeam', 'awayTeam', 'pemenang'])
            ->orderByRaw('tanggal IS NULL, tanggal, waktu, nomor_pertandingan')
            ->get();

        return view('tim_detail', compact('liga', 'tim', 'stat', 'matches'));
    }

    public function pertandinganDetail($id)
    {
        $liga = Liga::current();
        $m = Pertandingan::where('liga_id', $liga->id)
            ->with(['homeTeam', 'awayTeam', 'pemenang'])
            ->findOrFail($id);

        $prediksi = $m->status !== 'selesai'
            ? TournamentService::prediksiPertandingan($liga, $m)
            : null;

        return view('pertandingan_detail', compact('liga', 'm', 'prediksi'));
    }

    public function game()
    {
        $liga = Liga::current();
        return view('game', compact('liga'));
    }
}

