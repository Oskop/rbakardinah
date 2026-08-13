# Implementation Plan - Hapus Fitur Daftar Akun secara Mandiri

Menghapus fitur registrasi/pendaftaran akun secara mandiri (self-registration) oleh pengguna dari halaman landing (`welcome.blade.php`) dan halaman login (`auth/login.blade.php`), serta menonaktifkan rute pendaftaran publik sehingga pendaftaran akun baru hanya dapat dilakukan oleh **Administrator** dan **Supervisor** melalui panel manajemen pengguna.

## User Review Required

> [!IMPORTANT]
> Setelah fitur registrasi mandiri ini dihapus, calon pengguna baru tidak lagi dapat mendaftar sendiri. Pembuatan akun baru sepenuhnya dilakukan oleh Administrator melalui menu `Admin -> Kelola User` atau Supervisor melalui menu `Supervisor -> Kelola User`.

## Proposed Changes

### Routes

#### [MODIFY] [auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)
- Hapus/nonaktifkan rute `GET /register` dan `POST /register` yang mengarah ke `RegisteredUserController`.

---

### Views (Frontend UI)

#### [MODIFY] [welcome.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/welcome.blade.php)
- Hapus tombol **"Daftar Akun"** (`route('register')`) dari bilah navigasi utama halaman landing/dashboard depan.

#### [MODIFY] [login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)
- Hapus tautan dan teks **"Belum punya akun? Daftar di sini"** dari bagian bawah form login.

#### [DELETE] [register.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/register.blade.php)
- Hapus file tampilan registrasi mandiri `resources/views/auth/register.blade.php`.

---

### Automated Tests

#### [MODIFY] [RegistrationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/RegistrationTest.php)
- Perbarui pengujian pada `RegistrationTest.php` untuk memastikan akses ke `/register` mengembalikan status `404 Not Found`.

## Verification Plan

### Automated Tests
- Jalankan test suite auth:
  `php artisan test --filter=RegistrationTest`
- Jalankan seluruh test suite aplikasi untuk memastikan tidak ada breakdown:
  `php artisan test`

### Manual Verification
1. Buka halaman depan (`/`) dan pastikan tombol **"Daftar Akun"** sudah tidak tampil.
2. Buka halaman login (`/login`) dan pastikan tautan **"Daftar di sini"** sudah tidak tampil.
3. Coba akses langsung URL `/register` di browser, pastikan sistem menampilkan halaman **404 Not Found**.
4. Login sebagai Admin / Supervisor dan pastikan menu tambah user (`Admin -> Kelola User -> Tambah User` dan `Supervisor -> Kelola User -> Tambah User`) tetap berfungsi normal.
