# Implementation Plan - Fitur Cetak Rincian Belanja & Pagu (RBA Final) Tampilan Administrator dengan Opsi Filter Lengkap

Menambahkan fitur **Cetak Rincian Belanja dan Pagunya (RBA Final)** pada tampilan Administrator (`/admin/headers/{header}`) dengan opsi pencetakan yang persis sama dengan fitur Cetak Usulan Rincian Belanja Administrator, yaitu mencakup filter Latar Belakang serta filter Scope (Seluruh RSUD, Filter Per Unit Kerja/Supervisor, Filter Per Operator Spesifik, dan Kombinasi Unit & Operator).

---

## User Review Required

> [!IMPORTANT]
> **Detail Fitur Cetak RBA Final Administrator**:
> 1. **Judul Dokumen Resmi**:
>    - `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`
> 2. **Pembaruan Modal Konfigurasi Cetak (Admin RBA View)**:
>    - Pada modal cetak Admin (`/admin/headers/{id}`), ditambahkan opsi **Jenis Dokumen Laporan**:
>      - **Usulan Rincian Belanja (Draft/Usulan)**
>      - **Rincian Belanja & Pagunya (RBA Final)**
>    - Form Modal menyediakan konfigurasi lengkap khas Administrator:
>      - Opsi Jenis Dokumen (Usulan vs RBA Final).
>      - Radio Button Opsi Latar Belakang (Dengan / Tanpa Latar Belakang).
>      - Radio Button Filter Scope (Seluruh RSUD, Per Unit Supervisor, Per Operator Spesifik, Kombinasi Unit + Operator).
>      - Checklist Unit Kerja & Operator dengan tombol *Select All* / *Reset*.
> 3. **Urutan Kolom Tabel Rincian Belanja Administrator RBA Final**:
>    - Memuat kolom **UNIT KERJA** dan **OPERATOR** untuk transparansi usulan belanja lintas unit.
>    - Kolom **PAGU FINAL (Rp)** ditempatkan di antara **TOTAL USULAN (Rp)** dan **STATUS**.
>    - Urutan Kolom (12 Kolom):
>      1. `NO`
>      2. `KODE REKENING`
>      3. `URAIAN & SPESIFIKASI BELANJA`
>      4. `UNIT KERJA`
>      5. `OPERATOR`
>      6. `AWAL (Rp)`
>      7. `VOL`
>      8. `SATUAN`
>      9. `HARGA SATUAN (Rp)`
>      10. `TOTAL USULAN (Rp)`
>      11. `PAGU FINAL (Rp)`
>      12. `STATUS`
> 4. **Kop Surat & Layout Laporan**:
>    - Kop Surat Resmi RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
>    - Metadata Grid mencakup Tahun Anggaran, Periode RBA, Administrator Pengelola, Filter Cetak yang Dipilih, Status Global, dan Tanggal Cetak.
>    - Lembar Pengesahan Tanda Tangan Direktur / Administrator RSUD Kardinah & Tim Anggaran.
>    - Pratinjau cetak browser interaktif A4 Landscape (`window.print()`).

---

## Proposed Changes

### Controller & Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak RBA Final Admin di bawah middleware `auth` dan `role:Administrator`:
  - `GET /admin/headers/{header}/print-preview-final` $\rightarrow$ `RbaHeaderController@printPreviewFinal` (Name: `admin.headers.print-preview-final`)

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Menambahkan method `printPreviewFinal(Request $request, RbaHeader $header)`:
  - Memproses parameter `include_background` (`1` atau `0`).
  - Memproses parameter filter `unit_ids[]` dan `operator_ids[]`.
  - Mengambil data Rincian Belanja (`RbaDetail`) filtered berdasarkan unit dan/atau operator terpilih.
  - Memuat data Pagu Global / Pagu Penetapan (`RbaAccountPagu`) untuk header RBA tersebut.
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Memproses teks latar belakang terfilter (`$filteredBackground`).
  - Menyusun label metadata filter (`$filterLabel`).
  - Me-render view `reports.admin_rba_final_print`.

---

### View Layer (Admin RBA Headers View)

#### [MODIFY] [show.blade.php (Admin Headers)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Memperbarui Modal Konfigurasi Cetak Admin:
  - Menambahkan radio button **Jenis Dokumen Laporan** ("Usulan Rincian Belanja" vs "Rincian Belanja & Pagunya (RBA Final)").
  - Mengatur aksi form secara dinamis mengarah ke `admin.headers.print-preview` atau `admin.headers.print-preview-final` sesuai pilihan jenis dokumen.
  - Menjaga seluruh opsi filter Admin (Latar Belakang, Seluruh RSUD, Per Unit, Per Operator, Kombinasi).

---

### Report Template Layer

#### [NEW] [admin_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)
- Membuat Blade template laporan cetak RBA Final khusus Administrator:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`.
  - Metadata Grid (Tahun Anggaran, Periode RBA, Administrator, Filter Scope, Status Global, Tanggal Cetak).
  - Section I Latar Belakang Sub-Unit (Kondisional).
  - Section II Tabel Rincian Belanja & Pagu Final dengan kolom `UNIT KERJA`, `OPERATOR`, dan posisi kolom `PAGU FINAL (Rp)` di antara `TOTAL USULAN (Rp)` dan `STATUS`.
  - Ringkasan Total Usulan & Total Pagu Final.
  - Lembar Pengesahan Tanda Tangan Direktur / Administrator & Tim Anggaran.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

### Automated Tests Layer

#### [MODIFY] [AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)
- Menambahkan unit test case:
  - `test_admin_can_preview_rba_final_print_report_with_pagu_and_unit_operator_filters()`
  - Memverifikasi Admin dapat mengakses `/admin/headers/{header}/print-preview-final`.
  - Memverifikasi output mencakup `PAGU FINAL (Rp)`, nama unit, nama operator, dan nominal pagu.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite PHPUnit Admin:
  ```powershell
  php artisan test --filter=AdminDashboardTest
  ```

### Manual Verification
1. Login sebagai Administrator.
2. Masuk ke halaman Detail RBA Header (`/admin/headers/{id}`).
3. Klik tombol **"🖨️ Cetak Rincian Usulan / RBA Final"**:
   - Pilih Jenis Dokumen: **Rincian Belanja & Pagunya (RBA Final)**.
   - Uji Scope:
     - Pilih **Seluruh RSUD**: Verifikasi seluruh usulan dari semua unit tampil.
     - Pilih **Filter Per Unit**: Centang 1 unit, verifikasi hanya usulan unit tersebut yang tampil.
     - Pilih **Filter Per Operator**: Centang 1 operator, verifikasi hanya usulan operator tersebut yang tampil.
4. Klik **🌐 Buka Pratinjau Cetak**:
   - Memastikan laporan cetak RBA Final Admin terbuka di tab baru.
   - Memastikan judul `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)` dan kolom `UNIT KERJA`, `OPERATOR`, serta `PAGU FINAL (Rp)` tampil rapi.
5. Klik **Cetak Dokumen (Ctrl+P)**:
   - Memastikan pratinjau cetak A4 Landscape rapi dan siap diprint.
