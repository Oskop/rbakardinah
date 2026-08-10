# Implementation Plan - Fix Display Latar Belakang pada Cetak Supervisor saat Filter Operator Spesifik

Perbaikan bug pada fitur cetak Supervisor (Usulan Rincian Belanja dan RBA Final) di mana teks Latar Belakang Sub-Unit (`$submission->background`) tetap muncul pada hasil pratinjau dan cetak meskipun Supervisor hanya memilih **satu atau beberapa Operator spesifik** (bukan seluruh Operator bawahan).

---

## User Review Required

> [!IMPORTANT]
> **Analisis & Solusi Bug Latar Belakang Cetak Supervisor**:
> 1. **Penyebab Utama (Root Cause)**:
>    - Pada `App\Http\Controllers\Supervisor\ReviewController.php`, variabel `$includeBackground` hanya mengecek parameter `include_background == '1'`, tanpa memperhitungkan apakah Supervisor memilih **Semua Operator** atau **Pilih Operator Spesifik** (`$selectedOperatorIds`).
>    - Karena data Latar Belakang (`$submission->background`) bersifat akumulatif unit / milik operator tertentu, menampilkannya saat mencetak usulan operator spesifik menyebabkan latar belakang milik operator lain / unit ikut tercetak.
> 2. **Solusi Perbaikan Tepat Sasaran**:
>    - Memperbarui logika evaluasi `$includeBackground` pada method `printPreview` dan `printPreviewFinal` di `ReviewController.php`:
>      Teks latar belakang **hanya ditampilkan** jika Supervisor memilih opsi cetak **Semua Operator** DAN mencentang opsi **Dengan Latar Belakang**.
>      Jika Supervisor memilih **Pilih Operator Spesifik** (bukan seluruh operator unit), maka teks latar belakang otomatis disembunyikan agar cetakan usulan operator tersebut murni dan tidak tercampur latar belakang operator lain.

---

## Proposed Changes

### Controller Layer

#### [MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Pada method `printPreview()` dan `printPreviewFinal()`:
  - Menghitung variabel `$isAllOperators`:
    ```php
    $isAllOperators = empty($selectedOperatorIds) || count($selectedOperatorIds) === $allOperators->count();
    ```
  - Memperbarui evaluasi `$includeBackground`:
    ```php
    $includeBackground = ($request->get('include_background', '1') == '1') && $isAllOperators;
    ```

---

### Automated Tests Layer

#### [MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Memperbarui / menambahkan assertion pada test case Supervisor print preview:
  - Memverifikasi saat `include_background=1` dan `operator_ids` diisi 1 operator spesifik, teks latar belakang unit/operator lain **TIDAK tampil** (`assertDontSee`).
  - Memverifikasi saat `include_background=1` dan cetak **Semua Operator**, teks latar belakang **TETAP tampil** (`assertSee`).

---

## Verification Plan

### Automated Tests
- Menjalankan test suite PHPUnit Supervisor:
  ```powershell
  php artisan test --filter=ReviewTest
  ```

### Manual Verification
1. Login sebagai Supervisor.
2. Buka halaman Workboard Submisi (`/supervisor/submissions/{id}`).
3. Klik **"🖨️ Cetak Rincian Usulan / RBA Final"**:
   - Skenario A: Pilih **Dengan Latar Belakang** + **Semua Operator** $\rightarrow$ Pastikan teks Latar Belakang TAMPIL di pratinjau cetak.
   - Skenario B: Pilih **Dengan Latar Belakang** + **Pilih 1 Operator Spesifik** $\rightarrow$ Pastikan teks Latar Belakang TIDAK TAMPIL di pratinjau cetak.
