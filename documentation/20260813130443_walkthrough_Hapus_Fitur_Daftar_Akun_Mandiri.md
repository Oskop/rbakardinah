# Walkthrough - Hapus Fitur Daftar Akun secara Mandiri

Penghapusan fitur registrasi/pendaftaran akun mandiri oleh pengguna (self-registration) dari halaman depan (`welcome.blade.php`) dan halaman login (`auth/login.blade.php`) telah selesai dilakukan. Sekarang, akun pengguna baru hanya dapat didaftarkan melalui Administrator (`Admin -> Kelola User`) atau Supervisor (`Supervisor -> Kelola User`).

## Perubahan yang Dilakukan

### 1. Routes & Autentikasi
- **[MODIFY] [auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)**
  - Menonaktifkan rute `GET /register` dan `POST /register`.
  - Secara otomatis, fungsi `Route::has('register')` kini mengembalikan nilai `false`.

### 2. Antarmuka Pengguna (UI)
- **[MODIFY] [welcome.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/welcome.blade.php)**
  - Menghapus tombol **"Daftar Akun"** dari navigasi utama di bagian atas halaman landing.
- **[MODIFY] [login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)**
  - Menghapus blok tautan **"Belum punya akun? Daftar di sini"** dari bagian bawah form login.
- **[DELETE] `resources/views/auth/register.blade.php`**
  - Menghapus file tampilan form registrasi mandiri publik.

### 3. Pengujian Otomatis (Automated Tests)
- **[MODIFY] [RegistrationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/RegistrationTest.php)**
  - Mengubah pengujian registrasi publik untuk memverifikasi bahwa pengaksesan `GET /register` dan `POST /register` mengembalikan kode status `404 Not Found`.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh test suite aplikasi dijalankan dan LULUS (59 passed, 208 assertions):
- `RegistrationTest` (`test_registration_screen_cannot_be_rendered` & `test_new_users_cannot_self_register`): **PASS**
- Overall test suite: **59 PASSED**

```text
PASS  Tests\Feature\Auth\RegistrationTest
✓ registration screen cannot be rendered                                                                       0.94s  
✓ new users cannot self register                                                                               0.03s  

Tests:    59 passed (208 assertions)
Duration: 26.97s
```

### 2. Verifikasi Fitur
- Access URL `/register` -> Mengembalikan `404 Not Found`.
- Halaman Login (`/login`) -> Hapus tautan registrasi mandiri, hanya menampilkan form login.
- Halaman Landing (`/`) -> Menampilkan tombol "Masuk" tanpa pilihan "Daftar Akun".
- Pendaftaran oleh Administrator / Supervisor (`Admin -> Kelola User -> Tambah User` dan `Supervisor -> Kelola User -> Tambah User`) tetap berfungsi secara normal.
