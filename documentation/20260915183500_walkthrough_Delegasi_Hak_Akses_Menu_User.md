# Walkthrough: Fitur Delegasi Hak Akses Menu Pengguna (Master Barang BMD)

## Ringkasan Fitur
Sistem kini telah dilengkapi dengan fitur **Delegasi Hak Akses Menu**. Administrator dapat memberikan wewenang kepada pengguna tertentu (khususnya level akun **Operator**) untuk mengakses dan mengelola menu spesifik yang sebelumnya hanya dapat diakses oleh Administrator, dengan studi kasus utama: **Pengelolaan Master Barang BMD (Permendagri 108)**.

Arsitektur perizinan ini dirancang secara **non-invasif**, di mana struktur role inti sistem (`Administrator`, `Supervisor`, `Operator`) tetap utuh tanpa risiko eskalasi hak akses (*privilege escalation*). Akun Operator yang didelegasikan menu Master Barang tetap dilarang keras mengakses menu administratif lainnya seperti Kelola Pengguna, Unit/Sub Unit, Pagu, Log Aktivitas, maupun Dashboard Admin.

---

## Komponen yang Diimplementasikan

### 1. Migrasi Database
- **File**: [`database/migrations/2026_09_15_180000_add_menu_permissions_to_users_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_15_180000_add_menu_permissions_to_users_table.php)
- Menambahkan kolom `menu_permissions` bertipe `json` (nullable) pada tabel `users`.

### 2. Eloquent Model Pengguna
- **File**: [`app/Models/User.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/User.php)
- Menambahkan kolom `menu_permissions` ke array `$fillable` dan casting array `'menu_permissions' => 'array'`.
- Mendefinisikan konstanta registri menu yang dapat didelegasikan:
  ```php
  public const DELEGATABLE_MENUS = [
      'master_barangs' => [
          'label' => 'Master Barang BMD',
          'description' => 'Akses kelola katalog dan kodefikasi Master Barang BMD (Permendagri 108).',
          'route' => 'admin.master-barangs.index',
          'icon' => 'cube',
      ],
  ];
  ```
- Menyediakan method pembantu:
  - `hasMenuPermission(string $menuKey): bool`: Mengembalikan `true` secara otomatis bagi Administrator, atau memeriksa apakah `$menuKey` terdaftar dalam array `menu_permissions` user.
  - `getDelegatedMenus(): array`: Mengembalikan metadata menu delegasi aktif milik pengguna untuk kebutuhan rendering navigasi.

### 3. Middleware Otorisasi Granular
- **File**: [`app/Http/Middleware/CheckMenuPermission.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Middleware/CheckMenuPermission.php)
- Memeriksa kewenangan menu pengguna melalui `$request->user()->hasMenuPermission($menuKey)`. Mengembalikan respons HTTP `403 Forbidden` jika pengguna tidak memiliki hak akses.
- **File**: [`bootstrap/app.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/bootstrap/app.php)
- Mendaftarkan alias middleware `'menu_permission' => \App\Http\Middleware\CheckMenuPermission::class`.

### 4. Penyesuaian Rute
- **File**: [`routes/web.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Memindahkan rute resource `master-barangs` ke grup rute tersendiri dengan proteksi middleware `['auth', 'menu_permission:master_barangs']`:
  ```php
  Route::middleware(['auth', 'menu_permission:master_barangs'])->prefix('admin')->name('admin.')->group(function () {
      Route::resource('master-barangs', \App\Http\Controllers\Admin\MasterBarangController::class);
  });
  ```

### 5. Manajemen Pengguna Admin (Controller & Blade Views)
- **File**: [`app/Http/Controllers/Admin/UserController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/UserController.php)
  - Memvalidasi input `menu_permissions` pada `store()` dan `update()`.
  - Jika role adalah `Administrator`, `menu_permissions` otomatis diset `null` (karena Administrator memiliki hak akses universal).
  - Menyimpan pilihan perizinan delegasi menu bagi role non-admin.
