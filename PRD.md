# PRD & Checklist Perbaikan Aplikasi Liga Dam Batu

**Tanggal Dokumen:** 22 November 2026  
**Versi Aplikasi:** Laravel 12 (PHP) + Tailwind CSS + Vanilla JS / HTML5 Canvas 3D  
**Status Test Suite Terkini:** 50 Feature & Unit Tests (`608 assertions`) — Lulus 100%

---

## 1. Gambaran Umum Produk (Product Overview)

### 1.1 Tujuan Aplikasi
**Liga Dam Batu** adalah sistem informasi manajemen turnamen Dam Batu terpadu yang menggabungkan:
1. **Portal Turnamen Publik (*Real-time*)**: Menampilkan jadwal harian/keseluruhan, klasemen otomatis (*6-level tie-breaker*), statistik tim, prediksi peluang menang, berita turnamen dengan jadwal terbit & multi-reaksi emoji, cetak PDF resmi, notifikasi push FCM, serta *game* hiburan Dam Batu 3D.
2. **Panel Administrator**: Mengelola peserta (tim & pasangan pemain), menyusun jadwal *Round-Robin* multi-meja & hari libur, menginput hasil pertandingan, mengelola poster & berita, memantau analitik pengunjung, mencetak laporan berkala (harian/mingguan/bulanan berdasarkan `updated_at`), mem-backup & memulihkan database, serta menyediakan ruang game hiburan khusus admin.

### 1.2 Aktor & Hak Akses
| Aktor | Hak Akses | Batasan |
| :--- | :--- | :--- |
| **Publik / Pengunjung** | Melihat dashboard, jadwal (jika bukan `draft`), klasemen, histori, detail tim/laga, berita, cetak PDF, reaksi berita, subscribe notifikasi FCM, bermain **Game Dam Batu 3D** (`/game`). | Tidak dapat mengakses `/admin/*`; kunjungan dicatat di `visitor_logs` kecuali bot/API/cetak. |
| **Administrator** | Akses penuh ke seluruh fitur publik + seluruh rute `/admin/*` (Dashboard, Tren Tim, Tim, Jadwal, Input Hasil, Poster, Berita, Pengaturan, Laporan PDF, Analytics, Backup/Restore, Monopoli, Flappy Bird, Ludo Queen, Catur 3D). | Wajib login via `/login` (`auth` middleware). |

---

## 2. Inventaris Modul & Fitur Eksisting

### 2.1 Modul Publik
- **Dashboard & Klasemen**: Menghitung peringkat berdasarkan *Poin Liga (PTS) → Menang (W) → Selisih Batu (SB) → Batu Masuk (BM) → Head-to-Head → Nama Tim* (`TournamentService::hitungKlasemen`).
- **Live Auto-Update** (`public/assets/js/app.js` & `ApiController@checkUpdate`): Polling `/api/check_update.php` setiap 5 detik untuk menyegarkan konten `<main>` publik tanpa reload halaman penuh.
- **Berita & Reaksi** (`BeritaController`): Berita berstatus *published*, *scheduled*, atau *draft*, penghitung tampilan unik per sesi, dan multi-reaksi emoji berbasis `visitor_hash` + rate limit `throttle:30,1`.
- **Web Push Notification (FCM)** (`FcmController`, `FcmService`): Service worker dinamis (`/firebase-messaging-sw.js`), manifest (`/manifest.json`), serta notifikasi skor live dan berita baru.
- **Cetak PDF** (`PdfController`): Klasemen, Jadwal (2 hari per halaman), dan Daftar Tim via `DomPDF`.
- **Hiburan Publik**: Game Dam Batu 3D (`/game`).

### 2.2 Modul Admin
- **Generator Jadwal Turnamen** (`JadwalController`, `TournamentService::buatJadwal`): Penjadwalan *Round-Robin* tanpa bentrok tim pada slot waktu sama, mendukung multi-meja, hari aktif, tanggal libur (*blackout dates*), geser waktu (`geserJadwal`), dan koreksi slot manual (`updateSlot`).
- **Laporan & Analitik** (`ReportController`, `PengaturanController@analytics`): Filter laporan mutlak berdasarkan `updated_at` laga selesai, ekspor PDF laporan, serta pelacakan kunjungan publik anti *IP-spoofing* (`TrackPublicVisit`).
- **Backup & Restore** (`PengaturanController@backup`, `uploadDb`): Ekspor JSON serta impor JSON atau database SQLite lama.
- **Koleksi Game Khusus Admin** (`/admin/pengaturan/*`):
  1. **Monopoli Nusantara 3D** vs AI (3 level, animasi dadu 3D, langkah bidak, pembayaran/barter, akses hipotek penuh saat giliran).
  2. **Flappy Bird** (kanvas arcade + skor tertinggi lokal).
  3. **Ludo Queen 3D** (meja 3D, pion berdiri 3D, lempar kubus dadu 3D ke tengah papan, 3 level AI).
  4. **Catur 3D** (aturan FIDE lengkap, Rokade, En Passant, Promosi, 3 level AI Alpha-Beta + Quiescence, Jam Catur, Notasi Aljabar).

