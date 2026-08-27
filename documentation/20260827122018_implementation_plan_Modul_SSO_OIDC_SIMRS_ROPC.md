# Implementation Plan - Modul Terpisah SSO OIDC (Resource Owner Password Credentials) SIMRS (Revisi)

Penerapan metode autentikasi **OpenID Connect (OIDC) / OAuth 2.0 Resource Owner Password Credentials (ROPC)** untuk login akun SIMRS RSUD Kardinah secara **modular, decoupled (terpisah dari modul auth lokal saat ini)**, lengkap dengan spesifikasi parsing respons token (`id_token` JWT) dan penetapan default role untuk pengguna baru.

---

## User Review Required

> [!IMPORTANT]
> **Penetapan Default Role & Penanganan Akun Pertama Kali Login:**
> 1. **Default Role untuk Pengguna Baru SSO (First-Time Login):**
>    - Pengguna baru yang pertama kali login via SIMRS SSO akan secara *default* diberikan role **`Operator`**.
>    - **Alasan & Best Practice:**
>      - Mayoritas pegawai rumah sakit bertindak sebagai pengusul anggaran unit kerja (*Operator*).
>      - Role dengan wewenang tinggi (*Supervisor* untuk validasi dan *Administrator* untuk penetapan pagu) hanya diberikan/diubah secara eksplisit oleh Administrator melalui menu **Users** demi keamanan data anggaran rumah sakit.
>      - Nilai default ini dibuat fleksibel dan dapat dikonfigurasi melalui `.env`: `SIMRS_OIDC_DEFAULT_ROLE=Operator`.
> 2. **Perlindungan Akun Eksisting (*Preserve Existing Role*):**
>    - Jika akun sudah terdaftar sebelumnya di SIPAKAR (misalnya Direktur/Kabid dengan role *Supervisor* atau *Administrator*): saat mereka login menggunakan SIMRS SSO, **role dan unit kerja yang sudah ada TIDAK akan ditimpa/diturunkan menjadi Operator**, melainkan tetap mempertahankan hak akses aslinya.
> 3. **Spesifikasi Respons Token SIMRS:**
>    - Respons OIDC yang diterima:
>      ```json
>      {
>        "access_token": "eyJhbGciOiJFZERTQSI...",
>        "token_type": "Bearer",
>        "expires_in": 900,
>        "refresh_token": "8f3b2a1c9d4e...",
>        "id_token": "eyJhbGciOiJFZERTQSI...",
>        "scope": "openid profile email simrs:pegawai"
>      }
>      ```
>    - Sistem akan mengekstrak payload dari `id_token` (JWT Base64Url Payload) untuk mengambil:
>      - `sub` : ID unik pengguna SIMRS
>      - `name` / `nama` : Nama lengkap pegawai
>      - `email` : Alamat email pegawai (atau fallback ke `nip@rsudkardinah.tegal.go.id`)
>      - `nip` / `preferred_username` : NIP / Username SIMRS pegawai

---

## Proposed Changes

### 1. Database Schema (`database/migrations`)

#### [NEW] [2026_08_27_010000_add_oidc_fields_to_users_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_08_27_010000_add_oidc_fields_to_users_table.php)
- Menambahkan kolom pada tabel `users`:
  - `simrs_sub` (`string`, `unique`, `nullable`): ID unik subjek OIDC SIMRS.
  - `nip` (`string`, `nullable`, `index`): Nomor Induk Pegawai dari SIMRS.
  - `auth_provider` (`enum: 'local', 'simrs_oidc'`, default `'local'`): Indikator asal otentikasi.
  - `simrs_metadata` (`json`, `nullable`): Menyimpan payload klaim data pegawai dari SIMRS.

---

### 2. Konfigurasi (`config/simrs_oidc.php` & `.env.example`)

#### [NEW] [config/simrs_oidc.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/simrs_oidc.php)
- Konfigurasi parameter OIDC SIMRS:
  ```php
  return [
      'enabled' => env('SIMRS_OIDC_ENABLED', false),
      'base_url' => env('SIMRS_OIDC_BASE_URL', 'http://172.16.61.111:8080'),
      'token_endpoint' => env('SIMRS_OIDC_TOKEN_ENDPOINT', 'http://172.16.61.111:8080/oauth/v2/token'),
      'client_id' => env('SIMRS_OIDC_CLIENT_ID', ''),
      'client_secret' => env('SIMRS_OIDC_CLIENT_SECRET', ''),
      'scope' => env('SIMRS_OIDC_SCOPE', 'openid profile email simrs:pegawai'),
      'timeout_seconds' => env('SIMRS_OIDC_TIMEOUT', 10),
      'default_role' => env('SIMRS_OIDC_DEFAULT_ROLE', 'Operator'),
  ];
  ```

