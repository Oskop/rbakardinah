# Implementation Plan - Pengembangan Lanjutan SSO OIDC SIMRS (UserInfo, Refresh Token, & Single Logout)

Mengembangkan modul SSO OIDC SIMRS RSUD Kardinah lebih lanjut sesuai spesifikasi resmi penyedia SSO dengan menambahkan:
1. **UserInfo Endpoint (`GET /oauth/v2/userinfo`)** untuk memperkaya profil pengguna (NIK, SIP, Kategori Pegawai, DPJP, Metadata SIMRS).
2. **Refresh Token Flow (`POST /oauth/v2/token` dengan `grant_type=refresh_token`)** untuk pembaruan *access token* secara otomatis di latar belakang (*Token Rotation*).
3. **Single Logout / SLO (`POST /oauth/v2/logout`)** untuk mencabut *refresh token* dan mengakhiri sesi pada server SSO saat pengguna logout dari SIPAKAR.

---

## User Review Required

> [!IMPORTANT]
> **Poin Kunci Desain & Integrasi:**
> 1. **UserInfo Integration (`/oauth/v2/userinfo`):**
>    - Setelah token didapatkan pada saat login, sistem akan langsung memanggil endpoint `/oauth/v2/userinfo` dengan header `Authorization: Bearer {access_token}`.
>    - Data profil dari UserInfo (`sub`, `username`, `name`, `nik`, `sip`, `kategori_pegawai`, `is_dpjp`, `email`, `kode_dpjp_bpjs`, `unit_id` SIMRS) digabungkan ke dalam `users.simrs_metadata`.
>    - Kolom `name`, `email`, `simrs_sub`, dan `nip` pada model `User` diperbarui secara sinkron.
>    - Penugasan `users.unit_id` SIPAKAR dan `users.role` tetap **dipertahankan (preserved)** dan dikelola oleh Administrator SIPAKAR.
> 2. **Refresh Token & Auto-Renewal:**
>    - Menyimpan `simrs_token_expires_at` pada session saat login/refresh.
>    - Menyediakan metode `getValidAccessToken()` pada service yang otomatis memperbarui token via `grant_type=refresh_token` saat mendekati masa kedaluwarsa (15 menit).
> 3. **Single Logout (SLO) Tanpa Blokir:**
>    - Saat logout dari SIPAKAR (`POST /logout`), sistem mengirimkan `POST /oauth/v2/logout` dengan payload `client_id`, `client_secret`, dan `refresh_token` untuk mem-blacklist token di SSO server.
>    - Diberikan *timeout* singkat (3 detik) dan *graceful fallback* agar proses logout lokal SIPAKAR selalu berhasil meskipun server SSO sedang offline/lambat.

---

## Proposed Changes

### 1. Konfigurasi (`config/simrs_oidc.php`, `.env.example`, `.env`)

#### [MODIFY] [config/simrs_oidc.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/simrs_oidc.php)
- Menambahkan kunci konfigurasi:
  - `userinfo_endpoint`: `env('SIMRS_OIDC_USERINFO_ENDPOINT', 'http://172.16.61.111:8080/oauth/v2/userinfo')`
  - `logout_endpoint`: `env('SIMRS_OIDC_LOGOUT_ENDPOINT', 'http://172.16.61.111:8080/oauth/v2/logout')`

#### [MODIFY] [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example) & [.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env)
- Menambahkan variabel:
  - `SIMRS_OIDC_USERINFO_ENDPOINT=http://172.16.61.111:8080/oauth/v2/userinfo`
  - `SIMRS_OIDC_LOGOUT_ENDPOINT=http://172.16.61.111:8080/oauth/v2/logout`

---

### 2. Service Layer SSO (`app/Services/Auth/Oidc/SimrsOidcService.php`)

#### [MODIFY] [SimrsOidcService.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/Auth/Oidc/SimrsOidcService.php)
- **`fetchUserInfo(string $accessToken): ?array`**:
  - Mengirim `GET /oauth/v2/userinfo` dengan header `Authorization: Bearer {$accessToken}`.
  - Mengembalikan array associative data profil atau `null` jika gagal/timeout tanpa melempar fatal exception (non-blocking fallback).
- **`refreshToken(?string $refreshToken = null): ?array`**:
  - Mengirim `POST /oauth/v2/token` dengan form params:
    - `grant_type` = `'refresh_token'`
    - `client_id`, `client_secret`
    - `refresh_token` = `$refreshToken`
  - Memperbarui data session (`simrs_access_token`, `simrs_refresh_token`, `simrs_token_expires_at`).
- **`getValidAccessToken(): ?string`**:
  - Mengambil access token yang valid dari session; jika expired / mendekati expired, otomatis memanggil `refreshToken()`.
- **`revokeToken(?string $refreshToken = null): bool`**:
  - Mengirim `POST /oauth/v2/logout` dengan form params:
    - `client_id`, `client_secret`
    - `refresh_token`
  - Mengembalikan `true` jika berhasil di-blacklist di SSO server.
- **Pembaruan `syncUser(...)`**:
  - Menerima argumen opsional `$userInfo` dan menggabungkan data klaim JWT dan profil UserInfo (`nik`, `sip`, `kategori_pegawai`, dll.) ke dalam `users.simrs_metadata`.

---

### 3. Controller Login & Logout

#### [MODIFY] [SimrsSsoController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/SimrsSsoController.php)
- Memanggil `fetchUserInfo($tokenData['access_token'])` setelah otentikasi token berhasil.
- Mengirim data UserInfo ke `syncUser($tokenData, $usernameInput, $userInfo)`.
- Menyimpan timestamp `simrs_token_expires_at` pada session: `now()->addSeconds($tokenData['expires_in'] ?? 900)->timestamp`.

#### [MODIFY] [AuthenticatedSessionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/AuthenticatedSessionController.php)
- Pada method `destroy(Request $request)`:
  - Mengambil `simrs_refresh_token` dari session sebelum session di-invalidate.
  - Memanggil `$oidcService->revokeToken($refreshToken)`.
  - Melakukan logout guard web dan invalidasi session seperti biasa.

---

### 4. Pengujian Otomatis (`tests/Feature/Auth/SimrsSsoTest.php`)

#### [MODIFY] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)
- Menambahkan pengujian:
  1. `test_sso_login_fetches_and_persists_userinfo_profile`: Memverifikasi panggilan ke endpoint `/oauth/v2/userinfo` dan penyimpanan NIK, SIP, Kategori Pegawai ke `simrs_metadata`.
  2. `test_sso_service_can_refresh_token_via_token_rotation`: Memverifikasi pembaruan token melalui flow `grant_type=refresh_token`.
  3. `test_user_logout_triggers_single_logout_revocation_at_sso_server`: Memverifikasi pemanggilan endpoint `/oauth/v2/logout` dengan `refresh_token` saat user logout dari SIPAKAR.
  4. `test_user_logout_succeeds_even_if_sso_server_is_down`: Memastikan logout lokal tetap sukses tanpa error 500 meskipun server SSO offline saat logout.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite autentikasi SSO:
  `php artisan test --filter=SimrsSsoTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login dengan akun SIMRS SSO:
   - Pastikan login sukses dan diarahkan ke dashboard yang sesuai.
   - Cek database (`users.simrs_metadata`), pastikan data `nik`, `sip`, `kategori_pegawai`, `is_dpjp` tersimpan dengan lengkap.
2. Klik tombol **Log Out**:
   - Pastikan sesi berakhir dengan bersih dan kembali ke halaman utama `/` tanpa error.
