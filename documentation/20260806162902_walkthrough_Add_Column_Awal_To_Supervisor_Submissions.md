# Walkthrough - Add Column "AWAL" (Pagu Periode Sebelumnya) in Supervisor Submissions Table

Penambahan kolom baru **"AWAL"** pada tabel "Rincian Biaya" di halaman Peninjauan Usulan Belanja Supervisor (`supervisor.submissions.show`), yang menyajikan nominal pagu yang ditetapkan Administrator untuk nomor rekening tersebut pada periode RBA sebelumnya.

## Changes Made

### Controller Layer
- **`app/Http/Controllers/Supervisor/ReviewController.php`**:
  - Memperbarui method `show` untuk menentukan `previousHeader` berdasarkan urutan Tahun & Tipe Periode RBA:
    - Jika RBA saat ini bertipe **Perubahan** (misal 2026 Perubahan), maka pencarian mengacu pada RBA **Murni** di **tahun yang sama (2026 Murni)**.
    - Jika RBA saat ini bertipe **Murni** (misal 2026 Murni), maka pencarian mengacu pada RBA **Perubahan** di **tahun sebelumnya (2025 Perubahan)**.
    - Menangani fallback ke header RBA sebelum header saat ini apabila tipe spesifik tidak ditemukan.
  - Memuat data `$previousPagus` (keyed by `account_code_id`) dan mengirimkannya ke view `supervisor.submissions.show`.

### View Layer
- **`resources/views/supervisor/submissions/show.blade.php`**:
  - Menyisipkan `<th ...>AWAL</th>` pada header tabel di antara kolom **Deskripsi** dan **Volume**.
  - Menyisipkan cell `<td>` yang menampilkan nominal pagu rekening dari periode sebelumnya (`$previousPagus[$detail->account_code_id]->nominal_pagu`) dalam format mata uang Rupiah (`Rp`).
  - Memperbarui `colspan` baris tabel kosong dari `11` menjadi `12`.
  - Memperbarui lebar minimum tabel menjadi `min-w-[1200px]` agar 12 kolom tampil lapang.

### Automated Testing
- **`tests/Feature/Supervisor/ReviewTest.php`**:
  - Menambahkan unit test `test_supervisor_can_see_previous_period_pagu_in_awal_column` untuk memvalidasi penentuan pagu RBA 2025 Perubahan yang muncul pada kolom **AWAL** di RBA 2026 Murni tampilan supervisor.

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
   PASS  Tests\Feature\ProfileTest
   PASS  Tests\Feature\Supervisor\ReviewTest
  ✓ supervisor can view their unit submissions
  ✓ supervisor can validate submission
  ✓ supervisor can see previous period pagu in awal column

  Tests:    50 passed (137 assertions)
  Duration: 3.80s
```
 Seluruh 50 pengujian lulus 100% tanpa hambatan.
