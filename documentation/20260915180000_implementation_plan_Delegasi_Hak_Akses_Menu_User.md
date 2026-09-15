# Implementation Plan: Delegasi Hak Akses Menu Tambahan bagi User oleh Administrator

Dokumen ini menjelaskan rancangan teknis penambahan fitur **Delegasi Hak Akses Menu** pada SIPAKAR RSUD Kardinah. Fitur ini memungkinkan Administrator untuk memberikan wewenang akses menu tertentu kepada pengguna (misalnya: mendelegasikan pengelolaan **Master Data Barang BMD** kepada akun Operator tertentu) secara terstruktur, aman, dan dapat diperluas untuk menu lainnya di masa mendatang.

---

## 1. Latar Belakang & Kebutuhan Pengguna

### Latar Belakang
- Saat ini, hak akses menu dalam sistem didasarkan pada role statis (`Administrator`, `Supervisor`, `Operator`).
- Menu **Master Barang BMD (Permendagri No. 108 Tahun 2016)** sebelumnya hanya dapat diakses oleh Administrator (`role:Administrator`).
- Dalam operasional rumah sakit sehari-hari, Administrator perlu mendelegasikan tugas teknis penginputan dan pemeliharaan katalog barang kepada staf/operator tertentu tanpa harus mengubah role staf tersebut menjadi Administrator.

### Kebutuhan Pengguna
1. Administrator dapat memilih dan menentukan hak akses menu tambahan per pengguna saat membuat (*create*) atau mengubah (*edit*) data pengguna.
2. Kasus utama saat ini: Administrator dapat mendelegasikan menu **Master Barang BMD** kepada akun Operator tertentu.
3. Arsitektur harus dirancang *extensible* (mudah diperluas untuk menu delegasi lainnya di masa depan, seperti Pengumuman, Indikator Kinerja, dsb.).
4. Tampilan navigasi (*desktop* & *mobile*) bagi pengguna yang didelegasikan otomatis menampilkan menu yang diberikan.
5. Akses rute dilindungi oleh middleware otorisasi yang ketat (HTTP `403 Forbidden` bagi pengguna yang tidak memiliki izin).
6. Seluruh aktivitas penginputan barang oleh operator yang didelegasikan tetap terekam dalam kolom audit (`created_by`, `updated_by`) dan log aktivitas (`activity_logs`).

---

## 2. Rencana Arsitektur & Mekanisme Delegasi

### Struktur Kolom & Model
1. **Kolom Database (`users.menu_permissions`)**:
   - Menambahkan kolom `menu_permissions` bertipe `JSON` (*nullable*) pada tabel `users`.
   - Di-cast ke array pada model `User`: `'menu_permissions' => 'array'`.
2. **Registry Menu Delegasi (`User::DELEGATABLE_MENUS`)**:
   - Dibuat konstanta terpusat pada model `User` yang memetakan daftar menu yang dapat didelegasikan:
     - `master_barangs`: Master Barang BMD (Permendagri 108)
     - *(Siap diperluas untuk menu lain seperti `kelompok_belanja`, `account_codes`, `announcements`, `performance_indicators`)*
3. **Helper Methods pada `User`**:
   - `hasMenuPermission(string $menuKey): bool`:
     - Jika role `Administrator` -> selalu return `true`.
     - Jika role lain -> periksa apakah `$menuKey` ada di dalam array `$this->menu_permissions ?? []`.
   - `getDelegatedMenus(): array`: Mengembalikan daftar detail menu yang diizinkan untuk pengguna tersebut.

### Middleware Keamanan (`CheckMenuPermission`)
- Dibuat middleware baru `App\Http\Middleware\CheckMenuPermission`:
  - Menerima parameter nama menu, misalnya `menu_permission:master_barangs`.
  - Memeriksa apakah pengguna login memiliki izin via `$request->user()->hasMenuPermission($menuKey)`.
  - Jika ya: Lanjutkan request (`$next($request)`).
  - Jika tidak: Abort dengan kode status HTTP `403 Forbidden`.
- Didaftarkan alias `'menu_permission'` pada `bootstrap/app.php`.

### Routing
- Rute `admin/master-barangs` dipindahkan dari grup `role:Administrator` ke grup `menu_permission:master_barangs`:
  ```php
  Route::middleware(['auth', 'menu_permission:master_barangs'])->prefix('admin')->name('admin.')->group(function () {
      Route::resource('master-barangs', \App\Http\Controllers\Admin\MasterBarangController::class);
  });
  ```
  *Catatan: Nama rute dan URL tetap `admin.master-barangs.*` sehingga tidak merusak link, redirect, atau controller yang sudah berjalan.*

---

## User Review Required

