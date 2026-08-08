# Implementation Plan - Fitur Cetak Rincian Belanja Operator & Sistem Template Laporan RBA

Rencana ini bertujuan untuk menambahkan fitur **Cetak Rincian Belanja Usulan Operator** dengan dua opsi pencetakan (Dengan Latar Belakang vs Tanpa Latar Belakang), serta menghadirkan **Sistem Template Laporan Moduler (Blade Report Template Engine)** berbasis **mPDF** dan **Live Web Preview**.

---

## User Review Required

> [!IMPORTANT]
> - **Opsi Pencetakan**:
>   - **Cetak Dengan Latar Belakang**: Menyertakan teks Latar Belakang RBA sub-unit di bagian awal laporan.
>   - **Cetak Tanpa Latar Belakang**: Hanya mencetak identitas RBA dan tabel Rincian Belanja Usulan Operator.
> - **Rekomendasi & Analisis Engine PDF (`mPDF`)**:
>   - **mPDF (`mpdf/mpdf`)** dipilih sebagai *engine* cetak laporan keuangan/RBA instansi & rumah sakit terbaik karena:
>     1. **Manajemen Page Break & Header Tabel Otomatis**: Secara otomatis mengulang header tabel (`<thead>`) pada setiap halaman baru dan mencegah pemotongan baris data di tengah halaman (`page-break-inside: avoid`).
>     2. **Header & Footer Halaman Dinamis**: Mendukung `<htmlpageheader>` dan `<htmlpagefooter>` dengan nomor halaman otomatis (`Halaman {PAGENO} dari {nbpg}`).
>     3. **100% Pure PHP**: Tidak membutuhkan *external binary* (seperti Puppeteer/Chrome/wkhtmltopdf), sehingga sangat aman, cepat, dan mudah di-deploy di server Windows/Linux manapun.
> - **Developer-Friendly Live Template System**:
>   - Disediakan **Live Web Preview (`/operator/submissions/{submission}/print-preview`)** yang memungkinkan developer mengedit tampilan Blade template (`resources/views/reports/operator_rba_print.blade.php`) dan melihat hasilnya secara instan di peramban tanpa harus terus-menerus mengunduh file PDF.
>   - Template dibuat dengan struktur HTML/CSS laporan resmi BLUD RSUD Kardinah yang otomatis rapi menyesuaikan panjang deskripsi & jumlah item.

---

## Proposed Changes

### 1. Dependency & Packages

#### [MODIFY] [composer.json](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/composer.json)
- Menambahkan package `mpdf/mpdf` untuk *engine* pembuatan PDF server-side.

---

### 2. Controller & Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak di bawah middleware `auth` dan `role:Operator`:
  - `GET operator/submissions/{submission}/print-preview` $\rightarrow$ `SubmissionController@printPreview`
  - `GET operator/submissions/{submission}/export-pdf` $\rightarrow$ `SubmissionController@exportPdf`

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Menambahkan method `printPreview(Request $request, RbaSubmission $submission)` untuk menampilkan pratinjau web interaktif (Live Preview & Browser Print `window.print()`).
- Menambahkan method `exportPdf(Request $request, RbaSubmission $submission)` untuk mengenerate stream PDF menggunakan `mPDF`.
- Menerima parameter `include_background` (default `1` atau `0`).

---

### 3. Report Template Layer (Developer System)

#### [NEW] [operator_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_print.blade.php)
- Membuat Blade template terstruktur untuk laporan RBA Operator:
  - **Kop Surat / Header Resmi**: Logo RSUD Kardinah, Pemerintah Kota Tegal, dan Judul Dokumen.
  - **Tabel Identitas RBA**: Tahun Anggaran, Tipe Periode, Unit Kerja, Nama Operator.
  - **Klausul Latar Belakang (Kondisional)**: Ditampilkan hanya jika `include_background == 1` dan latar belakang tidak kosong.
  - **Tabel Rincian Belanja**: Menampilkan No, Rekening, Uraian/Spesifikasi, AWAL, Volume, Satuan, Harga Satuan, Total Usulan, dan Status.
  - **Footer & Halaman Sign-off**: Kolom tanda tangan resmi Operator & Supervisor, serta penomoran halaman otomatis.
  - **CSS Styling khusus Cetak & mPDF**: `page-break-inside: avoid`, styling tabel garis tipis khas laporan keuangan, dan `@media print` stylesheet.

---

### 4. Operator View Layer

#### [MODIFY] [show.blade.php (Operator Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Menambahkan tombol dropdown / modal **"🖨️ Cetak Rincian Belanja"** pada header halaman Usulan Belanja Operator.
- Menyediakan opsi:
  - **Cetak Dengan Latar Belakang** (Pilihan: Pratinjau Web / Unduh PDF).
  - **Cetak Tanpa Latar Belakang** (Pilihan: Pratinjau Web / Unduh PDF).

---

## Verification Plan

### Automated Tests
- Menambahkan test case baru pada `tests/Feature/Operator/RbaDetailFeaturesTest.php` atau `RbaDetailTest.php` untuk memvalidasi:
  - Akses route `print-preview` dan `export-pdf` oleh Operator.
  - Opsi `include_background=1` dan `include_background=0`.
  - Restriksi akses (Operator unit lain tidak dapat mencetak RBA milik unit berbeda).
- Menjalankan `php artisan test`.

### Manual Verification
1. Login sebagai Operator.
2. Masuk ke halaman Workboard Usulan Belanja (`/operator/submissions/{submission}`).
3. Klik tombol **"🖨️ Cetak Rincian Belanja"**.
4. Uji opsi **Cetak Dengan Latar Belakang**:
   - Pilih *Pratinjau Web*: Periksa visualisasi halaman laporan di peramban.
   - Pilih *Unduh PDF*: Periksa file PDF mPDF yang terunduh, pastikan header, tabel, latar belakang, dan penomoran halaman rapi.
5. Uji opsi **Cetak Tanpa Latar Belakang**:
   - Pastikan teks Latar Belakang disembunyikan secara bersih.
