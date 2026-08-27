# Implementation Plan - Modul Terpisah SSO OIDC (Resource Owner Password Credentials) SIMRS

Penerapan metode autentikasi **OpenID Connect (OIDC) / OAuth 2.0 Resource Owner Password Credentials (ROPC)** untuk login akun SIMRS RSUD Kardinah secara **modular, decoupled (terpisah dari modul auth lokal saat ini)**, serta mengadopsi prinsip *enterprise best practice* agar tidak menimbulkan dampak buruk (*zero breaking change*) pada aplikasi SIPAKAR.

---

## User Review Required

> [!IMPORTANT]
> **Prinsip Arsitektur Modular & Best Practice:**
> 1. **Pemisahan Modul (Decoupled Service Layer):**
>    - Modul OIDC SIMRS diletakkan pada layer servis tersendiri (`App\Services\Auth\Oidc\SimrsOidcService`) dan controller terpisah (`App\Http\Controllers\Auth\SimrsSsoController`).
>    - Modul autentikasi bawaan Laravel (`AuthenticatedSessionController` / DB Auth Lokal) tetap dipertahankan 100% tanpa diubah alur utamanya sebagai *fallback / bypass* darurat administrator.
> 2. **Feature Toggle / Kill Switch (`.env`):**
>    - Disediakan switch `SIMRS_OIDC_ENABLED=true/false` di `.env`. Jika server SIMRS OIDC sedang *maintenance*, *down*, atau tidak terjangkau, SIPAKAR tetap beroperasi normal dengan autentikasi lokal.
> 3. **Just-In-Time (JIT) Provisioning & Account Linking:**
>    - Saat pegawai login via SIMRS SSO untuk pertama kali: sistem otomatis menyinkronkan data profil (*NIP, Nama, Email*) dan menghubungkannya dengan akun lokal SIPAKAR secara aman tanpa merusak struktur relasi unit/role yang ada.
> 4. **Keamanan Kredensial Server-to-Server:**
>    - Request token `curl` dieksekusi secara aman di backend via `Http::asForm()->timeout(...)` sehingga `client_secret` SIMRS tidak pernah terekspos ke sisi klien/browser.
> 5. **Dual-Tab UI di Halaman Login:**
>    - Halaman login menyediakan 2 tab elegan: **"Pegawai SIMRS (SSO)"** dan **"Akun Lokal SIPAKAR"**. Jika `SIMRS_OIDC_ENABLED=false`, UI otomatis kembali ke login lokal standar.

---

## Proposed Changes

### 1. Database Schema (`database/migrations`)

#### [NEW] [2026_08_27_010000_add_oidc_fields_to_users_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_08_27_010000_add_oidc_fields_to_users_table.php)
- Menambahkan kolom nullable pada tabel `users`:
  - `simrs_sub` (`string`, `unique`, `nullable`): ID unik subjek OIDC SIMRS.
  - `nip` (`string`, `nullable`, `index`): Nomor Induk Pegawai dari SIMRS.
  - `auth_provider` (`enum: 'local', 'simrs_oidc'`, default `'local'`): Indikator asal autentikasi.
  - `simrs_metadata` (`json`, `nullable`): Menyimpan payload klaim data pegawai dari SIMRS (jabatan, unit asal, dll.).

---

### 2. Konfigurasi (`config/simrs_oidc.php` & `.env.example`)

#### [NEW] [config/simrs_oidc.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/simrs_oidc.php)
- Konfigurasi parameter OIDC SIMRS yang fleksibel:
  ```php
  return [
      'enabled' => env('SIMRS_OIDC_ENABLED', false),
      'base_url' => env('SIMRS_OIDC_BASE_URL', 'http://172.16.61.111:8080'),
      'token_endpoint' => env('SIMRS_OIDC_TOKEN_ENDPOINT', 'http://172.16.61.111:8080/oauth/v2/token'),
      'userinfo_endpoint' => env('SIMRS_OIDC_USERINFO_ENDPOINT', 'http://172.16.61.111:8080/oauth/v2/userinfo'),
      'client_id' => env('SIMRS_OIDC_CLIENT_ID', ''),
      'client_secret' => env('SIMRS_OIDC_CLIENT_SECRET', ''),
      'scope' => env('SIMRS_OIDC_SCOPE', 'openid profile email simrs:pegawai'),
      'timeout_seconds' => env('SIMRS_OIDC_TIMEOUT', 10),
      'default_role' => env('SIMRS_OIDC_DEFAULT_ROLE', 'Operator'),
  ];
  ```

#### [MODIFY] [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example) & [.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env)
- Menambahkan template variabel lingkungan `SIMRS_OIDC_*`.

---

### 3. Service Layer OIDC (`app/Services/Auth/Oidc`)

