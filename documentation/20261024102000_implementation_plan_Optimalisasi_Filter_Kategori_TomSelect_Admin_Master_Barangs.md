# Implementation Plan: Optimalisasi UI Filtering Kategori Master Barang Admin (`/admin/master-barangs`) Menggunakan Searchable Combobox (TomSelect)

Dokumen ini disusun untuk mengoptimalkan pengalaman pengguna (UX) administrator pada halaman **Master Barang BMD Permendagri No. 108 Tahun 2016** (`/admin/master-barangs`), khususnya pada toolbar **Filter Hierarki Kategori Barang (Level 5 & Level 6)** yang saat ini masih menggunakan native HTML `<select>` dan `<option>`.

---

## 1. Analisis Masalah & Kebutuhan Pengguna

### 1.1 Kendala UI/UX Saat Ini
1. **Volume Data Kategori Terlalu Besar untuk Native HTML `<select>`**:
   - **Level 5 (Rincian Objek)**: Memuat **158 item** pilihan.
   - **Level 6 (Sub Rincian Objek)**: Memuat hingga puluhan/ratusan opsi sub-kategori tergantung Level 5 yang dipilih (dari total 980 item di database).
2. **Ketiadaan Fitur Pencarian di dalam Dropdown**:
   - Native HTML `<select>` tidak memiliki kolom pencarian (*search-as-you-type*).
   - Pengguna harus men-scroll ratusan baris secara manual untuk menemukan kategori yang diinginkan (misal: "KOMPUTER UNIT", "ALAT KEDOKTERAN", atau "KENDARAAN DINAS").
3. **Ketidaksesuaian Lebar Kontainer**:
   - Administrator memerlukan ruang tampilan tabel yang lebih luas untuk membaca kode barang, hierarki kategori, dan status secara nyaman tanpa horizontal scrolling yang sempit.

---

## 2. Komparasi Pendekatan Teknis

Untuk menghadirkan dropdown yang dapat dicari (*searchable combobox*) dengan fitur *cascading* (Level 5 -> Level 6), terdapat 3 opsi implementasi:

```
+---------------------------------------------------------------------------------------------------+
| OPSI 1: TomSelect 2.3.1 (Rekomendasi Utama)                                                       |
| - Sudah dipakai di formulir belanja operator (details/create & edit)                              |
| - Ringan (< 30 KB), tanpa ketergantungan jQuery Select2 yang kaku, styling selaras Tailwind       |
+---------------------------------------------------------------------------------------------------+
| OPSI 2: jQuery Select2                                                                            |
| - Memerlukan penambahan pustaka Select2 baru                                                      |
| - Styling default kaku dan sering bentrok dengan utilitas Tailwind CSS                            |
+---------------------------------------------------------------------------------------------------+
| OPSI 3: Custom Alpine.js Floating Dropdown                                                        |
| - Mengembangkan komponen combobox Alpine.js dari nol                                              |
| - Membutuhkan kode JavaScript kustom yang panjang untuk keyboard accessibility & cascading        |
+---------------------------------------------------------------------------------------------------+
```

### Tabel Matriks Perbandingan

| Kriteria Evaluasi | Opsi 1: TomSelect 2.3.1 (Rekomendasi) | Opsi 2: jQuery Select2 | Opsi 3: Custom Alpine.js Combobox |
|---|---|---|---|
| **Konsistensi Arsitektur Proyek** | **Tinggi** (Sudah dipakai di modul RBA) | Rendah (Menambah library baru) | Sedang |
| **Pencarian Bebas (Search-as-you-type)** | **Sangat Baik** (Multi-field: kode & nama) | Baik | Perlu ditulis manual |
| **Kemudahan Integrasi Cascading AJAX** | **Sangat Mudah** (`clearOptions`, `addOption`) | Sedang | Perlu state management rumit |
| **Kesesuaian Desain Tailwind CSS** | **Sangat Harmonis** (CSS token sudah ada) | Kaku / Perlu banyak override | Sangat fleksibel |
| **Dukungan Tombol Clear Cepat** | **Native Plugin** (`clear_button`) | Memerlukan trik placeholder | Manual |
| **Ukuran & Performa Eksekusi** | **Sangat Ringan** (~28 KB) | Berat (~75 KB + CSS) | Minimalis |