> [!NOTE]
> - **Independensi Role**: Akun Operator yang diberi izin `master_barangs` tetap berstatus role `Operator` untuk urusan pengusulan dan permohonan RKBMD, namun mendapatkan tambahan akses mengelola katalog Master Barang.
> - **Akuntabilitas**: Setiap kali operator membuat atau mengedit barang, kolom `created_by` dan `updated_by` mencatat ID operator tersebut, sehingga terlacak siapa staf yang menginput.

---

## Proposed Changes

### Database & Migrations

#### [NEW] [2026_09_15_180000_add_menu_permissions_to_users_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_15_180000_add_menu_permissions_to_users_table.php)
- Menambahkan kolom `menu_permissions` bertipe `JSON` (*nullable*) setelah kolom `can_propose`.

---

### Eloquent Model

#### [MODIFY] [User.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/User.php)
- Daftarkan `menu_permissions` pada `$fillable` dan `$casts` (sebagai `array`).
- Definisikan konstanta `DELEGATABLE_MENUS`.
- Tambahkan method `hasMenuPermission(string $menuKey): bool`.
- Tambahkan method `getDelegatedMenus(): array`.

---

### Middleware & Application Bootstrap

#### [NEW] [CheckMenuPermission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Middleware/CheckMenuPermission.php)
- Middleware pengecekan izin menu delegasi berdasarkan `$request->user()->hasMenuPermission($menuKey)`.

#### [MODIFY] [bootstrap/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/bootstrap/app.php)
- Daftarkan alias `'menu_permission' => \App\Http\Middleware\CheckMenuPermission::class`.

---

### Routes & Controllers

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Terapkan middleware `menu_permission:master_barangs` pada resource route `master-barangs`.

#### [MODIFY] [UserController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/UserController.php)
- Validasi input `menu_permissions` pada method `store()` dan `update()`.
- Simpan pilihan menu delegasi ke dalam database saat form pengguna dikirimkan.

---

### Blade Views & UI

#### [MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/edit.blade.php)
- Tambahkan kartu konfigurasi **"🛡️ Delegasi Hak Akses Menu Tambahan"** yang berisi daftar checkbox interaktif untuk menu yang dapat didelegasikan (`DELEGATABLE_MENUS`).
- Checkbox otomatis tercentang sesuai data permissions yang tersimpan.

#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php)
- Tampilkan badge indikator menu delegasi pada baris tabel pengguna (misal: `📦 Master Barang`) agar admin dapat melihat delegasi wewenang secara transparan.

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Pada bagian navigasi Operator (dan Supervisor), tampilkan link menu delegasi (`📦 Master Barang BMD`) apabila user memiliki izin `hasMenuPermission('master_barangs')`, baik pada tampilan desktop maupun responsive mobile.

---

### Automated Tests

#### [MODIFY] [RkbmdTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdTest.php) & [UserManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UserManagementTest.php)
- Tambahkan test cases:
  1. `test_admin_can_delegate_master_barang_menu_to_operator`: Admin dapat memberikan izin `master_barangs` ke operator melalui form edit user.
  2. `test_delegated_operator_can_access_and_manage_master_barangs`: Operator dengan izin delegasi dapat membuka index, create, dan store master barang.
  3. `test_operator_without_delegation_cannot_access_master_barangs_and_gets_403`: Operator tanpa izin delegasi ditolak saat membuka `/admin/master-barangs` (HTTP 403).
  4. `test_admin_always_has_access_to_master_barangs`: Administrator tetap memiliki akses penuh tanpa perlu checkbox khusus.

---

## Verification Plan

### Automated Tests
- Menjalankan feature tests delegasi menu:
  ```powershell
  php artisan test tests/Feature/Operator/RkbmdTest.php
  ```
- Menjalankan seluruh test suite aplikasi (zero-regression):
  ```powershell
  php artisan test
  ```

### Manual Verification
1. Login sebagai Administrator, buka menu **Users & Pegawai** (`/admin/users`).
2. Edit salah satu akun Operator:
   - Centang checkbox **📦 Master Barang BMD**.
   - Simpan perubahan.
   - Pastikan badge `📦 Master Barang BMD` muncul pada tabel pengguna.
3. Logout dan Login sebagai Operator tersebut:
   - Pastikan menu **📦 Master Barang BMD** muncul di navbar atas dan menu mobile.
   - Buka menu tersebut, pastikan halaman terbuka mulus tanpa error.
   - Tambah satu master barang baru, pastikan berhasil disimpan.
4. Login sebagai Operator lain yang TIDAK diberi izin:
   - Pastikan menu **📦 Master Barang BMD** TIDAK muncul di navbar.
   - Akses manual `/admin/master-barangs`, pastikan muncul halaman `403 Forbidden`.
