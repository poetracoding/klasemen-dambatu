---
name: berita-dambatu
description: >-
  Use this skill whenever the user asks to write, draft, or create news articles
  (berita, rekap laga, liputan, pengumuman, analisis klasemen, kuda hitam,
  strategi tim, atau materi pemberitaan) for the Liga Dam Batu web application.
  Fetches live tournament standings, match scores, trends, and schedules from
  the Laravel database and formats ready-to-paste copy matching /admin/berita rules.
---

# Penulis Materi Berita Liga Dam Batu

Skill ini memastikan setiap materi berita yang dibuat selalu akurat berdasarkan data turnamen langsung di database serta langsung siap ditempel ke form `/admin/berita`.

## Langkah Kerja

1. **Wajib Ambil Data Langsung Terlebih Dahulu**
   Jalankan skrip read-only berikut melalui shell dari root proyek sebelum menulis satu kata pun:
   ```bash
   php .agents/skills/berita-dambatu/scripts/fetch_tournament_context.php
   ```
   - Jika permintaan menyebut tim atau duel spesifik, sertakan filter `--teams`:
     ```bash
     php .agents/skills/berita-dambatu/scripts/fetch_tournament_context.php --teams="SAYA AKAN LAWAN,KUDA BINAL" --recent=8
     ```
   - Opsional tambahkan `--recent=N` dan `--upcoming=N` sesuai kebutuhan.
   - **Dilarang mengarang** skor, nama tim, nama pasangan pemain, poin klasemen, ataupun jadwal.
   - Jika pengguna memberikan skor pertandingan terbaru yang belum sempat diinput ke database, gunakan angka terbaru dari pengguna dengan tetap menyandingkannya bersama konteks klasemen/riwayat dari database.

2. **Rujuk Sudut Berita & Aturan Kompetisi (Jika Perlu)**
   Lihat [references/angles-and-rules.md](./references/angles-and-rules.md) untuk hirarki tie-breaker klasemen resmi, alasan pengurutan performa memakai `updated_at DESC`, pilihan sudut penulisan, serta kalimat disclaimer standar.

3. **Patuhi Batas Validasi Form & Keamanan Render `/admin/berita`**
   - **Kategori Disarankan**: Pilih tepat satu dari `App\Models\Berita::KATEGORI_LIST` -> `Berita`, `Rekap Laga`, `Pengumuman`, atau `Liputan`.
   - **Judul**: Maksimal **180 karakter** (`Admin\BeritaController` memvalidasi `max:180`), menarik, jelas, dan plain text.
   - **Ringkasan Singkat**: Maksimal **300 karakter** (`max:300`), padat dengan angka kunci, dan plain text.
   - **Isi Materi Berita**: Maksimal **20.000 karakter** (`max:20000`) dan **wajib plain text bersih** — **JANGAN** gunakan sintaks Markdown (`**bold**`, `__italic__`, `# heading`, `` `code` ``) maupun tag HTML di dalam blok materi berita, karena halaman detail (`resources/views/berita_detail.blade.php`) merender isi menggunakan `{!! nl2br(e($berita->isi)) !!}`. Gunakan baris kosong antarparagraf dan penomoran polos (`1. ...`, `2. ...`).

4. **Kaidah Jurnalistik & Atribusi**
   - Tulis dengan gaya jurnalistik olahraga bahasa Indonesia yang hidup, menarik, dan berbasis fakta nyata (nama pasangan pemain, `MP`, `W-L`, `win rate`, selisih skor `Δ`, sisa laga tabungan, dan rekor `H2H` bila relevan).
   - Setiap kali menyebut prediksi atau tren kemenangan tim, sertakan catatan bahwa angka tersebut hanyalah perkiraan statistik dan kemenangan sejati tetap ditentukan di atas meja pertandingan.
   - Selalu akhiri `Isi Materi Berita` dengan baris pemisah dan sumber AI eksplisit:
     ```text
     ---
     Sumber: Disusun oleh AI (Antigravity / Gemini) berdasarkan data resmi Liga Dam Batu.
     ```

5. **Format Output & Batas Aksi**
   - Sajikan hasil akhir secara terpisah agar mudah disalin admin ke `/admin/berita`: **Kategori Disarankan**, lalu blok ```` ```text ```` masing-masing untuk **Judul**, **Ringkasan Singkat**, dan **Isi Materi Berita**.
   - Periksa mandiri sebelum menjawab: pastikan panjang `Judul <= 180` karakter, `Ringkasan Singkat <= 300` karakter, dan nol markup Markdown/HTML di dalam ketiga blok teks.
   - **Jangan** langsung menyimpan atau menyisipkan artikel ke tabel `beritas` kecuali pengguna secara eksplisit meminta untuk menyimpan/menerbitkan ke database sebagai `draft` atau `published`.
