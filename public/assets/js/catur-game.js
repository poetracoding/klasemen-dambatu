(function () {
    'use strict';

    const PREFS_KEY = 'catur_3d_dambatu_prefs_v1';
    const STATS_KEY = 'catur_3d_dambatu_stats_v1';

    const BOARD_SIZE = 640;
    const SQ = BOARD_SIZE / 8; // 80px

    const DIFFICULTY_LABELS = {
        easy: 'Santai',
        normal: 'Normal',
        hard: 'Sulit',
    };

    const CAMERA_LABELS = {
        '3d': '3D Meja',
        cinematic: 'Sinematik',
        '2d': '2D Atas',
    };
    const CAMERA_ORDER = ['3d', 'cinematic', '2d'];

    const BOARD_THEMES = {
        wood: {
            light: '#f3d9b1',
            lightHi: 'rgba(255,255,255,0.45)',
            dark: '#9c5a2d',
            darkHi: 'rgba(255,235,205,0.16)',
            coordLight: '#7c3f16',
            coordDark: '#f5dec0',
        },
        royal: {
            light: '#e2e8f0',
            lightHi: 'rgba(255,255,255,0.65)',
            dark: '#3b4d7b',
            darkHi: 'rgba(199,210,254,0.2)',
            coordLight: '#1e293b',
            coordDark: '#e2e8f0',
        },
        emerald: {
            light: '#ecfdf5',
            lightHi: 'rgba(255,255,255,0.6)',
            dark: '#15803d',
            darkHi: 'rgba(187,247,208,0.22)',
            coordLight: '#065f46',
            coordDark: '#ecfdf5',
        },
    };

    const PIECE_VALUES = { p: 100, n: 320, b: 330, r: 500, q: 900, k: 20000 };
    const PIECE_UNICODE = {
        w: { k: '♔', q: '♕', r: '♖', b: '♗', n: '♘', p: '♙' },
        b: { k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟' },
    };
    const PIECE_SOLID_GLYPH = { k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟' };
    const PIECE_SHORT_LABEL = { k: 'RAJA', q: 'RATU', r: 'BTG', b: 'GJH', n: 'KUDA', p: 'PION' };
    const PIECE_SAN_LETTER = { p: '', n: 'N', b: 'B', r: 'R', q: 'Q', k: 'K' };
    const FILES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

    // Piece-Square Tables (dari sudut pandang Putih baris 0 = rank 8 .. baris 7 = rank 1)
    const PST = {
        p: [
            [0, 0, 0, 0, 0, 0, 0, 0],
            [50, 50, 50, 50, 50, 50, 50, 50],
            [12, 14, 20, 30, 30, 20, 14, 12],
            [5, 6, 12, 26, 26, 12, 6, 5],
            [0, 0, 4, 22, 22, 4, 0, 0],
            [5, -5, -8, 0, 0, -8, -5, 5],
            [5, 10, 10, -20, -20, 10, 10, 5],
            [0, 0, 0, 0, 0, 0, 0, 0],
        ],
        n: [
            [-50, -40, -30, -30, -30, -30, -40, -50],
            [-40, -20, 0, 5, 5, 0, -20, -40],
            [-30, 5, 12, 16, 16, 12, 5, -30],
            [-30, 2, 16, 22, 22, 16, 2, -30],
            [-30, 5, 16, 22, 22, 16, 5, -30],
            [-30, 2, 12, 15, 15, 12, 2, -30],
            [-40, -20, 0, 5, 5, 0, -20, -40],
            [-50, -35, -30, -30, -30, -30, -35, -50],
        ],
        b: [
            [-20, -10, -10, -10, -10, -10, -10, -20],
            [-10, 0, 0, 0, 0, 0, 0, -10],
            [-10, 0, 8, 12, 12, 8, 0, -10],
            [-10, 6, 6, 12, 12, 6, 6, -10],
            [-10, 0, 12, 12, 12, 12, 0, -10],
            [-10, 10, 10, 10, 10, 10, 10, -10],
            [-10, 6, 0, 0, 0, 0, 6, -10],
            [-20, -10, -10, -10, -10, -10, -10, -20],
        ],
        r: [
            [0, 0, 0, 4, 4, 0, 0, 0],
            [8, 14, 14, 14, 14, 14, 14, 8],
            [-5, 0, 0, 0, 0, 0, 0, -5],
            [-5, 0, 0, 0, 0, 0, 0, -5],
            [-5, 0, 0, 0, 0, 0, 0, -5],
            [-5, 0, 0, 0, 0, 0, 0, -5],
            [-5, 0, 0, 0, 0, 0, 0, -5],
            [0, 0, 2, 8, 8, 2, 0, 0],
        ],
        q: [
            [-20, -10, -10, -5, -5, -10, -10, -20],
            [-10, 0, 0, 0, 0, 0, 0, -10],
            [-10, 0, 6, 6, 6, 6, 0, -10],
            [-5, 0, 6, 6, 6, 6, 0, -5],
            [0, 0, 6, 6, 6, 6, 0, -5],
            [-10, 6, 6, 6, 6, 6, 0, -10],
            [-10, 0, 6, 0, 0, 0, 0, -10],
            [-20, -10, -10, -5, -5, -10, -10, -20],
        ],
        k: [
            [-30, -40, -40, -50, -50, -40, -40, -30],
            [-30, -40, -40, -50, -50, -40, -40, -30],
            [-30, -40, -40, -50, -50, -40, -40, -30],
            [-30, -40, -40, -50, -50, -40, -40, -30],
            [-20, -30, -30, -40, -40, -30, -30, -20],
            [-10, -20, -20, -20, -20, -20, -20, -10],
            [18, 18, 0, 0, 0, 0, 18, 18],
            [20, 32, 12, 0, 0, 12, 32, 20],
        ],
    };

    let prefs = {
        difficulty: 'normal',
        humanColor: 'w',
        timeLimitSec: 600,
        camera: '3d',
        theme: 'wood',
        zoom: 1,
        flipped: false,
        muted: false,
    };

    let stats = {
        easy: { wins: 0, draws: 0, losses: 0 },
        normal: { wins: 0, draws: 0, losses: 0 },
        hard: { wins: 0, draws: 0, losses: 0 },
    };

    let state = {
        pos: null,
        snapshots: [], // Riwayat untuk Undo & Notasi
        moveHistory: [], // { san, color, from, to, capturedPiece }
        capturedByWhite: [], // bidak hitam yg dimakan Putih
        capturedByBlack: [], // bidak putih yg dimakan Hitam
        selectedSq: null, // { r, c }
        legalTargets: [], // moves starting from selectedSq
        lastMove: null, // { from: {r,c}, to: {r,c} }
        hintMove: null, // { from: {r,c}, to: {r,c} }
        pendingPromo: null, // { baseMove }
        animMove: null, // { piece, from, to, startedAt, duration }
        particles: [],
        aiThinking: false,
        clocks: { w: 600, b: 600 },
        lastClockTick: 0,
        result: null, // { winner: 'w' | 'b' | 'draw', reason: string }
        timers: [],
    };

    let canvas = null;
    let ctx = null;
    let rafId = null;

    // --- Sound Synthesis (Web Audio API) ---
    let audioCtx = null;
    function getAudioCtx() {
        if (prefs.muted || typeof window === 'undefined') return null;
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

    function sfxWoodMove() {
        playTone(190, 0.06, 'triangle', 0.11, 0, 110);
        playTone(320, 0.04, 'sine', 0.05, 0.015, 180);
    }

    function sfxCapture() {
        playTone(260, 0.07, 'sawtooth', 0.08, 0, 95);
        playTone(420, 0.09, 'triangle', 0.09, 0.03, 150);
    }

    function sfxCheck() {
        playTone(587, 0.09, 'triangle', 0.09, 0);
        playTone(784, 0.14, 'sine', 0.08, 0.07);
    }

    function sfxGameEnd(didWin) {
        if (didWin) {
            [523, 659, 784, 1046].forEach((f, i) => playTone(f, 0.15, 'sine', 0.09, i * 0.09));
        } else {
            [392, 349, 311, 261].forEach((f, i) => playTone(f, 0.15, 'triangle', 0.08, i * 0.09));
        }
    }

    // --- Core Chess Engine (Pure FIDE Rules) ---
    function createInitialPos() {
        const board = Array.from({ length: 8 }, () => Array(8).fill(null));
        const back = ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'];
        for (let c = 0; c < 8; c++) {
            board[0][c] = { color: 'b', type: back[c] };
            board[1][c] = { color: 'b', type: 'p' };
            board[6][c] = { color: 'w', type: 'p' };
            board[7][c] = { color: 'w', type: back[c] };
        }
        const pos = {
            board,
            turn: 'w',
            castling: { wK: true, wQ: true, bK: true, bQ: true },
            enPassant: null,
            halfMoveClock: 0,
            positionCounts: {},
        };
        const key = serializePosKey(pos);
        pos.positionCounts[key] = 1;
        return pos;
    }

    function cloneBoard(board) {
        return board.map(row => row.map(sq => (sq ? { color: sq.color, type: sq.type } : null)));
    }

    function serializePosKey(pos) {
        let s = '';
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = pos.board[r][c];
                s += p ? (p.color === 'w' ? p.type.toUpperCase() : p.type) : '.';
            }
        }
        const cr = (pos.castling.wK ? 'K' : '') + (pos.castling.wQ ? 'Q' : '') + (pos.castling.bK ? 'k' : '') + (pos.castling.bQ ? 'q' : '');
        const ep = pos.enPassant ? `${pos.enPassant.r}${pos.enPassant.c}` : '-';
        return `${s}|${pos.turn}|${cr}|${ep}`;
    }

    function inBounds(r, c) {
        return r >= 0 && r < 8 && c >= 0 && c < 8;
    }

    function findKing(board, color) {
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = board[r][c];
                if (p && p.color === color && p.type === 'k') return { r, c };
            }
        }
        return null;
    }

    function isSquareAttacked(board, r, c, attackerColor) {
        // Serangan Pion
        const pawnRow = attackerColor === 'w' ? r + 1 : r - 1;
        if (inBounds(pawnRow, c - 1)) {
            const p = board[pawnRow][c - 1];
            if (p && p.color === attackerColor && p.type === 'p') return true;
        }
        if (inBounds(pawnRow, c + 1)) {
            const p = board[pawnRow][c + 1];
            if (p && p.color === attackerColor && p.type === 'p') return true;
        }

        // Serangan Kuda
        const knightDeltas = [[-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1]];
        for (let i = 0; i < knightDeltas.length; i++) {
            const nr = r + knightDeltas[i][0];
            const nc = c + knightDeltas[i][1];
            if (inBounds(nr, nc)) {
                const p = board[nr][nc];
                if (p && p.color === attackerColor && p.type === 'n') return true;
            }
        }

        // Serangan Raja
        for (let dr = -1; dr <= 1; dr++) {
            for (let dc = -1; dc <= 1; dc++) {
                if (dr === 0 && dc === 0) continue;
                const kr = r + dr;
                const kc = c + dc;
                if (inBounds(kr, kc)) {
                    const p = board[kr][kc];
                    if (p && p.color === attackerColor && p.type === 'k') return true;
                }
            }
        }

        // Garis Lurus (Benteng & Menteri)
        const ortho = [[-1, 0], [1, 0], [0, -1], [0, 1]];
        for (let i = 0; i < ortho.length; i++) {
            let sr = r + ortho[i][0];
            let sc = c + ortho[i][1];
            while (inBounds(sr, sc)) {
                const p = board[sr][sc];
                if (p) {
                    if (p.color === attackerColor && (p.type === 'r' || p.type === 'q')) return true;
                    break;
                }
                sr += ortho[i][0];
                sc += ortho[i][1];
            }
        }

        // Garis Diagonal (Gajah & Menteri)
        const diag = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
        for (let i = 0; i < diag.length; i++) {
            let sr = r + diag[i][0];
            let sc = c + diag[i][1];
            while (inBounds(sr, sc)) {
                const p = board[sr][sc];
                if (p) {
                    if (p.color === attackerColor && (p.type === 'b' || p.type === 'q')) return true;
                    break;
                }
                sr += diag[i][0];
                sc += diag[i][1];
            }
        }

        return false;
    }

    function isKingInCheck(board, color) {
        const k = findKing(board, color);
        if (!k) return false;
        const enemy = color === 'w' ? 'b' : 'w';
        return isSquareAttacked(board, k.r, k.c, enemy);
    }

    function applyMoveToPos(pos, move, trackRepetition = false) {
        const board = cloneBoard(pos.board);
        const castling = { ...pos.castling };
        const moving = board[move.from.r][move.from.c];
        const enemy = moving.color === 'w' ? 'b' : 'w';
        let captured = board[move.to.r][move.to.c];
        let nextEp = null;

        board[move.from.r][move.from.c] = null;

        if (move.isEnPassant) {
            const capRow = moving.color === 'w' ? move.to.r + 1 : move.to.r - 1;
            captured = board[capRow][move.to.c];
            board[capRow][move.to.c] = null;
        }

        if (move.promotion) {
            board[move.to.r][move.to.c] = { color: moving.color, type: move.promotion };
        } else {
            board[move.to.r][move.to.c] = { color: moving.color, type: moving.type };
        }

        // Rokade: pindahkan Benteng
        if (move.castle === 'K') {
            const rook = board[move.from.r][7];
            board[move.from.r][7] = null;
            board[move.from.r][5] = rook;
        } else if (move.castle === 'Q') {
            const rook = board[move.from.r][0];
            board[move.from.r][0] = null;
            board[move.from.r][3] = rook;
        }

        // En Passant target jika pion maju 2 langkah
        if (moving.type === 'p' && Math.abs(move.to.r - move.from.r) === 2) {
            nextEp = { r: (move.from.r + move.to.r) / 2, c: move.from.c };
        }

        // Cabut hak rokade bila Raja atau Benteng bergerak/termakan
        if (moving.type === 'k') {
            castling[`${moving.color}K`] = false;
            castling[`${moving.color}Q`] = false;
        }
        if (moving.type === 'r') {
            if (move.from.r === 7 && move.from.c === 7) castling.wK = false;
            if (move.from.r === 7 && move.from.c === 0) castling.wQ = false;
            if (move.from.r === 0 && move.from.c === 7) castling.bK = false;
            if (move.from.r === 0 && move.from.c === 0) castling.bQ = false;
        }
        if (captured && captured.type === 'r') {
            if (move.to.r === 7 && move.to.c === 7) castling.wK = false;
            if (move.to.r === 7 && move.to.c === 0) castling.wQ = false;
            if (move.to.r === 0 && move.to.c === 7) castling.bK = false;
            if (move.to.r === 0 && move.to.c === 0) castling.bQ = false;
        }

        const halfMoveClock = (moving.type === 'p' || captured) ? 0 : (pos.halfMoveClock + 1);
        const nextPos = {
            board,
            turn: enemy,
            castling,
            enPassant: nextEp,
            halfMoveClock,
            positionCounts: pos.positionCounts,
        };

        if (trackRepetition) {
            const counts = { ...pos.positionCounts };
            const key = serializePosKey(nextPos);
            counts[key] = (counts[key] || 0) + 1;
            nextPos.positionCounts = counts;
        }

        return { nextPos, captured };
    }

    function generatePseudoMovesForSquare(pos, r, c) {
        const board = pos.board;
        const piece = board[r][c];
        if (!piece) return [];

        const color = piece.color;
        const enemy = color === 'w' ? 'b' : 'w';
        const moves = [];

        if (piece.type === 'p') {
            const dir = color === 'w' ? -1 : 1;
            const startRow = color === 'w' ? 6 : 1;
            const promoRow = color === 'w' ? 0 : 7;

            const nr = r + dir;
            if (inBounds(nr, c) && !board[nr][c]) {
                if (nr === promoRow) {
                    ['q', 'r', 'b', 'n'].forEach(promo => {
                        moves.push({ from: { r, c }, to: { r: nr, c }, promotion: promo });
                    });
                } else {
                    moves.push({ from: { r, c }, to: { r: nr, c } });
                    const nr2 = r + dir * 2;
                    if (r === startRow && inBounds(nr2, c) && !board[nr2][c]) {
                        moves.push({ from: { r, c }, to: { r: nr2, c } });
                    }
                }
            }

            [-1, 1].forEach(dc => {
                const tc = c + dc;
                if (!inBounds(nr, tc)) return;
                const target = board[nr][tc];
                if (target && target.color === enemy) {
                    if (nr === promoRow) {
                        ['q', 'r', 'b', 'n'].forEach(promo => {
                            moves.push({ from: { r, c }, to: { r: nr, c: tc }, promotion: promo, isCapture: true });
                        });
                    } else {
                        moves.push({ from: { r, c }, to: { r: nr, c: tc }, isCapture: true });
                    }
                } else if (pos.enPassant && pos.enPassant.r === nr && pos.enPassant.c === tc) {
                    moves.push({ from: { r, c }, to: { r: nr, c: tc }, isCapture: true, isEnPassant: true });
                }
            });
        } else if (piece.type === 'n') {
            [[-2, -1], [-2, 1], [-1, -2], [-1, 2], [1, -2], [1, 2], [2, -1], [2, 1]].forEach(d => {
                const nr = r + d[0];
                const nc = c + d[1];
                if (!inBounds(nr, nc)) return;
                const t = board[nr][nc];
                if (!t || t.color === enemy) {
                    moves.push({ from: { r, c }, to: { r: nr, c: nc }, isCapture: Boolean(t) });
                }
            });
        } else if (piece.type === 'b' || piece.type === 'r' || piece.type === 'q') {
            const dirs = piece.type === 'b'
                ? [[-1, -1], [-1, 1], [1, -1], [1, 1]]
                : (piece.type === 'r'
                    ? [[-1, 0], [1, 0], [0, -1], [0, 1]]
                    : [[-1, -1], [-1, 1], [1, -1], [1, 1], [-1, 0], [1, 0], [0, -1], [0, 1]]);
            dirs.forEach(d => {
                let nr = r + d[0];
                let nc = c + d[1];
                while (inBounds(nr, nc)) {
                    const t = board[nr][nc];
                    if (!t) {
                        moves.push({ from: { r, c }, to: { r: nr, c: nc } });
                    } else {
                        if (t.color === enemy) {
                            moves.push({ from: { r, c }, to: { r: nr, c: nc }, isCapture: true });
                        }
                        break;
                    }
                    nr += d[0];
                    nc += d[1];
                }
            });
        } else if (piece.type === 'k') {
            for (let dr = -1; dr <= 1; dr++) {
                for (let dc = -1; dc <= 1; dc++) {
                    if (dr === 0 && dc === 0) continue;
                    const nr = r + dr;
                    const nc = c + dc;
                    if (!inBounds(nr, nc)) continue;
                    const t = board[nr][nc];
                    if (!t || t.color === enemy) {
                        moves.push({ from: { r, c }, to: { r: nr, c: nc }, isCapture: Boolean(t) });
                    }
                }
            }

            // Rokade
            const backRow = color === 'w' ? 7 : 0;
            if (r === backRow && c === 4 && !isKingInCheck(board, color)) {
                if (pos.castling[`${color}K`] &&
                    board[backRow][7]?.type === 'r' &&
                    board[backRow][7]?.color === color &&
                    !board[backRow][5] && !board[backRow][6] &&
                    !isSquareAttacked(board, backRow, 5, enemy) &&
                    !isSquareAttacked(board, backRow, 6, enemy)) {
                    moves.push({ from: { r, c }, to: { r: backRow, c: 6 }, castle: 'K' });
                }
                if (pos.castling[`${color}Q`] &&
                    board[backRow][0]?.type === 'r' &&
                    board[backRow][0]?.color === color &&
                    !board[backRow][1] && !board[backRow][2] && !board[backRow][3] &&
                    !isSquareAttacked(board, backRow, 3, enemy) &&
                    !isSquareAttacked(board, backRow, 2, enemy)) {
                    moves.push({ from: { r, c }, to: { r: backRow, c: 2 }, castle: 'Q' });
                }
            }
        }

        return moves;
    }

    function getAllLegalMoves(pos, color = pos.turn) {
        const legal = [];
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = pos.board[r][c];
                if (!p || p.color !== color) continue;
                const pseudo = generatePseudoMovesForSquare(pos, r, c);
                for (let i = 0; i < pseudo.length; i++) {
                    const { nextPos } = applyMoveToPos(pos, pseudo[i], false);
                    if (!isKingInCheck(nextPos.board, color)) {
                        legal.push(pseudo[i]);
                    }
                }
            }
        }
        return legal;
    }

    function isInsufficientMaterial(board) {
        const minorOrMajor = { w: [], b: [] };
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = board[r][c];
                if (!p || p.type === 'k') continue;
                if (p.type === 'p' || p.type === 'r' || p.type === 'q') return false;
                minorOrMajor[p.color].push({ type: p.type, sqColor: (r + c) % 2 });
            }
        }
        const wc = minorOrMajor.w.length;
        const bc = minorOrMajor.b.length;
        if (wc === 0 && bc === 0) return true;
        if ((wc === 1 && bc === 0) || (wc === 0 && bc === 1)) return true;
        if (wc === 1 && bc === 1 && minorOrMajor.w[0].type === 'b' && minorOrMajor.b[0].type === 'b') {
            return minorOrMajor.w[0].sqColor === minorOrMajor.b[0].sqColor;
        }
        return false;
    }

    function evaluateOutcome(pos) {
        const legal = getAllLegalMoves(pos, pos.turn);
        if (legal.length === 0) {
            if (isKingInCheck(pos.board, pos.turn)) {
                return {
                    winner: pos.turn === 'w' ? 'b' : 'w',
                    reason: 'Skakmat!',
                };
            }
            return { winner: 'draw', reason: 'Remis (Stalemate — tidak ada langkah legal).' };
        }
        if (isInsufficientMaterial(pos.board)) {
            return { winner: 'draw', reason: 'Remis (Bidak tidak cukup untuk skakmat).' };
        }
        if (pos.halfMoveClock >= 100) {
            return { winner: 'draw', reason: 'Remis (Aturan 50 langkah tanpa makan/gerak pion).' };
        }
        const key = serializePosKey(pos);
        if ((pos.positionCounts[key] || 0) >= 3) {
            return { winner: 'draw', reason: 'Remis (Pengulangan posisi 3 kali).' };
        }
        return null;
    }

    function buildSanNotation(posBefore, move, nextPos) {
        if (move.castle === 'K') return addCheckSuffix(nextPos, 'O-O');
        if (move.castle === 'Q') return addCheckSuffix(nextPos, 'O-O-O');

        const moving = posBefore.board[move.from.r][move.from.c];
        const isCap = Boolean(move.isCapture || posBefore.board[move.to.r][move.to.c]);
        const targetSq = `${FILES[move.to.c]}${8 - move.to.r}`;
        let base = '';

        if (moving.type === 'p') {
            base = isCap ? `${FILES[move.from.c]}x${targetSq}` : targetSq;
        } else {
            // Disambiguasi bila 2 bidak sejenis bisa ke petak yang sama
            const peers = getAllLegalMoves(posBefore, moving.color).filter(m => {
                if (m.from.r === move.from.r && m.from.c === move.from.c) return false;
                if (m.to.r !== move.to.r || m.to.c !== move.to.c) return false;
                const p = posBefore.board[m.from.r][m.from.c];
                return p && p.type === moving.type;
            });
            let dis = '';
            if (peers.length > 0) {
                const sameFile = peers.some(m => m.from.c === move.from.c);
                const sameRank = peers.some(m => m.from.r === move.from.r);
                if (!sameFile) dis = FILES[move.from.c];
                else if (!sameRank) dis = String(8 - move.from.r);
                else dis = `${FILES[move.from.c]}${8 - move.from.r}`;
            }
            base = `${PIECE_SAN_LETTER[moving.type]}${dis}${isCap ? 'x' : ''}${targetSq}`;
        }

        if (move.promotion) {
            base += `=${PIECE_SAN_LETTER[move.promotion]}`;
        }
        return addCheckSuffix(nextPos, base);
    }

    function addCheckSuffix(nextPos, san) {
        if (!isKingInCheck(nextPos.board, nextPos.turn)) return san;
        const legalNext = getAllLegalMoves(nextPos, nextPos.turn);
        return legalNext.length === 0 ? `${san}#` : `${san}+`;
    }

    // --- 3-Level AI Search Engine (Minimax + Alpha-Beta + Quiescence) ---
    function evaluateBoardWhitePerspective(board) {
        let score = 0;
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = board[r][c];
                if (!p) continue;
                const baseVal = PIECE_VALUES[p.type] || 0;
                const pstRow = p.color === 'w' ? r : 7 - r;
                const posVal = PST[p.type] ? PST[p.type][pstRow][c] : 0;
                const total = baseVal + posVal;
                score += p.color === 'w' ? total : -total;
            }
        }
        return score;
    }

    function scoreMoveForOrdering(board, move) {
        let s = 0;
        const victim = board[move.to.r][move.to.c];
        const attacker = board[move.from.r][move.from.c];
        if (victim) {
            s += 10 * (PIECE_VALUES[victim.type] || 100) - (PIECE_VALUES[attacker.type] || 100);
        } else if (move.isEnPassant) {
            s += 900;
        }
        if (move.promotion) s += 850 + (PIECE_VALUES[move.promotion] || 0);
        if (move.castle) s += 65;
        // Dorongan kendali pusat
        if (move.to.r >= 2 && move.to.r <= 5 && move.to.c >= 2 && move.to.c <= 5) s += 12;
        return s;
    }

    function orderMoves(board, moves) {
        return moves.slice().sort((a, b) => scoreMoveForOrdering(board, b) - scoreMoveForOrdering(board, a));
    }

    function getLegalCapturesAndPromos(pos, color) {
        const legal = [];
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const p = pos.board[r][c];
                if (!p || p.color !== color) continue;
                const pseudo = generatePseudoMovesForSquare(pos, r, c);
                for (let i = 0; i < pseudo.length; i++) {
                    const m = pseudo[i];
                    if (!m.isCapture && !m.promotion) continue;
                    const { nextPos } = applyMoveToPos(pos, m, false);
                    if (!isKingInCheck(nextPos.board, color)) {
                        legal.push(m);
                    }
                }
            }
        }
        return legal;
    }

    function quiescence(pos, alpha, beta, maximizingWhite, qDepth) {
        const standPat = evaluateBoardWhitePerspective(pos.board);
        if (qDepth <= 0) return standPat;

        if (maximizingWhite) {
            if (standPat >= beta) return beta;
            if (alpha < standPat) alpha = standPat;

            const captures = orderMoves(pos.board, getLegalCapturesAndPromos(pos, 'w'));
            for (let i = 0; i < captures.length; i++) {
                const { nextPos } = applyMoveToPos(pos, captures[i], false);
                const val = quiescence(nextPos, alpha, beta, false, qDepth - 1);
                if (val >= beta) return beta;
                if (val > alpha) alpha = val;
            }
            return alpha;
        }

        if (standPat <= alpha) return alpha;
        if (beta > standPat) beta = standPat;

        const captures = orderMoves(pos.board, getLegalCapturesAndPromos(pos, 'b'));
        for (let i = 0; i < captures.length; i++) {
            const { nextPos } = applyMoveToPos(pos, captures[i], false);
            const val = quiescence(nextPos, alpha, beta, true, qDepth - 1);
            if (val <= alpha) return alpha;
            if (val < beta) beta = val;
        }
        return beta;
    }

    function alphaBeta(pos, depth, alpha, beta, maximizingWhite, useQuiescence) {
        if (depth === 0) {
            return useQuiescence
                ? quiescence(pos, alpha, beta, maximizingWhite, 2)
                : evaluateBoardWhitePerspective(pos.board);
        }

        const legal = getAllLegalMoves(pos, pos.turn);
        if (legal.length === 0) {
            if (isKingInCheck(pos.board, pos.turn)) {
                return pos.turn === 'w' ? (-90000 - depth) : (90000 + depth);
            }
            return 0;
        }

        const ordered = orderMoves(pos.board, legal);
        if (maximizingWhite) {
            let best = -Infinity;
            for (let i = 0; i < ordered.length; i++) {
                const { nextPos } = applyMoveToPos(pos, ordered[i], false);
                const val = alphaBeta(nextPos, depth - 1, alpha, beta, false, useQuiescence);
                if (val > best) best = val;
                if (val > alpha) alpha = val;
                if (beta <= alpha) break;
            }
            return best;
        }

        let best = Infinity;
        for (let i = 0; i < ordered.length; i++) {
            const { nextPos } = applyMoveToPos(pos, ordered[i], false);
            const val = alphaBeta(nextPos, depth - 1, alpha, beta, true, useQuiescence);
            if (val < best) best = val;
            if (val < beta) beta = val;
            if (beta <= alpha) break;
        }
        return best;
    }

    function chooseBestAiMove(pos, difficulty) {
        const legal = orderMoves(pos.board, getAllLegalMoves(pos, pos.turn));
        if (legal.length === 0) return null;

        const aiIsWhite = pos.turn === 'w';
        const depth = difficulty === 'hard' ? 3 : (difficulty === 'normal' ? 2 : 1);
        const useQuiescence = difficulty === 'hard';
        const noise = difficulty === 'easy' ? 65 : (difficulty === 'normal' ? 10 : 0);

        const scored = legal.map(move => {
            const { nextPos } = applyMoveToPos(pos, move, false);
            const raw = alphaBeta(nextPos, depth - 1, -Infinity, Infinity, !aiIsWhite, useQuiescence);
            const jitter = noise > 0 ? (Math.random() - 0.5) * 2 * noise : 0;
            return { move, raw, evalScore: raw + jitter };
        });

        scored.sort((a, b) => aiIsWhite ? (b.evalScore - a.evalScore) : (a.evalScore - b.evalScore));

        if (difficulty === 'easy' && scored.length > 1 && Math.random() < 0.25) {
            const pickIdx = Math.floor(Math.random() * Math.min(3, scored.length));
            return scored[pickIdx].move;
        }
        return scored[0].move;
    }

    // --- Persistence & Lifecycle ---
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

    function getAiColor() {
        return prefs.humanColor === 'w' ? 'b' : 'w';
    }

    function startNewMatch() {
        clearTimers();
        closeModal('chessResultModal');
        closePromoOverlay();

        const sec = Number(prefs.timeLimitSec) || 0;
        prefs.flipped = prefs.humanColor === 'b';
        savePrefs();

        state.pos = createInitialPos();
        state.snapshots = [];
        state.moveHistory = [];
        state.capturedByWhite = [];
        state.capturedByBlack = [];
        state.selectedSq = null;
        state.legalTargets = [];
        state.lastMove = null;
        state.hintMove = null;
        state.pendingPromo = null;
        state.animMove = null;
        state.particles = [];
        state.aiThinking = false;
        state.clocks = { w: sec, b: sec };
        state.lastClockTick = performance.now();
        state.result = null;

        syncControlsUI();
        applyBoardVisuals();
        renderUI();

        if (state.pos.turn !== prefs.humanColor) {
            triggerAiTurn();
        }
    }

    function executeMove(move, isAiMove = false) {
        if (!state.pos || state.result) return;

        // Simpan snapshot sebelum melangkah untuk fitur Undo
        state.snapshots.push({
            pos: {
                ...state.pos,
                board: cloneBoard(state.pos.board),
                castling: { ...state.pos.castling },
                enPassant: state.pos.enPassant ? { ...state.pos.enPassant } : null,
                positionCounts: { ...state.pos.positionCounts },
            },
            moveHistory: state.moveHistory.slice(),
            capturedByWhite: state.capturedByWhite.slice(),
            capturedByBlack: state.capturedByBlack.slice(),
            lastMove: state.lastMove ? { from: { ...state.lastMove.from }, to: { ...state.lastMove.to } } : null,
        });

        const posBefore = state.pos;
        const movingPiece = posBefore.board[move.from.r][move.from.c];
        const { nextPos, captured } = applyMoveToPos(posBefore, move, true);
        const san = buildSanNotation(posBefore, move, nextPos);

        state.pos = nextPos;
        state.selectedSq = null;
        state.legalTargets = [];
        state.hintMove = null;
        state.lastMove = { from: { ...move.from }, to: { ...move.to } };
        state.moveHistory.push({
            san,
            color: movingPiece.color,
            from: move.from,
            to: move.to,
            captured,
        });

        if (captured) {
            if (movingPiece.color === 'w') state.capturedByWhite.push(captured.type);
            else state.capturedByBlack.push(captured.type);
            const pt = squareToCanvasPoint(move.to.r, move.to.c);
            spawnParticles(pt.x + SQ / 2, pt.y + SQ / 2, captured.color === 'w' ? '#fef3c7' : '#334155', 14);
        }

        state.animMove = {
            piece: { color: movingPiece.color, type: move.promotion || movingPiece.type },
            from: { ...move.from },
            to: { ...move.to },
            startedAt: performance.now(),
            duration: 185,
        };

        const inCheck = isKingInCheck(nextPos.board, nextPos.turn);
        if (inCheck) sfxCheck();
        else if (captured) sfxCapture();
        else sfxWoodMove();

        const outcome = evaluateOutcome(nextPos);
        if (outcome) {
            finishMatch(outcome.winner, outcome.reason);
            return;
        }

        renderUI();
        if (!isAiMove && state.pos.turn === getAiColor()) {
            triggerAiTurn();
        }
    }

    function triggerAiTurn() {
        if (!state.pos || state.result || state.aiThinking) return;
        if (state.pos.turn !== getAiColor()) return;

        state.aiThinking = true;
        renderStatusPanel();

        scheduleTimer(() => {
            if (!state.pos || state.result) {
                state.aiThinking = false;
                return;
            }
            const aiMove = chooseBestAiMove(state.pos, prefs.difficulty);
            state.aiThinking = false;
            if (aiMove) {
                executeMove(aiMove, true);
            } else {
                const outcome = evaluateOutcome(state.pos);
                if (outcome) finishMatch(outcome.winner, outcome.reason);
            }
        }, 280);
    }

    function finishMatch(winner, reason) {
        clearTimers();
        state.aiThinking = false;
        state.result = { winner, reason };

        if (!stats[prefs.difficulty]) stats[prefs.difficulty] = { wins: 0, draws: 0, losses: 0 };
        if (winner === 'draw') stats[prefs.difficulty].draws += 1;
        else if (winner === prefs.humanColor) stats[prefs.difficulty].wins += 1;
        else stats[prefs.difficulty].losses += 1;
        saveStats();

        sfxGameEnd(winner === prefs.humanColor);
        renderUI();
        showResultModal();
    }

    function undoLastTurnPair() {
        if (state.aiThinking || state.snapshots.length === 0) return;
        closeModal('chessResultModal');
        closePromoOverlay();

        // Kembali ke giliran manusia sebelumnya
        let targetIdx = state.snapshots.length - 1;
        while (targetIdx > 0 && state.snapshots[targetIdx].pos.turn !== prefs.humanColor) {
            targetIdx -= 1;
        }

        const snap = state.snapshots[targetIdx];
        if (!snap || snap.pos.turn !== prefs.humanColor) return;

        state.pos = snap.pos;
        state.moveHistory = snap.moveHistory;
        state.capturedByWhite = snap.capturedByWhite;
        state.capturedByBlack = snap.capturedByBlack;
        state.lastMove = snap.lastMove;
        state.snapshots = state.snapshots.slice(0, targetIdx);
        state.selectedSq = null;
        state.legalTargets = [];
        state.hintMove = null;
        state.animMove = null;
        state.result = null;

        renderUI();
    }

    function showHintMove() {
        if (!state.pos || state.result || state.aiThinking || state.pos.turn !== prefs.humanColor) return;
        const best = chooseBestAiMove(state.pos, 'hard');
        if (best) {
            state.hintMove = { from: best.from, to: best.to };
            state.selectedSq = { ...best.from };
            state.legalTargets = getAllLegalMoves(state.pos, prefs.humanColor).filter(
                m => m.from.r === best.from.r && m.from.c === best.from.c
            );
            renderUI();
        }
    }

    // --- 3D Canvas Board & 3D Standing Figurine Renderer ---
    function squareToCanvasPoint(r, c) {
        const vr = prefs.flipped ? (7 - r) : r;
        const vc = prefs.flipped ? (7 - c) : c;
        return { x: vc * SQ, y: vr * SQ };
    }

    function canvasPointToSquare(px, py) {
        const vc = Math.floor(px / SQ);
        const vr = Math.floor(py / SQ);
        if (vc < 0 || vc > 7 || vr < 0 || vr > 7) return null;
        const r = prefs.flipped ? (7 - vr) : vr;
        const c = prefs.flipped ? (7 - vc) : vc;
        return { r, c };
    }

    function spawnParticles(cx, cy, color, count = 12) {
        for (let i = 0; i < count; i++) {
            const ang = (Math.PI * 2 * i) / count + Math.random() * 0.4;
            const spd = 55 + Math.random() * 105;
            state.particles.push({
                x: cx,
                y: cy,
                vx: Math.cos(ang) * spd,
                vy: Math.sin(ang) * spd,
                color,
                life: 0.4 + Math.random() * 0.2,
                maxLife: 0.6,
                r: 2.5 + Math.random() * 2.5,
            });
        }
    }

    function drawBoard3d(now) {
        const th = BOARD_THEMES[prefs.theme] || BOARD_THEMES.wood;
        const kingCheckSq = isKingInCheck(state.pos.board, state.pos.turn)
            ? findKing(state.pos.board, state.pos.turn)
            : null;

        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const isLight = (r + c) % 2 === 0;
                const pt = squareToCanvasPoint(r, c);
                const x = pt.x;
                const y = pt.y;

                ctx.fillStyle = isLight ? th.light : th.dark;
                ctx.fillRect(x, y, SQ, SQ);

                // Bevel 3D tiap petak papan
                ctx.fillStyle = isLight ? th.lightHi : th.darkHi;
                ctx.fillRect(x, y, SQ, 3);
                ctx.fillRect(x, y, 3, SQ);
                ctx.fillStyle = 'rgba(2, 6, 23, 0.16)';
                ctx.fillRect(x, y + SQ - 3, SQ, 3);
                ctx.fillRect(x + SQ - 3, y, 3, SQ);

                // Highlight langkah terakhir
                if (state.lastMove && (
                    (state.lastMove.from.r === r && state.lastMove.from.c === c) ||
                    (state.lastMove.to.r === r && state.lastMove.to.c === c)
                )) {
                    ctx.fillStyle = 'rgba(250, 204, 21, 0.34)';
                    ctx.fillRect(x, y, SQ, SQ);
                }

                // Highlight bidak terpilih
                if (state.selectedSq && state.selectedSq.r === r && state.selectedSq.c === c) {
                    ctx.fillStyle = 'rgba(99, 102, 241, 0.42)';
                    ctx.fillRect(x, y, SQ, SQ);
                }

                // Highlight saran langkah
                if (state.hintMove && (
                    (state.hintMove.from.r === r && state.hintMove.from.c === c) ||
                    (state.hintMove.to.r === r && state.hintMove.to.c === c)
                )) {
                    ctx.strokeStyle = '#38bdf8';
                    ctx.lineWidth = 4;
                    ctx.strokeRect(x + 3, y + 3, SQ - 6, SQ - 6);
                }

                // Highlight SKAK pada petak Raja
                if (kingCheckSq && kingCheckSq.r === r && kingCheckSq.c === c) {
                    const pulse = 0.55 + 0.3 * Math.sin(now * 0.01);
                    const chkGrad = ctx.createRadialGradient(
                        x + SQ / 2, y + SQ / 2, 6,
                        x + SQ / 2, y + SQ / 2, SQ * 0.52
                    );
                    chkGrad.addColorStop(0, `rgba(239, 68, 68, ${pulse})`);
                    chkGrad.addColorStop(1, 'rgba(220, 38, 38, 0.12)');
                    ctx.fillStyle = chkGrad;
                    ctx.fillRect(x, y, SQ, SQ);
                }

                // Label koordinat papan
                const vr = prefs.flipped ? (7 - r) : r;
                const vc = prefs.flipped ? (7 - c) : c;
                ctx.fillStyle = isLight ? th.coordLight : th.coordDark;
                ctx.font = 'bold 11px Inter, sans-serif';
                if (vr === 7) {
                    ctx.textAlign = 'right';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(FILES[c], x + SQ - 4, y + SQ - 3);
                }
                if (vc === 0) {
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'top';
                    ctx.fillText(String(8 - r), x + 4, y + 3);
                }
            }
        }

        // Indikator petak langkah legal
        state.legalTargets.forEach(m => {
            const pt = squareToCanvasPoint(m.to.r, m.to.c);
            const cx = pt.x + SQ / 2;
            const cy = pt.y + SQ / 2;
            const hasEnemy = Boolean(state.pos.board[m.to.r][m.to.c]) || m.isEnPassant;
            ctx.save();
            if (hasEnemy) {
                ctx.strokeStyle = 'rgba(225, 29, 72, 0.85)';
                ctx.lineWidth = 4.5;
                ctx.beginPath();
                ctx.arc(cx, cy, SQ * 0.38, 0, Math.PI * 2);
                ctx.stroke();
            } else {
                ctx.fillStyle = 'rgba(16, 185, 129, 0.72)';
                ctx.beginPath();
                ctx.arc(cx, cy, SQ * 0.16, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();
        });
    }

    function draw3dChessPiece(cx, cy, piece, isSelected, liftY, now) {
        const isWhite = piece.color === 'w';
        const palette = isWhite
            ? {
                hi: '#ffffff',
                mid: '#fef3c7',
                dark: '#d97706',
                wall: '#b45309',
                rim: '#78350f',
                glyphFill: '#1e1b4b',
                glyphStroke: '#ffffff',
                pillBg: '#78350f',
                pillText: '#fef3c7',
            }
            : {
                hi: '#475569',
                mid: '#1e293b',
                dark: '#020617',
                wall: '#0f172a',
                rim: '#f59e0b',
                glyphFill: '#fef08a',
                glyphStroke: '#020617',
                pillBg: '#f59e0b',
                pillText: '#090d16',
            };

        const hover = isSelected ? (6 + Math.sin(now * 0.008) * 2.5) : 0;
        const totalLift = liftY + hover;
        const py = cy + 6 - totalLift;
        const discR = 26.5;
        const discY = py - 8;

        ctx.save();

        // 1. Bayangan lantai 3D lebar
        const shScale = Math.max(0.65, 1 - totalLift * 0.015);
        ctx.fillStyle = `rgba(2, 6, 23, ${0.44 * shScale})`;
        ctx.beginPath();
        ctx.ellipse(cx + 3 + totalLift * 0.16, cy + 23, 30 * shScale, 13 * shScale, 0, 0, Math.PI * 2);
        ctx.fill();

        // 2. Alas pedestal 3D depan bertingkat
        ctx.fillStyle = palette.wall;
        ctx.beginPath();
        ctx.ellipse(cx, py + 19, 29, 11.5, 0, 0, Math.PI * 2);
        ctx.fill();

        const baseGrad = ctx.createLinearGradient(cx - 28, py + 14, cx + 28, py + 14);
        baseGrad.addColorStop(0, palette.hi);
        baseGrad.addColorStop(0.5, palette.mid);
        baseGrad.addColorStop(1, palette.dark);
        ctx.fillStyle = baseGrad;
        ctx.beginPath();
        ctx.ellipse(cx, py + 14, 28, 11, 0, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = palette.rim;
        ctx.lineWidth = 1.6;
        ctx.stroke();

        // 3. Leher 3D penyangga medali
        ctx.fillStyle = palette.wall;
        ctx.fillRect(cx - 12, discY + 12, 24, py + 12 - (discY + 12));

        // 4. Dinding tebal medali 3D (ekstrusi bawah)
        ctx.fillStyle = palette.dark;
        ctx.beginPath();
        ctx.arc(cx, discY + 6, discR, 0, Math.PI * 2);
        ctx.fill();

        // 5. Ornamen puncak Raja (salib emas) / Ratu (mahkota 3 permata)
        if (piece.type === 'k') {
            ctx.fillStyle = '#fbbf24';
            ctx.strokeStyle = palette.rim;
            ctx.lineWidth = 1.4;
            ctx.fillRect(cx - 3, discY - discR - 8, 6, 12);
            ctx.strokeRect(cx - 3, discY - discR - 8, 6, 12);
            ctx.fillRect(cx - 7.5, discY - discR - 4.5, 15, 4.5);
            ctx.strokeRect(cx - 7.5, discY - discR - 4.5, 15, 4.5);
        } else if (piece.type === 'q') {
            [-11, 0, 11].forEach((dx, idx) => {
                const gy = discY - discR - (idx === 1 ? 4.5 : 2);
                ctx.fillStyle = '#fbbf24';
                ctx.beginPath();
                ctx.arc(cx + dx, gy, idx === 1 ? 4.2 : 3.4, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = palette.rim;
                ctx.lineWidth = 1.2;
                ctx.stroke();
            });
        }

        // 6. Cakram medali 3D utama tempat karakter catur
        const medGrad = ctx.createRadialGradient(cx - 9, discY - 10, 3, cx, discY, discR);
        medGrad.addColorStop(0, palette.hi);
        medGrad.addColorStop(0.55, palette.mid);
        medGrad.addColorStop(1, palette.dark);
        ctx.fillStyle = medGrad;
        ctx.beginPath();
        ctx.arc(cx, discY, discR, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = isSelected ? '#38bdf8' : palette.rim;
        ctx.lineWidth = isSelected ? 3.2 : 2.4;
        ctx.stroke();

        // 7. Ikon karakter catur BESAR, padat & berkontras tinggi
        const sym = PIECE_SOLID_GLYPH[piece.type] || '♟';
        const fontSize = piece.type === 'p' ? 40 : 45;
        ctx.font = `900 ${fontSize}px "Segoe UI Symbol", "Apple Symbols", "Noto Sans Symbols 2", serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        ctx.lineJoin = 'round';
        ctx.lineWidth = 4;
        ctx.strokeStyle = palette.glyphStroke;
        ctx.strokeText(sym, cx, discY + 2);

        ctx.fillStyle = palette.glyphFill;
        ctx.fillText(sym, cx, discY + 2);

        // 8. Label jenis bidak ringkas di pedestal depan agar 100% langsung terbaca
        const lbl = PIECE_SHORT_LABEL[piece.type] || '';
        const pillW = 34;
        const pillH = 12;
        const pillX = cx - pillW / 2;
        const pillY = py + 11;
        ctx.fillStyle = palette.pillBg;
        ctx.beginPath();
        ctx.roundRect(pillX, pillY, pillW, pillH, 6);
        ctx.fill();

        ctx.fillStyle = palette.pillText;
        ctx.font = '900 8.5px Inter, sans-serif';
        ctx.fillText(lbl, cx, pillY + pillH / 2 + 0.5);

        ctx.restore();
    }

    function drawCanvas(now) {
        if (!ctx || !state.pos) return;
        ctx.clearRect(0, 0, BOARD_SIZE, BOARD_SIZE);
        drawBoard3d(now);

        // Kumpulkan semua bidak dan urutkan dari baris atas ke bawah layar (Painter's algorithm 3D)
        const items = [];
        for (let r = 0; r < 8; r++) {
            for (let c = 0; c < 8; c++) {
                const piece = state.pos.board[r][c];
                if (!piece) continue;

                const pt = squareToCanvasPoint(r, c);
                let cx = pt.x + SQ / 2;
                let cy = pt.y + SQ / 2;
                let liftY = 0;
                let isMovingPiece = false;

                if (state.animMove && state.animMove.to.r === r && state.animMove.to.c === c) {
                    const t = Math.min(1, Math.max(0, (now - state.animMove.startedAt) / state.animMove.duration));
                    if (t < 1) {
                        const fromPt = squareToCanvasPoint(state.animMove.from.r, state.animMove.from.c);
                        cx = fromPt.x + SQ / 2 + (cx - (fromPt.x + SQ / 2)) * t;
                        cy = fromPt.y + SQ / 2 + (cy - (fromPt.y + SQ / 2)) * t;
                        liftY = Math.sin(t * Math.PI) * 22;
                        isMovingPiece = true;
                    } else {
                        state.animMove = null;
                    }
                }

                const isSelected = Boolean(state.selectedSq && state.selectedSq.r === r && state.selectedSq.c === c);
                items.push({ cx, cy, piece, isSelected, liftY, sortZ: isMovingPiece ? 9999 : cy });
            }
        }

        items.sort((a, b) => a.sortZ - b.sortZ);
        items.forEach(item => {
            draw3dChessPiece(item.cx, item.cy, item.piece, item.isSelected, item.liftY, now);
        });

        // Partikel makan bidak
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

        // Tick jam catur real-time
        if (prefs.timeLimitSec > 0 && !state.result && state.moveHistory.length > 0) {
            const elapsedSec = (now - state.lastClockTick) / 1000;
            if (elapsedSec >= 1) {
                const wholeSec = Math.floor(elapsedSec);
                state.lastClockTick += wholeSec * 1000;
                const activeCol = state.pos.turn;
                state.clocks[activeCol] = Math.max(0, state.clocks[activeCol] - wholeSec);
                renderClocks();
                if (state.clocks[activeCol] === 0) {
                    const winner = activeCol === 'w' ? 'b' : 'w';
                    finishMatch(winner, 'Waktu habis!');
                }
            }
        } else {
            state.lastClockTick = now;
        }
    }

    function loop(now) {
        drawCanvas(now);
        rafId = requestAnimationFrame(loop);
    }

    // --- DOM Renderers ---
    function formatClock(sec) {
        if (prefs.timeLimitSec <= 0) return '∞';
        const m = Math.floor(sec / 60);
        const s = sec % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    function renderClocks() {
        const topCol = prefs.flipped ? 'w' : 'b';
        const botCol = prefs.flipped ? 'b' : 'w';
        const topEl = document.getElementById('chessTopClock');
        const botEl = document.getElementById('chessBottomClock');
        if (topEl) topEl.textContent = formatClock(state.clocks[topCol]);
        if (botEl) botEl.textContent = formatClock(state.clocks[botCol]);
    }

    function renderPlayerBars() {
        const topCol = prefs.flipped ? 'w' : 'b';
        const botCol = prefs.flipped ? 'b' : 'w';

        const isTopHuman = topCol === prefs.humanColor;
        const aiName = `AI Catur (${DIFFICULTY_LABELS[prefs.difficulty]})`;
        const humanName = `Anda (${prefs.humanColor === 'w' ? 'Putih' : 'Hitam'})`;

        const topAvatar = document.getElementById('chessTopAvatar');
        const botAvatar = document.getElementById('chessBottomAvatar');
        const topName = document.getElementById('chessTopName');
        const botName = document.getElementById('chessBottomName');

        if (topAvatar) topAvatar.textContent = topCol === 'w' ? '♔' : '♚';
        if (botAvatar) botAvatar.textContent = botCol === 'w' ? '♔' : '♚';
        if (topName) topName.textContent = isTopHuman ? humanName : aiName;
        if (botName) botName.textContent = !isTopHuman ? humanName : aiName;

        // Material advantage
        const wMat = state.capturedByWhite.reduce((sum, t) => sum + Math.round((PIECE_VALUES[t] || 0) / 100), 0);
        const bMat = state.capturedByBlack.reduce((sum, t) => sum + Math.round((PIECE_VALUES[t] || 0) / 100), 0);

        const formatCap = (col) => {
            const arr = col === 'w' ? state.capturedByWhite : state.capturedByBlack;
            const victimCol = col === 'w' ? 'b' : 'w';
            return arr.map(t => PIECE_UNICODE[victimCol][t]).join(' ');
        };

        const topCapEl = document.getElementById('chessTopCaptured');
        const botCapEl = document.getElementById('chessBottomCaptured');
        const topAdvEl = document.getElementById('chessTopAdvantage');
        const botAdvEl = document.getElementById('chessBottomAdvantage');

        if (topCapEl) topCapEl.textContent = formatCap(topCol) || '—';
        if (botCapEl) botCapEl.textContent = formatCap(botCol) || '—';

        const topDiff = (topCol === 'w' ? wMat - bMat : bMat - wMat);
        const botDiff = (botCol === 'w' ? wMat - bMat : bMat - wMat);
        if (topAdvEl) topAdvEl.textContent = topDiff > 0 ? `+${topDiff}` : '';
        if (botAdvEl) botAdvEl.textContent = botDiff > 0 ? `+${botDiff}` : '';

        renderClocks();
    }

    function renderStatusPanel() {
        const badge = document.getElementById('chessTurnBadge');
        const banner = document.getElementById('chessStatusBanner');
        const evalFill = document.getElementById('chessEvalFill');
        const evalLabel = document.getElementById('chessEvalLabel');

        if (badge) {
            if (state.result) {
                badge.textContent = state.result.winner === 'draw'
                    ? 'Permainan Remis'
                    : (state.result.winner === prefs.humanColor ? 'Anda Menang!' : 'AI Menang');
            } else {
                badge.textContent = state.pos.turn === 'w' ? 'Giliran Putih' : 'Giliran Hitam';
            }
        }

        if (banner) {
            if (state.result) {
                banner.textContent = state.result.reason;
            } else if (state.aiThinking) {
                banner.textContent = `AI (${DIFFICULTY_LABELS[prefs.difficulty]}) sedang menghitung langkah terbaik...`;
            } else if (isKingInCheck(state.pos.board, state.pos.turn)) {
                banner.textContent = `⚠️ SKAK! Raja ${state.pos.turn === 'w' ? 'Putih' : 'Hitam'} terancam.`;
            } else if (state.pos.turn === prefs.humanColor) {
                banner.textContent = 'Giliran Anda: klik bidak pada papan 3D untuk melangkah.';
            } else {
                banner.textContent = 'Menunggu giliran AI...';
            }
        }

        const rawEval = evaluateBoardWhitePerspective(state.pos.board) / 100;
        const pct = Math.max(8, Math.min(92, 50 + (rawEval * 5)));
        if (evalFill) evalFill.style.width = `${pct.toFixed(1)}%`;
        if (evalLabel) {
            if (Math.abs(rawEval) < 0.25) evalLabel.textContent = 'Seimbang (0.0)';
            else if (rawEval > 0) evalLabel.textContent = `Putih +${rawEval.toFixed(1)}`;
            else evalLabel.textContent = `Hitam +${Math.abs(rawEval).toFixed(1)}`;
        }
    }

    function renderMoveHistoryAndStats() {
        const histEl = document.getElementById('chessMoveHistory');
        if (histEl) {
            if (state.moveHistory.length === 0) {
                histEl.innerHTML = '<div class="text-center text-slate-400 py-3">Belum ada langkah.</div>';
            } else {
                const rows = [];
                for (let i = 0; i < state.moveHistory.length; i += 2) {
                    const num = Math.floor(i / 2) + 1;
                    const wSan = state.moveHistory[i]?.san || '';
                    const bSan = state.moveHistory[i + 1]?.san || '';
                    rows.push(`
                        <div class="grid grid-cols-12 gap-1 px-2 py-1 rounded odd:bg-white/60 dark:odd:bg-slate-900/40">
                            <span class="col-span-2 text-slate-400">${num}.</span>
                            <span class="col-span-5 font-bold">${escapeHtml(wSan)}</span>
                            <span class="col-span-5 font-bold text-indigo-600 dark:text-indigo-400">${escapeHtml(bSan)}</span>
                        </div>
                    `);
                }
                histEl.innerHTML = rows.join('');
                histEl.scrollTop = histEl.scrollHeight;
            }
        }

        const statsEl = document.getElementById('chessStatsPanel');
        if (statsEl) {
            const levels = [
                { key: 'easy', label: 'Santai' },
                { key: 'normal', label: 'Normal' },
                { key: 'hard', label: 'Sulit' },
            ];
            statsEl.innerHTML = levels.map(lv => {
                const s = stats[lv.key] || { wins: 0, draws: 0, losses: 0 };
                return `
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/60 p-2">
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400">${lv.label}</div>
                        <div class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">
                            ${s.wins}M &middot; <span class="text-slate-500">${s.draws}R</span> &middot; <span class="text-rose-500">${s.losses}K</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    function renderUI() {
        renderPlayerBars();
        renderStatusPanel();
        renderMoveHistoryAndStats();
    }

    function applyBoardVisuals() {
        const card = document.getElementById('chessBoardCard');
        const stage = document.getElementById('chessBoardStage');
        const surface = document.getElementById('chessBoard3dSurface');
        const camLabel = document.getElementById('chessCameraLabel');
        const zoomReset = document.getElementById('btnChessZoomReset');

        const cam = CAMERA_LABELS[prefs.camera] ? prefs.camera : '3d';
        const theme = BOARD_THEMES[prefs.theme] ? prefs.theme : 'wood';
        const zoom = Math.min(1.2, Math.max(0.8, Number(prefs.zoom) || 1));

        if (card) card.setAttribute('data-camera', cam);
        if (stage) stage.setAttribute('data-theme', theme);
        if (surface) surface.style.setProperty('--chess-zoom', String(zoom));
        if (camLabel) camLabel.textContent = CAMERA_LABELS[cam];
        if (zoomReset) zoomReset.textContent = `${Math.round(zoom * 100)}%`;

        document.querySelectorAll('[data-chess-theme]').forEach(btn => {
            const isSel = btn.getAttribute('data-chess-theme') === theme;
            btn.classList.toggle('ring-2', isSel);
            btn.classList.toggle('ring-amber-400', isSel);
            btn.classList.toggle('scale-110', isSel);
        });
    }

    function syncControlsUI() {
        document.querySelectorAll('.chess-opt-diff').forEach(btn => {
            const active = btn.getAttribute('data-chess-diff') === prefs.difficulty;
            btn.className = 'chess-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border cursor-pointer transition-colors ' +
                (active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700');
        });

        const colSel = document.getElementById('chessColorSelect');
        if (colSel) colSel.value = prefs.humanColor;
        const timeSel = document.getElementById('chessTimeSelect');
        if (timeSel) timeSel.value = String(prefs.timeLimitSec);

        const muteIcon = document.getElementById('chessMuteIcon');
        const muteLbl = document.getElementById('chessMuteLabel');
        if (muteIcon) muteIcon.className = prefs.muted ? 'bi bi-volume-mute-fill text-rose-500' : 'bi bi-volume-up-fill text-emerald-500';
        if (muteLbl) muteLbl.textContent = prefs.muted ? 'Suara Mati' : 'Suara Aktif';
    }

    // --- Modals, Promotion Overlay & Fullscreen ---
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

    function openPromoOverlay(promoMoves) {
        const ov = document.getElementById('chessPromoOverlay');
        const wrap = document.getElementById('chessPromoOptions');
        if (!ov || !wrap) return;

        const labels = { q: 'Menteri', r: 'Benteng', b: 'Gajah', n: 'Kuda' };
        wrap.innerHTML = promoMoves.map(m => {
            const sym = PIECE_UNICODE[prefs.humanColor][m.promotion];
            return `
                <button type="button" data-promo-type="${m.promotion}"
                    class="flex flex-col items-center justify-center gap-1 p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-slate-300 dark:border-slate-700 cursor-pointer">
                    <span class="text-4xl leading-none">${sym}</span>
                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200">${labels[m.promotion]}</span>
                </button>
            `;
        }).join('');

        ov.classList.remove('hidden');
        ov.classList.add('flex');

        wrap.querySelectorAll('[data-promo-type]').forEach(btn => {
            btn.addEventListener('click', () => {
                const chosenType = btn.getAttribute('data-promo-type');
                const chosenMove = promoMoves.find(m => m.promotion === chosenType);
                closePromoOverlay();
                if (chosenMove) executeMove(chosenMove, false);
            });
        });
    }

    function closePromoOverlay() {
        const ov = document.getElementById('chessPromoOverlay');
        if (!ov) return;
        ov.classList.add('hidden');
        ov.classList.remove('flex');
    }

    function showResultModal() {
        const body = document.getElementById('chessResultBody');
        if (!body || !state.result) return;

        const didWin = state.result.winner === prefs.humanColor;
        const isDraw = state.result.winner === 'draw';
        const title = isDraw ? 'Permainan Berakhir Remis!' : (didWin ? 'Selamat! Anda Menang!' : 'AI Memenangkan Laga!');
        const icon = isDraw ? '🤝' : (didWin ? '♔' : '♚');

        body.innerHTML = `
            <div class="text-center space-y-2">
                <div class="w-14 h-14 mx-auto rounded-full flex items-center justify-center text-3xl bg-amber-100 dark:bg-amber-950/60 border-2 border-amber-400 shadow-sm">${icon}</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">${title}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">${escapeHtml(state.result.reason)}</div>
            </div>
            <button type="button" id="btnChessPlayAgain" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold cursor-pointer">
                Main Lagi Sekarang
            </button>
        `;
        openModal('chessResultModal');
        document.getElementById('btnChessPlayAgain')?.addEventListener('click', startNewMatch);
    }

    function toggleFullscreen() {
        const wrapper = document.getElementById('chessFullscreenWrapper');
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

    // --- 3D Canvas Pointer Hit Detection ---
    function handleCanvasPointer(e) {
        if (!canvas || !state.pos || state.result || state.aiThinking) return;
        if (state.pos.turn !== prefs.humanColor) return;

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

        const sq = canvasPointToSquare(px, py);
        if (!sq) return;

        // Jika sudah memilih petak & klik tujuan langkah legal
        if (state.selectedSq) {
            const matchingMoves = state.legalTargets.filter(m => m.to.r === sq.r && m.to.c === sq.c);
            if (matchingMoves.length > 1 && matchingMoves[0].promotion) {
                openPromoOverlay(matchingMoves);
                return;
            }
            if (matchingMoves.length === 1) {
                executeMove(matchingMoves[0], false);
                return;
            }
        }

        // Pilih bidak milik manusia
        const clickedPiece = state.pos.board[sq.r][sq.c];
        if (clickedPiece && clickedPiece.color === prefs.humanColor) {
            state.selectedSq = { r: sq.r, c: sq.c };
            state.legalTargets = getAllLegalMoves(state.pos, prefs.humanColor).filter(
                m => m.from.r === sq.r && m.from.c === sq.c
            );
            renderStatusPanel();
            return;
        }

        state.selectedSq = null;
        state.legalTargets = [];
    }

    // --- Init & Bindings ---
    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', () => {
            canvas = document.getElementById('chessCanvas');
            if (!canvas) return;
            ctx = canvas.getContext('2d');

            loadStorage();
            startNewMatch();

            canvas.addEventListener('pointerdown', (e) => {
                if (e.button !== undefined && e.button !== 0 && e.pointerType === 'mouse') return;
                handleCanvasPointer(e);
            });

            document.querySelectorAll('.chess-opt-diff').forEach(btn => {
                btn.addEventListener('click', () => {
                    prefs.difficulty = btn.getAttribute('data-chess-diff') || 'normal';
                    savePrefs();
                    startNewMatch();
                    btn.blur();
                });
            });

            document.getElementById('chessColorSelect')?.addEventListener('change', (e) => {
                prefs.humanColor = e.target.value === 'b' ? 'b' : 'w';
                savePrefs();
                startNewMatch();
                e.target.blur();
            });

            document.getElementById('chessTimeSelect')?.addEventListener('change', (e) => {
                prefs.timeLimitSec = Number(e.target.value) || 0;
                savePrefs();
                startNewMatch();
                e.target.blur();
            });

            document.getElementById('btnChessUndo')?.addEventListener('click', (e) => {
                undoLastTurnPair();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessHint')?.addEventListener('click', (e) => {
                showHintMove();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessRestart')?.addEventListener('click', (e) => {
                const restart = () => startNewMatch();
                if (window.Swal) {
                    window.Swal.fire({ icon: 'warning', title: 'Mulai pertandingan baru?', text: 'Progres pertandingan catur ini akan dihapus.', showCancelButton: true, confirmButtonText: 'Ya, mulai baru', cancelButtonText: 'Batal', confirmButtonColor: '#4f46e5' }).then(r => { if (r.isConfirmed) restart(); });
                } else if (window.confirm('Progres pertandingan catur ini akan dihapus. Mulai baru?')) restart();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessCycleCamera')?.addEventListener('click', (e) => {
                const idx = CAMERA_ORDER.indexOf(prefs.camera);
                prefs.camera = CAMERA_ORDER[(idx + 1) % CAMERA_ORDER.length];
                savePrefs();
                applyBoardVisuals();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessFlipBoard')?.addEventListener('click', (e) => {
                prefs.flipped = !prefs.flipped;
                savePrefs();
                renderPlayerBars();
                e.currentTarget.blur();
            });

            document.querySelectorAll('[data-chess-theme]').forEach(btn => {
                btn.addEventListener('click', () => {
                    prefs.theme = btn.getAttribute('data-chess-theme') || 'wood';
                    savePrefs();
                    applyBoardVisuals();
                    btn.blur();
                });
            });

            document.getElementById('btnChessZoomOut')?.addEventListener('click', (e) => {
                prefs.zoom = Math.max(0.8, Number(((Number(prefs.zoom) || 1) - 0.1).toFixed(2)));
                savePrefs();
                applyBoardVisuals();
                e.currentTarget.blur();
            });
            document.getElementById('btnChessZoomReset')?.addEventListener('click', (e) => {
                prefs.zoom = 1;
                savePrefs();
                applyBoardVisuals();
                e.currentTarget.blur();
            });
            document.getElementById('btnChessZoomIn')?.addEventListener('click', (e) => {
                prefs.zoom = Math.min(1.2, Number(((Number(prefs.zoom) || 1) + 0.1).toFixed(2)));
                savePrefs();
                applyBoardVisuals();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessMute')?.addEventListener('click', (e) => {
                prefs.muted = !prefs.muted;
                savePrefs();
                syncControlsUI();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessFullscreen')?.addEventListener('click', (e) => {
                toggleFullscreen();
                e.currentTarget.blur();
            });

            document.getElementById('btnChessOpenRules')?.addEventListener('click', () => openModal('chessRulesModal'));
            document.getElementById('btnCloseChessRules')?.addEventListener('click', () => closeModal('chessRulesModal'));
            document.getElementById('btnCloseChessResult')?.addEventListener('click', () => closeModal('chessResultModal'));

            document.getElementById('btnChessResetStats')?.addEventListener('click', () => {
                if (!window.confirm('Hapus seluruh rekor catur yang tersimpan di perangkat ini?')) return;
                stats = {
                    easy: { wins: 0, draws: 0, losses: 0 },
                    normal: { wins: 0, draws: 0, losses: 0 },
                    hard: { wins: 0, draws: 0, losses: 0 },
                };
                saveStats();
                renderMoveHistoryAndStats();
            });

            rafId = requestAnimationFrame(loop);
        });
    }

    if (typeof window === 'undefined' && typeof module !== 'undefined' && module.exports) {
        module.exports = {
            createInitialPos,
            getAllLegalMoves,
            applyMoveToPos,
            isKingInCheck,
            evaluateOutcome,
            chooseBestAiMove,
            buildSanNotation,
        };
    }
})();
