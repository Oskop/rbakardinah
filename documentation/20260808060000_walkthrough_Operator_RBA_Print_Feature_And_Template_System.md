# Walkthrough - Fitur Cetak Rincian Belanja Operator & Sistem Template Laporan RBA

Fitur **Cetak Rincian Belanja Usulan Operator** dengan dua opsi pencetakan (Dengan Latar Belakang vs Tanpa Latar Belakang) serta **Sistem Template Laporan Moduler (Blade Report Template Engine)** berbasis **mPDF** dan **Live Web Preview** telah sukses diimplementasikan dan diverifikasi.

---

## Perubahan yang Dilakukan

### 1. Evaluasi & Integrasi Engine PDF (`mPDF`)
* **Package `mpdf/mpdf`**:
  * Telah di-install ke dalam project.
  * **Alasan Pemilihan mPDF sebagai Engine Terbaik**:
    1. **Manajemen Page Break & Auto Table Fitting**: Secara otomatis menangani pengulangan header tabel (`<thead>`) pada setiap halaman baru tanpa memotong baris data di tengah-tengah halaman (`page-break-inside: avoid`).
    2. **HTML Page Headers & Footers**: Mendukung penomoran halaman otomatis (`Halaman {PAGENO} dari {nbpg}`) dan footer resmi RSUD Kardinah Kota Tegal.
    3. **100% Pure PHP**: Tidak membutuhkan instalasi *external binary* (seperti Puppeteer/Chrome/wkhtmltopdf), sehingga zero-friction dan kompatibel di lingkungan Windows/Linux server.

### 2. Sistem Template Laporan Developer (`operator_rba_print.blade.php`)
* **[resources/views/reports/operator_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_print.blade.php) [NEW]**:
  * Dibuat secara moduler menggunakan Blade HTML/CSS standar, sehingga developer dapat dengan sangat mudah mengubah layout, warna, kolom, maupun kop surat tanpa harus mencetak berulang kali.
  * Menyediakan **Dual-Mode System**:
    1. **Live Web Preview**: Developer & Operator dapat melihat pratinjau tampilan laporan langsung di peramban (`/operator/submissions/{submission}/print-preview`) dengan dukungan tombol cetak cepat browser (`window.print()`).
    2. **PDF Document Generation**: Digenerate menjadi PDF resmi server-side melalui `mPDF` (`/operator/submissions/{submission}/export-pdf`).
  * **Fitur Template**:
    * Kop Surat Resmi RSUD Kardinah Kota Tegal & Pemerintah Kota Tegal.
    * Grid Informasi Identitas RBA, Unit Kerja, Operator, dan Tanggal Cetak.
    * **Klausul Latar Belakang Kondisional**: Tampil jika `include_background == 1` dan tersembunyi secara bersih jika `include_background == 0`.
    * Tabel Rincian Belanja Usulan (No, Rekening, Uraian/Spesifikasi, AWAL, Volume, Satuan, Harga Satuan, Total Usulan, dan Status).
    * Footnote Total Akumulasi Usulan.
    * Blok Tanda Tangan Resmi Pengesahan Operator dan Supervisor.

### 3. Controller & Routing Layer
* **[web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)**:
  * Menambahkan rute `operator.submissions.print-preview` dan `operator.submissions.export-pdf`.
* **[SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)**:
  * Menambahkan method `printPreview` untuk merender tampilan Live Web Preview.
  * Menambahkan method `exportPdf` untuk mengenerate stream file PDF menggunakan `mPDF` dengan footer penomoran halaman otomatis (`Halaman {PAGENO} dari {nbpg}`).

### 4. Antarmuka Operator (UI Workboard)
* **[show.blade.php (Operator Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)**:
  * Menyisipkan tombol dropdown **"🖨️ Cetak Rincian"** pada header Workboard RBA Operator dengan opsi interaktif:
    1. **1. Dengan Latar Belakang**:
       - 🌐 Pratinjau Web / Browser (HTML Live)
       - 📄 Unduh Dokumen PDF (mPDF Engine)
    2. **2. Tanpa Latar Belakang**:
       - 🌐 Pratinjau Web / Browser (HTML Live)
       - 📄 Unduh Dokumen PDF (mPDF Engine)

---

## Hasil Verifikasi & Automated Tests

### Automated Feature Tests
Menambahkan test case baru di [RbaDetailFeaturesTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailFeaturesTest.php):
- `test_operator_can_preview_print_report_with_and_without_background`: Memvalidasi penyertaan dan pemotongan teks latar belakang pada rute pratinjau.
- `test_operator_can_export_pdf_report`: Memvalidasi pembentukan respons laporan PDF.

Pengujian dijalankan dengan PHPUnit dan **lulus 100% (54 passed, 0 failed)**:
```powershell
php artisan test
```

### Manual Verification
1. Login sebagai Operator.
2. Masuk ke Workboard Usulan Belanja (`/operator/submissions/{submission}`).
3. Klik tombol **"🖨️ Cetak Rincian"**.
4. Memilih **Dengan Latar Belakang** -> Laporan menampilkan bagian Latar Belakang Sub-unit di atas tabel rincian belanja.
5. Memilih **Tanpa Latar Belakang** -> Laporan langsung menyajikan tabel rincian belanja tanpa seksi latar belakang.
6. Membuka **Pratinjau Web** -> Developer/User dapat melihat tampilan langsung dan melakukan cetak cepat via `window.print()`.
7. Membuka **Unduh PDF** -> File PDF terunduh rapi dengan kop surat, tabel terstruktur, dan nomor halaman `mPDF`.
