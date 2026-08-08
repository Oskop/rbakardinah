# Implementation Plan - Restorasi Logo Kop Surat & Optimalisasi Web Print Preview Rincian Belanja Usulan Operator

Fokus pengembangan disesuaikan untuk mengoptimalkan fitur **Web Print Preview (Pratinjau Cetak Browser)** dan mengembalikan struktur Kop Surat ke bentuk semula. Ekspor PDF mPDF yang tidak diperlukan dan menyebabkan gangguan layout akan disederhanakan/dihapus, sehingga pengguna dapat langsung mencetak laporan resmi RBA yang rapi dan lengkap melalui fitur cetak bawaan browser.

---

## User Review Required

> [!IMPORTANT]
> **Poin-Poin Penyesuaian**:
> 1. **Restorasi Logo Kop Surat**:
>    - Mengembalikan tag `<img>` logo pada kop laporan menggunakan helper `asset('images/LogoSipakar.png')` agar Logo SIPAKAR / RSUD Kardinah muncul secara sempurna saat Pratinjau Web (Browser Preview).
> 2. **Sederhanakan Alur Cetak (Web Print Preview Only)**:
>    - Karena fitur Pratinjau Web (`window.print()`) sudah sangat layak, rapi, dan responsif untuk cetak kertas A4 Landscape, tombol ekspor mPDF dihilangkan dari menu dropdown cetak dan dari toolbar atas laporan.
>    - Menu **"🖨️ Cetak Rincian"** pada workboard Operator akan langsung menyajikan opsi:
>      - **Cetak Dengan Latar Belakang** $\rightarrow$ Pratinjau & Cetak Web
>      - **Cetak Tanpa Latar Belakang** $\rightarrow$ Pratinjau & Cetak Web
> 3. **Perapihan Toolbar & CSS Print**:
>    - Toolbar peramban (`.no-print-bar`) hanya menyediakan tombol **"← Kembali ke Workboard"** dan **"🖨️ Cetak Dokumen (Ctrl+P)"**.
>    - Saat pencetakan browser dipicu, `.no-print-bar` otomatis tersembunyi total via `@media print`.

---

## Proposed Changes

### 1. Template Layer (Reports View)

#### [MODIFY] [operator_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_print.blade.php)
- Restorasi logo kop surat ke `src="{{ asset('images/LogoSipakar.png') }}"`.
- Menghapus tombol mPDF dari toolbar `.no-print-bar` dan menyederhanakan aksi menjadi tombol Kembali dan tombol Cetak Browser (`window.print()`).
- Mengembalikan CSS layout murni untuk tampilan browser preview dan `@media print` yang bersih dari gangguan parser mPDF.

---

### 2. View Layer (Operator Submissions Workboard)

#### [MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Menyederhanakan dropdown **"🖨️ Cetak Rincian"**:
  - Menyediakan 2 pilihan bersih tanpa opsi mPDF:
    1. **Dengan Latar Belakang** (Membuka Pratinjau Cetak Web)
    2. **Tanpa Latar Belakang** (Membuka Pratinjau Cetak Web)

---

### 3. Controller Layer

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Menyesuaikan method `exportPdf()` agar mengarah ke `printPreview()` atau me-render tampilan `operator_rba_print` standar untuk pencetakan web browser.

---

## Verification Plan

### Automated Tests
- Menjalankan tes Artisan untuk fitur detail RBA Operator:
  ```powershell
  php artisan test --filter=RbaDetailFeaturesTest
  ```

### Manual Verification
1. Login sebagai Operator dan masuk ke halaman Workboard Usulan RBA.
2. Klik tombol **"🖨️ Cetak Rincian"** $\rightarrow$ Pilih **Dengan Latar Belakang**:
   - Memastikan Logo RSUD Kardinah / SIPAKAR di Kop Surat tampil jelas di peramban.
   - Memastikan tabel Rincian Belanja, Latar Belakang, dan Lembar Pengesahan Tanda Tangan tampil rapi.
3. Klik tombol **🖨️ Cetak Dokumen** (atau tombol `Ctrl+P`):
   - Memastikan dialog cetak browser terbuka dan toolbar atas otomatis tersembunyi dari hasil cetak kertas / PDF browser.
