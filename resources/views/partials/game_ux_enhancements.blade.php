<style>
    .game-ux-prompt {
        display: flex;
        align-items: flex-start;
        gap: .7rem;
        border: 1px solid rgb(99 102 241 / .24);
        border-radius: 1rem;
        grid-column: 1 / -1;
        background: linear-gradient(110deg, rgb(238 242 255 / .95), rgb(255 255 255 / .96));
        color: #334155;
        padding: .8rem 1rem;
        font-size: .8rem;
        line-height: 1.45;
    }
    html.dark .game-ux-prompt { background: linear-gradient(110deg, rgb(30 27 75 / .72), rgb(15 23 42 / .96)); color: #e2e8f0; }
    .game-ux-prompt strong { color: #4338ca; }
    html.dark .game-ux-prompt strong { color: #c7d2fe; }
    .game-ux-prompt i { color: #4f46e5; font-size: 1rem; }
    .game-touch-arena button, #flappyFullscreenWrapper button { touch-action: manipulation; }
    @media (max-width: 640px) {
        .game-touch-arena button, #flappyFullscreenWrapper button { min-height: 40px; }
        .game-touch-arena canvas, #flappyCanvas { max-width: 100%; }
        #monoQuickActionBar { position: sticky; bottom: .35rem; z-index: 20; box-shadow: 0 8px 24px rgb(15 23 42 / .18); }
        #gameTableCard #boardViewport { min-height: 220px; }
        #gameTableCard #playerHandCard button { min-width: 44px; }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const configs = [
        { board: '#gameTableCard', status: '#turnIndicator', title: 'Cara Mulai', help: 'Pilih jumlah pemain dan tingkat AI, lalu tekan Mulai Permainan. Saat giliran Anda, pilih batu yang disorot; batu yang tidak cocok akan diredupkan.' },
        { board: '#monopoliBoardCard', status: '#monoRoundBadge', title: 'Lihat status giliran', help: 'Gunakan bilah aksi di atas papan untuk menjalankan aksi yang tersedia. Periksa saldo dan properti sebelum mengonfirmasi keputusan.' },
        { board: '#ludoBoardCard', status: '#ludoTurnBadge', title: 'Giliran Anda', help: 'Lempar dadu, lalu pilih pion yang disorot untuk menjalankan langkah. Pion yang aman atau belum bisa bergerak akan ditandai.' },
        { board: '#chessBoardCard', status: '#chessStatusBanner', title: 'Rencanakan langkah', help: 'Pilih bidak untuk melihat petak tujuan yang legal. Ketuk petak tujuan untuk menyelesaikan langkah.' },
        { board: '#flappyCanvas', status: null, title: 'Siap terbang?', help: 'Ketuk area permainan atau tombol Kepak Sayap untuk mulai dan mengendalikan burung. Tekan Jeda kapan saja.' }
    ];
    const config = configs.find(item => document.querySelector(item.board));
    if (!config) return;

    const board = document.querySelector(config.board);
    const prompt = document.createElement('section');
    prompt.className = 'game-ux-prompt';
    prompt.setAttribute('aria-live', 'polite');
    prompt.innerHTML = '<i class="bi bi-lightbulb-fill" aria-hidden="true"></i><div><strong></strong><div></div></div>';
    prompt.querySelector('strong').textContent = config.title;
    prompt.querySelector('div div').textContent = config.help;
    const anchor = board.closest('.rounded-2xl, .rounded-3xl') || board;
    anchor.parentNode.insertBefore(prompt, anchor);

    const status = config.status ? document.querySelector(config.status) : null;
    if (status) {
        status.setAttribute('aria-live', 'polite');
        status.setAttribute('aria-atomic', 'true');
        const observer = new MutationObserver(() => {
            const value = (status.innerText || status.textContent || '').trim();
            if (value && value !== '0') prompt.querySelector('strong').textContent = value.slice(0, 100);
        });
        observer.observe(status, { childList: true, subtree: true, characterData: true });
    }
});
</script>
