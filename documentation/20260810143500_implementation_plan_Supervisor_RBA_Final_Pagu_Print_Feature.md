# Implementation Plan - Fitur Cetak Rincian Belanja & Pagu (RBA Final) Tampilan Supervisor dengan Filter Operator

Menambahkan fitur **Cetak Rincian Belanja dan Pagunya (RBA Final)** pada tampilan Supervisor (`/supervisor/submissions/{submission}`) dengan opsi pencetakan yang persis sama dengan fitur Cetak Usulan Rincian Belanja Supervisor, yaitu filter Latar Belakang dan filter Operator penyusun. Fitur ini menyajikan laporan cetak resmi RBA Final yang memuat nominal **PAGU FINAL (Rp)** serta kolom **OPERATOR / PEMBUAT** untuk membedakan usulan dari tiap Operator.

---

## User Review Required

> [!IMPORTANT]
> **Detail Fitur Cetak RBA Final Supervisor**:
> 1. **Judul Dokumen Resmi**:
>    - `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`
> 2. **Pembaruan Modal Konfigurasi Cetak (Supervisor Workboard)**:
>    - Pada modal cetak Supervisor (`/supervisor/submissions/{id}`), ditambahkan pilihan **Jenis Dokumen Laporan**:
>      - **Usulan Rincian Belanja (Draft/Usulan)**
>      - **Rincian Belanja & Pagunya (RBA Final)**
>    - Form Modal menyediakan opsi lengkap:
>      - Opsi Jenis Dokumen (Radio Button / Select).
>      - Radio Button Opsi Latar Belakang (Dengan / Tanpa Latar Belakang).
>      - Radio Button Opsi Filter Operator ("Semua Operator" vs "Pilih Operator Tertentu").
>      - Checkbox list daftar nama Operator aktif di unit kerja tersebut (dengan tombol *Select All* / *Reset*).
> 3. **Urutan Kolom Tabel Rincian Belanja Supervisor RBA Final**:
>    - Kolom **OPERATOR** disertakan untuk transparansi penyusun belanja.
>    - Kolom **PAGU FINAL (Rp)** ditempatkan di antara **TOTAL USULAN (Rp)** dan **STATUS**.
>    - Urutan Kolom (11 Kolom):
>      1. `NO`
>      2. `KODE REKENING`
>      3. `URAIAN & SPESIFIKASI BELANJA`
>      4. `OPERATOR`
>      5. `AWAL (Rp)`
>      6. `VOL`
>      7. `SATUAN`
>      8. `HARGA SATUAN (Rp)`
>      9. `TOTAL USULAN (Rp)`
>      10. `PAGU FINAL (Rp)`
>      11. `STATUS`
> 4. **Kop Surat & Layout Laporan**:
>    - Menggunakan Kop Surat Resmi RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
>    - Metadata Grid mencakup Unit Kerja, Periode RBA, Supervisor, Filter Operator yang dicetak, Status Submisi, dan Tanggal Cetak.
>    - Lembar Pengesahan Tanda Tangan Supervisor & Operator.
>    - Pratinjau cetak browser interaktif A4 Landscape (`window.print()`).

---

## Proposed Changes

### Controller & Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak RBA Final Supervisor di bawah middleware `auth` dan `role:Supervisor`:
  - `GET /supervisor/submissions/{submission}/print-preview-final` $\rightarrow$ `Supervisor\ReviewController@printPreviewFinal` (Name: `supervisor.submissions.print-preview-final`)

#### [MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Menambahkan method `printPreviewFinal(Request $request, RbaSubmission $submission)`:
  - Memverifikasi otorisasi `unit_id` Supervisor.
  - Memproses parameter `include_background` (`1` atau `0`).
  - Memproses parameter `operator_ids` (array / comma-separated list ID operator yang dipilih).
  - Memuat data rincian belanja filtered berdasarkan `created_by` operator terpilih (jika spesifik).
  - Memuat data Pagu Global / Pagu Penetapan (`RbaAccountPagu`) untuk header RBA tersebut.
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Menyusun label metadata filter operator (`$operatorFilterLabel`).
  - Me-render view `reports.supervisor_rba_final_print`.

---

### View Layer (Supervisor Submissions Workboard)

#### [MODIFY] [show.blade.php (Supervisor Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Memperbarui Modal Konfigurasi Cetak Supervisor:
  - Menambahkan radio button / selector **Jenis Dokumen Laporan** ("Usulan Rincian Belanja" vs "Rincian Belanja & Pagunya (RBA Final)").
  - Mengatur aksi form secara dinamis mengarah ke `supervisor.submissions.print-preview` atau `supervisor.submissions.print-preview-final` sesuai pilihan jenis dokumen.
  - Menjaga filter **Latar Belakang Sub-Unit** dan filter **Operator Penyusun** agar berlaku sama untuk kedua jenis cetakan.

---

### Report Template Layer

#### [NEW] [supervisor_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php)
- Membuat Blade template laporan cetak RBA Final khusus Supervisor:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`.
  - Metadata Grid (Unit Kerja, Periode RBA, Nama Supervisor, Filter Operator yang Dicetak, Status Submisi, Tanggal Cetak).
  - Section I Latar Belakang Sub-Unit (Kondisional).
  - Section II Tabel Rincian Belanja & Pagu Final dengan kolom `OPERATOR` dan posisi kolom `PAGU FINAL (Rp)` berada di antara `TOTAL USULAN (Rp)` dan `STATUS`.
  - Ringkasan Total Usulan & Total Pagu Final per Rekening.
  - Lembar Pengesahan Tanda Tangan Supervisor & Operator.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

### Automated Tests Layer

#### [MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Menambahkan unit test case:
  - `test_supervisor_can_preview_rba_final_print_report_with_pagu_and_operator_filters()`
  - Memverifikasi Supervisor dapat mengakses rute `/supervisor/submissions/{submission}/print-preview-final`.
  - Memverifikasi output mencakup kolom `PAGU FINAL (Rp)`, nama operator terpilih, dan nominal pagu.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite PHPUnit untuk Supervisor:
  ```powershell
  php artisan test --filter=ReviewTest
  ```

### Manual Verification
1. Login sebagai Supervisor.
2. Masuk ke halaman Workboard Review Submisi (`/supervisor/submissions/{id}`).
3. Klik tombol **"🖨️ Cetak Rincian"**:
   - Pilih Jenis Dokumen: **Rincian Belanja & Pagunya (RBA Final)**.
   - Uji Opsi Filter Operator:
     - Pilih **Semua Operator**: Verifikasi seluruh usulan dari semua operator di unit tampil beserta kolom Operator dan Pagu Final.
     - Pilih **Operator Spesifik**: Centang 1 operator, verifikasi hanya usulan operator tersebut yang muncul.
   - Uji Opsi Latar Belakang (Dengan / Tanpa Latar Belakang).
4. Klik **🌐 Buka Pratinjau Cetak**:
   - Memastikan tab baru terbuka menampilkan laporan cetak RBA Final Supervisor.
   - Memastikan judul `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)` tampil.
   - Memastikan kolom **OPERATOR** dan **PAGU FINAL (Rp)** tampil rapi pada tabel.
5. Klik **Cetak Dokumen (Ctrl+P)**:
   - Memastikan pratinjau cetak A4 Landscape rapi dan siap dicetak / disimpan ke PDF.
