# Walkthrough - Soft Delete (Nonaktifkan) pada Menu Kelompok Belanja dan Pencatatan Log Aktivitas

Mekanisme **soft delete (nonaktifkan, bukan hapus permanen)** pada menu **Kelompok Belanja** Administrator (`admin.kelompok-belanja.*`) beserta **pencatatan otomatis ke Log Data (`activity_logs`)** dan **integrasi DataTables v2 TailwindCSS** telah selesai diimplementasikan, diverifikasi, dan diuji secara menyeluruh.

---

## Ringkasan Perubahan & Fitur yang Diterapkan

### 1. Perlindungan Integritas Data Induk (Tanpa Tombol Delete)
- Karena tabel `kelompok_belanjas` merupakan data induk bagi akun rekening belanja (`account_codes`) dan pengajuan anggaran belanja RBA, maka **penghapusan fisik secara permanen (*hard delete*) ditiadakan**.
- Ditambahkan kolom `is_active` (boolean, default `true`) pada tabel `kelompok_belanjas` melalui file migrasi:
  - `database/migrations/2026_09_03_095500_add_is_active_to_kelompok_belanjas_table.php`.
- Tombol **"Delete"** dihapus seluruhnya dari antarmuka dan digantikan dengan tombol status kontekstual:
  - **"Nonaktifkan"** (warna amber) untuk kelompok belanja yang sedang aktif.
  - **"Aktifkan"** (warna hijau) untuk kelompok belanja yang sedang nonaktif.
  - Dilengkapi dialog konfirmasi dinamis: *"Apakah Anda yakin ingin [menonaktifkan/mengaktifkan] kelompok belanja [Nama Kelompok]?"*.

### 2. Logika Controller & Proteksi Relasi
- **`KelompokBelanjaController@destroy`**:
  Dialihkan untuk melakukan toggle status `is_active` secara aman:
  ```php
  $kelompokBelanja->update(['is_active' => !$kelompokBelanja->is_active]);
  ```
  Data kelompok belanja tetap utuh di database sehingga tidak ada risiko *broken foreign key* pada nomor rekening belanja.
- **`AccountCodeController@create` & `edit`**:
  Pada form tambah rekening baru, hanya kelompok belanja yang **aktif** yang dapat dipilih. Pada form edit rekening, kelompok belanja aktif serta kelompok belanja yang saat ini sudah terpasang tetap dapat dipertahankan.

### 3. Pencatatan Otomatis ke Audit Log Data (`activity_logs`)
- Trait `LogsActivity` disempurnakan untuk menangani model `KelompokBelanja`:
  ```php
  if ($modelName === 'KelompokBelanja') {
      $groupName = ($model->kode ?? '') . ' - ' . ($model->name ?? ($model->getOriginal('name') ?? "#{$key}"));
      if ($action === 'updated' && isset($new['is_active'])) {
          $statusText = $new['is_active'] ? 'mengaktifkan' : 'menonaktifkan';
          return "{$actor} {$statusText} Kelompok Belanja: \"{$groupName}\"";
      }
      return "{$actor} {$actionVerb} Kelompok Belanja: \"{$groupName}\"";
  }
  ```
- Setiap aksi deaktivasi maupun reaktivasi otomatis dicatat dengan narasi jelas, waktu, nilai lama/baru (`old_values` & `new_values`), alamat IP, dan user agent.

### 4. Integrasi DataTables Modern & Toolbar Filter Kolom
- **Tabel DataTables v2 TailwindCSS (`#kelompok-belanja-table`):**
  - Kolom Kode, Nama, Rekening Terdaftar, Status, dan Aksi.
  - Pencarian bebas instan (*real-time search*), paginasi (10, 25, 50, 100), dan pengurutan dinamis (*sorting*).
- **Dedicated Column Filter Toolbar:**
  - **Filter Status:** Menyaring `Semua Status`, `Active`, dan `Inactive` dengan pencocokan regex eksak (`^Active$` / `^Inactive$`).
  - **Filter Keterikatan Rekening:** Menyaring kelompok yang memiliki rekening (`Memiliki Rekening Terdaftar`) vs yang belum memiliki rekening (`Belum Ada Rekening (0)`).
  - **Tombol Reset:** `🔄 Reset Semua Filter` untuk mengembalikan seluruh filter dan pencarian ke kondisi awal secara instan.
- **Kolom Rekening Terdaftar:** Menampilkan badge jumlah rekening belanja (`📋 X Rekening`) via `withCount('accountCodes')` sehingga Administrator mengetahui dampak sebelum menonaktifkan kelompok belanja.

---

## File yang Dimodifikasi & Dibuat

1. **[NEW] [2026_09_03_095500_add_is_active_to_kelompok_belanjas_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_095500_add_is_active_to_kelompok_belanjas_table.php)**
   - Migrasi penambahan kolom `is_active` pada tabel `kelompok_belanjas`.
2. **[MODIFY] [KelompokBelanja.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/KelompokBelanja.php)**
   - Menambahkan `is_active` ke `$fillable` dan `$casts`.
3. **[MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)**
   - Menambahkan penanganan narasi log khusus untuk model `KelompokBelanja`.
4. **[MODIFY] [KelompokBelanjaController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/KelompokBelanjaController.php)**
   - Menambahkan `withCount('accountCodes')`, mengubah `destroy` menjadi toggle status, dan mendukung `is_active` pada form store/update.
5. **[MODIFY] [AccountCodeController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/AccountCodeController.php)**
   - Menyaring kelompok belanja aktif pada form tambah/edit rekening belanja.
6. **[MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/index.blade.php)**
   - Mengintegrasikan DataTables v2 TailwindCSS, Filter Toolbar, kolom Status & Rekening Terdaftar, dan tombol toggle Nonaktifkan/Aktifkan.
7. **[MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/edit.blade.php)**
   - Menambahkan checkbox kontrol status aktif (`is_active`).
8. **[MODIFY] [KelompokBelanjaTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/KelompokBelanjaTest.php)**
   - Menambahkan pengujian otomatis untuk DataTables, filter status, soft delete toggle, pencatatan log data, dan proteksi form rekening.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests (PHPUnit)
Seluruh **123 test cases (547 assertions)** pada aplikasi lulus 100% tanpa kegagalan:
```text
PASS  Tests\Feature\Admin\KelompokBelanjaTest
✓ admin can create kelompok belanja with kode and name                          1.11s  
✓ admin can update kelompok belanja kode and name                              0.03s  
✓ admin can view kelompok belanja index with status and filters                 0.23s  
✓ admin can deactivate kelompok belanja instead of deleting                     0.03s  
✓ admin can reactivate kelompok belanja                                         0.03s  
✓ kelompok belanja deactivation is recorded in activity log                     0.04s  
✓ inactive kelompok belanja cannot be selected for new account code             0.19s  

PASS  Tests\Feature\Admin\UnitManagementTest (5 passed, 33 assertions)
PASS  Tests\Feature\Admin\AdminDashboardTest (4 passed, 45 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\ReviewTest (11 passed, 70 assertions)
PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)

Tests:    123 passed (547 assertions)
Duration: 38.28s
```

### 2. Kompilasi Frontend Assets (Bun)
Asset frontend dikompilasi menggunakan Vite melalui `bun run build`:
- `public/build/assets/app-DyG4jMwx.css` (84.10 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.27s**