- **File Form Tambah & Edit Pengguna**:
  - [`resources/views/admin/users/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/create.blade.php)
  - [`resources/views/admin/users/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/edit.blade.php)
  - Ditambahkan kartu konfigurasi modern **"🛡️ Delegasi Hak Akses Menu Tambahan"** lengkap dengan checkbox interaktif, icon, dan deskripsi fungsi. Kartu otomatis disembunyikan menggunakan Alpine.js saat role Administrator dipilih.
- **File Daftar Pengguna**:
  - [`resources/views/admin/users/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php)
  - Ditambahkan badge indikator menu delegasi (contoh: `📦 Master Barang BMD`) di bawah label role pengguna pada tabel.

### 6. Navigasi Antarmuka (Desktop & Mobile)
- **File**: [`resources/views/layouts/navigation.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Ditambahkan perulangan dinamis menu delegasi untuk pengguna non-admin pada navbar desktop dan drawer mobile:
  ```blade
  @if(Auth::user()->role !== 'Administrator')
      @foreach(Auth::user()->getDelegatedMenus() as $dKey => $dMenu)
          <x-nav-link :href="route($dMenu['route'])" :active="request()->routeIs(str_replace('.index', '.*', $dMenu['route']))">
              {{ $dMenu['label'] }}
          </x-nav-link>
      @endforeach
  @endif
  ```

---

## Verifikasi & Hasil Pengujian

### 1. Pengujian Otomatis Fitur Delegasi Menu (`Tests\Feature\Operator\RkbmdTest`)
Pengujian mencakup:
- Admin dapat mendelegasikan perizinan menu `master_barangs` ke Operator melalui endpoint update user.
- Operator dengan izin delegasi dapat membuka halaman indeks, formulir tambah, dan menyimpan master barang baru (`admin.master-barangs.*`).
- Operator tanpa izin delegasi menerima respons `403 Forbidden` saat mencoba mengakses `admin.master-barangs.*`.
- Operator dengan izin delegasi Master Barang tetap diblokir (`403 Forbidden`) saat mencoba mengakses menu admin lainnya (`admin.users.index`, `admin.units.index`, `admin.dashboard`).

Hasil eksekusi:
```text
   PASS  Tests\Feature\Operator\RkbmdTest
  ✓ operator can access rkbmd index and create page
  ✓ operator can submit rkbmd with items and pdf memo
  ✓ audit columns and activity logs are automatically populated
  ✓ target operator can reply with status and free text
  ✓ target operator can forward rkbmd to another proposer and logs history
  ✓ viewer operator cannot reply or forward rkbmd
  ✓ admin can manage master barang permendagri 108
  ✓ admin master barangs index handles empty table without breaking datatables
  ✓ target operator can edit reply and logs history
  ✓ viewer and unauthorized operator cannot edit reply
  ✓ admin can delegate master barangs menu permission to operator
  ✓ operator with delegated permission can manage master barangs
  ✓ operator without delegated permission cannot access master barangs
  ✓ operator with delegated permission cannot access other admin menus

  Tests:    14 passed (67 assertions)
  Duration: 2.75s
```

### 2. Pengujian Regresi Menyeluruh Sistem (Full Test Suite)
Memastikan seluruh otorisasi role eksisting (Admin, Supervisor, Operator pengusul/pemohon) dan fitur RBA/RKBMD lainnya tetap berfungsi normal tanpa kendala:
```text
Tests:    231 passed (1136 assertions)
Duration: 61.10s
Status:   100% HIJAU (PASSED)
```

---

## Panduan Penggunaan oleh Administrator

1. Masuk (*login*) sebagai **Administrator**.
2. Buka menu **Kelola Pengguna** (`/admin/users`).
3. Klik tombol **Edit** (ikon pensil) pada baris akun Operator yang ingin ditugaskan.
4. Pada bagian bawah form, temukan kartu **"🛡️ Delegasi Hak Akses Menu Tambahan"**.
5. Centang opsi **Master Barang BMD**.
6. Klik tombol **Simpan Perubahan**.
7. Saat Operator yang bersangkutan login, menu **Master Barang BMD** akan langsung muncul pada bilah navigasi atas (navbar), dan operator dapat menginput, memperbarui, atau menghapus katalog barang BMD sesuai penugasan.
