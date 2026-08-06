# Implementation Plan - Add Column "AWAL" (Pagu Periode Sebelumnya) in Operator Submissions Table

Menambahkan kolom baru berjudul **"AWAL"** pada tabel "Rincian Biaya" di halaman Workboard/Usulan Belanja Operator, yang terletak di antara kolom **Deskripsi** dan **Volume**. Kolom ini menampilkan nominal pagu yang ditetapkan oleh Administrator untuk nomor rekening belanja terkait pada periode RBA sebelumnya.

## User Review Required

> [!IMPORTANT]
> - **Penempatan Kolom Baru**: Kolom **AWAL** diletakkan tepat di antara kolom **Deskripsi** dan kolom **Volume** pada tabel rincian biaya di tampilan usulan belanja operator.
> - **Aturan Logika Penentuan Pagu Periode Sebelumnya**:
>   - **RBA Tahun Y Tipe Murni**: Menampilkan pagu nomor rekening tersebut pada **RBA Tahun Y - 1 Tipe Perubahan**.
>   - **RBA Tahun Y Tipe Perubahan**: Menampilkan pagu nomor rekening tersebut pada **RBA Tahun Y Tipe Murni**.
>   - Apabila pagu periode sebelumnya belum ditetapkan atau tidak ditemukan, akan ditampilkan nominal `-`.

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Pencarian header RBA sebelumnya didasarkan pada urutan kombinasi Tahun (`year`) dan Tipe Periode (`period->name` mencakup Murni/Perubahan).

## Proposed Changes

### Controller Layer

#### [MODIFY] [SubmissionController.php (Operator)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Memperbarui method `show`:
  - Mengambil header RBA saat ini (`$currentHeader`).
  - Menentukan `previousHeader` berdasarkan logika:
    - Jika tipe periode RBA saat ini mengandung kata *"Perubahan"*, cari RBA Header pada **tahun yang sama** dengan tipe *"Murni"*.
    - Jika tipe periode RBA saat ini mengandung kata *"Murni"*, cari RBA Header pada **tahun sebelumnya (tahun - 1)** dengan tipe *"Perubahan"*.
    - Apabila tidak ditemukan, gunakan header RBA sebelum header saat ini berdasarkan urutan ID/Tahun.
  - Memuat data pagu rekening dari RBA sebelumnya (`$previousPagus`) dan meneruskannya ke view `operator.submissions.show`.

### View Layer

#### [MODIFY] [show.blade.php (Operator Submissions)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Memperbarui header tabel: menambahkan `<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">AWAL</th>` di antara kolom **Deskripsi** dan **Volume**.
- Memperbarui baris tabel `@forelse($submission->details as $detail)`: menyisipkan cell `<td>` untuk menampilkan nominal `$previousPagu` dengan format mata uang Rupiah (`Rp`).
- Mengubah `colspan="11"` pada baris tabel kosong menjadi `colspan="12"`.

---

## Verification Plan

### Automated Tests
- Menambahkan test case `test_operator_submission_view_displays_previous_period_pagu_in_awal_column` pada `tests/Feature/Operator/RbaDetailTest.php` untuk memvalidasi pengambilan dan kemunculan pagu periode sebelumnya pada kolom AWAL.
- Menjalankan `php artisan test`.

### Manual Verification
- Login sebagai Operator.
- Buka usulan RBA (misal: RBA 2026 Murni atau 2026 Perubahan).
- Pastikan tabel rincian biaya menampilkan kolom baru **AWAL** di antara **Deskripsi** dan **Volume**.
- Pastikan nominal pada kolom **AWAL** secara akurat mengambil nominal pagu rekening tersebut dari RBA periode sebelumnya (misal 2025 Perubahan / 2026 Murni).