> [!TIP]
> **Rekomendasi**: Pilih **Opsi 1 (TomSelect 2.3.1)**. Pustaka ini sudah terbukti stabil, cepat, dan memiliki aturan CSS yang selaras dengan desain sistem aplikasi SIPAKAR di [resources/views/operator/details/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/details/create.blade.php#L9-L55).

---

## 3. Rincian Solusi & Desain Tampilan

### 3.1 Fitur Searchable Combobox Level 5 & Level 6
1. **Dropdown Level 5 (Kategori Induk)**:
   - Menampilkan 158 pilihan dengan badge kode dan nama yang rapi.
   - Kolom pencarian instan: Admin cukup mengetik kata kunci seperti `"komputer"`, `"alat medis"`, `"kantor"`, atau kode `"1.3.2.10"`.
   - Dilengkapi tombol silang (clear) untuk mengosongkan filter secara instan.
2. **Dropdown Level 6 (Sub-Kategori)**:
   - Dalam keadaan default (Level 5 belum dipilih), dropdown berstatus *disabled* dengan placeholder: *"-- Pilih Kategori Level 5 Terlebih Dahulu --"*.
   - Saat Level 5 dipilih:
     - TomSelect Level 6 otomatis diaktifkan (*enabled*).
     - Memanggil endpoint AJAX [admin/master-barangs/api/level6](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php#L79).
     - Opsi sub-kategori langsung terisi dan siap dicari (*search-enabled*).
3. **Penyelarasan Desain (UI/UX)**:
   - Mengadopsi CSS kustom TomSelect dari `operator.details.create` agar tampilan kotak input, dropdown hover, dan badge kode terlihat modern dan konsisten.
   - Merapikan pembungkus halaman dari `max-w-100` (salah ketik) menjadi `max-w-7xl` (atau opsi layout lebar `w-full` dengan padding yang seimbang) agar tabel master barang tampak lega dan nyaman dibaca.

---

## 4. File yang Akan Dimodifikasi

| No | Berkas | Tipe Perubahan | Deskripsi |
|---|---|---|---|
| 1 | [resources/views/admin/master_barangs/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php) | **Blade View & JS** | - Menambahkan TomSelect CSS & JS CDN<br>- Menambahkan styling harmonisasi Tailwind<br>- Mengganti native `<select>` dengan TomSelect instances<br>- Menghubungkan event `change` TomSelect ke DataTables AJAX reload<br>- Memperbaiki kelas container menjadi `max-w-7xl` |
| 2 | [tests/Feature/MasterBarangCategoryAndSearchTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/MasterBarangCategoryAndSearchTest.php) | **Test Suite** | Menambahkan pengujian responsivitas dan validasi skenario filter Level 5 & 6 |

---

## 5. Rencana Tahapan Eksekusi

```
Step 1: Update Asset Stylesheet & Script
  └─ Tambahkan CDN tom-select@2.3.1 (CSS & JS) di @push('styles') dan @push('scripts')
  └─ Terapkan aturan CSS penyesuaian Tailwind (.ts-control, .ts-dropdown, badge badge styling)

Step 2: Inisialisasi TomSelect untuk Level 5 & Level 6
  └─ Buat instance tomSelectLvl5 dengan pencarian kode & uraian
  └─ Buat instance tomSelectLvl6 dengan cascading loader dari endpoint api/level6
  └─ Pasang event listener agar saat opsi dipilih, DataTables langsung reload

Step 3: Integrasi Tombol Reset Filter
  └─ Saat tombol "Reset Filter" diklik, lakukan tomSelectLvl5.clear() dan tomSelectLvl6.clearOptions()
  └─ Reset DataTables search dan reload tabel

Step 4: Layout Container Fix
  └─ Kembalikan kelas pembungkus dari max-w-100 menjadi max-w-7xl sm:px-6 lg:px-8

Step 5: Verifikasi & Testing
  └─ Jalankan php artisan test --filter=MasterBarangCategoryAndSearchTest
  └─ Uji interaksi pemilihan kategori dan pencarian via browser
```

---

## 6. Kriteria Keberhasilan (Success Criteria)

1. **Pencarian Cepat & Akurat**: Admin dapat mengetik huruf apa saja (misal: `"komp"` atau `"1.3.2"`) dan dropdown Level 5 langsung menyaring opsi yang relevan dalam < 100 ms.
2. **Cascading Mulus**: Memilih Level 5 otomatis memicu pemuatan data sub-kategori Level 6 tanpa kedipan atau freeze UI.
3. **Penyaringan DataTables Real-Time**: Data barang di tabel otomatis tersaring sesuai kategori yang dipilih.
4. **Desain Rapi & Responsif**: Tampilan dropdown menyatu sempurna dengan gaya Tailwind CSS rumah sakit.
5. **Zero Regression**: Seluruh 281 pengujian otomatis aplikasi tetap berstatus **PASS** (100% hijau).
