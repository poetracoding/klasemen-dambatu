<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPublicVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            $this->recordVisit($request);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->ajax() || $request->hasHeader('X-Live-Request')) {
            return false;
        }

        if ($request->is('admin', 'admin/*', 'api/*', 'cetak/*', 'login', 'logout', 'up')) {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua !== '' && preg_match('/(bot|crawl|spider|slurp|curl|wget|python-requests|headless)/i', $ua)) {
            return false;
        }

        return true;
    }

    private function recordVisit(Request $request): void
    {
        try {
            $path = '/' . ltrim($request->path(), '/');
            if ($path === '//') {
                $path = '/';
            }

            $ua = (string) $request->userAgent();
            $isMobile = (bool) preg_match('/(mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini)/i', $ua);

            $sessionId = $request->hasSession() && $request->session()->getId()
                ? $request->session()->getId()
                : sha1(($request->ip() ?? '') . '|' . $ua);

            VisitorLog::create([
                'visit_date' => now()->toDateString(),
                'session_id' => mb_substr($sessionId, 0, 100),
                'ip_address' => $request->ip(),
                'path' => mb_substr($path, 0, 255),
                'page_title' => $this->resolvePageTitle($path),
                'device_type' => $isMobile ? 'mobile' : 'desktop',
                'user_agent' => mb_substr($ua, 0, 255),
                'referer' => $request->headers->get('referer') ? mb_substr((string) $request->headers->get('referer'), 0, 255) : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Jangan ganggu response utama jika pencatatan log gagal
        }
    }

    private function resolvePageTitle(string $path): string
    {
        if ($path === '/') {
            return 'Dashboard Publik';
        }
        if ($path === '/jadwal') {
            return 'Jadwal Liga';
        }
        if ($path === '/jadwal-hari') {
            return 'Jadwal Hari Ini';
        }
        if ($path === '/klasemen') {
            return 'Klasemen Liga';
        }
        if ($path === '/history') {
            return 'History Pertandingan';
        }
        if (str_starts_with($path, '/tim/')) {
            return 'Detail Tim';
        }
        if (str_starts_with($path, '/pertandingan/')) {
            return 'Detail Pertandingan';
        }

        return $path;
    }
}
