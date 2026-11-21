@php
    $fcmSubscribeUrl = route('fcm.subscribe');
    $fcmUnsubscribeUrl = route('fcm.unsubscribe');
    $fcmSwUrl = route('fcm.sw');
    $fcmVapidKey = (string) ($fcmConfig['vapidKey'] ?? '');
    $firebaseClientConfig = [
        'apiKey' => (string) ($fcmConfig['apiKey'] ?? ''),
        'authDomain' => (string) ($fcmConfig['authDomain'] ?? ''),
        'projectId' => (string) ($fcmConfig['projectId'] ?? ''),
        'storageBucket' => (string) ($fcmConfig['storageBucket'] ?? ''),
        'messagingSenderId' => (string) ($fcmConfig['messagingSenderId'] ?? ''),
        'appId' => (string) ($fcmConfig['appId'] ?? ''),
    ];
@endphp
<script data-cfasync="false" src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
<script data-cfasync="false" src="https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js"></script>
<script data-cfasync="false">
(function () {
    if (window.__fcmBound) return;
    window.__fcmBound = true;

    const STORAGE_KEY = 'dambatu_fcm_token';
    const SUBSCRIBE_URL = @json($fcmSubscribeUrl);
    const UNSUBSCRIBE_URL = @json($fcmUnsubscribeUrl);
    const SW_URL = @json($fcmSwUrl);
    const VAPID_KEY = @json($fcmVapidKey);
    const FIREBASE_CONFIG = @json($firebaseClientConfig);

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function syncFcmButtons(active) {
        document.querySelectorAll('.fcm-toggle-btn').forEach(function (btn) {
            const icon = btn.querySelector('.fcm-bell-icon');
            const dot = btn.querySelector('.fcm-bell-dot');
            btn.dataset.subscribed = active ? '1' : '0';
            btn.title = active
                ? 'Notifikasi Skor & Berita Aktif (Klik untuk Nonaktifkan)'
                : 'Aktifkan Notifikasi Skor & Berita';
            if (icon) {
                icon.className = active
                    ? 'fcm-bell-icon bi bi-bell-fill text-indigo-600 dark:text-indigo-400 text-base'
                    : 'fcm-bell-icon bi bi-bell text-base';
            }
            if (dot) {
                dot.classList.toggle('hidden', !active);
            }
        });
    }

    function showFcmToast(title, body, targetUrl) {
        const existing = document.getElementById('fcmFloatingToast');
        if (existing) existing.remove();

        const wrap = document.createElement('div');
        wrap.id = 'fcmFloatingToast';
        wrap.className = 'fixed bottom-4 right-4 z-[90] max-w-xs w-full bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-700/80 p-3.5 flex items-start gap-3 transition-all duration-300';

        const iconBox = document.createElement('div');
        iconBox.className = 'w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0';
        const bellIcon = document.createElement('i');
        bellIcon.className = 'bi bi-bell-fill text-sm';
        iconBox.appendChild(bellIcon);

        const contentBox = document.createElement('div');
        contentBox.className = 'flex-1 min-w-0';
        const titleEl = document.createElement('div');
        titleEl.className = 'text-xs font-bold text-white leading-snug';
        titleEl.textContent = title || 'Liga Dam Batu';
        contentBox.appendChild(titleEl);

        if (body) {
            const bodyEl = document.createElement('div');
            bodyEl.className = 'text-[11px] text-slate-300 mt-0.5 leading-snug line-clamp-2';
            bodyEl.textContent = body;
            contentBox.appendChild(bodyEl);
        }

        if (targetUrl) {
            wrap.classList.add('cursor-pointer', 'hover:border-indigo-500');
            wrap.addEventListener('click', function (ev) {
                if (ev.target.closest('button')) return;
                window.location.href = targetUrl;
            });
        }

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'text-slate-400 hover:text-white p-0.5 shrink-0 cursor-pointer';
        closeBtn.setAttribute('aria-label', 'Tutup');
        const closeIcon = document.createElement('i');
        closeIcon.className = 'bi bi-x-lg text-xs';
        closeBtn.appendChild(closeIcon);
        closeBtn.addEventListener('click', function () {
            wrap.remove();
        });

        wrap.appendChild(iconBox);
        wrap.appendChild(contentBox);
        wrap.appendChild(closeBtn);
        document.body.appendChild(wrap);

        setTimeout(function () {
            if (wrap.parentNode) wrap.remove();
        }, 6500);
    }

    const supported = ('Notification' in window) && ('serviceWorker' in navigator) && ('PushManager' in window);
    if (!supported || typeof firebase === 'undefined') {
        document.querySelectorAll('.fcm-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showFcmToast('Browser Tidak Mendukung', 'Gunakan HTTPS (atau tambahkan ke Layar Utama di iOS) untuk mengaktifkan notifikasi.', null);
            });
        });
        return;
    }

    if (!firebase.apps.length) {
        firebase.initializeApp(FIREBASE_CONFIG);
    }

    const messaging = firebase.messaging();
    let swRegPromise = null;

    function getSwRegistration() {
        if (!swRegPromise) {
            swRegPromise = navigator.serviceWorker.register(SW_URL, { scope: '/' });
        }
        return swRegPromise;
    }

    async function subscribeBrowser() {
        const permission = Notification.permission === 'granted'
            ? 'granted'
            : await Notification.requestPermission();

        if (permission !== 'granted') {
            showFcmToast('Izin Ditolak', 'Aktifkan izin notifikasi di pengaturan browser untuk menerima update skor & berita.', null);
            return;
        }

        const reg = await getSwRegistration();
        await navigator.serviceWorker.ready;
        const token = await messaging.getToken({
            vapidKey: VAPID_KEY,
            serviceWorkerRegistration: reg
        });

        if (!token) {
            throw new Error('Token FCM tidak diterima');
        }

        const res = await fetch(SUBSCRIBE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                token: token,
                notify_skor: true,
                notify_berita: true
            })
        });

        if (!res.ok) {
            throw new Error('Gagal menyimpan token notifikasi');
        }

        localStorage.setItem(STORAGE_KEY, token);
        syncFcmButtons(true);
        showFcmToast('Notifikasi Aktif', 'Anda akan menerima notifikasi saat ada update skor & berita baru.', null);
    }

    async function unsubscribeBrowser() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            await fetch(UNSUBSCRIBE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ token: stored })
            }).catch(function () {});
        }

        try {
            await messaging.deleteToken();
        } catch (e) {}

        localStorage.removeItem(STORAGE_KEY);
        syncFcmButtons(false);
        showFcmToast('Notifikasi Dinonaktifkan', 'Notifikasi push skor & berita telah dimatikan.', null);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const existingToken = localStorage.getItem(STORAGE_KEY);
        if (Notification.permission === 'granted' && existingToken) {
            syncFcmButtons(true);
            getSwRegistration().then(function () {
                return navigator.serviceWorker.ready;
            }).then(function (reg) {
                return messaging.getToken({
                    vapidKey: VAPID_KEY,
                    serviceWorkerRegistration: reg
                });
            }).then(function (freshToken) {
                if (freshToken && freshToken !== existingToken) {
                    localStorage.setItem(STORAGE_KEY, freshToken);
                    fetch(SUBSCRIBE_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            token: freshToken,
                            notify_skor: true,
                            notify_berita: true
                        })
                    }).catch(function () {});
                }
            }).catch(function () {});
        } else {
            syncFcmButtons(false);
        }
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.fcm-toggle-btn');
        if (!btn || btn.dataset.loading === '1') return;

        btn.dataset.loading = '1';
        btn.classList.add('opacity-60');

        const isSubscribed = Boolean(localStorage.getItem(STORAGE_KEY));
        const action = isSubscribed ? unsubscribeBrowser() : subscribeBrowser();

        action.catch(function () {
            showFcmToast('Gagal Memproses Notifikasi', 'Pastikan koneksi HTTPS aktif dan coba lagi.', null);
        }).finally(function () {
            btn.dataset.loading = '0';
            btn.classList.remove('opacity-60');
        });
    });

    messaging.onMessage(function (payload) {
        const notification = (payload && payload.notification) || {};
        const data = (payload && payload.data) || {};
        showFcmToast(
            notification.title || data.title || 'Update Liga Dam Batu',
            notification.body || data.body || '',
            data.url || null
        );
    });
})();
</script>
