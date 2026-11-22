<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\BeritaLike;
use App\Models\Liga;
use App\Models\Pertandingan;
use App\Models\PertandinganCheer;
use App\Models\PertandinganVote;
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
        $trenList = TournamentService::analisisTrenTim($liga);
        $mvpData = $this->buildMvpHighlight($liga, $kl, $trenList);

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
            'mvpData',
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
        $trenList = TournamentService::analisisTrenTim($liga);
        $trendMap = [];
        foreach ($trenList as $t) {
            $trendMap[$t['id']] = $t;
        }
        return view('klasemen', compact('liga', 'kl', 'trendMap'));
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

    public function pertandinganDetail(Request $request, $id)
    {
        $liga = Liga::current();
        $m = Pertandingan::where('liga_id', $liga->id)
            ->with(['homeTeam', 'awayTeam', 'pemenang'])
            ->findOrFail($id);

        $prediksi = $m->status !== 'selesai'
            ? TournamentService::prediksiPertandingan($liga, $m)
            : null;

        $visitorHash = BeritaController::resolveVisitorHash($request);
        $voteSummary = PertandinganVote::summaryForMatch($m->id, $visitorHash);
        $cheerSummary = PertandinganCheer::summaryForMatch($m->id, $visitorHash);

        return view('pertandingan_detail', compact('liga', 'm', 'prediksi', 'voteSummary', 'cheerSummary'));
    }

    public function voteMatch(Request $request, $id)
    {
        $liga = Liga::current();
        $m = Pertandingan::where('liga_id', $liga->id)->findOrFail($id);

        if ($m->status === 'selesai') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Polling ditutup karena pertandingan sudah selesai.'], 422);
            }
            return back()->with('warning', 'Polling ditutup karena pertandingan sudah selesai.');
        }

        $choice = strtolower(trim((string) $request->input('choice', '')));
        if (!in_array($choice, ['home', 'away'], true)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Pilihan voting tidak valid.'], 422);
            }
            return back()->with('danger', 'Pilihan voting tidak valid.');
        }

        $visitorHash = BeritaController::resolveVisitorHash($request);
        $existing = PertandinganVote::where('pertandingan_id', $m->id)
            ->where('visitor_hash', $visitorHash)
            ->first();

        if ($existing) {
            if ($existing->choice === $choice) {
                $existing->delete();
            } else {
                $existing->update(['choice' => $choice]);
            }
        } else {
            PertandinganVote::create([
                'pertandingan_id' => $m->id,
                'visitor_hash' => $visitorHash,
                'choice' => $choice,
            ]);
        }

        $summary = PertandinganVote::summaryForMatch($m->id, $visitorHash);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
            ]);
        }

        return back()->with('success', 'Dukungan polling Anda berhasil disimpan.');
    }

    public function cheerMatch(Request $request, $id)
    {
        $liga = Liga::current();
        $m = Pertandingan::where('liga_id', $liga->id)->findOrFail($id);

        $reaction = strtolower(trim((string) $request->input('reaction', '')));
        if (!array_key_exists($reaction, PertandinganCheer::REACTIONS)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Reaksi sorakan tidak valid.'], 422);
            }
            return back()->with('danger', 'Reaksi sorakan tidak valid.');
        }

        $visitorHash = BeritaController::resolveVisitorHash($request);
        $existing = PertandinganCheer::where('pertandingan_id', $m->id)
            ->where('visitor_hash', $visitorHash)
            ->first();

        if ($existing) {
            if ($existing->reaction === $reaction) {
                $existing->delete();
            } else {
                $existing->update(['reaction' => $reaction]);
            }
        } else {
            PertandinganCheer::create([
                'pertandingan_id' => $m->id,
                'visitor_hash' => $visitorHash,
                'reaction' => $reaction,
            ]);
        }

        $summary = PertandinganCheer::summaryForMatch($m->id, $visitorHash);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
            ]);
        }

        return back()->with('success', 'Sorakan Anda terkirim ke arena!');
    }

    public function gamesHub()
    {
        $liga = Liga::current();
        return view('games_hub', compact('liga'));
    }

    private function buildMvpHighlight(Liga $liga, array $kl, array $trenList): ?array
    {
        $played = array_values(array_filter($kl, fn ($r) => (int) ($r['mp'] ?? 0) > 0));
        if (empty($played)) {
            return null;
        }

        // MVP berdasarkan Win Rate tertinggi, lalu PTS & selisih skor
        $byWr = $played;
        usort($byWr, function ($a, $b) {
            $wrA = $a['mp'] > 0 ? ($a['w'] / $a['mp']) : 0;
            $wrB = $b['mp'] > 0 ? ($b['w'] / $b['mp']) : 0;
            if (abs($wrB - $wrA) > 0.0001) {
                return $wrB <=> $wrA;
            }
            if ($b['pts'] !== $a['pts']) {
                return $b['pts'] <=> $a['pts'];
            }
            return $b['sb'] <=> $a['sb'];
        });
        $mvp = $byWr[0];
        $mvp['wr'] = $mvp['mp'] > 0 ? round(($mvp['w'] / $mvp['mp']) * 100, 1) : 0;

        $bySb = $played;
        usort($bySb, fn ($a, $b) => $b['sb'] <=> $a['sb']);
        $bestSb = $bySb[0];

        $hotStreak = null;
        foreach ($trenList as $t) {
            if (($t['mp'] ?? 0) > 0 && ($t['streak_type'] ?? '') === 'W') {
                if (!$hotStreak || ($t['streak_count'] ?? 0) > ($hotStreak['streak_count'] ?? 0)) {
                    $hotStreak = $t;
                }
            }
        }

        $leader = $kl[0];
        $shareLines = [
            "🏆 *Update {$liga->nama_liga} ({$liga->musim})*",
            "👑 Pemuncak Klasemen: *{$leader['nama_tim']}* ({$leader['pemain1']} & {$leader['pemain2']}) - {$leader['pts']} PTS (SB " . ($leader['sb'] > 0 ? '+' : '') . "{$leader['sb']})",
            "⭐ MVP Win Rate: *{$mvp['nama_tim']}* ({$mvp['wr']}% | {$mvp['w']}M-{$mvp['l']}K)",
            "🎯 Selisih Batu Terbaik: *{$bestSb['nama_tim']}* (" . ($bestSb['sb'] > 0 ? '+' : '') . "{$bestSb['sb']})",
            "Cek klasemen & jadwal lengkap: " . route('klasemen'),
        ];

        return [
            'mvp' => $mvp,
            'best_sb' => $bestSb,
            'hot_streak' => $hotStreak,
            'share_text' => implode("\n", $shareLines),
        ];
    }

    public function game()
    {
        $liga = Liga::current();
        return view('game', compact('liga'));
    }

    public function monopoli()
    {
        $liga = Liga::current();
        return view('admin.monopoli', compact('liga'));
    }

    public function flappyBird()
    {
        $liga = Liga::current();
        return view('admin.flappy_bird', compact('liga'));
    }

    public function ludoQueen()
    {
        $liga = Liga::current();
        return view('admin.ludo_queen', compact('liga'));
    }

    public function catur()
    {
        $liga = Liga::current();
        return view('admin.catur', compact('liga'));
    }
}

