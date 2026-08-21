# Implementation Plan - Perbaikan Notifikasi Ubah Password pada Halaman Profile

Menambahkan komponen notifikasi berupa banner alert yang jelas saat pengguna berhasil memperbarui kata sandi, serta banner peringatan kesalahan (error banner) apabila terdapat kegagalan validasi pada form ubah kata sandi di halaman Profile.

## User Review Required

> [!IMPORTANT]
> Sebelumnya, saat terjadi kesalahan validasi kata sandi, pesan error hanya tampil kecil di bawah masing-masing input tanpa ada banner notifikasi utama di bagian atas form. Selain itu, notifikasi berhasil hanya berupa teks kecil `Saved.` yang menghilang dengan cepat.
> Dengan perbaikan ini, pengguna akan mendapatkan:
> 1. **Banner Berhasil (Hijau)**: Menampilkan pesan jelas "Kata sandi Anda telah berhasil diperbarui." saat pembaruan sukses.
> 2. **Banner Error (Merah)**: Menampilkan pesan peringatan utama beserta rincian kesalahan pengisian form apabila validasi gagal.

## Proposed Changes

### Controller

#### [MODIFY] [PasswordController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/PasswordController.php)
- Menambahkan flash session `with('success', 'Kata sandi Anda telah berhasil diperbarui.')` saat kata sandi berhasil diperbarui.

---

### Views (Frontend UI)

#### [MODIFY] [update-password-form.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/profile/partials/update-password-form.blade.php)
- Menambahkan komponen **Banner Alert Sukses (Emerald/Green)** di bagian atas form saat `session('status') === 'password-updated'` atau `session('success')`.
- Menambahkan komponen **Banner Alert Error (Red)** di bagian atas form saat `$errors->updatePassword->any()` yang memuat judul peringatan dan rincian daftar kesalahan validasi.
- Menyediakan tombol *dismiss/close* (`x-data="{ show: true }"`) pada banner agar pengguna dapat menutup notifikasi secara interaktif.

---

### Automated Tests

#### [MODIFY] [PasswordUpdateTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/PasswordUpdateTest.php)
- Memastikan pengujian `PasswordUpdateTest` memverifikasi keberadaan status session dan error bag yang tepat saat pembaruan kata sandi.

## Verification Plan

### Automated Tests
- Jalankan test suite `PasswordUpdateTest`:
  `php artisan test --filter=PasswordUpdateTest`
- Jalankan seluruh test suite aplikasi untuk memastikan tidak ada hambatan:
  `php artisan test`

### Manual Verification
1. Login ke aplikasi sebagai user (Operator/Supervisor/Admin).
2. Buka halaman Profile (`/profile`).
3. **Uji Kasus Berhasil**: Masukkan password lama yang benar dan password baru yang valid, klik Simpan. Pastikan **Banner Notifikasi Berhasil (Hijau)** tampil dengan jelas.
4. **Uji Kasus Error**: Masukkan password lama yang salah atau password konfirmasi yang tidak cocok, klik Simpan. Pastikan **Banner Notifikasi Error (Merah)** tampil di bagian atas form dengan rincian kesalahan yang dialami.
