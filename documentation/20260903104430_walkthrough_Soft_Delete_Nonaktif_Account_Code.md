# Walkthrough - Soft Delete (Nonaktifkan) pada Menu Nomor Rekening (Account Code) dan Pencatatan Log Aktivitas

Mekanisme **soft delete (nonaktifkan / *deactivate*, bukan hapus permanen)** pada menu **Nomor Rekening (Account Code)** Administrator (`admin.account-codes.*`) beserta **pencatatan otomatis ke Log Data (`activity_logs`)**, **integrasi DataTables v2 TailwindCSS**, dan **proteksi penginputan usulan belanja operator** telah selesai diimplementasikan, diverifikasi, dan diuji secara menyeluruh.

---

## Ringkasan Perubahan & Fitur yang Diterapkan

### 1. Perlindungan Integritas Data Induk (Tanpa Tombol Delete)
- Karena tabel `account_codes` merupakan data induk bagi rincian usulan belanja unit (`rba_details`) dan penetapan pagu rekening (`rba_account_pagus`), maka **penghapusan fisik secara permanen (*hard delete*) ditiadakan**.
- Ditambahkan kolom `is_active` (boolean, default `true`) pada tabel `account_codes` melalui file migrasi:
  - `database/migrations/2026_09_03_103000_add_is_active_to_account_codes_table.php`.
- Tombol **"Delete"** dihapus seluruhnya dari antarmuka dan digantikan dengan tombol status kontekstual:
  - **"Nonaktifkan"** (warna amber) untuk nomor rekening yang sedang aktif.
  - **"Aktifkan"** (warna hijau) untuk nomor rekening yang sedang nonaktif.
  - Dilengkapi dialog konfirmasi dinamis: *"Apakah Anda yakin ingin [menonaktifkan/mengaktifkan] nomor rekening [Nama Rekening]?"*.

### 2. Logika Controller & Proteksi Relasi
- **`AccountCodeController@destroy`**:
  Dialihkan untuk melakukan toggle status `is_active` secara aman:
  ```php
  $accountCode->update(['is_active' => !$accountCode->is_active]);
  ```
  Data nomor rekening tetap utuh di database sehingga tidak ada risiko *foreign key constraint violation* pada usulan belanja maupun pagu rekening.
- **`DetailController@create` (Operator)**:
  Pada form pembuatan rincian usulan belanja RBA operator, nomor rekening yang berstatus **nonaktif** otomatis disaring sehingga operator tidak dapat memilih rekening yang sudah dinonaktifkan:
  ```php
  $accountCodes = AccountCode::where('is_active', true)->whereNotIn('id', $lockedAccountIds)->get();
  ```
  Pada form edit usulan yang sudah ada sebelumnya, rekening yang bersangkutan tetap diizinkan tampil agar data historis tidak rusak (*backward compatible*).

### 3. Pencatatan Otomatis ke Audit Log Data (`activity_logs`)
- Trait `LogsActivity` disempurnakan untuk menangani model `AccountCode`:
  ```php
  if ($modelName === 'AccountCode') {
      $codeName = ($model->code ?? '') . ' - ' . ($model->name ?? ($model->getOriginal('name') ?? "#{$key}"));
      if ($action === 'updated' && isset($new['is_active'])) {
          $statusText = $new['is_active'] ? 'mengaktifkan' : 'menonaktifkan';
          return "{$actor} {$statusText} Nomor Rekening: \"{$codeName}\"";
      }
      return "{$actor} {$actionVerb} Nomor Rekening: \"{$codeName}\"";
  }
  ```
- Setiap aksi aktivasi maupun deaktivasi nomor rekening otomatis dicatat dengan narasi jelas, waktu, nilai sebelum/sesudah (`old_values` & `new_values`), alamat IP, dan user agent.

### 4. Integrasi DataTables Modern & Toolbar Filter Kolom
- **Tabel DataTables v2 TailwindCSS (`#account-codes-table`):**
  - Kolom Kode Rekening, Nama Rekening Belanja, Kelompok Belanja, Status, dan Aksi.
  - Pencarian bebas instan (*real-time client-side search*), paginasi (10, 25, 50, 100), dan pengurutan dinamis (*sorting*).
- **Dedicated Column Filter Toolbar:**
  - **Filter Status:** Menyaring `Semua Status`, `Active`, dan `Inactive` dengan pencocokan regex eksak (`^Active$` / `^Inactive$`).
  - **Filter Kelompok Belanja:** Dropdown menyaring rekening berdasarkan kelompok belanja induknya.
  - **Tombol Reset:** `🔄 Reset Semua Filter` untuk mengembalikan seluruh filter dan pencarian ke kondisi awal secara instan.
- **Formulir Tambah & Edit:**
  - Ditambahkan kontrol checkbox status aktif (`is_active`) pada `create.blade.php` dan `edit.blade.php`.

---

## File yang Dimodifikasi & Dibuat

1. **[NEW] [2026_09_03_103000_add_is_active_to_account_codes_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_103000_add_is_active_to_account_codes_table.php)**
   - Migrasi penambahan kolom `is_active` pada tabel `account_codes`.
2. **[MODIFY] [AccountCode.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/AccountCode.php)**
   - Menambahkan `is_active` ke `$fillable` dan `$casts`.
3. **[MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)**
   - Menambahkan penanganan narasi log khusus saat status `is_active` AccountCode diperbarui.
4. **[MODIFY] [AccountCodeController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/AccountCodeController.php)**
   - Mengubah `destroy` menjadi toggle status, menyertakan daftar kelompok belanja pada `index`, dan mendukung `is_active` pada `store`/`update`.
5. **[MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**
   - Menyaring hanya nomor rekening aktif saat operator membuat rincian belanja baru.
6. **[MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/account-codes/index.blade.php)**
   - Mengintegrasikan DataTables v2 TailwindCSS, Filter Toolbar (Status & Kelompok Belanja), kolom Status, dan tombol toggle Nonaktifkan/Aktifkan.
7. **[MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/account-codes/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/account-codes/edit.blade.php)**
   - Menambahkan checkbox kontrol status aktif (`is_active`).
8. **[NEW] [AccountCodeTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AccountCodeTest.php)**
   - Menulis pengujian otomatis untuk DataTables, filter status & kelompok, soft delete toggle, pencatatan log data, dan proteksi usulan belanja operator.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests (PHPUnit)
Seluruh **129 test cases (587 assertions)** pada aplikasi lulus 100% tanpa kegagalan:
```text
PASS  Tests\Feature\Admin\AccountCodeTest
✓ admin can view account codes index with status and filters                    1.18s  
✓ admin can deactivate account code instead of deleting                         0.03s  
✓ admin can reactivate account code                                             0.03s  
✓ account code deactivation is recorded in activity log                         0.04s  
✓ inactive account code cannot be selected by operator for new rba detail       0.23s  
✓ admin can create and update account code with status                          0.04s  

PASS  Tests\Feature\Admin\KelompokBelanjaTest (7 passed, 42 assertions)
PASS  Tests\Feature\Admin\UnitManagementTest (5 passed, 33 assertions)
PASS  Tests\Feature\Admin\AdminDashboardTest (4 passed, 45 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\ReviewTest (11 passed, 70 assertions)
PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)

Tests:    129 passed (587 assertions)
Duration: 39.28s
```

### 2. Kompilasi Frontend Assets (Bun)
Asset frontend dikompilasi menggunakan Vite melalui `bun run build`:
- `public/build/assets/app-DyG4jMwx.css` (84.10 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.23s**