---

## 3. Checklist Bagian yang Harus Diperbaiki

Daftar berikut disusun berdasarkan hasil audit kode aktual terhadap keamanan sistem, bug/edge-case logika, UI/UX, dan performa.

### Fase 1 — Keamanan Sistem (Security Hardening)
> Prioritas tertinggi sebelum aplikasi dijalankan pada server produksi/publik.

- [x] **1.1 Tambahkan Rate Limiting pada Endpoint Login Admin (`throttle`)**
  - **Lokasi:** [routes/web.php](file:///d:/laragon/www/dambatu/routes/web.php#L69-L71), [AuthController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/AuthController.php#L19-L39)
  - **Masalah:** Endpoint `POST /login` dan `POST /admin/login` belum memiliki pembatasan percobaan login sehingga rentan serangan *brute-force*.
  - **Perbaikan:** Pasang `->middleware('throttle:5,1')` pada kedua rute `POST` login dan tambahkan pesan error bahasa Indonesia saat terkena limit (`429`).

- [x] **1.2 Batasi Rute Logout Hanya untuk Method `POST`**
  - **Lokasi:** [routes/web.php](file:///d:/laragon/www/dambatu/routes/web.php#L72-L73)
  - **Masalah:** `Route::match(['get', 'post'], '/logout', ...)` mengizinkan logout via `GET`, sehingga berisiko *logout CSRF* atau terpicu oleh *prefetching browser*.
  - **Perbaikan:** Ubah `/logout` dan `/admin/logout` menjadi `Route::post(...)` (form logout di `layouts/admin.blade.php` sudah menggunakan `POST`), dan pastikan seluruh test ikut terverifikasi.

- [x] **1.3 Perketat Validasi Upload File di `PosterController`**
  - **Lokasi:** [PosterController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/PosterController.php#L24-L60) & [PosterController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/PosterController.php#L80-L104)
  - **Masalah:** Upload poster belum menyertakan rule `'image'` dan masih memakai `$file->getClientOriginalExtension()` alih-alih `guessExtension()` berbasis MIME seperti pada `BeritaController`.
  - **Perbaikan:** Tambahkan validasi `'image'` dan tentukan ekstensi output menggunakan `$file->guessExtension()` dengan daftar putih ketat `['jpg', 'jpeg', 'png', 'webp', 'gif']`.

- [x] **1.4 Nonaktifkan `isPhpEnabled` & `isRemoteEnabled` pada Konfigurasi DomPDF**
  - **Lokasi:** [PdfController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/PdfController.php#L25-L29), [PdfController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/PdfController.php#L76-L81), [PdfController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/PdfController.php#L104-L109), [ReportController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/ReportController.php#L70-L75)
  - **Masalah:** Opsi `'isPhpEnabled' => true` dan `'isRemoteEnabled' => true` meningkatkan permukaan serangan apabila ada template atau konten teks PDF yang tidak aman, padahal template PDF tidak memerlukan PHP eval atau aset eksternal.
  - **Perbaikan:** Set `'isPhpEnabled' => false` dan `'isRemoteEnabled' => false` pada seluruh pemanggilan `Pdf::loadView(...)`.

- [x] **1.5 Tambahkan Middleware HTTP Security Headers**
  - **Lokasi:** [bootstrap/app.php](file:///d:/laragon/www/dambatu/bootstrap/app.php) & [SecurityHeaders.php](file:///d:/laragon/www/dambatu/app/Http/Middleware/SecurityHeaders.php)
  - **Masalah:** Response belum menyertakan header keamanan standar (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`).
  - **Perbaikan:** Daftarkan middleware web untuk menyisipkan header keamanan standar tanpa memblokir resource CDN/FCM yang dibutuhkan.

---

### Fase 2 — Perbaikan Bug & Edge-Case Logika (Bug Fixes)
> Menutup celah konsistensi data, validasi input, dan kebocoran draf.

- [x] **2.1 Tutup Kebocoran Jadwal `draft` pada Rute Publik `/cetak/jadwal`**
  - **Lokasi:** [PdfController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/PdfController.php#L40-L66)
  - **Masalah:** Berbeda dengan `HomeController@jadwal` yang menyembunyikan pertandingan saat `$liga->status === 'draft'`, `PdfController@jadwal` tetap mengekspor seluruh pertandingan meskipun liga masih `draft`.
  - **Perbaikan:** Jika `$liga->status === 'draft'` dan user belum login sebagai admin (`!auth()->check()`), kosongkan data pertandingan pada PDF jadwal atau tampilkan pesan bahwa jadwal belum dipublikasikan.

- [x] **2.2 Tambahkan Validasi Tegas pada `PertandinganController@simpanHasil`**
  - **Lokasi:** [PertandinganController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/PertandinganController.php#L65-L99)
  - **Masalah:** Input `status`, `skor_home`, `skor_away`, `pemenang_id`, dan `catatan` belum melalui `$request->validate(...)`, khususnya `status` tidak dibatasi pada `Rule::in(array_keys(TournamentService::STATUS_MATCH))`.
  - **Perbaikan:** Terapkan `$request->validate(...)` yang ketat untuk status, skor (`integer|min:0|max:9999`), `pemenang_id`, dan `catatan` (`nullable|string|max:500`).

- [x] **2.3 Tambahkan Validasi Input pada `JadwalController@updateSlot`**
  - **Lokasi:** [JadwalController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/JadwalController.php#L158-L170)
  - **Masalah:** Input `tanggal`, `waktu`, dan `lokasi` disimpan langsung tanpa validasi format (`date` / `regex` / panjang string).
  - **Perbaikan:** Tambahkan `$request->validate(['tanggal' => 'nullable|date', 'waktu' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'], 'lokasi' => 'nullable|string|max:60'])`.

- [x] **2.4 Tangani Pergantian Tanggal Saat Slot Jadwal Melewati Pukul 24:00**
  - **Lokasi:** [TournamentService.php](file:///d:/laragon/www/dambatu/app/Services/TournamentService.php#L151-L190)
  - **Masalah:** Perhitungan `date('H:i', strtotime($jamMulai) + $slotIndex * $durasi * 60)` berputar ke `00:xx` pada `$tanggal` yang sama jika total durasi slot dalam satu hari melewati tengah malam.
  - **Perbaikan:** Hitung selisih menit harian dan pindahkan ke hari pertandingan aktif berikutnya bila waktu slot melewati `23:59`.

- [x] **2.5 Sertakan Data `beritas` dan `posters` pada Fitur Backup & Restore JSON**
  - **Lokasi:** [PengaturanController.php](file:///d:/laragon/www/dambatu/app/Http/Controllers/Admin/PengaturanController.php#L236-L420)
  - **Masalah:** Backup JSON saat ini hanya mengekspor `liga`, `tims`, dan `pertandingans`; data `beritas` dan `posters` belum ikut tersimpan saat backup/restore penuh.
  - **Perbaikan:** Sertakan `beritas` dan `posters` secara opsional/backward-compatible pada payload JSON `backup()` dan dukung pemulihannya di `uploadDb()` tanpa menghapus data lama bila file backup versi lama tidak memiliki key tersebut.

---

### Fase 3 — Perbaikan UI/UX & Konsistensi Tema (Frontend Polish)
> Meningkatkan kenyamanan pengguna pada Mode Gelap, navigasi admin, dan stabilitas interaksi live.

- [x] **3.1 Lengkapi Dukungan Mode Gelap (`dark:`) pada Elemen `<footer>`**
  - **Lokasi:** [layouts/app.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/app.blade.php#L332-L341) & [layouts/admin.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/admin.blade.php#L368-L377)
  - **Masalah:** Elemen `<footer>` masih memakai `bg-white/60 border-slate-200/80 text-slate-500` tanpa kelas `dark:`, sehingga tetap berwarna putih terang saat Mode Gelap aktif.
  - **Perbaikan:** Tambahkan varian `dark:bg-slate-900/80 dark:border-slate-800 dark:text-slate-400` serta warna teks anak elemen `dark:text-slate-200` / `dark:text-slate-500`.

- [x] **3.2 Perbaiki Kontras Mode Gelap pada Flash Alert Box**
  - **Lokasi:** [layouts/app.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/app.blade.php#L263-L327) & [layouts/admin.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/admin.blade.php#L295-L361)
  - **Masalah:** Kotak flash message (`bg-emerald-50`, `bg-rose-50`, `bg-amber-50`, `bg-indigo-50`) belum memiliki styling `dark:` yang lembut sehingga terlalu menyilaukan saat Mode Gelap aktif.
  - **Perbaikan:** Tambahkan varian `dark:bg-*-950/60 dark:border-*-800/70 dark:text-*-200`.

- [x] **3.3 Rapikan Struktur Dropdown Menu `Pengaturan` di Navigasi Admin**
  - **Lokasi:** [layouts/admin.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/admin.blade.php#L147-L202) & [layouts/admin.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/admin.blade.php#L258-L270)
  - **Masalah:** Dropdown **Pengaturan** saat ini berisi 11 item (konfigurasi, laporan/analitik, dan 4 mini-game) tanpa label seksi sehingga terasa memanjang.
  - **Perbaikan:** Tambahkan label pemisah grup (*Sistem & Turnamen*, *Laporan & Analitik*, *Mini Games Admin*) pada dropdown desktop dan mobile agar cepat dipindai.

- [x] **3.4 Optimalkan Polling Live Auto-Update di `public/assets/js/app.js`**
  - **Lokasi:** [public/assets/js/app.js](file:///d:/laragon/www/dambatu/public/assets/js/app.js#L221-L267)
  - **Masalah:** Polling `/api/check_update.php` tetap berjalan saat tab tidak aktif (`document.hidden`), dan penggantian `curMain.innerHTML` dapat mereset fokus filter yang sedang diketik/dipilih pengguna.
  - **Perbaikan:** Lewati fetch jika `document.hidden` bernilai `true` dan tunda penggantian DOM apabila user sedang fokus di dalam `<input>`, `<select>`, atau `<textarea>`.

---

### Fase 4 — Performa & Pemeliharaan Jangka Panjang (Performance & Maintenance)
- [ ] **4.1 Optimalkan Pengiriman Massal FCM dengan `Http::pool()`**
  - **Lokasi:** [FcmService.php](file:///d:/laragon/www/dambatu/app/Services/FcmService.php#L264-L291)
  - **Masalah:** Pengiriman notifikasi dilakukan satu per satu secara sinkron (`Http::post`), yang berisiko memperlambat request simpan skor saat subscriber banyak.
  - **Perbaikan:** Gunakan `Http::pool()` dalam *chunk* (mis. 25–50 token per batch) agar latensi pengiriman jauh lebih cepat *(opsional saat jumlah subscriber meningkat)*.

- [x] **4.2 Tambahkan Command/Jadwal Pruning Log Analitik (`visitor_logs`)**
  - **Lokasi:** [routes/console.php](file:///d:/laragon/www/dambatu/routes/console.php#L17-L22)
  - **Masalah:** Tabel `visitor_logs` terus bertambah tanpa batas retensi otomatis apabila tidak di-reset manual oleh admin.
  - **Perbaikan:** Menambahkan command Artisan `visitor:prune {--days=90}` untuk membersihkan log kunjungan lama tanpa menyentuh data terbaru.

- [ ] **4.3 Evaluasi Migrasi Tailwind CSS ke Vite Build Lokal (Opsional Produksi)**
  - **Lokasi:** [layouts/app.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/app.blade.php#L23) & [layouts/admin.blade.php](file:///d:/laragon/www/dambatu/resources/views/layouts/admin.blade.php)
  - **Masalah:** Ketergantungan pada `cdn.tailwindcss.com` membuat kompilasi CSS berjalan di *client* dan membutuhkan akses internet ke CDN untuk styling awal.
  - **Perbaikan:** Pertimbangkan mengompilasi CSS statis lewat Vite (`@vite`) untuk eliminasi FOUC dan dukungan *offline* lebih baik.

---

## 4. Kriteria Penerimaan (Definition of Done)

Setiap checklist dianggap selesai apabila memenuhi seluruh kriteria berikut:
1. Perubahan diimplementasikan bersih sesuai pola kode Laravel yang berlaku di repositori.
2. Tersedia *automated feature/unit test* yang memverifikasi perbaikan keamanan maupun bug logika (misalnya uji *rate limit login*, penolakan `GET /logout`, penyembunyian jadwal `draft` di `/cetak/jadwal`, dan validasi input admin).
3. Seluruh test suite berjalan dan lulus 100% (`php artisan test`).
4. Seluruh file JavaScript lolos pemeriksaan sintaks (`node --check`).
