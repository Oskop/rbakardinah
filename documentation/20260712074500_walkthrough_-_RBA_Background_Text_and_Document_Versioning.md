# Walkthrough - RBA Background Text & Document Versioning

Rencana implementasi telah berhasil diwujudkan sepenuhnya. Berikut adalah rekapitulasi perubahan, hasil pengujian, dan cara memverifikasinya.

---

## Ringkasan Perubahan

### 1. Database & Migrasi
* **Latar Belakang Sub-unit**:
  * Menambahkan kolom `background` tipe `TEXT` (nullable) pada tabel `rba_submissions` melalui [2026_07_12_073100_add_background_to_rba_submissions.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_07_12_073100_add_background_to_rba_submissions.php).
* **Manajemen Dokumen (KAK, RAK, RTP)**:
  * Membuat tabel `rba_submission_documents` untuk jenis dokumen (`KAK`, `RAK`, `RTP`) per unit submission melalui [2026_07_12_073110_create_rba_submission_documents_tables.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_07_12_073110_create_rba_submission_documents_tables.php).
  * Membuat tabel `rba_submission_document_versions` untuk menampung berkas fisik (PDF), nomor versi, pengunggah, dan timestamp. Menggunakan constraint nama pendek agar kompatibel dengan pembatasan panjang identifier MySQL.

### 2. Model Eloquent
* **[RbaSubmission.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmission.php)**: Menambahkan `background` ke properti `$fillable` dan relasi `documents(): HasMany` ke model [RbaSubmissionDocument](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmissionDocument.php).
* **[RbaSubmissionDocument.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmissionDocument.php) [NEW]**: Model penampung tipe dokumen (`type`), yang memuat relasi `submission(): BelongsTo`, `versions(): HasMany`, dan `latestVersion(): HasOne`.
* **[RbaSubmissionDocumentVersion.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmissionDocumentVersion.php) [NEW]**: Model penampung berkas versi, yang memuat relasi `document(): BelongsTo` dan `uploader(): BelongsTo`.

### 3. Controller & Routing
* **[web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)**:
  * Mendefinisikan route untuk memperbarui latar belakang.
  * Mendefinisikan route secara global agar Operator, Supervisor, dan Admin bisa mengakses riwayat versi dokumen.
* **[SubmissionController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)**:
  * Menambahkan method `updateBackground` untuk memproses penginputan latar belakang oleh operator.
  * Memperbarui method `show` agar me-load relasi `documents.versions` dan `documents.latestVersion`.
* **[DetailController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**:
  * Memodifikasi method `create` dan `store` untuk memvalidasi bahwa `background` pada submission tidak boleh kosong. Jika kosong, operator akan diredirect kembali dengan pesan kesalahan.
* **[DocumentController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DocumentController.php) [NEW]**:
  * Mengatur logika unggah berkas KAK, RAK, dan RTP.
  * Menyimpan berkas di storage publik, otomatis menaikkan versi berkas (`version_number` + 1), serta mencatat user pengunggah.
  * Menampilkan halaman log riwayat versi dokumen.

### 4. Tampilan (Blade Views)
* **[operator/submissions/show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)**:
  * Menampilkan form textarea untuk Latar Belakang di bagian atas halaman.
  * Menampilkan tombol "+ Tambah Rincian" hanya jika latar belakang sudah terisi. Jika kosong, tombol dinonaktifkan dengan banner peringatan.
  * Menampilkan bagian unggah berkas KAK, RAK, dan RTP di bagian paling bawah halaman jika status RBA sudah terkunci (`status_global === 'Locked'`).
  * Menyediakan tombol "Unggah Revisi Baru" serta link "Lihat Riwayat Versi".
* **[supervisor/submissions/show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)**:
  * Menampilkan isi Latar Belakang sub-unit di bagian atas.
  * Menampilkan daftar KAK, RAK, dan RTP beserta unduhan versi terbaru dan tautan riwayat versi di bagian bawah.
* **[operator/documents/history.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/documents/history.blade.php) [NEW]**:
  * Menampilkan daftar versi unggahan dokumen tertentu secara kronologis lengkap dengan nama pengunggah, tanggal unggah, dan link unduh PDF-nya.

---

## Hasil Pengujian & Verifikasi

### Pengujian Otomatis
Seluruh rangkaian pengujian (termasuk pengujian fitur baru dan pengujian regresi) telah dijalankan dan **lulus 100%** (`44 passed, 0 failed`):

```bash
php artisan test
```

Metode pengujian baru yang ditambahkan di [RbaDetailTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Operator/RbaDetailTest.php):
1. **`test_operator_cannot_add_detail_if_background_is_empty`**: Memastikan operator tidak bisa menginput rincian belanja melalui form jika kolom latar belakang kosong.
2. **`test_operator_can_save_background`**: Memastikan operator bisa mengisi dan memperbarui data latar belakang.
3. **`test_operator_can_upload_kak_rak_rtp_versioned_documents_when_locked`**: Memastikan dokumen pendukung KAK, RAK, dan RTP bisa diunggah ketika RBA dikunci, serta penomoran versinya meningkat (V1 -> V2) dengan tepat tanpa menimpa berkas lama.
