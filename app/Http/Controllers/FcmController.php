<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Models\Liga;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FcmController extends Controller
{
    public function serviceWorker(FcmService $fcm): Response
    {
        if (!$fcm->isClientReady()) {
            $js = "// FCM Web Push belum diaktifkan. Isi konfigurasi FCM_* di .env untuk mengaktifkan.\n";

            return response($js, 200, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Service-Worker-Allowed' => '/',
            ]);
        }

        $cfg = $fcm->webConfig();
        $firebaseConfigJson = json_encode([
            'apiKey' => $cfg['apiKey'],
            'authDomain' => $cfg['authDomain'],
            'projectId' => $cfg['projectId'],
            'storageBucket' => $cfg['storageBucket'],
            'messagingSenderId' => $cfg['messagingSenderId'],
            'appId' => $cfg['appId'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $js = <<<JS
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

firebase.initializeApp({$firebaseConfigJson});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
    // Jika payload masih membawa blok notification bawaan, biarkan SDK menampilkannya sekali saja
    if (payload && payload.notification) {
        return;
    }

    const data = (payload && payload.data) || {};
    const title = data.title || 'Update Liga Dam Batu';
    const options = {
        body: data.body || 'Ada pembaruan skor atau berita terbaru.',
        icon: data.icon || '/favicon.ico',
        tag: data.tag || ('dambatu-' + (data.type || 'update')),
        renotify: true,
        data: {
            url: data.url || '/'
        }
    };

    return self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const rawUrl = (event.notification && event.notification.data && event.notification.data.url) || '/';
    let targetUrl = self.location.origin + '/';
    try {
        targetUrl = new URL(rawUrl, self.location.origin).href;
    } catch (e) {}

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
            return null;
        })
    );
});
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    public function manifest(): JsonResponse
    {
        $liga = Liga::first();
        $ligaName = $liga?->nama_liga ?? 'Liga Dam Batu';
        $themeColor = $liga?->theme_color ?: '#4f46e5';
        $customIcon = $liga?->appIconUrl();

        $icons = $customIcon
            ? [[
                'src' => $customIcon,
                'sizes' => '192x192 512x512',
                'type' => 'image/png',
            ]]
            : [[
                'src' => '/favicon.ico',
                'sizes' => '64x64 32x32 24x24 16x16',
                'type' => 'image/x-icon',
            ]];

        return response()->json([
            'name' => $ligaName,
            'short_name' => 'Dam Batu',
            'start_url' => '/',
            'display' => 'standalone',
            'theme_color' => $themeColor,
            'background_color' => '#0f172a',
            'icons' => $icons,
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|min:20|max:512',
            'notify_skor' => 'sometimes|boolean',
            'notify_berita' => 'sometimes|boolean',
        ]);

        $token = trim($validated['token']);
        $tokenHash = FcmToken::hashToken($token);

        FcmToken::updateOrCreate(
            ['token_hash' => $tokenHash],
            [
                'token' => $token,
                'notify_skor' => array_key_exists('notify_skor', $validated) ? (bool) $validated['notify_skor'] : true,
                'notify_berita' => array_key_exists('notify_berita', $validated) ? (bool) $validated['notify_berita'] : true,
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'subscribed' => true,
        ]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|min:20|max:512',
        ]);

        $tokenHash = FcmToken::hashToken($validated['token']);
        FcmToken::where('token_hash', $tokenHash)->delete();

        return response()->json([
            'success' => true,
            'subscribed' => false,
        ]);
    }
}
