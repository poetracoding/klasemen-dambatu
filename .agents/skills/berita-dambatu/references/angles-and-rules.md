# Referensi Sudut Berita & Aturan Kompetisi Liga Dam Batu

Gunakan panduan ini saat memilih kategori, struktur cerita, dan penjelasan aturan dalam artikel berita Liga Dam Batu.

## 1. Hirarki Tie-Breaker Klasemen Resmi (`TournamentService::hitungKlasemen`)

Jika dua atau lebih tim memiliki **Poin Klasemen (`pts`)** yang sama (3 poin per kemenangan), peringkat diurutkan berdasarkan:
1. **Poin Liga (`pts`)** terbanyak
2. **Jumlah Kemenangan (`W`)** terbanyak
3. **Selisih Skor / Batu (`Δ` / `SB` = `BM - BK`)** terbesar
4. **Total Poin Masuk (`BM`)** terbanyak
5. **Head-to-Head (`H2H`)** antar tim yang bersangkutan
6. **Nama Tim** secara alfabetis

> Penting dalam penulisan analisis: Karena `SB` (Selisih Skor) berada di urutan #3 sebelum `H2H` (#5), menjaga selisih poin tetap positif dan meminimalkan kekalahan telak sangat krusial bagi tim papan atas maupun papan bawah.

## 2. Urutan Performa Terkini (`updated_at DESC, id DESC`)

Analisis tren 5 pertandingan terakhir dan streak pada sistem diurutkan berdasarkan **`updated_at DESC, id DESC`**, bukan berdasarkan kolom `tanggal` jadwal semata. Alasannya, jadwal pertandingan dapat melompat atau dimainkan lebih awal tergantung kesiapan kedua pasangan di lapangan. Saat menceritakan "laga terbaru" atau "5 pertandingan terakhir", selalu ikuti urutan `updated_at` yang ditampilkan skrip helper.

## 3. Pemetaan Kategori & Template Sudut Berita

Pilih kategori **wajib tepat** dari `App\Models\Berita::KATEGORI_LIST`: `Berita`, `Rekap Laga`, `Pengumuman`, `Liputan`.

| Kebutuhan / Topik | Kategori Disarankan | Fokus Narasi & Data Wajib |
| :--- | :--- | :--- |
| **Gambaran Umum / Kickoff Turnamen** | `Berita` / `Liputan` | Nama liga & musim, periode tanggal, total tim/pemain, format round-robin, total pertandingan, jumlah meja & hari tanding, fitur portal digital (live klasemen, prediksi pra-laga, Game Dam Batu Online). |
| **Rekap Pertandingan / Laga Penutup** | `Rekap Laga` | Skor akhir, nama tim & kedua pemainnya, nomor laga/ronde/meja bila relevan, margin kemenangan, dampak langsung pada posisi klasemen, selisih skor, dan momentum tim pemenang maupun tim kalah. |
| **Duel Perebutan Puncak Klasemen** | `Rekap Laga` / `Berita` | Pemuncak klasemen vs penantang terdekat, jarak poin, jumlah laga dimainkan (`MP`) vs sisa laga (`SISA`), perbandingan selisih skor (`SB`), catatan head-to-head (`H2H`), serta ancaman tim lain di zona papan atas. |
| **Sorotan Kuda Hitam Tak Terkalahkan** | `Liputan` | Daftar tim dengan `MP > 0` dan `L = 0`, skor kemenangan mereka (khususnya jika mengalahkan tim unggulan), jumlah sisa laga tabungan dibanding pemuncak, serta proyeksi potensi poin maksimal. |
| **Analisis Strategi Papan Bawah** | `Liputan` | Progres total liga yang sudah selesai (`selesai / total`), bukti laga ketat/upset tim papan bawah melawan tim elite, jumlah tabungan laga tersisa (`SISA`), serta langkah taktis (menjaga selisih skor, fokus di poin kritis, menyapu laga papan tengah-bawah, pemanasan lewat statistik & Dam Batu Online). |
| **Pengumuman Jadwal / Libur / Aturan** | `Pengumuman` | Tanggal efektif, daftar tanggal libur resmi (`tanggal_libur`), perubahan jam/meja atau mekanisme tie-breaker/pelaksanaan yang perlu diperhatikan peserta. |

## 4. Kalimat Penafian (Disclaimer) Prediksi & Atribusi Sumber AI

- **Disclaimer Wajib Saat Membahas Peluang/Tren Kemenangan:**
  Tekankan bahwa angka persentase kemenangan atau proyeksi tren merupakan perkiraan statistik berdasarkan rekam jejak pertandingan — *kemenangan sejati tetap ditentukan di atas meja pertandingan*.
- **Footer Sumber AI Standar (di bagian akhir `Isi Materi Berita`):**
  ```text
  ---
  Sumber: Disusun oleh AI (Antigravity / Gemini) berdasarkan data klasemen, jadwal, dan catatan hasil pertandingan resmi Liga Dam Batu.
  ```
