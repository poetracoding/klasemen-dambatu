(function () {
    'use strict';

    const STATS_KEY = 'dam_batu_stats_v1';
    const MUTE_KEY = 'dam_batu_muted_v1';

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

    const AI_NAMES = ['AI Budi', 'AI Agus', 'AI Rina'];

    let state = {
        aiCount: 3,           // 2 (3 orang, 8 kartu) atau 3 (4 orang, 6 kartu)
        difficulty: 'normal', // easy | normal | hard
        players: [],          // [{ id, name, isHuman, hand: [], passed: false }]
        hiddenTiles: [],      // 4 kartu sisa tertutup
        board: [],            // [{ id, left, right, isDouble, ownerId }]
        leftEnd: null,
        rightEnd: null,
        currentTurn: 0,
        openingTileId: null,
        consecutivePasses: 0,
        passedNumbers: {},    // { playerIdx: Set<number> }
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

    // --- Audio (Web Audio API, tanpa file eksternal) ---
    let audioCtx = null;
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

    function playSoundShuffle() {
        for (let i = 0; i < 6; i++) {
            playTone(180 + (i % 3) * 55, 0.045, 'triangle', 0.08, i * 0.14);
        }
    }

    function playSoundPlace() {
        playTone(220, 0.06, 'triangle', 0.16, 0);
        playTone(480, 0.04, 'sine', 0.08, 0.015);
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

    // --- Fullscreen ---
    function isFullscreenActive() {
        const wrapper = document.getElementById('gameFullscreenWrapper');
        return Boolean(document.fullscreenElement) || (wrapper && wrapper.classList.contains('is-pseudo-fullscreen'));
    }

    function syncFullscreenUI() {
        const btn = document.getElementById('btnToggleFullscreen');
        if (!btn) return;
        if (isFullscreenActive()) {
            btn.innerHTML = '<i class="bi bi-fullscreen-exit"></i> <span>Kecilkan Meja</span>';
            btn.title = 'Keluar Mode Layar Penuh';
        } else {
            btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i> <span>Perluas Meja</span>';
            btn.title = 'Perluas Meja (Layar Penuh)';
        }
    }

    function confirmMidGameAction(title, text, confirmText, onConfirm) {
        if (!state.isRunning || state.isFinished) {
            onConfirm();
            return;
        }

        // Jeda sementara giliran AI selama dialog konfirmasi terbuka
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

    function addLog(text) {
        state.logs.unshift(text);
        if (state.logs.length > 6) state.logs.pop();
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
        state.leftEnd = null;
        state.rightEnd = null;
        state.selectedTileId = null;
        state.lastPlacedId = null;
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
        renderOpponents();
        renderBoard();

        const handEl = document.getElementById('playerHand');
        const countEl = document.getElementById('playerCardCount');
        if (countEl) countEl.textContent = 'Belum dimulai';
        if (handEl) {
            handEl.innerHTML = '<div class="text-xs text-slate-400 py-4">Klik <strong>Start Game</strong> untuk mengocok dan membagikan batu.</div>';
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
        state.leftEnd = null;
        state.rightEnd = null;
        state.selectedTileId = null;
        state.lastPlacedId = null;
        state.animateLatest = false;

        syncStartRestartButtons();
        playSoundShuffle();

        const turnBadge = document.getElementById('turnIndicator');
        const logEl = document.getElementById('lastMoveLog');
        if (turnBadge) turnBadge.textContent = 'Mengocok batu...';
        if (logEl) logEl.textContent = 'Mengocok 28 batu dan membagikan ke pemain...';

        const handEl = document.getElementById('playerHand');
        if (handEl) {
            handEl.innerHTML = '<div class="text-xs text-indigo-500 font-semibold py-4 animate-pulse">Membagikan batu ke tangan Anda...</div>';
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
        state.leftEnd = null;
        state.rightEnd = null;
        state.currentTurn = starter.playerIdx;
        state.openingTileId = starter.tile.id;
        state.consecutivePasses = 0;
        state.passedNumbers = {};
        players.forEach((_, idx) => { state.passedNumbers[idx] = new Set(); });
        state.selectedTileId = null;
        state.lastPlacedId = null;
        state.animateLatest = false;
        state.justDealt = true;
        state.isRunning = true;
        state.isFinished = false;
        state.logs = [];

        addLog(`${players[starter.playerIdx].name} memulai ronde dengan batu ${starter.tile.a}|${starter.tile.b}.`);

        syncStartRestartButtons();
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

        playSoundPlace();
        const sideLabel = placedSide === 'center' ? 'tengah' : (placedSide === 'left' ? 'kiri' : 'kanan');
        addLog(`${player.name} menaruh ${tile.a}|${tile.b} di ${sideLabel}.`);

        if (player.hand.length === 0) {
            finishRound('empty', [playerIdx]);
            return;
        }

        state.currentTurn = (state.currentTurn + 1) % state.players.length;
        renderAll();
        state.animateLatest = false;
        scheduleNextTurnIfAi();
    }

    function passTurn(playerIdx) {
        if (!state.isRunning || state.isFinished || playerIdx !== state.currentTurn) return;

        const player = state.players[playerIdx];
        player.passed = true;
        state.consecutivePasses++;
        state.selectedTileId = null;

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

    // --- Visual Domino HTML Builder ---
    function buildPipGridHtml(val, small = false) {
        const positions = PIP_POSITIONS[val] || [];
        const color = PIP_COLORS[val] || '#0f172a';
        const dotSize = small ? 'w-1.5 h-1.5' : 'w-2 h-2';
        let cells = '';
        for (let i = 0; i < 9; i++) {
            if (positions.includes(i)) {
                cells += `<span class="flex items-center justify-center"><span class="${dotSize} rounded-full" style="background-color:${color}"></span></span>`;
            } else {
                cells += '<span></span>';
            }
        }
        return `<div class="grid grid-cols-3 grid-rows-3 w-full h-full p-0.5">${cells}</div>`;
    }

    function buildDominoHtml(a, b, opts = {}) {
        const {
            vertical = false,
            small = false,
            playable = false,
            selected = false,
            dimmed = false,
            isLatest = false,
            clickable = false,
            tileId = null,
            animClass = '',
            animDelayMs = 0,
        } = opts;

        const dims = vertical
            ? (small ? 'w-7 h-14 sm:w-8 sm:h-16 flex-col' : 'w-11 h-22 sm:w-14 sm:h-28 flex-col')
            : (small ? 'w-14 h-7 sm:w-16 sm:h-8 flex-row' : 'w-20 h-10 sm:w-24 sm:h-12 flex-row');

        const divider = vertical ? 'w-full h-px bg-slate-300' : 'h-full w-px bg-slate-300';
        const halfSize = vertical ? 'w-full h-1/2' : 'w-1/2 h-full';

        let stateClasses = 'bg-white border-slate-300 shadow-xs';
        if (selected) {
            stateClasses = 'bg-indigo-50 border-indigo-600 ring-2 ring-indigo-500 -translate-y-2 shadow-lg';
        } else if (playable) {
            stateClasses = 'bg-white border-indigo-400 ring-2 ring-indigo-400/50 hover:-translate-y-1.5 hover:border-indigo-600 shadow-md cursor-pointer';
        } else if (dimmed) {
            stateClasses = 'bg-slate-100 border-slate-200 opacity-50 cursor-not-allowed';
        } else if (isLatest) {
            stateClasses = 'bg-amber-50 border-amber-400 ring-2 ring-amber-400/70 shadow-md';
        }

        const tag = clickable ? 'button type="button"' : 'div';
        const closeTag = clickable ? '</button>' : '</div>';
        const dataAttr = tileId !== null ? `data-tile-id="${tileId}"` : '';
        const delayStyle = animDelayMs > 0 ? `animation-delay:${animDelayMs}ms;` : '';

        return `
            <${tag} ${dataAttr} class="domino-tile inline-flex ${dims} rounded-lg border-2 ${stateClasses} ${animClass} overflow-hidden transition-all duration-150 select-none touch-manipulation shrink-0" style="background-color:#ffffff;${delayStyle}">
                <div class="${halfSize} flex items-center justify-center p-0.5">${buildPipGridHtml(a, small)}</div>
                <div class="${divider} shrink-0"></div>
                <div class="${halfSize} flex items-center justify-center p-0.5">${buildPipGridHtml(b, small)}</div>
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
                '<span class="inline-block w-2 h-3.5 sm:w-2.5 sm:h-4 rounded-[2px] sm:rounded-[3px] bg-indigo-600 border border-indigo-400/60 shadow-2xs"></span>'
            ).join('');

            const statusText = !state.hasStarted
                ? '<span class="text-[9px] sm:text-[10px] text-slate-400">Siap</span>'
                : (p.passed
                    ? '<span class="px-1.5 py-0.5 rounded-md text-[9px] sm:text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 uppercase">Lewat</span>'
                    : (isTurn ? '<span class="text-[9px] sm:text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">Jalan...</span>' : ''));

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
                    <div class="flex items-center justify-between gap-1 min-h-[16px] sm:min-h-[20px]">
                        <div class="flex flex-wrap items-center gap-0.5">${miniCards || (state.hasStarted ? '<span class="text-[10px] text-emerald-600 font-bold">Habis!</span>' : '<span class="text-[10px] text-slate-400">-</span>')}</div>
                        ${statusText}
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderBoard() {
        const boardEl = document.getElementById('dominoBoard');
        const leftBadge = document.getElementById('boardLeftEnd');
        const rightBadge = document.getElementById('boardRightEnd');
        const turnBadge = document.getElementById('turnIndicator');
        const logEl = document.getElementById('lastMoveLog');

        if (leftBadge) leftBadge.textContent = state.leftEnd !== null ? state.leftEnd : '-';
        if (rightBadge) rightBadge.textContent = state.rightEnd !== null ? state.rightEnd : '-';

        if (turnBadge) {
            if (!state.hasStarted) {
                turnBadge.textContent = 'Siap Bermain — Klik Start Game';
            } else if (state.isShuffling) {
                turnBadge.textContent = 'Mengocok batu...';
            } else if (state.isFinished) {
                turnBadge.textContent = 'Ronde Selesai';
            } else {
                const cur = state.players[state.currentTurn];
                turnBadge.textContent = cur.isHuman ? 'Giliran Anda!' : ('Giliran ' + cur.name + '...');
            }
        }

        if (logEl) {
            logEl.textContent = state.logs[0] || 'Pilih jumlah pemain & tingkat kesulitan, lalu klik Start Game.';
        }

        if (!boardEl) return;

        if (state.board.length === 0) {
            boardEl.innerHTML = '';
            return;
        }

        boardEl.innerHTML = state.board.map(tile => {
            const isLatest = tile.id === state.lastPlacedId;
            let placeAnim = '';
            if (isLatest && state.animateLatest) {
                placeAnim = state.lastPlacedSide === 'left'
                    ? 'anim-place-left'
                    : (state.lastPlacedSide === 'right' ? 'anim-place-right' : 'anim-place-center');
            }
            return buildDominoHtml(tile.left, tile.right, {
                vertical: tile.isDouble,
                small: true,
                isLatest,
                animClass: placeAnim,
            });
        }).join('');
    }

    function renderPlayerHand() {
        const handEl = document.getElementById('playerHand');
        const countEl = document.getElementById('playerCardCount');
        const passBtn = document.getElementById('btnPlayerPass');
        const sidePicker = document.getElementById('sidePickerBar');
        const btnLeft = document.getElementById('btnPlaceLeft');
        const btnRight = document.getElementById('btnPlaceRight');

        const human = state.players[0];
        if (!human || !handEl) return;

        if (countEl) countEl.textContent = `${human.hand.length} batu (${handPipSum(human.hand)} mata)`;

        const isMyTurn = state.isRunning && !state.isFinished && state.currentTurn === 0;
        const validMoves = isMyTurn ? getValidMovesForPlayer(0) : [];
        const playableIds = new Set(validMoves.map(m => m.tile.id));

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
                small: false,
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
                    state.selectedTileId = (state.selectedTileId === tid) ? null : tid;
                    renderPlayerHand();
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
                <div class="p-3 rounded-2xl border ${p.isWinner ? 'bg-emerald-50/70 border-emerald-200' : 'bg-slate-50/70 border-slate-200/80'}">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                            ${p.isWinner ? '<i class="bi bi-trophy-fill text-amber-500"></i>' : ''}
                            <span>${p.name}</span>
                        </div>
                        <span class="text-xs font-mono font-bold ${p.isWinner ? 'text-emerald-700' : 'text-slate-600'}">
                            ${p.hand.length} batu &middot; ${p.pips} mata
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        ${p.hand.length > 0
                            ? p.hand.map(t => buildDominoHtml(t.a, t.b, { vertical: false, small: true })).join('')
                            : '<span class="text-xs font-semibold text-emerald-600">Kartu Habis (0 mata)</span>'}
                    </div>
                </div>
            `).join('');
        }

        if (hiddenEl) {
            hiddenEl.innerHTML = state.hiddenTiles.map(t =>
                buildDominoHtml(t.a, t.b, { vertical: false, small: true })
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
    }

    document.addEventListener('DOMContentLoaded', function () {
        try {
            state.muted = localStorage.getItem(MUTE_KEY) === '1';
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

        renderStats();
        syncSettingButtons();
        syncFullscreenUI();
        renderIdleState();
    });
})();
