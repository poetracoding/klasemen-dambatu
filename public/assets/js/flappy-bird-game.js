(function () {
    'use strict';

    const PREFS_KEY = 'flappy_dambatu_prefs_v1';
    const STATS_KEY = 'flappy_dambatu_stats_v1';

    const W = 480;
    const H = 640;
    const GROUND_H = 88;
    const PLAY_H = H - GROUND_H;
    const BIRD_X = 132;
    const BIRD_R = 16;
    const PIPE_W = 66;

    const DIFFICULTIES = {
        easy: { label: 'Santai', gravity: 1220, flapVel: -385, pipeSpeed: 148, pipeGap: 170, spawnDist: 238, movingFrom: 999 },
        normal: { label: 'Normal', gravity: 1460, flapVel: -418, pipeSpeed: 185, pipeGap: 144, spawnDist: 215, movingFrom: 999 },
        hard: { label: 'Sulit', gravity: 1640, flapVel: -438, pipeSpeed: 222, pipeGap: 124, spawnDist: 196, movingFrom: 10 },
    };

    const THEMES = {
        day: {
            skyTop: '#38bdf8',
            skyBot: '#bae6fd',
            cloudColor: 'rgba(255, 255, 255, 0.85)',
            hillColor: '#86efac',
            cityColor: '#64748b',
            pipeLight: '#4ade80',
            pipeMid: '#22c55e',
            pipeDark: '#15803d',
            pipeBorder: '#14532d',
            groundTop: '#4ade80',
            groundDirt: '#d6c18a',
            groundStripe: '#c4ab6c',
            stars: false,
            celestial: 'sun',
        },
        sunset: {
            skyTop: '#7c2d12',
            skyBot: '#fb923c',
            cloudColor: 'rgba(254, 215, 170, 0.65)',
            hillColor: '#9a3412',
            cityColor: '#431407',
            pipeLight: '#34d399',
            pipeMid: '#059669',
            pipeDark: '#065f46',
            pipeBorder: '#022c22',
            groundTop: '#22c55e',
            groundDirt: '#b48a5a',
            groundStripe: '#966d41',
            stars: false,
            celestial: 'sunset',
        },
        night: {
            skyTop: '#0f172a',
            skyBot: '#1e1b4b',
            cloudColor: 'rgba(148, 163, 184, 0.25)',
            hillColor: '#1e293b',
            cityColor: '#020617',
            pipeLight: '#22d3ee',
            pipeMid: '#0891b2',
            pipeDark: '#164e63',
            pipeBorder: '#67e8f9',
            groundTop: '#10b981',
            groundDirt: '#334155',
            groundStripe: '#1e293b',
            stars: true,
            celestial: 'moon',
        },
    };

    const SKINS = {
        classic: { body: '#facc15', belly: '#fef08a', wing: '#ffffff', beak: '#f97316', stroke: '#713f12', crest: null },
        garuda: { body: '#d97706', belly: '#fde68a', wing: '#fef3c7', beak: '#eab308', stroke: '#451a03', crest: '#dc2626' },
        electric: { body: '#38bdf8', belly: '#e0f2fe', wing: '#bae6fd', beak: '#fb923c', stroke: '#0c4a6e', crest: '#facc15' },
        fire: { body: '#ef4444', belly: '#fecaca', wing: '#fb923c', beak: '#facc15', stroke: '#7f1d1d', crest: '#fde047' },
    };

    const STARS = Array.from({ length: 28 }, (_, i) => ({
        x: ((i * 73 + 19) % W),
        y: ((i * 47 + 23) % (PLAY_H - 140)),
        r: (i % 3 === 0 ? 2 : 1.2),
        phase: i * 0.7,
    }));

    let prefs = {
        difficulty: 'normal',
        theme: 'day',
        skin: 'classic',
        muted: false,
    };

    let stats = {
        best: { easy: 0, normal: 0, hard: 0 },
        recent: [],
    };

    let game = {
        phase: 'READY', // READY | PLAYING | PAUSED | GAME_OVER
        birdY: PLAY_H * 0.45,
        birdVy: 0,
        birdRot: 0,
        wingPhase: 0,
        pipes: [],
        distSinceSpawn: 0,
        groundOffset: 0,
        cloudOffset: 0,
        score: 0,
        isNewBest: false,
        gameOverAt: 0,
        shakeTimer: 0,
        particles: [],
        popups: [],
        flashAlpha: 0,
    };

    let canvas = null;
    let ctx = null;
    let rafId = null;
    let lastTs = 0;

    // --- Web Audio API Sound Effects ---
    let audioCtx = null;
    function getAudioCtx() {
        if (prefs.muted) return null;
        try {
            if (!audioCtx) {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (Ctx) audioCtx = new Ctx();
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        } catch (e) {
            return null;
        }
    }

    function playTone(freq, dur, type = 'sine', vol = 0.08, delay = 0, freqEnd = null) {
        const ac = getAudioCtx();
        if (!ac) return;
        try {
            const t = ac.currentTime + delay;
            const osc = ac.createOscillator();
            const gain = ac.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, t);
            if (freqEnd !== null) {
                osc.frequency.exponentialRampToValueAtTime(Math.max(40, freqEnd), t + dur);
            }
            gain.gain.setValueAtTime(vol, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + dur);
            osc.connect(gain);
            gain.connect(ac.destination);
            osc.start(t);
            osc.stop(t + dur);
        } catch (e) {}
    }

    function sfxFlap() {
        playTone(300, 0.09, 'sine', 0.08, 0, 520);
    }
    function sfxScore() {
        playTone(740, 0.07, 'sine', 0.08, 0);
        playTone(1046, 0.13, 'sine', 0.09, 0.06);
    }
    function sfxHit() {
        playTone(190, 0.14, 'sawtooth', 0.09, 0, 65);
        playTone(120, 0.22, 'triangle', 0.09, 0.05, 45);
    }
    function sfxMedal() {
        playTone(523, 0.08, 'sine', 0.08, 0.12);
        playTone(659, 0.08, 'sine', 0.08, 0.2);
        playTone(784, 0.1, 'sine', 0.08, 0.28);
        playTone(1046, 0.18, 'sine', 0.09, 0.36);
    }

    // --- Persistence ---
    function loadStorage() {
        try {
            const rawP = localStorage.getItem(PREFS_KEY);
            if (rawP) {
                const parsedP = JSON.parse(rawP);
                prefs = { ...prefs, ...parsedP };
            }
        } catch (e) {}
        try {
            const rawS = localStorage.getItem(STATS_KEY);
            if (rawS) {
                const parsedS = JSON.parse(rawS);
                stats = {
                    best: { easy: 0, normal: 0, hard: 0, ...(parsedS.best || {}) },
                    recent: Array.isArray(parsedS.recent) ? parsedS.recent.slice(0, 5) : [],
                };
            }
        } catch (e) {}
    }

    function savePrefs() {
        try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) {}
    }

    function saveStats() {
        try { localStorage.setItem(STATS_KEY, JSON.stringify(stats)); } catch (e) {}
    }

    function getMedalForScore(score) {
        if (score >= 70) return { name: 'Berlian', icon: '💎', color: '#38bdf8' };
        if (score >= 40) return { name: 'Emas', icon: '🥇', color: '#facc15' };
        if (score >= 20) return { name: 'Perak', icon: '🥈', color: '#cbd5e1' };
        if (score >= 10) return { name: 'Perunggu', icon: '🥉', color: '#d97706' };
        return null;
    }

    // --- Core Game Logic ---
    function resetGame(targetPhase = 'READY') {
        game.phase = targetPhase;
        game.birdY = PLAY_H * 0.44;
        game.birdVy = 0;
        game.birdRot = 0;
        game.wingPhase = 0;
        game.pipes = [];
        game.distSinceSpawn = 0;
        game.score = 0;
        game.isNewBest = false;
        game.shakeTimer = 0;
        game.particles = [];
        game.popups = [];
        game.flashAlpha = 0;
        syncHud();
        syncControlsUI();
    }

    function spawnPipePair() {
        const cfg = DIFFICULTIES[prefs.difficulty] || DIFFICULTIES.normal;
        const margin = 68;
        const minTop = margin;
        const maxTop = PLAY_H - cfg.pipeGap - margin;
        const gapTop = minTop + Math.random() * Math.max(20, maxTop - minTop);
        const moving = game.score >= cfg.movingFrom && Math.random() < 0.55;

        game.pipes.push({
            x: W + 16,
            gapTop,
            gapH: cfg.pipeGap,
            scored: false,
            moving,
            moveDir: Math.random() < 0.5 ? 1 : -1,
            moveSpeed: 38 + Math.random() * 24,
        });
    }

    function spawnFlapParticles() {
        const skin = SKINS[prefs.skin] || SKINS.classic;
        for (let i = 0; i < 5; i++) {
            game.particles.push({
                x: BIRD_X - 10 + (Math.random() * 6 - 3),
                y: game.birdY + (Math.random() * 8 - 4),
                vx: -50 - Math.random() * 70,
                vy: 30 + Math.random() * 80,
                life: 0.42 + Math.random() * 0.2,
                maxLife: 0.55,
                r: 2.5 + Math.random() * 2.5,
                color: i % 2 === 0 ? skin.wing : skin.belly,
            });
        }
    }

    function flap() {
        if (game.phase === 'PAUSED') return;
        if (game.phase === 'GAME_OVER') {
            if (performance.now() - game.gameOverAt < 450) return;
            resetGame('PLAYING');
            spawnPipePair();
        } else if (game.phase === 'READY') {
            game.phase = 'PLAYING';
            game.pipes = [];
            game.distSinceSpawn = 0;
            spawnPipePair();
            syncControlsUI();
        }

        const cfg = DIFFICULTIES[prefs.difficulty] || DIFFICULTIES.normal;
        game.birdVy = cfg.flapVel;
        game.birdRot = -0.44;
        game.wingPhase = Math.PI * 0.5;
        sfxFlap();
        spawnFlapParticles();
    }

    function togglePause() {
        if (game.phase === 'PLAYING') {
            game.phase = 'PAUSED';
            syncControlsUI();
        } else if (game.phase === 'PAUSED') {
            game.phase = 'PLAYING';
            lastTs = performance.now();
            syncControlsUI();
        }
    }

    function triggerGameOver() {
        if (game.phase === 'GAME_OVER') return;
        game.phase = 'GAME_OVER';
        game.gameOverAt = performance.now();
        game.shakeTimer = 0.26;
        game.flashAlpha = 0.65;
        sfxHit();

        const diffKey = prefs.difficulty;
        const prevBest = stats.best[diffKey] || 0;
        if (game.score > prevBest) {
            stats.best[diffKey] = game.score;
            game.isNewBest = true;
            if (game.score >= 10) sfxMedal();
        }

        const medal = getMedalForScore(game.score);
        stats.recent.unshift({
            score: game.score,
            difficulty: (DIFFICULTIES[diffKey] || DIFFICULTIES.normal).label,
            medal: medal ? `${medal.icon} ${medal.name}` : '-',
        });
        stats.recent = stats.recent.slice(0, 5);
        saveStats();
        renderStatsSidebar();
        syncHud();
        syncControlsUI();
    }

    function updatePhysics(dt, now) {
        const cfg = DIFFICULTIES[prefs.difficulty] || DIFFICULTIES.normal;
        game.cloudOffset = (game.cloudOffset + 22 * dt) % W;

        if (game.flashAlpha > 0) {
            game.flashAlpha = Math.max(0, game.flashAlpha - 2.6 * dt);
        }
        if (game.shakeTimer > 0) {
            game.shakeTimer = Math.max(0, game.shakeTimer - dt);
        }

        // Particles & popups tetap berjalan halus saat Game Over
        game.particles.forEach(pt => {
            pt.x += pt.vx * dt;
            pt.y += pt.vy * dt;
            pt.life -= dt;
        });
        game.particles = game.particles.filter(pt => pt.life > 0);

        game.popups.forEach(pp => {
            pp.y -= 38 * dt;
            pp.life -= dt;
        });
        game.popups = game.popups.filter(pp => pp.life > 0);

        if (game.phase === 'READY') {
            game.groundOffset = (game.groundOffset + cfg.pipeSpeed * 0.65 * dt) % 24;
            game.birdY = PLAY_H * 0.44 + Math.sin(now * 0.005) * 9;
            game.wingPhase += dt * 11;
            game.birdRot = 0;
            return;
        }

        if (game.phase === 'PAUSED') return;

        if (game.phase === 'GAME_OVER') {
            if (game.birdY + BIRD_R < PLAY_H) {
                game.birdVy += cfg.gravity * dt;
                game.birdY = Math.min(PLAY_H - BIRD_R, game.birdY + game.birdVy * dt);
                game.birdRot = Math.min(Math.PI * 0.48, game.birdRot + 4.5 * dt);
            }
            return;
        }

        // PLAYING
        const speedMult = 1 + Math.min(0.22, game.score * 0.004);
        const speed = cfg.pipeSpeed * speedMult;

        game.groundOffset = (game.groundOffset + speed * dt) % 24;
        game.wingPhase += dt * (game.birdVy < 0 ? 18 : 9);

        game.birdVy += cfg.gravity * dt;
        game.birdY += game.birdVy * dt;

        // Batas langit
        if (game.birdY - BIRD_R < 4) {
            game.birdY = BIRD_R + 4;
            game.birdVy = 0;
        }

        // Rotasi realistis burung
        if (game.birdVy < 0) {
            game.birdRot = Math.max(-0.46, game.birdRot - 6 * dt);
        } else {
            game.birdRot = Math.min(Math.PI * 0.44, game.birdRot + 2.7 * dt);
        }

        // Spawn pipa berikutnya
        game.distSinceSpawn += speed * dt;
        if (game.distSinceSpawn >= cfg.spawnDist) {
            game.distSinceSpawn = 0;
            spawnPipePair();
        }

        // Update pipa & cek skor/tabrakan
        const hitR = BIRD_R - 3; // hitbox toleran agar adil
        for (let i = game.pipes.length - 1; i >= 0; i--) {
            const p = game.pipes[i];
            p.x -= speed * dt;

            if (p.moving) {
                p.gapTop += p.moveDir * p.moveSpeed * dt;
                if (p.gapTop < 56) {
                    p.gapTop = 56;
                    p.moveDir = 1;
                } else if (p.gapTop + p.gapH > PLAY_H - 56) {
                    p.gapTop = PLAY_H - 56 - p.gapH;
                    p.moveDir = -1;
                }
            }

            if (!p.scored && p.x + PIPE_W / 2 < BIRD_X) {
                p.scored = true;
                game.score += 1;
                sfxScore();
                game.popups.push({ x: BIRD_X + 6, y: game.birdY - 24, text: '+1', life: 0.7 });
                syncHud();
            }

            // Tabrakan lingkaran burung dengan dua pipa persegi
            if (BIRD_X + hitR > p.x && BIRD_X - hitR < p.x + PIPE_W) {
                if (game.birdY - hitR < p.gapTop || game.birdY + hitR > p.gapTop + p.gapH) {
                    triggerGameOver();
                    return;
                }
            }

            if (p.x + PIPE_W < -20) {
                game.pipes.splice(i, 1);
            }
        }

        // Tabrakan tanah
        if (game.birdY + BIRD_R >= PLAY_H) {
            game.birdY = PLAY_H - BIRD_R;
            triggerGameOver();
        }
    }

    // --- Canvas Renderer ---
    function drawRoundRect(x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    function drawBackground(now) {
        const theme = THEMES[prefs.theme] || THEMES.day;
        const grad = ctx.createLinearGradient(0, 0, 0, PLAY_H);
        grad.addColorStop(0, theme.skyTop);
        grad.addColorStop(1, theme.skyBot);
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, PLAY_H);

        // Bintang (malam)
        if (theme.stars) {
            STARS.forEach(s => {
                const alpha = 0.45 + 0.55 * Math.sin(now * 0.003 + s.phase);
                ctx.fillStyle = `rgba(248, 250, 252, ${alpha.toFixed(2)})`;
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        // Matahari / Bulan
        if (theme.celestial === 'sun') {
            ctx.fillStyle = 'rgba(254, 240, 138, 0.9)';
            ctx.beginPath();
            ctx.arc(388, 92, 34, 0, Math.PI * 2);
            ctx.fill();
        } else if (theme.celestial === 'sunset') {
            ctx.fillStyle = 'rgba(253, 186, 116, 0.85)';
            ctx.beginPath();
            ctx.arc(360, 210, 44, 0, Math.PI * 2);
            ctx.fill();
        } else {
            ctx.fillStyle = '#fef9c3';
            ctx.beginPath();
            ctx.arc(392, 86, 26, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillStyle = theme.skyTop;
            ctx.beginPath();
            ctx.arc(402, 80, 22, 0, Math.PI * 2);
            ctx.fill();
        }

        // Awan parallax
        ctx.fillStyle = theme.cloudColor;
        for (let i = 0; i < 4; i++) {
            const cx = ((i * 155 - game.cloudOffset + W * 2) % (W + 120)) - 60;
            const cy = 72 + (i % 2) * 52;
            ctx.beginPath();
            ctx.arc(cx, cy, 22, 0, Math.PI * 2);
            ctx.arc(cx + 20, cy - 8, 26, 0, Math.PI * 2);
            ctx.arc(cx + 42, cy, 20, 0, Math.PI * 2);
            ctx.fill();
        }

        // Siluet kota jauh
        ctx.fillStyle = theme.cityColor;
        ctx.globalAlpha = 0.25;
        for (let x = 0; x < W; x += 34) {
            const bh = 38 + ((x * 17) % 52);
            ctx.fillRect(x, PLAY_H - bh, 28, bh);
        }
        ctx.globalAlpha = 1;

        // Bukit halus di horizon
        ctx.fillStyle = theme.hillColor;
        ctx.globalAlpha = 0.45;
        for (let x = -40; x < W + 80; x += 96) {
            ctx.beginPath();
            ctx.arc(x, PLAY_H + 12, 62, Math.PI, 0);
            ctx.fill();
        }
        ctx.globalAlpha = 1;
    }

    function drawPipeRect(x, y, w, h, capBottom) {
        const theme = THEMES[prefs.theme] || THEMES.day;
        const grad = ctx.createLinearGradient(x, 0, x + w, 0);
        grad.addColorStop(0, theme.pipeDark);
        grad.addColorStop(0.28, theme.pipeLight);
        grad.addColorStop(0.65, theme.pipeMid);
        grad.addColorStop(1, theme.pipeDark);

        ctx.fillStyle = grad;
        ctx.strokeStyle = theme.pipeBorder;
        ctx.lineWidth = 2.5;
        ctx.fillRect(x, y, w, h);
        ctx.strokeRect(x, y, w, h);

        // Bibir pipa menonjol (cap)
        const capH = 26;
        const capY = capBottom ? (y + h - capH) : y;
        ctx.fillStyle = grad;
        ctx.fillRect(x - 5, capY, w + 10, capH);
        ctx.strokeRect(x - 5, capY, w + 10, capH);
    }

    function drawPipes() {
        game.pipes.forEach(p => {
            if (p.gapTop > 0) {
                drawPipeRect(p.x, -4, PIPE_W, p.gapTop + 4, true);
            }
            const botY = p.gapTop + p.gapH;
            const botH = PLAY_H - botY;
            if (botH > 0) {
                drawPipeRect(p.x, botY, PIPE_W, botH + 2, false);
            }
        });
    }

    function drawGround() {
        const theme = THEMES[prefs.theme] || THEMES.day;
        ctx.fillStyle = theme.groundDirt;
        ctx.fillRect(0, PLAY_H, W, GROUND_H);

        // Rumput atas tanah
        ctx.fillStyle = theme.groundTop;
        ctx.fillRect(0, PLAY_H, W, 16);

        // Garis strip bergerak tanah
        ctx.fillStyle = theme.groundStripe;
        for (let x = -game.groundOffset; x < W + 24; x += 24) {
            ctx.beginPath();
            ctx.moveTo(x, PLAY_H);
            ctx.lineTo(x + 12, PLAY_H);
            ctx.lineTo(x + 4, PLAY_H + 16);
            ctx.lineTo(x - 8, PLAY_H + 16);
            ctx.closePath();
            ctx.fill();
        }

        ctx.strokeStyle = 'rgba(15, 23, 42, 0.35)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(0, PLAY_H);
        ctx.lineTo(W, PLAY_H);
        ctx.stroke();
    }

    function drawBird() {
        const skin = SKINS[prefs.skin] || SKINS.classic;
        ctx.save();
        ctx.translate(BIRD_X, game.birdY);
        ctx.rotate(game.birdRot);

        // Jambul (untuk varian Garuda / Elektrik / Api)
        if (skin.crest) {
            ctx.fillStyle = skin.crest;
            ctx.beginPath();
            ctx.moveTo(-4, -BIRD_R + 2);
            ctx.lineTo(-12, -BIRD_R - 7);
            ctx.lineTo(2, -BIRD_R - 1);
            ctx.closePath();
            ctx.fill();
        }

        // Badan
        ctx.fillStyle = skin.body;
        ctx.strokeStyle = skin.stroke;
        ctx.lineWidth = 2.5;
        ctx.beginPath();
        ctx.ellipse(0, 0, BIRD_R + 3, BIRD_R, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();

        // Perut
        ctx.fillStyle = skin.belly;
        ctx.beginPath();
        ctx.ellipse(2, 5, BIRD_R - 4, BIRD_R - 8, 0, 0, Math.PI);
        ctx.fill();

        // Mata putih + pupil
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.arc(8, -5, 6.5, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();

        ctx.fillStyle = '#0f172a';
        ctx.beginPath();
        ctx.arc(10, -5, 2.8, 0, Math.PI * 2);
        ctx.fill();

        // Paruh oranye
        ctx.fillStyle = skin.beak;
        ctx.beginPath();
        ctx.moveTo(14, -1);
        ctx.lineTo(26, 3);
        ctx.lineTo(14, 7);
        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // Sayap beranimasi kepakan
        const wingOffsetY = Math.sin(game.wingPhase) * 5;
        ctx.fillStyle = skin.wing;
        ctx.beginPath();
        ctx.ellipse(-5, 2 + wingOffsetY * 0.5, 9, 6, wingOffsetY * 0.06, 0, Math.PI * 2);
        ctx.fill();
        ctx.stroke();

        ctx.restore();
    }

    function drawParticlesAndPopups() {
        game.particles.forEach(pt => {
            ctx.globalAlpha = Math.max(0, pt.life / pt.maxLife);
            ctx.fillStyle = pt.color;
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, pt.r, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.globalAlpha = 1;

        game.popups.forEach(pp => {
            ctx.globalAlpha = Math.min(1, pp.life * 2);
            ctx.font = '900 20px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth = 4;
            ctx.strokeText(pp.text, pp.x, pp.y);
            ctx.fillStyle = '#fde047';
            ctx.fillText(pp.text, pp.x, pp.y);
        });
        ctx.globalAlpha = 1;
    }

    function drawScoreAndOverlays() {
        // Skor besar di tengah atas saat bermain
        if (game.phase === 'PLAYING' || game.phase === 'PAUSED') {
            ctx.font = '900 44px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.lineWidth = 6;
            ctx.strokeStyle = 'rgba(15, 23, 42, 0.85)';
            ctx.strokeText(String(game.score), W / 2, 72);
            ctx.fillStyle = '#ffffff';
            ctx.fillText(String(game.score), W / 2, 72);
        }

        if (game.phase === 'READY') {
            drawRoundRect(W / 2 - 155, 110, 310, 108, 18);
            ctx.fillStyle = 'rgba(15, 23, 42, 0.72)';
            ctx.fill();
            ctx.strokeStyle = 'rgba(250, 204, 21, 0.75)';
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.textAlign = 'center';
            ctx.fillStyle = '#fde047';
            ctx.font = '900 24px Inter, sans-serif';
            ctx.fillText('SIAP TERBANG!', W / 2, 152);
            ctx.fillStyle = '#f8fafc';
            ctx.font = '600 13px Inter, sans-serif';
            ctx.fillText('Klik Kanvas / Tap / Tekan Spasi untuk mulai', W / 2, 184);
        } else if (game.phase === 'PAUSED') {
            ctx.fillStyle = 'rgba(15, 23, 42, 0.52)';
            ctx.fillRect(0, 0, W, PLAY_H);

            drawRoundRect(W / 2 - 140, PLAY_H / 2 - 52, 280, 104, 18);
            ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
            ctx.fill();
            ctx.strokeStyle = '#fbbf24';
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.textAlign = 'center';
            ctx.fillStyle = '#fde047';
            ctx.font = '900 22px Inter, sans-serif';
            ctx.fillText('DIJEDA', W / 2, PLAY_H / 2 - 6);
            ctx.fillStyle = '#e2e8f0';
            ctx.font = '600 13px Inter, sans-serif';
            ctx.fillText('Tekan P atau klik Lanjut untuk bermain', W / 2, PLAY_H / 2 + 24);
        } else if (game.phase === 'GAME_OVER') {
            ctx.fillStyle = 'rgba(15, 23, 42, 0.45)';
            ctx.fillRect(0, 0, W, PLAY_H);

            const bx = W / 2 - 155;
            const by = 140;
            drawRoundRect(bx, by, 310, 235, 20);
            ctx.fillStyle = 'rgba(15, 23, 42, 0.92)';
            ctx.fill();
            ctx.strokeStyle = '#fbbf24';
            ctx.lineWidth = 2.5;
            ctx.stroke();

            ctx.textAlign = 'center';
            ctx.fillStyle = '#f87171';
            ctx.font = '900 26px Inter, sans-serif';
            ctx.fillText('GAME OVER!', W / 2, by + 44);

            const bestNow = stats.best[prefs.difficulty] || 0;
            const medal = getMedalForScore(game.score);

            // Kotak Medali
            ctx.fillStyle = 'rgba(30, 41, 59, 0.95)';
            drawRoundRect(bx + 22, by + 68, 112, 92, 12);
            ctx.fill();
            ctx.font = '700 11px Inter, sans-serif';
            ctx.fillStyle = '#94a3b8';
            ctx.fillText('MEDALI', bx + 78, by + 90);
            ctx.font = '32px Inter, sans-serif';
            ctx.fillText(medal ? medal.icon : '🪽', bx + 78, by + 130);
            ctx.font = '700 11px Inter, sans-serif';
            ctx.fillStyle = medal ? medal.color : '#64748b';
            ctx.fillText(medal ? medal.name : 'Belum ada', bx + 78, by + 150);

            // Skor & Rekor
            ctx.textAlign = 'right';
            ctx.fillStyle = '#94a3b8';
            ctx.font = '700 11px Inter, sans-serif';
            ctx.fillText('SKOR', bx + 286, by + 88);
            ctx.fillStyle = '#ffffff';
            ctx.font = '900 28px Inter, sans-serif';
            ctx.fillText(String(game.score), bx + 286, by + 118);

            ctx.fillStyle = game.isNewBest ? '#fde047' : '#94a3b8';
            ctx.font = '700 11px Inter, sans-serif';
            ctx.fillText(game.isNewBest ? 'REKOR BARU!' : 'TERBAIK', bx + 286, by + 140);
            ctx.fillStyle = '#4ade80';
            ctx.font = '900 24px Inter, sans-serif';
            ctx.fillText(String(bestNow), bx + 286, by + 166);

            ctx.textAlign = 'center';
            ctx.fillStyle = '#fde047';
            ctx.font = '700 13px Inter, sans-serif';
            ctx.fillText('Klik Kanvas / Tekan Spasi untuk Main Lagi', W / 2, by + 208);
        }

        // Flash putih saat tabrakan
        if (game.flashAlpha > 0) {
            ctx.fillStyle = `rgba(255, 255, 255, ${game.flashAlpha.toFixed(2)})`;
            ctx.fillRect(0, 0, W, H);
        }
    }

    function render(now) {
        if (!ctx) return;
        ctx.save();
        if (game.shakeTimer > 0) {
            const mag = game.shakeTimer * 28;
            ctx.translate((Math.random() - 0.5) * mag, (Math.random() - 0.5) * mag);
        }
        drawBackground(now);
        drawPipes();
        drawGround();
        drawBird();
        drawParticlesAndPopups();
        drawScoreAndOverlays();
        ctx.restore();
    }

    function loop(now) {
        if (!lastTs) lastTs = now;
        const dt = Math.min(0.035, Math.max(0, (now - lastTs) / 1000));
        lastTs = now;

        updatePhysics(dt, now);
        render(now);
        rafId = requestAnimationFrame(loop);
    }

    // --- DOM & UI Sync ---
    function syncHud() {
        const liveEl = document.getElementById('flappyLiveScore');
        const bestEl = document.getElementById('flappyBestBadge');
        const diffEl = document.getElementById('flappyDifficultyBadge');

        const bestNow = stats.best[prefs.difficulty] || 0;
        if (liveEl) liveEl.textContent = String(game.score);
        if (bestEl) bestEl.textContent = 'Rekor: ' + bestNow;
        if (diffEl) diffEl.textContent = 'Level: ' + (DIFFICULTIES[prefs.difficulty]?.label || 'Normal');
    }

    function renderStatsSidebar() {
        const eEl = document.getElementById('flappyBestEasy');
        const nEl = document.getElementById('flappyBestNormal');
        const hEl = document.getElementById('flappyBestHard');
        const medalEl = document.getElementById('flappyBestMedal');
        const recentEl = document.getElementById('flappyRecentRuns');

        if (eEl) eEl.textContent = String(stats.best.easy || 0);
        if (nEl) nEl.textContent = String(stats.best.normal || 0);
        if (hEl) hEl.textContent = String(stats.best.hard || 0);

        const maxScore = Math.max(stats.best.easy || 0, stats.best.normal || 0, stats.best.hard || 0);
        const topMedal = getMedalForScore(maxScore);
        if (medalEl) {
            medalEl.innerHTML = `<span>Medali Tertinggi:</span><span class="font-bold text-slate-900 dark:text-white">${topMedal ? `${topMedal.icon} ${topMedal.name} (Skor ${maxScore})` : 'Belum ada (Min. skor 10)'}</span>`;
        }

        if (recentEl) {
            if (!stats.recent || stats.recent.length === 0) {
                recentEl.innerHTML = '<div class="text-center text-slate-400 py-3">Belum ada riwayat permainan.</div>';
            } else {
                recentEl.innerHTML = stats.recent.map((r, i) => `
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/60 px-3 py-1.5">
                        <div class="flex items-center gap-2 font-semibold">
                            <span class="text-slate-400">#${i + 1}</span>
                            <span>${r.difficulty}</span>
                        </div>
                        <div class="flex items-center gap-2 font-mono font-bold">
                            <span>${r.medal}</span>
                            <span class="text-indigo-600 dark:text-indigo-400">${r.score}</span>
                        </div>
                    </div>
                `).join('');
            }
        }
    }

    function syncControlsUI() {
        document.querySelectorAll('.flappy-diff-btn').forEach(btn => {
            const active = btn.getAttribute('data-flappy-diff') === prefs.difficulty;
            btn.className = 'flappy-diff-btn px-2 py-1.5 rounded-lg text-xs font-semibold border cursor-pointer transition-colors ' +
                (active
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700');
        });

        const themeSel = document.getElementById('flappyThemeSelect');
        if (themeSel) themeSel.value = prefs.theme;
        const skinSel = document.getElementById('flappySkinSelect');
        if (skinSel) skinSel.value = prefs.skin;

        const muteIcon = document.getElementById('flappyMuteIcon');
        const muteLabel = document.getElementById('flappyMuteLabel');
        if (muteIcon) muteIcon.className = prefs.muted ? 'bi bi-volume-mute-fill text-rose-500' : 'bi bi-volume-up-fill text-emerald-500';
        if (muteLabel) muteLabel.textContent = prefs.muted ? 'Suara Mati' : 'Suara Aktif';

        const pauseBtn = document.getElementById('btnFlappyPause');
        const pauseIcon = document.getElementById('flappyPauseIcon');
        const pauseLabel = document.getElementById('flappyPauseLabel');
        if (pauseBtn) {
            pauseBtn.disabled = game.phase !== 'PLAYING' && game.phase !== 'PAUSED';
        }
        if (pauseIcon && pauseLabel) {
            const isPaused = game.phase === 'PAUSED';
            pauseIcon.className = isPaused ? 'bi bi-play-fill' : 'bi bi-pause-fill';
            pauseLabel.textContent = isPaused ? 'Lanjut' : 'Jeda';
        }
    }

    function toggleFullscreen() {
        const wrapper = document.getElementById('flappyFullscreenWrapper');
        if (!wrapper) return;
        const isFs = Boolean(document.fullscreenElement || wrapper.classList.contains('is-pseudo-fullscreen'));
        if (isFs) {
            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
            wrapper.classList.remove('is-pseudo-fullscreen');
            document.body.style.overflow = '';
            return;
        }
        if (wrapper.requestFullscreen) {
            wrapper.requestFullscreen().catch(() => {
                wrapper.classList.add('is-pseudo-fullscreen');
                document.body.style.overflow = 'hidden';
            });
        } else {
            wrapper.classList.add('is-pseudo-fullscreen');
            document.body.style.overflow = 'hidden';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        canvas = document.getElementById('flappyCanvas');
        if (!canvas) return;
        ctx = canvas.getContext('2d');

        loadStorage();
        resetGame('READY');
        renderStatsSidebar();

        // Pointer control untuk respons instan tanpa delay klik mobile
        canvas.addEventListener('pointerdown', (e) => {
            if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;
            e.preventDefault();
            flap();
        });

        document.getElementById('btnFlappyTapFlap')?.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            e.currentTarget.blur();
            flap();
        });

        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('flappyRulesModal');
            if (modal && !modal.classList.contains('hidden')) return;

            const activeTag = document.activeElement ? document.activeElement.tagName.toUpperCase() : '';
            if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') return;

            if (e.code === 'Space' || e.code === 'ArrowUp' || e.code === 'KeyW') {
                e.preventDefault();
                if (activeTag === 'BUTTON' && document.activeElement) document.activeElement.blur();
                if (!e.repeat) flap();
            } else if (e.code === 'KeyP') {
                e.preventDefault();
                togglePause();
            }
        });

        document.querySelectorAll('.flappy-diff-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.difficulty = btn.getAttribute('data-flappy-diff');
                savePrefs();
                resetGame('READY');
                btn.blur();
            });
        });

        document.getElementById('flappyThemeSelect')?.addEventListener('change', (e) => {
            prefs.theme = e.target.value;
            savePrefs();
            e.target.blur();
        });

        document.getElementById('flappySkinSelect')?.addEventListener('change', (e) => {
            prefs.skin = e.target.value;
            savePrefs();
            e.target.blur();
        });

        document.getElementById('btnFlappyStart')?.addEventListener('click', (e) => {
            resetGame('READY');
            e.currentTarget.blur();
        });
        document.getElementById('btnFlappyPause')?.addEventListener('click', (e) => {
            togglePause();
            e.currentTarget.blur();
        });

        document.getElementById('btnFlappyMute')?.addEventListener('click', (e) => {
            prefs.muted = !prefs.muted;
            savePrefs();
            syncControlsUI();
            e.currentTarget.blur();
        });

        document.getElementById('btnFlappyFullscreen')?.addEventListener('click', (e) => {
            toggleFullscreen();
            e.currentTarget.blur();
        });

        document.getElementById('btnFlappyResetStats')?.addEventListener('click', () => {
            stats = { best: { easy: 0, normal: 0, hard: 0 }, recent: [] };
            saveStats();
            renderStatsSidebar();
            syncHud();
        });

        const rulesModal = document.getElementById('flappyRulesModal');
        document.getElementById('btnFlappyOpenRules')?.addEventListener('click', () => {
            if (game.phase === 'PLAYING') togglePause();
            if (rulesModal) {
                rulesModal.classList.remove('hidden');
                rulesModal.classList.add('flex');
            }
        });
        document.getElementById('btnCloseFlappyRules')?.addEventListener('click', () => {
            if (rulesModal) {
                rulesModal.classList.add('hidden');
                rulesModal.classList.remove('flex');
            }
        });

        rafId = requestAnimationFrame(loop);
    });
})();
