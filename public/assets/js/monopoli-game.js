(function () {
    'use strict';

    const SAVE_KEY = 'monopoli_nusantara_save_v1';
    const STATS_KEY = 'monopoli_nusantara_stats_v1';
    const PREFS_KEY = 'monopoli_nusantara_prefs_v1';

    const AI_ROSTER = [
        { name: 'Juragan Budi', token: '🐊', color: '#2563eb' },
        { name: 'Bos Sinta', token: '🌺', color: '#db2777' },
        { name: 'Sultan Hendra', token: '🐯', color: '#d97706' },
    ];

    const HUMAN_COLOR = '#059669';

    const COLOR_GROUP_LABELS = {
        brown: 'Cokelat',
        lightblue: 'Biru Muda',
        pink: 'Merah Muda',
        orange: 'Oranye',
        red: 'Merah',
        yellow: 'Kuning',
        green: 'Hijau',
        darkblue: 'Biru Tua',
    };

    const DIFFICULTY_LABELS = {
        easy: 'Santai',
        normal: 'Normal',
        hard: 'Sulit (Juragan)',
    };

    const CAMERA_ORDER = ['3d', 'cinematic', '2d'];
    const CAMERA_LABELS = {
        '3d': '3D Meja',
        'cinematic': 'Sinematik',
        '2d': '2D Atas',
    };

    // High-frequency landing probability weights (Oranye, Merah, Kuning, Transportasi)
    const STRATEGIC_PRIORITY = {
        16: 1.35, 18: 1.35, 19: 1.4,
        21: 1.3, 23: 1.35, 24: 1.35,
        26: 1.2, 27: 1.2, 29: 1.25,
        5: 1.3, 15: 1.3, 25: 1.35, 35: 1.25,
        11: 1.15, 13: 1.15, 14: 1.2,
        37: 1.2, 39: 1.25,
    };

    const INITIAL_BANK_HOUSES = 32;
    const INITIAL_BANK_HOTELS = 12;
    const START_SALARY = 2000000;
    const JAIL_BAIL = 500000;
    const INITIAL_CASH = 15000000;

    // 40 Petak Papan Monopoli Nusantara (indeks 0..39)
    const BOARD_TILES = [
        { idx: 0, name: 'START', short: 'START', type: 'start', desc: 'Terima gaji Rp 2.000.000 setiap melewati atau mendarat di sini.' },
        { idx: 1, name: 'Banda Aceh', short: 'B. Aceh', type: 'city', group: 'brown', price: 600000, buildCost: 500000, mortgage: 300000, rents: [20000, 100000, 300000, 900000, 1600000, 2500000] },
        { idx: 2, name: 'Dana Umum', short: 'Dana Umum', type: 'community' },
        { idx: 3, name: 'Medan', short: 'Medan', type: 'city', group: 'brown', price: 600000, buildCost: 500000, mortgage: 300000, rents: [40000, 200000, 600000, 1800000, 3200000, 4500000] },
        { idx: 4, name: 'Pajak Penghasilan (PPh)', short: 'Pajak PPh', type: 'tax_income', desc: 'Bayar Pajak Penghasilan sebesar 10% dari total kekayaan bersih Anda.' },
        { idx: 5, name: 'Stasiun Gambir', short: 'St. Gambir', type: 'transport', price: 2000000, mortgage: 1000000 },
        { idx: 6, name: 'Pekanbaru', short: 'Pekanbaru', type: 'city', group: 'lightblue', price: 1000000, buildCost: 500000, mortgage: 500000, rents: [60000, 300000, 900000, 2700000, 4000000, 5500000] },
        { idx: 7, name: 'Kesempatan', short: 'Kesempatan', type: 'chance' },
        { idx: 8, name: 'Padang', short: 'Padang', type: 'city', group: 'lightblue', price: 1000000, buildCost: 500000, mortgage: 500000, rents: [60000, 300000, 900000, 2700000, 4000000, 5500000] },
        { idx: 9, name: 'Palembang', short: 'Palembang', type: 'city', group: 'lightblue', price: 1200000, buildCost: 500000, mortgage: 600000, rents: [80000, 400000, 1000000, 3000000, 4500000, 6000000] },
        { idx: 10, name: 'Penjara (LP Nusakambangan)', short: 'Penjara', type: 'jail' },
        { idx: 11, name: 'Serang', short: 'Serang', type: 'city', group: 'pink', price: 1400000, buildCost: 1000000, mortgage: 700000, rents: [100000, 500000, 1500000, 4500000, 6250000, 7500000] },
        { idx: 12, name: 'PLN', short: 'PLN', type: 'utility', price: 1500000, mortgage: 750000 },
        { idx: 13, name: 'Bogor', short: 'Bogor', type: 'city', group: 'pink', price: 1400000, buildCost: 1000000, mortgage: 700000, rents: [100000, 500000, 1500000, 4500000, 6250000, 7500000] },
        { idx: 14, name: 'Bandung', short: 'Bandung', type: 'city', group: 'pink', price: 1600000, buildCost: 1000000, mortgage: 800000, rents: [120000, 600000, 1800000, 5000000, 7000000, 9000000] },
        { idx: 15, name: 'Pelabuhan Tanjung Priok', short: 'Tj. Priok', type: 'transport', price: 2000000, mortgage: 1000000 },
        { idx: 16, name: 'Semarang', short: 'Semarang', type: 'city', group: 'orange', price: 1800000, buildCost: 1000000, mortgage: 900000, rents: [140000, 700000, 2000000, 5500000, 7500000, 9500000] },
        { idx: 17, name: 'Dana Umum', short: 'Dana Umum', type: 'community' },
        { idx: 18, name: 'Solo', short: 'Solo', type: 'city', group: 'orange', price: 1800000, buildCost: 1000000, mortgage: 900000, rents: [140000, 700000, 2000000, 5500000, 7500000, 9500000] },
        { idx: 19, name: 'Yogyakarta', short: 'Yogyakarta', type: 'city', group: 'orange', price: 2000000, buildCost: 1000000, mortgage: 1000000, rents: [160000, 800000, 2200000, 6000000, 8000000, 10000000] },
        { idx: 20, name: 'Parkir Bebas (Rest Area)', short: 'Parkir Bebas', type: 'free_parking' },
        { idx: 21, name: 'Malang', short: 'Malang', type: 'city', group: 'red', price: 2200000, buildCost: 1500000, mortgage: 1100000, rents: [180000, 900000, 2500000, 7000000, 8750000, 10500000] },
        { idx: 22, name: 'Kesempatan', short: 'Kesempatan', type: 'chance' },
        { idx: 23, name: 'Surabaya', short: 'Surabaya', type: 'city', group: 'red', price: 2200000, buildCost: 1500000, mortgage: 1100000, rents: [180000, 900000, 2500000, 7000000, 8750000, 10500000] },
        { idx: 24, name: 'Balikpapan', short: 'Balikpapan', type: 'city', group: 'red', price: 2400000, buildCost: 1500000, mortgage: 1200000, rents: [200000, 1000000, 3000000, 7500000, 9250000, 11000000] },
        { idx: 25, name: 'Bandara Soekarno-Hatta', short: 'Soetta', type: 'transport', price: 2000000, mortgage: 1000000 },
        { idx: 26, name: 'Manado', short: 'Manado', type: 'city', group: 'yellow', price: 2600000, buildCost: 1500000, mortgage: 1300000, rents: [220000, 1100000, 3300000, 8000000, 9750000, 11500000] },
        { idx: 27, name: 'Makassar', short: 'Makassar', type: 'city', group: 'yellow', price: 2600000, buildCost: 1500000, mortgage: 1300000, rents: [220000, 1100000, 3300000, 8000000, 9750000, 11500000] },
        { idx: 28, name: 'PDAM / Pertamina', short: 'PDAM', type: 'utility', price: 1500000, mortgage: 750000 },
        { idx: 29, name: 'Lombok', short: 'Lombok', type: 'city', group: 'yellow', price: 2800000, buildCost: 1500000, mortgage: 1400000, rents: [240000, 1200000, 3600000, 8500000, 10250000, 12000000] },
        { idx: 30, name: 'Masuk Penjara!', short: 'Masuk Bui', type: 'go_to_jail' },
        { idx: 31, name: 'Jayapura', short: 'Jayapura', type: 'city', group: 'green', price: 3000000, buildCost: 2000000, mortgage: 1500000, rents: [260000, 1300000, 3900000, 9000000, 11000000, 12750000] },
        { idx: 32, name: 'Labuan Bajo', short: 'L. Bajo', type: 'city', group: 'green', price: 3000000, buildCost: 2000000, mortgage: 1500000, rents: [260000, 1300000, 3900000, 9000000, 11000000, 12750000] },
        { idx: 33, name: 'Dana Umum', short: 'Dana Umum', type: 'community' },
        { idx: 34, name: 'Bali', short: 'Bali', type: 'city', group: 'green', price: 3200000, buildCost: 2000000, mortgage: 1600000, rents: [280000, 1500000, 4500000, 10000000, 12000000, 14000000] },
        { idx: 35, name: 'Stasiun Kereta Cepat Whoosh', short: 'Whoosh', type: 'transport', price: 2000000, mortgage: 1000000 },
        { idx: 36, name: 'Kesempatan', short: 'Kesempatan', type: 'chance' },
        { idx: 37, name: 'IKN Nusantara', short: 'IKN', type: 'city', group: 'darkblue', price: 3500000, buildCost: 2000000, mortgage: 1750000, rents: [350000, 1750000, 5000000, 11000000, 13000000, 15000000] },
        { idx: 38, name: 'Pajak Barang Mewah', short: 'Pajak Mewah', type: 'tax_luxury', amount: 1000000, desc: 'Bayar Pajak Barang Mewah Rp 1.000.000 ke Bank.' },
        { idx: 39, name: 'Jakarta', short: 'Jakarta', type: 'city', group: 'darkblue', price: 4000000, buildCost: 2000000, mortgage: 2000000, rents: [500000, 2000000, 6000000, 14000000, 17000000, 20000000] },
    ];

    // 16 Kartu Dana Umum Indonesia
    const COMMUNITY_CARDS = [
        { id: 'du1', title: 'Cairkan Deposito Bank', desc: 'Deposito berjangka Anda cair! Terima Rp 2.000.000 dari Bank.', action: 'cash', amount: 2000000 },
        { id: 'du2', title: 'Juara Lomba UMKM', desc: 'Anda memenangkan penghargaan UMKM terbaik. Terima Rp 100.000.', action: 'cash', amount: 100000 },
        { id: 'du3', title: 'Pengembalian SPT Pajak', desc: 'Lebih bayar SPT Tahunan dikembalikan. Terima Rp 200.000.', action: 'cash', amount: 200000 },
        { id: 'du4', title: 'Dividen Saham BUMN', desc: 'Pembagian laba BUMN cair! Terima Rp 500.000 dari Bank.', action: 'cash', amount: 500000 },
        { id: 'du5', title: 'Terima Warisan Keluarga', desc: 'Anda menerima dana warisan sebesar Rp 1.000.000.', action: 'cash', amount: 1000000 },
        { id: 'du6', title: 'Klaim Asuransi Kesehatan', desc: 'Asuransi Anda menyetujui klaim. Terima Rp 250.000.', action: 'cash', amount: 250000 },
        { id: 'du7', title: 'Beasiswa Riset Bisnis', desc: 'Proyek usaha Anda menerima hibah Rp 1.000.000.', action: 'cash', amount: 1000000 },
        { id: 'du8', title: 'Penjualan Obligasi Jatuh Tempo', desc: 'Terima imbal hasil obligasi senilai Rp 1.000.000.', action: 'cash', amount: 1000000 },
        { id: 'du9', title: 'Ulang Tahun Anda!', desc: 'Terima kado Rp 100.000 dari setiap pemain yang masih aktif.', action: 'collect_each', amount: 100000 },
        { id: 'du10', title: 'Biaya Rumah Sakit', desc: 'Bayar tagihan perawatan klinik sebesar Rp 1.000.000 ke Bank.', action: 'cash', amount: -1000000 },
        { id: 'du11', title: 'Bayar Iuran Pendidikan', desc: 'Bayar biaya SPP & pelatihan karyawan Rp 500.000 ke Bank.', action: 'cash', amount: -500000 },
        { id: 'du12', title: 'Tagihan Layanan Kesehatan', desc: 'Setor premi kesehatan tahunan Rp 500.000 ke Bank.', action: 'cash', amount: -500000 },
        { id: 'du13', title: 'Renovasi PBB Jalan & Properti', desc: 'Bayar iuran perbaikan kota: Rp 400.000 per Rumah & Rp 1.150.000 per Hotel.', action: 'repairs', perHouse: 400000, perHotel: 1150000 },
        { id: 'du14', title: 'Maju ke START', desc: 'Langsung meluncur ke Petak START dan ambil gaji Rp 2.000.000.', action: 'move_to', target: 0, awardStart: true },
        { id: 'du15', title: 'Masuk Penjara!', desc: 'Langsung masuk ke LP Nusakambangan tanpa melewati START.', action: 'go_to_jail' },
        { id: 'du16', title: 'Kartu Bebas Penjara', desc: 'Simpan kartu ini untuk keluar gratis dari LP Nusakambangan atau dibarterkan.', action: 'jail_free' },
    ];

    // 16 Kartu Kesempatan Indonesia
    const CHANCE_CARDS = [
        { id: 'ks1', title: 'Terbang ke Jakarta', desc: 'Maju ke Jakarta (Petak 39). Jika melewati START, ambil gaji Rp 2.000.000.', action: 'move_to', target: 39, awardStart: true },
        { id: 'ks2', title: 'Perjalanan Bisnis ke Surabaya', desc: 'Maju ke Surabaya (Petak 23). Jika melewati START, ambil gaji Rp 2.000.000.', action: 'move_to', target: 23, awardStart: true },
        { id: 'ks3', title: 'Kunjungan Kerja ke Serang', desc: 'Maju ke Serang (Petak 11). Jika melewati START, ambil gaji Rp 2.000.000.', action: 'move_to', target: 11, awardStart: true },
        { id: 'ks4', title: 'Naik Kereta Cepat Whoosh', desc: 'Maju ke Stasiun Kereta Cepat Whoosh (Petak 35). Jika melewati START, ambil Rp 2.000.000.', action: 'move_to', target: 35, awardStart: true },
        { id: 'ks5', title: 'Maju ke Transportasi Terdekat', desc: 'Pindah ke stasiun/bandara terdekat. Jika dimiliki pemain lain, bayar 2× tarif sewa!', action: 'nearest_transport' },
        { id: 'ks6', title: 'Maju ke Transportasi Terdekat', desc: 'Pindah ke pelabuhan/bandara terdekat. Jika dimiliki pemain lain, bayar 2× tarif sewa!', action: 'nearest_transport' },
        { id: 'ks7', title: 'Maju ke Utilitas BUMN Terdekat', desc: 'Pindah ke PLN/PDAM terdekat. Jika dimiliki lawan, lempar dadu baru & bayar 100.000 × Dadu!', action: 'nearest_utility' },
        { id: 'ks8', title: 'Kembali ke START', desc: 'Langsung maju ke Petak START dan terima gaji Rp 2.000.000.', action: 'move_to', target: 0, awardStart: true },
        { id: 'ks9', title: 'Macet Parah — Mundur 3 Petak', desc: 'Putar balik menghindari kemacetan! Mundur 3 petak dari posisi sekarang.', action: 'move_back', steps: 3 },
        { id: 'ks10', title: 'Dividen Bank Indonesia', desc: 'Bank memberi bagi hasil investasi sebesar Rp 500.000.', action: 'cash', amount: 500000 },
        { id: 'ks11', title: 'Obligasi Negara Ritel Cair', desc: 'ORI Anda jatuh tempo dengan untung! Terima Rp 1.500.000.', action: 'cash', amount: 1500000 },
        { id: 'ks12', title: 'Denda Tilang Elektronik (ETLE)', desc: 'Kamera jalan menangkap pelanggaran. Bayar denda Rp 150.000 ke Bank.', action: 'cash', amount: -150000 },
        { id: 'ks13', title: 'Terpilih Jadi Ketua RT/RW', desc: 'Traktir syukuran Rp 500.000 kepada setiap pemain lain yang masih aktif.', action: 'pay_each', amount: 500000 },
        { id: 'ks14', title: 'Perbaikan Massal Properti', desc: 'Wajib perawatan properti: bayar Rp 250.000/Rumah & Rp 1.000.000/Hotel.', action: 'repairs', perHouse: 250000, perHotel: 1000000 },
        { id: 'ks15', title: 'Masuk Penjara!', desc: 'Surat panggilan mendadak! Langsung masuk LP Nusakambangan tanpa lewat START.', action: 'go_to_jail' },
        { id: 'ks16', title: 'Kartu Bebas Penjara', desc: 'Simpan kartu ini untuk bebas dari penjara kapan saja atau sebagai alat barter.', action: 'jail_free' },
    ];

    const PIP_MAP = {
        1: [4],
        2: [0, 8],
        3: [0, 4, 8],
        4: [0, 2, 6, 8],
        5: [0, 2, 4, 6, 8],
        6: [0, 2, 3, 5, 6, 8],
    };

    // --- Runtime & Persistent Preferences ---
    let prefs = {
        playerCount: 4,
        difficulty: 'normal',
        winMode: 'classic',
        auctionEnabled: true,
        humanToken: '🏯',
        camera: '3d',
        felt: 'emerald',
        zoom: 1,
        muted: false,
    };

    let state = {
        started: false,
        hasMoved: false,
        finished: false,
        difficulty: 'normal',
        winMode: 'classic',
        maxRounds: 0,
        auctionEnabled: true,
        roundNumber: 1,
        currentTurn: 0,
        doublesCount: 0,
        phase: 'idle', // idle | need_roll | rolling | buy_or_auction | auction | jail_choice | manage | debt | card_wait | finished
        lastDice: [1, 6],
        rollingAnim: false,
        diceJustLanded: false,
        movingAnim: false,
        steppingTileIdx: null,
        landedTileIdx: null,
        stepTrail: [],
        stepProgress: null,
        animTimers: [],
        activeTxAnim: null,
        playerCashDeltas: {},
        txClearTimer: null,
        bankHouses: INITIAL_BANK_HOUSES,
        bankHotels: INITIAL_BANK_HOTELS,
        players: [],
        ownership: {}, // tileIdx -> { owner: playerIdx, level: 0..5, mortgaged: bool }
        communityDeck: [],
        chanceDeck: [],
        logs: [],
        portfolioFilter: 0,
        pendingCard: null,
        pendingDebt: null,
        pendingTurnContinuation: null,
        auction: null,
        aiTimer: null,
        winnerIdx: null,
        winReason: '',
    };

    // --- Sound Engine (Web Audio API) ---
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

    function playTone(freq, duration, type = 'sine', vol = 0.08, delay = 0) {
        const ctx = getAudioCtx();
        if (!ctx) return;
        try {
            const t = ctx.currentTime + delay;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, t);
            gain.gain.setValueAtTime(vol, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(t);
            osc.stop(t + duration);
        } catch (e) {}
    }

    function sfxDice() {
        playTone(220, 0.06, 'triangle', 0.09, 0);
        playTone(340, 0.06, 'triangle', 0.08, 0.07);
        playTone(280, 0.07, 'triangle', 0.08, 0.14);
    }
    function sfxDiceTick(stepIdx) {
        const pitch = 240 + ((stepIdx * 37) % 140);
        playTone(pitch, 0.045, 'triangle', 0.05, 0);
    }
    function sfxDiceLand(isDoubles) {
        playTone(isDoubles ? 523 : 392, 0.09, 'sine', 0.08, 0);
        playTone(isDoubles ? 659 : 494, 0.12, 'sine', 0.08, 0.07);
    }
    function sfxStep(stepNum, totalSteps) {
        const ratio = totalSteps > 0 ? (stepNum / totalSteps) : 0;
        const freq = 340 + Math.round(ratio * 220);
        playTone(freq, 0.055, 'sine', 0.055, 0);
    }
    function sfxArrive() {
        playTone(587, 0.08, 'triangle', 0.065, 0);
        playTone(784, 0.12, 'triangle', 0.065, 0.06);
    }
    function sfxCashIn() {
        playTone(660, 0.09, 'sine', 0.07, 0);
        playTone(880, 0.14, 'sine', 0.08, 0.08);
    }
    function sfxCashOut() {
        playTone(330, 0.1, 'triangle', 0.07, 0);
        playTone(220, 0.14, 'triangle', 0.07, 0.08);
    }
    function sfxRentPay() {
        playTone(1046, 0.07, 'sine', 0.075, 0);
        playTone(784, 0.08, 'triangle', 0.07, 0.06);
        playTone(659, 0.1, 'triangle', 0.07, 0.13);
        playTone(523, 0.15, 'sine', 0.075, 0.2);
    }
    function sfxBuyProperty() {
        playTone(196, 0.07, 'triangle', 0.09, 0);
        playTone(523, 0.09, 'sine', 0.08, 0.06);
        playTone(659, 0.09, 'sine', 0.08, 0.13);
        playTone(784, 0.16, 'sine', 0.085, 0.2);
    }
    function sfxTradeSuccess() {
        playTone(523, 0.08, 'sine', 0.07, 0);
        playTone(659, 0.08, 'sine', 0.07, 0.06);
        playTone(587, 0.08, 'sine', 0.07, 0.13);
        playTone(784, 0.16, 'sine', 0.08, 0.2);
    }
    function sfxMortgageCash() {
        playTone(440, 0.07, 'triangle', 0.07, 0);
        playTone(660, 0.09, 'sine', 0.075, 0.07);
        playTone(880, 0.14, 'sine', 0.075, 0.15);
    }
    function sfxBuild() {
        playTone(440, 0.08, 'square', 0.04, 0);
        playTone(554, 0.12, 'square', 0.04, 0.08);
    }
    function sfxJail() {
        playTone(480, 0.16, 'sawtooth', 0.05, 0);
        playTone(340, 0.22, 'sawtooth', 0.05, 0.16);
    }

    // --- Formatting & Pure Helpers ---
    function formatRp(amount) {
        const val = Math.round(Number(amount) || 0);
        const sign = val < 0 ? '-' : '';
        return sign + 'Rp ' + Math.abs(val).toLocaleString('id-ID');
    }

    function formatShortRp(amount) {
        const abs = Math.abs(amount);
        if (abs >= 1000000) {
            const jt = abs / 1000000;
            return 'Rp ' + (Number.isInteger(jt) ? jt : jt.toFixed(2).replace(/\.?0+$/, '').replace('.', ',')) + 'jt';
        }
        return 'Rp ' + Math.round(abs / 1000) + 'rb';
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function shuffle(arr) {
        const copy = arr.slice();
        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }
        return copy;
    }

    function getGroupTiles(group) {
        return BOARD_TILES.filter(t => t.type === 'city' && t.group === group);
    }

    function getOwnedPropertyIndices(playerIdx) {
        return Object.keys(state.ownership)
            .map(Number)
            .filter(idx => state.ownership[idx] && state.ownership[idx].owner === playerIdx)
            .sort((a, b) => a - b);
    }

    function hasFullColorGroup(playerIdx, group) {
        const tiles = getGroupTiles(group);
        return tiles.length > 0 && tiles.every(t => state.ownership[t.idx] && state.ownership[t.idx].owner === playerIdx);
    }

    function countOwnedByType(playerIdx, type) {
        return BOARD_TILES.filter(t => t.type === type && state.ownership[t.idx] && state.ownership[t.idx].owner === playerIdx).length;
    }

    function getUnmortgageCost(tile) {
        return Math.round(tile.mortgage * 1.1);
    }

    function calculateNetWorth(playerIdx) {
        const p = state.players[playerIdx];
        if (!p || p.bankrupt) return 0;
        let total = p.cash;
        getOwnedPropertyIndices(playerIdx).forEach(idx => {
            const tile = BOARD_TILES[idx];
            const own = state.ownership[idx];
            total += own.mortgaged ? tile.mortgage : tile.price;
            if (tile.type === 'city' && own.level > 0) {
                total += own.level * tile.buildCost;
            }
        });
        return total;
    }

    function calculateIncomeTax(playerIdx) {
        const nw = Math.max(0, calculateNetWorth(playerIdx));
        return Math.max(0, Math.round((nw * 0.10) / 10000) * 10000);
    }

    function calculateRent(tileIdx, diceSum, multiplier = 1) {
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        if (!own || own.mortgaged) return 0;
        const owner = state.players[own.owner];
        if (!owner || owner.bankrupt) return 0;

        if (tile.type === 'city') {
            if (own.level > 0) {
                return tile.rents[own.level];
            }
            const mono = hasFullColorGroup(own.owner, tile.group);
            return mono ? tile.rents[0] * 2 : tile.rents[0];
        }
        if (tile.type === 'transport') {
            const count = countOwnedByType(own.owner, 'transport');
            return (250000 * Math.pow(2, Math.max(0, count - 1))) * multiplier;
        }
        if (tile.type === 'utility') {
            if (multiplier >= 10) {
                return diceSum * 100000;
            }
            const count = countOwnedByType(own.owner, 'utility');
            return diceSum * (count >= 2 ? 100000 : 40000);
        }
        return 0;
    }

    function canBuildOn(playerIdx, tileIdx) {
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        const p = state.players[playerIdx];
        if (!p || p.bankrupt || !tile || tile.type !== 'city' || !own || own.owner !== playerIdx) {
            return { ok: false, reason: 'Bukan pemilik kota ini' };
        }
        if (!hasFullColorGroup(playerIdx, tile.group)) {
            return { ok: false, reason: 'Harus memiliki seluruh kota warna ' + COLOR_GROUP_LABELS[tile.group] };
        }
        const groupTiles = getGroupTiles(tile.group);
        if (groupTiles.some(gt => state.ownership[gt.idx].mortgaged)) {
            return { ok: false, reason: 'Tidak bisa bangun saat ada kota sewarna yang dihipotek' };
        }
        if (own.level >= 5) {
            return { ok: false, reason: 'Sudah maksimal (1 Hotel)' };
        }
        const minLevel = Math.min(...groupTiles.map(gt => state.ownership[gt.idx].level));
        if (own.level > minLevel) {
            return { ok: false, reason: 'Aturan Bangun Merata: bangun kota sewarna lain lebih dulu' };
        }
        if (own.level < 4 && state.bankHouses < 1) {
            return { ok: false, reason: 'Stok Rumah Bank habis (maks 32)' };
        }
        if (own.level === 4 && state.bankHotels < 1) {
            return { ok: false, reason: 'Stok Hotel Bank habis (maks 12)' };
        }
        if (p.cash < tile.buildCost) {
            return { ok: false, reason: 'Kas tidak mencukupi (' + formatRp(tile.buildCost) + ')' };
        }
        return { ok: true, reason: own.level === 4 ? 'Bangun Hotel' : 'Bangun Rumah ke-' + (own.level + 1) };
    }

    function canSellBuildingOn(playerIdx, tileIdx) {
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        if (!tile || tile.type !== 'city' || !own || own.owner !== playerIdx || own.level <= 0) {
            return { ok: false, reason: 'Tidak ada bangunan' };
        }
        const groupTiles = getGroupTiles(tile.group);
        const maxLevel = Math.max(...groupTiles.map(gt => state.ownership[gt.idx].level));
        if (own.level < maxLevel) {
            return { ok: false, reason: 'Aturan Jual Merata: jual bangunan tertinggi di warna ini dulu' };
        }
        return { ok: true, refund: Math.round(tile.buildCost * 0.5) };
    }

    function canMortgageOn(playerIdx, tileIdx) {
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        if (!tile || !own || own.owner !== playerIdx || own.mortgaged) {
            return { ok: false, reason: 'Sudah dihipotek atau bukan milik Anda' };
        }
        if (tile.type === 'city') {
            const groupTiles = getGroupTiles(tile.group);
            if (groupTiles.some(gt => state.ownership[gt.idx] && state.ownership[gt.idx].level > 0)) {
                return { ok: false, reason: 'Jual semua bangunan di warna ' + COLOR_GROUP_LABELS[tile.group] + ' lebih dulu' };
            }
        }
        return { ok: true, amount: tile.mortgage };
    }

    function canUnmortgageOn(playerIdx, tileIdx) {
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        const p = state.players[playerIdx];
        if (!tile || !own || own.owner !== playerIdx || !own.mortgaged) {
            return { ok: false, reason: 'Tidak sedang dihipotek' };
        }
        const cost = getUnmortgageCost(tile);
        if (p.cash < cost) {
            return { ok: false, reason: 'Kas kurang (butuh ' + formatRp(cost) + ')' };
        }
        return { ok: true, cost };
    }

    function canHumanManageAssets() {
        if (!state.started || state.finished || state.rollingAnim || state.movingAnim) return false;
        if (state.phase === 'debt' && state.pendingDebt && state.pendingDebt.debtorIdx === 0) return true;
        return state.currentTurn === 0 && ['need_roll', 'jail_choice', 'buy_or_auction', 'auction', 'manage', 'debt'].includes(state.phase);
    }

    function buildHouseAction(playerIdx, tileIdx) {
        const check = canBuildOn(playerIdx, tileIdx);
        if (!check.ok) return false;
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        const p = state.players[playerIdx];
        p.cash -= tile.buildCost;
        if (own.level === 4) {
            state.bankHouses += 4;
            state.bankHotels -= 1;
            own.level = 5;
            addLog(`${p.name} membangun HOTEL di ${tile.name} (${formatRp(tile.buildCost)}).`, 'build');
        } else {
            state.bankHouses -= 1;
            own.level += 1;
            addLog(`${p.name} membangun Rumah ke-${own.level} di ${tile.name} (${formatRp(tile.buildCost)}).`, 'build');
        }
        sfxBuild();
        triggerTransactionAnim({
            kind: 'buy',
            title: own.level === 5 ? 'Bangun Hotel!' : 'Bangun Rumah!',
            subtitle: `${p.name} • ${tile.name}`,
            amountText: `-${formatRp(tile.buildCost)}`,
            tagText: own.level === 5 ? '🏨 HOTEL' : `🏠 R-${own.level}`,
            tileIndices: [tileIdx],
            deltas: { [playerIdx]: -tile.buildCost },
        });
        saveGameState();
        renderAll();
        return true;
    }

    function sellBuildingAction(playerIdx, tileIdx) {
        const check = canSellBuildingOn(playerIdx, tileIdx);
        if (!check.ok) return false;
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        const p = state.players[playerIdx];
        p.cash += check.refund;
        if (own.level === 5) {
            state.bankHotels += 1;
            const housesReturned = Math.min(4, state.bankHouses);
            state.bankHouses -= housesReturned;
            own.level = housesReturned;
            addLog(`${p.name} menjual Hotel di ${tile.name} (+${formatRp(check.refund)}).`, 'cash');
        } else {
            own.level -= 1;
            state.bankHouses += 1;
            addLog(`${p.name} menjual 1 Rumah di ${tile.name} (+${formatRp(check.refund)}).`, 'cash');
        }
        sfxMortgageCash();
        triggerTransactionAnim({
            kind: 'mortgage',
            title: 'Jual Bangunan',
            subtitle: `${p.name} • ${tile.name}`,
            amountText: `+${formatRp(check.refund)}`,
            tagText: `+${formatShortRp(check.refund)}`,
            tileIndices: [tileIdx],
            deltas: { [playerIdx]: check.refund },
        });
        saveGameState();
        renderAll();
        if (state.phase === 'auction' && state.auction) renderAuctionModal();
        return true;
    }

    function mortgageAction(playerIdx, tileIdx) {
        const check = canMortgageOn(playerIdx, tileIdx);
        if (!check.ok) return false;
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        const p = state.players[playerIdx];
        own.mortgaged = true;
        p.cash += tile.mortgage;
        sfxMortgageCash();
        triggerTransactionAnim({
            kind: 'mortgage',
            title: 'Properti Dihipotekkan',
            subtitle: `${p.name} menggadaikan ${tile.name}`,
            amountText: `+${formatRp(tile.mortgage)}`,
            tagText: '🏦 HIPOTEK',
            tileIndices: [tileIdx],
            deltas: { [playerIdx]: tile.mortgage },
        });
        addLog(`${p.name} menghipotekkan ${tile.name} (+${formatRp(tile.mortgage)}).`, 'cash');
        saveGameState();
        renderAll();
        if (state.phase === 'auction' && state.auction) renderAuctionModal();
        return true;
    }

    function unmortgageAction(playerIdx, tileIdx) {
        const check = canUnmortgageOn(playerIdx, tileIdx);
        if (!check.ok) return false;
        const tile = BOARD_TILES[tileIdx];
        const own = state.ownership[tileIdx];
        const p = state.players[playerIdx];
        p.cash -= check.cost;
        own.mortgaged = false;
        sfxMortgageCash();
        triggerTransactionAnim({
            kind: 'mortgage',
            title: 'Hipotek Ditebus',
            subtitle: `${p.name} menebus ${tile.name}`,
            amountText: `-${formatRp(check.cost)}`,
            tagText: '✅ TEBUS',
            tileIndices: [tileIdx],
            deltas: { [playerIdx]: -check.cost },
        });
        addLog(`${p.name} menebus hipotek ${tile.name} (-${formatRp(check.cost)}).`, 'cash');
        saveGameState();
        renderAll();
        if (state.phase === 'auction' && state.auction) renderAuctionModal();
        return true;
    }

    // --- Stats & Save Persistence ---
    function loadPrefs() {
        try {
            const raw = localStorage.getItem(PREFS_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                prefs = { ...prefs, ...parsed };
            }
        } catch (e) {}
    }

    function savePrefs() {
        try {
            localStorage.setItem(PREFS_KEY, JSON.stringify(prefs));
        } catch (e) {}
    }

    function loadStats() {
        try {
            const raw = localStorage.getItem(STATS_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) {}
        return {
            easy: { wins: 0, losses: 0 },
            normal: { wins: 0, losses: 0 },
            hard: { wins: 0, losses: 0 },
        };
    }

    function recordMatchStats(didHumanWin) {
        const stats = loadStats();
        const key = stats[state.difficulty] ? state.difficulty : 'normal';
        if (didHumanWin) stats[key].wins += 1;
        else stats[key].losses += 1;
        try {
            localStorage.setItem(STATS_KEY, JSON.stringify(stats));
        } catch (e) {}
        renderStats();
    }

    function saveGameState() {
        if (!state.started) return;
        if (state.finished) {
            try { localStorage.removeItem(SAVE_KEY); } catch (e) {}
            return;
        }
        try {
            const snapshot = {
                difficulty: state.difficulty,
                winMode: state.winMode,
                maxRounds: state.maxRounds,
                auctionEnabled: state.auctionEnabled,
                roundNumber: state.roundNumber,
                currentTurn: state.currentTurn,
                doublesCount: state.doublesCount,
                phase: (state.phase === 'rolling' || state.phase === 'moving') ? 'need_roll' : state.phase,
                lastDice: state.lastDice,
                bankHouses: state.bankHouses,
                bankHotels: state.bankHotels,
                players: state.players,
                ownership: state.ownership,
                communityDeck: state.communityDeck,
                chanceDeck: state.chanceDeck,
                logs: state.logs.slice(0, 40),
                pendingDebt: state.pendingDebt,
                pendingTurnContinuation: state.pendingTurnContinuation,
                pendingCard: state.pendingCard,
                savedAt: Date.now(),
            };
            localStorage.setItem(SAVE_KEY, JSON.stringify(snapshot));
        } catch (e) {}
    }

    function hasSavedGame() {
        try {
            const raw = localStorage.getItem(SAVE_KEY);
            if (!raw) return false;
            const parsed = JSON.parse(raw);
            return parsed && Array.isArray(parsed.players) && parsed.players.length >= 2;
        } catch (e) {
            return false;
        }
    }

    function restoreSavedGame() {
        try {
            const raw = localStorage.getItem(SAVE_KEY);
            if (!raw) return false;
            const snap = JSON.parse(raw);
            if (!snap || !Array.isArray(snap.players)) return false;

            clearAiTimer();
            state.started = true;
            state.hasMoved = true;
            state.finished = false;
            state.difficulty = snap.difficulty || 'normal';
            state.winMode = snap.winMode || 'classic';
            state.maxRounds = snap.maxRounds || 0;
            state.auctionEnabled = snap.auctionEnabled !== false;
            state.roundNumber = snap.roundNumber || 1;
            state.currentTurn = snap.currentTurn || 0;
            state.doublesCount = snap.doublesCount || 0;
            state.phase = snap.phase === 'auction' ? 'manage' : (snap.phase || 'need_roll');
            state.lastDice = snap.lastDice || [1, 6];
            state.bankHouses = snap.bankHouses ?? INITIAL_BANK_HOUSES;
            state.bankHotels = snap.bankHotels ?? INITIAL_BANK_HOTELS;
            state.players = snap.players;
            state.ownership = snap.ownership || {};
            state.communityDeck = snap.communityDeck || shuffle(COMMUNITY_CARDS);
            state.chanceDeck = snap.chanceDeck || shuffle(CHANCE_CARDS);
            state.logs = snap.logs || [];
            state.pendingDebt = snap.pendingDebt || null;
            state.pendingTurnContinuation = snap.pendingTurnContinuation || null;
            state.pendingCard = snap.pendingCard || null;
            state.auction = null;
            state.rollingAnim = false;
            state.diceJustLanded = false;
            state.movingAnim = false;
            state.steppingTileIdx = null;
            state.landedTileIdx = null;
            state.stepTrail = [];
            state.stepProgress = null;
            state.activeTxAnim = null;
            state.playerCashDeltas = {};
            state.winnerIdx = null;
            state.winReason = '';

            prefs.difficulty = state.difficulty;
            prefs.winMode = state.winMode;
            prefs.auctionEnabled = state.auctionEnabled;
            prefs.playerCount = state.players.length;
            syncControlsUI();
            renderAll();

            if (state.pendingCard) {
                showCardModal(state.pendingCard.deckType, state.pendingCard.card);
            } else {
                scheduleAiIfNeeded();
            }
            return true;
        } catch (e) {
            return false;
        }
    }

    // --- Logging ---
    function addLog(msg, kind = 'info') {
        const roundTag = state.started ? `[P${state.roundNumber}] ` : '';
        state.logs.unshift({ text: roundTag + msg, kind });
        if (state.logs.length > 60) state.logs.length = 60;
    }

    function scheduleAnimTimeout(fn, delay) {
        const id = setTimeout(() => {
            state.animTimers = state.animTimers.filter(t => t !== id);
            fn();
        }, delay);
        state.animTimers.push(id);
        return id;
    }

    function clearAiTimer() {
        if (state.aiTimer) {
            clearTimeout(state.aiTimer);
            state.aiTimer = null;
        }
        if (state.txClearTimer) {
            clearTimeout(state.txClearTimer);
            state.txClearTimer = null;
        }
        if (Array.isArray(state.animTimers) && state.animTimers.length > 0) {
            state.animTimers.forEach(t => clearTimeout(t));
            state.animTimers = [];
        }
        state.rollingAnim = false;
        state.diceJustLanded = false;
        state.movingAnim = false;
        state.steppingTileIdx = null;
        state.landedTileIdx = null;
        state.stepTrail = [];
        state.stepProgress = null;
        state.activeTxAnim = null;
        state.playerCashDeltas = {};
    }

    function triggerTransactionAnim({ kind, title, subtitle = '', amountText = '', tagText = '', tileIndices = [], deltas = {}, durationMs = 2100 }) {
        if (state.txClearTimer) {
            clearTimeout(state.txClearTimer);
            state.txClearTimer = null;
        }
        state.activeTxAnim = {
            kind,
            title,
            subtitle,
            amountText,
            tagText,
            tileIndices: Array.isArray(tileIndices) ? tileIndices : [],
        };
        state.playerCashDeltas = deltas || {};
        state.txClearTimer = setTimeout(() => {
            state.txClearTimer = null;
            state.activeTxAnim = null;
            state.playerCashDeltas = {};
            renderBoard();
            renderPlayers();
        }, durationMs);
    }

    // --- Game Lifecycle ---
    function startNewMatch(opts = {}) {
        const silent = Boolean(opts && opts.silent);
        clearAiTimer();
        closeAllModals();

        const winMode = prefs.winMode || 'classic';
        let maxRounds = 0;
        if (winMode === 'rounds_20') maxRounds = 20;
        else if (winMode === 'rounds_30') maxRounds = 30;
        else if (winMode === 'rounds_40') maxRounds = 40;

        const players = [{
            id: 0,
            name: 'Anda (Admin)',
            isHuman: true,
            token: prefs.humanToken || '🏯',
            color: HUMAN_COLOR,
            pos: 0,
            cash: INITIAL_CASH,
            inJail: false,
            jailTurns: 0,
            jailFreeCards: 0,
            bankrupt: false,
        }];

        const aiTokensAvailable = AI_ROSTER.map(a => a.token === prefs.humanToken ? '🦁' : a.token);
        for (let i = 0; i < prefs.playerCount - 1; i++) {
            const template = AI_ROSTER[i];
            players.push({
                id: i + 1,
                name: template.name,
                isHuman: false,
                token: aiTokensAvailable[i],
                color: template.color,
                pos: 0,
                cash: INITIAL_CASH,
                inJail: false,
                jailTurns: 0,
                jailFreeCards: 0,
                bankrupt: false,
            });
        }

        state.started = true;
        state.hasMoved = !silent;
        state.finished = false;
        state.difficulty = prefs.difficulty;
        state.winMode = winMode;
        state.maxRounds = maxRounds;
        state.auctionEnabled = Boolean(prefs.auctionEnabled);
        state.roundNumber = 1;
        state.currentTurn = 0;
        state.doublesCount = 0;
        state.phase = 'need_roll';
        state.lastDice = [3, 4];
        state.rollingAnim = false;
        state.diceJustLanded = false;
        state.movingAnim = false;
        state.steppingTileIdx = null;
        state.landedTileIdx = null;
        state.stepTrail = [];
        state.stepProgress = null;
        state.bankHouses = INITIAL_BANK_HOUSES;
        state.bankHotels = INITIAL_BANK_HOTELS;
        state.players = players;
        state.ownership = {};
        state.communityDeck = shuffle(COMMUNITY_CARDS);
        state.chanceDeck = shuffle(CHANCE_CARDS);
        state.logs = [];
        state.portfolioFilter = 0;
        state.pendingCard = null;
        state.pendingDebt = null;
        state.pendingTurnContinuation = null;
        state.auction = null;
        state.winnerIdx = null;
        state.winReason = '';

        addLog(`Meja siap (${players.length} pemain, Level ${DIFFICULTY_LABELS[state.difficulty]}). Modal awal ${formatRp(INITIAL_CASH)} — silakan Lempar Dadu!`, 'start');
        if (!silent) {
            sfxCashIn();
            saveGameState();
        }
        syncControlsUI();
        renderAll();
    }

    function sendToJail(playerIdx, reasonText) {
        const p = state.players[playerIdx];
        p.pos = 10;
        p.inJail = true;
        p.jailTurns = 0;
        state.doublesCount = 0;
        sfxJail();
        addLog(`${p.name} masuk LP Nusakambangan (${reasonText})!`, 'jail');
    }

    function checkWinConditions() {
        const alive = state.players.filter(p => !p.bankrupt);
        if (alive.length <= 1) {
            finishGame(alive[0] ? alive[0].id : 0, 'Seluruh lawan bangkrut (Kemenangan Mutlak)');
            return true;
        }
        if (state.maxRounds > 0 && state.roundNumber > state.maxRounds) {
            const ranked = alive.slice().sort((a, b) => calculateNetWorth(b.id) - calculateNetWorth(a.id));
            finishGame(ranked[0].id, `Batas ${state.maxRounds} Putaran tercapai — Kekayaan Bersih Tertinggi`);
            return true;
        }
        return false;
    }

    function finishGame(winnerIdx, reason) {
        clearAiTimer();
        state.finished = true;
        state.phase = 'finished';
        state.winnerIdx = winnerIdx;
        state.winReason = reason;
        try { localStorage.removeItem(SAVE_KEY); } catch (e) {}

        recordMatchStats(winnerIdx === 0);
        if (winnerIdx === 0) sfxCashIn();
        else sfxCashOut();

        syncControlsUI();
        renderAll();
        showEndModal();
    }

    // --- Debt, Liquidation & Bankruptcy ---
    function chargePlayer(debtorIdx, creditorIdx, amount, reason, nextAction, splitEachAmount = 0) {
        const debtor = state.players[debtorIdx];
        if (!debtor || debtor.bankrupt) return;

        if (amount <= 0) {
            continueAfterCharge(nextAction);
            return;
        }

        const payoutCreditors = () => {
            if (splitEachAmount > 0) {
                state.players.forEach(o => {
                    if (o.id !== debtorIdx && !o.bankrupt) o.cash += splitEachAmount;
                });
            } else if (creditorIdx !== null && state.players[creditorIdx] && !state.players[creditorIdx].bankrupt) {
                state.players[creditorIdx].cash += amount;
            }
        };

        const fireChargeTxAnim = () => {
            const deltas = { [debtorIdx]: -amount };
            if (splitEachAmount > 0) {
                state.players.forEach(o => {
                    if (o.id !== debtorIdx && !o.bankrupt) deltas[o.id] = splitEachAmount;
                });
            } else if (creditorIdx !== null && state.players[creditorIdx] && !state.players[creditorIdx].bankrupt) {
                deltas[creditorIdx] = amount;
            }
            const tileIndices = (creditorIdx !== null && state.ownership[debtor.pos] && state.ownership[debtor.pos].owner === creditorIdx)
                ? [debtor.pos]
                : [];
            triggerTransactionAnim({
                kind: 'rent',
                title: creditorIdx !== null ? 'Pembayaran Sewa!' : 'Pembayaran Biaya',
                subtitle: `${debtor.name}${creditorIdx !== null ? ' ➔ ' + state.players[creditorIdx].name : ''}`,
                amountText: `-${formatRp(amount)}`,
                tagText: `💸 -${formatShortRp(amount)}`,
                tileIndices,
                deltas,
            });
        };

        if (debtor.cash >= amount) {
            debtor.cash -= amount;
            payoutCreditors();
            sfxRentPay();
            fireChargeTxAnim();
            addLog(`${debtor.name} membayar ${formatRp(amount)} (${reason}).`, 'cash');
            continueAfterCharge(nextAction);
            return;
        }

        state.pendingDebt = { debtorIdx, creditorIdx, amount, reason, nextAction, splitEachAmount };

        if (!debtor.isHuman) {
            const settled = aiAutoLiquidateForDebt(debtorIdx, amount);
            if (settled) {
                debtor.cash -= amount;
                payoutCreditors();
                state.pendingDebt = null;
                sfxRentPay();
                fireChargeTxAnim();
                addLog(`${debtor.name} melikuidasi aset dan membayar ${formatRp(amount)} (${reason}).`, 'cash');
                continueAfterCharge(nextAction);
            } else {
                state.pendingDebt = null;
                declareBankruptcy(debtorIdx, creditorIdx, reason);
            }
            return;
        }

        state.phase = 'debt';
        addLog(`PERINGATAN KAS: Anda wajib membayar ${formatRp(amount)} (${reason}). Jual bangunan/hipotek aset!`, 'jail');
        saveGameState();
        renderAll();
    }

    function continueAfterCharge(nextAction) {
        if (checkWinConditions()) return;

        const p = state.players[state.currentTurn];
        if (!p || p.bankrupt) {
            advanceTurn();
            return;
        }

        if (nextAction === 'after_jail_bail_move') {
            p.inJail = false;
            p.jailTurns = 0;
            const steps = state.lastDice[0] + state.lastDice[1];
            movePlayerBySteps(state.currentTurn, steps, true, false);
            return;
        }

        if (nextAction === 'after_land') {
            finalizeLandingStep();
            return;
        }

        saveGameState();
        renderAll();
        scheduleAiIfNeeded();
    }

    function aiAutoLiquidateForDebt(playerIdx, neededAmount) {
        const p = state.players[playerIdx];
        // 1. Jual bangunan merata dari kota non-strategis/murah terlebih dahulu
        let guard = 0;
        while (p.cash < neededAmount && guard < 100) {
            guard++;
            const sellCandidates = getOwnedPropertyIndices(playerIdx)
                .filter(idx => canSellBuildingOn(playerIdx, idx).ok)
                .sort((a, b) => BOARD_TILES[a].buildCost - BOARD_TILES[b].buildCost);
            if (sellCandidates.length > 0) {
                sellBuildingAction(playerIdx, sellCandidates[0]);
                continue;
            }
            const mortCandidates = getOwnedPropertyIndices(playerIdx)
                .filter(idx => canMortgageOn(playerIdx, idx).ok)
                .sort((a, b) => BOARD_TILES[a].price - BOARD_TILES[b].price);
            if (mortCandidates.length > 0) {
                mortgageAction(playerIdx, mortCandidates[0]);
                continue;
            }
            break;
        }
        return p.cash >= neededAmount;
    }

    function humanAutoLiquidateStep() {
        if (!state.pendingDebt || state.pendingDebt.debtorIdx !== 0) return;
        const ok = aiAutoLiquidateForDebt(0, state.pendingDebt.amount);
        if (ok) {
            settleHumanDebt();
        } else {
            renderAll();
        }
    }

    function settleHumanDebt() {
        const debt = state.pendingDebt;
        if (!debt || debt.debtorIdx !== 0) return;
        const human = state.players[0];
        if (human.cash < debt.amount) return;

        human.cash -= debt.amount;
        const deltas = { 0: -debt.amount };
        if (debt.splitEachAmount > 0) {
            state.players.forEach(o => {
                if (o.id !== 0 && !o.bankrupt) {
                    o.cash += debt.splitEachAmount;
                    deltas[o.id] = debt.splitEachAmount;
                }
            });
        } else if (debt.creditorIdx !== null && state.players[debt.creditorIdx]) {
            state.players[debt.creditorIdx].cash += debt.amount;
            deltas[debt.creditorIdx] = debt.amount;
        }
        state.pendingDebt = null;
        sfxRentPay();
        triggerTransactionAnim({
            kind: 'rent',
            title: 'Utang Sewa Lunas!',
            subtitle: `${human.name} melunasi ${debt.reason}`,
            amountText: `-${formatRp(debt.amount)}`,
            tagText: `💸 -${formatShortRp(debt.amount)}`,
            tileIndices: debt.creditorIdx !== null ? [human.pos] : [],
            deltas,
        });
        addLog(`Anda berhasil melunasi ${formatRp(debt.amount)} (${debt.reason}).`, 'cash');
        continueAfterCharge(debt.nextAction);
    }

    function declareBankruptcy(debtorIdx, creditorIdx, reason) {
        const debtor = state.players[debtorIdx];
        if (!debtor || debtor.bankrupt) return;

        // Jual seluruh bangunan dulu secara otomatis agar kas maksimal diserahkan/dikembalikan
        let safety = 0;
        while (safety < 80) {
            safety++;
            const bIdx = getOwnedPropertyIndices(debtorIdx).find(idx => canSellBuildingOn(debtorIdx, idx).ok);
            if (bIdx === undefined) break;
            const tile = BOARD_TILES[bIdx];
            const own = state.ownership[bIdx];
            debtor.cash += Math.round(tile.buildCost * 0.5);
            if (own.level === 5) {
                state.bankHotels += 1;
                const ret = Math.min(4, state.bankHouses);
                state.bankHouses -= ret;
                own.level = ret;
            } else {
                own.level -= 1;
                state.bankHouses += 1;
            }
        }

        const owned = getOwnedPropertyIndices(debtorIdx);
        if (creditorIdx !== null && state.players[creditorIdx] && !state.players[creditorIdx].bankrupt) {
            const creditor = state.players[creditorIdx];
            creditor.cash += Math.max(0, debtor.cash);
            creditor.jailFreeCards += debtor.jailFreeCards;
            owned.forEach(idx => {
                state.ownership[idx].owner = creditorIdx;
                state.ownership[idx].level = 0;
            });
            addLog(`${debtor.name} BANGKRUT (${reason})! Seluruh aset & kas diserahkan ke ${creditor.name}.`, 'jail');
        } else {
            owned.forEach(idx => {
                delete state.ownership[idx];
            });
            addLog(`${debtor.name} BANGKRUT kepada Bank (${reason})! Properti dikembalikan ke Bank.`, 'jail');
        }

        debtor.cash = 0;
        debtor.jailFreeCards = 0;
        debtor.inJail = false;
        debtor.bankrupt = true;
        state.pendingDebt = null;
        state.doublesCount = 0;
        sfxJail();

        if (checkWinConditions()) return;

        if (debtorIdx === state.currentTurn) {
            advanceTurn();
        } else {
            saveGameState();
            renderAll();
            scheduleAiIfNeeded();
        }
    }

    // --- Dice Roll & Movement ---
    function handleDiceClick() {
        if (!state.started || state.finished) {
            startNewMatch({ silent: false });
            playerRollDice();
            return;
        }
        if (state.currentTurn !== 0 || state.rollingAnim || state.movingAnim) return;
        if (state.phase === 'need_roll') {
            playerRollDice();
        } else if (state.phase === 'jail_choice') {
            handleJailRollDoubles(0);
        } else if (state.phase === 'manage') {
            addLog('Dadu giliran ini sudah dilempar. Klik "Selesai Giliran" atau kelola properti.', 'info');
            renderAll();
        }
    }

    function updateDiceFacesDom(d1, d2) {
        const el1 = document.getElementById('monoDie1');
        const el2 = document.getElementById('monoDie2');
        const isDbl = d1 === d2;
        if (el1) el1.innerHTML = buildDieCellsHtml(d1, isDbl);
        if (el2) el2.innerHTML = buildDieCellsHtml(d2, isDbl);
    }

    function animateDiceRoll(onResult) {
        const delays = [85, 85, 95, 105, 115, 130, 145, 165, 185, 200, 210]; // ~1520ms total roll
        if (state.txClearTimer) {
            clearTimeout(state.txClearTimer);
            state.txClearTimer = null;
        }
        state.activeTxAnim = null;
        state.playerCashDeltas = {};
        state.hasMoved = true;
        state.rollingAnim = true;
        state.diceJustLanded = false;
        state.phase = 'rolling';
        sfxDice();
        syncControlsUI();
        renderQuickActionBar();
        renderBoard();

        let step = 0;
        const runTick = () => {
            if (!state.started || state.finished || !state.rollingAnim) return;

            if (step < delays.length) {
                let r1 = Math.floor(Math.random() * 6) + 1;
                let r2 = Math.floor(Math.random() * 6) + 1;
                if (r1 === state.lastDice[0] && r2 === state.lastDice[1]) {
                    r1 = (r1 % 6) + 1;
                }
                state.lastDice = [r1, r2];
                sfxDiceTick(step);
                updateDiceFacesDom(r1, r2);
                const waitMs = delays[step];
                step += 1;
                scheduleAnimTimeout(runTick, waitMs);
                return;
            }

            // Dadu berhenti pada nilai final
            const d1 = Math.floor(Math.random() * 6) + 1;
            const d2 = Math.floor(Math.random() * 6) + 1;
            state.rollingAnim = false;
            state.diceJustLanded = true;
            state.lastDice = [d1, d2];
            sfxDiceLand(d1 === d2);
            renderBoard();
            renderQuickActionBar();

            // Jeda singkat setelah dadu berhenti agar angka jelas terbaca sebelum pion melangkah
            scheduleAnimTimeout(() => {
                state.diceJustLanded = false;
                onResult(d1, d2);
            }, 580);
        };

        scheduleAnimTimeout(runTick, delays[0]);
    }

    function playerRollDice() {
        if (state.phase !== 'need_roll' || state.rollingAnim || state.movingAnim) return;
        const p = state.players[state.currentTurn];
        if (!p || p.bankrupt) return;

        animateDiceRoll((d1, d2) => {
            if (!state.started || state.finished || p.bankrupt) return;
            const isDoubles = d1 === d2;
            addLog(`${p.name} melempar dadu: ${d1} + ${d2} = ${d1 + d2}${isDoubles ? ' (KEMBAR!)' : ''}.`, 'roll');

            if (isDoubles) {
                state.doublesCount += 1;
                if (state.doublesCount >= 3) {
                    sendToJail(state.currentTurn, '3× Dadu Kembar berturut-turut');
                    state.phase = 'manage';
                    saveGameState();
                    renderAll();
                    scheduleAiIfNeeded();
                    return;
                }
            } else {
                state.doublesCount = 0;
            }

            movePlayerBySteps(state.currentTurn, d1 + d2, true, isDoubles);
        });
    }

    function animateTokenPath(playerIdx, path, awardStart, overrideRentMult) {
        const p = state.players[playerIdx];
        if (!p || p.bankrupt) return;
        if (!path || path.length === 0) {
            resolveTileLanding(playerIdx, overrideRentMult);
            return;
        }

        const stepMs = path.length > 12 ? 95 : 175;
        state.movingAnim = true;
        state.phase = 'moving';
        state.stepTrail = [];
        state.landedTileIdx = null;

        let idx = 0;
        const doStep = () => {
            if (!state.started || state.finished || p.bankrupt) return;

            if (idx < path.length) {
                const prevSquare = p.pos;
                const nextSquare = path[idx];
                p.pos = nextSquare;

                if (idx > 0) {
                    state.stepTrail.push(prevSquare);
                    if (state.stepTrail.length > 4) state.stepTrail.shift();
                }
                state.steppingTileIdx = nextSquare;
                state.stepProgress = {
                    current: idx + 1,
                    total: path.length,
                    tileName: BOARD_TILES[nextSquare] ? BOARD_TILES[nextSquare].short : '-',
                };

                if (awardStart && nextSquare === 0) {
                    p.cash += START_SALARY;
                    sfxCashIn();
                    triggerTransactionAnim({
                        kind: 'cash',
                        title: 'Gaji Lewat START!',
                        subtitle: `${p.name} menerima gaji`,
                        amountText: `+${formatRp(START_SALARY)}`,
                        tagText: '+Rp 2jt',
                        tileIndices: [0],
                        deltas: { [playerIdx]: START_SALARY },
                        durationMs: 1700,
                    });
                    addLog(`${p.name} melewati/mendarat START dan menerima gaji ${formatRp(START_SALARY)}!`, 'cash');
                    renderPlayers();
                }

                sfxStep(idx + 1, path.length);
                renderBoard();
                renderQuickActionBar();

                idx += 1;
                scheduleAnimTimeout(doStep, idx < path.length ? stepMs : 190);
                return;
            }

            // Pion tiba di petak tujuan
            const dest = path[path.length - 1];
            state.movingAnim = false;
            state.steppingTileIdx = null;
            state.stepTrail = [];
            state.stepProgress = null;
            state.landedTileIdx = dest;
            sfxArrive();
            renderAll();

            scheduleAnimTimeout(() => {
                state.landedTileIdx = null;
                resolveTileLanding(playerIdx, overrideRentMult);
            }, 260);
        };

        doStep();
    }

    function movePlayerBySteps(playerIdx, steps, awardStart, hasDoublesBonus, overrideRentMult = 1) {
        const p = state.players[playerIdx];
        const path = [];
        const total = Math.abs(steps);
        const dir = steps >= 0 ? 1 : -1;
        let cursor = p.pos;
        for (let i = 0; i < total; i++) {
            cursor = ((cursor + dir) % 40 + 40) % 40;
            path.push(cursor);
        }

        state.pendingTurnContinuation = hasDoublesBonus && !p.inJail;
        animateTokenPath(playerIdx, path, awardStart && steps > 0, overrideRentMult);
    }

    function moveToTargetPos(playerIdx, targetPos, awardStart, overrideRentMult = 1) {
        const p = state.players[playerIdx];
        let steps = (targetPos - p.pos + 40) % 40;
        if (steps === 0) steps = 40;
        const path = [];
        let cursor = p.pos;
        for (let i = 0; i < steps; i++) {
            cursor = (cursor + 1) % 40;
            path.push(cursor);
        }
        animateTokenPath(playerIdx, path, Boolean(awardStart), overrideRentMult);
    }

    function resolveTileLanding(playerIdx, overrideRentMult = 1) {
        const p = state.players[playerIdx];
        const tile = BOARD_TILES[p.pos];
        const diceSum = state.lastDice[0] + state.lastDice[1];

        addLog(`${p.name} tiba di ${tile.name}.`, 'move');

        if (tile.type === 'start' || tile.type === 'jail' || tile.type === 'free_parking') {
            finalizeLandingStep();
            return;
        }

        if (tile.type === 'go_to_jail') {
            state.pendingTurnContinuation = false;
            sendToJail(playerIdx, 'Mendarat di petak Masuk Penjara');
            state.phase = 'manage';
            saveGameState();
            renderAll();
            scheduleAiIfNeeded();
            return;
        }

        if (tile.type === 'tax_income') {
            const tax = calculateIncomeTax(playerIdx);
            chargePlayer(playerIdx, null, tax, `PPh 10% Kekayaan (${formatRp(calculateNetWorth(playerIdx))})`, 'after_land');
            return;
        }

        if (tile.type === 'tax_luxury') {
            chargePlayer(playerIdx, null, tile.amount, 'Pajak Barang Mewah', 'after_land');
            return;
        }

        if (tile.type === 'community' || tile.type === 'chance') {
            drawEventCard(playerIdx, tile.type);
            return;
        }

        // Properti (kota, transportasi, utilitas)
        const own = state.ownership[tile.idx];
        if (!own) {
            if (p.isHuman) {
                state.phase = 'buy_or_auction';
                saveGameState();
                renderAll();
            } else {
                aiDecideUnownedProperty(playerIdx, tile);
            }
            return;
        }

        if (own.owner === playerIdx) {
            addLog(`${p.name} singgah di properti miliknya sendiri (${tile.name}).`, 'info');
            finalizeLandingStep();
            return;
        }

        if (own.mortgaged) {
            addLog(`${tile.name} sedang dihipotek — bebas biaya sewa!`, 'info');
            finalizeLandingStep();
            return;
        }

        if (overrideRentMult >= 10 && tile.type === 'utility') {
            const d1 = Math.floor(Math.random() * 6) + 1;
            const d2 = Math.floor(Math.random() * 6) + 1;
            state.lastDice = [d1, d2];
            const rent = (d1 + d2) * 100000;
            const ownerName = state.players[own.owner].name;
            chargePlayer(playerIdx, own.owner, rent, `Sewa khusus ${tile.name} (Dadu ${d1}+${d2}) ke ${ownerName}`, 'after_land');
            return;
        }

        const rent = calculateRent(tile.idx, diceSum, overrideRentMult);
        const ownerName = state.players[own.owner].name;
        chargePlayer(playerIdx, own.owner, rent, `Sewa ${tile.name} ke ${ownerName}`, 'after_land');
    }

    function finalizeLandingStep() {
        const p = state.players[state.currentTurn];
        if (!p || p.bankrupt) {
            advanceTurn();
            return;
        }
        if (state.pendingTurnContinuation && !p.inJail) {
            state.pendingTurnContinuation = false;
            state.phase = 'need_roll';
            addLog(`${p.name} mendapat giliran lempar ulang karena Dadu Kembar!`, 'roll');
        } else {
            state.pendingTurnContinuation = false;
            state.phase = 'manage';
        }
        saveGameState();
        renderAll();
        scheduleAiIfNeeded();
    }

    // --- Community Chest & Chance Cards ---
    function drawEventCard(playerIdx, deckType) {
        const deck = deckType === 'community' ? state.communityDeck : state.chanceDeck;
        if (deck.length === 0) {
            const base = deckType === 'community' ? COMMUNITY_CARDS : CHANCE_CARDS;
            const replenished = shuffle(base.filter(c => c.action !== 'jail_free'));
            if (deckType === 'community') state.communityDeck = replenished;
            else state.chanceDeck = replenished;
        }
        const activeDeck = deckType === 'community' ? state.communityDeck : state.chanceDeck;
        const card = activeDeck.shift();
        if (card.action !== 'jail_free') {
            activeDeck.push(card);
        }

        const p = state.players[playerIdx];
        addLog(`${p.name} mengambil kartu ${deckType === 'community' ? 'Dana Umum' : 'Kesempatan'}: "${card.title}".`, 'card');

        if (p.isHuman) {
            state.pendingCard = { deckType, card };
            state.phase = 'card_wait';
            saveGameState();
            renderAll();
            showCardModal(deckType, card);
        } else {
            executeEventCard(playerIdx, card);
        }
    }

    function executeEventCard(playerIdx, card) {
        const p = state.players[playerIdx];
        if (!p || p.bankrupt) return;

        switch (card.action) {
            case 'cash': {
                if (card.amount >= 0) {
                    p.cash += card.amount;
                    sfxCashIn();
                    triggerTransactionAnim({
                        kind: 'cash',
                        title: card.title,
                        subtitle: `${p.name} menerima kas`,
                        amountText: `+${formatRp(card.amount)}`,
                        deltas: { [playerIdx]: card.amount },
                    });
                    addLog(`${p.name} menerima ${formatRp(card.amount)} (${card.title}).`, 'cash');
                    finalizeLandingStep();
                } else {
                    chargePlayer(playerIdx, null, Math.abs(card.amount), card.title, 'after_land');
                }
                break;
            }
            case 'collect_each': {
                const others = state.players.filter(o => o.id !== playerIdx && !o.bankrupt);
                let totalReceived = 0;
                const deltas = {};
                others.forEach(o => {
                    const paid = Math.min(o.cash, card.amount);
                    o.cash -= paid;
                    totalReceived += paid;
                    deltas[o.id] = -paid;
                });
                p.cash += totalReceived;
                deltas[playerIdx] = totalReceived;
                sfxCashIn();
                triggerTransactionAnim({
                    kind: 'cash',
                    title: card.title,
                    subtitle: `${p.name} menerima dari semua pemain`,
                    amountText: `+${formatRp(totalReceived)}`,
                    deltas,
                });
                addLog(`${p.name} menerima total ${formatRp(totalReceived)} dari pemain lain.`, 'cash');
                finalizeLandingStep();
                break;
            }
            case 'pay_each': {
                const others = state.players.filter(o => o.id !== playerIdx && !o.bankrupt);
                const totalPay = others.length * card.amount;
                chargePlayer(playerIdx, null, totalPay, card.title + ` (${formatRp(card.amount)}/pemain)`, 'after_land', card.amount);
                break;
            }
            case 'repairs': {
                let houses = 0;
                let hotels = 0;
                getOwnedPropertyIndices(playerIdx).forEach(idx => {
                    const lvl = state.ownership[idx].level;
                    if (lvl === 5) hotels += 1;
                    else houses += lvl;
                });
                const cost = (houses * card.perHouse) + (hotels * card.perHotel);
                addLog(`${p.name} terkena biaya perbaikan (${houses} rumah, ${hotels} hotel) = ${formatRp(cost)}.`, 'cash');
                chargePlayer(playerIdx, null, cost, card.title, 'after_land');
                break;
            }
            case 'move_to': {
                moveToTargetPos(playerIdx, card.target, Boolean(card.awardStart), 1);
                break;
            }
            case 'move_back': {
                movePlayerBySteps(playerIdx, -card.steps, false, state.pendingTurnContinuation, 1);
                break;
            }
            case 'nearest_transport': {
                const transports = [5, 15, 25, 35];
                const nextT = transports.find(t => t > p.pos) ?? transports[0];
                moveToTargetPos(playerIdx, nextT, true, 2);
                break;
            }
            case 'nearest_utility': {
                const utils = [12, 28];
                const nextU = utils.find(u => u > p.pos) ?? utils[0];
                moveToTargetPos(playerIdx, nextU, true, 10);
                break;
            }
            case 'go_to_jail': {
                state.pendingTurnContinuation = false;
                sendToJail(playerIdx, 'Perintah Kartu');
                state.phase = 'manage';
                saveGameState();
                renderAll();
                scheduleAiIfNeeded();
                break;
            }
            case 'jail_free': {
                p.jailFreeCards += 1;
                sfxCashIn();
                addLog(`${p.name} menyimpan 1 Kartu Bebas Penjara!`, 'card');
                finalizeLandingStep();
                break;
            }
            default:
                finalizeLandingStep();
        }
    }

    // --- Purchase & Interactive Auction ---
    function buyPropertyForPlayer(playerIdx, tileIdx, price) {
        const p = state.players[playerIdx];
        const tile = BOARD_TILES[tileIdx];
        if (!p || p.bankrupt || state.ownership[tileIdx] || p.cash < price) return false;
        p.cash -= price;
        state.ownership[tileIdx] = { owner: playerIdx, level: 0, mortgaged: false };
        sfxBuyProperty();
        triggerTransactionAnim({
            kind: 'buy',
            title: 'Beli Properti Baru!',
            subtitle: `${p.name} membeli ${tile.name}`,
            amountText: `-${formatRp(price)}`,
            tagText: '📜 DIBELI!',
            tileIndices: [tileIdx],
            deltas: { [playerIdx]: -price },
        });
        addLog(`${p.name} membeli ${tile.name} seharga ${formatRp(price)}.`, 'buy');
        return true;
    }

    function humanBuyCurrentProperty() {
        if (state.phase !== 'buy_or_auction' || state.currentTurn !== 0) return;
        const p = state.players[0];
        const tile = BOARD_TILES[p.pos];
        if (p.cash < tile.price) return;
        buyPropertyForPlayer(0, tile.idx, tile.price);
        finalizeLandingStep();
    }

    function humanDeclineProperty() {
        if (state.phase !== 'buy_or_auction' || state.currentTurn !== 0) return;
        const p = state.players[0];
        const tile = BOARD_TILES[p.pos];
        if (state.auctionEnabled) {
            startAuction(tile.idx);
        } else {
            addLog(`Anda melewati pembelian ${tile.name}.`, 'info');
            finalizeLandingStep();
        }
    }

    function aiEvaluatePropertyMaxBid(aiIdx, tileIdx) {
        const ai = state.players[aiIdx];
        const tile = BOARD_TILES[tileIdx];
        const diff = state.difficulty;
        const weight = STRATEGIC_PRIORITY[tileIdx] || 1.0;

        let completesOwnSet = false;
        let blocksOtherSet = false;
        if (tile.type === 'city') {
            const grp = getGroupTiles(tile.group);
            const ownInGroup = grp.filter(g => state.ownership[g.idx] && state.ownership[g.idx].owner === aiIdx).length;
            if (ownInGroup === grp.length - 1) completesOwnSet = true;

            state.players.forEach(other => {
                if (other.id !== aiIdx && !other.bankrupt) {
                    const otherOwned = grp.filter(g => state.ownership[g.idx] && state.ownership[g.idx].owner === other.id).length;
                    if (otherOwned === grp.length - 1) blocksOtherSet = true;
                }
            });
        }

        let mult = 1.0;
        let reserve = 1500000;
        if (diff === 'easy') {
            mult = completesOwnSet ? 1.05 : 0.9;
            reserve = 2800000;
        } else if (diff === 'normal') {
            mult = completesOwnSet ? 1.30 : (1.02 * weight);
            reserve = 1400000;
        } else {
            // Sulit: color blocking + ROI weight agresif
            mult = completesOwnSet ? 1.70 : (blocksOtherSet ? 1.48 : (1.12 * weight));
            reserve = completesOwnSet || blocksOtherSet ? 750000 : 1100000;
        }

        const capByVal = Math.round((tile.price * mult) / 50000) * 50000;
        const capByCash = Math.max(0, Math.floor((ai.cash - reserve) / 50000) * 50000);
        return Math.min(capByVal, capByCash);
    }

    function aiDecideUnownedProperty(aiIdx, tile) {
        const ai = state.players[aiIdx];
        const diff = state.difficulty;

        let shouldBuy = false;
        if (ai.cash >= tile.price) {
            const leftAfter = ai.cash - tile.price;
            if (diff === 'easy') {
                shouldBuy = leftAfter >= 2800000 || (tile.price <= 1400000 && leftAfter >= 1500000);
            } else if (diff === 'normal') {
                shouldBuy = leftAfter >= 1000000 || (tile.type !== 'utility' && leftAfter >= 600000);
            } else {
                // Sulit hampir selalu beli jika cukup uang, kecuali kas benar-benar tipis (<300rb) pada utilitas biasa
                shouldBuy = leftAfter >= 400000 || tile.type !== 'utility';
            }
        }

        if (shouldBuy) {
            buyPropertyForPlayer(aiIdx, tile.idx, tile.price);
            finalizeLandingStep();
            return;
        }

        if (state.auctionEnabled) {
            startAuction(tile.idx);
        } else {
            addLog(`${ai.name} memilih tidak membeli ${tile.name}.`, 'info');
            finalizeLandingStep();
        }
    }

    function startAuction(tileIdx) {
        const tile = BOARD_TILES[tileIdx];
        const activeBidders = state.players.filter(p => !p.bankrupt && p.cash >= 100000).map(p => p.id);
        if (activeBidders.length === 0) {
            addLog(`Lelang ${tile.name} dibatalkan karena tidak ada peserta berkas cukup.`, 'info');
            finalizeLandingStep();
            return;
        }

        state.phase = 'auction';
        state.auction = {
            tileIdx,
            bidders: activeBidders,
            turnPos: 0,
            currentBid: 0,
            highestBidder: null,
            aiMaxBids: {},
        };

        activeBidders.forEach(id => {
            if (!state.players[id].isHuman) {
                state.auction.aiMaxBids[id] = aiEvaluatePropertyMaxBid(id, tileIdx);
            }
        });

        addLog(`LELANG DIMULAI untuk ${tile.name} (Harga normal ${formatRp(tile.price)})!`, 'buy');
        renderAuctionModal();
        stepAuctionTurn();
    }

    function stepAuctionTurn() {
        const auc = state.auction;
        if (!auc) return;

        // Jika tinggal 1 peserta & sudah ada penawar tertinggi -> selesai!
        if (auc.bidders.length === 1 && auc.highestBidder !== null) {
            finishAuction(true);
            return;
        }
        if (auc.bidders.length === 0) {
            finishAuction(false);
            return;
        }

        if (auc.turnPos >= auc.bidders.length) auc.turnPos = 0;
        const bidderId = auc.bidders[auc.turnPos];
        const bidder = state.players[bidderId];

        if (bidderId === auc.highestBidder && auc.bidders.length === 1) {
            finishAuction(true);
            return;
        }

        if (bidder.isHuman) {
            renderAuctionModal();
            return;
        }

        // Giliran AI di lelang
        clearAiTimer();
        state.aiTimer = setTimeout(() => {
            if (!state.auction) return;
            const minNext = auc.currentBid === 0 ? 100000 : (auc.currentBid + 50000);
            const maxAllowed = auc.aiMaxBids[bidderId] || 0;
            if (bidderId !== auc.highestBidder && minNext <= maxAllowed && bidder.cash >= minNext) {
                const jump = (minNext + 100000 <= maxAllowed && state.difficulty !== 'easy') ? 100000 : 50000;
                auc.currentBid = auc.currentBid === 0 ? 100000 : Math.min(maxAllowed, auc.currentBid + jump);
                auc.highestBidder = bidderId;
                auc.turnPos = (auc.turnPos + 1) % auc.bidders.length;
                addLog(`[Lelang] ${bidder.name} menawar ${formatRp(auc.currentBid)}.`, 'buy');
            } else {
                auc.bidders.splice(auc.turnPos, 1);
                if (auc.bidders.length > 0) auc.turnPos = auc.turnPos % auc.bidders.length;
                addLog(`[Lelang] ${bidder.name} mundur.`, 'info');
            }
            renderAuctionModal();
            stepAuctionTurn();
        }, 320);
    }

    function humanAuctionBid(increment) {
        const auc = state.auction;
        if (!auc) return;
        const bidderId = auc.bidders[auc.turnPos];
        if (bidderId !== 0) return;

        const nextBid = auc.currentBid === 0 ? Math.max(100000, increment) : (auc.currentBid + increment);
        if (state.players[0].cash < nextBid) return;

        auc.currentBid = nextBid;
        auc.highestBidder = 0;
        auc.turnPos = (auc.turnPos + 1) % auc.bidders.length;
        sfxCashOut();
        addLog(`[Lelang] Anda menawar ${formatRp(auc.currentBid)}.`, 'buy');
        renderAuctionModal();
        stepAuctionTurn();
    }

    function humanAuctionPass() {
        const auc = state.auction;
        if (!auc) return;
        const bidderId = auc.bidders[auc.turnPos];
        if (bidderId !== 0) return;
        auc.bidders.splice(auc.turnPos, 1);
        if (auc.bidders.length > 0) auc.turnPos = auc.turnPos % auc.bidders.length;
        addLog(`[Lelang] Anda mundur dari lelang.`, 'info');
        renderAuctionModal();
        stepAuctionTurn();
    }

    function finishAuction(hasWinner) {
        clearAiTimer();
        const auc = state.auction;
        if (!auc) return;
        const tile = BOARD_TILES[auc.tileIdx];
        if (hasWinner && auc.highestBidder !== null && auc.currentBid > 0) {
            buyPropertyForPlayer(auc.highestBidder, tile.idx, auc.currentBid);
        } else {
            addLog(`Lelang ${tile.name} berakhir tanpa pemenang; properti tetap di Bank.`, 'info');
        }
        state.auction = null;
        closeModal('monoAuctionModal');
        finalizeLandingStep();
    }

    // --- Jail Actions ---
    function handleHumanJailPay() {
        if (state.phase !== 'jail_choice' || state.currentTurn !== 0) return;
        const p = state.players[0];
        if (p.cash < JAIL_BAIL) return;
        p.cash -= JAIL_BAIL;
        p.inJail = false;
        p.jailTurns = 0;
        state.doublesCount = 0;
        state.phase = 'need_roll';
        sfxCashOut();
        addLog(`Anda membayar tebusan Rp 500.000 dan bebas dari LP Nusakambangan!`, 'cash');
        saveGameState();
        renderAll();
    }

    function handleHumanJailFreeCard() {
        if (state.phase !== 'jail_choice' || state.currentTurn !== 0) return;
        const p = state.players[0];
        if (p.jailFreeCards < 1) return;
        p.jailFreeCards -= 1;
        p.inJail = false;
        p.jailTurns = 0;
        state.doublesCount = 0;
        state.phase = 'need_roll';
        state.chanceDeck.push(CHANCE_CARDS.find(c => c.action === 'jail_free'));
        sfxCashIn();
        addLog(`Anda menggunakan Kartu Bebas Penjara dan langsung bebas!`, 'card');
        saveGameState();
        renderAll();
    }

    function handleJailRollDoubles(playerIdx) {
        if (state.rollingAnim || state.movingAnim) return;
        const p = state.players[playerIdx];
        if (!p || p.bankrupt) return;

        animateDiceRoll((d1, d2) => {
            if (!state.started || state.finished || p.bankrupt) return;
            if (d1 === d2) {
                p.inJail = false;
                p.jailTurns = 0;
                state.doublesCount = 0;
                addLog(`${p.name} lempar dadu penjara: ${d1} + ${d2} KEMBAR! Bebas & langsung jalan!`, 'roll');
                movePlayerBySteps(playerIdx, d1 + d2, true, false);
                return;
            }

            p.jailTurns += 1;
            addLog(`${p.name} coba keluar penjara (${p.jailTurns}/3): dapat ${d1} + ${d2} (gagal kembar).`, 'jail');

            if (p.jailTurns >= 3) {
                addLog(`Gagal ke-3: ${p.name} wajib membayar denda ${formatRp(JAIL_BAIL)} dan jalan ${d1 + d2} petak.`, 'cash');
                chargePlayer(playerIdx, null, JAIL_BAIL, 'Denda wajib gagal ke-3 di Penjara', 'after_jail_bail_move');
                return;
            }

            state.phase = 'manage';
            saveGameState();
            renderAll();
            scheduleAiIfNeeded();
        });
    }

    // --- Turn Progression & AI Turn Logic ---
    function advanceTurn() {
        clearAiTimer();
        if (checkWinConditions()) return;

        let next = (state.currentTurn + 1) % state.players.length;
        let loopedRound = next <= state.currentTurn;
        let safety = 0;
        while (state.players[next].bankrupt && safety < state.players.length) {
            const prev = next;
            next = (next + 1) % state.players.length;
            if (next <= prev) loopedRound = true;
            safety++;
        }

        if (loopedRound) {
            state.roundNumber += 1;
            if (checkWinConditions()) return;
        }

        state.currentTurn = next;
        state.doublesCount = 0;
        state.pendingTurnContinuation = false;
        const active = state.players[next];
        state.phase = active.inJail ? 'jail_choice' : 'need_roll';

        saveGameState();
        renderAll();
        scheduleAiIfNeeded();
    }

    function scheduleAiIfNeeded() {
        clearAiTimer();
        if (!state.started || state.finished) return;
        const p = state.players[state.currentTurn];
        if (!p || p.isHuman || p.bankrupt) return;
        if (state.phase === 'auction' || state.phase === 'card_wait' || state.phase === 'debt' || state.phase === 'rolling' || state.phase === 'moving' || state.rollingAnim || state.movingAnim) return;

        state.aiTimer = setTimeout(() => {
            if (!state.started || state.finished) return;
            if (state.phase === 'jail_choice') {
                aiPlayJailTurn(state.currentTurn);
            } else if (state.phase === 'need_roll') {
                playerRollDice();
            } else if (state.phase === 'manage') {
                aiManagePropertiesAndEndTurn(state.currentTurn);
            }
        }, 620);
    }

    function aiPlayJailTurn(aiIdx) {
        const ai = state.players[aiIdx];
        const diff = state.difficulty;
        const unownedCount = BOARD_TILES.filter(t => (t.type === 'city' || t.type === 'transport' || t.type === 'utility') && !state.ownership[t.idx]).length;
        const totalHousesBuilt = Object.values(state.ownership).reduce((acc, o) => acc + (o.level || 0), 0);

        if (ai.jailFreeCards > 0) {
            // Sulit menahan kartu jika akhir game lebih aman di penjara
            const stayInJailLate = diff === 'hard' && totalHousesBuilt >= 10 && unownedCount <= 4;
            if (!stayInJailLate) {
                ai.jailFreeCards -= 1;
                ai.inJail = false;
                ai.jailTurns = 0;
                state.chanceDeck.push(CHANCE_CARDS.find(c => c.action === 'jail_free'));
                state.phase = 'need_roll';
                addLog(`${ai.name} memakai Kartu Bebas Penjara.`, 'card');
                renderAll();
                scheduleAiIfNeeded();
                return;
            }
        }

        let wantBail = false;
        if (ai.cash >= JAIL_BAIL + 800000) {
            if (diff === 'easy') wantBail = true;
            else if (diff === 'normal') wantBail = unownedCount >= 6 || state.roundNumber <= 12;
            else wantBail = unownedCount >= 7 && totalHousesBuilt < 8;
        }

        if (wantBail) {
            ai.cash -= JAIL_BAIL;
            ai.inJail = false;
            ai.jailTurns = 0;
            state.phase = 'need_roll';
            addLog(`${ai.name} membayar tebusan ${formatRp(JAIL_BAIL)} untuk bebas dari Penjara.`, 'cash');
            renderAll();
            scheduleAiIfNeeded();
        } else {
            handleJailRollDoubles(aiIdx);
        }
    }

    function aiManagePropertiesAndEndTurn(aiIdx) {
        const ai = state.players[aiIdx];
        const diff = state.difficulty;

        // 1. Tebus hipotek bila uang berlebih
        const mortOwned = getOwnedPropertyIndices(aiIdx).filter(idx => state.ownership[idx].mortgaged);
        mortOwned.forEach(idx => {
            const tile = BOARD_TILES[idx];
            const cost = getUnmortgageCost(tile);
            const reserve = diff === 'hard' ? 1800000 : 2500000;
            if (ai.cash >= cost + reserve) {
                unmortgageAction(aiIdx, idx);
            }
        });

        // 2. Bangun rumah sesuai level AI
        let reserve = 2500000;
        let maxTargetLevel = 3;
        if (diff === 'easy') {
            reserve = 5000000;
            maxTargetLevel = 2;
        } else if (diff === 'hard') {
            // Hitung ancaman sewa musuh tertinggi di papan
            let maxOppRent = 0;
            Object.keys(state.ownership).forEach(k => {
                const idx = Number(k);
                const own = state.ownership[idx];
                if (own.owner !== aiIdx && !own.mortgaged) {
                    maxOppRent = Math.max(maxOppRent, calculateRent(idx, 7));
                }
            });
            reserve = Math.min(4500000, Math.max(1200000, Math.round(maxOppRent * 0.7)));
            maxTargetLevel = 5;
        }

        let builds = 0;
        while (builds < 6) {
            const candidates = getOwnedPropertyIndices(aiIdx)
                .filter(idx => {
                    const check = canBuildOn(aiIdx, idx);
                    const lvl = state.ownership[idx].level;
                    return check.ok && lvl < maxTargetLevel && (ai.cash - BOARD_TILES[idx].buildCost) >= reserve;
                })
                .sort((a, b) => (STRATEGIC_PRIORITY[b] || 1) - (STRATEGIC_PRIORITY[a] || 1));

            if (candidates.length === 0) break;
            if (!buildHouseAction(aiIdx, candidates[0])) break;
            builds++;
        }

        advanceTurn();
    }

    // --- Trade / Barter AI Engine ---
    function evaluateTradeProposal(aiIdx, myProps, myCash, myJailCards, aiProps, aiCash, aiJailCards) {
        const ai = state.players[aiIdx];
        if (!ai || ai.bankrupt) return { accepted: false, reason: 'Pemain tidak aktif.' };
        if (aiCash > ai.cash) return { accepted: false, reason: `${ai.name} tidak memiliki kas tunai cukup.` };
        if (aiJailCards > ai.jailFreeCards) return { accepted: false, reason: `${ai.name} tidak memiliki kartu bebas penjara cukup.` };
        if (myProps.length === 0 && myCash === 0 && myJailCards === 0 && aiProps.length === 0 && aiCash === 0 && aiJailCards === 0) {
            return { accepted: false, reason: 'Pilih aset untuk ditukar terlebih dahulu.' };
        }

        const valueOf = (propIdxs, cash, jailCards, receiverIdx, isGivingAway) => {
            let v = cash + (jailCards * 450000);
            propIdxs.forEach(idx => {
                const tile = BOARD_TILES[idx];
                const own = state.ownership[idx];
                let base = own && own.mortgaged ? tile.mortgage : tile.price;
                let bonus = 1.0;
                if (tile.type === 'city') {
                    const grp = getGroupTiles(tile.group);
                    const receiverOwned = grp.filter(g => state.ownership[g.idx] && state.ownership[g.idx].owner === receiverIdx).length;
                    if (isGivingAway && receiverOwned === grp.length) {
                        bonus = state.difficulty === 'hard' ? 2.2 : 1.7;
                    } else if (!isGivingAway && receiverOwned === grp.length - 1) {
                        bonus = state.difficulty === 'hard' ? 1.55 : 1.35;
                    }
                    // Jika yang dilepas melengkapi set untuk lawan (Human) -> nilai risiko tinggi!
                    if (isGivingAway) {
                        const humanOwned = grp.filter(g => state.ownership[g.idx] && state.ownership[g.idx].owner === 0).length;
                        if (humanOwned >= grp.length - 1) {
                            bonus = Math.max(bonus, state.difficulty === 'hard' ? 2.1 : (state.difficulty === 'normal' ? 1.5 : 1.1));
                        }
                    }
                }
                v += base * bonus;
            });
            return Math.round(v);
        };

        const aiReceivesValue = valueOf(myProps, myCash, myJailCards, aiIdx, false);
        const aiGivesValue = valueOf(aiProps, aiCash, aiJailCards, aiIdx, true);

        const requiredRatio = state.difficulty === 'easy' ? 0.85 : (state.difficulty === 'normal' ? 1.05 : 1.28);
        const accepted = aiReceivesValue >= Math.round(aiGivesValue * requiredRatio);

        return {
            accepted,
            aiReceivesValue,
            aiGivesValue,
            requiredRatio,
            reason: accepted
                ? `${ai.name} menyetujui penawaran ini (Nilai masuk ${formatShortRp(aiReceivesValue)} vs keluar ${formatShortRp(aiGivesValue)}).`
                : `${ai.name} menolak: nilai penawaran dirasa belum seimbang untuk level ${DIFFICULTY_LABELS[state.difficulty]}.`,
        };
    }

    // --- Board & UI Rendering ---
    function getGridPosition(idx) {
        if (idx === 0) return { row: 11, col: 11, side: 'corner mono-cell--corner-go' };
        if (idx < 10) return { row: 11, col: 11 - idx, side: 'bottom' };
        if (idx === 10) return { row: 11, col: 1, side: 'corner mono-cell--corner-jail' };
        if (idx < 20) return { row: 11 - (idx - 10), col: 1, side: 'left' };
        if (idx === 20) return { row: 1, col: 1, side: 'corner mono-cell--corner-free' };
        if (idx < 30) return { row: 1, col: 1 + (idx - 20), side: 'top' };
        if (idx === 30) return { row: 1, col: 11, side: 'corner mono-cell--corner-jail' };
        return { row: 1 + (idx - 30), col: 11, side: 'right' };
    }

    function buildDieCellsHtml(val, highlightRed = false) {
        const pips = PIP_MAP[val] || PIP_MAP[1];
        let cells = '';
        for (let i = 0; i < 9; i++) {
            cells += pips.includes(i)
                ? `<span class="mono-die-pip ${highlightRed ? 'mono-die-pip--red' : ''}"></span>`
                : '<span></span>';
        }
        return cells;
    }

    function renderDiceFace(val, highlightRed = false, slot = 1) {
        let cls = 'mono-die';
        if (state.rollingAnim) {
            cls += slot === 1 ? ' mono-die--rolling mono-die--rolling-a' : ' mono-die--rolling mono-die--rolling-b';
        } else if (state.diceJustLanded) {
            cls += ' mono-die--landed';
        }
        const idAttr = (slot === 1 || slot === 2) ? `id="monoDie${slot}"` : '';
        return `<div ${idAttr} class="${cls}">${buildDieCellsHtml(val, highlightRed)}</div>`;
    }

    function renderCenterHubHtml() {
        if (!state.started) {
            return `
                <div class="mono-center-hub items-center justify-center text-center gap-3">
                    <div class="space-y-1">
                        <div class="text-xs font-bold uppercase tracking-widest text-amber-300">Edisi Admin Liga Dam Batu</div>
                        <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white drop-shadow">MONOPOLI NUSANTARA</h3>
                        <p class="text-[11px] sm:text-xs text-emerald-100/90 max-w-xs mx-auto">
                            Pilih jumlah lawan AI &amp; level pertandingan di atas, lalu klik <strong>Mulai Pertandingan</strong>.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        ${renderDiceFace(6, true)}
                        ${renderDiceFace(6, false)}
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-2 text-[10px] font-semibold text-white/90">
                        <span class="px-2.5 py-1 rounded-full bg-black/30 border border-white/20">22 Kota Indonesia</span>
                        <span class="px-2.5 py-1 rounded-full bg-black/30 border border-white/20">PPh 10% Kekayaan</span>
                        <span class="px-2.5 py-1 rounded-full bg-black/30 border border-white/20">Lelang &amp; Barter AI</span>
                    </div>
                </div>
            `;
        }

        const active = state.players[state.currentTurn];
        const activeTile = BOARD_TILES[active.pos];
        const isHumanTurn = active && active.isHuman && !active.bankrupt && !state.finished;

        const myPropCount = getOwnedPropertyIndices(0).length;
        const canAssetBtn = canHumanManageAssets() && myPropCount > 0;
        const assetBtnHub = canAssetBtn
            ? `<button type="button" data-hub-action="open-mortgage" class="px-3 py-1.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-amber-200 border border-amber-400/50 text-[11px] font-bold shadow cursor-pointer">🏦 Hipotek (${myPropCount})</button>`
            : '';

        let actionControls = '';
        if (state.finished) {
            const winner = state.players[state.winnerIdx];
            actionControls = `
                <div class="text-center space-y-2">
                    <div class="text-sm font-extrabold text-amber-300">🏆 Pemenang: ${escapeHtml(winner ? winner.name : '')}</div>
                    <button type="button" data-hub-action="show-end" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-extrabold shadow-md cursor-pointer">
                        Lihat Ringkasan Hasil
                    </button>
                </div>
            `;
        } else if (state.rollingAnim) {
            actionControls = `
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-black/45 border border-amber-300/60 text-[11px] font-extrabold text-amber-200 animate-pulse">
                    <span>🎲 Menggelindingkan dadu...</span>
                </div>
            `;
        } else if (state.diceJustLanded) {
            const dSum = state.lastDice[0] + state.lastDice[1];
            const isDbl = state.lastDice[0] === state.lastDice[1];
            actionControls = `
                <div class="mono-dice-result-pill">
                    <span>🎲 Hasil: ${state.lastDice[0]} + ${state.lastDice[1]} = ${dSum}${isDbl ? ' (KEMBAR!)' : ''}</span>
                </div>
            `;
        } else if (state.movingAnim && state.stepProgress) {
            actionControls = `
                <div class="mono-dice-result-pill">
                    <span>👣 Langkah ${state.stepProgress.current}/${state.stepProgress.total} ➔ ${escapeHtml(state.stepProgress.tileName)}</span>
                </div>
            `;
        } else if (state.phase === 'debt' && state.pendingDebt && state.pendingDebt.debtorIdx === 0) {
            const debt = state.pendingDebt;
            const humanCash = state.players[0].cash;
            const canPayNow = humanCash >= debt.amount;
            actionControls = `
                <div class="w-full max-w-sm rounded-xl bg-rose-950/90 border border-rose-400/70 p-2.5 text-center space-y-2 shadow-lg">
                    <div class="text-[11px] font-bold text-rose-200">DARURAT KAS: Butuh ${formatRp(debt.amount)} (${escapeHtml(debt.reason)})</div>
                    <div class="text-[10px] text-rose-100">Kas Anda sekarang: <strong>${formatRp(humanCash)}</strong>. Klik Hipotek/Jual Bangunan di bawah.</div>
                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                        ${canPayNow
                            ? `<button type="button" data-hub-action="pay-debt" class="px-3 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[11px] font-extrabold cursor-pointer">Lunasi Utang (${formatShortRp(debt.amount)})</button>`
                            : `<button type="button" data-hub-action="auto-liquidate" class="px-3 py-1.5 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 text-[11px] font-extrabold cursor-pointer">Auto-Likuidasi Aset</button>`
                        }
                        ${assetBtnHub}
                        <button type="button" data-hub-action="bankrupt" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-[11px] font-bold cursor-pointer">Menyerah (Bangkrut)</button>
                    </div>
                </div>
            `;
        } else if (isHumanTurn) {
            if (state.phase === 'need_roll') {
                actionControls = `
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <button type="button" data-hub-action="roll" class="px-5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs sm:text-sm font-black shadow-lg transition-transform active:scale-95 cursor-pointer">
                            🎲 Lempar Dadu
                        </button>
                        ${assetBtnHub}
                    </div>
                `;
            } else if (state.phase === 'jail_choice') {
                const canPay = active.cash >= JAIL_BAIL;
                actionControls = `
                    <div class="text-center space-y-2">
                        <div class="text-[11px] font-bold text-rose-200">Anda di LP Nusakambangan (Percobaan ${active.jailTurns}/3)</div>
                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                            ${canPay ? `<button type="button" data-hub-action="jail-pay" class="px-3 py-1.5 rounded-lg bg-emerald-400 hover:bg-emerald-300 text-slate-950 text-[11px] font-extrabold cursor-pointer">Bayar Rp 500rb</button>` : ''}
                            ${active.jailFreeCards > 0 ? `<button type="button" data-hub-action="jail-card" class="px-3 py-1.5 rounded-lg bg-sky-400 hover:bg-sky-300 text-slate-950 text-[11px] font-extrabold cursor-pointer">Pakai Kartu Bebas (${active.jailFreeCards})</button>` : ''}
                            <button type="button" data-hub-action="jail-roll" class="px-3 py-1.5 rounded-lg bg-amber-400 hover:bg-amber-300 text-slate-950 text-[11px] font-extrabold cursor-pointer">🎲 Coba Dadu Kembar</button>
                            ${assetBtnHub}
                        </div>
                    </div>
                `;
            } else if (state.phase === 'buy_or_auction') {
                const canAfford = active.cash >= activeTile.price;
                actionControls = `
                    <div class="w-full max-w-xs rounded-xl bg-slate-950/75 border border-amber-400/60 p-2.5 text-center space-y-2">
                        <div class="text-xs font-extrabold text-amber-300">${escapeHtml(activeTile.name)}</div>
                        <div class="text-[11px] text-slate-200">Properti kosong &middot; Harga <strong>${formatRp(activeTile.price)}</strong></div>
                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                            ${canAfford ? `<button type="button" data-hub-action="buy" class="px-3.5 py-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[11px] font-extrabold cursor-pointer">Beli Properti</button>` : '<span class="text-[10px] text-rose-300 font-bold">Kas tidak cukup</span>'}
                            ${assetBtnHub}
                            <button type="button" data-hub-action="decline" class="px-3.5 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-[11px] font-bold cursor-pointer">${state.auctionEnabled ? 'Lelang Cepat' : 'Lewati'}</button>
                        </div>
                    </div>
                `;
            } else if (state.phase === 'manage') {
                actionControls = `
                    <div class="flex flex-wrap items-center justify-center gap-1.5">
                        ${assetBtnHub}
                        <button type="button" data-hub-action="open-trade" class="px-3 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-[11px] font-extrabold shadow cursor-pointer">
                            🤝 Barter AI
                        </button>
                        <button type="button" data-hub-action="end-turn" class="px-4 py-2 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 text-xs font-extrabold shadow cursor-pointer">
                            Selesai Giliran ➔
                        </button>
                    </div>
                `;
            }
        } else if (active) {
            actionControls = `
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-black/35 border border-white/20 text-[11px] font-bold text-amber-200 animate-pulse">
                    <span>${active.token} ${escapeHtml(active.name)} sedang berpikir...</span>
                </div>
            `;
        }

        const latestLogs = state.logs.slice(0, 2).map(l => `<div class="truncate">${escapeHtml(l.text)}</div>`).join('');

        let txBannerHtml = '';
        if (state.activeTxAnim) {
            const tx = state.activeTxAnim;
            const particles = tx.kind === 'buy'
                ? '<span>📜</span><span>🏠</span><span>✨</span>'
                : (tx.kind === 'trade'
                    ? '<span>🤝</span><span>📜</span><span>💵</span>'
                    : (tx.kind === 'mortgage'
                        ? '<span>🏦</span><span>💵</span><span>📄</span>'
                        : '<span>💸</span><span>🪙</span><span>💵</span>'));
            txBannerHtml = `
                <div class="mono-tx-banner mono-tx-banner--${tx.kind}">
                    <div class="mono-tx-particles">${particles}</div>
                    <div class="text-xs font-black tracking-tight">${escapeHtml(tx.title)}</div>
                    ${tx.subtitle ? `<div class="text-[10px] opacity-90 truncate">${escapeHtml(tx.subtitle)}</div>` : ''}
                    ${tx.amountText ? `<div class="text-sm font-mono font-black text-amber-200 mt-0.5">${escapeHtml(tx.amountText)}</div>` : ''}
                </div>
            `;
        }

        return `
            <div class="mono-center-hub">
                ${txBannerHtml}
                <div class="flex items-center justify-between gap-2 text-[10px] font-bold">
                    <span class="px-2 py-0.5 rounded-full bg-black/35 border border-white/20">
                        ${state.maxRounds > 0 ? `Putaran ${state.roundNumber}/${state.maxRounds}` : `Putaran ${state.roundNumber}`}
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-black/35 border border-white/20 text-amber-300">
                        ${active ? `${active.token} Giliran: ${escapeHtml(active.name)}` : ''}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-2 my-1">
                    <div class="mono-deck-box"><span>🎁</span><span>Dana Umum</span></div>
                    <div class="text-center hidden sm:block">
                        <div class="text-xs font-black tracking-wider text-amber-200/90">MONOPOLI NUSANTARA</div>
                    </div>
                    <div class="mono-deck-box"><span>❓</span><span>Kesempatan</span></div>
                </div>

                <div class="flex flex-col items-center justify-center gap-2.5 my-auto">
                    <button type="button" data-hub-action="dice-click" class="mono-dice-tray-btn" title="Klik dadu atau tombol di bawah untuk melempar dadu">
                        ${renderDiceFace(state.lastDice[0], state.lastDice[0] === state.lastDice[1], 1)}
                        ${renderDiceFace(state.lastDice[1], state.lastDice[0] === state.lastDice[1], 2)}
                    </button>
                    ${actionControls}
                </div>

                <div class="rounded-lg bg-black/35 border border-white/15 px-2.5 py-1.5 text-[10px] text-slate-100 space-y-0.5">
                    ${latestLogs || '<div>Menunggu langkah berikutnya...</div>'}
                </div>
            </div>
        `;
    }

    function renderBoard() {
        const grid = document.getElementById('monopoliBoardGrid');
        if (!grid) return;

        const activePlayer = state.players[state.currentTurn];
        const activePos = activePlayer ? activePlayer.pos : -1;

        const cellsHtml = BOARD_TILES.map(tile => {
            const g = getGridPosition(tile.idx);
            const own = state.ownership[tile.idx];
            const isCorner = g.side.startsWith('corner');
            const isStepping = tile.idx === state.steppingTileIdx;
            const isLanded = tile.idx === state.landedTileIdx;
            const isTrail = state.stepTrail.includes(tile.idx);
            const txMatch = state.activeTxAnim && state.activeTxAnim.tileIndices.includes(tile.idx);
            const isActive = tile.idx === activePos && state.started;
            const mortgagedCls = own && own.mortgaged ? 'mono-cell--mortgaged' : '';
            const txCellCls = txMatch ? ` mono-cell--tx-${state.activeTxAnim.kind}` : '';
            const activeCls = (isStepping
                ? 'mono-cell--stepping'
                : (isLanded ? 'mono-cell--landed' : (isTrail ? 'mono-cell--trail' : (isActive ? 'mono-cell--active' : '')))) + txCellCls;
            const stepBadge = (isStepping && state.stepProgress)
                ? `<span class="mono-step-badge">${state.stepProgress.current}/${state.stepProgress.total}</span>`
                : '';
            const txTagHtml = (txMatch && state.activeTxAnim.tagText)
                ? `<span class="mono-cell-tx-tag">${escapeHtml(state.activeTxAnim.tagText)}</span>`
                : '';

            // Tokens present at this square
            const tokensHere = state.started
                ? state.players.filter(p => !p.bankrupt && p.pos === tile.idx)
                : [];
            const tokensHtml = tokensHere.length > 0
                ? `<div class="mono-tokens-layer">${tokensHere.map(p => {
                    const isMovingTok = state.movingAnim && p.id === state.currentTurn;
                    const tokCls = isMovingTok ? 'mono-token--hopping' : (p.id === state.currentTurn ? 'mono-token--active' : '');
                    return `<span class="mono-token ${tokCls}" style="background-color:${p.color}" title="${escapeHtml(p.name)}">${p.token}</span>`;
                }).join('')}</div>`
                : '';

            const ownerDot = own
                ? `<span class="mono-owner-tag" style="background-color:${state.players[own.owner].color}" title="Milik ${escapeHtml(state.players[own.owner].name)}"></span>`
                : '';
            const mortgagedStamp = own && own.mortgaged ? '<span class="mono-mortgaged-stamp">HIPOTEK</span>' : '';

            if (isCorner) {
                let cornerIcon = '🏁';
                let subText = '+Rp 2jt';
                if (tile.idx === 10) {
                    cornerIcon = '⛓️';
                    subText = 'Nusakambangan';
                } else if (tile.idx === 20) {
                    cornerIcon = '🅿️';
                    subText = 'Rest Area';
                } else if (tile.idx === 30) {
                    cornerIcon = '🚨';
                    subText = 'Ke Petak 10';
                }
                return `
                    <div class="mono-cell mono-cell--corner ${g.side.replace('corner ', '')} ${activeCls}" data-tile-index="${tile.idx}" style="grid-row:${g.row};grid-column:${g.col};">
                        ${stepBadge}
                        ${txTagHtml}
                        <div class="text-base sm:text-lg leading-none">${cornerIcon}</div>
                        <div class="mono-corner-title">${escapeHtml(tile.short)}</div>
                        <div class="mono-corner-sub">${subText}</div>
                        ${tokensHtml}
                    </div>
                `;
            }

            let bandHtml = '';
            if (tile.type === 'city') {
                let buildingsHtml = '';
                if (own && own.level === 5) {
                    buildingsHtml = '<span class="mono-hotel-pip" title="Hotel"></span>';
                } else if (own && own.level > 0) {
                    buildingsHtml = Array.from({ length: own.level }).map(() => '<span class="mono-house-pip" title="Rumah"></span>').join('');
                }
                bandHtml = `<div class="mono-band mono-group-${tile.group}">${buildingsHtml}</div>`;
            }

            let centerIcon = '';
            if (tile.type === 'community') centerIcon = '<span class="mono-cell-icon">🎁</span>';
            else if (tile.type === 'chance') centerIcon = '<span class="mono-cell-icon">❓</span>';
            else if (tile.type === 'transport') centerIcon = `<span class="mono-cell-icon">${tile.idx === 15 ? '🚢' : (tile.idx === 25 ? '✈️' : '🚄')}</span>`;
            else if (tile.type === 'utility') centerIcon = `<span class="mono-cell-icon">${tile.idx === 12 ? '⚡' : '💧'}</span>`;
            else if (tile.type === 'tax_income' || tile.type === 'tax_luxury') centerIcon = '<span class="mono-cell-icon">💸</span>';

            const subPrice = tile.price
                ? formatShortRp(tile.price)
                : (tile.type === 'tax_income' ? '10% Aset' : (tile.type === 'tax_luxury' ? 'Rp 1jt' : 'Ambil Kartu'));

            return `
                <div class="mono-cell mono-cell--${g.side} ${mortgagedCls} ${activeCls}" data-tile-index="${tile.idx}" style="grid-row:${g.row};grid-column:${g.col};" title="${escapeHtml(tile.name)}">
                    ${stepBadge}
                    ${txTagHtml}
                    ${bandHtml}
                    <div class="mono-cell-body">
                        <div class="mono-cell-name">${escapeHtml(tile.short)}</div>
                        ${centerIcon}
                        <div class="mono-cell-price">${subPrice}</div>
                    </div>
                    ${ownerDot}
                    ${mortgagedStamp}
                    ${tokensHtml}
                </div>
            `;
        }).join('');

        grid.innerHTML = cellsHtml + renderCenterHubHtml();
    }

    function renderPlayers() {
        const wrap = document.getElementById('monoPlayersList');
        const roundBadge = document.getElementById('monoRoundBadge');
        if (!wrap) return;

        if (!state.started) {
            if (roundBadge) roundBadge.textContent = 'Siap Bermain';
            wrap.innerHTML = '<div class="text-xs text-slate-500 dark:text-slate-400 py-3 text-center">Klik <strong>Mulai Pertandingan</strong> untuk membagikan modal awal Rp 15.000.000.</div>';
            return;
        }

        if (roundBadge) {
            roundBadge.textContent = state.finished
                ? 'Selesai'
                : (state.maxRounds > 0 ? `Putaran ${state.roundNumber}/${state.maxRounds}` : `Putaran ${state.roundNumber}`);
        }

        wrap.innerHTML = state.players.map(p => {
            const isCur = !state.finished && state.currentTurn === p.id;
            const netWorth = calculateNetWorth(p.id);
            const propCount = getOwnedPropertyIndices(p.id).length;
            const tileName = BOARD_TILES[p.pos] ? BOARD_TILES[p.pos].short : '-';
            const delta = state.playerCashDeltas && typeof state.playerCashDeltas[p.id] === 'number'
                ? state.playerCashDeltas[p.id]
                : 0;
            const cardTxCls = delta > 0 ? 'mono-player-card--gain' : (delta < 0 ? 'mono-player-card--loss' : '');
            const deltaHtml = delta !== 0
                ? `<span class="mono-cash-delta-badge ${delta > 0 ? 'mono-cash-delta-badge--plus' : 'mono-cash-delta-badge--minus'}">${delta > 0 ? '+' : '-'}${formatShortRp(Math.abs(delta))}</span>`
                : '';

            let statusPill = '';
            if (p.bankrupt) statusPill = '<span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-bold">BANGKRUT</span>';
            else if (p.inJail) statusPill = '<span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">PENJARA</span>';
            else if (isCur) statusPill = '<span class="px-1.5 py-0.5 rounded bg-indigo-600 text-white text-[10px] font-bold animate-pulse">GILIRAN</span>';

            return `
                <div class="rounded-xl p-2.5 border transition-all ${cardTxCls} ${p.bankrupt ? 'opacity-50 bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800' : (isCur ? 'bg-indigo-50/70 dark:bg-indigo-950/50 border-indigo-400 ring-1 ring-indigo-400/50' : 'bg-slate-50/70 dark:bg-slate-800/50 border-slate-200/80 dark:border-slate-700/70')}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm text-white shrink-0 border-2 border-white shadow-xs" style="background-color:${p.color}">${p.token}</span>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-slate-900 dark:text-white truncate">${escapeHtml(p.name)}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">Di ${escapeHtml(tileName)} &middot; ${propCount} properti${p.jailFreeCards > 0 ? ` &middot; 🎫${p.jailFreeCards}` : ''}</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono font-extrabold text-emerald-600 dark:text-emerald-400 flex items-center justify-end gap-1">
                                <span>${formatRp(p.cash)}</span>
                                ${deltaHtml}
                            </div>
                            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400">Net: ${formatShortRp(netWorth)}</div>
                        </div>
                    </div>
                    ${statusPill ? `<div class="mt-1.5 flex justify-end">${statusPill}</div>` : ''}
                </div>
            `;
        }).join('');
    }

    function renderPortfolio() {
        const tabsEl = document.getElementById('monoPortfolioTabs');
        const listEl = document.getElementById('monoPortfolioList');
        const housesEl = document.getElementById('monoBankHouses');
        const hotelsEl = document.getElementById('monoBankHotels');
        const tradeBtn = document.getElementById('btnMonoOpenTrade');

        if (housesEl) housesEl.textContent = `🏠 ${state.bankHouses} Rumah`;
        if (hotelsEl) hotelsEl.textContent = `🏨 ${state.bankHotels} Hotel`;
        if (tradeBtn) {
            const canTrade = state.started && !state.finished && state.currentTurn === 0 && (state.phase === 'manage' || state.phase === 'need_roll');
            tradeBtn.disabled = !canTrade;
        }

        if (!tabsEl || !listEl) return;

        if (!state.started) {
            tabsEl.innerHTML = '';
            listEl.innerHTML = '<div class="text-center text-slate-400 py-4">Belum ada sertifikat properti dimiliki.</div>';
            return;
        }

        tabsEl.innerHTML = state.players.map(p => {
            const active = state.portfolioFilter === p.id;
            const count = getOwnedPropertyIndices(p.id).length;
            return `<button type="button" data-port-player="${p.id}" class="px-2 py-1 rounded-lg text-[11px] font-semibold border transition-colors cursor-pointer ${active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'}">${p.token} ${escapeHtml(p.name.split(' ')[0])} (${count})</button>`;
        }).join('');

        const indices = getOwnedPropertyIndices(state.portfolioFilter);
        if (indices.length === 0) {
            listEl.innerHTML = '<div class="text-center text-slate-400 py-4">Pemain ini belum memiliki properti.</div>';
            return;
        }

        const canInlineHumanManage = state.portfolioFilter === 0 && canHumanManageAssets();
        listEl.innerHTML = indices.map(idx => {
            const tile = BOARD_TILES[idx];
            const own = state.ownership[idx];
            const dotStyle = tile.group ? `mono-group-${tile.group}` : 'bg-slate-400';
            const bText = own.mortgaged
                ? '<span class="text-[10px] font-bold text-rose-500">HIPOTEK</span>'
                : (own.level === 5
                    ? '<span class="text-[10px] font-bold text-rose-600">🏨 Hotel</span>'
                    : (own.level > 0
                        ? `<span class="text-[10px] font-bold text-emerald-600">🏠 ${own.level}</span>`
                        : '<span class="text-[10px] text-slate-400">Tanah</span>'));

            let quickActionHtml = '';
            if (canInlineHumanManage) {
                if (!own.mortgaged && canMortgageOn(0, idx).ok) {
                    quickActionHtml = `<button type="button" data-quick-prop="mortgage" data-tile-idx="${idx}" class="px-2 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold cursor-pointer">Hipotek +${formatShortRp(tile.mortgage)}</button>`;
                } else if (own.mortgaged) {
                    const uc = canUnmortgageOn(0, idx);
                    quickActionHtml = `<button type="button" data-quick-prop="unmortgage" data-tile-idx="${idx}" ${uc.ok ? '' : 'disabled'} title="${escapeHtml(uc.reason || '')}" class="px-2 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold disabled:opacity-40 cursor-pointer">Tebus -${formatShortRp(getUnmortgageCost(tile))}</button>`;
                } else if (own.level > 0 && canSellBuildingOn(0, idx).ok) {
                    quickActionHtml = `<button type="button" data-quick-prop="sell" data-tile-idx="${idx}" class="px-2 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold cursor-pointer">-Rumah +${formatShortRp(Math.round(tile.buildCost * 0.5))}</button>`;
                }
            }

            return `
                <div class="rounded-xl border border-slate-200/80 dark:border-slate-700/80 bg-slate-50/70 dark:bg-slate-800/50 p-2 flex items-center justify-between gap-2">
                    <button type="button" data-shm-index="${idx}" class="flex items-center gap-2 min-w-0 flex-1 text-left cursor-pointer hover:opacity-80">
                        <span class="w-3 h-6 rounded-sm shrink-0 ${dotStyle}"></span>
                        <div class="min-w-0">
                            <div class="font-bold text-slate-800 dark:text-slate-100 truncate">${escapeHtml(tile.name)}</div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400">Sewa: ${formatShortRp(calculateRent(idx, 7))} &middot; ${bText}</div>
                        </div>
                    </button>
                    <div class="shrink-0 flex items-center gap-1">${quickActionHtml || bText}</div>
                </div>
            `;
        }).join('');
    }

    function renderStats() {
        const statsWrap = document.getElementById('monoStatsSummary');
        const logWrap = document.getElementById('monoActivityLog');
        if (statsWrap) {
            const stats = loadStats();
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
                logWrap.innerHTML = '<div class="text-slate-400 text-center py-2">Belum ada aktivitas.</div>';
            } else {
                logWrap.innerHTML = state.logs.slice(0, 25).map(l => {
                    const cls = l.kind === 'jail' ? 'text-rose-600 dark:text-rose-400 font-semibold'
                        : (l.kind === 'buy' || l.kind === 'build' ? 'text-emerald-700 dark:text-emerald-400'
                        : (l.kind === 'card' ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : ''));
                    return `<div class="${cls} border-b border-slate-200/50 dark:border-slate-700/40 pb-1 last:border-0">${escapeHtml(l.text)}</div>`;
                }).join('');
            }
        }
    }

    function renderQuickActionBar() {
        const bar = document.getElementById('monoQuickActionBar');
        if (!bar) return;

        if (!state.started) {
            bar.innerHTML = '<div class="text-xs font-semibold text-slate-600 dark:text-slate-300">Menyiapkan papan Monopoli Nusantara...</div>';
            return;
        }

        const active = state.players[state.currentTurn];
        const activeTile = active ? BOARD_TILES[active.pos] : null;
        const isHuman = active && active.isHuman && !active.bankrupt && !state.finished;
        const dSum = state.lastDice[0] + state.lastDice[1];

        let statusHtml = `
            <div class="flex items-center gap-2 min-w-0">
                <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm text-white shrink-0 border border-white shadow-xs" style="background-color:${active ? active.color : HUMAN_COLOR}">${active ? active.token : '🎲'}</span>
                <div class="min-w-0">
                    <div class="text-xs font-extrabold text-slate-800 dark:text-slate-100 truncate">
                        ${state.finished ? 'Pertandingan Selesai' : (isHuman ? 'Giliran Anda!' : `Giliran ${escapeHtml(active ? active.name : '')}`)}
                        <span class="font-mono text-[11px] text-indigo-600 dark:text-indigo-400 ml-1">[${state.lastDice[0]}+${state.lastDice[1]}=${dSum}]</span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                        Posisi: ${escapeHtml(activeTile ? activeTile.name : '-')} &middot; Putaran ${state.roundNumber}${state.maxRounds > 0 ? '/' + state.maxRounds : ''}
                    </div>
                </div>
            </div>
        `;

        const myPropCount = getOwnedPropertyIndices(0).length;
        const quickAssetBtn = canHumanManageAssets() && myPropCount > 0
            ? `<button type="button" data-hub-action="open-mortgage" class="px-3 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-amber-200 text-xs font-bold cursor-pointer">🏦 Hipotek (${myPropCount})</button>`
            : '';

        let btnsHtml = '';
        if (state.finished) {
            btnsHtml = `<button type="button" data-hub-action="show-end" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-extrabold cursor-pointer">Lihat Klasemen Akhir</button>`;
        } else if (state.rollingAnim) {
            btnsHtml = `<span class="px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 text-xs font-extrabold animate-pulse">🎲 Menggelindingkan dadu...</span>`;
        } else if (state.diceJustLanded) {
            const isDbl = state.lastDice[0] === state.lastDice[1];
            btnsHtml = `<span class="px-3.5 py-1.5 rounded-xl bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700 text-xs font-extrabold">🎲 Hasil: ${state.lastDice[0]} + ${state.lastDice[1]} = ${dSum}${isDbl ? ' (KEMBAR!)' : ''}</span>`;
        } else if (state.movingAnim && state.stepProgress) {
            btnsHtml = `<span class="px-3.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-extrabold">👣 Melangkah ${state.stepProgress.current}/${state.stepProgress.total} ➔ ${escapeHtml(state.stepProgress.tileName)}</span>`;
        } else if (state.phase === 'debt' && state.pendingDebt && state.pendingDebt.debtorIdx === 0) {
            const canPayNow = state.players[0].cash >= state.pendingDebt.amount;
            btnsHtml = `
                <div class="flex flex-wrap items-center gap-1.5">
                    ${canPayNow
                        ? `<button type="button" data-hub-action="pay-debt" class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold cursor-pointer">Lunasi Utang (${formatShortRp(state.pendingDebt.amount)})</button>`
                        : `<button type="button" data-hub-action="auto-liquidate" class="px-3 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-extrabold cursor-pointer">Auto-Likuidasi (${formatShortRp(state.pendingDebt.amount)})</button>`
                    }
                    ${quickAssetBtn}
                    <button type="button" data-hub-action="bankrupt" class="px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold cursor-pointer">Bangkrut</button>
                </div>
            `;
        } else if (isHuman) {
            if (state.phase === 'need_roll') {
                btnsHtml = `
                    <div class="flex flex-wrap items-center gap-1.5">
                        ${quickAssetBtn}
                        <button type="button" data-hub-action="roll" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-sm cursor-pointer">🎲 Lempar Dadu</button>
                    </div>
                `;
            } else if (state.phase === 'jail_choice') {
                btnsHtml = `
                    <div class="flex flex-wrap items-center gap-1.5">
                        ${active.cash >= JAIL_BAIL ? `<button type="button" data-hub-action="jail-pay" class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer">Bayar Rp 500rb</button>` : ''}
                        ${active.jailFreeCards > 0 ? `<button type="button" data-hub-action="jail-card" class="px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold cursor-pointer">Kartu Bebas</button>` : ''}
                        ${quickAssetBtn}
                        <button type="button" data-hub-action="jail-roll" class="px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold cursor-pointer">🎲 Coba Dadu Kembar</button>
                    </div>
                `;
            } else if (state.phase === 'buy_or_auction') {
                const canAfford = active.cash >= activeTile.price;
                btnsHtml = `
                    <div class="flex flex-wrap items-center gap-1.5">
                        ${canAfford ? `<button type="button" data-hub-action="buy" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold cursor-pointer">Beli (${formatShortRp(activeTile.price)})</button>` : ''}
                        ${quickAssetBtn}
                        <button type="button" data-hub-action="decline" class="px-3.5 py-2 rounded-xl bg-slate-600 hover:bg-slate-700 text-white text-xs font-bold cursor-pointer">${state.auctionEnabled ? 'Lelang Cepat' : 'Lewati'}</button>
                    </div>
                `;
            } else if (state.phase === 'manage') {
                btnsHtml = `
                    <div class="flex flex-wrap items-center gap-1.5">
                        ${quickAssetBtn}
                        <button type="button" data-hub-action="open-trade" class="px-3 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold cursor-pointer">🤝 Barter AI</button>
                        <button type="button" data-hub-action="end-turn" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm cursor-pointer">Selesai Giliran ➔</button>
                    </div>
                `;
            } else if (state.phase === 'card_wait') {
                btnsHtml = `<span class="text-xs font-bold text-amber-600 dark:text-amber-400">Konfirmasi kartu di layar...</span>`;
            }
        } else {
            btnsHtml = `<span class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 text-xs font-bold animate-pulse">${escapeHtml(active ? active.name : 'AI')} sedang bermain...</span>`;
        }

        bar.innerHTML = statusHtml + btnsHtml;
    }

    function renderAll() {
        renderQuickActionBar();
        renderBoard();
        renderPlayers();
        renderPortfolio();
        renderStats();
    }

    // --- Modals ---
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

    function closeAllModals() {
        ['monoShmModal', 'monoAuctionModal', 'monoTradeModal', 'monoCardModal', 'monoEndModal', 'monoRulesModal'].forEach(closeModal);
    }

    function showShmModal(tileIdx) {
        const tile = BOARD_TILES[tileIdx];
        const body = document.getElementById('monoShmBody');
        if (!tile || !body) return;

        if (!tile.price) {
            body.innerHTML = `
                <div class="text-center space-y-2 py-2">
                    <div class="text-lg font-extrabold text-slate-900 dark:text-white">${escapeHtml(tile.name)}</div>
                    <p class="text-xs text-slate-600 dark:text-slate-300">${escapeHtml(tile.desc || 'Petak aksi / kartu peristiwa papan Monopoli Nusantara.')}</p>
                </div>
            `;
            openModal('monoShmModal');
            return;
        }

        const own = state.ownership[tileIdx];
        const ownerObj = own ? state.players[own.owner] : null;
        const canHumanManage = canHumanManageAssets() && own && own.owner === 0;

        let scheduleHtml = '';
        if (tile.type === 'city') {
            scheduleHtml = `
                <div class="grid grid-cols-2 gap-y-1 text-xs">
                    <span>Sewa Tanah Kosong:</span><strong class="text-right font-mono">${formatRp(tile.rents[0])}</strong>
                    <span>Sewa 1 Warna (Monopoli):</span><strong class="text-right font-mono text-indigo-600 dark:text-indigo-400">${formatRp(tile.rents[0] * 2)}</strong>
                    <span>Dengan 1 Rumah:</span><strong class="text-right font-mono">${formatRp(tile.rents[1])}</strong>
                    <span>Dengan 2 Rumah:</span><strong class="text-right font-mono">${formatRp(tile.rents[2])}</strong>
                    <span>Dengan 3 Rumah:</span><strong class="text-right font-mono">${formatRp(tile.rents[3])}</strong>
                    <span>Dengan 4 Rumah:</span><strong class="text-right font-mono">${formatRp(tile.rents[4])}</strong>
                    <span>Dengan 1 HOTEL:</span><strong class="text-right font-mono text-rose-600 dark:text-rose-400">${formatRp(tile.rents[5])}</strong>
                    <span>Biaya Bangun 1 Rumah:</span><strong class="text-right font-mono">${formatRp(tile.buildCost)}</strong>
                </div>
            `;
        } else if (tile.type === 'transport') {
            scheduleHtml = `
                <div class="grid grid-cols-2 gap-y-1 text-xs">
                    <span>Milik 1 Transportasi:</span><strong class="text-right font-mono">Rp 250.000</strong>
                    <span>Milik 2 Transportasi:</span><strong class="text-right font-mono">Rp 500.000</strong>
                    <span>Milik 3 Transportasi:</span><strong class="text-right font-mono">Rp 1.000.000</strong>
                    <span>Milik 4 Transportasi:</span><strong class="text-right font-mono text-emerald-600">Rp 2.000.000</strong>
                </div>
            `;
        } else {
            scheduleHtml = `
                <div class="text-xs space-y-1">
                    <div>1 Utilitas BUMN: <strong>Rp 40.000 &times; Angka Dadu</strong></div>
                    <div>2 Utilitas BUMN: <strong>Rp 100.000 &times; Angka Dadu</strong></div>
                </div>
            `;
        }

        let actionBtns = '';
        if (canHumanManage) {
            const bCheck = canBuildOn(0, tileIdx);
            const sCheck = canSellBuildingOn(0, tileIdx);
            const mCheck = canMortgageOn(0, tileIdx);
            const uCheck = canUnmortgageOn(0, tileIdx);

            actionBtns = `
                <div class="pt-3 border-t border-slate-200 dark:border-slate-700 grid grid-cols-2 gap-2">
                    ${tile.type === 'city' ? `<button type="button" id="shmBtnBuild" ${bCheck.ok ? '' : 'disabled'} title="${escapeHtml(bCheck.reason)}" class="px-3 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">+ ${own.level === 4 ? 'Bangun Hotel' : 'Bangun Rumah'}</button>` : ''}
                    ${tile.type === 'city' ? `<button type="button" id="shmBtnSell" ${sCheck.ok ? '' : 'disabled'} title="${escapeHtml(sCheck.reason || '')}" class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">- Jual Bangunan</button>` : ''}
                    ${!own.mortgaged
                        ? `<button type="button" id="shmBtnMortgage" ${mCheck.ok ? '' : 'disabled'} title="${escapeHtml(mCheck.reason || '')}" class="col-span-2 px-3 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">Hipotekkan (+${formatRp(tile.mortgage)})</button>`
                        : `<button type="button" id="shmBtnUnmortgage" ${uCheck.ok ? '' : 'disabled'} title="${escapeHtml(uCheck.reason || '')}" class="col-span-2 px-3 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">Tebus Hipotek (-${formatRp(getUnmortgageCost(tile))})</button>`
                    }
                </div>
            `;
        }

        const headerCls = tile.group ? `mono-group-${tile.group}` : 'bg-slate-700';
        body.innerHTML = `
            <div class="mono-shm-card">
                <div class="${headerCls} px-4 py-3 text-white text-center">
                    <div class="text-[10px] uppercase tracking-wider font-bold opacity-90">${tile.group ? 'Kelompok ' + COLOR_GROUP_LABELS[tile.group] : tile.type.toUpperCase()}</div>
                    <div class="text-base font-black">${escapeHtml(tile.name)}</div>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-200 dark:border-slate-700">
                        <span>Harga Beli: <strong>${formatRp(tile.price)}</strong></span>
                        <span>Hipotek: <strong>${formatRp(tile.mortgage)}</strong></span>
                    </div>
                    ${scheduleHtml}
                    <div class="rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2 text-xs flex items-center justify-between">
                        <span>Status Kepemilikan:</span>
                        <strong>${ownerObj ? `${ownerObj.token} ${escapeHtml(ownerObj.name)}${own.mortgaged ? ' (HIPOTEK)' : ''}` : 'Milik Bank'}</strong>
                    </div>
                    ${actionBtns}
                </div>
            </div>
        `;

        openModal('monoShmModal');

        const bind = (id, fn) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', () => { if (fn()) showShmModal(tileIdx); });
        };
        bind('shmBtnBuild', () => buildHouseAction(0, tileIdx));
        bind('shmBtnSell', () => sellBuildingAction(0, tileIdx));
        bind('shmBtnMortgage', () => mortgageAction(0, tileIdx));
        bind('shmBtnUnmortgage', () => unmortgageAction(0, tileIdx));
    }

    function openAssetManagerModal() {
        if (!canHumanManageAssets()) return;
        const body = document.getElementById('monoShmBody');
        if (!body) return;

        const human = state.players[0];
        const owned = getOwnedPropertyIndices(0);
        const activeTile = BOARD_TILES[human.pos];

        let targetHintHtml = '';
        if (state.phase === 'buy_or_auction' && activeTile && activeTile.price) {
            const needMore = Math.max(0, activeTile.price - human.cash);
            targetHintHtml = needMore > 0
                ? `<div class="rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700 px-3 py-2 text-xs text-amber-800 dark:text-amber-200">Membeli <strong>${escapeHtml(activeTile.name)}</strong> (${formatRp(activeTile.price)}) butuh tambahan <strong>${formatRp(needMore)}</strong>.</div>`
                : `<div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-700 px-3 py-2 text-xs text-emerald-800 dark:text-emerald-200 flex items-center justify-between gap-2"><span>Kas sudah cukup membeli <strong>${escapeHtml(activeTile.name)}</strong>!</span><button type="button" id="btnCloseMgrReadyBuy" class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-bold cursor-pointer">Tutup</button></div>`;
        } else if (state.phase === 'debt' && state.pendingDebt) {
            const needMore = Math.max(0, state.pendingDebt.amount - human.cash);
            targetHintHtml = `<div class="rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-700 px-3 py-2 text-xs text-rose-800 dark:text-rose-200">Utang: <strong>${formatRp(state.pendingDebt.amount)}</strong>${needMore > 0 ? ` (kurang ${formatRp(needMore)})` : ' — Siap dilunasi!'}.</div>`;
        }

        const rowsHtml = owned.length > 0
            ? owned.map(idx => {
                const tile = BOARD_TILES[idx];
                const own = state.ownership[idx];
                const dotCls = tile.group ? `mono-group-${tile.group}` : 'bg-slate-500';
                const mCheck = canMortgageOn(0, idx);
                const uCheck = canUnmortgageOn(0, idx);
                const sCheck = canSellBuildingOn(0, idx);
                const bCheck = canBuildOn(0, idx);

                return `
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 p-2.5 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-3 h-6 rounded-sm shrink-0 ${dotCls}"></span>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate">${escapeHtml(tile.name)}</div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400">
                                        ${own.mortgaged ? '<span class="text-rose-500 font-bold">HIPOTEK</span>' : (own.level === 5 ? '🏨 Hotel' : (own.level > 0 ? `🏠 ${own.level} Rumah` : 'Tanah'))} &middot; Nilai Hipotek ${formatShortRp(tile.mortgage)}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            ${!own.mortgaged
                                ? `<button type="button" data-mgr-act="mortgage" data-mgr-idx="${idx}" ${mCheck.ok ? '' : 'disabled'} title="${escapeHtml(mCheck.reason || '')}" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold disabled:opacity-40 cursor-pointer">Hipotek +${formatShortRp(tile.mortgage)}</button>`
                                : `<button type="button" data-mgr-act="unmortgage" data-mgr-idx="${idx}" ${uCheck.ok ? '' : 'disabled'} title="${escapeHtml(uCheck.reason || '')}" class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold disabled:opacity-40 cursor-pointer">Tebus -${formatShortRp(getUnmortgageCost(tile))}</button>`
                            }
                            ${own.level > 0 ? `<button type="button" data-mgr-act="sell" data-mgr-idx="${idx}" ${sCheck.ok ? '' : 'disabled'} title="${escapeHtml(sCheck.reason || '')}" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[11px] font-bold disabled:opacity-40 cursor-pointer">-Rumah +${formatShortRp(Math.round(tile.buildCost * 0.5))}</button>` : ''}
                            ${tile.type === 'city' && own.level < 5 && !own.mortgaged ? `<button type="button" data-mgr-act="build" data-mgr-idx="${idx}" ${bCheck.ok ? '' : 'disabled'} title="${escapeHtml(bCheck.reason || '')}" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold disabled:opacity-40 cursor-pointer">+${own.level === 4 ? 'Hotel' : 'Rumah'}</button>` : ''}
                        </div>
                    </div>
                `;
            }).join('')
            : '<div class="text-xs text-slate-400 text-center py-4">Anda belum memiliki properti untuk dikelola/dihipotekkan.</div>';

        body.innerHTML = `
            <div class="space-y-3">
                <div class="flex items-center justify-between rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2 text-xs">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Kas Anda Sekarang:</span>
                    <strong class="font-mono text-sm text-emerald-600 dark:text-emerald-400">${formatRp(human.cash)}</strong>
                </div>
                ${targetHintHtml}
                <div class="max-h-72 overflow-y-auto space-y-2 pr-1">${rowsHtml}</div>
            </div>
        `;

        openModal('monoShmModal');

        document.getElementById('btnCloseMgrReadyBuy')?.addEventListener('click', () => closeModal('monoShmModal'));
        body.querySelectorAll('[data-mgr-act]').forEach(btn => {
            btn.addEventListener('click', () => {
                const act = btn.getAttribute('data-mgr-act');
                const idx = Number(btn.getAttribute('data-mgr-idx'));
                let changed = false;
                if (act === 'mortgage') changed = mortgageAction(0, idx);
                else if (act === 'unmortgage') changed = unmortgageAction(0, idx);
                else if (act === 'sell') changed = sellBuildingAction(0, idx);
                else if (act === 'build') changed = buildHouseAction(0, idx);
                if (changed) openAssetManagerModal();
            });
        });
    }

    function renderAuctionModal() {
        const auc = state.auction;
        const body = document.getElementById('monoAuctionBody');
        if (!auc || !body) return;

        const tile = BOARD_TILES[auc.tileIdx];
        const currentBidder = auc.bidders[auc.turnPos];
        const isHumanTurn = currentBidder === 0;
        const leader = auc.highestBidder !== null ? state.players[auc.highestBidder] : null;
        const humanCash = state.players[0].cash;

        body.innerHTML = `
            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-3 text-center space-y-1">
                <div class="text-sm font-black text-slate-900 dark:text-white">${escapeHtml(tile.name)}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">Harga Normal: ${formatRp(tile.price)}</div>
                <div class="text-lg font-mono font-black text-amber-600 dark:text-amber-400 mt-1">${auc.currentBid > 0 ? formatRp(auc.currentBid) : 'Belum ada penawaran'}</div>
                <div class="text-xs font-semibold text-slate-600 dark:text-slate-300">${leader ? `Penawar tertinggi: ${leader.token} ${escapeHtml(leader.name)}` : 'Mulai tawar dari Rp 100.000'}</div>
            </div>
            <div class="flex flex-wrap gap-1.5 justify-center">
                ${state.players.filter(p => !p.bankrupt).map(p => {
                    const stillIn = auc.bidders.includes(p.id);
                    const turnNow = stillIn && currentBidder === p.id;
                    return `<span class="px-2 py-1 rounded-lg text-[11px] font-bold border ${turnNow ? 'bg-amber-500 text-white border-amber-500 animate-pulse' : (stillIn ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700' : 'opacity-40 line-through border-slate-200 dark:border-slate-800')}">${p.token} ${escapeHtml(p.name.split(' ')[0])}</span>`;
                }).join('')}
            </div>
            ${isHumanTurn ? `
                <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <div class="text-xs font-bold text-center text-indigo-600 dark:text-indigo-400">Giliran Anda Menawar! (Kas: ${formatRp(humanCash)})</div>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" data-auc-bid="50000" ${humanCash < (auc.currentBid === 0 ? 100000 : auc.currentBid + 50000) ? 'disabled' : ''} class="px-2 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold disabled:opacity-40 cursor-pointer">${auc.currentBid === 0 ? 'Rp 100rb' : '+Rp 50rb'}</button>
                        <button type="button" data-auc-bid="100000" ${humanCash < auc.currentBid + 100000 ? 'disabled' : ''} class="px-2 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold disabled:opacity-40 cursor-pointer">+Rp 100rb</button>
                        <button type="button" data-auc-bid="250000" ${humanCash < auc.currentBid + 250000 ? 'disabled' : ''} class="px-2 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold disabled:opacity-40 cursor-pointer">+Rp 250rb</button>
                    </div>
                    <button type="button" id="btnAucPass" class="w-full py-2 rounded-xl bg-rose-100 hover:bg-rose-200 text-rose-700 text-xs font-bold cursor-pointer">Mundur dari Lelang</button>
                </div>
            ` : '<div class="text-xs text-center text-slate-500 animate-pulse py-2">Menunggu keputusan lawan AI...</div>'}
        `;

        openModal('monoAuctionModal');

        body.querySelectorAll('[data-auc-bid]').forEach(btn => {
            btn.addEventListener('click', () => humanAuctionBid(Number(btn.getAttribute('data-auc-bid'))));
        });
        const passBtn = document.getElementById('btnAucPass');
        if (passBtn) passBtn.addEventListener('click', humanAuctionPass);
    }

    function showCardModal(deckType, card) {
        const header = document.getElementById('monoCardHeader');
        const icon = document.getElementById('monoCardIcon');
        const title = document.getElementById('monoCardTitle');
        const desc = document.getElementById('monoCardDesc');

        if (header) {
            header.className = 'px-4 py-3 text-white font-extrabold text-sm ' + (deckType === 'community' ? 'bg-sky-600' : 'bg-amber-500');
            header.textContent = deckType === 'community' ? 'DANA UMUM' : 'KESEMPATAN';
        }
        if (icon) icon.textContent = deckType === 'community' ? '🎁' : '❓';
        if (title) title.textContent = card.title;
        if (desc) desc.textContent = card.desc;
        openModal('monoCardModal');
    }

    function openTradeModal() {
        if (!state.started || state.finished || state.currentTurn !== 0) return;
        const body = document.getElementById('monoTradeBody');
        const aiList = state.players.filter(p => !p.isHuman && !p.bankrupt);
        if (!body || aiList.length === 0) return;

        let selectedAi = aiList[0].id;
        const renderTradeForm = () => {
            const ai = state.players[selectedAi];
            const myProps = getOwnedPropertyIndices(0).filter(idx => !state.ownership[idx].level);
            const aiProps = getOwnedPropertyIndices(selectedAi).filter(idx => !state.ownership[idx].level);

            body.innerHTML = `
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Pilih Rekan Barter AI:</label>
                    <select id="tradePartnerSelect" class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2 text-xs font-bold">
                        ${aiList.map(a => `<option value="${a.id}" ${a.id === selectedAi ? 'selected' : ''}>${a.token} ${escapeHtml(a.name)} (Kas: ${formatRp(a.cash)})</option>`).join('')}
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                        <div class="text-xs font-extrabold text-emerald-600">Anda Tawarkan:</div>
                        <div class="max-h-36 overflow-y-auto space-y-1 text-xs">
                            ${myProps.length > 0 ? myProps.map(idx => `<label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="trade-my-prop" value="${idx}"><span>${escapeHtml(BOARD_TILES[idx].name)} (${formatShortRp(BOARD_TILES[idx].price)})</span></label>`).join('') : '<div class="text-slate-400">Tidak ada properti kosong</div>'}
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Tambah Uang Tunai (Rp):</label>
                            <input type="number" id="tradeMyCash" min="0" max="${state.players[0].cash}" step="50000" value="0" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2.5 py-1.5 text-xs font-mono">
                        </div>
                        ${state.players[0].jailFreeCards > 0 ? '<label class="flex items-center gap-2 text-xs cursor-pointer"><input type="checkbox" id="tradeMyJail"><span>Sertakan Kartu Bebas Penjara</span></label>' : ''}
                    </div>
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                        <div class="text-xs font-extrabold text-indigo-600">Anda Minta dari ${escapeHtml(ai.name)}:</div>
                        <div class="max-h-36 overflow-y-auto space-y-1 text-xs">
                            ${aiProps.length > 0 ? aiProps.map(idx => `<label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="trade-ai-prop" value="${idx}"><span>${escapeHtml(BOARD_TILES[idx].name)} (${formatShortRp(BOARD_TILES[idx].price)})</span></label>`).join('') : '<div class="text-slate-400">AI belum punya properti kosong</div>'}
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold mb-1">Minta Uang Tunai (Rp):</label>
                            <input type="number" id="tradeAiCash" min="0" max="${ai.cash}" step="50000" value="0" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-2.5 py-1.5 text-xs font-mono">
                        </div>
                        ${ai.jailFreeCards > 0 ? '<label class="flex items-center gap-2 text-xs cursor-pointer"><input type="checkbox" id="tradeAiJail"><span>Minta Kartu Bebas Penjara</span></label>' : ''}
                    </div>
                </div>
                <div id="tradeEvalFeedback" class="rounded-xl bg-slate-100 dark:bg-slate-800 px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Pilih properti/uang untuk melihat respon AI.
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" id="btnCancelTrade" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 text-xs font-bold cursor-pointer">Batal</button>
                    <button type="button" id="btnSubmitTrade" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-extrabold cursor-pointer">Ajukan Barter</button>
                </div>
            `;

            const readSelections = () => ({
                myProps: Array.from(body.querySelectorAll('.trade-my-prop:checked')).map(el => Number(el.value)),
                aiProps: Array.from(body.querySelectorAll('.trade-ai-prop:checked')).map(el => Number(el.value)),
                myCash: Math.max(0, Math.min(state.players[0].cash, Number(document.getElementById('tradeMyCash')?.value || 0))),
                aiCash: Math.max(0, Math.min(ai.cash, Number(document.getElementById('tradeAiCash')?.value || 0))),
                myJail: document.getElementById('tradeMyJail')?.checked ? 1 : 0,
                aiJail: document.getElementById('tradeAiJail')?.checked ? 1 : 0,
            });

            const updatePreview = () => {
                const s = readSelections();
                const res = evaluateTradeProposal(selectedAi, s.myProps, s.myCash, s.myJail, s.aiProps, s.aiCash, s.aiJail);
                const fb = document.getElementById('tradeEvalFeedback');
                if (fb) {
                    fb.className = 'rounded-xl px-3 py-2 text-xs font-semibold ' + (res.accepted ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300');
                    fb.textContent = res.reason;
                }
            };

            document.getElementById('tradePartnerSelect')?.addEventListener('change', (e) => {
                selectedAi = Number(e.target.value);
                renderTradeForm();
            });
            body.querySelectorAll('input').forEach(inp => inp.addEventListener('change', updatePreview));
            document.getElementById('btnCancelTrade')?.addEventListener('click', () => closeModal('monoTradeModal'));
            document.getElementById('btnSubmitTrade')?.addEventListener('click', () => {
                const s = readSelections();
                const res = evaluateTradeProposal(selectedAi, s.myProps, s.myCash, s.myJail, s.aiProps, s.aiCash, s.aiJail);
                updatePreview();
                if (!res.accepted) {
                    sfxCashOut();
                    return;
                }
                // Eksekusi barter atomik
                const human = state.players[0];
                const partner = state.players[selectedAi];
                human.cash = human.cash - s.myCash + s.aiCash;
                partner.cash = partner.cash - s.aiCash + s.myCash;
                human.jailFreeCards = human.jailFreeCards - s.myJail + s.aiJail;
                partner.jailFreeCards = partner.jailFreeCards - s.aiJail + s.myJail;
                s.myProps.forEach(idx => { state.ownership[idx].owner = selectedAi; });
                s.aiProps.forEach(idx => { state.ownership[idx].owner = 0; });

                const netHumanDelta = s.aiCash - s.myCash;
                const propCountTotal = s.myProps.length + s.aiProps.length;
                const cashLabel = netHumanDelta !== 0
                    ? `${netHumanDelta > 0 ? '+' : '-'}${formatRp(Math.abs(netHumanDelta))}`
                    : `${propCountTotal} Properti`;

                sfxTradeSuccess();
                triggerTransactionAnim({
                    kind: 'trade',
                    title: 'Barter Deal Berhasil!',
                    subtitle: `Anda ⇄ ${partner.name} (${propCountTotal} SHM)`,
                    amountText: cashLabel,
                    tagText: '🤝 BARTER',
                    tileIndices: [...s.myProps, ...s.aiProps],
                    deltas: { 0: netHumanDelta, [selectedAi]: -netHumanDelta },
                });
                addLog(`Barter berhasil dengan ${partner.name}!`, 'buy');
                closeModal('monoTradeModal');
                saveGameState();
                renderAll();
            });
        };

        renderTradeForm();
        openModal('monoTradeModal');
    }

    function showEndModal() {
        const body = document.getElementById('monoEndBody');
        if (!body) return;
        const ranked = state.players.slice().sort((a, b) => {
            if (a.bankrupt !== b.bankrupt) return a.bankrupt ? 1 : -1;
            return calculateNetWorth(b.id) - calculateNetWorth(a.id);
        });
        const winner = state.players[state.winnerIdx];

        body.innerHTML = `
            <div class="text-center space-y-1">
                <div class="text-3xl">${winner ? winner.token : '🏆'}</div>
                <div class="text-lg font-black text-slate-900 dark:text-white">${escapeHtml(winner ? winner.name : '')} Menang!</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">${escapeHtml(state.winReason)}</div>
            </div>
            <div class="space-y-2">
                ${ranked.map((p, idx) => `
                    <div class="flex items-center justify-between rounded-xl px-3 py-2 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/70 text-xs">
                        <div class="flex items-center gap-2 font-bold">
                            <span class="w-5 text-slate-400">#${idx + 1}</span>
                            <span>${p.token} ${escapeHtml(p.name)}</span>
                        </div>
                        <div class="font-mono font-extrabold ${p.bankrupt ? 'text-rose-500' : 'text-emerald-600'}">
                            ${p.bankrupt ? 'BANGKRUT' : formatRp(calculateNetWorth(p.id))}
                        </div>
                    </div>
                `).join('')}
            </div>
            <button type="button" id="btnPlayAgainEnd" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold cursor-pointer">
                Main Lagi Sekarang
            </button>
        `;
        openModal('monoEndModal');
        document.getElementById('btnPlayAgainEnd')?.addEventListener('click', startNewMatch);
    }

    // --- UI Preferences & Controls Sync ---
    function syncControlsUI() {
        document.querySelectorAll('.mono-opt-players').forEach(btn => {
            const active = Number(btn.getAttribute('data-mono-players')) === prefs.playerCount;
            btn.className = 'mono-opt-players px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer ' +
                (active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700');
        });

        document.querySelectorAll('.mono-opt-diff').forEach(btn => {
            const active = btn.getAttribute('data-mono-diff') === prefs.difficulty;
            btn.className = 'mono-opt-diff px-2 py-2 rounded-xl text-xs font-semibold border transition-all cursor-pointer ' +
                (active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700');
        });

        const winSel = document.getElementById('monoWinModeSelect');
        if (winSel) winSel.value = prefs.winMode;

        const tokSel = document.getElementById('monoTokenSelect');
        if (tokSel) tokSel.value = prefs.humanToken;

        const aucChk = document.getElementById('monoAuctionToggle');
        if (aucChk) aucChk.checked = Boolean(prefs.auctionEnabled);

        const boardCard = document.getElementById('monopoliBoardCard');
        const surface = document.getElementById('monopoliBoardSurface');
        const camLabel = document.getElementById('monoCameraLabel');
        const zoomReset = document.getElementById('btnMonoZoomReset');
        if (boardCard) {
            boardCard.setAttribute('data-camera', prefs.camera);
            boardCard.setAttribute('data-felt', prefs.felt);
        }
        if (surface) surface.style.setProperty('--board-zoom', String(prefs.zoom));
        if (camLabel) camLabel.textContent = CAMERA_LABELS[prefs.camera] || '3D Meja';
        if (zoomReset) zoomReset.textContent = Math.round(prefs.zoom * 100) + '%';

        document.querySelectorAll('.mono-felt-btn').forEach(btn => {
            const active = btn.getAttribute('data-mono-felt') === prefs.felt;
            btn.className = 'mono-felt-btn px-2 py-1 rounded-md text-[11px] font-semibold cursor-pointer ' +
                (active ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700');
        });

        const muteIcon = document.getElementById('monoMuteIcon');
        const muteLbl = document.getElementById('monoMuteLabel');
        if (muteIcon) muteIcon.className = prefs.muted ? 'bi bi-volume-mute-fill text-rose-500' : 'bi bi-volume-up-fill text-emerald-500';
        if (muteLbl) muteLbl.textContent = prefs.muted ? 'Suara Mati' : 'Suara Aktif';

        const startBtn = document.getElementById('btnMonoStartGame');
        const resumeBtn = document.getElementById('btnMonoResumeSaved');
        const restartBtn = document.getElementById('btnMonoRestartGame');
        if (state.started && state.hasMoved && !state.finished) {
            if (startBtn) startBtn.classList.add('hidden');
            if (resumeBtn) resumeBtn.classList.add('hidden');
            if (restartBtn) restartBtn.classList.remove('hidden');
        } else {
            if (startBtn) startBtn.classList.remove('hidden');
            if (restartBtn) restartBtn.classList.add('hidden');
            if (resumeBtn) resumeBtn.classList.toggle('hidden', !hasSavedGame());
        }
    }

    function toggleFullscreen() {
        const wrapper = document.getElementById('monopoliFullscreenWrapper');
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

    // --- Central Hub / Quick Action Handler ---
    function handleHubAction(act) {
        if (act === 'dice-click') handleDiceClick();
        else if (act === 'roll') playerRollDice();
        else if (act === 'buy') humanBuyCurrentProperty();
        else if (act === 'decline') humanDeclineProperty();
        else if (act === 'end-turn') advanceTurn();
        else if (act === 'open-trade') openTradeModal();
        else if (act === 'open-mortgage') openAssetManagerModal();
        else if (act === 'jail-pay') handleHumanJailPay();
        else if (act === 'jail-card') handleHumanJailFreeCard();
        else if (act === 'jail-roll') handleJailRollDoubles(0);
        else if (act === 'pay-debt') settleHumanDebt();
        else if (act === 'auto-liquidate') humanAutoLiquidateStep();
        else if (act === 'bankrupt') declareBankruptcy(0, state.pendingDebt ? state.pendingDebt.creditorIdx : null, state.pendingDebt ? state.pendingDebt.reason : 'Utang');
        else if (act === 'show-end') showEndModal();
    }

    // --- Event Bindings ---
    document.addEventListener('DOMContentLoaded', () => {
        loadPrefs();
        startNewMatch({ silent: true });

        document.querySelectorAll('.mono-opt-players').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.playerCount = Number(btn.getAttribute('data-mono-players'));
                savePrefs();
                if (!state.hasMoved) startNewMatch({ silent: true });
                else syncControlsUI();
            });
        });

        document.querySelectorAll('.mono-opt-diff').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.difficulty = btn.getAttribute('data-mono-diff');
                savePrefs();
                if (!state.hasMoved) startNewMatch({ silent: true });
                else syncControlsUI();
            });
        });

        document.getElementById('monoWinModeSelect')?.addEventListener('change', (e) => {
            prefs.winMode = e.target.value;
            savePrefs();
            if (!state.hasMoved) startNewMatch({ silent: true });
        });
        document.getElementById('monoTokenSelect')?.addEventListener('change', (e) => {
            prefs.humanToken = e.target.value;
            savePrefs();
            if (!state.hasMoved) startNewMatch({ silent: true });
        });
        document.getElementById('monoAuctionToggle')?.addEventListener('change', (e) => {
            prefs.auctionEnabled = e.target.checked;
            savePrefs();
            if (!state.hasMoved) startNewMatch({ silent: true });
        });

        document.getElementById('btnMonoStartGame')?.addEventListener('click', () => startNewMatch({ silent: false }));
        document.getElementById('btnMonoResumeSaved')?.addEventListener('click', restoreSavedGame);
        document.getElementById('btnMonoRestartGame')?.addEventListener('click', () => {
            if (window.Swal) {
                window.Swal.fire({
                    icon: 'warning',
                    title: 'Mulai Ulang Monopoli?',
                    text: 'Progress pertandingan saat ini akan direset dari awal.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Mulai Ulang',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#4f46e5',
                }).then(r => { if (r.isConfirmed) startNewMatch({ silent: false }); });
            } else {
                startNewMatch({ silent: false });
            }
        });

        document.getElementById('btnMonoCycleCamera')?.addEventListener('click', () => {
            const idx = CAMERA_ORDER.indexOf(prefs.camera);
            prefs.camera = CAMERA_ORDER[(idx + 1) % CAMERA_ORDER.length];
            savePrefs();
            syncControlsUI();
        });

        document.querySelectorAll('.mono-felt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                prefs.felt = btn.getAttribute('data-mono-felt');
                savePrefs();
                syncControlsUI();
            });
        });

        document.getElementById('btnMonoZoomOut')?.addEventListener('click', () => {
            prefs.zoom = Math.max(0.65, Math.round((prefs.zoom - 0.1) * 100) / 100);
            savePrefs();
            syncControlsUI();
        });
        document.getElementById('btnMonoZoomIn')?.addEventListener('click', () => {
            prefs.zoom = Math.min(1.25, Math.round((prefs.zoom + 0.1) * 100) / 100);
            savePrefs();
            syncControlsUI();
        });
        document.getElementById('btnMonoZoomReset')?.addEventListener('click', () => {
            prefs.zoom = 1;
            savePrefs();
            syncControlsUI();
        });

        document.getElementById('btnMonoToggleMute')?.addEventListener('click', () => {
            prefs.muted = !prefs.muted;
            savePrefs();
            syncControlsUI();
        });

        document.getElementById('btnMonoFullscreen')?.addEventListener('click', toggleFullscreen);
        document.getElementById('btnMonoOpenRules')?.addEventListener('click', () => openModal('monoRulesModal'));
        document.getElementById('btnCloseRulesModal')?.addEventListener('click', () => closeModal('monoRulesModal'));
        document.getElementById('btnCloseShmModal')?.addEventListener('click', () => closeModal('monoShmModal'));
        document.getElementById('btnCloseTradeModal')?.addEventListener('click', () => closeModal('monoTradeModal'));
        document.getElementById('btnCloseEndModal')?.addEventListener('click', () => closeModal('monoEndModal'));
        document.getElementById('btnMonoOpenTrade')?.addEventListener('click', openTradeModal);

        document.getElementById('btnConfirmCardModal')?.addEventListener('click', () => {
            const pending = state.pendingCard;
            state.pendingCard = null;
            closeModal('monoCardModal');
            if (pending && pending.card) {
                executeEventCard(0, pending.card);
            }
        });

        document.getElementById('btnMonoResetStats')?.addEventListener('click', () => {
            try { localStorage.removeItem(STATS_KEY); } catch (e) {}
            renderStats();
        });

        // Delegated listener pada Panel Cepat 2D
        document.getElementById('monoQuickActionBar')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-hub-action]');
            if (btn) handleHubAction(btn.getAttribute('data-hub-action'));
        });

        // Delegated listener di papan 11x11 (klik petak SHM & tombol aksi Center Hub)
        document.getElementById('monopoliBoardGrid')?.addEventListener('click', (e) => {
            const hubBtn = e.target.closest('[data-hub-action]');
            if (hubBtn) {
                handleHubAction(hubBtn.getAttribute('data-hub-action'));
                return;
            }
            const cell = e.target.closest('[data-tile-index]');
            if (cell) {
                const idx = Number(cell.getAttribute('data-tile-index'));
                showShmModal(idx);
            }
        });

        // Shortcut Spasi untuk melempar dadu saat fokus tidak sedang mengetik/di tombol
        document.addEventListener('keydown', (e) => {
            if (e.code !== 'Space') return;
            const activeEl = document.activeElement;
            const tag = activeEl ? activeEl.tagName.toUpperCase() : '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || tag === 'BUTTON') return;
            const anyModalOpen = ['monoShmModal', 'monoAuctionModal', 'monoTradeModal', 'monoCardModal', 'monoEndModal', 'monoRulesModal']
                .some(id => {
                    const m = document.getElementById(id);
                    return m && !m.classList.contains('hidden');
                });
            if (anyModalOpen) return;
            if (state.currentTurn === 0 && !state.rollingAnim && !state.movingAnim && (state.phase === 'need_roll' || state.phase === 'jail_choice')) {
                e.preventDefault();
                handleDiceClick();
            }
        });

        document.getElementById('monoPortfolioTabs')?.addEventListener('click', (e) => {
            const tab = e.target.closest('[data-port-player]');
            if (tab) {
                state.portfolioFilter = Number(tab.getAttribute('data-port-player'));
                renderPortfolio();
            }
        });

        document.getElementById('monoPortfolioList')?.addEventListener('click', (e) => {
            const quickBtn = e.target.closest('[data-quick-prop]');
            if (quickBtn) {
                const act = quickBtn.getAttribute('data-quick-prop');
                const idx = Number(quickBtn.getAttribute('data-tile-idx'));
                if (!canHumanManageAssets()) return;
                if (act === 'mortgage') mortgageAction(0, idx);
                else if (act === 'unmortgage') unmortgageAction(0, idx);
                else if (act === 'sell') sellBuildingAction(0, idx);
                return;
            }
            const item = e.target.closest('[data-shm-index]');
            if (item) {
                showShmModal(Number(item.getAttribute('data-shm-index')));
            }
        });
    });
})();
