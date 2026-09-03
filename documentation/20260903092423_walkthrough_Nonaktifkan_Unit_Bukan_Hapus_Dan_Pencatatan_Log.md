# Walkthrough - Nonaktifkan Unit (Bukan Hapus) dan Pencatatan Log Aktivitas

Fitur peniadaan tombol delete pada menu **Units** Administrator dan penggantiannya dengan mekanisme **Nonaktifkan / Aktifkan (*deactivate/activate*)** beserta **pencatatan otomatis ke Log Data (`activity_logs`)** telah selesai diimplementasikan dan diverifikasi secara menyeluruh.

---

## Ringkasan Perubahan & Fitur yang Diterapkan

### 1. Perlindungan Integritas Data Parent
- Karena tabel `units` merupakan entitas induk bagi relasi pengguna (`users`), dokumen pengajuan anggaran (`rba_submissions`), dan transaksi belanja lainnya, maka **penghapusan data secara permanen (*hard delete*) ditiadakan sepenuhnya**.
- Kolom `is_active` (boolean, default `true`) telah ditambahkan pada tabel `units` melalui migrasi database:
  - File migrasi: `database/migrations/2026_09_03_092500_add_is_active_to_units_table.php`

### 2. Antarmuka Manajemen Unit (`resources/views/admin/units/index.blade.php`)
- **Kolom Status Baru:**
  - Ditambahkan kolom **Status** dengan badge visual yang jelas:
    - `Active` (warna hijau) untuk unit aktif.
    - `Inactive` (warna abu-abu) untuk unit nonaktif.
- **Peniadaan Tombol Delete:**
  - Tombol bertuliskan "Delete" telah dihapus secara total dari antarmuka.
  - Digantikan dengan tombol aksi kontekstual:
    - **"Nonaktifkan"** (warna amber/merah) untuk unit yang sedang aktif.
    - **"Aktifkan"** (warna hijau) untuk unit yang sedang nonaktif.
  - Dilengkapi kotak dialog konfirmasi dinamis: *"Apakah Anda yakin ingin [menonaktifkan/mengaktifkan] unit kerja [Nama Unit]?"*.
- **Formulir Tambah & Edit:**
  - Ditambahkan kontrol status aktif (`is_active` checkbox) pada halaman `create.blade.php` dan `edit.blade.php`.

### 3. Logika Controller (`UnitController@destroy`)
- Method `destroy(\App\Models\Unit $unit)` dialihkan secara aman untuk melakukan toggle status:
  ```php
  $unit->update(['is_active' => !$unit->is_active]);
  ```
- Mengembalikan flash message notifikasi yang informatif: *"Unit {name} berhasil dinonaktifkan"* atau *"Unit {name} berhasil diaktifkan"*.
- Data unit tetap utuh di database sehingga foreign key dan riwayat RBA tidak pernah mengalami *broken relation*.

### 4. Pencatatan Otomatis ke Audit Log Data (`activity_logs`)
- Trait `LogsActivity` pada model `Unit` disempurnakan:
  ```php
  if ($modelName === 'Unit') {
      $unitName = $model->name ?? ($model->getOriginal('name') ?? "#{$key}");
      if ($action === 'updated' && isset($new['is_active'])) {
          $statusText = $new['is_active'] ? 'mengaktifkan' : 'menonaktifkan';
          return "{$actor} {$statusText} Unit Kerja: \"{$unitName}\"";
      }
      return "{$actor} {$actionVerb} data Unit: \"{$unitName}\"";
  }
  ```
- Setiap kali status unit diubah, sistem mencatat riwayat ke tabel `activity_logs` dengan deskripsi deskriptif:
  - *"Administrator menonaktifkan Unit Kerja: 'Nama Unit'"*
  - *"Administrator mengaktifkan Unit Kerja: 'Nama Unit'"*
  - Lengkap dengan nilai sebelum & sesudah (`old_values` & `new_values`), alamat IP, dan user agent.

---

## File yang Dimodifikasi & Dibuat

1. **[NEW] [2026_09_03_092500_add_is_active_to_units_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_092500_add_is_active_to_units_table.php)**
   - Migrasi penambahan kolom `is_active` pada tabel `units`.
2. **[MODIFY] [Unit.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/Unit.php)**
   - Penambahan `is_active` ke `$fillable` dan `$casts`.
3. **[MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)**
   - Penyesuaian deskripsi aktivitas log saat status `is_active` Unit diperbarui.
4. **[MODIFY] [UnitController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/UnitController.php)**
   - Mengubah `destroy` menjadi toggle status unit dan memperbarui `index`, `store`, serta `update`.
5. **[MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/index.blade.php)**
   - Penambahan kolom Status dan penggantian tombol Delete menjadi tombol Nonaktifkan/Aktifkan.
6. **[MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/edit.blade.php)**
   - Penambahan kontrol status aktif pada form input.
7. **[NEW] [UnitManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UnitManagementTest.php)**
   - Pengujian otomatis untuk verifikasi kolom status, peniadaan delete, toggle nonaktif/aktif, pencatatan log data, dan otorisasi admin.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests (PHPUnit)
Seluruh **118 test cases (505 assertions)** pada aplikasi lulus 100% tanpa kegagalan:
```text
PASS  Tests\Feature\Admin\UnitManagementTest
✓ admin can view units index with status column                                 1.25s  
✓ admin can deactivate unit instead of deleting                                 0.04s  
✓ admin can reactivate unit                                                     0.03s  
✓ unit deactivation is recorded in activity log                                 0.03s  
✓ non admin cannot manage units                                                 0.04s  

PASS  Tests\Feature\Admin\AdminDashboardTest (4 passed, 45 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\ReviewTest (11 passed, 70 assertions)
PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)

Tests:    118 passed (505 assertions)
Duration: 38.03s
```

### 2. Kompilasi Frontend Assets (Bun)
Asset frontend dikompilasi menggunakan Vite melalui `bun run build`:
- `public/build/assets/app-DyG4jMwx.css` (84.10 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.18s**
