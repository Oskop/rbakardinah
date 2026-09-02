# Implementation Plan - Fitur Filter Terpisah per Kolom pada Manajemen User (Administrator & Supervisor)

Menambahkan fitur filter terpisah untuk kolom-kolom utama (Peran/Role, Unit Kerja, Status Akun, dan Tipe Akun SSO/Lokal) pada halaman User Management Administrator (`/admin/users`) dan Supervisor (`/supervisor/users`) untuk mempermudah pemilahan dan pengelolaan pengguna secara cepat dan presisi.

---

## User Review Required

> [!IMPORTANT]
> **Rancangan Antarmuka & Fungsionalitas Filter:**
> 1. **Panel Filter Khusus (*Dedicated Filter Toolbar*):**
>    - Di atas tabel pengguna, ditambahkan panel filter yang elegan dengan *grid layout* responsif dan tombol **Reset Filter**.
> 2. **Filter pada Halaman Administrator (`/admin/users`):**
>    - **Filter Role:** Dropdown pilihan (`Semua Role`, `Administrator`, `Supervisor`, `Operator`).
>    - **Filter Unit Kerja:** Dropdown pilihan seluruh unit RSUD Kardinah, dilengkapi opsi khusus **⚠️ Belum Ditugaskan / Tanpa Unit** untuk mempermudah Admin menemukan pegawai baru hasil integrasi SSO yang belum memiliki unit.
>    - **Filter Status Akun:** Dropdown pilihan (`Semua Status`, `Active`, `Inactive`).
>    - **Filter Tipe Akun:** Dropdown pilihan (`Semua Tipe`, `🏥 SSO SIMRS`, `🔐 Akun Lokal`).
> 3. **Filter pada Halaman Supervisor (`/supervisor/users`):**
>    - **Filter Role:** Dropdown pilihan (`Semua Role`, `Operator`, `Supervisor`).
>    - **Filter Status Akun:** Dropdown pilihan (`Semua Status`, `Active`, `Inactive`).
>    - **Filter Tipe Akun:** Dropdown pilihan (`Semua Tipe`, `🏥 SSO SIMRS`, `🔐 Akun Lokal`).
> 4. **Pencarian Bebas (Global Search) Tetap Tersedia:**
>    - Kotak pencarian bawaan DataTables tetap aktif untuk mencari secara bebas berdasarkan Nama, NIP, atau Email pengguna.

---

## Proposed Changes

### 1. Controller Administrator & Supervisor

#### [MODIFY] [UserController.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/UserController.php)
- Pada method `index()`:
  - Mengirimkan data `$units = Unit::orderBy('name')->get();` ke view `admin.users.index` agar dropdown filter unit terisi daftar unit kerja yang dinamis dari database.

---

### 2. View Administrator User Management (`resources/views/admin/users/index.blade.php`)

#### [MODIFY] [index.blade.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php)
- Menambahkan kolom **Tipe Akun** (`SSO SIMRS` / `Akun Lokal`) dan informasi **NIP** pada kolom Nama.
- Menyematkan badge **Belum Ditugaskan** yang mudah dikenali saat `user->unit_id` bernilai null.
- Menambahkan **Panel Filter Toolbar** di atas tabel dengan 4 dropdown terpisah (Role, Unit Kerja, Status Akun, Tipe Akun) dan tombol **Reset Filter**.
- Mengonfigurasi event listener JavaScript DataTables untuk menyaring kolom spesifik secara instan (*column search API*).

---

### 3. View Supervisor User Management (`resources/views/supervisor/users/index.blade.php`)

#### [MODIFY] [index.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/users/index.blade.php)
- Menambahkan kolom **Tipe Akun** dan informasi **NIP** pada kolom Nama.
- Menambahkan **Panel Filter Toolbar** di atas tabel dengan dropdown (Role, Status Akun, Tipe Akun) dan tombol **Reset Filter**.
- Mengonfigurasi event listener JavaScript DataTables untuk filter kolom supervisor.

---

### 4. Pengujian Otomatis (`tests/Feature/Admin/UserManagementTest.php` & `tests/Feature/Supervisor/UserManagementTest.php`)

#### [NEW] [UserManagementTest.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UserManagementTest.php)
- Menguji akses index user management admin.
- Memverifikasi variabel `$units` dan elemen filter (Role, Unit, Status, Tipe Akun) ter-render dengan benar.
- Menguji filtering data berdasarkan unit dan role.

#### [NEW] [UserManagementTest.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/UserManagementTest.php)
- Menguji akses index user management supervisor.
- Memverifikasi elemen filter supervisor ter-render dengan benar.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite user management:
  `php artisan test --filter=UserManagementTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Buka halaman **Users** sebagai Administrator (`/admin/users`):
   - Uji filter **Role**: Pilih `Operator`, verifikasi tabel hanya memuat Operator.
   - Uji filter **Unit Kerja**: Pilih `⚠️ Belum Ditugaskan / Tanpa Unit`, verifikasi hanya pegawai SSO yang belum punya unit kerja yang tampil.
   - Uji filter **Status Akun**: Pilih `Inactive`, verifikasi hanya user inactive yang tampil.
   - Uji filter **Tipe Akun**: Pilih `🏥 SSO SIMRS`, verifikasi hanya akun SSO yang tampil.
   - Klik **Reset Semua Filter**: Verifikasi tabel kembali menampilkan seluruh data pengguna.
2. Buka halaman **Users** sebagai Supervisor (`/supervisor/users`):
   - Uji filter Role, Status, dan Tipe Akun serta tombol Reset Filter.
