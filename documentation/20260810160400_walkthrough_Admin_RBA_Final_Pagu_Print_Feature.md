# Walkthrough - Fitur Cetak Rincian Belanja & Pagu (RBA Final) Tampilan Administrator

Implementasi fitur **Cetak Rincian Belanja dan Pagunya (RBA Final)** pada tampilan Administrator (`/admin/headers/{header}`) telah selesai dilaksanakan. Fitur ini menyajikan laporan RBA Final bersandingan dengan nominal Pagu Final dengan opsi pencetakan lengkap khas Administrator (opsi Latar Belakang, Seluruh RSUD, Filter Per Unit Kerja/Supervisor, Filter Per Operator Spesifik, dan Kombinasi Unit + Operator).

---

## Perubahan yang Dilakukan

### Routing Layer
- **[MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)**:
  - Menambahkan rute `GET /admin/headers/{header}/print-preview-final` (`admin.headers.print-preview-final`).

### Controller Layer
- **[MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)**:
  - Menambahkan method `printPreviewFinal()` untuk mengambil data Rincian Belanja (`RbaDetail`), data Pagu Global/Penetapan (`RbaAccountPagu`), data Pagu Periode Sebelumnya (`$previousPagus`), serta memproses parameter filter unit dan/atau operator.

### View Layer (Admin RBA Headers View)
- **[MODIFY] [show.blade.php (Admin Headers)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)**:
  - Memperbarui Modal Konfigurasi Cetak Administrator dengan penambahan radio button **1. Jenis Dokumen Laporan**:
    - **Usulan Rincian Belanja**
    - **Rincian Belanja & Pagu (RBA Final)**
  - Form action dikonfigurasi secara dinamis mengarah ke rute cetak usulan (`print-preview`) atau rute cetak RBA Final (`print-preview-final`).

### Report Template Layer
- **[NEW] [admin_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)**:
  - Membuat Blade template laporan cetak RBA Final Administrator A4 Landscape:
    - Kop Surat Resmi RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
    - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`.
    - Metadata Grid (Tahun Anggaran, Periode, Administrator, Filter Scope, Status Global, Tanggal Cetak).
    - Section I Latar Belakang Sub-Unit (Kondisional per Unit).
    - Section II Tabel Rincian Belanja & Pagu Final (12 Kolom: `NO`, `KODE REKENING`, `URAIAN & SPESIFIKASI BELANJA`, `UNIT KERJA`, `OPERATOR`, `AWAL (Rp)`, `VOL`, `SATUAN`, `HARGA SATUAN (Rp)`, `TOTAL USULAN (Rp)`, `PAGU FINAL (Rp)`, `STATUS`).
    - Ringkasan Grand Total Usulan & Grand Total Pagu Final.
    - Lembar Pengesahan Tanda Tangan Direktur / Administrator & Tim Anggaran.
    - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

### Automated Tests Layer
- **[MODIFY] [AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)**:
  - Menambahkan test case `test_admin_can_preview_rba_final_print_report_with_pagu_and_unit_operator_filters()`.

---

## Verification Results

### Automated Tests
- Menjalankan test suite PHPUnit Admin:
  ```powershell
  php artisan test --filter=AdminDashboardTest
  ```
  **Status**: PASS (3/3 tests passed clean).

### Verification Summary
1. **Jenis Dokumen Laporan**: Administrator dapat memilih mencetak Usulan Rincian Belanja atau Rincian Belanja & Pagu Final.
2. **Filter Scope Lengkap**: Seluruh opsi filter Admin (Seluruh RSUD, Filter Per Unit, Filter Per Operator, Kombinasi) berfungsi dengan akurat pada laporan RBA Final.
3. **Kolom Laporan**: Kolom `UNIT KERJA`, `OPERATOR`, dan `PAGU FINAL (Rp)` tampil rapi dan transparan.
