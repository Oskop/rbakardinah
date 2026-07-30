# Walkthrough - Pengelompokan Dokumen KAK, RAK, dan RTP Berdasarkan Unit Bawahan (Operator) di Tampilan Supervisor

Fitur pengelompokan dokumen KAK, RAK, dan RTP berdasarkan Operator (unit bawahan) di tampilan Supervisor telah berhasil diimplementasikan dan diverifikasi.

---

## Perubahan yang Telah Dilakukan

### 1. Database Migration
* **[2026_07_30_132043_add_user_id_to_rba_submission_documents_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_07_30_132043_add_user_id_to_rba_submission_documents_table.php)**:
  * Menambahkan kolom `user_id` ke tabel `rba_submission_documents`.
  * Mengisi kolom `user_id` pada dokumen lama menggunakan ID pengunggah (`uploaded_by`) dari versi dokumen pertama.
  * Mengganti indeks unique lama `['rba_submission_id', 'type']` dengan indeks unique baru `['rba_submission_id', 'type', 'user_id']`.

### 2. Backend Layer
* **[RbaSubmissionDocument.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmissionDocument.php)**:
  * Menambahkan `user_id` ke dalam `$fillable`.
  * Menambahkan relasi `user()` (`belongsTo` ke model `User`).
* **[DocumentController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DocumentController.php)**:
  * Memperbarui `uploadDocument()` untuk menyertakan `'user_id' => Auth::id()`.
  * Memperbarui `history()` agar dapat menerima parameter `user_id` query string guna memuat riwayat versi dokumen milik Operator spesifik.
* **[SubmissionController.php (Operator)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)**:
  * Memperbarui method `show()` dengan memfilter relasi eager loading `documents` berdasarkan `user_id == Auth::id()` agar Operator hanya memuat dokumen miliknya sendiri.
* **[ReviewController.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)**:
  * Memperbarui method `show()` untuk memuat daftar seluruh Operator bawahan di unit kerja Supervisor tersebut (`$operators`) dan mengelompokkan dokumen berdasarkan `user_id` (`$documents`).

### 3. Frontend Layer
* **[show.blade.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)**:
  * Memperbarui seksi Dokumen Realisasi & Penyesuaian (KAK, RAK, RTP) agar melakukan iterasi per Operator bawahan (`$operators`).
  * Menampilkan header identitas Operator (Nama & Email) beserta grid berkas KAK, RAK, dan RTP khusus milik Operator tersebut.
  * Menyertakan parameter `user_id` pada tautan "Lihat Riwayat Versi" dokumen.

---

## Verifikasi yang Dilakukan

1. **Migrasi Database**:
   ```bash
   php artisan migrate
   ```
   *Hasil*: Migrasi `2026_07_30_132043_add_user_id_to_rba_submission_documents_table` berhasil dijalankan.

2. **Automated Unit & Feature Tests**:
   ```bash
   php artisan test
   ```
   *Hasil*: SELURUH SUITE TEST LULUS (PASS - 0 error).

---

## Kesimpulan

Sistem kini berhasil memisahkan dokumen KAK, RAK, dan RTP untuk masing-masing Operator (Unit Bawahan), sehingga pengunggahan dokumen oleh satu Operator tidak akan menindih berkas Operator lain di unit yang sama. Supervisor dapat melihat dan meninjau berkas pendukung milik setiap Operator bawahan secara terstruktur.
