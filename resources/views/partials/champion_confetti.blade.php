<canvas id="championConfettiCanvas" class="fixed inset-0 pointer-events-none z-50 w-full h-full"></canvas>
<script>
(function () {
    var canvas = document.getElementById('championConfettiCanvas');
    if (!canvas || window.__championConfettiStarted) return;
    window.__championConfettiStarted = true;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    var colors = ['#facc15', '#4f46e5', '#10b981', '#f43f5e', '#38bdf8', '#fb923c'];
    var pieces = [];
    for (var i = 0; i < 85; i++) {
        pieces.push({
            x: Math.random() * canvas.width,
            y: -20 - Math.random() * canvas.height * 0.6,
            w: 6 + Math.random() * 6,
            h: 8 + Math.random() * 6,
            vx: (Math.random() - 0.5) * 2.4,
            vy: 2.2 + Math.random() * 3.4,
            rot: Math.random() * Math.PI * 2,
            vRot: (Math.random() - 0.5) * 0.14,
            color: colors[i % colors.length],
        });
    }

    var start = performance.now();
    var duration = 3800;

    function tick(now) {
        var elapsed = now - start;
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        var alpha = elapsed > (duration - 900) ? Math.max(0, (duration - elapsed) / 900) : 1;
        ctx.globalAlpha = alpha;

        for (var i = 0; i < pieces.length; i++) {
            var p = pieces[i];
            p.x += p.vx;
            p.y += p.vy;
            p.rot += p.vRot;
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        }

        if (elapsed < duration) {
            requestAnimationFrame(tick);
        } else {
            canvas.remove();
        }
    }

    requestAnimationFrame(tick);
})();
</script>
