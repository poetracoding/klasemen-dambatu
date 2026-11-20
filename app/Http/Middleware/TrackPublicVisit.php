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
            $clientIp = $this->resolveClientIp($request);

            $sessionId = $request->hasSession() && $request->session()->getId()
                ? $request->session()->getId()
                : sha1(($clientIp ?? '') . '|' . $ua);

            VisitorLog::create([
                'visit_date' => now()->toDateString(),
                'session_id' => mb_substr($sessionId, 0, 100),
                'ip_address' => mb_substr($clientIp, 0, 45),
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

    /**
     * Dapatkan IP address klien secara aman dengan memvalidasi reverse proxy lokal.
     * Mencegah IP spoofing dari pihak luar atau jaringan LAN.
     */
    private function resolveClientIp(Request $request): string
    {
        $rawRemote = (string) $request->server('REMOTE_ADDR');

        // Hanya percaya header proxy jika koneksi berasal dari localhost (cloudflared / nginx lokal)
        if (in_array($rawRemote, ['127.0.0.1', '::1'], true)) {
            $cfIp = $request->header('cf-connecting-ip');
            if ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }

            $forwarded = $request->header('x-forwarded-for');
            if ($forwarded) {
                $parts = explode(',', $forwarded);
                $firstIp = trim($parts[0] ?? '');
                if (filter_var($firstIp, FILTER_VALIDATE_IP)) {
                    return $firstIp;
                }
            }
        }

        return $request->ip() ?: '127.0.0.1';
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
        if ($path === '/game') {
            return 'Game Dam Batu';
        }
        if ($path === '/berita') {
            return 'Berita Turnamen';
        }
        if (str_starts_with($path, '/berita/')) {
            return 'Baca Berita';
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
