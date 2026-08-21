# Walkthrough - Nonaktifkan Fitur Reset Password Tanpa Login (via Email)

Penonaktifan fitur permohonan dan reset kata sandi tanpa login melalui email (forgot/reset password) serta penghapusan tautan **"Lupa sandi?"** dari tampilan form login (`resources/views/auth/login.blade.php`) telah selesai dilakukan.

## Perubahan yang Dilakukan

### 1. Routes & Autentikasi
- **[MODIFY] [auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)**
  - Menonaktifkan rute publik pengajuan reset password: `GET /forgot-password`, `POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password`.
  - Secara otomatis `Route::has('password.request')` kini mengembalikan nilai `false`.

### 2. Antarmuka Pengguna (UI)
- **[MODIFY] [login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)**
  - Menghapus tautan **"Lupa sandi?"** pada bidang input kata sandi di form login.
- **[DELETE] `resources/views/auth/forgot-password.blade.php`** & **`resources/views/auth/reset-password.blade.php`**
  - Menghapus file tampilan form reset password publik yang tidak lagi digunakan.

### 3. Pengujian Otomatis (Automated Tests)
- **[MODIFY] [PasswordResetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/PasswordResetTest.php)**
  - Mengubah pengujian publik untuk memastikan bahwa pengaksesan `/forgot-password` dan `/reset-password` mengembalikan status `404 Not Found`.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Pengujian `PasswordResetTest` dijalankan dan LULUS:

```text
PASS  Tests\Feature\Auth\PasswordResetTest
✓ reset password link screen cannot be rendered                                                                8.49s  
✓ reset password link cannot be requested                                                                      0.06s  
✓ reset password screen cannot be rendered                                                                     0.03s  

Tests:    3 passed (3 assertions)
Duration: 8.86s
```

### 2. Verifikasi Fitur
- Akses URL `/forgot-password` atau `/reset-password/...` -> Mengembalikan `404 Not Found`.
- Halaman Login (`/login`) -> Tampilan bersih tanpa link "Lupa sandi?".
- Pengelolaan kata sandi untuk user terautentikasi (via Profile) atau via Administrator/Supervisor tetap aman dan terpisah dari rute ini.
