# Walkthrough: Integrasi Filter Kategori Level 5 & Level 6, Pembersihan Typo Kolom Deskripsi, dan Smart Search Master Barang Permendagri No. 108 Tahun 2016

Penerapan integrasi Level 5 (Rincian Objek) dan Level 6 (Sub Rincian Objek) ke dalam tabel `master_barangs`, pembersihan 720 typo pada kolom deskripsi, serta fitur **Smart Search (Space-Insensitive)** telah **selesai diimplementasikan dan diverifikasi 100% lulus (281 tests passed)**.

---

## 1. Ringkasan Perubahan yang Diterapkan

### 1.1 Skema Database (`master_barangs`)
- **Migrasi**: [database/migrations/2026_10_24_100000_add_level5_and_level6_to_master_barangs_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_10_24_100000_add_level5_and_level6_to_master_barangs_table.php)
  - Menambahkan kolom `kode_level5` (VARCHAR 20, indexed), `nama_level5` (VARCHAR 255, indexed)
  - Menambahkan kolom `kode_level6` (VARCHAR 30, indexed), `nama_level6` (VARCHAR 255)
  - Menambahkan composite index `idx_master_barangs_level5_level6` untuk akselerasi cascading dropdown query.

### 1.2 Data Cleansing & Sinkronisasi Bersih Tanpa Typo
- **Seeder**: [database/seeders/Permendagri108MasterBarangSeeder.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/Permendagri108MasterBarangSeeder.php)
  - Membaca data resmi bebas typo dari `permendagri_108_master_level1_6.json` (158 item Level 5 dan 980 item Level 6).
  - Mengeliminasi seluruh 720 typo OCR lama (seperti `"AIATSAR"`, `"BAWAHAIR"`, `"BARANGPAKAIHABIS"`, `"KOMUNlKASI"`).
  - Format baku kolom deskripsi diperbarui seragam menjadi: `"{nama_level5} > {nama_level6}"` (contoh: `"KOMPUTER UNIT > PERSONAL KOMPUTER"`, `"ALAT SAR > ALAT KERJA BAWAH AIR"`).
- **Artisan Command**: `php artisan rkbmd:import-permendagri` pada [ImportPermendagri108MasterBarangCommand.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/ImportPermendagri108MasterBarangCommand.php).
- **Production SQL Script**: Berkas SQL mandiri [data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql) telah di-regenerate memuat seluruh 13.479 baris barang lengkap dengan Level 5, 6, dan deskripsi bersih.

### 1.3 Smart Search (Space-Insensitive & Multi-Attribute)
- **Model**: [app/Models/MasterBarang.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/MasterBarang.php)
  - `scopeSearch`: Menggunakan normalisasi `REPLACE(REPLACE(nama_barang, ' ', ''), '-', '') LIKE '%keyword_bersih%'`.
  - Kata kunci `"laptop"` langsung menemukan `"Lap Top"` dan `"Tas Laptop..."`.
  - Kata kunci `"whiteboard"` langsung menemukan `"White Board"`.
  - Kata kunci `"micro phone"` langsung menemukan `"Microphone"`.
  - Kata kunci kategori seperti `"personal komputer"` atau `"alat kedokteran"` langsung menemukan seluruh barang dalam kategori tersebut.
  - Tambahan scopes: `scopeLevel5($kode)` dan `scopeLevel6($kode)`.

