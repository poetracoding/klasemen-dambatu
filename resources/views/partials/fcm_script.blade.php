@php
    $fcmSubscribeUrl = route('fcm.subscribe', [], false);
    $fcmUnsubscribeUrl = route('fcm.unsubscribe', [], false);
    $fcmSwUrl = route('fcm.sw', [], false);
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
    const PROMPT_STATE_KEY = 'dambatu_fcm_prompt_state';
    const PROMPT_NEXT_AT_KEY = 'dambatu_fcm_prompt_next_at';
    const SUBSCRIBE_URL = @json($fcmSubscribeUrl);
    const UNSUBSCRIBE_URL = @json($fcmUnsubscribeUrl);
    const SW_URL = @json($fcmSwUrl);
    const VAPID_KEY = @json($fcmVapidKey);
    const FIREBASE_CONFIG = @json($firebaseClientConfig);

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function getTomorrowMidnightMs() {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0, 0).getTime();
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

    function syncNudgeCards(show) {
        document.querySelectorAll('.fcm-nudge-card').forEach(function (card) {
            card.classList.toggle('hidden', !show);
        });
    }

    function openPromptModal() {
        const modal = document.getElementById('fcmPromptModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closePromptModal() {
        const modal = document.getElementById('fcmPromptModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function evaluatePromptAndNudgeState() {
        const hasToken = Boolean(localStorage.getItem(STORAGE_KEY));
        if (Notification.permission === 'granted' && hasToken) {
            return { subscribed: true, showNudge: false, shouldShowModal: false };
        }

        const state = localStorage.getItem(PROMPT_STATE_KEY);
        const nextAt = Number(localStorage.getItem(PROMPT_NEXT_AT_KEY) || 0);

        if (state === 'never' || Notification.permission === 'denied') {
            return { subscribed: false, showNudge: true, shouldShowModal: false };
        }

        if (state === 'later' && Date.now() < nextAt) {
            return { subscribed: false, showNudge: false, shouldShowModal: false };
        }

        return { subscribed: false, showNudge: false, shouldShowModal: true };
    }

    function syncUiState() {
        const evalState = evaluatePromptAndNudgeState();
        syncFcmButtons(evalState.subscribed);
        syncNudgeCards(evalState.showNudge);
        return evalState;
    }

    function describeFcmError(err) {
        const msg = String((err && (err.message || err.code)) || '').toLowerCase();
        const name = String((err && err.name) || '');
        if (name === 'InvalidAccessError' || msg.includes('applicationserverkey') || msg.includes('vapid')) {
            return 'VAPID Key Firebase tidak valid. Periksa FCM_VAPID_KEY di .env server.';
        }
        if (name === 'SecurityError' || msg.includes('secure origin')) {
            return 'Notifikasi membutuhkan koneksi HTTPS (atau localhost).';
        }
        if (msg.includes('permission-blocked') || Notification.permission === 'denied') {
            return 'Izin notifikasi diblokir browser. Aktifkan lewat pengaturan situs di browser Anda.';
        }
        return 'Pastikan koneksi HTTPS aktif & izin browser diberikan, lalu coba lagi.';
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
        document.querySelectorAll('.fcm-toggle-btn, .fcm-nudge-enable-btn').forEach(function (btn) {
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
            if (permission === 'denied') {
                localStorage.setItem(PROMPT_STATE_KEY, 'never');
                localStorage.removeItem(PROMPT_NEXT_AT_KEY);
                syncNudgeCards(true);
            } else {
                localStorage.setItem(PROMPT_STATE_KEY, 'later');
                localStorage.setItem(PROMPT_NEXT_AT_KEY, String(getTomorrowMidnightMs()));
            }
            showFcmToast('Izin Belum Diberikan', 'Aktifkan izin notifikasi di browser untuk menerima update skor & berita.', null);
            return false;
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
        localStorage.removeItem(PROMPT_STATE_KEY);
        localStorage.removeItem(PROMPT_NEXT_AT_KEY);
        syncFcmButtons(true);
        syncNudgeCards(false);
        showFcmToast('Notifikasi Aktif', 'Anda akan menerima notifikasi saat ada update skor & berita baru.', null);
        return true;
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
        localStorage.setItem(PROMPT_STATE_KEY, 'never');
        localStorage.removeItem(PROMPT_NEXT_AT_KEY);
        syncFcmButtons(false);
        syncNudgeCards(true);
        showFcmToast('Notifikasi Dinonaktifkan', 'Notifikasi push skor & berita telah dimatikan.', null);
    }

    function snoozePromptTomorrow() {
        localStorage.setItem(PROMPT_STATE_KEY, 'later');
        localStorage.setItem(PROMPT_NEXT_AT_KEY, String(getTomorrowMidnightMs()));
        closePromptModal();
        syncNudgeCards(false);
    }

    function declinePromptPermanently() {
        localStorage.setItem(PROMPT_STATE_KEY, 'never');
        localStorage.removeItem(PROMPT_NEXT_AT_KEY);
        closePromptModal();
        syncNudgeCards(true);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const state = syncUiState();

        if (state.subscribed) {
            const existingToken = localStorage.getItem(STORAGE_KEY);
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
            }).catch(function (err) {
                console.error('FCM Refresh Error:', err);
            });
        } else if (state.shouldShowModal) {
            setTimeout(openPromptModal, 1200);
        }

        // Jaga state card ajakan tetap konsisten setelah auto-refresh DOM
        const mainEl = document.querySelector('main');
        if (mainEl && window.MutationObserver) {
            const observer = new MutationObserver(function () {
                syncUiState();
            });
            observer.observe(mainEl, { childList: true });
        }

        const yesBtn = document.getElementById('fcmPromptYesBtn');
        const laterBtn = document.getElementById('fcmPromptLaterBtn');
        const noBtn = document.getElementById('fcmPromptNoBtn');
        const closeBtn = document.getElementById('fcmPromptCloseBtn');
        const modal = document.getElementById('fcmPromptModal');

        if (yesBtn) {
            yesBtn.addEventListener('click', function () {
                if (yesBtn.dataset.loading === '1') return;
                yesBtn.dataset.loading = '1';
                yesBtn.classList.add('opacity-70');

                subscribeBrowser()
                    .catch(function (err) {
                        console.error('FCM Subscribe Error:', err);
                        showFcmToast('Gagal Mengaktifkan Notifikasi', describeFcmError(err), null);
                    })
                    .finally(function () {
                        yesBtn.dataset.loading = '0';
                        yesBtn.classList.remove('opacity-70');
                        closePromptModal();
                    });
            });
        }

        if (laterBtn) laterBtn.addEventListener('click', snoozePromptTomorrow);
        if (closeBtn) closeBtn.addEventListener('click', snoozePromptTomorrow);
        if (noBtn) noBtn.addEventListener('click', declinePromptPermanently);
        if (modal) {
            modal.addEventListener('click', function (ev) {
                if (ev.target === modal) {
                    snoozePromptTomorrow();
                }
            });
        }
    });

    document.addEventListener('click', function (e) {
        const nudgeBtn = e.target.closest('.fcm-nudge-enable-btn');
        if (nudgeBtn && nudgeBtn.dataset.loading !== '1') {
            nudgeBtn.dataset.loading = '1';
            nudgeBtn.classList.add('opacity-60');
            subscribeBrowser()
                .catch(function (err) {
                    console.error('FCM Nudge Subscribe Error:', err);
                    showFcmToast('Gagal Mengaktifkan Notifikasi', describeFcmError(err), null);
                })
                .finally(function () {
                    nudgeBtn.dataset.loading = '0';
                    nudgeBtn.classList.remove('opacity-60');
                });
            return;
        }

        const btn = e.target.closest('.fcm-toggle-btn');
        if (!btn || btn.dataset.loading === '1') return;

        btn.dataset.loading = '1';
        btn.classList.add('opacity-60');

        const isSubscribed = Boolean(localStorage.getItem(STORAGE_KEY));
        const action = isSubscribed ? unsubscribeBrowser() : subscribeBrowser();

        action.catch(function (err) {
            console.error('FCM Toggle Error:', err);
            showFcmToast('Gagal Memproses Notifikasi', describeFcmError(err), null);
        }).finally(function () {
            btn.dataset.loading = '0';
            btn.classList.remove('opacity-60');
        });
    });

    messaging.onMessage(function (payload) {
        const notification = (payload && payload.notification) || {};
        const data = (payload && payload.data) || {};
        let targetUrl = data.url || null;
        if (targetUrl) {
            try {
                targetUrl = new URL(targetUrl, window.location.origin).href;
            } catch (e) {}
        }
        showFcmToast(
            data.title || notification.title || 'Update Liga Dam Batu',
            data.body || notification.body || '',
            targetUrl
        );
    });
})();
</script>
