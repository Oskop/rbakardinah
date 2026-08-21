# Walkthrough - Perbaikan Error Tambah Kelompok Belanja (Missing `kode` Field)

Perbaikan terhadap `QueryException` saat menyimpan data Kelompok Belanja baru akibat hilangnya field `kode` pada form dan controller telah selesai dilakukan dan terverifikasi secara penuh.

## Penyebab Error
Tabel database `kelompok_belanjas` membutuhkan nilai kolom `kode` (NOT NULL). Sebelumnya, form tambah (`create.blade.php`) dan form ubah (`edit.blade.php`) serta `KelompokBelanjaController` hanya memproses field `name`, sehingga MySQL mengembalikan error `Field 'kode' doesn't have a default value`.

## Perubahan yang Dilakukan

### 1. Controller (`app/Http/Controllers/KelompokBelanjaController.php`)
- **[MODIFY] [KelompokBelanjaController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/KelompokBelanjaController.php)**
  - Menambahkan aturan validasi `kode` (`required|string|max:50|unique:kelompok_belanjas,kode`) pada method `store()`.
  - Menambahkan aturan validasi `kode` (`required|string|max:50|unique:kelompok_belanjas,kode,` . $kelompokBelanja->id) pada method `update()`.

### 2. Antarmuka Pengguna (UI)
- **[MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/create.blade.php)**
  - Menambahkan bidang input **Kode Kelompok** (misal: `5.1.04`) beserta penanganan pesan error validasinya.
- **[MODIFY] [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/edit.blade.php)**
  - Menambahkan bidang input **Kode Kelompok** beserta pemuatan nilai sebelumnya (`old('kode', $kelompokBelanja->kode)`).

### 3. Pengujian Otomatis (Automated Tests)
- **[NEW] [KelompokBelanjaTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/KelompokBelanjaTest.php)**
  - Menambahkan test suite untuk memverifikasi proses pendaftaran (*store*) dan pembaruan (*update*) Kelompok Belanja dengan kolom `kode` dan `name`.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh test suite aplikasi (59 unit & feature tests) telah dijalankan dan **PASS 100%**:

```text
PASS  Tests\Feature\Admin\KelompokBelanjaTest
✓ admin can create kelompok belanja with kode and name                                                         0.04s  
✓ admin can update kelompok belanja kode and name                                                              0.03s  

Tests:    59 passed (203 assertions)
Duration: 5.14s
```

### 2. Verifikasi Manual
- Akses menu `Admin -> Kelompok Belanja -> Add New Group`.
- Inputkan **Kode Kelompok** (misal `5.1.04`) dan **Nama Kelompok** (misal `Belanja Operasional Lainnya`).
- Data berhasil disimpan ke database tanpa error `1364 Field 'kode' doesn't have a default value`.
