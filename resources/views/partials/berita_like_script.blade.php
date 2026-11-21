<script>
    if (!window.__beritaLikeBound) {
        window.__beritaLikeBound = true;

        const ACTIVE_CLS = ['bg-rose-50', 'dark:bg-rose-950/60', 'text-rose-600', 'dark:text-rose-400', 'border-rose-300', 'dark:border-rose-700', 'ring-1', 'ring-rose-200/70'];
        const INACTIVE_CLS = ['bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-300', 'border-slate-200', 'dark:border-slate-700', 'hover:bg-indigo-50', 'hover:border-indigo-200'];

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-react-berita');
            if (!btn) return;

            const bar = btn.closest('.berita-reaction-bar');
            if (!bar || bar.dataset.loading === '1') return;

            e.preventDefault();
            const url = bar.getAttribute('data-url');
            const reaction = btn.getAttribute('data-reaction') || 'love';
            if (!url) return;

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const token = csrfMeta ? csrfMeta.getAttribute('content') : '';

            bar.dataset.loading = '1';
            bar.classList.add('opacity-70');

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ reaction: reaction })
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    if (!data || !data.success) return;

                    const activeReaction = data.user_reaction || null;
                    const counts = data.reaction_counts || {};

                    document.querySelectorAll('.berita-reaction-bar').forEach(function (targetBar) {
                        if (targetBar.getAttribute('data-url') !== url) return;

                        targetBar.querySelectorAll('.btn-react-berita').forEach(function (rBtn) {
                            const rKey = rBtn.getAttribute('data-reaction');
                            const isActive = activeReaction === rKey;

                            rBtn.dataset.active = isActive ? '1' : '0';
                            rBtn.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                            if (isActive) {
                                rBtn.classList.remove.apply(rBtn.classList, INACTIVE_CLS);
                                rBtn.classList.add.apply(rBtn.classList, ACTIVE_CLS);
                            } else {
                                rBtn.classList.remove.apply(rBtn.classList, ACTIVE_CLS);
                                rBtn.classList.add.apply(rBtn.classList, INACTIVE_CLS);
                            }

                            const countEl = rBtn.querySelector('.reaction-count');
                            if (countEl && Object.prototype.hasOwnProperty.call(counts, rKey)) {
                                countEl.textContent = counts[rKey];
                            }
                        });
                    });
                })
                .catch(function () {
                    // Biarkan state tidak berubah jika request gagal / rate limit
                })
                .finally(function () {
                    bar.dataset.loading = '0';
                    bar.classList.remove('opacity-70');
                });
        });
    }
</script>
