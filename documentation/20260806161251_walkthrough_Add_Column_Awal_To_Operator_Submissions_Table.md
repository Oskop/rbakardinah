# Walkthrough - Add Column "AWAL" (Pagu Periode Sebelumnya) in Operator Submissions Table

Penambahan kolom baru **"AWAL"** pada tabel "Rincian Biaya" di tampilan Usulan Belanja Operator (`operator.submissions.show`), yang menyajikan nominal pagu yang ditetapkan Administrator untuk nomor rekening tersebut pada periode RBA sebelumnya.

## Changes Made

### Controller Layer
- **`app/Http/Controllers/Operator/SubmissionController.php`**:
  - Memperbarui method `show` untuk menentukan `previousHeader` berdasarkan urutan Tahun & Tipe Periode RBA:
    - Jika RBA saat ini bertipe **Perubahan** (misal 2026 Perubahan), maka pencarian mengacu pada RBA **Murni** di **tahun yang sama (2026 Murni)**.
    - Jika RBA saat ini bertipe **Murni** (misal 2026 Murni), maka pencarian mengacu pada RBA **Perubahan** di **tahun sebelumnya (2025 Perubahan)**.
    - Menangani fallback ke header RBA sebelum header saat ini apabila tipe spesifik tidak ditemukan.
  - Memuat data `$previousPagus` (keyed by `account_code_id`) dan mengirimkannya ke view `operator.submissions.show`.

### View Layer
- **`resources/views/operator/submissions/show.blade.php`**:
  - Menyisipkan `<th ...>AWAL</th>` pada header tabel di antara kolom **Deskripsi** dan **Volume**.
  - Menyisipkan cell `<td>` yang menampilkan nominal pagu rekening dari periode sebelumnya (`$previousPagus[$detail->account_code_id]->nominal_pagu`) dalam format mata uang Rupiah (`Rp`).
  - Memperbarui `colspan` baris tabel kosong dari `11` menjadi `12`.

### Automated Testing
- **`tests/Feature/Operator/RbaDetailTest.php`**:
  - Menambahkan unit test `test_operator_submission_view_displays_previous_period_pagu_in_awal_column` untuk memvalidasi penentuan pagu RBA 2025 Perubahan yang muncul pada kolom **AWAL** di RBA 2026 Murni.

---

## Verification Results

### Automated Tests
Menjalankan `php artisan test`:
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\Admin\PaguTest
   PASS  Tests\Feature\Auth\AuthenticationTest
   PASS  Tests\Feature\Auth\EmailVerificationTest
   PASS  Tests\Feature\Auth\PasswordConfirmationTest
   PASS  Tests\Feature\Auth\PasswordResetTest
   PASS  Tests\Feature\Auth\PasswordUpdateTest
   PASS  Tests\Feature\Auth\RegistrationTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\General\HistoryTest
   PASS  Tests\Feature\Operator\OperatorDashboardTest
   PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
   PASS  Tests\Feature\Operator\RbaDetailTest
  ✓ operator can view their submissions
  ✓ operator can create rba detail with pdf
  ✓ operator submission view displays previous period pagu in awal column
  ✓ operator can upload new version of pdf
  ✓ operator can submit item to supervisor
  ✓ operator can soft delete rba detail
  ✓ operator must upload new pdf when nominal exceeds pagu
  ✓ supervisor cannot validate item exceeding pagu without revision
  ✓ operator cannot add detail if background is empty
  ✓ operator can save background
  ✓ operator can upload kak rak rtp versioned documents when locked
   PASS  Tests\Feature\ProfileTest
   PASS  Tests\Feature\Supervisor\ReviewTest

  Tests:    49 passed (134 assertions)
  Duration: 23.30s
```
 Seluruh 49 pengujian lulus 100% tanpa hambatan.
