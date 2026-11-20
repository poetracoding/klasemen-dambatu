<script>
    if (!window.__beritaLikeBound) {
        window.__beritaLikeBound = true;
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-like-berita');
            if (!btn || btn.dataset.loading === '1') return;

            e.preventDefault();
            const url = btn.getAttribute('data-url');
            if (!url) return;

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const token = csrfMeta ? csrfMeta.getAttribute('content') : '';

            btn.dataset.loading = '1';
            btn.classList.add('opacity-70');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({})
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    if (!data || !data.success) return;

                    const liked = Boolean(data.liked);
                    const countEl = btn.querySelector('.like-count');
                    const iconEl = btn.querySelector('.like-icon');
                    const labelEl = btn.querySelector('.like-label');

                    if (countEl) countEl.textContent = data.likes_count;

                    if (liked) {
                        btn.dataset.liked = '1';
                        btn.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300', 'border-slate-200', 'dark:border-slate-700', 'hover:bg-rose-50', 'hover:text-rose-600');
                        btn.classList.add('bg-rose-50', 'dark:bg-rose-950/60', 'text-rose-600', 'dark:text-rose-400', 'border-rose-200', 'dark:border-rose-800');
                        if (iconEl) iconEl.className = 'like-icon bi bi-heart-fill text-rose-500';
                        if (labelEl) labelEl.textContent = 'Disukai';
                    } else {
                        btn.dataset.liked = '0';
                        btn.classList.remove('bg-rose-50', 'dark:bg-rose-950/60', 'text-rose-600', 'dark:text-rose-400', 'border-rose-200', 'dark:border-rose-800');
                        btn.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300', 'border-slate-200', 'dark:border-slate-700', 'hover:bg-rose-50', 'hover:text-rose-600');
                        if (iconEl) iconEl.className = 'like-icon bi bi-heart';
                        if (labelEl) labelEl.textContent = 'Suka';
                    }
                })
                .catch(function () {
                    // Biarkan state tidak berubah jika request gagal / rate limit
                })
                .finally(function () {
                    btn.dataset.loading = '0';
                    btn.classList.remove('opacity-70');
                });
        });
    }
</script>
