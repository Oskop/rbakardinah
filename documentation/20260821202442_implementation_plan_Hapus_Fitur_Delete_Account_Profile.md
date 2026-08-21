# Implementation Plan - Hapus Fitur Delete Account pada Halaman Profile

Menghapus fitur hapus akun mandiri oleh pengguna (*Self Account Deletion*) dari halaman Profile (`resources/views/profile/edit.blade.php`) serta menonaktifkan rute `DELETE /profile`, karena pengelolaan dan penonaktifan akun sepenuhnya dilakukan oleh Administrator atau Supervisor.

## User Review Required

> [!IMPORTANT]
> Fitur *Delete Account* di halaman Profile dihapus secara permanen untuk mencegah pengguna menghapus akun mereka sendiri. Penonaktifan atau pengelolaan akun pengguna hanya dapat dilakukan oleh **Administrator** atau **Supervisor** melalui menu manajemen user.

## Proposed Changes

### Routes

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menonaktifkan/menghapus rute `DELETE /profile` (`profile.destroy`).

---

### Controllers

#### [MODIFY] [ProfileController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/ProfileController.php)
- Menghapus method `destroy(Request $request)` yang menangani proses penghapusan akun pengguna.

---

### Views (Frontend UI)

#### [MODIFY] [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/profile/edit.blade.php)
- Menghapus komponen `@include('profile.partials.delete-user-form')` beserta pembungkus kotaknya dari tampilan halaman Profile.

#### [DELETE] [delete-user-form.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/profile/partials/delete-user-form.blade.php)
- Menghapus file tampilan form penghapusan akun mandiri (`delete-user-form.blade.php`).

---

### Automated Tests

#### [MODIFY] [ProfileTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/ProfileTest.php)
- Memperbarui pengujian `ProfileTest` untuk memverifikasi bahwa pengaksesan `DELETE /profile` mengembalikan respon `404 Not Found` dan akun pengguna tidak dapat dihapus secara mandiri.

## Verification Plan

### Automated Tests
- Jalankan test suite `ProfileTest`:
  `php artisan test --filter=ProfileTest`
- Jalankan seluruh test suite aplikasi untuk memastikan stabilitas:
  `php artisan test`

### Manual Verification
1. Login ke aplikasi sebagai pengguna (Operator/Supervisor/Admin).
2. Buka halaman Profile (`/profile`).
3. Pastikan bagian form **"Delete Account"** sudah tidak tampil lagi di halaman Profile.
4. Uji pengaksesan langsung endpoint `DELETE /profile` di browser/API client, pastikan sistem mengembalikan respon **404 Not Found**.
