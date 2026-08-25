# Implementation Plan - Filter Usulan Belum Diajukan (Draft) dari Tampilan Supervisor

Memastikan bahwa usulan rincian belanja (`RbaDetail`) yang berstatus **Draft / Belum Diajukan** (`is_submitted = false`)—baik berupa usulan baru yang belum diklik "Ajukan" maupun usulan hasil edit pasca-validasi—**tidak muncul pada tampilan Supervisor**, dan baru akan tampil setelah Operator secara eksplisit menekan tombol **Ajukan** (`is_submitted = true`).

---

## User Review Required

> [!IMPORTANT]
> **Aturan Visibilitas Usulan pada Supervisor:**
> 1. **Usulan Berstatus Draft (`is_submitted = false`):** Hanya dapat dilihat dan dikelola oleh Operator pemilik usulan di halaman pengusulannya. Usulan ini **disembunyikan sepenuhnya** dari daftar review dan laporan cetak Supervisor.
> 2. **Usulan Berstatus Diajukan (`is_submitted = true`):** Ditampilkan pada antarmuka Supervisor untuk direview, divalidasi, atau ditolak.
> 3. **Usulan Pasca-Edit:** Ketika Operator mengedit usulan (sehingga statusnya kembali ke Draft `is_submitted = false`), usulan tersebut otomatis menghilang dari daftar review Supervisor hingga Operator kembali mengklik **Ajukan**.

---

## Proposed Changes

### 1. Supervisor Review Controller (`app/Http/Controllers/Supervisor/ReviewController.php`)

#### [MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- **Method `show(RbaSubmission $submission)`**:
  - Menambahkan constraint pada eager loading `details`:
    ```php
    'details' => function ($query) {
        $query->where('is_submitted', true)->with(['accountCode', 'attachments']);
    }
    ```
  - Memfilter perhitungan `$headerTotals` hanya untuk usulan yang telah diajukan (`is_submitted = true`).
- **Method `printPreview(Request $request, RbaSubmission $submission)`** & **`printPreviewFinal(...)`**:
  - Menambahkan constraint `where('is_submitted', true)` pada query pemuatan rincian usulan.
- **Method `toggleDetailValidation(...)`** & **`rejectDetail(...)`**:
  - Menambahkan validasi proteksi: jika `$detail->is_submitted === false`, batalkan proses dengan pesan error `"Usulan rincian belanja ini belum diajukan oleh Operator."`.

---

### 2. Pengujian Otomatis (`tests/Feature/Supervisor/ReviewTest.php`)

#### [MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Menambahkan pengujian:
  1. `test_supervisor_cannot_see_draft_unsubmitted_details`: Memastikan usulan rincian belanja dengan `is_submitted = false` tidak muncul dalam daftar review Supervisor.
  2. `test_detail_disappears_from_supervisor_when_edited_and_reappears_when_resubmitted`: Memastikan rincian yang diedit oleh Operator (status kembali Draft) hilang dari tampilan Supervisor, dan muncul kembali setelah Operator klik "Ajukan".

---

## Verification Plan

### Automated Tests
- Jalankan test suite Review Supervisor:
  `php artisan test --filter=ReviewTest`
- Jalankan test suite Rincian Belanja Operator:
  `php artisan test --filter=RbaDetailTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator**, buat usulan rincian belanja baru (status masih Draft, jangan klik Ajukan).
2. Login sebagai **Supervisor**, buka halaman Review Usulan Unit.
   - **Hasil yang diharapkan:** Rincian belanja Draft tersebut **tidak muncul** pada tabel review Supervisor.
3. Login kembali sebagai **Operator**, klik tombol **Ajukan** pada rincian belanja tersebut.
4. Login kembali sebagai **Supervisor**, refresh halaman Review.
   - **Hasil yang diharapkan:** Rincian belanja sekarang **muncul** dan siap untuk divalidasi.
5. Login sebagai **Operator**, klik **Edit** dan simpan perubahan.
6. Login sebagai **Supervisor**, refresh halaman Review.
   - **Hasil yang diharapkan:** Rincian belanja yang kembali berstatus Draft tersebut **otomatis hilang** dari tampilan Supervisor hingga diajukan kembali.
