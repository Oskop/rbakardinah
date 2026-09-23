# Implementation Plan - Perbaikan Tata Letak Signatures Table Berita Acara Asistensi / Desk RBA

## Deskripsi Masalah & Tujuan
Pada lembar cetak Berita Acara Asistensi / Desk RBA (`resources/views/reports/berita_acara_desk_print.blade.php`), pada bagian **Signatures Table**:
1. Terjadi tumpukan (*overlap / collision*) antara kolom tanda tangan (`sign-dots` / `(...................)`) dan nama operator dari sub unit (`sign-name`).
2. Terdapat potensi tabrakan / pergeseran antara kolom **Tim Asistensi (Kiri)** dan **Sub Unit Asistensi (Kanan)** ketika nama salah satu pihak sangat panjang atau memiliki gelar yang banyak.
3. Diperlukan fleksibilitas ukuran (*wrap teks untuk nama*) sehingga jika terdapat nama pejabat/operator yang sangat panjang beserta gelar akademis/spesialis, teks dapat turun baris (*multiline*) secara proporsional dan rapi tanpa menabrak kolom tanda tangan maupun kolom sebelahnya.

---

## User Review Required

> [!NOTE]
> **Perubahan Tipografi Placeholder Tanda Tangan**:
> Font untuk tanda titik tanda tangan `(...................)` diubah dari `font-family: monospace` menjadi font standar dokumen `font-family: 'Times New Roman', Times, serif`.
> - **Alasan**: Karakter titik `.` pada font monospace memiliki lebar tetap yang sangat lebar (hampir sama lebarnya dengan huruf `W`), sehingga memakan hingga ~45% lebar kolom dan mendesak ruang nama operator. Dengan font serif standar dokumen, tanda titik tanda tangan menjadi sangat proporsional (~20-25mm), lebih elegan sesuai naskah dinas resmi, dan membebaskan lebih dari 60% lebar kolom untuk nama operator.

---

## Analisis Penyebab Masalah (Root Causes)

1. **Auto Table Layout pada Tabel Utama (`.signatures-table`)**:
   Tabel tanda tangan tidak memiliki `table-layout: fixed`. Ketika tabel HTML menggunakan layout otomatis (`auto`), browser menghitung lebar kolom secara dinamis berdasarkan konten terpanjang. Jika nama di kolom kanan sangat panjang, kolom kanan membesar dan menekan kolom kiri, atau keluar dari batas margin cetak A4.
2. **Keterbatasan Flexbox pada Print CSS (`.sign-row`)**:
   Penggunaan `display: flex; justify-content: space-between; align-items: baseline;` dengan pembagian lebar persentase (`55%` dan `45%`) tanpa `min-width: 0` menyebabkan flex item nama mempertahankan lebar intrinsiknya saat dicetak. Akibatnya, teks nama panjang tidak turun baris melainkan meluber (*overflow*) dan menabrak tanda tangan `(...................)`.
3. **Penyelarasan Multi-Line (*Vertical Alignment*)**:
   Ketika nama operator memiliki panjang 2 baris (misal: `"dr. H. Muhammad Reza Pahlevi, Sp.A, M.Kes, FINASIM"`), tidak ada aturan perataan yang menjamin posisi titik tanda tangan tetap berada di baris dasar (*baseline*) terbawah nama dengan ruang tanda tangan basah yang lapang di atasnya.
4. **Ketiadaan Gutter Antar Kolom Kiri & Kanan**:
   Kedua kolom tanda tangan hanya dibatasi padding kecil tanpa pemisahan gutter yang eksplisit, sehingga rawan bertumpukan saat dicetak di berbagai skala printer / browser.

---

## Proposed Changes

### Komponen Tampilan Cetak (Print View)

#### [MODIFY] [`resources/views/reports/berita_acara_desk_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/berita_acara_desk_print.blade.php)

