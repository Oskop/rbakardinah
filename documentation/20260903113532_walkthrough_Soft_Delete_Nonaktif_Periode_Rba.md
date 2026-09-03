# Walkthrough - Soft Delete (Nonaktifkan) pada Menu Periode RBA dan Pencatatan Log Aktivitas

Mekanisme **soft delete (nonaktifkan / *deactivate*, bukan hapus permanen)** pada menu **Periode RBA** Administrator (`admin.periods.*`) beserta **pencatatan otomatis ke Log Data (`activity_logs`)**, **integrasi DataTables v2 TailwindCSS**, dan **proteksi pembuatan dokumen RBA baru** telah selesai diimplementasikan, diverifikasi, dan diuji secara menyeluruh.

---

## Ringkasan Perubahan & Fitur yang Diterapkan

### 1. Perlindungan Integritas Data Induk (Tanpa Tombol Delete)
- Karena tabel `rba_periods` merupakan data induk bagi seluruh struktur anggaran dan dokumen pengajuan RBA (`rba_headers`), maka **penghapusan fisik secara permanen (*hard delete*) ditiadakan**.
- Ditambahkan kolom `is_active` (boolean, default `true`) pada tabel `rba_periods` melalui file migrasi:
  - `database/migrations/2026_09_03_113500_add_is_active_to_rba_periods_table.php`.
- Tombol **"Delete"** dihapus seluruhnya dari antarmuka dan digantikan dengan tombol status kontekstual:
  - **"Nonaktifkan"** (warna amber) untuk periode yang sedang aktif.
  - **"Aktifkan"** (warna hijau) untuk periode yang sedang nonaktif.
  - Dilengkapi dialog konfirmasi dinamis: *"Apakah Anda yakin ingin [menonaktifkan/mengaktifkan] periode [Nama Periode]?"*.

### 2. Logika Controller & Proteksi Relasi
- **`RbaPeriodController@destroy`**:
  Dialihkan untuk melakukan toggle status `is_active` secara aman:
  ```php
  $period->update(['is_active' => !$period->is_active]);
  ```
  Data periode tetap utuh di database sehingga tidak ada risiko *foreign key constraint violation* pada dokumen RBA (`rba_headers`).
- **`RbaHeaderController@create`**:
  Pada form pembuatan dokumen RBA baru oleh Administrator, periode yang berstatus **nonaktif** otomatis disaring sehingga hanya periode yang aktif yang dapat dipilih:
  ```php
  $periods = \App\Models\RbaPeriod::where('is_active', true)->orderBy('name')->get();
  ```

### 3. Pencatatan Otomatis ke Audit Log Data (`activity_logs`)
- Trait `LogsActivity` disempurnakan untuk menangani model `RbaPeriod`:
  ```php
  if ($modelName === 'RbaPeriod') {
      $periodName = $model->name ?? ($model->getOriginal('name') ?? "#{$key}");
      if ($action === 'updated' && isset($new['is_active'])) {
          $statusText = $new['is_active'] ? 'mengaktifkan' : 'menonaktifkan';
          return "{$actor} {$statusText} Periode RBA: \"{$periodName}\"";
      }
      return "{$actor} {$actionVerb} Periode RBA: \"{$periodName}\"";
  }
  ```
- Setiap aksi aktivasi maupun deaktivasi periode otomatis dicatat dengan narasi jelas, waktu, nilai sebelum & sesudah (`old_values` & `new_values`), alamat IP, dan user agent.

### 4. Integrasi DataTables Modern & Toolbar Filter Kolom
- **Tabel DataTables v2 TailwindCSS (`#periods-table`):**
  - Kolom ID, Nama Periode RBA, RBA Terdaftar (`headers_count`), Status, dan Aksi.
  - Pencarian bebas instan (*real-time search*), paginasi (10, 25, 50, 100), dan pengurutan dinamis (*sorting*).
- **Dedicated Column Filter Toolbar:**
  - **Filter Status:** Menyaring `Semua Status`, `Active`, dan `Inactive` dengan pencocokan regex eksak (`^Active$` / `^Inactive$`).
  - **Filter Keterikatan RBA:** Menyaring periode yang sedang digunakan dalam RBA (`Digunakan dalam RBA`) vs yang belum digunakan (`Belum Digunakan (0)`).
  - **Tombol Reset:** `🔄 Reset Semua Filter` untuk mengembalikan seluruh filter dan pencarian ke kondisi awal secara instan.
- **Kolom RBA Terdaftar:** Menampilkan badge jumlah RBA terkait (`📊 X Dokumen RBA`) via `withCount('headers')` sehingga Administrator mengetahui keterikatan dokumen sebelum menonaktifkan periode.
- **Formulir Tambah & Edit:**
  - Ditambahkan kontrol checkbox status aktif (`is_active`) pada `create.blade.php` dan `edit.blade.php`.

---

## File yang Dimodifikasi & Dibuat

1. **[NEW] [2026_09_03_113500_add_is_active_to_rba_periods_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_113500_add_is_active_to_rba_periods_table.php)**
   - Migrasi penambahan kolom `is_active` pada tabel `rba_periods`.
2. **[MODIFY] [RbaPeriod.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaPeriod.php)**
   - Menambahkan `is_active` ke `$fillable` dan `$casts`, serta relasi `headers()`.
3. **[MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)**
   - Menambahkan penanganan narasi log khusus saat status `is_active` RbaPeriod diperbarui.
4. **[MODIFY] [RbaPeriodController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaPeriodController.php)**
   - Mengubah `destroy` menjadi toggle status, menyertakan hitungan `withCount('headers')` pada `index`, dan mendukung `is_active` pada `store`/`update`.
5. **[MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)**
   - Menyaring hanya periode aktif saat membuat dokumen RBA baru.
6. **[MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/periods/index.blade.php)**
   - Mengintegrasikan DataTables v2 TailwindCSS, Filter Toolbar (Status & Keterikatan RBA), kolom Status, dan tombol toggle Nonaktifkan/Aktifkan.
7. **[MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/periods/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/periods/edit.blade.php)**
   - Menambahkan checkbox kontrol status aktif (`is_active`).
8. **[NEW] [PeriodManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PeriodManagementTest.php)**
   - Menulis pengujian otomatis untuk DataTables, filter status & keterikatan RBA, soft delete toggle, pencatatan log data, dan proteksi dropdown pembuatan RBA.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests (PHPUnit)
Seluruh **135 test cases (627 assertions)** pada aplikasi lulus 100% tanpa kegagalan:
```text
PASS  Tests\Feature\Admin\PeriodManagementTest
✓ admin can view periods index with status and filters                          1.44s  
✓ admin can deactivate period instead of deleting                               0.04s  
✓ admin can reactivate period                                                   0.03s  
✓ period deactivation is recorded in activity log                               0.03s  
✓ inactive period cannot be selected when creating new rba header               0.16s  
✓ admin can create and update period with status                                0.04s  

PASS  Tests\Feature\Admin\AccountCodeTest (6 passed, 40 assertions)
PASS  Tests\Feature\Admin\KelompokBelanjaTest (7 passed, 42 assertions)
PASS  Tests\Feature\Admin\UnitManagementTest (5 passed, 33 assertions)
PASS  Tests\Feature\Admin\AdminDashboardTest (4 passed, 45 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\ReviewTest (11 passed, 70 assertions)
PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)

Tests:    135 passed (627 assertions)
Duration: 39.03s
```

### 2. Kompilasi Frontend Assets (Bun)
Asset frontend dikompilasi menggunakan Vite melalui `bun run build`:
- `public/build/assets/app-DyG4jMwx.css` (84.10 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.18s**
