# Implementation Plan - Add Column "AWAL" (Pagu Periode Sebelumnya) in Supervisor Submissions Table

Menambahkan kolom baru berjudul **"AWAL"** pada tabel "Rincian Biaya" di halaman Peninjauan Usulan Belanja Supervisor (`supervisor.submissions.show`), yang terletak di antara kolom **Deskripsi** dan **Volume**. Kolom ini menyajikan nominal pagu yang ditetapkan oleh Administrator untuk nomor rekening belanja terkait pada periode RBA sebelumnya.

## User Review Required

> [!IMPORTANT]
> - **Penempatan Kolom**: Kolom **AWAL** diletakkan tepat di antara kolom **Deskripsi** dan kolom **Volume** pada tabel rincian belanja tampilan peninjauan supervisor.
> - **Aturan Logika Penentuan Pagu Periode Sebelumnya**:
>   - **RBA Tahun Y Tipe Murni**: Menampilkan pagu nomor rekening tersebut pada **RBA Tahun Y - 1 Tipe Perubahan**.
>   - **RBA Tahun Y Tipe Perubahan**: Menampilkan pagu nomor rekening tersebut pada **RBA Tahun Y Tipe Murni**.
>   - Apabila pagu periode sebelumnya belum ditetapkan atau tidak ditemukan, akan ditampilkan nominal `-`.

## Open Questions

> [!NOTE]
> - Logika pengambilan pagu periode sebelumnya seragam dengan yang sudah diterapkan pada tampilan Operator.

## Proposed Changes

### Controller Layer

#### [MODIFY] [ReviewController.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Memperbarui method `show`:
  - Mengambil header RBA saat ini (`$currentHeader = $submission->header`).
  - Menentukan `previousHeader` berdasarkan logika tipe periode RBA (Murni vs Perubahan) dan tahun RBA.
  - Memuat data pagu rekening RBA sebelumnya (`$previousPagus`) keyed by `account_code_id`.
  - Mengirimkan variabel `$previousPagus` ke view `supervisor.submissions.show`.

### View Layer

#### [MODIFY] [show.blade.php (Supervisor Submissions)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Memperbarui header tabel: menyisipkan `<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">AWAL</th>` di antara kolom **Deskripsi** dan **Volume**.
- Memperbarui baris tabel `@forelse($submission->details as $detail)`: menyisipkan cell `<td>` untuk menampilkan nominal `$previousPagu` dengan format Rupiah (`Rp`).
- Mengubah `colspan="11"` pada baris kosong menjadi `colspan="12"`.
- Menyesuaikan `min-w-[1100px]` menjadi `min-w-[1200px]` untuk menampung 12 kolom dengan rapi.

---

## Verification Plan

### Automated Tests
- Menambahkan test case `test_supervisor_can_see_previous_period_pagu_in_awal_column` pada `tests/Feature/Supervisor/ReviewTest.php`.
- Menjalankan `php artisan test`.

### Manual Verification
- Login sebagai Supervisor.
- Buka peninjauan usulan RBA (misal: RBA 2026 Murni / 2026 Perubahan).
- Pastikan tabel rincian belanja menampilkan kolom **AWAL** di antara **Deskripsi** dan **Volume**.
- Pastikan nominal pada kolom **AWAL** secara akurat mengambil nominal pagu rekening tersebut dari RBA periode sebelumnya.
