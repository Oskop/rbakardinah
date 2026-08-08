# Implementation Plan - Perbaikan Tampilan Cetak PDF Engine mPDF Rincian Belanja Usulan Operator

Perbaikan masalah tampilan cetak PDF pada modul **Rincian Belanja Usulan Operator**. Saat ini, fitur pratinjau web (*Web Preview*) di browser sudah tampil rapi dan layak cetak, tetapi saat diekspor menggunakan *engine* **mPDF**, hasilnya berantakan (tampilan hanya 1 baris per lembar, tabel tidak muncul/terpotong, dan elemen *toolbar* ikut terbawa ke PDF).

---

## Analysis & Root Cause

Berdasarkan analisis pada [operator_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_print.blade.php) dan [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php):

1. **Toolbar `.no-print-bar` Ikut Ter-render di mPDF**:
   - `SubmissionController@exportPdf` me-render Blade template yang sama persis dengan Web Preview tanpa memisahkan/menyembunyikan toolbar web peramban.
   - `.no-print-bar` menggunakan `position: fixed` dan `display: flex`.
2. **Ketidakcocokan CSS mPDF dengan Modern CSS (`display: flex`, `@media screen`)**:
   - mPDF **tidak mendukung CSS Flexbox (`display: flex`)** dan tidak memisahkan blok CSS `@media screen`.
   - Ketika mPDF membaca `display: flex` dan `position: fixed` pada toolbar top bar, kalkulasi tinggi (*height calculation*) halaman mPDF rusak fatal sehingga memaksa *page break* setelah 1-2 baris dan membuat tabel menghilang/rusak.
3. **Elemen HTML & Attribute yang Mengganggu Parser mPDF**:
   - Atribut JavaScript seperti `onerror="this.src=..."` pada tag `<img>` dapat mengacaukan parser HTML internal mPDF.
   - Penggunaan `display: inline-block` pada badge status di dalam cell tabel (`<td>`) menyebabkan kalkulasi tinggi baris tabel eksplosif di mPDF.

---

## User Review Required

> [!IMPORTANT]
> **Pendekatan Perbaikan**:
> 1. **Pemisahan Mode Render (`$isPdf`)**:
>    - `SubmissionController@exportPdf` akan mengirimkan variabel `$isPdf = true` ke Blade template.
>    - Toolbar `.no-print-bar` hanya akan ditampilkan jika `$isPdf == false` (Pratinjau Web). Dokumen PDF hasil ekspor mPDF akan murni berisi laporan resmi tanpa toolbar.
> 2. **Optimalisasi CSS Khusus mPDF**:
>    - Menghapus aturan `display: flex` dan menggantinya dengan layout `table` standar yang 100% kompatibel dengan mPDF engine.
>    - Menghapus atribut JS `onerror` dari tag `<img>` dan menggunakan jalur file lokal mutlak (`public_path()`) yang valid untuk mPDF.
>    - Memperbaiki aturan pagination tabel (`page-break-inside: avoid` pada `tr` dan pengulangan `<thead>` di tiap halaman).

---

## Proposed Changes

### Controller & Logic Layer

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Pada method `exportPdf()`, tambahkan parameter `'isPdf' => true` saat render view `reports.operator_rba_print`.
- Pada method `printPreview()`, tambahkan parameter `'isPdf' => false`.

---

### Report Template Layer

#### [MODIFY] [operator_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_print.blade.php)
- **Kondisi Toolbar Web**: Wrap komponen `.no-print-bar` dalam `@if(!$isPdf) ... @endif`.
- **Dukungan CSS mPDF**:
  - Ganti styling toolbar agar menggunakan pembungkus yang aman dan disembunyikan total saat mode PDF.
  - Sederhanakan kelas badge status di tabel agar tidak menggunakan `display: inline-block` yang memicu bug perataan tinggi cell mPDF.
  - Pastikan tag `<img>` logo menggunakan `public_path('images/LogoSipakar.png')` yang bersih dari atribut JavaScript `onerror`.
  - Pastikan struktur `<table>`, `<thead>`, `<tbody>`, dan `<tfoot>` terkonfigurasi dengan CSS mPDF pendukung pagination halaman yang mulus.

---

## Verification Plan

### Automated Tests
- Menjalankan pengujian sintaks & fungsionalitas rute export PDF:
  ```powershell
  php artisan test --filter=RbaDetailFeaturesTest
  ```

### Manual Verification
1. Login sebagai Operator.
2. Buka halaman Usulan Belanja Operator (`/operator/submissions/{id}`).
3. Klik **Cetak Rincian Belanja** $\rightarrow$ **Pratinjau Web**:
   - Pastikan toolbar atas ("Kembali ke Workboard", "Unduh PDF", "Cetak via Browser") tetap tampil dengan baik di peramban.
4. Klik **Unduh PDF (mPDF Engine)**:
   - Buka file PDF yang terunduh.
   - Memastikan PDF tampil rapi:
     - Kop surat & Logo SIPAKAR muncul dengan benar.
     - Tabel Rincian Belanja Usulan muncul utuh lengkap dengan seluruh kolom dan baris.
     - Laporan tidak lagi terpecah menjadi 1 baris per lembar.
     - Toolbar peramban tidak ikut tercetak di dalam file PDF.
