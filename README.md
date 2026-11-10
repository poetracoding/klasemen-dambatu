# LIGA DAM BATU

Aplikasi manajemen liga permainan Dam Batu (Single Round Robin, 2 pemain per tim).
PHP Native + SQLite (PDO) + Tailwind CSS. Tanpa Composer/Node.

## Fitur Utama

1. **Multi-Meja Dam Batu (Simultan):** Mendukung lebih dari 1 meja/papan dam batu sehingga beberapa pertandingan berjalan serentak tanpa antri, dengan algoritma anti-bentrok (tim yang sama tidak akan bermain di jam yang sama).
2. **Poin / Skor Pertandingan:** Mencatat skor poin (seperti gol sepak bola), menghitung otomatis Poin Masuk (`+`), Poin Keluar (`-`), dan Selisih Poin (`Δ`) di klasemen.
3. **Live Auto-Update HTML (Tanpa Refresh):** Penonton/pengguna publik mendapatkan pembaruan skor, hasil, dan klasemen secara live otomatis tanpa reload browser.
4. **Percepat / Perlambat Jadwal:** Fitur geser waktu massal (misal: dipercepat 15/30 menit atau diperlambat) bila pertandingan di lapangan selesai lebih cepat atau molor.
5. **Pembatalan Jadwal Aman:** Menu untuk membatalkan/menghapus jadwal yang digenerate dengan pengaman (tidak dapat dibatalkan jika sudah ada yang bertanding).
6. **Responsif Mobile:** Antarmuka fleksibel dan nyaman diakses melalui smartphone maupun desktop.

## Menjalankan

Syarat: PHP 8.0+ dengan ekstensi `pdo_sqlite`.

```bash
cd liga-dam-batu
php -S localhost:8000
```

Buka http://localhost:8000. Database `database/liga.sqlite` dibuat otomatis saat pertama diakses.
Di Apache/Nginx, arahkan document root ke folder ini (folder `database/`, `config/`, `includes/` sudah diberi `.htaccess` deny; pada Nginx blokir manual).

## Login admin

- URL: `/login.php`
- Username: `admin`, password awal: `admin123` — **segera ganti** di Admin → Pengaturan.

## Alur penggunaan

1. Admin → **Tim & Pemain**: tambah tim (tepat 2 pemain).
2. Admin → **Jadwal**: Generate Jadwal (tanggal mulai, jumlah pertandingan/hari, jam, durasi, lokasi), atur ulang tanggal/jam/lokasi bila perlu, lalu **Publikasikan**.
3. Admin → **Input Hasil**: pilih status & pemenang. Klasemen, history, dan status liga ikut diperbarui otomatis.
4. Semua pertandingan selesai → liga berstatus *Selesai* dan juara tampil di halaman publik.

## Aturan penting

- Jadwal hanya bisa digenerate ulang saat liga masih **draft**; tim hanya bisa ditambah/dihapus saat draft.
- Mendukung jumlah tim ganjil (bye) maupun genap. Total pertandingan = N(N-1)/2.
- Klasemen: poin (menang 3, kalah 0) → jumlah menang → pertemuan langsung → nama tim.
  Penentuan posisi manual oleh admin (opsional di PRD) belum ada.

## Backup

Admin → Pengaturan → Unduh Backup (salinan SQLite konsisten), atau salin `database/liga.sqlite`.
