# Implementation Plan - Penguncian Usulan Rincian Belanja Pasca Validasi Supervisor

Memastikan bahwa ketika usulan rincian belanja telah **divalidasi oleh Supervisor (`is_validated = true`)**, usulan tersebut **terkunci penuh** sehingga Operator **tidak dapat lagi mengedit data (`update`), mengunggah revisi PDF (`uploadVersion`), maupun menghapus data (`delete`)**.

---

## Aturan Bisnis & Logika Penguncian

> [!IMPORTANT]
> **Kebijakan Akses Pasca Validasi Supervisor:**
> 1. **Kondisi Terkunci (Locked):**
>    - Usulan dengan status **Valid (`is_validated = true`)** telah disetujui oleh Supervisor unit kerja.
>    - Operator **dilarang**:
>      - Mengedit informasi rincian belanja (deskripsi, volume, harga satuan, dll.) -> HTTP 403 Forbidden.
>      - Mengunggah versi PDF revisi baru -> HTTP 403 Forbidden.
>      - Menghapus usulan rincian belanja -> HTTP 403 Forbidden.
> 2. **Kondisi Dapat Diedit / Revisi (Unlocked):**
>    - Usulan berstatus **Draft** (`is_submitted = false`, `is_validated = false`).
>    - Usulan berstatus **Ditolak** (`is_rejected = true`, `is_validated = false`).
> 3. **Antarmuka Pengguna (UI):**
>    - Baris rincian belanja yang berstatus **Valid** menampilkan indikator terkunci `🔒 Tervalidasi (Terkunci)`.
>    - Tombol **Edit**, **Hapus**, dan form **Upload Revisi PDF** disembunyikan/ditiadakan untuk rincian yang sudah valid.

---

## Proposed Changes

### 1. Kebijakan Otorisasi (`app/Policies/RbaDetailPolicy.php`)

#### [MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)
- **Method `update()`**:
  - Tambahkan guard check: jika `$rbaDetail->is_validated === true`, tolak dengan pesan `"Usulan rincian belanja yang sudah divalidasi oleh Supervisor tidak dapat diedit."`.
- **Method `uploadVersion()`**:
  - Tambahkan guard check: jika `$rbaDetail->is_validated === true`, tolak dengan pesan `"Usulan rincian belanja yang sudah divalidasi oleh Supervisor tidak dapat diunggah revisi PDF."`.
- **Method `delete()`**:
  - Pertahankan guard check: tolak penghapusan jika `$rbaDetail->is_validated === true`.

---

### 2. Controller Operator (`app/Http/Controllers/Operator/DetailController.php`)

#### [MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Pastikan method `edit()`, `update()`, `uploadVersion()`, dan `destroy()` memanggil `Gate::authorize(...)` yang secara ketat menerapkan kebijakan `RbaDetailPolicy`.

---

### 3. Tampilan Operator Workboard (`resources/views/operator/submissions/show.blade.php`)

#### [MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Pada kolom Aksi baris rincian belanja:
  - Jika `$detail->is_validated === true`: Tampilkan badge `🔒 Tervalidasi (Terkunci)` dan sembunyikan tombol Edit, Hapus, serta form Revisi PDF.
  - Jika `$detail->is_validated === false`: Tetap tampilkan tombol Edit, Hapus, Ajukan, dan form Revisi PDF sesuai status (Draft / Ditolak).

---

### 4. Pengujian Otomatis (`tests/Feature/Operator/RbaDetailTest.php`)

#### [MODIFY] [RbaDetailTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailTest.php)
- Menyesuaikan dan menambahkan pengujian:
  1. `test_operator_cannot_edit_validated_detail`: Menguji bahwa request PUT/PATCH edit pada detail yang sudah divalidasi ditolak dengan HTTP 403.
  2. `test_operator_cannot_upload_revision_pdf_on_validated_detail`: Menguji bahwa request upload revisi PDF pada detail yang sudah divalidasi ditolak dengan HTTP 403.
  3. `test_operator_cannot_delete_validated_detail`: Menguji bahwa request hapus pada detail yang sudah divalidasi ditolak dengan HTTP 403.
  4. `test_operator_can_edit_and_upload_revision_on_unvalidated_or_rejected_detail`: Memastikan usulan Draft/Ditolak tetap dapat diedit dan diunggah revisi PDF.

---

## Verification Plan

### Automated Tests
- Jalankan test suite Operator:
  `php artisan test --filter=RbaDetailTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator**, buat usulan rincian belanja baru (status Draft), lalu ajukan ke Supervisor.
2. Login sebagai **Supervisor**, validasi usulan rincian belanja tersebut (status menjadi Valid).
3. Login kembali sebagai **Operator** dan buka halaman Workboard RBA (`/operator/submissions/{submission}`):
   - Verifikasi baris rincian yang sudah divalidasi menampilkan badge `🔒 Tervalidasi (Terkunci)`.
   - Verifikasi tombol **Edit**, **Hapus**, dan form **Revisi PDF** tidak muncul.
   - Coba akses URL edit langsung (`/operator/details/{detail}/edit`) di browser untuk memastikan sistem merespons **403 Forbidden**.