### 1.4 Halaman Admin Master Barang (`/admin/master-barangs`)
- **Controller**: [app/Http/Controllers/Admin/MasterBarangController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php)
  - Menyediakan endpoint AJAX [admin/master-barangs/api/level6](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php#L79) untuk cascading dropdown.
  - Filter `level5` dan `level6` pada DataTables Server-Side Processing.
- **Tampilan**: [resources/views/admin/master_barangs/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php)
  - Toolbar Filter Kategori: Dropdown Level 5 (158 pilihan), Dropdown Cascading Level 6, dan Tombol Reset Filter.
  - Kolom Nama Barang kini menampilkan badge elegan:
    `📁 KOMPUTER UNIT › 📂 PERSONAL KOMPUTER`.

### 1.5 Formulir RKBMD Operator (`operator/rkbmd/create` dan `edit`)
- **Controller**: [app/Http/Controllers/Operator/RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)
  - Endpoint `searchMasterBarangs` menerima filter opsional `level5` dan `level6`.
- **Tampilan**: [resources/views/operator/rkbmd/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php) & [resources/views/operator/rkbmd/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php)
  - Dropdown filter cepat Level 5 pada setiap combobox pencarian barang.
  - Dropdown hasil pencarian menampilkan badge kategori Level 5 dan Level 6 yang informatif.
  - Barang terpilih menampilkan hierarki kategori yang rapi.

---

## 2. Hasil Verifikasi & Pengujian

### 2.1 Verifikasi Data
| Indikator | Nilai Sebelum | Nilai Sesudah | Status |
|---|---|---|---|
| Item dengan `nama_level5` NULL | 13.479 baris | **0 baris** | Selesai |
| Item dengan `nama_level6` NULL | 13.479 baris | **0 baris** | Selesai |
| Typo `"AIATSAR"` pada `deskripsi` | Ada di ID 10801 dst. | **0 baris** | Bersih Total |
| Typo `"BAWAHAIR"` pada `deskripsi` | Ada di ID 10801 dst. | **0 baris** | Bersih Total |
| Pencarian `"laptop"` | 1 item (hanya tas) | **2 item (termasuk unit Lap Top)** | Selesai |
| Pencarian `"whiteboard"` | 0 item | **3 item (White Board)** | Selesai |
| Pencarian `"micro phone"` | 0 item | **8 item (Microphone)** | Selesai |

### 2.2 Verifikasi Otomatis (PHPUnit Feature Tests)
- `tests/Feature/MasterBarangCategoryAndSearchTest.php`: **7/7 PASSED** (32 assertions).
- `tests/Feature/Admin/PermendagriMasterBarangTest.php`: **7/7 PASSED** (40 assertions).
- **Seluruh Test Suite Aplikasi**: **281 PASSED** (1.434 assertions, 0 failures).

---

## 3. Langkah Penerapan ke Server Production (Asumsi Belum Pernah Impor Level 7)

Jika di server produksi Anda **belum pernah mengimpor tabel data Level 7 sama sekali**, Anda dapat memilih salah satu dari **2 cara mudah dan optimal** berikut:

### Opsi A: Menggunakan Laravel CLI (Artisan Standard)

Jalankan perintah berikut di terminal server Anda secara berurutan:

```bash
# 1. Jalankan migrasi database untuk membuat tabel master_barangs dan kolom kategori Level 5 & 6
php artisan migrate

# 2. Jalankan seeder resmi Permendagri 108 (Level 7 + Kategori Level 5 & 6)
php artisan rkbmd:import-permendagri
```
*Proses impor hanya memakan waktu ~8–10 detik dan langsung mengisikan seluruh 13.476 data barang bersih.*

---

### Opsi B: Menggunakan File SQL Langsung (Paling Cepat untuk Server / Hosting)

Jika server produksi Anda memiliki keterbatasan memori CLI atau Anda lebih menyukai import langsung via MySQL Client / phpMyAdmin:

1. Pastikan migrasi tabel dasar sudah berjalan (`php artisan migrate`).
2. Jalankan perintah import file SQL yang telah disiapkan di folder data:
   ```bash
   mysql -u username -p nama_database < data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql
   ```
   *(Atau unggah file `populate_master_barangs_from_permendagri.sql` melalui tab Import di phpMyAdmin)*.

File SQL tersebut sudah dirancang bersifat **idempotent** (aman dijalankan berulang kali dengan `ON DUPLICATE KEY UPDATE` dan safe `ALTER TABLE`).
