# Implementation Plan - Perbaikan Bug Form Login Ganda (SSO & Lokal) Muncul Bersamaan

Memperbaiki *bug* pada halaman login di mana kedua formulir login (Metode SSO SIMRS dan Metode Akun Lokal SIPAKAR) tampil secara bersamaan bertumpuk di layar.

---

## User Review Required

> [!IMPORTANT]
> **Akar Masalah (Root Cause):**
> 1. **Ketiadaan Bundle JS/Alpine pada Layout Tamu (`guest.blade.php`):**
>    - File `resources/views/layouts/guest.blade.php` belum memuat `@vite(['resources/css/app.css', 'resources/js/app.js'])` yang membungkus Alpine.js.
>    - Akibatnya, direktif reaktivitas `x-data`, `x-show`, dan `@click` pada form login tidak dieksekusi oleh browser, sehingga kedua tag `<form>` dirender sekaligus sebagai elemen HTML statis.
> 2. **Ketiadaan `x-cloak` & Fallback `display: none`:**
>    - Form tidak memiliki proteksi *Flash of Unstyled Content (FOUC)* sehingga sebelum JavaScript aktif, kedua form terlihat bersamaan.

---

## Proposed Changes

### 1. Layout Tamu (`resources/views/layouts/guest.blade.php`)

#### [MODIFY] [guest.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/guest.blade.php)
- Menyematkan `@vite(['resources/css/app.css', 'resources/js/app.js'])` di dalam `<head>` layout tamu agar Alpine.js dan script aplikasi ter-load sempurna.
- Menambahkan aturan CSS global `[x-cloak] { display: none !important; }` untuk mencegah *flicker* form sebelum Alpine terhidrasi.

---

### 2. Form Login (`resources/views/auth/login.blade.php`)

#### [MODIFY] [login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)
- Menambahkan atribut `x-cloak` pada masing-masing form tab.
- Menambahkan inline fallback `style="display: none;"` secara kondisional berdasarkan nilai server-side `$initialTab` sehingga browser merender hanya 1 form aktif bahkan sebelum JS berjalan:
  - Form SIMRS: `x-show="tab === 'simrs'" :style="tab === 'simrs' ? '' : 'display: none;'"` (default tampil jika SSO aktif).
  - Form Lokal: `x-show="tab === 'local'" :style="tab === 'local' ? '' : 'display: none;'"` (default tersembunyi jika SSO aktif, kecuali jika terdapat error login lokal).

---

### 3. Pengujian Otomatis (`tests/Feature/Auth/SimrsSsoTest.php`)

#### [MODIFY] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)
- Menambahkan pengujian render tampilan login:
  - `test_login_screen_loads_with_vite_assets_and_tabs_properly`: Memastikan halaman `/login` merender layout dengan aset Vite, atribut x-cloak, dan tab SSO serta lokal secara terstruktur.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite autentikasi:
  `php artisan test --filter=SimrsSsoTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Buka halaman `/login` di browser:
   - Verifikasi hanya **Tab 1: Pegawai SIMRS (SSO)** yang aktif dan form input NIP SIMRS yang terlihat. Form login lokal harus tersembunyi.
   - Klik tab **"🔐 Akun Lokal SIPAKAR"**:
     - Verifikasi form berganti seketika ke form Alamat Email SIPAKAR dan Kata Sandi. Form SIMRS tersembunyi.
   - Klik kembali tab **"🏥 Pegawai SIMRS (SSO)"**:
     - Verifikasi form kembali ke kredensial SIMRS secara mulus tanpa *flicker* atau tampilan bertumpuk.
