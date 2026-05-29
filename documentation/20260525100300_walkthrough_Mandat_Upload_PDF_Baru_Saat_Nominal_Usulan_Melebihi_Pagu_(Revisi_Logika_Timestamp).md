# Walkthrough - Mandat Upload PDF Baru Saat Nominal Usulan Melebihi Pagu (Revisi Logika Timestamp)

Fitur ini telah diimplementasikan sepenuhnya untuk mewajibkan Operator mengunggah berkas PDF rincian belanja baru (sebagai revisi/penyesuaian) apabila total usulan nominal usulan belanja melebihi nominal pagu global yang ditetapkan oleh Administrator.

## Perubahan yang Dilakukan

### 1. Backend & Logika Bisnis (Sudah di-commit sebelumnya)
- **Model `RbaDetail`**:
  - Metode `isExceedingPagu()` ditambahkan untuk mendeteksi apakah nominal request usulan melebihi pagu global.
  - Metode `hasUploadedRevision()` ditambahkan untuk mendeteksi apakah ada PDF revisi yang diunggah setelah pagu global terakhir kali di-update (`attachment.created_at >= pagu.updated_at`).
- **Policy `RbaDetailPolicy`**:
  - Metode `uploadVersion()` dan `submit()` ditambahkan/diperbarui untuk membatasi hak akses Operator dalam mengunggah versi PDF revisi baru serta memvalidasi pengajuan item yang melebihi pagu.
- **Controllers**:
  - `DetailController`: Memperbarui `submitItem` untuk menolak pengajuan jika usulan melebihi pagu tetapi belum ada PDF revisi yang diunggah.
  - `ReviewController`: Memperbarui `toggleDetailValidation` untuk memblokir Supervisor dari memvalidasi item yang melebihi pagu tanpa adanya revisi PDF terbaru.

### 2. Frontend / Tampilan Antarmuka (Sudah di-commit sebelumnya)
- **Halaman Operator (`operator/submissions/show.blade.php`)**:
  - Menampilkan badge merah `⚠ Wajib Upload PDF Baru` dan form upload jika nominal melebihi pagu dan belum ada revisi PDF terbaru.
  - Menyembunyikan tombol "Ajukan" jika revisi belum diunggah.
  - Menampilkan badge hijau `✓ PDF Penyesuaian Diunggah` dan mengaktifkan tombol "Ajukan" setelah file revisi diunggah.
- **Halaman Supervisor (`supervisor/submissions/show.blade.php`)**:
  - Menampilkan indikator `⚠ Butuh PDF Baru` dan menonaktifkan tombol validasi jika berkas revisi belum diunggah.
  - Menampilkan indikator `✓ PDF Penyesuaian` jika berkas revisi sudah diunggah.

### 3. Pengujian / Automated Tests (Unstaged Changes)
- **Pembaruan Pabrik & Model**:
  - [UserFactory.php](file:///c:/Users/PC12/Project/rbakardinah/database/factories/UserFactory.php): Menambahkan `'is_active' => true` sebagai default.
  - Menyesuaikan kode pembuatan kelompok belanja di seluruh file test dengan menambahkan atribut `kode` yang wajib diisi:
    - [PaguTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Admin/PaguTest.php)
    - [HistoryTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/General/HistoryTest.php)
    - [RbaDetailFeaturesTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Operator/RbaDetailFeaturesTest.php)
    - [RbaDetailTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Operator/RbaDetailTest.php)
    - [ReviewTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- **Pengujian Fitur Baru**:
  - Menambahkan test case `test_operator_must_upload_new_pdf_when_nominal_exceeds_pagu` di [RbaDetailTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Operator/RbaDetailTest.php) untuk menguji kewajiban unggah PDF revisi bagi Operator sebelum mengajukan usulan yang melebihi pagu.
  - Menambahkan test case `test_supervisor_cannot_validate_item_exceeding_pagu_without_revision` di [RbaDetailTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Operator/RbaDetailTest.php) untuk memverifikasi bahwa Supervisor diblokir dari memvalidasi item usulan yang melebihi pagu jika belum ada revisi PDF terbaru.

---

## Hasil Pengujian (Automated Verification)

Menjalankan pengujian lokal dengan PHPUnit:
```bash
vendor/bin/phpunit
```
**Hasil:**
`OK (41 tests, 106 assertions)`

Semua pengujian berjalan dengan sukses tanpa ada error.
