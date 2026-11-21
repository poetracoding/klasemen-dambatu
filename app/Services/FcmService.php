<?php

namespace App\Services;

use App\Models\Berita;
use App\Models\FcmToken;
use App\Models\Pertandingan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.fcm.enabled', false);
    }

    public function isClientReady(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        foreach (['project_id', 'api_key', 'messaging_sender_id', 'app_id', 'vapid_key'] as $key) {
            if (trim((string) config("services.fcm.{$key}")) === '') {
                return false;
            }
        }

        return true;
    }

    public function resolveCredentialsPath(): ?string
    {
        $configured = trim((string) config('services.fcm.credentials_path', ''));
        if ($configured === '') {
            return null;
        }

        if (is_file($configured)) {
            return $configured;
        }

        $relativeCandidate = base_path($configured);
        if (is_file($relativeCandidate)) {
            return $relativeCandidate;
        }

        return null;
    }

    public function loadServiceAccount(): ?array
    {
        $path = $this->resolveCredentialsPath();
        if ($path === null) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $clientEmail = trim((string) ($decoded['client_email'] ?? ''));
        $privateKey = (string) ($decoded['private_key'] ?? '');
        $projectId = trim((string) ($decoded['project_id'] ?? config('services.fcm.project_id', '')));

        if ($clientEmail === '' || trim($privateKey) === '' || $projectId === '') {
            return null;
        }

        $decoded['client_email'] = $clientEmail;
        $decoded['private_key'] = $privateKey;
        $decoded['project_id'] = $projectId;

        return $decoded;
    }

    public function isServerReady(): bool
    {
        return $this->isEnabled() && $this->loadServiceAccount() !== null;
    }

    public function webConfig(): array
    {
        $projectId = (string) config('services.fcm.project_id', '');
        $authDomain = (string) config('services.fcm.auth_domain', '');
        $storageBucket = (string) config('services.fcm.storage_bucket', '');

        return [
            'apiKey' => (string) config('services.fcm.api_key', ''),
            'authDomain' => $authDomain !== '' ? $authDomain : ($projectId !== '' ? "{$projectId}.firebaseapp.com" : ''),
            'projectId' => $projectId,
            'storageBucket' => $storageBucket !== '' ? $storageBucket : ($projectId !== '' ? "{$projectId}.firebasestorage.app" : ''),
            'messagingSenderId' => (string) config('services.fcm.messaging_sender_id', ''),
            'appId' => (string) config('services.fcm.app_id', ''),
            'vapidKey' => (string) config('services.fcm.vapid_key', ''),
        ];
    }

    public function getAccessToken(): ?string
    {
        $sa = $this->loadServiceAccount();
        if ($sa === null || !function_exists('openssl_sign')) {
            return null;
        }

        $cacheKey = 'fcm_oauth_token_' . md5($sa['client_email'] . '|' . $sa['project_id']);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $header = $this->base64UrlEncode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $this->base64UrlEncode((string) json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$claims}";
        $signature = '';
        $signed = @openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
        if (!$signed) {
            Log::warning('FCM: Gagal menandatangani JWT service account.');

            return null;
        }

        $jwt = $signingInput . '.' . $this->base64UrlEncode($signature);

        try {
            $response = Http::asForm()->timeout(5)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                Log::warning('FCM: Gagal menukar OAuth access token.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $token = $response->json('access_token');
            if (!is_string($token) || $token === '') {
                return null;
            }

            Cache::put($cacheKey, $token, 3000);

            return $token;
        } catch (Throwable $e) {
            Log::warning('FCM: Error HTTP saat mengambil access token.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function sendToSubscribers(string $type, string $title, string $body, string $url, array $extraData = []): array
    {
        $column = $type === 'berita' ? 'notify_berita' : 'notify_skor';

        if (!$this->isServerReady()) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0, 'skipped' => true];
        }

        $tokens = FcmToken::where($column, true)->get(['id', 'token']);

        return $this->dispatchToTokens($tokens, $type, $title, $body, $url, $extraData);
    }

    public function sendTestNotification(string $title, string $body, string $url): array
    {
        if (!$this->isServerReady()) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0, 'skipped' => true];
        }

        $tokens = FcmToken::query()->get(['id', 'token']);

        return $this->dispatchToTokens($tokens, 'test', $title, $body, $url, ['test' => '1']);
    }

    private function dispatchToTokens(Collection $tokens, string $type, string $title, string $body, string $url, array $extraData = []): array
    {
        if ($tokens->isEmpty()) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0, 'skipped' => false];
        }

        $sa = $this->loadServiceAccount();
        $accessToken = $this->getAccessToken();
        if ($sa === null || $accessToken === null) {
            return ['sent' => 0, 'failed' => $tokens->count(), 'removed' => 0, 'skipped' => false];
        }

        $projectId = $sa['project_id'];
        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $dataPayload = array_merge([
            'type' => $type,
            'url' => $url,
            'title' => $title,
            'body' => $body,
        ], $extraData);

        $stringData = [];
        foreach ($dataPayload as $k => $v) {
            $stringData[(string) $k] = (string) $v;
        }

        $sent = 0;
        $failed = 0;
        $removed = 0;

        foreach ($tokens as $row) {
            try {
                $response = Http::withToken($accessToken)
                    ->timeout(5)
                    ->post($endpoint, [
                        'message' => [
                            'token' => $row->token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'webpush' => [
                                'fcm_options' => [
                                    'link' => $url,
                                ],
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                    'icon' => asset('favicon.ico'),
                                ],
                            ],
                            'data' => $stringData,
                        ],
                    ]);

                if ($response->successful()) {
                    $sent++;
                    continue;
                }

                if ($this->shouldPruneInvalidToken($response)) {
                    FcmToken::whereKey($row->id)->delete();
                    $removed++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                Log::warning('FCM: Gagal mengirim pesan ke token.', ['token_id' => $row->id, 'error' => $e->getMessage()]);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'removed' => $removed,
            'skipped' => false,
        ];
    }

    public function notifyScoreUpdate(Pertandingan $match): array
    {
        $match->loadMissing(['homeTeam', 'awayTeam']);

        if (!in_array($match->status, ['berlangsung', 'selesai'], true)) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0, 'skipped' => true];
        }

        $home = $match->homeTeam?->nama_tim ?? ('Tim #' . $match->tim_home);
        $away = $match->awayTeam?->nama_tim ?? ('Tim #' . $match->tim_away);
        $title = $match->status === 'selesai' ? 'Hasil Akhir Liga Dam Batu' : 'Update Skor Live';
        $body = "{$home} {$match->skor_home} - {$match->skor_away} {$away}"
            . ($match->lokasi ? " ({$match->lokasi})" : '');
        $url = route('pertandingan.detail', $match->id);

        return $this->sendToSubscribers('skor', $title, $body, $url, [
            'pertandingan_id' => (string) $match->id,
            'status' => (string) $match->status,
        ]);
    }

    public function notifyBeritaPublished(Berita $berita): array
    {
        if (!$berita->isLive() || $berita->notified_at !== null || !$this->isServerReady()) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0, 'skipped' => true];
        }

        $claimed = Berita::where('id', $berita->id)
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]);

        if ($claimed === 0) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0, 'skipped' => true];
        }

        $berita->refresh();

        return $this->sendToSubscribers(
            'berita',
            "Berita Baru: {$berita->kategori}",
            $berita->judul,
            route('berita.show', $berita->slug),
            [
                'berita_id' => (string) $berita->id,
                'slug' => $berita->slug,
            ]
        );
    }

    public function dispatchDueScheduledBeritaNotifications(): int
    {
        if (!$this->isServerReady()) {
            return 0;
        }

        $dispatched = 0;
        $dueBeritas = Berita::published()
            ->whereNull('notified_at')
            ->orderBy('published_at')
            ->get();

        foreach ($dueBeritas as $berita) {
            $res = $this->notifyBeritaPublished($berita);
            if (($res['skipped'] ?? false) === false) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    private function shouldPruneInvalidToken($response): bool
    {
        $status = $response->status();
        $errorCode = (string) $response->json('error.details.0.errorCode', '');
        $errorStatus = (string) $response->json('error.status', '');
        $message = strtolower((string) $response->json('error.message', ''));

        if ($status === 404 || $errorCode === 'UNREGISTERED' || $errorStatus === 'NOT_FOUND') {
            return true;
        }

        if (($errorCode === 'INVALID_ARGUMENT' || $errorStatus === 'INVALID_ARGUMENT')
            && str_contains($message, 'registration token')) {
            return true;
        }

        return false;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
