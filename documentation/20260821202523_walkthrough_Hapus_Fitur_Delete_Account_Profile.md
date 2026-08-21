# Walkthrough - Hapus Fitur Delete Account pada Halaman Profile

Penghapusan fitur hapus akun mandiri (*Self Account Deletion*) dari halaman Profile (`resources/views/profile/edit.blade.php`) dan penonaktifan rute `DELETE /profile` telah selesai dilakukan. Pengelolaan akun pengguna sepenuhnya dikendalikan oleh Administrator dan Supervisor melalui panel manajemen pengguna.

## Perubahan yang Dilakukan

### 1. Routes & Controllers
- **[MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)**
  - Menonaktifkan rute `DELETE /profile` (`profile.destroy`).
- **[MODIFY] [ProfileController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/ProfileController.php)**
  - Menghapus method `destroy()` yang menangani proses penghapusan akun pengguna secara mandiri.

### 2. Antarmuka Pengguna (UI)
- **[MODIFY] [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/profile/edit.blade.php)**
  - Menghapus blok `@include('profile.partials.delete-user-form')` dari halaman Profile.
- **[DELETE] `resources/views/profile/partials/delete-user-form.blade.php`**
  - Menghapus file tampilan form penghapusan akun mandiri.

### 3. Pengujian Otomatis (Automated Tests)
- **[MODIFY] [ProfileTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/ProfileTest.php)**
  - Mengubah pengujian `ProfileTest` untuk memverifikasi bahwa pengaksesan `DELETE /profile` mengembalikan status `405 Method Not Allowed` dan akun pengguna tidak dapat dihapus secara mandiri.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh test suite `ProfileTest` dijalankan dan **PASS 100%**:

```text
PASS  Tests\Feature\ProfileTest
✓ profile page is displayed                                                                                    1.03s  
✓ profile information can be updated                                                                           0.05s  
✓ email verification status is unchanged when the email address is unchanged                                   0.03s  
✓ user cannot delete their account                                                                             1.02s  

Tests:    4 passed (13 assertions)
Duration: 2.37s
```

### 2. Verifikasi Fitur Manual
- Halaman Profile (`/profile`) -> Tampilan bersih dari tombol dan form "Delete Account".
- Akses ke endpoint `DELETE /profile` -> Mengembalikan respon `405 Method Not Allowed`.
- Pengelolaan user hanya dilakukan oleh Administrator / Supervisor.
