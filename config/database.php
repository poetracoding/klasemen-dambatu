<?php
// Konfigurasi database SQLite (PDO) + pembuatan skema otomatis.
define('BASE_PATH', dirname(__DIR__));
define('DB_FILE', BASE_PATH . '/database/liga.sqlite');
date_default_timezone_set('Asia/Jakarta');

// URL dasar aplikasi (hanya folder admin/ yang berada di sub-folder)
$__dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if (basename($__dir) === 'admin') {
    $__dir = str_replace('\\', '/', dirname($__dir));
}
define('BASE_URL', rtrim($__dir === '/' || $__dir === '.' ? '' : $__dir, '/'));

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $dir = dirname(DB_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    init_schema($pdo);
    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS liga (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nama_liga TEXT NOT NULL,
        musim TEXT,
        tanggal_mulai DATE,
        tanggal_selesai DATE,
        status TEXT DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS tim (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_liga INTEGER NOT NULL,
        nama_tim TEXT NOT NULL,
        pemain1 TEXT NOT NULL,
        pemain2 TEXT NOT NULL,
        status INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_liga) REFERENCES liga(id)
    );
    CREATE TABLE IF NOT EXISTS pertandingan (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_liga INTEGER NOT NULL,
        ronde INTEGER,
        nomor_pertandingan INTEGER,
        tanggal DATE,
        waktu TIME,
        lokasi TEXT,
        tim_home INTEGER NOT NULL,
        tim_away INTEGER NOT NULL,
        skor_home INTEGER DEFAULT NULL,
        skor_away INTEGER DEFAULT NULL,
        pemenang_id INTEGER,
        status TEXT DEFAULT 'belum_main',
        catatan TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_liga) REFERENCES liga(id),
        FOREIGN KEY (tim_home) REFERENCES tim(id),
        FOREIGN KEY (tim_away) REFERENCES tim(id),
        FOREIGN KEY (pemenang_id) REFERENCES tim(id),
        UNIQUE (id_liga, tim_home, tim_away),
        CHECK (tim_home <> tim_away)
    );
    CREATE TABLE IF NOT EXISTS admin (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        nama TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ");

    if (!(int) $pdo->query('SELECT COUNT(*) FROM liga')->fetchColumn()) {
        $pdo->prepare('INSERT INTO liga (nama_liga, musim, status) VALUES (?,?,?)')
            ->execute(['LIGA DAM BATU', date('Y'), 'draft']);
    }
    if (!(int) $pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn()) {
        // Akun awal: admin / admin123  (WAJIB diganti di menu Pengaturan)
        $pdo->prepare('INSERT INTO admin (username, password, nama) VALUES (?,?,?)')
            ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator']);
    }

    // Auto-migration kolom baru
    $cols = $pdo->query("PRAGMA table_info(pertandingan)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('skor_home', $cols, true)) {
        $pdo->exec("ALTER TABLE pertandingan ADD COLUMN skor_home INTEGER DEFAULT 0");
    }
    if (!in_array('skor_away', $cols, true)) {
        $pdo->exec("ALTER TABLE pertandingan ADD COLUMN skor_away INTEGER DEFAULT 0");
    }
    if (!in_array('updated_at', $cols, true)) {
        $pdo->exec("ALTER TABLE pertandingan ADD COLUMN updated_at DATETIME DEFAULT NULL");
    }

    $colsLiga = $pdo->query("PRAGMA table_info(liga)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('jumlah_meja', $colsLiga, true)) {
        $pdo->exec("ALTER TABLE liga ADD COLUMN jumlah_meja INTEGER DEFAULT 2");
    }
    if (!in_array('hari_pertandingan', $colsLiga, true)) {
        $pdo->exec("ALTER TABLE liga ADD COLUMN hari_pertandingan TEXT DEFAULT '1,2,3,4,5,6,7'");
    }
}
