# Implementation Plan - Nonaktifkan Fitur Reset Password Tanpa Login (via Email)

Menonaktifkan fitur permohonan dan reset kata sandi tanpa login melalui email (forgot/reset password) serta menghapus tautan **"Lupa sandi?"** dari tampilan form login (`resources/views/auth/login.blade.php`).

## User Review Required

> [!IMPORTANT]
> Setelah fitur ini dinonaktifkan, pengguna tidak lagi dapat meminta link reset kata sandi via email dari halaman login. Perubahan kata sandi akun hanya dapat dilakukan saat pengguna sudah login (via menu Profile) atau melalui pengelolaan pengguna oleh Administrator / Supervisor.

## Proposed Changes

### Routes

#### [MODIFY] [auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)
- Menonaktifkan/menghapus rute publik pengajuan reset password:
  - `GET /forgot-password` (`password.request`)
  - `POST /forgot-password` (`password.email`)
  - `GET /reset-password/{token}` (`password.reset`)
  - `POST /reset-password` (`password.store`)
- Penghentian rute ini menyebabkan `Route::has('password.request')` otomatis menghasilkan `false`.

---

### Views (Frontend UI)

#### [MODIFY] [login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)
- Menghapus blok tautan **"Lupa sandi?"** pada bagian input kata sandi di form login.

#### [DELETE] [forgot-password.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/forgot-password.blade.php)
- Menghapus file tampilan form minta reset password via email.

#### [DELETE] [reset-password.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/reset-password.blade.php)
- Menghapus file tampilan form pengisian password baru via token link.

---

### Automated Tests

#### [MODIFY] [PasswordResetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/PasswordResetTest.php)
- Memperbarui pengujian pada `PasswordResetTest.php` untuk memverifikasi bahwa pengaksesan `/forgot-password` dan `/reset-password` mengembalikan status `404 Not Found`.

## Verification Plan

### Automated Tests
- Jalankan test suite autentikasi:
  `php artisan test --filter=PasswordResetTest`
- Jalankan seluruh test suite aplikasi untuk memastikan stabilitas:
  `php artisan test`

### Manual Verification
1. Buka halaman login (`/login`) dan pastikan tautan **"Lupa sandi?"** sudah tidak tampil.
2. Coba akses langsung URL `/forgot-password` atau `/reset-password/sample-token` di browser, pastikan mengembalikan respon **404 Not Found**.
3. Pastikan fungsi login normal bagi pengguna yang terdaftar.
