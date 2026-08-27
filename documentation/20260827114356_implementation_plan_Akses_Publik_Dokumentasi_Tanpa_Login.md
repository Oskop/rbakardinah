# Implementation Plan - Akses Publik Menu Dokumentasi Tanpa Login

Membuka akses modul **Dokumentasi & Manual Book (Buku Panduan)** agar dapat diakses secara publik dan langsung oleh siapapun (termasuk pengguna yang belum login / tamu), sekaligus menyediakan tautan akses dokumentasi pada halaman awal (*landing page*) dan halaman login.

---

## User Review Required

> [!IMPORTANT]
> **Kebijakan Akses Publik & Keamanan:**
> 1. **Akses Pembaca Dokumentasi (Publik / Guest):**
>    - Halaman pembaca dokumentasi (`/documentation`, `/documentation/{version}/{slug}`, preview PDF, dan unduh PDF) dapat diakses **tanpa harus login** (*open public access*).
>    - Pengunjung publik yang belum login akan melihat navigasi yang ramah tamu (*guest navbar*) dengan tombol **Masuk (Login)** di sudut kanan atas.
> 2. **Keamanan Panel Pengelolaan Administrator (Admin CMS):**
>    - Seluruh rute pengelolaan dokumentasi (`/admin/documentation/*`) tetap **terproteksi ketat** di bawah middleware `['auth', 'role:Administrator']`. Pengunjung publik atau role non-admin yang mencoba mengakses akan tetap ditolak (403/Redirect Login).
> 3. **Integrasi Akses di Halaman Utama:**
>    - Menambahkan tombol akses langsung ke Buku Panduan pada bilah navigasi dan hero section halaman depan (`welcome.blade.php`).
>    - Menambahkan tautan bantuan dokumentasi pada form login (`auth/login.blade.php`).

---

## Proposed Changes

### 1. Routing (`routes/web.php`)

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menghapus middleware `auth` dari grup rute pembaca dokumentasi:
  ```php
  // Public Documentation Reader Routes (No Login Required)
  Route::get('documentation', [\App\Http\Controllers\General\DocumentationController::class, 'index'])->name('documentation.index');
  Route::get('documentation/pdf/preview/{version}', [\App\Http\Controllers\General\DocumentationController::class, 'previewPdf'])->name('documentation.pdf.preview');
  Route::get('documentation/pdf/download/{version}', [\App\Http\Controllers\General\DocumentationController::class, 'downloadPdf'])->name('documentation.pdf.download');
  Route::get('documentation/{version}/{slug}', [\App\Http\Controllers\General\DocumentationController::class, 'article'])->name('documentation.article');
  ```
- Rute manajemen admin (`admin/documentation/*`) tetap berada di dalam grup middleware `['auth', 'role:Administrator']`.

---

### 2. Layout & Navigasi

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Memperbarui komponen navigasi agar mendukung status otentikasi ganda (`@auth` dan `@guest`):
  - Jika pengguna belum login (`@guest`):
    - Tampilkan logo aplikasi dan tautan menu **📖 Dokumentasi**.
    - Di sisi kanan, tampilkan tombol **Masuk (Login)** yang elegan menuju `route('login')`.
    - Pada tampilan mobile, sediakan link **Dokumentasi** dan **Masuk**.
  - Jika pengguna telah login (`@auth`):
    - Tetap menampilkan menu Dashboard, modul per role, Dokumentasi, dan dropdown profil pengguna.

#### [MODIFY] [documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/documentation/index.blade.php)
- Memastikan pengecekan hak akses admin pada tombol "⚙️ Kelola Dokumentasi" menggunakan pengecekan aman:
  `@if(Auth::check() && Auth::user()->role === 'Administrator')` sehingga tidak terjadi *null object error* saat diakses oleh tamu/publik.

---

### 3. Halaman Depan & Login

#### [MODIFY] [welcome.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/welcome.blade.php)
- Menyematkan tautan menu **📖 Dokumentasi** pada navbar landing page.
- Menambahkan tombol **Baca Buku Panduan →** pada area hero action button.

#### [MODIFY] [login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)
- Menambahkan footer link pada kotak login: *"📖 Butuh panduan penggunaan? Buka Dokumentasi & Manual Book"* menuju `route('documentation.index')`.

---

### 4. Pengujian Otomatis

#### [MODIFY] [tests/Feature/General/DocumentationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/DocumentationTest.php)
- Menyesuaikan pengujian:
  - `test_unauthenticated_guest_can_access_documentation_reader`: Memastikan request GET `/documentation` oleh guest mengembalikan status HTTP 200 OK (tidak redirect ke login).
  - `test_guest_can_view_specific_article_slug`: Memastikan guest dapat membaca artikel spesifik.
  - `test_guest_can_preview_and_download_pdf`: Memastikan guest dapat melihat preview dan mengunduh file PDF manual book.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite dokumentasi:
  `php artisan test --filter=Documentation`
- Menjalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Buka browser dalam mode **Incognito / Private Window** (tanpa login):
   - Akses URL `/` (halaman landing page): Verifikasi tautan menu Dokumentasi tersedia dan dapat diklik.
   - Akses URL `/documentation`:
     - Verifikasi halaman pembaca GoFiber Docs terbuka dengan sempurna.
     - Verifikasi seluruh bab dan artikel dapat dibaca, pencarian `Ctrl + K` berfungsi, dan tabel "On This Page" aktif.
     - Verifikasi tab **Manual Book PDF** dapat dibuka, serta tombol **Unduh PDF** dan **Preview PDF** dapat dieksekusi.
     - Verifikasi bilah navigasi atas menampilkan tombol **Masuk (Login)**.
   - Buka halaman login (`/login`): Verifikasi terdapat tautan cepat menuju dokumentasi.
2. Coba akses URL manajemen admin (`/admin/documentation`) saat belum login:
   - Verifikasi sistem otomatis menolak dan mengarahkan ke halaman login (*HTTP 302 / Redirect*).
