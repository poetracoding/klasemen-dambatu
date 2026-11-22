(function () {
    'use strict';

    const PREFS_KEY = 'ludo_queen_dambatu_prefs_v1';
    const STATS_KEY = 'ludo_queen_dambatu_stats_v1';

    const BOARD_SIZE = 660;
    const CELL = BOARD_SIZE / 15; // 44px

    const COLOR_META = {
        red: {
            label: 'Merah',
            aiName: 'Ratu Merah',
            startIdx: 0,
            fill: '#ef4444',
            dark: '#b91c1c',
            light: '#fee2e2',
            baseSlots: [[1.65, 1.65], [4.35, 1.65], [1.65, 4.35], [4.35, 4.35]],
            homeLane: [[7, 1], [7, 2], [7, 3], [7, 4], [7, 5]],
            crownCenter: [7.5, 6.52],
        },
        green: {
            label: 'Hijau',
            aiName: 'Ratu Hijau',
            startIdx: 13,
            fill: '#16a34a',
            dark: '#15803d',
            light: '#dcfce7',
            baseSlots: [[1.65, 10.65], [4.35, 10.65], [1.65, 13.35], [4.35, 13.35]],
            homeLane: [[1, 7], [2, 7], [3, 7], [4, 7], [5, 7]],
            crownCenter: [6.52, 7.5],
        },
        yellow: {
            label: 'Kuning',
            aiName: 'Ratu Emas',
            startIdx: 26,
            fill: '#eab308',
            dark: '#a16207',
            light: '#fef9c3',
            baseSlots: [[10.65, 10.65], [13.35, 10.65], [10.65, 13.35], [13.35, 13.35]],
            homeLane: [[7, 13], [7, 12], [7, 11], [7, 10], [7, 9]],
            crownCenter: [7.5, 8.48],
        },
        blue: {
            label: 'Biru',
            aiName: 'Ratu Biru',
            startIdx: 39,
            fill: '#2563eb',
            dark: '#1d4ed8',
            light: '#dbeafe',
            baseSlots: [[10.65, 1.65], [13.35, 1.65], [10.65, 4.35], [13.35, 4.35]],
            homeLane: [[13, 7], [12, 7], [11, 7], [10, 7], [9, 7]],
            crownCenter: [8.48, 7.5],
        },
    };

    const CLOCKWISE_COLORS = ['blue', 'red', 'green', 'yellow'];

    // 52 outer perimeter coordinates [row, col] starting from Red's start (6, 1)
    const MAIN_TRACK = [
        [6, 1], [6, 2], [6, 3], [6, 4], [6, 5],
        [5, 6], [4, 6], [3, 6], [2, 6], [1, 6], [0, 6],
        [0, 7], [0, 8],
        [1, 8], [2, 8], [3, 8], [4, 8], [5, 8],
        [6, 9], [6, 10], [6, 11], [6, 12], [6, 13], [6, 14],
        [7, 14], [8, 14],
        [8, 13], [8, 12], [8, 11], [8, 10], [8, 9],
        [9, 8], [10, 8], [11, 8], [12, 8], [13, 8], [14, 8],
        [14, 7], [14, 6],
        [13, 6], [12, 6], [11, 6], [10, 6], [9, 6],
        [8, 5], [8, 4], [8, 3], [8, 2], [8, 1], [8, 0],
        [7, 0], [6, 0],
    ];

    // 8 Safe Star track indices (4 Start squares + 4 mid-arm stars)
    const SAFE_INDICES = new Set([0, 8, 13, 21, 26, 34, 39, 47]);

    const DIFFICULTY_LABELS = {
        easy: 'Santai',
        normal: 'Normal',
        hard: 'Sulit',
    };

    const CUBE_FACE_ROTATIONS = {
        1: { x: 0, y: 0 },
        2: { x: -90, y: 0 },
        3: { x: 0, y: -90 },
        4: { x: 0, y: 90 },
        5: { x: 90, y: 0 },
        6: { x: 0, y: 180 },
    };

    const CAMERA_LABELS = {
        '3d': '3D Meja',
        cinematic: 'Sinematik',
        '2d': '2D Atas',
    };
    const CAMERA_ORDER = ['3d', 'cinematic', '2d'];

    let prefs = {
        playerCount: 4,
        difficulty: 'normal',
        humanColor: 'blue',
        winMode: 'classic', // classic (4) | quick2 (2)
        muted: false,
        camera: '3d',
        felt: 'royal',
        zoom: 1,
    };

    let stats = {
        easy: { wins: 0, losses: 0 },
        normal: { wins: 0, losses: 0 },
        hard: { wins: 0, losses: 0 },
    };

    let state = {
        players: [], // { id, color, name, isHuman, pawns: [-1..-1] }
        currentTurn: 0,
        phase: 'need_roll', // need_roll | rolling | choose_pawn | moving | finished
        dice: 6,
        sixStreak: 0,
        movablePawns: [],
        logs: [],
        rollingAnim: false,
        diceJustLanded: false,
        movingPawn: null, // { playerIdx, pawnIdx, fromCoord, toCoord, stepStartedAt, stepDuration }
        particles: [],
        shakeTimer: 0,
        winnerIdx: null,
        timers: [],
    };

    let canvas = null;
    let ctx = null;
    let rafId = null;
    let cubeSpin = { x: 0, y: 180 };
    let lastOverlayMode = '';

    // --- Sound Synthesis (Web Audio API) ---
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

    function sfxRollTick(idx) {
        playTone(250 + ((idx * 43) % 130), 0.04, 'triangle', 0.05, 0);
    }
    function sfxRollLand(isSix) {
        playTone(isSix ? 587 : 440, 0.09, 'sine', 0.08, 0);
        if (isSix) playTone(880, 0.14, 'sine', 0.085, 0.07);
    }
    function sfxHop(stepNum, total) {
        playTone(360 + Math.round((stepNum / Math.max(1, total)) * 200), 0.05, 'sine', 0.055, 0);
    }
    function sfxCapture() {
        playTone(520, 0.08, 'sawtooth', 0.06, 0, 210);
        playTone(880, 0.14, 'sine', 0.08, 0.08);
    }
    function sfxCrown() {
        playTone(523, 0.08, 'sine', 0.08, 0);
        playTone(659, 0.08, 'sine', 0.08, 0.07);
        playTone(784, 0.14, 'sine', 0.085, 0.14);
    }
    function sfxWin() {
        [523, 659, 784, 1046].forEach((f, i) => playTone(f, 0.16, 'sine', 0.09, i * 0.09));
    }

    // --- Persistence & Helpers ---
    function loadStorage() {
        try {
            const rawP = localStorage.getItem(PREFS_KEY);
            if (rawP) prefs = { ...prefs, ...JSON.parse(rawP) };
        } catch (e) {}
        try {
            const rawS = localStorage.getItem(STATS_KEY);
            if (rawS) stats = { ...stats, ...JSON.parse(rawS) };
        } catch (e) {}
    }

    function savePrefs() {
        try { localStorage.setItem(PREFS_KEY, JSON.stringify(prefs)); } catch (e) {}
    }

    function saveStats() {
        try { localStorage.setItem(STATS_KEY, JSON.stringify(stats)); } catch (e) {}
    }

    function clearTimers() {
        state.timers.forEach(t => clearTimeout(t));
        state.timers = [];
    }

    function scheduleTimer(fn, ms) {
        const id = setTimeout(() => {
            state.timers = state.timers.filter(t => t !== id);
            fn();
        }, ms);
        state.timers.push(id);
        return id;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function addLog(text, kind = 'info') {
        state.logs.unshift({ text, kind });
        if (state.logs.length > 40) state.logs.length = 40;
    }

    function getGlobalTrackIndex(color, step) {
        if (step < 0 || step > 50) return null;
        const meta = COLOR_META[color];
        return (meta.startIdx + step) % 52;
    }

    function getStepCoordinate(color, step, pawnIdx) {
        const meta = COLOR_META[color];
        if (step < 0) {
            return meta.baseSlots[pawnIdx];
        }
        if (step <= 50) {
            const gIdx = (meta.startIdx + step) % 52;
            const rc = MAIN_TRACK[gIdx];
            return [rc[0] + 0.5, rc[1] + 0.5];
        }
        if (step <= 55) {
            const rc = meta.homeLane[step - 51];
            return [rc[0] + 0.5, rc[1] + 0.5];
        }
        return meta.crownCenter;
    }

    function getMovablePawns(playerIdx, diceVal) {
        const p = state.players[playerIdx];
        if (!p) return [];
        const indices = [];
        p.pawns.forEach((step, idx) => {
            if (step === -1 && diceVal === 6) {
                indices.push(idx);
            } else if (step >= 0 && step + diceVal <= 56) {
                indices.push(idx);
            }
        });
        return indices;
    }

    function getTargetFinishCount() {
        return prefs.winMode === 'quick2' ? 2 : 4;
    }

    // --- Game Lifecycle ---
    function startNewMatch() {
        clearTimers();
        closeModal('ludoWinModal');

        const humanColor = COLOR_META[prefs.humanColor] ? prefs.humanColor : 'blue';
        let colorsOrder;
        const startCIdx = CLOCKWISE_COLORS.indexOf(humanColor);
        if (prefs.playerCount === 2) {
            // 2 pemain selalu berhadapan diagonal
            colorsOrder = [humanColor, CLOCKWISE_COLORS[(startCIdx + 2) % 4]];
        } else if (prefs.playerCount === 3) {
            colorsOrder = [
                humanColor,
                CLOCKWISE_COLORS[(startCIdx + 1) % 4],
                CLOCKWISE_COLORS[(startCIdx + 2) % 4],
            ];
        } else {
            colorsOrder = [
                humanColor,
                CLOCKWISE_COLORS[(startCIdx + 1) % 4],
                CLOCKWISE_COLORS[(startCIdx + 2) % 4],
                CLOCKWISE_COLORS[(startCIdx + 3) % 4],
            ];
        }

        state.players = colorsOrder.map((c, idx) => ({
            id: idx,
            color: c,
            name: idx === 0 ? `Anda (${COLOR_META[c].label})` : COLOR_META[c].aiName,
            isHuman: idx === 0,
            pawns: [-1, -1, -1, -1],
        }));

        state.currentTurn = 0;
        state.phase = 'need_roll';
        state.dice = 6;
        state.sixStreak = 0;
        state.movablePawns = [];
        state.logs = [];
        state.rollingAnim = false;
        state.diceJustLanded = false;
        state.movingPawn = null;
        state.particles = [];
        state.shakeTimer = 0;
        state.winnerIdx = null;
        cubeSpin = { ...CUBE_FACE_ROTATIONS[6] };
        lastOverlayMode = '';

        addLog(`Pertandingan dimulai! Level AI: ${DIFFICULTY_LABELS[prefs.difficulty]}, Target: ${getTargetFinishCount()} pion ke Mahkota.`, 'start');
        syncControlsUI();
        applyBoardVisuals();
        renderUI();
    }

    function spawnParticles(cx, cy, color, count = 12) {
        for (let i = 0; i < count; i++) {
            const ang = (Math.PI * 2 * i) / count + Math.random() * 0.4;
            const spd = 60 + Math.random() * 110;
            state.particles.push({
                x: cx,
                y: cy,
                vx: Math.cos(ang) * spd,
                vy: Math.sin(ang) * spd,
                color,
                life: 0.45 + Math.random() * 0.25,
                maxLife: 0.7,
                r: 3 + Math.random() * 2.5,
            });
        }
    }

    function snapCubeSpinToFace(faceVal) {
        const target = CUBE_FACE_ROTATIONS[faceVal] || CUBE_FACE_ROTATIONS[1];
        const cycleX = Math.ceil(cubeSpin.x / 360) * 360;
        const cycleY = Math.ceil(cubeSpin.y / 360) * 360;
        cubeSpin = {
            x: cycleX + target.x,
            y: cycleY + target.y,
        };
    }

    function rollDice() {
        if (state.phase !== 'need_roll' || state.rollingAnim || state.movingPawn) return;
        const p = state.players[state.currentTurn];
        if (!p) return;

        state.phase = 'rolling';
        state.rollingAnim = true;
        state.diceJustLanded = false;
        state.movablePawns = [];
        renderUI();

        const delays = [75, 75, 85, 95, 110, 125, 140, 160, 175, 195]; // ~1235ms
        let step = 0;

        const tick = () => {
            if (state.phase !== 'rolling') return;
            if (step < delays.length) {
                let r = Math.floor(Math.random() * 6) + 1;
                if (r === state.dice) r = (r % 6) + 1;
                state.dice = r;
                cubeSpin.x += 175 + step * 18;
                cubeSpin.y += 235 + step * 22;
                const cubeEl = document.getElementById('ludoCenter3dDie');
                if (cubeEl) {
                    cubeEl.style.transform = `rotateX(${cubeSpin.x}deg) rotateY(${cubeSpin.y}deg)`;
                }
                sfxRollTick(step);
                renderQuickBar();
                const waitMs = delays[step];
                step += 1;
                scheduleTimer(tick, waitMs);
                return;
            }

            const finalDie = Math.floor(Math.random() * 6) + 1;
            state.dice = finalDie;
            state.rollingAnim = false;
            state.diceJustLanded = true;
            snapCubeSpinToFace(finalDie);
            sfxRollLand(finalDie === 6);
            addLog(`${p.name} melempar dadu: angka ${finalDie}.`, 'roll');
            renderUI();

            scheduleTimer(() => {
                if (state.phase !== 'rolling') return;
                state.diceJustLanded = false;

                if (finalDie === 6) {
                    state.sixStreak += 1;
                    if (state.sixStreak >= 3) {
                        state.sixStreak = 0;
                        addLog(`${p.name} mendapat angka 6 tiga kali berturut-turut — giliran hangus!`, 'warn');
                        renderUI();
                        scheduleTimer(advanceTurn, 720);
                        return;
                    }
                } else {
                    state.sixStreak = 0;
                }

                const movable = getMovablePawns(state.currentTurn, finalDie);
                if (movable.length === 0) {
                    if (finalDie === 6) {
                        addLog(`${p.name} tidak punya langkah valid, lempar ulang bonus angka 6.`, 'info');
                        state.phase = 'need_roll';
                        renderUI();
                        if (!p.isHuman) scheduleTimer(rollDice, 540);
                    } else {
                        addLog(`${p.name} tidak bisa melangkah.`, 'info');
                        renderUI();
                        scheduleTimer(advanceTurn, 580);
                    }
                    return;
                }

                state.movablePawns = movable;

                if (!p.isHuman) {
                    renderUI();
                    scheduleTimer(() => {
                        const chosen = aiChoosePawn(state.currentTurn, finalDie, movable);
                        executePawnMove(state.currentTurn, chosen, finalDie);
                    }, 340);
                    return;
                }

                // Jika hanya 1 langkah valid atau semua pion yang bisa gerak masih di kandang (-1)
                const allInBase = movable.every(idx => p.pawns[idx] === -1);
                const allSameStep = movable.every(idx => p.pawns[idx] === p.pawns[movable[0]]);
                if (movable.length === 1 || allInBase || allSameStep) {
                    state.phase = 'choose_pawn';
                    renderUI();
                    scheduleTimer(() => {
                        executePawnMove(0, movable[0], finalDie);
                    }, 300);
                    return;
                }

                state.phase = 'choose_pawn';
                renderUI();
            }, 520);
        };

        scheduleTimer(tick, delays[0]);
    }

    function executePawnMove(playerIdx, pawnIdx, diceVal) {
        const p = state.players[playerIdx];
        if (!p || state.phase === 'finished') return;

        state.diceJustLanded = false;
        state.movablePawns = [];
        const startStep = p.pawns[pawnIdx];
        const pathSteps = startStep === -1
            ? [0]
            : Array.from({ length: diceVal }, (_, i) => startStep + i + 1);

        state.phase = 'moving';
        renderCenterDiceOverlay();
        let stepCursor = 0;
        const STEP_DURATION_MS = 155;

        const doHop = () => {
            if (state.phase !== 'moving') return;

            if (stepCursor < pathSteps.length) {
                const prevStep = p.pawns[pawnIdx];
                const targetStep = pathSteps[stepCursor];
                const fromCoord = getStepCoordinate(p.color, prevStep, pawnIdx);
                const toCoord = getStepCoordinate(p.color, targetStep, pawnIdx);

                p.pawns[pawnIdx] = targetStep;
                state.movingPawn = {
                    playerIdx,
                    pawnIdx,
                    fromCoord,
                    toCoord,
                    stepStartedAt: performance.now(),
                    stepDuration: STEP_DURATION_MS,
                };
                sfxHop(stepCursor + 1, pathSteps.length);
                renderPlayersPanel();

                stepCursor += 1;
                scheduleTimer(doHop, STEP_DURATION_MS);
                return;
            }

            state.movingPawn = null;
            const endStep = p.pawns[pawnIdx];
            let earnedBonusRoll = diceVal === 6;

            // Cek sampai Mahkota Tengah (step === 56)
            if (endStep === 56) {
                sfxCrown();
                const coord = getStepCoordinate(p.color, 56, pawnIdx);
                spawnParticles(coord[1] * CELL, coord[0] * CELL, '#facc15', 18);
                addLog(`${p.name} memasukkan Pion #${pawnIdx + 1} ke Mahkota Tengah! (+Bonus lempar)`, 'crown');
                earnedBonusRoll = true;
            } else if (endStep >= 0 && endStep <= 50) {
                // Cek tangkap pion lawan pada non-Safe Square
                const globalIdx = getGlobalTrackIndex(p.color, endStep);
                if (globalIdx !== null && !SAFE_INDICES.has(globalIdx)) {
                    state.players.forEach(other => {
                        if (other.id === playerIdx) return;
                        other.pawns.forEach((oStep, oIdx) => {
                            if (oStep >= 0 && oStep <= 50) {
                                const oGlobal = getGlobalTrackIndex(other.color, oStep);
                                if (oGlobal === globalIdx) {
                                    other.pawns[oIdx] = -1;
                                    earnedBonusRoll = true;
                                    sfxCapture();
                                    state.shakeTimer = 0.22;
                                    const coord = getStepCoordinate(p.color, endStep, pawnIdx);
                                    spawnParticles(coord[1] * CELL, coord[0] * CELL, COLOR_META[other.color].fill, 16);
                                    addLog(`${p.name} MENANGKAP pion ${other.name} dan mendapat bonus lempar!`, 'capture');
                                }
                            }
                        });
                    });
                }
            }

            // Cek Kemenangan
            const finishedCount = p.pawns.filter(s => s === 56).length;
            if (finishedCount >= getTargetFinishCount()) {
                finishMatch(playerIdx);
                return;
            }

            if (earnedBonusRoll) {
                state.phase = 'need_roll';
                renderUI();
                if (!p.isHuman) scheduleTimer(rollDice, 580);
            } else {
                advanceTurn();
            }
        };

        doHop();
    }

    function advanceTurn() {
        state.sixStreak = 0;
        state.movablePawns = [];
        state.currentTurn = (state.currentTurn + 1) % state.players.length;
        state.phase = 'need_roll';
        renderUI();

        const nextP = state.players[state.currentTurn];
        if (nextP && !nextP.isHuman) {
            scheduleTimer(rollDice, 580);
        }
    }

    function finishMatch(winnerIdx) {
        clearTimers();
        state.phase = 'finished';
        state.winnerIdx = winnerIdx;
        sfxWin();

        const winner = state.players[winnerIdx];
        const didHumanWin = winnerIdx === 0;
        if (!stats[prefs.difficulty]) stats[prefs.difficulty] = { wins: 0, losses: 0 };
        if (didHumanWin) stats[prefs.difficulty].wins += 1;
        else stats[prefs.difficulty].losses += 1;
        saveStats();

        addLog(`🏆 ${winner.name} MEMENANGKAN PERTANDINGAN!`, 'crown');
        renderUI();
        showWinModal();
    }

    // --- 3-Level AI Heuristic Engine ---
    function aiChoosePawn(aiIdx, diceVal, movable) {
        const ai = state.players[aiIdx];
        if (prefs.difficulty === 'easy') {
            return movable[Math.floor(Math.random() * movable.length)];
        }

        let bestIdx = movable[0];
        let bestScore = -Infinity;

        movable.forEach(idx => {
            const step = ai.pawns[idx];
            const nextStep = step === -1 ? 0 : step + diceVal;
            let score = 0;

            if (step === -1 && diceVal === 6) {
                score += prefs.difficulty === 'hard' ? 85 : 70;
            }
            if (nextStep === 56) {
                score += 130;
            } else if (nextStep >= 51) {
                score += 55 + nextStep; // masuk jalur aman rumah sendiri
            } else {
                const nextGlobal = getGlobalTrackIndex(ai.color, nextStep);
                const curGlobal = step >= 0 ? getGlobalTrackIndex(ai.color, step) : null;

                // Cek tangkap lawan
                let captures = 0;
                if (nextGlobal !== null && !SAFE_INDICES.has(nextGlobal)) {
                    state.players.forEach(other => {
                        if (other.id === aiIdx) return;
                        other.pawns.forEach(oStep => {
                            if (oStep >= 0 && oStep <= 50 && getGlobalTrackIndex(other.color, oStep) === nextGlobal) {
                                captures += 1;
                            }
                        });
                    });
                }
                if (captures > 0) score += 115 * captures;

                // Skor mendarat di Bintang Aman
                if (nextGlobal !== null && SAFE_INDICES.has(nextGlobal)) score += 48;

                // Khusus Level Sulit: hindari meninggalkan bintang aman atau masuk area bahaya depan lawan
                if (prefs.difficulty === 'hard') {
                    if (curGlobal !== null && SAFE_INDICES.has(curGlobal)) score -= 22;
                    if (nextGlobal !== null && !SAFE_INDICES.has(nextGlobal)) {
                        let dangerCount = 0;
                        state.players.forEach(other => {
                            if (other.id === aiIdx) return;
                            other.pawns.forEach(oStep => {
                                if (oStep >= 0 && oStep <= 50) {
                                    const oGlob = getGlobalTrackIndex(other.color, oStep);
                                    const distAhead = (nextGlobal - oGlob + 52) % 52;
                                    if (distAhead >= 1 && distAhead <= 6) dangerCount += 1;
                                }
                            });
                        });
                        score -= dangerCount * 28;
                    }
                    score += nextStep * 0.6;
                } else {
                    score += nextStep * 0.4;
                }
            }

            if (score > bestScore) {
                bestScore = score;
                bestIdx = idx;
            }
        });

        return bestIdx;
    }

    // --- 15x15 Canvas Board & Pawn Renderer ---
    function drawStar(cx, cy, outerR, color) {
        const innerR = outerR * 0.45;
        ctx.save();
        ctx.fillStyle = color;
        ctx.beginPath();
        for (let i = 0; i < 10; i++) {
            const ang = (Math.PI / 5) * i - Math.PI / 2;
            const r = i % 2 === 0 ? outerR : innerR;
            const px = cx + Math.cos(ang) * r;
            const py = cy + Math.sin(ang) * r;
            if (i === 0) ctx.moveTo(px, py);
            else ctx.lineTo(px, py);
        }
        ctx.closePath();
        ctx.fill();
        ctx.restore();
    }

    function draw3dBevelCell(x, y, w, h, bg) {
        ctx.fillStyle = bg;
        ctx.fillRect(x, y, w, h);

        // 3D top-left highlight & bottom-right bevel shadow
        ctx.fillStyle = 'rgba(255, 255, 255, 0.42)';
        ctx.fillRect(x + 1, y + 1, w - 2, 2.5);
        ctx.fillRect(x + 1, y + 1, 2.5, h - 2);

        ctx.fillStyle = 'rgba(15, 23, 42, 0.16)';
        ctx.fillRect(x + 1, y + h - 3, w - 2, 2.5);
        ctx.fillRect(x + w - 3, y + 1, 2.5, h - 2);

        ctx.strokeStyle = 'rgba(15, 23, 42, 0.28)';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, w, h);
    }

    function drawYard(row0, col0, colorKey) {
        const meta = COLOR_META[colorKey];
        const x = col0 * CELL;
        const y = row0 * CELL;
        const size = 6 * CELL;

        const yardGrad = ctx.createLinearGradient(x, y, x + size, y + size);
        yardGrad.addColorStop(0, meta.fill);
        yardGrad.addColorStop(1, meta.dark);
        ctx.fillStyle = yardGrad;
        ctx.fillRect(x, y, size, size);

        // Bevel 3D sisi luar kandang
        ctx.fillStyle = 'rgba(255, 255, 255, 0.28)';
        ctx.fillRect(x + 2, y + 2, size - 4, 4);
        ctx.fillRect(x + 2, y + 2, 4, size - 4);
        ctx.fillStyle = 'rgba(15, 23, 42, 0.28)';
        ctx.fillRect(x + 2, y + size - 5, size - 4, 4);
        ctx.fillRect(x + size - 5, y + 2, 4, size - 4);

        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 2;
        ctx.strokeRect(x, y, size, size);

        // Pelataran cekung 3D di dalam kandang
        const pad = CELL * 0.85;
        const rx = x + pad;
        const ry = y + pad;
        const rw = size - pad * 2;
        const rh = size - pad * 2;
        const rr = 16;

        ctx.save();
        ctx.fillStyle = 'rgba(2, 6, 23, 0.25)';
        ctx.beginPath();
        ctx.roundRect(rx + 3, ry + 5, rw, rh, rr);
        ctx.fill();

        const trayGrad = ctx.createLinearGradient(rx, ry, rx, ry + rh);
        trayGrad.addColorStop(0, '#f1efe8');
        trayGrad.addColorStop(1, '#ffffff');
        ctx.fillStyle = trayGrad;
        ctx.beginPath();
        ctx.roundRect(rx, ry, rw, rh, rr);
        ctx.fill();
        ctx.strokeStyle = meta.dark;
        ctx.lineWidth = 2.5;
        ctx.stroke();
        ctx.restore();

        // 4 Lingkaran slot pion kandang 3D cekung
        meta.baseSlots.forEach(rc => {
            const sx = rc[1] * CELL;
            const sy = rc[0] * CELL;
            const sr = CELL * 0.43;

            const sockGrad = ctx.createRadialGradient(sx + 2, sy + 3, 2, sx, sy, sr);
            sockGrad.addColorStop(0, '#ffffff');
            sockGrad.addColorStop(0.55, meta.light);
            sockGrad.addColorStop(1, meta.fill);
            ctx.fillStyle = sockGrad;
            ctx.beginPath();
            ctx.arc(sx, sy, sr, 0, Math.PI * 2);
            ctx.fill();

            ctx.strokeStyle = meta.dark;
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    }

    function drawCenterCrown() {
        const x = 6 * CELL;
        const y = 6 * CELL;
        const s = 3 * CELL;
        const cx = x + s / 2;
        const cy = y + s / 2;

        const tris = [
            { color: COLOR_META.red.fill, dark: COLOR_META.red.dark, pts: [[x, y], [cx, cy], [x, y + s]] },
            { color: COLOR_META.green.fill, dark: COLOR_META.green.dark, pts: [[x, y], [x + s, y], [cx, cy]] },
            { color: COLOR_META.yellow.fill, dark: COLOR_META.yellow.dark, pts: [[x + s, y], [x + s, y + s], [cx, cy]] },
            { color: COLOR_META.blue.fill, dark: COLOR_META.blue.dark, pts: [[x, y + s], [x + s, y + s], [cx, cy]] },
        ];

        tris.forEach(t => {
            const g = ctx.createLinearGradient(t.pts[0][0], t.pts[0][1], cx, cy);
            g.addColorStop(0, t.color);
            g.addColorStop(1, t.dark);
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.moveTo(t.pts[0][0], t.pts[0][1]);
            ctx.lineTo(t.pts[1][0], t.pts[1][1]);
            ctx.lineTo(t.pts[2][0], t.pts[2][1]);
            ctx.closePath();
            ctx.fill();
            ctx.strokeStyle = 'rgba(15, 23, 42, 0.72)';
            ctx.lineWidth = 1.5;
            ctx.stroke();
        });

        // Medali Mahkota Tengah 3D
        ctx.fillStyle = 'rgba(2, 6, 23, 0.48)';
        ctx.beginPath();
        ctx.arc(cx + 2, cy + 4, 24, 0, Math.PI * 2);
        ctx.fill();

        const medalGrad = ctx.createRadialGradient(cx - 5, cy - 6, 2, cx, cy, 23);
        medalGrad.addColorStop(0, '#334155');
        medalGrad.addColorStop(1, '#090d16');
        ctx.fillStyle = medalGrad;
        ctx.beginPath();
        ctx.arc(cx, cy, 23, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = '#fde047';
        ctx.lineWidth = 3;
        ctx.stroke();

        ctx.font = '22px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('👑', cx, cy + 1);
    }

    function drawBoardBase() {
        ctx.fillStyle = '#faf6eb';
        ctx.fillRect(0, 0, BOARD_SIZE, BOARD_SIZE);

        // 52 Petak Jalur Utama 3D
        MAIN_TRACK.forEach((rc, idx) => {
            const x = rc[1] * CELL;
            const y = rc[0] * CELL;

            let bg = '#faf6eb';
            if (idx === 0) bg = COLOR_META.red.fill;
            else if (idx === 13) bg = COLOR_META.green.fill;
            else if (idx === 26) bg = COLOR_META.yellow.fill;
            else if (idx === 39) bg = COLOR_META.blue.fill;
            else if (SAFE_INDICES.has(idx)) bg = '#e2e8f0';

            draw3dBevelCell(x, y, CELL, CELL, bg);

            if (SAFE_INDICES.has(idx)) {
                const isColoredStart = idx === 0 || idx === 13 || idx === 26 || idx === 39;
                drawStar(x + CELL / 2, y + CELL / 2, CELL * 0.28, isColoredStart ? '#ffffff' : '#64748b');
            }
        });

        // 4 Jalur Home Stretch Berwarna 3D
        Object.values(COLOR_META).forEach(meta => {
            meta.homeLane.forEach(rc => {
                const x = rc[1] * CELL;
                const y = rc[0] * CELL;
                draw3dBevelCell(x, y, CELL, CELL, meta.fill);
            });
        });

        // 4 Kandang Home Yard
        drawYard(0, 0, 'red');
        drawYard(0, 9, 'green');
        drawYard(9, 9, 'yellow');
        drawYard(9, 0, 'blue');

        // Segitiga Mahkota Tengah
        drawCenterCrown();
    }

    function draw3dStandingPawn(cx, cy, r, meta, pawnIndex, isMovable, jumpHeight, now) {
        ctx.save();

        // Halo berkedip saat Human harus memilih pion
        if (isMovable) {
            const pulse = 1 + 0.14 * Math.sin(now * 0.01);
            ctx.strokeStyle = '#facc15';
            ctx.lineWidth = 3;
            ctx.beginPath();
            ctx.ellipse(cx, cy + 4, r * 1.35 * pulse, r * 0.72 * pulse, 0, 0, Math.PI * 2);
            ctx.stroke();
        }

        // Bayangan 3D pada lantai papan
        const shadowScale = Math.max(0.55, 1 - jumpHeight * 0.018);
        ctx.fillStyle = `rgba(2, 6, 23, ${0.38 * shadowScale})`;
        ctx.beginPath();
        ctx.ellipse(cx + 2 + jumpHeight * 0.16, cy + 5, r * 0.92 * shadowScale, r * 0.44 * shadowScale, 0, 0, Math.PI * 2);
        ctx.fill();

        const py = cy - jumpHeight;
        const baseRy = r * 0.42;

        // 1. Alas bawah 3D (tebal pedestal)
        ctx.fillStyle = meta.dark;
        ctx.beginPath();
        ctx.ellipse(cx, py + 5.5, r * 0.9, baseRy, 0, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = meta.fill;
        ctx.beginPath();
        ctx.ellipse(cx, py + 3, r * 0.9, baseRy, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.8)';
        ctx.lineWidth = 1.4;
        ctx.stroke();

        // 2. Badan kerucut 3D berdiri
        const neckY = py - r * 0.78;
        const bodyGrad = ctx.createLinearGradient(cx - r * 0.78, neckY, cx + r * 0.78, py + 3);
        bodyGrad.addColorStop(0, '#ffffff');
        bodyGrad.addColorStop(0.28, meta.fill);
        bodyGrad.addColorStop(0.75, meta.fill);
        bodyGrad.addColorStop(1, meta.dark);
        ctx.fillStyle = bodyGrad;
        ctx.beginPath();
        ctx.moveTo(cx - r * 0.72, py + 3);
        ctx.quadraticCurveTo(cx - r * 0.45, py - r * 0.35, cx - r * 0.34, neckY);
        ctx.lineTo(cx + r * 0.34, neckY);
        ctx.quadraticCurveTo(cx + r * 0.45, py - r * 0.35, cx + r * 0.72, py + 3);
        ctx.closePath();
        ctx.fill();
        ctx.strokeStyle = 'rgba(15, 23, 42, 0.38)';
        ctx.lineWidth = 1.1;
        ctx.stroke();

        // 3. Kerah leher emas/putih
        ctx.fillStyle = '#fef08a';
        ctx.beginPath();
        ctx.ellipse(cx, neckY, r * 0.42, r * 0.16, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = meta.dark;
        ctx.lineWidth = 1;
        ctx.stroke();

        // 4. Kepala bola 3D mengilap
        const headY = py - r * 1.18;
        const headR = r * 0.6;
        const headGrad = ctx.createRadialGradient(
            cx - headR * 0.32,
            headY - headR * 0.35,
            1.5,
            cx,
            headY,
            headR
        );
        headGrad.addColorStop(0, '#ffffff');
        headGrad.addColorStop(0.32, meta.fill);
        headGrad.addColorStop(1, meta.dark);
        ctx.fillStyle = headGrad;
        ctx.beginPath();
        ctx.arc(cx, headY, headR, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 1.6;
        ctx.stroke();

        // Nomor pion di atas kepala 3D
        ctx.fillStyle = '#ffffff';
        ctx.font = `900 ${Math.max(9, Math.round(headR * 1.15))}px Inter, sans-serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(String(pawnIndex + 1), cx, headY + 0.5);

        ctx.restore();
    }

    function drawPawns(now) {
        const buckets = {};
        state.players.forEach(p => {
            p.pawns.forEach((step, idx) => {
                const coord = getStepCoordinate(p.color, step, idx);
                const key = `${coord[0].toFixed(2)},${coord[1].toFixed(2)}`;
                if (!buckets[key]) buckets[key] = [];
                buckets[key].push({ p, step, idx, coord });
            });
        });

        const drawList = [];
        Object.values(buckets).forEach(group => {
            const n = group.length;
            group.forEach((item, gIdx) => {
                const { p, idx, coord } = item;
                let cx = coord[1] * CELL;
                let cy = coord[0] * CELL;
                let radius = CELL * 0.29;

                if (n > 1) {
                    radius = n >= 3 ? CELL * 0.22 : CELL * 0.24;
                    const angle = (Math.PI * 2 * gIdx) / n;
                    const spread = n >= 3 ? 9 : 7.5;
                    cx += Math.cos(angle) * spread;
                    cy += Math.sin(angle) * spread;
                }

                const isMovable = state.phase === 'choose_pawn' && p.id === state.currentTurn && state.movablePawns.includes(idx);
                const mp = state.movingPawn;
                const isMoving = mp && mp.playerIdx === p.id && mp.pawnIdx === idx;
                let jumpHeight = isMovable ? Math.abs(Math.sin(now * 0.006)) * 5 : 0;

                if (isMoving) {
                    const t = Math.min(1, Math.max(0, (now - mp.stepStartedAt) / Math.max(1, mp.stepDuration)));
                    cx = (mp.fromCoord[1] + (mp.toCoord[1] - mp.fromCoord[1]) * t) * CELL;
                    cy = (mp.fromCoord[0] + (mp.toCoord[0] - mp.fromCoord[0]) * t) * CELL;
                    jumpHeight = Math.sin(t * Math.PI) * 20;
                    radius *= 1.12;
                }

                drawList.push({
                    cx,
                    cy,
                    radius,
                    meta: COLOR_META[p.color],
                    pawnIndex: idx,
                    isMovable,
                    jumpHeight,
                    sortZ: isMoving ? 9999 : cy,
                });
            });
        });

        // Urutkan belakang-ke-depan agar overlap bidak 3D terlihat natural
        drawList.sort((a, b) => a.sortZ - b.sortZ);
        drawList.forEach(item => {
            draw3dStandingPawn(
                item.cx,
                item.cy,
                item.radius,
                item.meta,
                item.pawnIndex,
                item.isMovable,
                item.jumpHeight,
                now
            );
        });
    }

    function drawCanvas(now) {
        if (!ctx) return;
        ctx.save();
        if (state.shakeTimer > 0) {
            const mag = state.shakeTimer * 24;
            ctx.translate((Math.random() - 0.5) * mag, (Math.random() - 0.5) * mag);
            state.shakeTimer = Math.max(0, state.shakeTimer - 0.016);
        }

        drawBoardBase();
        drawPawns(now);

        // Particles
        state.particles.forEach(pt => {
            pt.x += pt.vx * 0.016;
            pt.y += pt.vy * 0.016;
            pt.life -= 0.016;
            ctx.globalAlpha = Math.max(0, pt.life / pt.maxLife);
            ctx.fillStyle = pt.color;
            ctx.beginPath();
            ctx.arc(pt.x, pt.y, pt.r, 0, Math.PI * 2);
            ctx.fill();
        });
        state.particles = state.particles.filter(pt => pt.life > 0);
        ctx.globalAlpha = 1;

        ctx.restore();
    }

    function loop(now) {
        drawCanvas(now);
        rafId = requestAnimationFrame(loop);
    }

    // --- DOM UI Renderers ---
    function renderQuickBar() {
        const bar = document.getElementById('ludoQuickBar');
        const turnBadge = document.getElementById('ludoTurnBadge');
        if (!bar) return;

        const active = state.players[state.currentTurn];
        const meta = active ? COLOR_META[active.color] : COLOR_META.blue;

        if (turnBadge) {
            turnBadge.textContent = state.phase === 'finished'
                ? 'Selesai'
                : (active && active.isHuman ? 'Giliran Anda!' : `Giliran ${active ? active.name : ''}`);
        }

        let controlsHtml = '';
        if (state.phase === 'finished') {
            controlsHtml = `<button type="button" id="btnLudoShowWin" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-extrabold cursor-pointer">Lihat Pemenang</button>`;
        } else if (state.rollingAnim) {
            controlsHtml = `<span class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 text-xs font-bold animate-pulse">Mengocok dadu...</span>`;
        } else if (state.phase === 'moving') {
            controlsHtml = `<span class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-xs font-bold">Pion melangkah...</span>`;
        } else if (active && active.isHuman) {
            if (state.phase === 'need_roll') {
                controlsHtml = `
                    <button type="button" id="btnLudoRollDice" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-sm cursor-pointer">
                        🎲 Lempar Dadu${state.sixStreak > 0 ? ' (Bonus!)' : ''}
                    </button>
                `;
            } else if (state.phase === 'choose_pawn') {
                controlsHtml = `
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400">Pilih Pion:</span>
                        ${state.movablePawns.map(idx => `<button type="button" data-pick-pawn="${idx}" class="px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-extrabold shadow-xs cursor-pointer">Pion #${idx + 1}</button>`).join('')}
                    </div>
                `;
            }
        } else {
            controlsHtml = `<span class="text-xs font-bold text-slate-500 dark:text-slate-400 animate-pulse">${escapeHtml(active ? active.name : 'AI')} sedang bermain...</span>`;
        }

        const dieFaces = ['⚀', '⚁', '⚂', '⚃', '⚄', '⚅'];
        const dieChar = dieFaces[Math.max(0, Math.min(5, state.dice - 1))];

        bar.innerHTML = `
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm font-black shadow-xs shrink-0" style="background-color:${meta.fill}">
                    ${active && active.isHuman ? '👑' : '🤖'}
                </span>
                <div class="min-w-0">
                    <div class="text-xs font-extrabold text-slate-800 dark:text-slate-100 truncate">
                        ${active ? escapeHtml(active.name) : ''}
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                        Angka Dadu: <strong class="font-mono text-indigo-600 dark:text-indigo-400">${state.dice}</strong>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-3xl leading-none select-none ${state.rollingAnim ? 'ludo-die-rolling text-amber-500' : 'text-slate-800 dark:text-white'}" title="Dadu saat ini">${dieChar}</span>
                ${controlsHtml}
            </div>
        `;

        document.getElementById('btnLudoRollDice')?.addEventListener('click', (e) => {
            e.currentTarget.blur();
            rollDice();
        });
        document.getElementById('btnLudoShowWin')?.addEventListener('click', showWinModal);
        bar.querySelectorAll('[data-pick-pawn]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (state.phase === 'choose_pawn' && state.currentTurn === 0) {
                    executePawnMove(0, Number(btn.getAttribute('data-pick-pawn')), state.dice);
                }
            });
        });
    }

    function renderPlayersPanel() {
        const panel = document.getElementById('ludoPlayersPanel');
        if (!panel) return;
        const goal = getTargetFinishCount();

        panel.innerHTML = state.players.map(p => {
            const meta = COLOR_META[p.color];
            const isCur = state.phase !== 'finished' && state.currentTurn === p.id;
            const atHome = p.pawns.filter(s => s === -1).length;
            const atCrown = p.pawns.filter(s => s === 56).length;
            const onTrack = 4 - atHome - atCrown;

            return `
                <div class="rounded-xl p-2.5 border transition-all ${isCur ? 'bg-indigo-50/70 dark:bg-indigo-950/50 border-indigo-400 ring-1 ring-indigo-400/50' : 'bg-slate-50/70 dark:bg-slate-800/50 border-slate-200/80 dark:border-slate-700/70'}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-6 h-6 rounded-full shrink-0 border-2 border-white shadow-xs" style="background-color:${meta.fill}"></span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate">${escapeHtml(p.name)}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Kandang: ${atHome} &middot; Jalur: ${onTrack}</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono font-extrabold text-amber-600 dark:text-amber-400">👑 ${atCrown}/${goal}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderStatsPanel() {
        const statsWrap = document.getElementById('ludoStatsPanel');
        const logWrap = document.getElementById('ludoActivityLog');
        if (statsWrap) {
            const levels = [
                { key: 'easy', label: 'Santai' },
                { key: 'normal', label: 'Normal' },
                { key: 'hard', label: 'Sulit' },
            ];
            statsWrap.innerHTML = levels.map(lv => {
                const s = stats[lv.key] || { wins: 0, losses: 0 };
                return `
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/60 p-2">
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400">${lv.label}</div>
                        <div class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">${s.wins}M - <span class="text-rose-500">${s.losses}K</span></div>
                    </div>
                `;
            }).join('');
        }

        if (logWrap) {
            if (state.logs.length === 0) {
                logWrap.innerHTML = '<div class="text-center text-slate-400 py-2">Belum ada aktivitas.</div>';
            } else {
                logWrap.innerHTML = state.logs.slice(0, 20).map(l => {
                    const cls = l.kind === 'capture' ? 'text-rose-600 dark:text-rose-400 font-bold'
                        : (l.kind === 'crown' ? 'text-emerald-600 dark:text-emerald-400 font-bold'
                        : (l.kind === 'warn' ? 'text-amber-600 dark:text-amber-400 font-semibold' : ''));
                    return `<div class="${cls} border-b border-slate-200/50 dark:border-slate-700/40 pb-1 last:border-0">${escapeHtml(l.text)}</div>`;
                }).join('');
            }
        }
    }

    function applyBoardVisuals() {
        const card = document.getElementById('ludoBoardCard');
        const stage = document.getElementById('ludoBoardStage');
        const surface = document.getElementById('ludoBoard3dSurface');
        const camLabel = document.getElementById('ludoCameraLabel');
        const zoomReset = document.getElementById('btnLudoZoomReset');

        const cam = CAMERA_LABELS[prefs.camera] ? prefs.camera : '3d';
        const felt = ['royal', 'emerald', 'crimson'].includes(prefs.felt) ? prefs.felt : 'royal';
        const zoom = Math.min(1.2, Math.max(0.8, Number(prefs.zoom) || 1));

        if (card) card.setAttribute('data-camera', cam);
        if (stage) stage.setAttribute('data-felt', felt);
        if (surface) surface.style.setProperty('--ludo-zoom', String(zoom));
        if (camLabel) camLabel.textContent = CAMERA_LABELS[cam];
        if (zoomReset) zoomReset.textContent = `${Math.round(zoom * 100)}%`;

        document.querySelectorAll('[data-ludo-felt]').forEach(btn => {
            const isSel = btn.getAttribute('data-ludo-felt') === felt;
            btn.classList.toggle('ring-2', isSel);
            btn.classList.toggle('ring-amber-400', isSel);
            btn.classList.toggle('scale-110', isSel);
        });
    }

    function renderCenterDiceOverlay() {
        const overlay = document.getElementById('ludoCenterDiceOverlay');
        const card = document.getElementById('ludoCenterDiceCard');
        const turnChip = document.getElementById('ludoCenterDiceTurnChip');
        const badge = document.getElementById('ludoCenterDiceBadge');
        const mover = document.getElementById('ludoCenter3dMover');
        const shadow = document.getElementById('ludoCenter3dShadow');
        const dieEl = document.getElementById('ludoCenter3dDie');
        if (!overlay || !card || !mover || !dieEl) return;

        let mode = 'hidden';
        if (state.rollingAnim) mode = 'rolling';
        else if (state.diceJustLanded) mode = 'landed';
        else if (state.phase === 'need_roll') mode = 'idle';

        overlay.classList.toggle('is-hidden', mode === 'hidden');
        if (mode === 'hidden') {
            card.classList.remove('is-clickable');
            lastOverlayMode = 'hidden';
            return;
        }

        const active = state.players[state.currentTurn];
        const meta = active ? COLOR_META[active.color] : COLOR_META.blue;
        const canHumanRoll = mode === 'idle' && Boolean(active && active.isHuman);

        card.style.setProperty('--ludo-player-glow', `${meta.fill}88`);
        card.classList.toggle('is-clickable', canHumanRoll);

        if (turnChip) {
            turnChip.textContent = active && active.isHuman ? 'Giliran Anda' : (active ? active.name : 'AI');
            turnChip.style.backgroundColor = meta.fill;
        }

        if (badge) {
            if (mode === 'rolling') {
                badge.textContent = `🎲 ${active ? active.name : ''} Melempar...`;
                badge.className = 'px-3 py-1 rounded-full bg-indigo-500 text-white text-[11px] font-black tracking-wide shadow-sm';
            } else if (mode === 'landed') {
                badge.textContent = state.dice === 6
                    ? '🎲 ANGKA 6 — BONUS LEMPAR!'
                    : `🎲 HASIL DADU: ${state.dice}`;
                badge.className = `px-3 py-1 rounded-full ${state.dice === 6 ? 'bg-amber-400 text-slate-950' : 'bg-emerald-500 text-white'} text-[11px] font-black tracking-wide shadow-sm`;
            } else if (canHumanRoll) {
                badge.textContent = state.sixStreak > 0
                    ? '🎲 KLIK / SPASI (BONUS!)'
                    : '🎲 KLIK / SPASI LEMPAR DADU';
                badge.className = 'px-3 py-1 rounded-full bg-amber-400 text-slate-950 text-[11px] font-black tracking-wide shadow-sm';
            } else {
                badge.textContent = `🤖 ${active ? active.name : 'AI'} Bersiap...`;
                badge.className = 'px-3 py-1 rounded-full bg-slate-700 text-slate-100 text-[11px] font-bold tracking-wide shadow-sm';
            }
        }

        if (mode !== 'rolling') {
            dieEl.style.transform = `rotateX(${cubeSpin.x}deg) rotateY(${cubeSpin.y}deg)`;
        }

        if (mode !== lastOverlayMode) {
            mover.classList.remove('is-idle', 'is-rolling', 'is-landed');
            if (shadow) shadow.classList.remove('is-rolling');

            if (mode === 'rolling') {
                // Force reflow agar animasi lemparan 3D ke tengah selalu dimulai dari awal
                void mover.offsetWidth;
                mover.classList.add('is-rolling');
                if (shadow) shadow.classList.add('is-rolling');
            } else if (mode === 'landed') {
                void mover.offsetWidth;
                mover.classList.add('is-landed');
            } else {
                mover.classList.add('is-idle');
            }
            lastOverlayMode = mode;
        }
    }

    function renderUI() {
        renderQuickBar();
        renderCenterDiceOverlay();
        renderPlayersPanel();
        renderStatsPanel();
    }

    function syncControlsUI() {
        document.querySelectorAll('.ludo-opt-players').forEach(btn => {
            const active = Number(btn.getAttribute('data-ludo-players')) === prefs.playerCount;
            btn.className = 'ludo-opt-players px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer transition-colors ' +
                (active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700');
        });

        document.querySelectorAll('.ludo-opt-diff').forEach(btn => {
            const active = btn.getAttribute('data-ludo-diff') === prefs.difficulty;
            btn.className = 'ludo-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer transition-colors ' +
                (active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700');
        });

        const colSel = document.getElementById('ludoColorSelect');
        if (colSel) colSel.value = prefs.humanColor;
        const winSel = document.getElementById('ludoWinModeSelect');
        if (winSel) winSel.value = prefs.winMode;

        const muteIcon = document.getElementById('ludoMuteIcon');
        const muteLbl = document.getElementById('ludoMuteLabel');
        if (muteIcon) muteIcon.className = prefs.muted ? 'bi bi-volume-mute-fill text-rose-500' : 'bi bi-volume-up-fill text-emerald-500';
        if (muteLbl) muteLbl.textContent = prefs.muted ? 'Suara Mati' : 'Suara Aktif';
    }

    // --- Modals & Fullscreen ---
    function openModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('hidden');
        el.classList.add('flex');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    function showWinModal() {
        const body = document.getElementById('ludoWinBody');
        const winner = state.players[state.winnerIdx];
        if (!body || !winner) return;

        const meta = COLOR_META[winner.color];
        body.innerHTML = `
            <div class="text-center space-y-2">
                <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center text-2xl text-white border-4 border-white shadow-md" style="background-color:${meta.fill}">👑</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">${escapeHtml(winner.name)} Menang!</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Berhasil mengantarkan ${getTargetFinishCount()} pion ke Mahkota Tengah.</div>
            </div>
            <button type="button" id="btnLudoPlayAgain" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold cursor-pointer">
                Main Lagi Sekarang
            </button>
        `;
        openModal('ludoWinModal');
        document.getElementById('btnLudoPlayAgain')?.addEventListener('click', startNewMatch);
    }

    function toggleFullscreen() {
        const wrapper = document.getElementById('ludoFullscreenWrapper');
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

    // --- Canvas Click Hit Detection (3D Perspective Accurate) ---
    function handleCanvasPointer(e) {
        if (!canvas || state.currentTurn !== 0) return;

        let px;
        let py;
        if (typeof e.offsetX === 'number' && typeof e.offsetY === 'number' && canvas.clientWidth > 0 && canvas.clientHeight > 0) {
            px = (e.offsetX / canvas.clientWidth) * BOARD_SIZE;
            py = (e.offsetY / canvas.clientHeight) * BOARD_SIZE;
        } else {
            const rect = canvas.getBoundingClientRect();
            px = (e.clientX - rect.left) * (BOARD_SIZE / rect.width);
            py = (e.clientY - rect.top) * (BOARD_SIZE / rect.height);
        }

        if (state.phase === 'need_roll') {
            rollDice();
            return;
        }

        if (state.phase === 'choose_pawn') {
            const p = state.players[0];
            let picked = null;
            let bestDist = 34;

            state.movablePawns.forEach(idx => {
                const coord = getStepCoordinate(p.color, p.pawns[idx], idx);
                const tx = coord[1] * CELL;
                const ty = coord[0] * CELL - 4;
                const dist = Math.hypot(px - tx, py - ty);
                if (dist < bestDist) {
                    bestDist = dist;
                    picked = idx;
                }
            });

            if (picked !== null) {
                executePawnMove(0, picked, state.dice);
            }
        }
    }

    // --- Init & Event Bindings ---
    document.addEventListener('DOMContentLoaded', () => {
        canvas = document.getElementById('ludoCanvas');
        if (!canvas) return;
        ctx = canvas.getContext('2d');

        loadStorage();
        startNewMatch();

        canvas.addEventListener('pointerdown', (e) => {
            if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;
            handleCanvasPointer(e);
        });

        document.getElementById('ludoCenterDiceCard')?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (state.currentTurn === 0 && state.phase === 'need_roll') {
                rollDice();
            }
        });

        document.getElementById('btnLudoCycleCamera')?.addEventListener('click', (e) => {
            const idx = CAMERA_ORDER.indexOf(prefs.camera);
            prefs.camera = CAMERA_ORDER[(idx + 1) % CAMERA_ORDER.length];
            savePrefs();
            applyBoardVisuals();
            e.currentTarget.blur();
        });

        document.querySelectorAll('[data-ludo-felt]').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.felt = btn.getAttribute('data-ludo-felt') || 'royal';
                savePrefs();
                applyBoardVisuals();
                btn.blur();
            });
        });

        document.getElementById('btnLudoZoomOut')?.addEventListener('click', (e) => {
            prefs.zoom = Math.max(0.8, Number(((Number(prefs.zoom) || 1) - 0.1).toFixed(2)));
            savePrefs();
            applyBoardVisuals();
            e.currentTarget.blur();
        });
        document.getElementById('btnLudoZoomReset')?.addEventListener('click', (e) => {
            prefs.zoom = 1;
            savePrefs();
            applyBoardVisuals();
            e.currentTarget.blur();
        });
        document.getElementById('btnLudoZoomIn')?.addEventListener('click', (e) => {
            prefs.zoom = Math.min(1.2, Number(((Number(prefs.zoom) || 1) + 0.1).toFixed(2)));
            savePrefs();
            applyBoardVisuals();
            e.currentTarget.blur();
        });

        document.addEventListener('keydown', (e) => {
            if (e.code !== 'Space') return;
            const activeTag = document.activeElement ? document.activeElement.tagName.toUpperCase() : '';
            if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') return;
            const anyModal = ['ludoWinModal', 'ludoRulesModal'].some(id => {
                const m = document.getElementById(id);
                return m && !m.classList.contains('hidden');
            });
            if (anyModal) return;
            if (state.currentTurn === 0 && state.phase === 'need_roll') {
                e.preventDefault();
                if (activeTag === 'BUTTON' && document.activeElement) document.activeElement.blur();
                rollDice();
            }
        });

        document.querySelectorAll('.ludo-opt-players').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.playerCount = Number(btn.getAttribute('data-ludo-players'));
                savePrefs();
                startNewMatch();
                btn.blur();
            });
        });

        document.querySelectorAll('.ludo-opt-diff').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.difficulty = btn.getAttribute('data-ludo-diff');
                savePrefs();
                startNewMatch();
                btn.blur();
            });
        });

        document.getElementById('ludoColorSelect')?.addEventListener('change', (e) => {
            prefs.humanColor = e.target.value;
            savePrefs();
            startNewMatch();
            e.target.blur();
        });

        document.getElementById('ludoWinModeSelect')?.addEventListener('change', (e) => {
            prefs.winMode = e.target.value;
            savePrefs();
            startNewMatch();
            e.target.blur();
        });

        document.getElementById('btnLudoRestart')?.addEventListener('click', (e) => {
            startNewMatch();
            e.currentTarget.blur();
        });

        document.getElementById('btnLudoMute')?.addEventListener('click', (e) => {
            prefs.muted = !prefs.muted;
            savePrefs();
            syncControlsUI();
            e.currentTarget.blur();
        });

        document.getElementById('btnLudoFullscreen')?.addEventListener('click', (e) => {
            toggleFullscreen();
            e.currentTarget.blur();
        });
        document.getElementById('btnLudoOpenRules')?.addEventListener('click', () => openModal('ludoRulesModal'));
        document.getElementById('btnCloseLudoRules')?.addEventListener('click', () => closeModal('ludoRulesModal'));
        document.getElementById('btnCloseLudoWin')?.addEventListener('click', () => closeModal('ludoWinModal'));

        document.getElementById('btnLudoResetStats')?.addEventListener('click', () => {
            stats = { easy: { wins: 0, losses: 0 }, normal: { wins: 0, losses: 0 }, hard: { wins: 0, losses: 0 } };
            saveStats();
            renderStatsPanel();
        });

        rafId = requestAnimationFrame(loop);
    });
})();
