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
        $likedIds = BeritaLike::where('visitor_hash', $visitorHash)
            ->whereIn('berita_id', $beritas->pluck('id'))
            ->pluck('berita_id')
            ->all();

        return view('berita', compact('liga', 'beritas', 'kategori', 'qSearch', 'likedIds'));
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
        $isLiked = BeritaLike::where('berita_id', $berita->id)
            ->where('visitor_hash', $visitorHash)
            ->exists();

        $beritaTerkait = Berita::published()
            ->where('id', '<>', $berita->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('berita_detail', compact('liga', 'berita', 'isLiked', 'beritaTerkait'));
    }

    public function toggleLike(Request $request, string $slug)
    {
        $berita = Berita::published()->where('slug', $slug)->firstOrFail();
        $visitorHash = self::resolveVisitorHash($request);

        $result = DB::transaction(function () use ($berita, $visitorHash) {
            $existing = BeritaLike::where('berita_id', $berita->id)
                ->where('visitor_hash', $visitorHash)
                ->first();

            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                BeritaLike::create([
                    'berita_id' => $berita->id,
                    'visitor_hash' => $visitorHash,
                    'created_at' => now(),
                ]);
                $liked = true;
            }

            $likesCount = BeritaLike::where('berita_id', $berita->id)->count();
            $berita->update(['likes_count' => $likesCount]);

            return [
                'liked' => $liked,
                'likes_count' => $likesCount,
            ];
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'liked' => $result['liked'],
                'likes_count' => $result['likes_count'],
            ]);
        }

        return back()->with('success', $result['liked'] ? 'Anda menyukai berita ini.' : 'Like dibatalkan.');
    }
}
