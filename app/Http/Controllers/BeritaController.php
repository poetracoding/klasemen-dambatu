<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\BeritaLike;
use App\Models\Liga;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BeritaController extends Controller
{
    public static function resolveVisitorHash(Request $request): string
    {
        $userAgent = mb_substr((string) $request->userAgent(), 0, 150);

        return hash('sha256', config('app.key') . '|' . ($request->ip() ?? '') . '|' . $userAgent);
    }

    public function index(Request $request)
    {
        $liga = Liga::current();
        $kategori = trim((string) $request->get('kategori', ''));
        if (!in_array($kategori, Berita::KATEGORI_LIST, true)) {
            $kategori = '';
        }
        $qSearch = trim((string) $request->get('q', ''));

        $query = Berita::published()->with('author');

        if ($kategori !== '') {
            $query->where('kategori', $kategori);
        }

        if ($qSearch !== '') {
            $like = '%' . $qSearch . '%';
            $query->where(function ($q) use ($like) {
                $q->where('judul', 'like', $like)
                    ->orWhere('ringkasan', 'like', $like)
                    ->orWhere('isi', 'like', $like);
            });
        }

        $beritas = $query->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9)
            ->withQueryString();

        $visitorHash = self::resolveVisitorHash($request);
        $beritaIds = $beritas->pluck('id')->all();
        $reactionCountsMap = BeritaLike::countsForBeritas($beritaIds);
        $visitorReactions = BeritaLike::visitorReactionsForBeritas($visitorHash, $beritaIds);
        $likedIds = array_keys($visitorReactions);

        return view('berita', compact('liga', 'beritas', 'kategori', 'qSearch', 'likedIds', 'reactionCountsMap', 'visitorReactions'));
    }

    public function show(Request $request, string $slug)
    {
        $liga = Liga::current();
        $berita = Berita::published()
            ->where('slug', $slug)
            ->with('author')
            ->firstOrFail();

        $sessionKey = 'viewed_berita_' . $berita->id;
        if ($request->hasSession() && !$request->session()->has($sessionKey)) {
            $request->session()->put($sessionKey, true);
            $berita->increment('views_count');
            $berita->refresh();
        }

        $visitorHash = self::resolveVisitorHash($request);
        $reactionCounts = BeritaLike::countsForBeritas([$berita->id])[$berita->id] ?? BeritaLike::emptyCounts();
        $userReaction = BeritaLike::visitorReactionsForBeritas($visitorHash, [$berita->id])[$berita->id] ?? null;
        $isLiked = $userReaction !== null;

        $beritaTerkait = Berita::published()
            ->where('id', '<>', $berita->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('berita_detail', compact('liga', 'berita', 'isLiked', 'userReaction', 'reactionCounts', 'beritaTerkait'));
    }

    public function toggleLike(Request $request, string $slug)
    {
        $berita = Berita::published()->where('slug', $slug)->firstOrFail();

        $reaction = strtolower(trim((string) $request->input('reaction', 'love')));
        if ($reaction === '') {
            $reaction = 'love';
        }

        if (!array_key_exists($reaction, BeritaLike::REACTIONS)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reaksi tidak valid.',
                ], 422);
            }

            return back()->with('error', 'Reaksi tidak valid.');
        }

        $visitorHash = self::resolveVisitorHash($request);

        $result = DB::transaction(function () use ($berita, $visitorHash, $reaction) {
            $existing = BeritaLike::where('berita_id', $berita->id)
                ->where('visitor_hash', $visitorHash)
                ->first();

            if ($existing) {
                $existingReaction = array_key_exists((string) $existing->reaction, BeritaLike::REACTIONS)
                    ? (string) $existing->reaction
                    : 'love';

                if ($existingReaction === $reaction) {
                    $existing->delete();
                    $liked = false;
                    $userReaction = null;
                } else {
                    $existing->update([
                        'reaction' => $reaction,
                        'created_at' => now(),
                    ]);
                    $liked = true;
                    $userReaction = $reaction;
                }
            } else {
                BeritaLike::create([
                    'berita_id' => $berita->id,
                    'visitor_hash' => $visitorHash,
                    'reaction' => $reaction,
                    'created_at' => now(),
                ]);
                $liked = true;
                $userReaction = $reaction;
            }

            $reactionCounts = BeritaLike::countsForBeritas([$berita->id])[$berita->id] ?? BeritaLike::emptyCounts();
            $likesCount = array_sum($reactionCounts);
            $berita->update(['likes_count' => $likesCount]);

            return [
                'liked' => $liked,
                'user_reaction' => $userReaction,
                'likes_count' => $likesCount,
                'reaction_counts' => $reactionCounts,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'liked' => $result['liked'],
                'user_reaction' => $result['user_reaction'],
                'likes_count' => $result['likes_count'],
                'reaction_counts' => $result['reaction_counts'],
            ]);
        }

        return back()->with('success', $result['liked'] ? 'Reaksi Anda berhasil disimpan.' : 'Reaksi dibatalkan.');
    }
}