#### [NEW] [SimrsOidcService.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/Auth/Oidc/SimrsOidcService.php)
- Enkapsulasi seluruh komunikasi HTTP ke OAuth/OIDC Server SIMRS:
  - `authenticate(string $username, string $password): array`:
    - Mengirim POST `application/x-www-form-urlencoded` dengan payload `grant_type=password`, `client_id`, `client_secret`, `username`, `password`, `scope`.
    - Menangani respons token: `access_token`, `id_token`, `refresh_token`, `expires_in`.
  - `extractUserClaims(array $tokenResponse): array`:
    - Mendekode klaim ID Token / Userinfo (NIP, nama lengkap, email, dsb).
  - `findOrCreateUser(array $claims): User`:
    - Mencari user berdasarkan `simrs_sub`, `nip`, atau `email`.
    - Melakukan sinkronisasi akun (Just-In-Time Provisioning) dengan role default jika user baru.

#### [NEW] [OidcAuthenticationException.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Exceptions/OidcAuthenticationException.php)
- Exception kustom untuk menangani pesan error spesifik dari SIMRS OIDC (*invalid_grant*, *server_error*, *timeout*, *network unreachable*).

---

### 4. Controller & Request Form (`app/Http/Controllers/Auth`)

#### [NEW] [SimrsSsoLoginRequest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Requests/Auth/SimrsSsoLoginRequest.php)
- Validasi input `username_simrs` (NIP/Username) dan `password_simrs`.

#### [NEW] [SimrsSsoController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/SimrsSsoController.php)
- `login(SimrsSsoLoginRequest $request)`:
  - Memanggil `SimrsOidcService`.
  - Melakukan `Auth::login($user, $remember)`.
  - Melakukan session regeneration dan redirect sesuai role pengguna (Administrator, Supervisor, Operator).
  - Menangani error secara ramah (misal: "Username atau kata sandi SIMRS salah" atau "Server SSO SIMRS sedang tidak dapat dihubungi").

---

### 5. Routing (`routes/auth.php`)

#### [MODIFY] [routes/auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)
- Menambahkan rute POST login SSO OIDC:
  ```php
  Route::middleware('guest')->group(function () {
      Route::post('login/sso', [\App\Http\Controllers\Auth\SimrsSsoController::class, 'login'])->name('login.sso');
  });
  ```

---

### 6. User Interface Login (`resources/views/auth/login.blade.php`)

#### [MODIFY] [resources/views/auth/login.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/auth/login.blade.php)
- Menerapkan Tab Switcher interaktif berbasis Alpine.js:
  - **Tab 1: 🏥 Login Akun SIMRS (SSO)** (Default jika OIDC aktif):
    - Input: NIP / Username SIMRS
    - Input: Kata Sandi Akun SIMRS
    - Action: `route('login.sso')`
  - **Tab 2: 🔐 Akun Lokal SIPAKAR**:
    - Input: Alamat Email SIPAKAR
    - Input: Kata Sandi SIPAKAR
    - Action: `route('login')`
- Jika `config('simrs_oidc.enabled') == false`, tampilan langsung menampilkan form lokal biasa tanpa tab SSO.

---

### 7. Pengujian Otomatis (`tests/Feature/Auth/SimrsSsoTest.php`)

#### [NEW] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)
- Menguji skenario:
  1. `test_user_can_login_via_simrs_sso_with_mocked_oidc_server` (Mock HTTP 200 token response -> Berhasil login & session terbentuk).
  2. `test_first_time_sso_user_is_automatically_provisioned_with_correct_role` (JIT Provisioning).
  3. `test_sso_login_fails_gracefully_when_simrs_returns_invalid_grant` (Mock HTTP 400 invalid credentials).
  4. `test_sso_login_fails_gracefully_when_simrs_server_is_down_or_timeouts` (Mock HTTP Timeout).
  5. `test_local_login_remains_fully_functional_when_sso_is_enabled` (Zero impact ke login lokal).

---

## Verification Plan

### Automated Tests
- Menjalankan test suite SSO OIDC baru:
  `php artisan test --filter=SimrsSsoTest`
- Menjalankan seluruh test suite otentikasi & aplikasi:
  `php artisan test`

### Manual Verification
1. Uji Login SSO SIMRS:
   - Input username dan password SIMRS yang valid.
   - Verifikasi pengguna terautentikasi dan diarahkan ke Dashboard sesuai role.
2. Uji Login Akun Lokal:
   - Beralih ke tab "Akun Lokal SIPAKAR", input akun admin lokal (`admin@hospital.com`).
   - Verifikasi login lokal tetap berfungsi 100%.
3. Uji Skenario Server Mati / Timeout:
   - Ubah endpoint OIDC ke IP yang tidak aktif di `.env`.
   - Coba login SSO, pastikan aplikasi tidak *crash* melainkan menampilkan pesan ramah: *"Gagal terhubung ke server autentikasi SIMRS. Silakan gunakan Akun Lokal atau coba beberapa saat lagi"*.
