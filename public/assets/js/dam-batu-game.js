(function () {
    'use strict';

    const STATS_KEY = 'dam_batu_stats_v1';
    const MUTE_KEY = 'dam_batu_muted_v1';
    const CAMERA_KEY = 'dam_batu_camera_v1';
    const FELT_KEY = 'dam_batu_felt_v1';

    const PIP_POSITIONS = {
        0: [],
        1: [4],
        2: [0, 8],
        3: [0, 4, 8],
        4: [0, 2, 6, 8],
        5: [0, 2, 4, 6, 8],
        6: [0, 2, 3, 5, 6, 8],
    };

    const PIP_COLORS = {
        1: '#dc2626',
        2: '#2563eb',
        3: '#059669',
        4: '#d97706',
        5: '#7c3aed',
        6: '#0f172a',
    };

    const DIFFICULTY_LABELS = {
        easy: 'Santai',
        normal: 'Normal',
        hard: 'Sulit (Pro)',
    };

    const CAMERA_ORDER = ['3d', 'cinematic', '2d'];
    const CAMERA_LABELS = {
        '3d': '3D Standar',
        cinematic: '3D Sinematik',
        '2d': '2D Atas',
    };

    const AI_NAMES = ['AI Budi', 'AI Agus', 'AI Rina'];

    let state = {
        aiCount: 3,              // 2 (3 orang, 8 kartu) atau 3 (4 orang, 6 kartu)
        difficulty: 'normal',    // easy | normal | hard
        camera: '3d',            // 3d | cinematic | 2d
        felt: 'emerald',         // emerald | royal | classic
        boardZoom: 1,            // 0.75 .. 1.25
        autoFitScale: 1,
        userZoomOverride: false,
        historyOpen: false,
        players: [],             // [{ id, name, isHuman, hand: [], passed: false }]
        hiddenTiles: [],         // 4 kartu sisa tertutup
        board: [],               // [{ id, left, right, isDouble, ownerId }]
        anchorTileId: null,
        leftEnd: null,
        rightEnd: null,
        currentTurn: 0,
        openingTileId: null,
        consecutivePasses: 0,
        passedNumbers: {},       // { playerIdx: Set<number> }
        lastActionByPlayer: {},  // { playerIdx: { text, kind } }
        selectedTileId: null,
        lastPlacedId: null,
        lastPlacedSide: 'center',
        animateLatest: false,
        justDealt: false,
        hasStarted: false,
        isShuffling: false,
        isRunning: false,
        isFinished: false,
        aiTimer: null,
        shuffleTimer: null,
        logs: [],
        muted: false,
    };

    // --- Audio (Web Audio API: Ceramic Clack + Noise Burst) ---
    let audioCtx = null;
    let cachedNoiseBuffer = null;

    function getAudioCtx() {
        if (state.muted) return null;
        if (!audioCtx) {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (Ctx) audioCtx = new Ctx();
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().catch(() => {});
        }
        return audioCtx;
    }

    function getNoiseBuffer(ctx) {
        if (cachedNoiseBuffer && cachedNoiseBuffer.sampleRate === ctx.sampleRate) {
            return cachedNoiseBuffer;
        }
        const length = Math.floor(ctx.sampleRate * 0.08);
        const buffer = ctx.createBuffer(1, length, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < length; i++) {
            data[i] = (Math.random() * 2 - 1) * Math.exp(-i / (length * 0.28));
        }
        cachedNoiseBuffer = buffer;
        return buffer;
    }

    function playTone(freq, duration, type = 'triangle', volume = 0.12, delay = 0) {
        const ctx = getAudioCtx();
        if (!ctx) return;
        try {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, ctx.currentTime + delay);
            gain.gain.setValueAtTime(volume, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + duration);
        } catch (e) {}
    }

    function playCeramicClick(filterFreq = 1600, gainPeak = 0.22, delay = 0) {
        const ctx = getAudioCtx();
        if (!ctx) return;
        try {
            const src = ctx.createBufferSource();
            src.buffer = getNoiseBuffer(ctx);
            const filter = ctx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.frequency.setValueAtTime(filterFreq, ctx.currentTime + delay);
            filter.Q.setValueAtTime(2.4, ctx.currentTime + delay);
            const gain = ctx.createGain();
            gain.gain.setValueAtTime(gainPeak, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.055);
            src.connect(filter);
            filter.connect(gain);
            gain.connect(ctx.destination);
            src.start(ctx.currentTime + delay);
            src.stop(ctx.currentTime + delay + 0.065);
        } catch (e) {}
    }

    function playSoundShuffle() {
        for (let i = 0; i < 6; i++) {
            playCeramicClick(1250 + (i % 3) * 320, 0.16, i * 0.13);
            playTone(170 + (i % 3) * 45, 0.04, 'triangle', 0.05, i * 0.13);
        }
    }

    function playSoundPlace() {
        playCeramicClick(1850, 0.25, 0);
        playTone(210, 0.055, 'triangle', 0.15, 0);
        playTone(540, 0.035, 'sine', 0.07, 0.012);
    }

    function playSoundSelect() {
        playCeramicClick(2300, 0.12, 0);
        playTone(420, 0.03, 'sine', 0.06, 0);
    }

    function playSoundPass() {
        playTone(170, 0.11, 'sine', 0.09, 0);
    }

    function playSoundWin() {
        playTone(523.25, 0.12, 'triangle', 0.12, 0);
        playTone(659.25, 0.12, 'triangle', 0.12, 0.11);
        playTone(783.99, 0.22, 'triangle', 0.14, 0.22);
    }

    function playSoundLose() {
        playTone(330, 0.14, 'sine', 0.1, 0);
        playTone(262, 0.22, 'sine', 0.1, 0.14);
    }

    // --- 3D Table Preferences & Parallax ---
    function getEffectiveZoom() {
        if (state.userZoomOverride) {
            return state.boardZoom;
        }
        return state.autoFitScale || 1;
    }

    function syncTablePreferences() {
        const card = document.getElementById('gameTableCard');
        const surface = document.getElementById('table3dSurface');
        const camLabel = document.getElementById('cameraLabel');
        const zoomReset = document.getElementById('btnZoomReset');
        const zoomOutBtn = document.getElementById('btnZoomOut');
        const zoomInBtn = document.getElementById('btnZoomIn');
        const viewport = document.getElementById('boardViewport');

        if (card) {
            card.setAttribute('data-camera', state.camera);
            card.setAttribute('data-felt', state.felt);
        }

        const effectiveZoom = getEffectiveZoom();
        if (viewport) {
            viewport.dataset.userZoomed = state.userZoomOverride ? 'true' : 'false';
        }
        if (surface) {
            surface.style.setProperty('--board-zoom', String(effectiveZoom));
            if (state.camera === '2d') {
                surface.style.setProperty('--tilt-x', '0deg');
                surface.style.setProperty('--tilt-y', '0deg');
            }
        }

        if (camLabel) {
            camLabel.textContent = CAMERA_LABELS[state.camera] || '3D Standar';
        }

        if (zoomReset) {
            zoomReset.textContent = Math.round(effectiveZoom * 100) + '%';
        }
        if (zoomOutBtn) zoomOutBtn.disabled = state.boardZoom <= 0.75;
        if (zoomInBtn) zoomInBtn.disabled = state.boardZoom >= 1.25;

        document.querySelectorAll('[data-felt-theme]').forEach(btn => {
            const val = btn.getAttribute('data-felt-theme');
            const active = val === state.felt;
            btn.className = 'inline-flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer ' +
                (active
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700');
        });
    }

    function bind3dParallax() {
        const viewport = document.getElementById('boardViewport');
        const surface = document.getElementById('table3dSurface');
        if (!viewport || !surface) return;

        const finePointer = window.matchMedia && window.matchMedia('(pointer: fine)').matches;
        if (!finePointer) return;

        viewport.addEventListener('mousemove', (e) => {
            if (state.camera === '2d') return;
            const rect = viewport.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            const nx = ((e.clientX - rect.left) / rect.width) - 0.5;
            const ny = ((e.clientY - rect.top) / rect.height) - 0.5;
            surface.style.setProperty('--tilt-x', (nx * 4.5).toFixed(2) + 'deg');
            surface.style.setProperty('--tilt-y', (-ny * 3.5).toFixed(2) + 'deg');
        });

        viewport.addEventListener('mouseleave', () => {
            surface.style.setProperty('--tilt-x', '0deg');
            surface.style.setProperty('--tilt-y', '0deg');
        });
    }

    // --- Fullscreen ---
    function isFullscreenActive() {
        const wrapper = document.getElementById('gameFullscreenWrapper');
        return Boolean(document.fullscreenElement) || (wrapper && wrapper.classList.contains('is-pseudo-fullscreen'));
    }

    function syncFullscreenUI() {
        const btn = document.getElementById('btnToggleFullscreen');
        if (btn) {
            if (isFullscreenActive()) {
                btn.innerHTML = '<i class="bi bi-fullscreen-exit"></i> <span>Kecilkan Meja</span>';
                btn.title = 'Keluar Mode Layar Penuh';
            } else {
                btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i> <span>Perluas Meja</span>';
                btn.title = 'Perluas Meja (Layar Penuh)';
            }
        }
        if (state.board.length > 0) {
            requestAnimationFrame(() => renderBoard());
        }
    }

    function confirmMidGameAction(title, text, confirmText, onConfirm) {
        if (!state.isRunning || state.isFinished) {
            onConfirm();
            return;
        }

        const hadAiTimer = Boolean(state.aiTimer);
        if (state.aiTimer) {
            clearTimeout(state.aiTimer);
            state.aiTimer = null;
        }

        const resumeIfCanceled = () => {
            if (hadAiTimer && state.isRunning && !state.isFinished) {
                scheduleNextTurnIfAi();
            }
        };

        if (window.Swal) {
            const wrapper = document.getElementById('gameFullscreenWrapper');
            window.Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                target: isFullscreenActive() && wrapper ? wrapper : 'body',
                customClass: {
                    popup: 'swal2-modern-popup',
                    confirmButton: 'swal2-modern-confirm',
                    cancelButton: 'swal2-modern-cancel',
                },
            }).then(result => {
                if (result.isConfirmed) {
                    onConfirm();
                } else {
                    resumeIfCanceled();
                }
            });
        } else {
            if (window.confirm(text)) {
                onConfirm();
            } else {
                resumeIfCanceled();
            }
        }
    }

    function toggleFullscreen() {
        const wrapper = document.getElementById('gameFullscreenWrapper');
        if (!wrapper) return;

        if (document.fullscreenElement) {
            document.exitFullscreen().catch(() => {});
            return;
        }

        if (wrapper.classList.contains('is-pseudo-fullscreen')) {
            wrapper.classList.remove('is-pseudo-fullscreen');
            document.body.style.overflow = '';
            syncFullscreenUI();
            return;
        }

        if (wrapper.requestFullscreen) {
            wrapper.requestFullscreen().catch(() => {
                wrapper.classList.add('is-pseudo-fullscreen');
                document.body.style.overflow = 'hidden';
                syncFullscreenUI();
            });
        } else {
            wrapper.classList.add('is-pseudo-fullscreen');
            document.body.style.overflow = 'hidden';
            syncFullscreenUI();
        }
    }

    // --- Statistik Lokal ---
    function loadStats() {
        const defaults = {
            easy: { win: 0, lose: 0, draw: 0 },
            normal: { win: 0, lose: 0, draw: 0 },
            hard: { win: 0, lose: 0, draw: 0 },
        };
        try {
            const raw = localStorage.getItem(STATS_KEY);
            if (!raw) return defaults;
            const parsed = JSON.parse(raw);
            return {
                easy: { ...defaults.easy, ...(parsed.easy || {}) },
                normal: { ...defaults.normal, ...(parsed.normal || {}) },
                hard: { ...defaults.hard, ...(parsed.hard || {}) },
            };
        } catch (e) {
            return defaults;
        }
    }

    function recordStat(result) {
        const stats = loadStats();
        const bucket = stats[state.difficulty] || stats.normal;
        if (result === 'win') bucket.win++;
        else if (result === 'lose') bucket.lose++;
        else bucket.draw++;
        try {
            localStorage.setItem(STATS_KEY, JSON.stringify(stats));
        } catch (e) {}
        renderStats();
    }

    function renderStats() {
        const stats = loadStats();
        const totalWin = stats.easy.win + stats.normal.win + stats.hard.win;
        const totalLose = stats.easy.lose + stats.normal.lose + stats.hard.lose;
        const totalDraw = stats.easy.draw + stats.normal.draw + stats.hard.draw;
        const total = totalWin + totalLose + totalDraw;
        const rate = total > 0 ? Math.round((totalWin / total) * 100) : 0;

        const elW = document.getElementById('statWin');
        const elL = document.getElementById('statLose');
        const elR = document.getElementById('statRate');
        if (elW) elW.textContent = totalWin;
        if (elL) elL.textContent = totalLose;
        if (elR) elR.textContent = rate + '%';
    }

    function launchConfetti() {
        const container = document.getElementById('victoryConfetti');
        if (!container) return;
        const colors = ['#f59e0b', '#10b981', '#6366f1', '#ec4899', '#38bdf8'];
        container.innerHTML = '';

        for (let i = 0; i < 34; i++) {
            const piece = document.createElement('span');
            piece.className = 'confetti-piece';
            piece.style.left = Math.round(Math.random() * 100) + '%';
            piece.style.backgroundColor = colors[i % colors.length];
            piece.style.animationDuration = (1.6 + Math.random() * 1.1).toFixed(2) + 's';
            piece.style.animationDelay = (Math.random() * 0.45).toFixed(2) + 's';
            container.appendChild(piece);
        }

        setTimeout(() => {
            if (container) container.innerHTML = '';
        }, 3000);
    }

    // --- Deck & Helpers ---
    function createDeck() {
        const deck = [];
        let id = 1;
        for (let a = 0; a <= 6; a++) {
            for (let b = a; b <= 6; b++) {
                deck.push({ id: id++, a, b });
            }
        }
        return deck;
    }

    function shuffle(arr) {
        const copy = arr.slice();
        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }
        return copy;
    }

    function handPipSum(hand) {
        return hand.reduce((sum, t) => sum + t.a + t.b, 0);
    }

    function countNumberInHand(hand, num) {
        return hand.reduce((acc, t) => acc + (t.a === num || t.b === num ? 1 : 0), 0);
    }

    function uniqueNumbersInHand(hand) {
        const s = new Set();
        hand.forEach(t => { s.add(t.a); s.add(t.b); });
        return s.size;
    }

    function countNumberOnBoard(num) {
        return state.board.reduce((acc, t) => acc + (t.left === num || t.right === num ? 1 : 0), 0);
    }

    function getValidMovesForPlayer(playerIdx) {
        const player = state.players[playerIdx];
        if (!player) return [];

        if (state.board.length === 0) {
            const opener = player.hand.find(t => t.id === state.openingTileId);
            if (opener) {
                return [{ tile: opener, side: 'right' }];
            }
            return player.hand.map(tile => ({ tile, side: 'right' }));
        }

        const moves = [];
        for (const tile of player.hand) {
            const canLeft = tile.a === state.leftEnd || tile.b === state.leftEnd;
            const canRight = tile.a === state.rightEnd || tile.b === state.rightEnd;
            if (canLeft) moves.push({ tile, side: 'left' });
            if (canRight && (state.leftEnd !== state.rightEnd || !canLeft)) {
                moves.push({ tile, side: 'right' });
            }
        }
        return moves;
    }

    function findStarter(players) {
        for (let d = 6; d >= 0; d--) {
            for (let p = 0; p < players.length; p++) {
                const found = players[p].hand.find(t => t.a === d && t.b === d);
                if (found) {
                    return { playerIdx: p, tile: found };
                }
            }
        }
        let best = { playerIdx: 0, tile: players[0].hand[0], score: -1 };
        players.forEach((pl, idx) => {
            pl.hand.forEach(t => {
                const sc = t.a + t.b;
                if (sc > best.score) best = { playerIdx: idx, tile: t, score: sc };
            });
        });
        return { playerIdx: best.playerIdx, tile: best.tile };
    }

    // --- AI Engine (3 Level: Santai, Normal, Sulit) ---
    function chooseAiMove(playerIdx) {
        const moves = getValidMovesForPlayer(playerIdx);
        if (moves.length === 0) return null;
        if (moves.length === 1) return moves[0];

        if (state.difficulty === 'easy') {
            return moves[Math.floor(Math.random() * moves.length)];
        }

        const player = state.players[playerIdx];
        const nextPlayerIdx = (playerIdx + 1) % state.players.length;
        const nextPassed = state.passedNumbers[nextPlayerIdx] || new Set();

        let bestMove = moves[0];
        let bestScore = -Infinity;

        for (const move of moves) {
            const { tile, side } = move;
            const remaining = player.hand.filter(t => t.id !== tile.id);
            const isDouble = tile.a === tile.b;
            const pipValue = tile.a + tile.b;

            let newLeft = state.leftEnd;
            let newRight = state.rightEnd;
            let changedEnd = tile.a;

            if (state.board.length === 0) {
                newLeft = tile.a;
                newRight = tile.b;
                changedEnd = tile.a;
            } else if (side === 'left') {
                newLeft = (tile.b === state.leftEnd) ? tile.a : tile.b;
                changedEnd = newLeft;
            } else {
                newRight = (tile.a === state.rightEnd) ? tile.b : tile.a;
                changedEnd = newRight;
            }

            let score = 0;
            score += pipValue * 2.2;
            if (isDouble) score += 6;
            score += countNumberInHand(remaining, changedEnd) * 3;

            if (state.difficulty === 'hard') {
                score += pipValue * 0.4;
                if (isDouble) score += 2.5;
                score += countNumberInHand(remaining, newLeft) * 2 + countNumberInHand(remaining, newRight) * 2;
                score += uniqueNumbersInHand(remaining) * 1.5;

                if (nextPassed.has(newLeft) && nextPassed.has(newRight)) {
                    score += 22;
                } else if (nextPassed.has(changedEnd)) {
                    score += 10;
                }

                for (let opp = 0; opp < state.players.length; opp++) {
                    if (opp === playerIdx || opp === nextPlayerIdx) continue;
                    const oppPassed = state.passedNumbers[opp];
                    if (oppPassed && oppPassed.has(changedEnd)) {
                        score += 3;
                    }
                }

                const remOnBoardLeft = countNumberOnBoard(newLeft) + (tile.a === newLeft || tile.b === newLeft ? 1 : 0);
                const remOnBoardRight = countNumberOnBoard(newRight) + (tile.a === newRight || tile.b === newRight ? 1 : 0);
                const unseenLeft = Math.max(0, 7 - remOnBoardLeft - countNumberInHand(remaining, newLeft));
                const unseenRight = Math.max(0, 7 - remOnBoardRight - countNumberInHand(remaining, newRight));

                if (unseenLeft === 0 && unseenRight === 0) {
                    const myPips = handPipSum(remaining);
                    score += myPips <= 12 ? 18 : -10;
                }
            }

            score += Math.random() * 0.8;

            if (score > bestScore) {
                bestScore = score;
                bestMove = move;
            }
        }

        return bestMove;
    }

    // --- Game Flow ---
    function clearTimers() {
        if (state.aiTimer) {
            clearTimeout(state.aiTimer);
            state.aiTimer = null;
        }
        if (state.shuffleTimer) {
            clearTimeout(state.shuffleTimer);
            state.shuffleTimer = null;
        }
    }

    function renderMoveHistory() {
        const listEl = document.getElementById('moveHistoryList');
        if (!listEl) return;
        if (state.logs.length === 0) {
            listEl.innerHTML = '<li class="text-slate-400">Belum ada langkah dimainkan.</li>';
            return;
        }
        listEl.innerHTML = state.logs.map((entry, idx) =>
            `<li class="${idx === 0 ? 'text-amber-200 font-semibold' : 'text-slate-300/90'}">&bull; ${entry}</li>`
        ).join('');
    }

    function addLog(text) {
        state.logs.unshift(text);
        if (state.logs.length > 12) state.logs.pop();
        renderMoveHistory();
    }

    function syncStartRestartButtons() {
        const btnStart = document.getElementById('btnStartGame');
        const btnRestart = document.getElementById('btnRestartGame');
        const startOverlay = document.getElementById('startOverlay');
        const shuffleOverlay = document.getElementById('shuffleOverlay');

        if (btnStart && btnRestart) {
            if (state.hasStarted) {
                btnStart.classList.add('hidden');
                btnStart.classList.remove('inline-flex');
                btnRestart.classList.remove('hidden');
                btnRestart.classList.add('inline-flex');
            } else {
                btnStart.classList.remove('hidden');
                btnStart.classList.add('inline-flex');
                btnRestart.classList.add('hidden');
                btnRestart.classList.remove('inline-flex');
            }
        }

        if (startOverlay) {
            if (!state.hasStarted && !state.isShuffling) {
                startOverlay.classList.remove('hidden');
                startOverlay.classList.add('flex');
            } else {
                startOverlay.classList.add('hidden');
                startOverlay.classList.remove('flex');
            }
        }

        if (shuffleOverlay) {
            if (state.isShuffling) {
                shuffleOverlay.classList.remove('hidden');
                shuffleOverlay.classList.add('flex');
            } else {
                shuffleOverlay.classList.add('hidden');
                shuffleOverlay.classList.remove('flex');
            }
        }
    }

    function updateStartSummaryText() {
        const el = document.getElementById('startSummaryText');
        if (!el) return;
        const modeText = state.aiCount === 2 ? 'Mode 3 Orang (2 AI · 8 Kartu)' : 'Mode 4 Orang (3 AI · 6 Kartu)';
        el.textContent = `${modeText} · Level ${DIFFICULTY_LABELS[state.difficulty]} · 4 Kartu Sisa Tertutup`;
    }

    function renderIdleState() {
        clearTimers();
        state.hasStarted = false;
        state.isShuffling = false;
        state.isRunning = false;
        state.isFinished = false;
        state.board = [];
        state.anchorTileId = null;
        state.autoFitScale = 1;
        state.leftEnd = null;
        state.rightEnd = null;
        state.selectedTileId = null;
        state.lastPlacedId = null;
        state.lastActionByPlayer = {};
        state.logs = [];
        state.players = [{ id: 0, name: 'Anda', isHuman: true, hand: [], passed: false }];
        for (let i = 1; i <= state.aiCount; i++) {
            state.players.push({
                id: i,
                name: AI_NAMES[i - 1] || ('AI ' + i),
                isHuman: false,
                hand: [],
                passed: false,
            });
        }

        updateStartSummaryText();
        syncStartRestartButtons();
        syncTablePreferences();
        renderMoveHistory();
        renderOpponents();
        renderBoard();

        const handEl = document.getElementById('playerHand');
        const countEl = document.getElementById('playerCardCount');
        const hintEl = document.getElementById('playableHintBadge');
        if (countEl) countEl.textContent = 'Belum dimulai';
        if (hintEl) hintEl.classList.add('hidden');
        if (handEl) {
            handEl.innerHTML = '<div class="text-xs text-slate-500 dark:text-slate-400 py-4 text-center">Tekan <strong>Mulai Permainan</strong> untuk mengocok dan membagikan batu.</div>';
        }
    }

    function startNewRoundWithShuffle() {
        clearTimers();
        closeRoundModal();

        state.hasStarted = true;
        state.isShuffling = true;
        state.isRunning = false;
        state.isFinished = false;
        state.board = [];
        state.anchorTileId = null;
        state.autoFitScale = 1;
        state.leftEnd = null;
        state.rightEnd = null;
        state.selectedTileId = null;
        state.lastPlacedId = null;
        state.lastActionByPlayer = {};
        state.animateLatest = false;

        syncStartRestartButtons();
        syncTablePreferences();
        playSoundShuffle();

        const turnBadge = document.getElementById('turnIndicator');
        const logEl = document.getElementById('lastMoveLog');
        if (turnBadge) turnBadge.textContent = 'Mengocok batu...';
        if (logEl) logEl.textContent = 'Mengocok 28 batu dan membagikan ke pemain...';

        const handEl = document.getElementById('playerHand');
        if (handEl) {
            handEl.innerHTML = '<div class="text-xs text-indigo-500 font-semibold py-4 animate-pulse">Membagikan batu ke rak tangan Anda...</div>';
        }

        state.shuffleTimer = setTimeout(() => {
            state.isShuffling = false;
            dealAndBeginRound();
        }, 1050);
    }

    function dealAndBeginRound() {
        const cardsPerPlayer = state.aiCount === 2 ? 8 : 6;
        const deck = shuffle(createDeck());

        const players = [
            { id: 0, name: 'Anda', isHuman: true, hand: [], passed: false },
        ];
        for (let i = 1; i <= state.aiCount; i++) {
            players.push({
                id: i,
                name: AI_NAMES[i - 1] || ('AI ' + i),
                isHuman: false,
                hand: [],
                passed: false,
            });
        }

        let cursor = 0;
        for (let p = 0; p < players.length; p++) {
            players[p].hand = deck.slice(cursor, cursor + cardsPerPlayer)
                .sort((x, y) => (x.a + x.b) - (y.a + y.b) || x.a - y.a);
            cursor += cardsPerPlayer;
        }

        const hiddenTiles = deck.slice(cursor);
        const starter = findStarter(players);

        state.players = players;
        state.hiddenTiles = hiddenTiles;
        state.board = [];
        state.anchorTileId = null;
        state.autoFitScale = 1;
        state.leftEnd = null;
        state.rightEnd = null;
        state.currentTurn = starter.playerIdx;
        state.openingTileId = starter.tile.id;
        state.consecutivePasses = 0;
        state.passedNumbers = {};
        state.lastActionByPlayer = {};
        players.forEach((_, idx) => { state.passedNumbers[idx] = new Set(); });
        state.selectedTileId = null;
        state.lastPlacedId = null;
        state.animateLatest = false;
        state.justDealt = true;
        state.isRunning = true;
        state.isFinished = false;
        state.logs = [];

        addLog(`${players[starter.playerIdx].name} membuka ronde dengan batu ${starter.tile.a}|${starter.tile.b}.`);

        syncStartRestartButtons();
        syncTablePreferences();
        renderAll();
        state.justDealt = false;
        scheduleNextTurnIfAi();
    }

    function placeTile(playerIdx, tileId, side) {
        if (!state.isRunning || state.isFinished || playerIdx !== state.currentTurn) return;

        const player = state.players[playerIdx];
        const idx = player.hand.findIndex(t => t.id === tileId);
        if (idx === -1) return;

        const tile = player.hand[idx];
        let placed = null;
        let placedSide = 'center';

        if (state.board.length === 0) {
            placed = { id: tile.id, left: tile.a, right: tile.b, isDouble: tile.a === tile.b, ownerId: playerIdx };
            state.board.push(placed);
            state.anchorTileId = placed.id;
            state.leftEnd = placed.left;
            state.rightEnd = placed.right;
            placedSide = 'center';
        } else if (side === 'left') {
            if (tile.b === state.leftEnd) {
                placed = { id: tile.id, left: tile.a, right: tile.b, isDouble: tile.a === tile.b, ownerId: playerIdx };
            } else if (tile.a === state.leftEnd) {
                placed = { id: tile.id, left: tile.b, right: tile.a, isDouble: tile.a === tile.b, ownerId: playerIdx };
            } else {
                return;
            }
            state.board.unshift(placed);
            state.leftEnd = placed.left;
            placedSide = 'left';
        } else {
            if (tile.a === state.rightEnd) {
                placed = { id: tile.id, left: tile.a, right: tile.b, isDouble: tile.a === tile.b, ownerId: playerIdx };
            } else if (tile.b === state.rightEnd) {
                placed = { id: tile.id, left: tile.b, right: tile.a, isDouble: tile.a === tile.b, ownerId: playerIdx };
            } else {
                return;
            }
            state.board.push(placed);
            state.rightEnd = placed.right;
            placedSide = 'right';
        }

        player.hand.splice(idx, 1);
        state.players.forEach(p => { p.passed = false; });
        state.consecutivePasses = 0;
        state.selectedTileId = null;
        state.lastPlacedId = placed.id;
        state.lastPlacedSide = placedSide;
        state.animateLatest = true;
        state.openingTileId = null;
        state.lastActionByPlayer = {
            [playerIdx]: { text: `Taruh ${tile.a}|${tile.b}`, kind: 'play' },
        };

        playSoundPlace();
        const sideLabel = placedSide === 'center' ? 'tengah' : (placedSide === 'left' ? 'kiri' : 'kanan');
        addLog(`${player.name} menaruh ${tile.a}|${tile.b} di ${sideLabel}.`);

        syncTablePreferences();

        if (player.hand.length === 0) {
            finishRound('empty', [playerIdx]);
            return;
        }

        state.currentTurn = (state.currentTurn + 1) % state.players.length;
        renderAll();
        state.animateLatest = false;
        scrollViewportToSide(placedSide);
        scheduleNextTurnIfAi();
    }

    function passTurn(playerIdx) {
        if (!state.isRunning || state.isFinished || playerIdx !== state.currentTurn) return;

        const player = state.players[playerIdx];
        player.passed = true;
        state.consecutivePasses++;
        state.selectedTileId = null;
        state.lastActionByPlayer = {
            [playerIdx]: { text: 'LEWAT', kind: 'pass' },
        };

        if (state.leftEnd !== null) state.passedNumbers[playerIdx].add(state.leftEnd);
        if (state.rightEnd !== null) state.passedNumbers[playerIdx].add(state.rightEnd);

        playSoundPass();
        addLog(`${player.name} LEWAT (tidak ada batu cocok).`);

        if (state.consecutivePasses >= state.players.length) {
            let minPips = Infinity;
            let winners = [];
            state.players.forEach((p, idx) => {
                const pips = handPipSum(p.hand);
                if (pips < minPips) {
                    minPips = pips;
                    winners = [idx];
                } else if (pips === minPips) {
                    winners.push(idx);
                }
            });
            finishRound('blocked', winners);
            return;
        }

        state.currentTurn = (state.currentTurn + 1) % state.players.length;
        renderAll();
        scheduleNextTurnIfAi();
    }

    function scheduleNextTurnIfAi() {
        if (!state.isRunning || state.isFinished) return;
        const current = state.players[state.currentTurn];
        if (!current || current.isHuman) return;

        const delay = state.difficulty === 'hard' ? 850 : 700;
        state.aiTimer = setTimeout(() => {
            const move = chooseAiMove(state.currentTurn);
            if (move) {
                placeTile(state.currentTurn, move.tile.id, move.side);
            } else {
                passTurn(state.currentTurn);
            }
        }, delay);
    }

    function finishRound(reason, winnerIndices) {
        state.isFinished = true;
        state.isRunning = false;
        clearTimers();

        const humanWon = winnerIndices.includes(0) && winnerIndices.length === 1;
        const isDraw = winnerIndices.includes(0) && winnerIndices.length > 1;

        if (humanWon) {
            recordStat('win');
            playSoundWin();
            launchConfetti();
        } else if (isDraw) {
            recordStat('draw');
            playSoundWin();
        } else {
            recordStat('lose');
            playSoundLose();
        }

        renderAll();
        state.animateLatest = false;
        openRoundModal(reason, winnerIndices);
    }

    function scrollViewportToSide(side) {
        const vp = document.getElementById('boardViewport');
        if (!vp || vp.scrollWidth <= vp.clientWidth) return;
        if (side === 'left') {
            vp.scrollTo({ left: 0, behavior: 'smooth' });
        } else if (side === 'right') {
            vp.scrollTo({ left: vp.scrollWidth, behavior: 'smooth' });
        }
    }

    // --- Visual Domino 3D HTML Builder ---
    function buildPipGridHtml(val, sizeMode = 'hand') {
        const positions = PIP_POSITIONS[val] || [];
        const color = PIP_COLORS[val] || '#0f172a';
        const dotSize = sizeMode === 'mini'
            ? 'w-1.5 h-1.5'
            : (sizeMode === 'snake'
                ? ''
                : (sizeMode === 'board' ? 'w-1.5 h-1.5 sm:w-2 sm:h-2' : 'w-2 h-2 sm:w-2.5 sm:h-2.5'));
        const gridPad = sizeMode === 'snake' ? 'p-[2px]' : 'p-0.5 sm:p-1';
        let cells = '';
        for (let i = 0; i < 9; i++) {
            if (positions.includes(i)) {
                cells += `<span class="flex items-center justify-center"><span class="domino-pip ${dotSize}" style="background-color:${color}"></span></span>`;
            } else {
                cells += '<span></span>';
            }
        }
        return `<div class="grid grid-cols-3 grid-rows-3 w-full h-full ${gridPad}">${cells}</div>`;
    }

    function buildDominoHtml(a, b, opts = {}) {
        const {
            vertical = false,
            variant = 'hand', // board | hand | mini | snake
            playable = false,
            selected = false,
            dimmed = false,
            isLatest = false,
            showImpactRing = false,
            clickable = false,
            tileId = null,
            animClass = '',
            animDelayMs = 0,
        } = opts;

        let dims;
        if (variant === 'mini') {
            dims = vertical ? 'w-7 h-14 sm:w-8 sm:h-16 flex-col' : 'w-14 h-7 sm:w-16 sm:h-8 flex-row';
        } else if (variant === 'snake') {
            dims = vertical ? 'domino-3d--snake-v flex-col' : 'domino-3d--snake-h flex-row';
        } else if (variant === 'board') {
            dims = vertical ? 'w-8 h-16 sm:w-10 sm:h-20 flex-col' : 'w-16 h-8 sm:w-20 sm:h-10 flex-row';
        } else {
            dims = vertical ? 'w-12 h-24 sm:w-14 sm:h-28 flex-col' : 'w-24 h-12 sm:w-28 sm:h-14 flex-row';
        }

        const dividerClass = vertical ? 'domino-3d-divider w-full h-[2px]' : 'domino-3d-divider h-full w-[2px]';
        const halfSize = vertical ? 'w-full h-1/2' : 'w-1/2 h-full';

        let stateClass = variant === 'snake'
            ? 'domino-3d--board domino-3d--snake'
            : `domino-3d--${variant}`;
        if (selected) {
            stateClass += ' domino-3d--selected';
        } else if (playable) {
            stateClass += ' domino-3d--playable';
        } else if (dimmed) {
            stateClass += ' domino-3d--dimmed';
        } else if (isLatest) {
            stateClass += ' domino-3d--latest';
        }

        const tag = clickable ? 'button type="button"' : 'div';
        const closeTag = clickable ? '</button>' : '</div>';
        const dataAttr = tileId !== null ? `data-tile-id="${tileId}"` : '';
        const delayStyle = animDelayMs > 0 ? `style="animation-delay:${animDelayMs}ms;"` : '';
        const impactRing = (isLatest && showImpactRing) ? '<span class="domino-impact-ring" aria-hidden="true"></span>' : '';

        const snakeHalfPad = variant === 'snake' ? 'p-[1px]' : 'p-0.5';
        const accessibleTile = clickable
            ? `aria-label="Batu ${a} dan ${b}${playable ? ', bisa dimainkan' : ', belum bisa dimainkan'}" aria-pressed="${selected ? 'true' : 'false'}" aria-disabled="${playable ? 'false' : 'true'}"`
            : '';
        return `
            <${tag} ${dataAttr} ${accessibleTile} class="domino-tile domino-3d ${stateClass} ${dims} ${animClass} touch-manipulation" ${delayStyle}>
                ${impactRing}
                <div class="${halfSize} flex items-center justify-center ${snakeHalfPad}">${buildPipGridHtml(a, variant)}</div>
                <div class="${dividerClass}"><span class="domino-brass-pin" aria-hidden="true"></span></div>
                <div class="${halfSize} flex items-center justify-center ${snakeHalfPad}">${buildPipGridHtml(b, variant)}</div>
            ${closeTag}
        `;
    }

    // --- Rendering UI ---
    function renderOpponents() {
        const wrap = document.getElementById('opponentsRow');
        if (!wrap) return;

        const opponents = state.players.slice(1);
        wrap.className = `fs-compact-mb grid ${opponents.length === 2 ? 'grid-cols-2' : 'grid-cols-3'} gap-2 sm:gap-3 mb-4 shrink-0`;

        wrap.innerHTML = opponents.map(p => {
            const isTurn = state.isRunning && state.currentTurn === p.id;
            const miniCards = Array.from({ length: p.hand.length }).map(() =>
                '<span class="opponent-mini-stone"></span>'
            ).join('');

            const lastAct = state.lastActionByPlayer[p.id];
            const actionBadge = lastAct
                ? `<span class="ai-action-bubble px-1.5 py-0.5 rounded-md text-[9px] sm:text-[10px] font-bold border ${lastAct.kind === 'pass' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'}">${lastAct.text}</span>`
                : '';

            const statusText = !state.hasStarted
                ? '<span class="text-[9px] sm:text-[10px] text-slate-400">Siap</span>'
                : (isTurn
                    ? '<span class="text-[9px] sm:text-[10px] font-bold text-indigo-600 dark:text-indigo-400 animate-pulse">Berpikir...</span>'
                    : (p.passed
                        ? '<span class="px-1.5 py-0.5 rounded-md text-[9px] sm:text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">Lewat</span>'
                        : actionBadge));

            return `
                <div class="rounded-xl sm:rounded-2xl p-2 sm:p-3.5 border transition-all ${isTurn ? 'bg-indigo-50/90 dark:bg-indigo-950/60 border-indigo-400 ring-2 ring-indigo-400/40 shadow-sm' : 'bg-white dark:bg-slate-900 border-slate-200/80 dark:border-slate-800 shadow-2xs'}">
                    <div class="flex items-center justify-between gap-1.5 mb-1.5 sm:mb-2">
                        <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                            <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg sm:rounded-xl flex items-center justify-center text-[11px] sm:text-xs font-bold shrink-0 ${isTurn ? 'bg-indigo-600 text-white animate-pulse' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'}">
                                <i class="bi bi-robot"></i>
                            </span>
                            <div class="truncate">
                                <div class="text-[11px] sm:text-xs font-bold text-slate-900 dark:text-white truncate">${p.name}</div>
                                <div class="hidden sm:block text-[10px] text-slate-400">${DIFFICULTY_LABELS[state.difficulty]}</div>
                            </div>
                        </div>
                        <span class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-mono font-bold shrink-0 ${state.hasStarted && p.hand.length <= 2 ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300'}">
                            ${p.hand.length}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-1 min-h-[18px] sm:min-h-[20px]">
                        <div class="flex flex-wrap items-center gap-0.5">${miniCards || (state.hasStarted ? '<span class="text-[10px] text-emerald-600 font-bold">Habis!</span>' : '<span class="text-[10px] text-slate-400">-</span>')}</div>
                        ${statusText}
                    </div>
                </div>
            `;
        }).join('');
    }

    const SNAKE_UNIT = 34;
    const SNAKE_GAP = 3;

    function computeSnakeMaxHalfW(viewportEl) {
        const w = viewportEl ? viewportEl.clientWidth : 360;
        if (w < 460) return 120;
        if (w < 720) return 185;
        return 255;
    }

    function placeNextOnArm(cursor, matchVal, openVal, isDouble, maxHalfW) {
        if (isDouble) {
            const w = SNAKE_UNIT;
            const h = SNAKE_UNIT * 2;
            const x = cursor.dir === 'right' ? cursor.edgeX : cursor.edgeX - w;
            const y = cursor.laneY - SNAKE_UNIT;
            cursor.edgeX = cursor.dir === 'right' ? (x + w + SNAKE_GAP) : (x - SNAKE_GAP);
            cursor.lastWasDouble = true;
            return { x, y, w, h, vertical: true, a: matchVal, b: openVal };
        }

        const reachedEdge = cursor.dir === 'right'
            ? (cursor.edgeX + SNAKE_UNIT * 2 > maxHalfW)
            : (cursor.edgeX - SNAKE_UNIT * 2 < -maxHalfW);

        if (reachedEdge && !cursor.lastWasDouble) {
            const w = SNAKE_UNIT;
            const h = SNAKE_UNIT * 2;
            const prevDir = cursor.dir;
            const x = prevDir === 'right'
                ? (cursor.edgeX - SNAKE_GAP - SNAKE_UNIT)
                : (cursor.edgeX + SNAKE_GAP);
            const y = cursor.turnDir === 1
                ? (cursor.laneY + (SNAKE_UNIT / 2) + SNAKE_GAP)
                : (cursor.laneY - (SNAKE_UNIT / 2) - SNAKE_GAP - h);

            // Sudut belok ke bawah (+1): atas menyambung batu sebelumnya (a = matchVal), bawah terbuka (b = openVal).
            // Sudut belok ke atas (-1): bawah menyambung batu sebelumnya (b = matchVal), atas terbuka (a = openVal).
            const a = cursor.turnDir === 1 ? matchVal : openVal;
            const b = cursor.turnDir === 1 ? openVal : matchVal;

            cursor.laneY += cursor.turnDir * (SNAKE_UNIT * 2 + SNAKE_GAP);
            cursor.dir = prevDir === 'right' ? 'left' : 'right';
            cursor.edgeX = cursor.dir === 'left' ? (x - SNAKE_GAP) : (x + w + SNAKE_GAP);
            cursor.lastWasDouble = false;

            return { x, y, w, h, vertical: true, a, b };
        }

        const w = SNAKE_UNIT * 2;
        const h = SNAKE_UNIT;
        const x = cursor.dir === 'right' ? cursor.edgeX : cursor.edgeX - w;
        const y = cursor.laneY - (SNAKE_UNIT / 2);
        const a = cursor.dir === 'right' ? matchVal : openVal;
        const b = cursor.dir === 'right' ? openVal : matchVal;

        cursor.edgeX = cursor.dir === 'right' ? (x + w + SNAKE_GAP) : (x - SNAKE_GAP);
        cursor.lastWasDouble = false;
        return { x, y, w, h, vertical: false, a, b };
    }

    function buildSnakeLayout(board, anchorTileId, maxHalfW, ghostSpec = null) {
        if (!board || board.length === 0) {
            return { tiles: [], ghosts: [], leftTip: null, rightTip: null, width: 0, height: 0 };
        }

        let anchorIdx = anchorTileId ? board.findIndex(t => t.id === anchorTileId) : -1;
        if (anchorIdx < 0) anchorIdx = Math.floor(board.length / 2);

        const anchorTile = board[anchorIdx];
        const anchorW = anchorTile.isDouble ? SNAKE_UNIT : SNAKE_UNIT * 2;
        const anchorH = anchorTile.isDouble ? SNAKE_UNIT * 2 : SNAKE_UNIT;
        const placed = [{
            tile: anchorTile,
            id: anchorTile.id,
            x: -anchorW / 2,
            y: -anchorH / 2,
            w: anchorW,
            h: anchorH,
            vertical: anchorTile.isDouble,
            a: anchorTile.left,
            b: anchorTile.right,
            arm: 'center',
        }];

        const rightCursor = {
            dir: 'right',
            turnDir: 1,
            laneY: 0,
            edgeX: (anchorW / 2) + SNAKE_GAP,
            lastWasDouble: anchorTile.isDouble,
        };
        const leftCursor = {
            dir: 'left',
            turnDir: -1,
            laneY: 0,
            edgeX: -(anchorW / 2) - SNAKE_GAP,
            lastWasDouble: anchorTile.isDouble,
        };

        for (let i = anchorIdx - 1; i >= 0; i--) {
            const t = board[i];
            const pos = placeNextOnArm(leftCursor, t.right, t.left, t.isDouble, maxHalfW);
            placed.push({ tile: t, id: t.id, arm: 'left', ...pos });
        }

        for (let i = anchorIdx + 1; i < board.length; i++) {
            const t = board[i];
            const pos = placeNextOnArm(rightCursor, t.left, t.right, t.isDouble, maxHalfW);
            placed.push({ tile: t, id: t.id, arm: 'right', ...pos });
        }

        const leftTip = {
            x: leftCursor.dir === 'right' ? (leftCursor.edgeX + 4) : (leftCursor.edgeX - 4),
            y: leftCursor.laneY,
            align: leftCursor.dir === 'right' ? 'right' : 'left',
        };
        const rightTip = {
            x: rightCursor.dir === 'right' ? (rightCursor.edgeX + 4) : (rightCursor.edgeX - 4),
            y: rightCursor.laneY,
            align: rightCursor.dir === 'right' ? 'right' : 'left',
        };

        const ghosts = [];
        if (ghostSpec && ghostSpec.show) {
            const leftGhost = placeNextOnArm(leftCursor, ghostSpec.leftVal, ghostSpec.leftVal, ghostSpec.isDouble, maxHalfW);
            ghosts.push({ side: 'left', endValue: ghostSpec.leftVal, ...leftGhost });

            const rightGhost = placeNextOnArm(rightCursor, ghostSpec.rightVal, ghostSpec.rightVal, ghostSpec.isDouble, maxHalfW);
            ghosts.push({ side: 'right', endValue: ghostSpec.rightVal, ...rightGhost });
        }

        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        placed.forEach(item => {
            if (item.x < minX) minX = item.x;
            if (item.y < minY) minY = item.y;
            if (item.x + item.w > maxX) maxX = item.x + item.w;
            if (item.y + item.h > maxY) maxY = item.y + item.h;
        });
        ghosts.forEach(g => {
            if (g.x < minX) minX = g.x;
            if (g.y < minY) minY = g.y;
            if (g.x + g.w > maxX) maxX = g.x + g.w;
            if (g.y + g.h > maxY) maxY = g.y + g.h;
        });

        if (!ghostSpec || !ghostSpec.show) {
            [leftTip, rightTip].forEach(tip => {
                if (tip.align === 'left') {
                    minX = Math.min(minX, tip.x - 54);
                } else {
                    maxX = Math.max(maxX, tip.x + 54);
                }
                minY = Math.min(minY, tip.y - 16);
                maxY = Math.max(maxY, tip.y + 16);
            });
        }

        const padX = 16;
        const padY = 16;
        const offsetX = -minX + padX;
        const offsetY = -minY + padY;

        placed.forEach(item => {
            item.x += offsetX;
            item.y += offsetY;
        });
        ghosts.forEach(g => {
            g.x += offsetX;
            g.y += offsetY;
        });
        leftTip.x += offsetX;
        leftTip.y += offsetY;
        rightTip.x += offsetX;
        rightTip.y += offsetY;

        return {
            tiles: placed,
            ghosts,
            leftTip,
            rightTip,
            width: Math.ceil((maxX - minX) + padX * 2),
            height: Math.ceil((maxY - minY) + padY * 2),
        };
    }

    function renderBoard() {
        const boardEl = document.getElementById('dominoBoard');
        const viewportEl = document.getElementById('boardViewport');
        const leftBadge = document.getElementById('boardLeftEnd');
        const rightBadge = document.getElementById('boardRightEnd');
        const turnBadge = document.getElementById('turnIndicator');
        const logEl = document.getElementById('lastMoveLog');

        if (leftBadge) leftBadge.textContent = state.leftEnd !== null ? state.leftEnd : '-';
        if (rightBadge) rightBadge.textContent = state.rightEnd !== null ? state.rightEnd : '-';

        if (turnBadge) {
            if (!state.hasStarted) {
                turnBadge.textContent = 'Siap Bermain — pilih pengaturan lalu mulai';
                turnBadge.dataset.turnState = 'ready';
            } else if (state.isShuffling) {
                turnBadge.textContent = 'Mengocok batu...';
                turnBadge.dataset.turnState = 'busy';
            } else if (state.isFinished) {
                turnBadge.textContent = 'Ronde Selesai';
                turnBadge.dataset.turnState = 'finished';
            } else {
                const cur = state.players[state.currentTurn];
                turnBadge.textContent = cur.isHuman ? 'Giliran Anda!' : ('Giliran ' + cur.name + '...');
                turnBadge.dataset.turnState = cur.isHuman ? 'human' : 'ai';
            }
        }

        if (logEl) {
            logEl.textContent = state.logs[0] || 'Pilih jumlah pemain dan tingkat kesulitan, lalu mulai permainan.';
        }

        if (!boardEl) return;

        if (state.board.length === 0) {
            state.autoFitScale = 1;
            boardEl.style.width = '';
            boardEl.style.height = '';
            boardEl.innerHTML = '';
            syncTablePreferences();
            return;
        }

        const isMyTurn = state.isRunning && !state.isFinished && state.currentTurn === 0;
        let showGhostSlots = false;
        let selectedTile = null;
        if (isMyTurn && state.selectedTileId !== null) {
            const selectedMoves = getValidMovesForPlayer(0).filter(m => m.tile.id === state.selectedTileId);
            showGhostSlots = selectedMoves.length === 2;
            if (selectedMoves.length > 0) {
                selectedTile = selectedMoves[0].tile;
            }
        }

        const maxHalfW = computeSnakeMaxHalfW(viewportEl);
        const layout = buildSnakeLayout(
            state.board,
            state.anchorTileId,
            maxHalfW,
            showGhostSlots && selectedTile ? {
                show: true,
                isDouble: selectedTile.a === selectedTile.b,
                leftVal: state.leftEnd,
                rightVal: state.rightEnd,
            } : null
        );

        const availW = Math.max(240, (viewportEl ? viewportEl.clientWidth : 360) - 24);
        const availH = isFullscreenActive()
            ? Math.max(240, (viewportEl ? viewportEl.clientHeight : 380) - 24)
            : Math.max(240, window.innerWidth >= 640 ? 330 : 270);
        const scaleW = layout.width > availW ? (availW / layout.width) : 1;
        const scaleH = layout.height > availH ? (availH / layout.height) : 1;
        state.autoFitScale = Math.max(0.56, Math.round(Math.min(1, scaleW, scaleH) * 100) / 100);
        syncTablePreferences();

        boardEl.style.width = layout.width + 'px';
        boardEl.style.height = layout.height + 'px';

        const tilesHtml = layout.tiles.map(item => {
            const tile = item.tile;
            const isLatest = tile.id === state.lastPlacedId;
            let placeAnim = '';
            if (isLatest && state.animateLatest) {
                placeAnim = state.lastPlacedSide === 'left'
                    ? 'anim-place-left'
                    : (state.lastPlacedSide === 'right' ? 'anim-place-right' : 'anim-place-center');
            }
            const stoneHtml = buildDominoHtml(item.a, item.b, {
                vertical: item.vertical,
                variant: 'snake',
                isLatest,
                showImpactRing: isLatest && state.animateLatest,
                animClass: placeAnim,
            });
            return `<div class="domino-snake-item" style="left:${item.x}px;top:${item.y}px;width:${item.w}px;height:${item.h}px;">${stoneHtml}</div>`;
        }).join('');

        let overlayHtml = '';
        if (showGhostSlots && layout.ghosts.length > 0) {
            overlayHtml = layout.ghosts.map(g => {
                const label = g.side === 'left' ? 'Kiri' : 'Kanan';
                const orientCls = g.vertical ? 'domino-ghost-slot--v' : 'domino-ghost-slot--h';
                return `<button type="button" class="domino-ghost-slot domino-ghost-slot--snake ${orientCls}" data-place-side="${g.side}" style="left:${g.x}px;top:${g.y}px;" title="Taruh di Ujung ${label} (${g.endValue})">
                    <span>${label}</span>
                    <span class="font-mono text-amber-100">(${g.endValue})</span>
                </button>`;
            }).join('');
        } else if (layout.leftTip && layout.rightTip) {
            overlayHtml = `
                <div class="domino-end-badge domino-end-badge--${layout.leftTip.align}" style="left:${layout.leftTip.x}px;top:${layout.leftTip.y}px;">Kiri: <span class="font-mono text-white">${state.leftEnd}</span></div>
                <div class="domino-end-badge domino-end-badge--${layout.rightTip.align}" style="left:${layout.rightTip.x}px;top:${layout.rightTip.y}px;">Kanan: <span class="font-mono text-white">${state.rightEnd}</span></div>
            `;
        }

        boardEl.innerHTML = tilesHtml + overlayHtml;

        if (showGhostSlots) {
            boardEl.querySelectorAll('button.domino-ghost-slot[data-place-side]').forEach(slotBtn => {
                slotBtn.addEventListener('click', () => {
                    if (!isMyTurn || state.selectedTileId === null) return;
                    const side = slotBtn.getAttribute('data-place-side');
                    if (side === 'left' || side === 'right') {
                        placeTile(0, state.selectedTileId, side);
                    }
                });
            });
        }
    }

    function renderPlayerHand() {
        const handEl = document.getElementById('playerHand');
        const countEl = document.getElementById('playerCardCount');
        const hintEl = document.getElementById('playableHintBadge');
        const passBtn = document.getElementById('btnPlayerPass');
        const sidePicker = document.getElementById('sidePickerBar');
        const btnLeft = document.getElementById('btnPlaceLeft');
        const btnRight = document.getElementById('btnPlaceRight');

        const human = state.players[0];
        if (!human || !handEl) return;

        if (countEl) countEl.textContent = `${human.hand.length} batu (${handPipSum(human.hand)} mata)`;

        const isMyTurn = state.isRunning && !state.isFinished && state.currentTurn === 0;
        const handCard = document.getElementById('playerHandCard');
        if (handCard) {
            handCard.dataset.playerTurn = isMyTurn ? 'true' : 'false';
            handCard.setAttribute('aria-label', isMyTurn ? 'Tangan Anda, giliran Anda' : 'Tangan Anda');
        }
        const validMoves = isMyTurn ? getValidMovesForPlayer(0) : [];
        const playableIds = new Set(validMoves.map(m => m.tile.id));

        if (hintEl) {
            if (isMyTurn) {
                hintEl.classList.remove('hidden');
                if (playableIds.size > 0) {
                    hintEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800';
                    hintEl.textContent = `${playableIds.size} batu cocok`;
                } else {
                    hintEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800';
                    hintEl.textContent = 'Tidak ada batu cocok';
                }
            } else {
                hintEl.classList.add('hidden');
            }
        }

        if (passBtn) {
            if (isMyTurn && validMoves.length === 0) {
                passBtn.classList.remove('hidden');
                passBtn.classList.add('inline-flex');
            } else {
                passBtn.classList.add('hidden');
                passBtn.classList.remove('inline-flex');
            }
        }

        if (sidePicker) {
            if (isMyTurn && state.selectedTileId !== null) {
                sidePicker.classList.remove('hidden');
                sidePicker.classList.add('flex');
                if (btnLeft) btnLeft.textContent = `Taruh Ujung Kiri (${state.leftEnd})`;
                if (btnRight) btnRight.textContent = `Taruh Ujung Kanan (${state.rightEnd})`;
            } else {
                sidePicker.classList.add('hidden');
                sidePicker.classList.remove('flex');
            }
        }

        if (human.hand.length === 0) {
            handEl.innerHTML = '<div class="text-xs text-emerald-600 font-semibold py-4">Semua batu Anda sudah habis!</div>';
            return;
        }

        handEl.innerHTML = human.hand.map((tile, idx) => {
            const canPlay = isMyTurn && playableIds.has(tile.id);
            const isSelected = state.selectedTileId === tile.id;
            return buildDominoHtml(tile.a, tile.b, {
                vertical: true,
                variant: 'hand',
                playable: canPlay,
                selected: isSelected,
                dimmed: isMyTurn && !canPlay,
                clickable: true,
                tileId: tile.id,
                animClass: state.justDealt ? 'anim-deal-in' : '',
                animDelayMs: state.justDealt ? idx * 55 : 0,
            });
        }).join('');

        handEl.querySelectorAll('button[data-tile-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (!isMyTurn) return;
                const tid = parseInt(btn.getAttribute('data-tile-id'), 10);
                const tileMoves = validMoves.filter(m => m.tile.id === tid);
                if (tileMoves.length === 0) return;

                if (tileMoves.length === 1) {
                    placeTile(0, tid, tileMoves[0].side);
                } else {
                    playSoundSelect();
                    state.selectedTileId = (state.selectedTileId === tid) ? null : tid;
                    renderPlayerHand();
                    renderBoard();
                }
            });
        });
    }

    function renderAll() {
        renderOpponents();
        renderBoard();
        renderPlayerHand();
    }

    // --- Modal Akhir Ronde ---
    function openRoundModal(reason, winnerIndices) {
        const modal = document.getElementById('roundEndModal');
        const titleEl = document.getElementById('roundResultTitle');
        const subtitleEl = document.getElementById('roundResultSubtitle');
        const iconWrap = document.getElementById('roundResultIcon');
        const summaryEl = document.getElementById('roundPlayersSummary');
        const hiddenEl = document.getElementById('roundHiddenTiles');

        if (!modal) return;

        const winnerNames = winnerIndices.map(i => state.players[i].name).join(' & ');
        const humanWon = winnerIndices.includes(0) && winnerIndices.length === 1;
        const isDraw = winnerIndices.includes(0) && winnerIndices.length > 1;

        if (titleEl) {
            if (humanWon) titleEl.textContent = 'Selamat! Anda Menang!';
            else if (isDraw) titleEl.textContent = 'Hasil Seri!';
            else titleEl.textContent = winnerNames + ' Menang!';
        }

        if (subtitleEl) {
            subtitleEl.textContent = reason === 'empty'
                ? `${winnerNames} berhasil menghabiskan seluruh batu (Dam!).`
                : `Meja buntu (semua lewat). Pemenang ditentukan dari total mata sisa terkecil.`;
        }

        if (iconWrap) {
            iconWrap.className = 'w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl ' +
                (humanWon ? 'bg-emerald-100 text-emerald-600' : (isDraw ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-rose-600'));
            iconWrap.innerHTML = humanWon ? '<i class="bi bi-trophy-fill"></i>' : (isDraw ? '<i class="bi bi-award-fill"></i>' : '<i class="bi bi-emoji-neutral-fill"></i>');
        }

        if (summaryEl) {
            const ranked = state.players.map((p, idx) => ({
                ...p,
                idx,
                pips: handPipSum(p.hand),
                isWinner: winnerIndices.includes(idx),
            })).sort((a, b) => a.pips - b.pips || a.hand.length - b.hand.length);

            summaryEl.innerHTML = ranked.map(p => `
                <div class="p-3 rounded-2xl border ${p.isWinner ? 'bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800' : 'bg-slate-50/70 dark:bg-slate-800/60 border-slate-200/80 dark:border-slate-700'}">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            ${p.isWinner ? '<i class="bi bi-trophy-fill text-amber-500"></i>' : ''}
                            <span>${p.name}</span>
                        </div>
                        <span class="text-xs font-mono font-bold ${p.isWinner ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-600 dark:text-slate-300'}">
                            ${p.hand.length} batu &middot; ${p.pips} mata
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        ${p.hand.length > 0
                            ? p.hand.map(t => buildDominoHtml(t.a, t.b, { vertical: false, variant: 'mini' })).join('')
                            : '<span class="text-xs font-semibold text-emerald-600">Kartu Habis (0 mata)</span>'}
                    </div>
                </div>
            `).join('');
        }

        if (hiddenEl) {
            hiddenEl.innerHTML = state.hiddenTiles.map(t =>
                buildDominoHtml(t.a, t.b, { vertical: false, variant: 'mini' })
            ).join('');
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeRoundModal() {
        const modal = document.getElementById('roundEndModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // --- Event Binding & Init ---
    function syncSettingButtons() {
        document.querySelectorAll('[data-ai-count]').forEach(btn => {
            const val = parseInt(btn.getAttribute('data-ai-count'), 10);
            const active = val === state.aiCount;
            btn.className = 'px-3 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer ' +
                (active
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700');
        });

        document.querySelectorAll('[data-difficulty]').forEach(btn => {
            const val = btn.getAttribute('data-difficulty');
            const active = val === state.difficulty;
            btn.className = 'px-3 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer ' +
                (active
                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs'
                    : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700');
        });

        const muteBtn = document.getElementById('btnToggleMute');
        if (muteBtn) {
            muteBtn.innerHTML = state.muted
                ? '<i class="bi bi-volume-mute-fill"></i> <span class="hidden sm:inline">Senyap</span>'
                : '<i class="bi bi-volume-up-fill"></i> <span class="hidden sm:inline">Suara</span>';
        }

        syncTablePreferences();
    }

    document.addEventListener('DOMContentLoaded', function () {
        try {
            state.muted = localStorage.getItem(MUTE_KEY) === '1';
            const savedCam = localStorage.getItem(CAMERA_KEY);
            if (savedCam && CAMERA_ORDER.includes(savedCam)) state.camera = savedCam;
            const savedFelt = localStorage.getItem(FELT_KEY);
            if (savedFelt && ['emerald', 'royal', 'classic'].includes(savedFelt)) state.felt = savedFelt;
        } catch (e) {}

        document.querySelectorAll('[data-ai-count]').forEach(btn => {
            btn.addEventListener('click', () => {
                const count = parseInt(btn.getAttribute('data-ai-count'), 10);
                if ((count === 2 || count === 3) && count !== state.aiCount) {
                    const applyChange = () => {
                        state.aiCount = count;
                        syncSettingButtons();
                        if (state.hasStarted) {
                            startNewRoundWithShuffle();
                        } else {
                            renderIdleState();
                        }
                    };

                    confirmMidGameAction(
                        'Ganti Jumlah Pemain?',
                        'Pertandingan yang sedang berlangsung akan diakhiri dan dimulai ulang dengan jumlah pemain baru. Lanjutkan?',
                        'Ya, Akhiri & Ganti',
                        applyChange
                    );
                }
            });
        });

        document.querySelectorAll('[data-difficulty]').forEach(btn => {
            btn.addEventListener('click', () => {
                const diff = btn.getAttribute('data-difficulty');
                if (['easy', 'normal', 'hard'].includes(diff) && diff !== state.difficulty) {
                    const applyChange = () => {
                        state.difficulty = diff;
                        syncSettingButtons();
                        if (state.hasStarted) {
                            startNewRoundWithShuffle();
                        } else {
                            renderIdleState();
                        }
                    };

                    confirmMidGameAction(
                        'Ganti Tingkat Kesulitan?',
                        `Pertandingan yang sedang berlangsung akan diakhiri dan dimulai ulang pada level ${DIFFICULTY_LABELS[diff]}. Lanjutkan?`,
                        'Ya, Akhiri & Ganti',
                        applyChange
                    );
                }
            });
        });

        document.querySelectorAll('[data-felt-theme]').forEach(btn => {
            btn.addEventListener('click', () => {
                const felt = btn.getAttribute('data-felt-theme');
                if (['emerald', 'royal', 'classic'].includes(felt)) {
                    state.felt = felt;
                    try { localStorage.setItem(FELT_KEY, felt); } catch (e) {}
                    syncTablePreferences();
                }
            });
        });

        const btnCamera = document.getElementById('btnCycleCamera');
        if (btnCamera) {
            btnCamera.addEventListener('click', () => {
                const idx = CAMERA_ORDER.indexOf(state.camera);
                state.camera = CAMERA_ORDER[(idx + 1) % CAMERA_ORDER.length];
                try { localStorage.setItem(CAMERA_KEY, state.camera); } catch (e) {}
                syncTablePreferences();
            });
        }

        const btnZoomOut = document.getElementById('btnZoomOut');
        const btnZoomIn = document.getElementById('btnZoomIn');
        const btnZoomReset = document.getElementById('btnZoomReset');
        if (btnZoomOut) {
            btnZoomOut.addEventListener('click', () => {
                state.userZoomOverride = true;
                state.boardZoom = Math.max(0.75, Math.round((state.boardZoom - 0.1) * 100) / 100);
                syncTablePreferences();
            });
        }
        if (btnZoomIn) {
            btnZoomIn.addEventListener('click', () => {
                state.userZoomOverride = true;
                state.boardZoom = Math.min(1.25, Math.round((state.boardZoom + 0.1) * 100) / 100);
                syncTablePreferences();
            });
        }
        if (btnZoomReset) {
            btnZoomReset.addEventListener('click', () => {
                state.userZoomOverride = false;
                state.boardZoom = 1;
                syncTablePreferences();
            });
        }

        const btnHistory = document.getElementById('btnToggleHistory');
        const historyPanel = document.getElementById('moveHistoryPanel');
        if (btnHistory && historyPanel) {
            btnHistory.addEventListener('click', () => {
                state.historyOpen = !state.historyOpen;
                historyPanel.classList.toggle('hidden', !state.historyOpen);
            });
        }

        const btnStart = document.getElementById('btnStartGame');
        if (btnStart) btnStart.addEventListener('click', startNewRoundWithShuffle);

        const btnStartOverlay = document.getElementById('btnStartOverlay');
        if (btnStartOverlay) btnStartOverlay.addEventListener('click', startNewRoundWithShuffle);

        const btnRestart = document.getElementById('btnRestartGame');
        if (btnRestart) {
            btnRestart.addEventListener('click', () => {
                confirmMidGameAction(
                    'Restart Pertandingan?',
                    'Pertandingan yang sedang berjalan akan diakhiri dan batu dikocok ulang dari awal. Lanjutkan?',
                    'Ya, Restart',
                    startNewRoundWithShuffle
                );
            });
        }

        const btnPlayAgain = document.getElementById('btnPlayAgain');
        if (btnPlayAgain) btnPlayAgain.addEventListener('click', startNewRoundWithShuffle);

        const btnCloseRound = document.getElementById('btnCloseRoundModal');
        if (btnCloseRound) btnCloseRound.addEventListener('click', closeRoundModal);

        const btnPass = document.getElementById('btnPlayerPass');
        if (btnPass) btnPass.addEventListener('click', () => passTurn(0));

        const btnLeft = document.getElementById('btnPlaceLeft');
        if (btnLeft) {
            btnLeft.addEventListener('click', () => {
                if (state.selectedTileId !== null) placeTile(0, state.selectedTileId, 'left');
            });
        }

        const btnRight = document.getElementById('btnPlaceRight');
        if (btnRight) {
            btnRight.addEventListener('click', () => {
                if (state.selectedTileId !== null) placeTile(0, state.selectedTileId, 'right');
            });
        }

        const btnCancelSide = document.getElementById('btnCancelSide');
        if (btnCancelSide) {
            btnCancelSide.addEventListener('click', () => {
                state.selectedTileId = null;
                renderPlayerHand();
                renderBoard();
            });
        }

        const muteBtn = document.getElementById('btnToggleMute');
        if (muteBtn) {
            muteBtn.addEventListener('click', () => {
                state.muted = !state.muted;
                try { localStorage.setItem(MUTE_KEY, state.muted ? '1' : '0'); } catch (e) {}
                syncSettingButtons();
            });
        }

        const fsBtn = document.getElementById('btnToggleFullscreen');
        if (fsBtn) {
            fsBtn.addEventListener('click', toggleFullscreen);
        }
        document.addEventListener('fullscreenchange', syncFullscreenUI);
        let resizeRafTimer = null;
        window.addEventListener('resize', () => {
            if (resizeRafTimer) clearTimeout(resizeRafTimer);
            resizeRafTimer = setTimeout(() => {
                if (state.board.length > 0) renderBoard();
            }, 120);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const wrapper = document.getElementById('gameFullscreenWrapper');
                if (wrapper && wrapper.classList.contains('is-pseudo-fullscreen')) {
                    wrapper.classList.remove('is-pseudo-fullscreen');
                    document.body.style.overflow = '';
                    syncFullscreenUI();
                }
            }
        });

        const btnRules = document.getElementById('btnOpenRules');
        const rulesModal = document.getElementById('rulesModal');
        const btnCloseRules = document.getElementById('btnCloseRules');
        if (btnRules && rulesModal) {
            btnRules.addEventListener('click', () => {
                rulesModal.classList.remove('hidden');
                rulesModal.classList.add('flex');
            });
        }
        if (btnCloseRules && rulesModal) {
            btnCloseRules.addEventListener('click', () => {
                rulesModal.classList.add('hidden');
                rulesModal.classList.remove('flex');
            });
        }

        bind3dParallax();
        renderStats();
        syncSettingButtons();
        syncFullscreenUI();
        renderIdleState();
    });
})();
