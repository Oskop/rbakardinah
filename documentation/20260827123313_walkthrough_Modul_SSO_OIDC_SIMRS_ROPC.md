# Walkthrough - Modul Terpisah SSO OIDC SIMRS (Resource Owner Password Credentials)

Penerapan metode autentikasi **OpenID Connect (OIDC) / OAuth 2.0 Resource Owner Password Credentials (ROPC)** untuk login akun SIMRS RSUD Kardinah telah selesai diimplementasikan secara **modular, decoupled (terpisah dari modul auth lokal)**, dan teruji 100% dengan prinsip *enterprise best practice*.

---

## Ringkasan Fitur yang Diterapkan

### 1. Arsitektur Decoupled & Modular (`App\Services\Auth\Oidc\SimrsOidcService`)
- **Direct API Server-to-Server:**
  - Mengirimkan request token ROPC (`grant_type=password`, `client_id`, `client_secret`, `username`, `password`, `scope`) via `Illuminate\Support\Facades\Http::asForm()->timeout(10)->post(...)`.
  - `client_secret` terlindungi penuh di sisi backend dan tidak pernah terekspos ke browser pengguna.
- **Parsing JWT `id_token` Tanpa Dependensi Berat:**
  - Fungsi mandiri `parseJwtPayload()` mendekode payload JWT (`sub`, `nip`, `name`, `email`, `preferred_username`) secara aman (*Base64Url decode*).
- **Just-In-Time (JIT) Provisioning & Role Preservation:**
  - **User Baru:** Otomatis didaftarkan ke SIPAKAR dengan role default **`Operator`** (dapat diatur via `.env: SIMRS_OIDC_DEFAULT_ROLE`) dan `auth_provider = 'simrs_oidc'`.
  - **User Eksisting:** Mencocokkan via `simrs_sub`, `nip`, atau `email`. **Role eksisting (misal: Supervisor atau Administrator) dan unit kerja yang sudah ada TIDAK akan ditimpa/diubah**.
- **Session & Token Management:**
  - Menyimpan `access_token`, `refresh_token`, dan `expires_in` pada session pengguna untuk keperluan komunikasi API SIMRS lanjutan.
- **Feature Toggle / Kill Switch:**
  - `SIMRS_OIDC_ENABLED=true/false` di `config/simrs_oidc.php`. Jika server SIMRS mati atau dinonaktifkan, SIPAKAR tetap beroperasi normal dengan autentikasi lokal.

---

### 2. Antarmuka Login Ganda (*Dual-Tab Switcher*) (`resources/views/auth/login.blade.php`)
- **Tab 1: 🏥 Pegawai SIMRS (SSO):**
  - Form input: **NIP / Username SIMRS** dan **Kata Sandi SIMRS**.
  - Mengarahkan request ke `POST /login/sso`.
  - Menampilkan pesan notifikasi bahwa kredensial yang digunakan sama dengan akun SIMRS resmi.
- **Tab 2: 🔐 Akun Lokal SIPAKAR:**
  - Form input: **Alamat Email SIPAKAR** dan **Kata Sandi**.
  - Mengarahkan request ke `POST /login`.
- Transisi halus menggunakan Alpine.js dengan retensi input dan pesan error yang spesifik.

---

### 3. File & Modul yang Dibuat / Dimodifikasi

#### Database & Models
- **[NEW] [2026_08_27_010000_add_oidc_fields_to_users_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_08_27_010000_add_oidc_fields_to_users_table.php)**: Menambahkan `simrs_sub`, `nip`, `auth_provider`, `simrs_metadata` pada tabel `users`.
- **[MODIFY] [User.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/User.php)**: Menambahkan kolom OIDC ke `$fillable` dan `$casts`.

#### Configuration & Environment
- **[NEW] [config/simrs_oidc.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/simrs_oidc.php)**: Konfigurasi endpoint, client_id, client_secret, scope, timeout, dan default role.
- **[MODIFY] [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example)** & **[.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env)**.

#### Services, Controllers, & Exceptions
- **[NEW] [OidcAuthenticationException.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Exceptions/OidcAuthenticationException.php)**: Exception kustom error SSO OIDC.
- **[NEW] [SimrsOidcService.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/Auth/Oidc/SimrsOidcService.php)**: Servis utama otentikasi token, decode JWT, dan JIT User Provisioning.
- **[NEW] [SimrsSsoLoginRequest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Requests/Auth/SimrsSsoLoginRequest.php)**: Validasi input form SSO.
- **[NEW] [SimrsSsoController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/SimrsSsoController.php)**: Controller login SSO.
- **[MODIFY] [routes/auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)**: Registrasi route `POST login/sso`.

#### Views & Automated Tests
- **[MODIFY] [resources/views/auth/login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)**: Desain dual-tab login SSO vs Lokal.
- **[NEW] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)**: Suite pengujian otomatis SSO.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh **99 feature & unit tests** pada aplikasi dijalankan dan **PASSED 100% (99 passed, 369 assertions)**:

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

Tests:    99 passed (369 assertions)
Duration: 7.89s
```

### 2. Frontend Assets Build (Bun) PASS
Kompilasi asset frontend menggunakan `bun run build` sukses:
- `public/build/assets/app-Cpp52t3B.css` (81.06 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.19s**
