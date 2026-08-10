# Walkthrough - Fitur Cetak Rincian Belanja & Pagu (RBA Final) Tampilan Supervisor

Fitur **Cetak Rincian Belanja dan Pagunya (RBA Final)** untuk **Tampilan Supervisor** telah berhasil diimplementasikan. Supervisor sekarang dapat mencetak laporan RBA Final yang menyandingkan usulan rincian belanja dengan nominal **PAGU FINAL (Rp)** dari masing-masing Kode Rekening, dengan opsi filter Latar Belakang dan Operator Penyusun yang sama persis seperti pada fitur Cetak Usulan Rincian Belanja.

---

## Perubahan yang Dilakukan

### 1. Controller & Routing Layer
- **[MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)**:
  - Menambahkan rute `GET /supervisor/submissions/{submission}/print-preview-final` -> `Supervisor\ReviewController@printPreviewFinal` (Name: `supervisor.submissions.print-preview-final`).
- **[MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)**:
  - Menambahkan method `printPreviewFinal()` untuk mengolah data rincian belanja, pagu final global (`RbaAccountPagu`), pagu AWAL periode sebelumnya, serta penyusunan label filter operator (`operatorFilterLabel`).

### 2. View Layer (Supervisor Submissions Workboard)
- **[MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)**:
  - Memperbarui Modal Konfigurasi Cetak Supervisor dengan opsi **Jenis Dokumen Laporan**:
    1. **Usulan Rincian Belanja** (Draft/Usulan)
    2. **Rincian Belanja & Pagunya (RBA Final)**
  - Form modal secara dinamis mengarahkan ke rute cetak yang sesuai berdasarkan jenis dokumen yang dipilih, sambil tetap menerapkan filter Latar Belakang dan Operator Penyusun.

### 3. Report Template Layer
- **[NEW] [supervisor_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php)**:
  - Template laporan cetak RBA Final Supervisor A4 Landscape.
  - Kop Surat Resmi RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Dokumen: `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`.
  - Grid Metadata (Unit Kerja, Periode RBA, Supervisor, Filter Operator, Status Submisi, Tanggal Cetak).
  - Tabel 11 Kolom: `NO`, `KODE REKENING`, `URAIAN & SPESIFIKASI BELANJA`, `OPERATOR`, `AWAL (Rp)`, `VOL`, `SATUAN`, `HARGA SATUAN (Rp)`, `TOTAL USULAN (Rp)`, `PAGU FINAL (Rp)`, dan `STATUS`.
  - Pengesahan Tanda Tangan Supervisor & Operator.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

### 4. Automated Tests Layer
- **[MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)**:
  - Menambahkan unit test `test_supervisor_can_preview_rba_final_print_report_with_pagu_and_operator_filters()`.

---

## Verification Results

### Automated Tests
- Menjalankan test suite PHPUnit:
  ```powershell
  php artisan test --filter=ReviewTest
  ```
  **Status**: PASS (4/4 tests passed clean, exit code 0).

### Verification Summary
1. Supervisor dapat mengakses modal cetak di workboard `/supervisor/submissions/{id}` dan memilih antara **Usulan Rincian Belanja** atau **Rincian Belanja & Pagunya (RBA Final)**.
2. Filter Operator (Semua Operator vs Operator Spesifik) dan filter Latar Belakang berfungsi dengan sempurna pada cetakan RBA Final.
3. Dokumen laporan cetak menyajikan kolom **OPERATOR** dan kolom **PAGU FINAL (Rp)** secara presisi dan rapi saat diprint atau disimpan ke PDF.
