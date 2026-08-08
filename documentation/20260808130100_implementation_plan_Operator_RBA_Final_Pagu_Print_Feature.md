# Implementation Plan - Fitur Cetak Rincian Belanja & Pagu (RBA Final) Tampilan Operator (Revisi)

Menambahkan fitur **Cetak Rincian Belanja dengan Pagunya (RBA Final)** pada halaman Workboard Usulan RBA Operator (`/operator/submissions/{submission}`). Fitur ini menyajikan laporan cetak resmi RBA Final yang memuat nominal **PAGU FINAL (Rp)** dari masing-masing Kode Rekening Belanja bersandingan dengan rincian usulan belanja Operator.

---

## User Review Required

> [!IMPORTANT]
> **Detail Revisi Sesuai Arahan User**:
> 1. **Judul Dokumen Resmi**:
>    - `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`
> 2. **Urutan Kolom Tabel Rincian Belanja**:
>    - Kolom **PAGU FINAL (Rp)** ditempatkan di antara **TOTAL USULAN (Rp)** dan **STATUS**.
>    - Urutan Kolom:
>      1. `NO`
>      2. `KODE REKENING`
>      3. `URAIAN & SPESIFIKASI BELANJA`
>      4. `AWAL (Rp)`
>      5. `VOL`
>      6. `SATUAN`
>      7. `HARGA SATUAN (Rp)`
>      8. `TOTAL USULAN (Rp)`
>      9. `PAGU FINAL (Rp)`
>      10. `STATUS`
> 3. **Pembaruan Dropdown Menu Cetak (Operator Workboard)**:
>    - Tombol **"🖨️ Cetak Rincian"** pada header Workboard Operator akan disesuaikan menjadi 2 kategori utama:
>      - **1. Usulan Rincian Belanja (Draft/Usulan)**:
>        - *📄 Cetak Dengan Latar Belakang*
>        - *📄 Cetak Tanpa Latar Belakang*
>      - **2. Rincian Belanja & Pagu (RBA Final)**:
>        - *📄 Cetak RBA Final (Dengan Latar Belakang)*
>        - *📄 Cetak RBA Final (Tanpa Latar Belakang)*

---

## Proposed Changes

### Controller & Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak RBA Final di bawah middleware `auth` dan `role:Operator`:
  - `GET /operator/submissions/{submission}/print-preview-final` $\rightarrow$ `Operator\SubmissionController@printPreviewFinal`

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Menambahkan method `printPreviewFinal(Request $request, RbaSubmission $submission)`:
  - Memuat data rincian usulan belanja Operator.
  - Memuat data Pagu Global / Pagu Penetapan (`RbaAccountPagu`) untuk RBA Header tersebut.
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Me-render view `reports.operator_rba_final_print`.

---

### View Layer (Operator Submissions Workboard)

#### [MODIFY] [show.blade.php (Operator Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Memperbarui dropdown **"🖨️ Cetak Rincian"** agar mencakup kategori cetak RBA Final (Dengan Pagu) selain cetak Usulan Rincian Belanja standar.

---

### Report Template Layer

#### [NEW] [operator_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_final_print.blade.php)
- Membuat Blade template terstruktur khusus RBA Final dengan Pagu:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`.
  - Informasi Metadata Grid (Unit Kerja, Periode RBA, Nama Operator, Status Submisi, Opsi Cetak, Tanggal Cetak).
  - Section I Latar Belakang Sub-Unit (Kondisional).
  - Section II Tabel Rincian Belanja & Pagu Final dengan posisi kolom `PAGU FINAL (Rp)` berada di antara `TOTAL USULAN (Rp)` dan `STATUS`.
  - Lembar Pengesahan Tanda Tangan Supervisor & Operator.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

## Verification Plan

### Automated Tests
- Menambahkan test case baru pada `tests/Feature/Operator/RbaDetailFeaturesTest.php`:
  - Memvalidasi Operator dapat meng-akses rute `operator/submissions/{submission}/print-preview-final`.
  - Memvalidasi filter `include_background=1` dan `include_background=0`.
  - Memvalidasi judul laporan dan kolom `PAGU FINAL (Rp)` tampil sesuai spesifikasi.
- Menjalankan tes Artisan:
  ```powershell
  php artisan test --filter=RbaDetailFeaturesTest
  ```

### Manual Verification
1. Login sebagai Operator.
2. Buka halaman Workboard Usulan Belanja (`/operator/submissions/{id}`).
3. Klik dropdown **"🖨️ Cetak Rincian"**:
   - Pilih **2. Rincian Belanja & Pagu (RBA Final)** $\rightarrow$ **Cetak RBA Final (Dengan Latar Belakang)**.
   - Memastikan laporan cetak RBA Final terbuka di tab baru.
   - Memastikan judul laporan `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)` tampil.
   - Memastikan kolom **PAGU FINAL (Rp)** berada tepat di antara kolom **TOTAL USULAN (Rp)** dan **STATUS**.
4. Klik **Cetak Dokumen (Ctrl+P)** pada pratinjau cetak:
   - Memastikan cetakan kertas A4 Landscape rapi, logo kop surat muncul, dan toolbar tersembunyi.