#### [MODIFY] [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example) & [.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env)
- Menambahkan entri konfigurasi OIDC.

---

### 3. Service Layer OIDC (`app/Services/Auth/Oidc`)

#### [NEW] [SimrsOidcService.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/Auth/Oidc/SimrsOidcService.php)
- Menangani alur otentikasi server-to-server:
  1. `requestToken(string $username, string $password): array`:
     - Menjalankan HTTP POST form ke token endpoint dengan `grant_type=password`, `client_id`, `client_secret`, `username`, `password`, `scope`.
  2. `parseIdToken(string $idToken): array`:
     - Mendekode payload JWT tanpa dependensi pihak ketiga (Base64Url decode segmen payload) secara aman.
  3. `syncUser(array $tokenData, array $claims, string $usernameInput): User`:
     - Pencarian user: berdasarkan `simrs_sub`, `nip`, atau `email`.
     - **Jika User Baru:** Buat akun baru dengan role **`Operator`** (atau `config('simrs_oidc.default_role')`), `auth_provider = 'simrs_oidc'`, `password = null / random`.
     - **Jika User Lama:** Pertahankan role & unit eksisting, perbarui `name`, `email`, dan simpan `simrs_sub`.
     - Simpan `access_token` dan `refresh_token` di sesi pengguna untuk keperluan komunikasi API masa depan.

#### [NEW] [OidcAuthenticationException.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Exceptions/OidcAuthenticationException.php)
- Exception handler khusus untuk error OIDC (*invalid_grant*, *server down*, *timeout*).

---

### 4. Controller & Request Form (`app/Http/Controllers/Auth`)

#### [NEW] [SimrsSsoLoginRequest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Requests/Auth/SimrsSsoLoginRequest.php)
- Validasi input `username_simrs` dan `password_simrs`.

#### [NEW] [SimrsSsoController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/SimrsSsoController.php)
- Menangani `POST /login/sso`:
  - Memanggil `SimrsOidcService`.
  - Melakukan `Auth::login($user, $remember)`.
  - Redirect otomatis ke dashboard sesuai role (`admin.dashboard`, `supervisor.dashboard`, `operator.dashboard`).

---

### 5. Routing (`routes/auth.php`)

#### [MODIFY] [routes/auth.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/auth.php)
- Menambahkan rute POST `login/sso`:
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
  1. `test_user_can_login_via_simrs_sso_with_mocked_oidc_server` (Mock response token OIDC lengkap dengan `id_token` -> Berhasil login & redirect ke dashboard).
  2. `test_new_sso_user_is_provisioned_with_default_operator_role` (Memastikan role default adalah Operator).
  3. `test_existing_supervisor_or_admin_retains_role_when_logging_in_via_sso` (Memastikan role Supervisor/Admin tidak tertimpa).
  4. `test_sso_login_fails_gracefully_when_simrs_returns_invalid_grant` (Mock 400 invalid credentials).
  5. `test_sso_login_fails_gracefully_when_simrs_server_is_down_or_timeouts` (Mock timeout/connection refused).
  6. `test_local_login_remains_fully_functional_when_sso_is_enabled` (Login lokal tetap berjalan 100%).

---

## Verification Plan

### Automated Tests
- Menjalankan test suite SSO OIDC baru:
  `php artisan test --filter=SimrsSsoTest`
- Menjalankan seluruh test suite otentikasi & aplikasi:
  `php artisan test`

### Manual Verification
1. Uji Login SSO SIMRS:
   - Input username dan password SIMRS.
   - Verifikasi pengguna baru otomatis terdaftar dengan role `Operator` dan dapat langsung mengakses Workboard RBA.
2. Uji Akun Supervisor Eksisting:
   - Login dengan SSO untuk akun yang sudah berstatus Supervisor di SIPAKAR.
   - Verifikasi role tetap `Supervisor` dan langsung diarahkan ke `/supervisor/dashboard`.
3. Uji Login Akun Lokal:
   - Beralih ke tab "Akun Lokal SIPAKAR", input akun admin lokal (`admin@hospital.com`).
   - Verifikasi login lokal tetap berfungsi 100%.
