document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile navigation toggle
    var navToggle = document.getElementById('navToggle');
    var mobileMenu = document.getElementById('mobileMenu');
    if (navToggle && mobileMenu) {
        navToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // 2. Dismiss flash alert & SweetAlert2 Flash Notifications
    function bindAlertButtons() {
        document.querySelectorAll('.alert-close-btn').forEach(function (btn) {
            btn.onclick = function () {
                var box = btn.closest('.alert-box');
                if (box) {
                    box.style.transition = 'opacity 0.25s ease-out, transform 0.25s ease-out';
                    box.style.opacity = '0';
                    box.style.transform = 'translateY(-6px)';
                    setTimeout(function () {
                        box.remove();
                    }, 250);
                }
            };
        });
    }
    bindAlertButtons();

    // Trigger SweetAlert2 for session notifications on page load
    function triggerSweetAlertFlash() {
        if (!window.Swal) return;

        // Cek alert success
        var successAlert = document.querySelector('.alert-box.bg-emerald-50, .alert-box.border-emerald-200');
        if (successAlert) {
            var msgEl = successAlert.querySelector('div:not(.flex)');
            var msg = msgEl ? msgEl.innerText.trim() : successAlert.innerText.trim();
            if (msg) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: msg,
                    timer: 3500,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    customClass: {
                        popup: 'swal2-toast-modern'
                    }
                });
            }
        }

        // Cek alert error/danger
        var dangerAlert = document.querySelector('.alert-box.bg-rose-50, .alert-box.border-rose-200');
        if (dangerAlert) {
            var dMsgEl = dangerAlert.querySelector('div:not(.flex)');
            var dMsg = dMsgEl ? dMsgEl.innerText.trim() : dangerAlert.innerText.trim();
            if (dMsg) {
                Swal.fire({
                    icon: 'error',
                    title: 'Pemberitahuan',
                    text: dMsg,
                    confirmButtonColor: '#e11d48',
                    confirmButtonText: 'Tutup',
                    customClass: {
                        popup: 'swal2-modern-popup',
                        confirmButton: 'swal2-modern-confirm'
                    }
                });
            }
        }

        // Cek alert warning
        var warningAlert = document.querySelector('.alert-box.bg-amber-50, .alert-box.border-amber-200');
        if (warningAlert) {
            var wMsgEl = warningAlert.querySelector('div:not(.flex)');
            var wMsg = wMsgEl ? wMsgEl.innerText.trim() : warningAlert.innerText.trim();
            if (wMsg) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: wMsg,
                    confirmButtonColor: '#f59e0b',
                    confirmButtonText: 'Mengerti',
                    customClass: {
                        popup: 'swal2-modern-popup',
                        confirmButton: 'swal2-modern-confirm'
                    }
                });
            }
        }
    }
    triggerSweetAlertFlash();

    // 3. Confirmation for all CRUD & Logout using SweetAlert2
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.dataset.swalConfirmed === 'true') {
            return;
        }

        var isLogout = form.classList.contains('form-logout') || (form.action && form.action.indexOf('/logout') !== -1);
        var isDelete = form.classList.contains('form-delete') || !!form.querySelector('input[name="_method"][value="DELETE"]');
        var msg = form.getAttribute('data-confirm');
        var title = form.getAttribute('data-confirm-title');
        var btnText = form.getAttribute('data-confirm-btn');

        if (isLogout && !msg) {
            title = 'Konfirmasi Keluar';
            msg = 'Apakah Anda yakin ingin keluar dari panel admin?';
            btnText = 'Ya, Keluar';
        }

        if (msg) {
            e.preventDefault();
            var isDanger = isDelete || isLogout || form.hasAttribute('data-confirm-danger');

            if (window.Swal) {
                Swal.fire({
                    title: title || (isDanger ? 'Konfirmasi Tindakan' : 'Konfirmasi'),
                    text: msg,
                    icon: isDanger ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isDanger ? '#e11d48' : '#4f46e5',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: btnText || (isDanger ? 'Ya, Lanjutkan' : 'Ya, Setuju'),
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: isDanger,
                    customClass: {
                        popup: 'swal2-modern-popup',
                        confirmButton: 'swal2-modern-confirm',
                        cancelButton: 'swal2-modern-cancel'
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.dataset.swalConfirmed = 'true';
                        form.submit();
                    }
                });
            } else {
                if (window.confirm(msg)) {
                    form.dataset.swalConfirmed = 'true';
                    form.submit();
                }
            }
        }
    });

    // 4. Match result form synchronization: winner dropdown is active only if status is 'selesai'
    function bindResultForms() {
        document.querySelectorAll('form.form-hasil').forEach(function (f) {
            var row = f.closest('tr') || f;
            var status = f.elements['status'] || document.querySelector('[form="' + f.id + '"][name="status"]');
            var winner = f.elements['pemenang_id'] || document.querySelector('[form="' + f.id + '"][name="pemenang_id"]');
            var homeInput = row.querySelector('.score-input-home');
            var awayInput = row.querySelector('.score-input-away');

            if (!status || !winner) return;

            function sync() {
                var on = status.value === 'selesai';
                winner.disabled = !on;
                if (!on) {
                    winner.value = '';
                    winner.classList.add('opacity-50', 'bg-slate-100');
                } else {
                    winner.classList.remove('opacity-50', 'bg-slate-100');
                }
            }

            status.addEventListener('change', sync);
            sync();

            // Auto-pilih pemenang berdasarkan skor yang diketik
            function handleScoreChange() {
                if (!homeInput || !awayInput) return;
                var sh = parseInt(homeInput.value, 10);
                var sa = parseInt(awayInput.value, 10);
                if (!isNaN(sh) && !isNaN(sa)) {
                    // Jika ada skor dan status masih belum_main, ubah ke selesai
                    if (status.value === 'belum_main' && (sh > 0 || sa > 0)) {
                        status.value = 'selesai';
                        sync();
                    }
                    if (winner.options.length >= 3) {
                        var homeVal = winner.options[1].value;
                        var awayVal = winner.options[2].value;
                        if (sh > sa) {
                            winner.value = homeVal;
                        } else if (sa > sh) {
                            winner.value = awayVal;
                        }
                    }
                }
            }

            if (homeInput && awayInput) {
                homeInput.addEventListener('input', handleScoreChange);
                awayInput.addEventListener('input', handleScoreChange);
            }
        });
    }
    bindResultForms();

    // 5. Live Auto-Update untuk halaman publik (tanpa refresh browser)
    var isAreaAdmin = document.body.getAttribute('data-area') === 'admin';
    if (!isAreaAdmin) {
        var base = (window.BASE_URL || '').replace(/\/+$/, '');
        var checkUrl = (base ? base : '') + '/api/check_update.php';
        var currentHash = null;
        var isFetching = false;

        function checkLiveUpdate() {
            if (isFetching) return;
            fetch(checkUrl, { cache: 'no-store' })
                .then(function (res) {
                    if (!res.ok) throw new Error('API unavailable');
                    return res.json();
                })
                .then(function (data) {
                    if (data && data.success && data.hash) {
                        if (currentHash === null) {
                            currentHash = data.hash;
                        } else if (data.hash !== currentHash) {
                            currentHash = data.hash;
                            fetchFreshHTML();
                        }
                    }
                })
                .catch(function () {
                    // Silently ignore intermittent connectivity
                });
        }

        function fetchFreshHTML() {
            isFetching = true;
            fetch(window.location.href, {
                cache: 'no-store',
                headers: { 'X-Live-Request': '1' }
            })
                .then(function (res) { return res.text(); })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var newMain = doc.querySelector('main');
                    var curMain = document.querySelector('main');
                    if (newMain && curMain) {
                        curMain.innerHTML = newMain.innerHTML;
                        bindAlertButtons();
                        showLiveToast('Data pertandingan & klasemen berhasil diperbarui!');
                    }
                })
                .catch(function (e) {
                    console.warn('Live fetch error:', e);
                })
                .finally(function () {
                    isFetching = false;
                });
        }

        function showLiveToast(msg) {
            var existing = document.getElementById('liveToastBox');
            if (existing) existing.remove();

            var toast = document.createElement('div');
            toast.id = 'liveToastBox';
            toast.className = 'fixed bottom-5 right-5 z-50 flex items-center gap-2 bg-slate-900/95 text-white text-xs font-semibold px-4 py-2.5 rounded-2xl shadow-xl border border-slate-700 transition-all duration-300 transform translate-y-3 opacity-0';
            toast.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span><span>' + msg + '</span>';
            document.body.appendChild(toast);

            setTimeout(function () {
                toast.classList.remove('translate-y-3', 'opacity-0');
            }, 20);

            setTimeout(function () {
                toast.classList.add('translate-y-3', 'opacity-0');
                setTimeout(function () {
                    if (toast.parentNode) toast.remove();
                }, 300);
            }, 3000);
        }

        // Jalankan pengecekan pertama setelah 4 detik, lalu setiap 5 detik
        setTimeout(function () {
            checkLiveUpdate();
            setInterval(checkLiveUpdate, 5000);
        }, 4000);
    }
});
