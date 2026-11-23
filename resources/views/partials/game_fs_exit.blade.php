@props(['btnId' => ''])
<button type="button"
        class="game-fs-exit-btn items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-slate-900/90 hover:bg-slate-800 text-white border border-white/20 shadow-lg backdrop-blur-sm transition-colors cursor-pointer"
        data-fs-exit-target="{{ $btnId }}"
        aria-label="Keluar dari Layar Penuh"
        onclick="(function(el){ var id = el.getAttribute('data-fs-exit-target'); var btn = id ? document.getElementById(id) : null; if (btn) { btn.click(); return; } if (document.fullscreenElement && document.exitFullscreen) { document.exitFullscreen().catch(function(){}); } var wrap = el.closest('.is-pseudo-fullscreen'); if (wrap) wrap.classList.remove('is-pseudo-fullscreen'); })(this)">
    <i class="bi bi-fullscreen-exit text-amber-400"></i>
    <span>Keluar Layar Penuh</span>
</button>
