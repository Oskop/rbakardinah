# Walkthrough: Opsi Beralih ke PDF Eksisting pada Modal Upload Tabel Usulan Belanja

Telah ditambahkan fitur untuk beralih/mengganti dokumen ke PDF eksisting pada modal upload di tabel usulan belanja halaman `/operator/submissions/{submission_id}` tampilan operator, **tanpa merombak alur upload berkas fisik revisi PDF yang sudah ada sebelumnya**.

---

## Ringkasan Perubahan

### 1. Backend & Controller Layer

#### [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Mengambil daftar dokumen eksisting (`$existingDocuments`) yang terkait dengan `rba_submission_id` tersebut menggunakan relasi `latestVersion.details.accountCode`.
- Meneruskan variabel `$existingDocuments` ke view `operator.submissions.show`.

#### [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Pada method `uploadVersion(Request $request, RbaDetail $detail)`:
  - Memeriksa apakah `source_mode === 'existing'`:
    - Memvalidasi keberadaan `rba_detail_document_id`.
    - Memeriksa otorisasi kepemilikan unit dan Gate `uploadVersion`.
    - Mengaitkan attachment versi terbaru dari dokumen terpilih: `$detail->attachments()->sync([$doc->latestVersion->id])`.
    - Mereset status usulan menjadi `Draft` (`is_validated = false`, `is_submitted = false`, `is_rejected = false`, `rejection_reason = null`).
    - Menyinkronkan status makro pengajuan unit via `$detail->submission->syncValidationStatus()`.
    - Mengembalikan respons flash success: `"Berhasil mengganti ke dokumen eksisting: '{nama_dokumen}' (V{versi}). Status usulan kembali menjadi Draft."`.
  - Jika `source_mode !== 'existing'` (alur lama):
    - Seluruh validasi dan alur unggah berkas revisi PDF fisik baru (baik *Perbarui Bersama* maupun *Pisahkan Dokumen*) tetap berjalan 100% seperti semula (**Non-Breaking Guarantee**).

---

### 2. View & UI Layer

#### [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
1. **Alpine.js Data & State**:
   - Memetakan `$existingDocuments` ke array JavaScript `allDocuments` yang berisi `id`, `name`, `version`, `filename`, `file_url`, dan `details_count`.
   - Menambahkan state `sourceMode: 'upload'` (default), `selectedExistingDocId: null`, dan getter `availableExistingDocs` untuk memfilter dokumen selain yang sedang digunakan oleh usulan terkait.
2. **Standardisasi Trigger Modal**:
   - Tombol trigger modal pada baris usulan belanja (mode normal, mode over pagu, dan mode update versi lebih baru) kini secara seragam meneruskan `currentDocId`, `documentName`, dan `sharedDetails`.
3. **Penyempurnaan Modal Dialog**:
   - **Segmented Control / Tab Switcher**:
     - Tab 1: **"📤 Unggah Berkas PDF Revisi"** *(Default - alur unggah berkas fisik)*
     - Tab 2: **"🔄 Pilih Dokumen Lain"** *(Disertai badge jumlah dokumen eksisting lain yang tersedia)*
   - **Tampilan Tab Dokumen Eksisting**:
     - Menampilkan info banner panduan.
     - Menampilkan daftar kartu interaktif pilihan dokumen eksisting: radio button, judul dokumen, badge versi, nama file, jumlah usulan belanja terikat, serta tombol pratinjau "Lihat PDF" (membuka berkas di tab baru).
     - Menampilkan pesan informatif jika belum ada dokumen lain dalam pengajuan tersebut.
   - **Tombol Aksi**:
     - Menyesuaikan label dan validasi submit:
       - Mode Unggah: label "Simpan & Unggah PDF", aktif saat file PDF telah dipilih.
       - Mode Eksisting: label "Ganti ke Dokumen Terpilih", aktif saat dokumen eksisting telah dipilih.

---

## Hasil Pengujian

### Automated Tests
Semua pengujian otomatis berhasil dijalankan dengan status **100% PASS** tanpa kegagalan:

1. **Pengujian Khusus Fitur RBA Detail (`RbaDetailTest`)**:
   ```bash
   php artisan test --filter=RbaDetailTest
   ```
   **Hasil**: 23 passed (111 assertions), termasuk 2 pengujian baru:
   - `test_operator_can_switch_detail_to_existing_document_via_upload_version`: Memastikan operator dapat mengganti dokumen ke PDF eksisting dan status kembali ke `Draft`.
   - `test_switching_to_existing_document_resets_rejected_status_to_draft`: Memastikan usulan yang ditolak dapat dialihkan ke dokumen eksisting dan status penolakan dibersihkan.

2. **Pengujian Seluruh Modul Operator (`Feature/Operator`)**:
   ```bash
   php artisan test tests/Feature/Operator
   ```
   **Hasil**: 77 passed (344 assertions), 0 failures.

3. **Pengujian Seluruh Sistem Aplikasi (`php artisan test`)**:
   ```bash
   php artisan test
   ```
   **Hasil**: **259 passed (1295 assertions)**, 0 failures.

---

## Verifikasi Manual

1. Buka browser dan login sebagai Operator pengusul.
2. Akses halaman `/operator/submissions/{submission_id}`.
3. Pada tabel Rincian Biaya, klik tombol **Upload PDF Revisi** / ikon upload dokumen pada baris usulan.
4. Perhatikan modal dialog yang muncul:
   - Terbuka dengan tab default **"📤 Unggah Berkas PDF Revisi"** (alur lama tetap utuh).
   - Klik tab **"🔄 Pilih Dokumen Lain"**:
     - Muncul daftar kartu dokumen eksisting pengajuan tersebut lengkap dengan badge versi, nama berkas, dan tombol "Lihat PDF".
     - Pilih salah satu dokumen dan klik **"Ganti ke Dokumen Terpilih"**.
     - Halaman memuat ulang dengan pesan sukses, usulan sekarang terikat ke dokumen yang dipilih, dan status usulan kembali menjadi `Draft`.
