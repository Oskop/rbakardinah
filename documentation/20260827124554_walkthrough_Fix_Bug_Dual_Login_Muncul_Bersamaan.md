# Walkthrough - Perbaikan Bug Form Login Ganda (SSO & Lokal) Muncul Bersamaan

Perbaikan *bug* pada halaman login di mana kedua formulir login (Metode SSO SIMRS dan Metode Akun Lokal SIPAKAR) tampil bersamaan bertumpuk di layar telah selesai diperbaiki dan teruji 100%.

---

## Ringkasan Perbaikan yang Diterapkan

### 1. Integrasi Bundle Vite & Alpine.js pada Layout Tamu (`resources/views/layouts/guest.blade.php`)
- **Masalah:** File `guest.blade.php` sebelumnya hanya memuat Tailwind CDN tanpa memuat script JavaScript `@vite(['resources/css/app.css', 'resources/js/app.js'])`. Akibatnya, modul Alpine.js tidak terinisialisasi dan direktif reaktif (`x-data`, `x-show`, `@click`) tidak berjalan.
- **Solusi:**
  - Menyematkan `@vite(['resources/css/app.css', 'resources/js/app.js'])` di dalam `<head>` layout tamu.
  - Menambahkan aturan CSS global `[x-cloak] { display: none !important; }` untuk mencegah *flicker* form saat rendering awal.

---

### 2. Atribut `x-cloak` & Inline Style Fallback (`resources/views/auth/login.blade.php`)
- Menambahkan atribut `x-cloak` pada kedua tag `<form>` (SSO SIMRS dan Akun Lokal SIPAKAR).
- Menambahkan inline fallback `style="display: none;"` secara server-side kondisional sehingga sebelum JavaScript terhidrasi di browser, hanya form tab aktif yang dirender dan form non-aktif dijamin 100% tersembunyi:
  - Form SSO SIMRS: `x-cloak x-show="tab === 'simrs'" style="{{ $initialTab === 'simrs' ? '' : 'display: none;' }}"`
  - Form Akun Lokal: `x-cloak x-show="!{{ $oidcEnabled ? 'true' : 'false' }} || tab === 'local'" style="{{ (!$oidcEnabled || $initialTab === 'local') ? '' : 'display: none;' }}"`

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests PASS
Seluruh **100 feature & unit tests** pada aplikasi dijalankan dan **PASSED 100% (100 passed, 373 assertions)**:

```text
PASS  Tests\Feature\Admin\ActivityLogTest
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\DocumentationManagementTest
PASS  Tests\Feature\Admin\KelompokBelanjaTest
PASS  Tests\Feature\Admin\PaguTest
PASS  Tests\Feature\Auth\AuthenticationTest
PASS  Tests\Feature\Auth\EmailVerificationTest
PASS  Tests\Feature\Auth\PasswordConfirmationTest
PASS  Tests\Feature\Auth\PasswordResetTest
PASS  Tests\Feature\Auth\PasswordUpdateTest
PASS  Tests\Feature\Auth\RegistrationTest
PASS  Tests\Feature\Auth\SimrsSsoTest
✓ user can login via simrs sso with mocked oidc server                                                         0.06s  
✓ new sso user is provisioned with default operator role                                                       0.03s  
✓ existing supervisor or admin retains role when logging in via sso                                            0.03s  
✓ sso login fails gracefully when simrs returns invalid grant                                                  0.04s  
✓ sso login fails gracefully when simrs server is down or timeouts                                             0.03s  
✓ local login remains fully functional when sso is enabled                                                     0.04s  
✓ login screen renders with vite assets and tabs properly                                                      0.03s  
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\General\DocumentationTest
PASS  Tests\Feature\General\HistoryTest
PASS  Tests\Feature\General\StorageTest
PASS  Tests\Feature\Operator\OperatorDashboardTest
PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
PASS  Tests\Feature\Operator\RbaDetailTest
PASS  Tests\Feature\ProfileTest
PASS  Tests\Feature\Supervisor\ReviewTest
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    100 passed (373 assertions)
Duration: 7.45s
```

### 2. Frontend Assets Build (Bun) PASS
Asset CSS dan JavaScript berhasil dikompilasi menggunakan `bun run build`:
- `public/build/assets/app-Cpp52t3B.css` (81.06 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.13s**