1. **Penerapan Fixed 2-Column Table Layout**:
   - Memperbarui class `.signatures-table`:
     ```css
     .signatures-table {
         width: 100%;
         table-layout: fixed;
         border-collapse: separate;
         border-spacing: 0;
         margin-top: 24px;
         font-size: 10.5pt;
         page-break-inside: avoid;
         break-inside: avoid;
         box-sizing: border-box;
     }
     ```
   - Memastikan kolom Kiri (Tim Asistensi) dan Kanan (Sub Unit) terkunci tepat 50% masing-masing dengan pembatas gutter:
     - `.sign-column-left`: `width: 50%; vertical-align: top; padding-right: 15px; padding-left: 0; box-sizing: border-box;`
     - `.sign-column-right`: `width: 50%; vertical-align: top; padding-left: 15px; padding-right: 0; box-sizing: border-box;`

2. **Struktur Sub-Table Khusus Baris Tanda Tangan (`.sign-subtable`)**:
   Mengganti `display: flex` pada baris penandatangan dengan sub-table berbasis `table-layout: fixed` yang 100% konsisten pada semua browser dan PDF print engine:
   - **Kolom Nama (`.sign-name-col`)**:
     - Alokasi lebar: `62%` (ruang sangat leluasa).
     - Fleksibilitas teks: `word-wrap: break-word; overflow-wrap: break-word; word-break: normal; white-space: normal; line-height: 1.35;`.
     - *Hanging Indent*: Menggunakan `padding-left: 1.25em; text-indent: -1.25em;` sehingga saat nama panjang turun ke baris ke-2 atau ke-3, teks baris berikutnya sejajar rapi di bawah huruf pertama nama, bukan di bawah nomor urut.
     - Ruang tanda tangan: `padding-bottom: 24px; padding-right: 6px;`.
     - Perataan: `vertical-align: bottom;`.
   - **Kolom Titik Tanda Tangan (`.sign-dots-col`)**:
     - Alokasi lebar: `38%`.
     - Tipografi: `font-family: 'Times New Roman', Times, serif; font-size: 10.5pt; letter-spacing: 0.5px;`.
     - Perataan: `text-align: right; white-space: nowrap; vertical-align: bottom; padding-bottom: 24px;`.
     - Titik tanda tangan: `(...................)` tetap berada di sisi kanan tanpa pernah tertabrak oleh nama.

3. **Perlindungan Judul Sub Unit (`.sign-header`)**:
   - Memastikan judul sub-unit pada sisi kanan memiliki aturan wrapping: `word-wrap: break-word; overflow-wrap: break-word; white-space: normal; line-height: 1.35; margin-bottom: 12px;` agar sub unit dengan nama panjang tidak merusak simetri tabel.

---

### Pengujian Otomatis (Automated Tests)

#### [MODIFY] [`tests/Feature/Operator/BeritaAcaraTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/BeritaAcaraTest.php)

- Menambahkan skenario pengujian khusus untuk kasus nama operator dan nama sub unit yang sangat panjang:
  - `test_operator_print_preview_handles_long_names_and_titles_without_distortion()`:
    - Memasukkan nama dengan gelar panjang (misal: `"dr. H. Muhammad Reza Pahlevi, Sp.A, M.Kes, FINASIM"` dan `"Ns. Siti Fatimah Nurjanah, S.Kep., M.Kep., Sp.Kep.MB"`).
    - Memasukkan nama sub unit panjang (misal: `"Sub Bagian Tata Usaha dan Kepegawaian serta Hukum dan Hubungan Masyarakat"`).
    - Memastikan respons status 200, nama-nama ter-render dengan benar di HTML, dan markup tabel tanda tangan mengandung struktur kelas `sign-subtable`, `sign-name-col`, dan `sign-dots-col`.

---

## Verification Plan

### Automated Tests
1. Eksekusi pengujian khusus Berita Acara:
   ```bash
   php artisan test tests/Feature/Operator/BeritaAcaraTest.php
   ```
2. Eksekusi seluruh rangkaian pengujian sistem:
   ```bash
   php artisan test
   ```
   *Target: 254 tests passed (100% lulus tanpa kegagalan).*

### Manual Verification
1. Mengakses halaman cetak Berita Acara pada browser:
   `http://localhost:8000/operator/submissions/{id}/desk-verification/print`
2. Memverifikasi secara visual pada mode Print Preview (`Ctrl + P` / Cetak):
   - Kolom kiri dan kanan terbagi 50:50 dengan rapi.
   - Nama panjang operator turun baris dengan *hanging indent* tanpa menabrak tanda titik `(...................)`.
   - Tidak ada teks yang terpotong atau saling menumpuk.
