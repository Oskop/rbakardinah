# Walkthrough: Implementasi Impor Master Barang Permendagri No. 108 Tahun 2016 (Level 7)

Implementasi impor katalog data barang Level 7 dari berkas [tabel_2_6_sub_sub_rincian_objek.json](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/data/permendagri_108_2016/tabel_2_6_sub_sub_rincian_objek.json) ke dalam tabel `master_barangs` pada sistem SIPAKAR telah berhasil diselesaikan secara menyeluruh.

Solusi dirancang dengan prinsip **100% Interoperabel & Bebas Konflik (Zero Conflict)**:
- Di **Server Produksi**: Anda dapat mengeksekusi file `.sql` langsung, ATAU menjalankan `php artisan migrate` dan seeder tanpa takut terjadi benturan/duplikasi kolom.
- Di **Environment Development**: Anda dapat menjalankan `php artisan migrate`, `php artisan rkbmd:import-permendagri`, atau `php artisan db:seed --class=Permendagri108MasterBarangSeeder` secara idempoten kapan saja.

---

## 1. Komponen yang Telah Diterapkan

### A. Migrasi Database Idempoten
- File: [`database/migrations/2026_10_23_130000_add_kode_permendagri_to_master_barangs_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_10_23_130000_add_kode_permendagri_to_master_barangs_table.php)
- Memeriksa `if (!Schema::hasColumn('master_barangs', 'kode_permendagri'))` sebelum menambahkan kolom dan indeks `idx_master_barangs_kode_permendagri`.
- Menjamin tidak ada error *"Duplicate column name"* jika tabel sudah diperbarui melalui file `.sql` sebelumnya.

### B. Pembaruan Model Eloquent
- File: [`app/Models/MasterBarang.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/MasterBarang.php)
- Kolom `kode_permendagri` telah ditambahkan ke `$fillable`.
- Menambahkan method `scopeSearch($query, $keyword)` yang mencari secara bersamaan ke `nama_barang`, `kode_barang`, dan `kode_permendagri`.

### C. Seeder & Artisan Command (Metode Opsi 2)
1. **Seeder**: [`database/seeders/Permendagri108MasterBarangSeeder.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/Permendagri108MasterBarangSeeder.php)
   - Memfilter hanya item **Level 7** (total 13.476 item barang fisik).
   - Memetakan `item.kode` ke `kode_permendagri` (kode referensi non-primary key).
   - Memetakan `item.uraian` ke `nama_barang`.
   - Mengambil hierarki kategori Level 5 & Level 6 ke dalam kolom `deskripsi`.
   - Menangani **16 pasang kode duplikat** pada regulasi Permendagri: kode referensi `kode_permendagri` tetap asli, sedangkan `kode_barang` diberikan postfix `.1` pada baris kedua agar keunikan database tetap terjaga.
   - Menggunakan `DB::table('master_barangs')->upsert(..., ['kode_barang'], [...])` dalam batch 500 baris sehingga eksekusi tuntas hanya dalam hitungan detik dan sepenuhnya idempoten.
2. **Artisan Command**: [`app/Console/Commands/ImportPermendagri108MasterBarangCommand.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/ImportPermendagri108MasterBarangCommand.php)
   - Signature: `php artisan rkbmd:import-permendagri`

### D. File SQL Produksi (Metode Opsi 1)
- File: [`data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql)
- Dilengkapi DDL kondisional (`information_schema.columns` check) sehingga aman dieksekusi baik sebelum maupun sesudah migrasi Laravel dijalankan.
- Menggunakan `INSERT INTO master_barangs (...) VALUES (...) ON DUPLICATE KEY UPDATE` sebanyak 27 batch query multi-row.

### E. Tampilan Admin & Form CRUD
- [`resources/views/admin/master_barangs/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php): Menampilkan badge `Ref 108: [kode]` pada kolom Kode Barang.
- [`resources/views/admin/master_barangs/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/create.blade.php): Input `Kode Permendagri No. 108 Tahun 2016 (Referensi)`.
- [`resources/views/admin/master_barangs/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/edit.blade.php): Input edit `Kode Permendagri`.
- [`app/Http/Controllers/Admin/MasterBarangController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php): Validasi input `kode_permendagri` pada `store` dan `update`.

---

## 2. Cara Penggunaan di Server

### Skenario A: Di Server Produksi (Via Eksekusi File SQL)
Jika Anda memilih mengimpor langsung melalui terminal MySQL / GUI database server:
```bash
# 1. Update source code dari git
git pull origin main

# 2. Eksekusi file SQL ke database
mysql -u [username] -p db_rba_hospital < data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql

# 3. Jalankan migrate (aman, migrasi otomatis mendeteksi kolom sudah ada)
php artisan migrate
```

### Skenario B: Di Server Produksi atau Development (Via Artisan / Seeder)
Jika Anda memilih menggunakan perintah PHP / Artisan:
```bash
# 1. Jalankan migrasi database
php artisan migrate

# 2. Jalankan perintah impor
php artisan rkbmd:import-permendagri
# ATAU via db:seed
php artisan db:seed --class=Permendagri108MasterBarangSeeder
```

> [!NOTE]
> Kedua skenario di atas dapat dijalankan bolak-balik dalam urutan apa pun tanpa menimbulkan konflik atau duplikasi data (*100% Idempotent*).

---

## 3. Hasil Pengujian Otomatis (Automated Testing)

Dibuat test suite baru pada [`tests/Feature/Admin/PermendagriMasterBarangTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PermendagriMasterBarangTest.php) dan dieksekusi bersama regression test RKBMD:

```bash
php artisan test --filter="RkbmdTest|PermendagriMasterBarangTest"
```

**Hasil**:
```
   PASS  Tests\Feature\Admin\PermendagriMasterBarangTest
  ✓ master barangs table has kode permendagri column
  ✓ admin can create master barang with kode permendagri
  ✓ admin can update master barang with kode permendagri
  ✓ scope search matches kode permendagri
  ✓ artisan command and seeder are idempotent

   PASS  Tests\Feature\Operator\RkbmdTest
  ✓ (26 test cases existing RKBMD passed)

  Tests:    31 passed (142 assertions)
  Duration: 6.63s
```
Semua 31 tests **PASS 100%** tanpa error atau regresi.
