# Walkthrough - Pengembangan Lanjutan SSO OIDC SIMRS (UserInfo, Refresh Token, & Single Logout)

Pengembangan lanjutan modul **OpenID Connect (OIDC) SSO SIMRS RSUD Kardinah** telah selesai diimplementasikan secara penuh sesuai spesifikasi resmi penyedia SSO:
1. **UserInfo Endpoint (`GET /oauth/v2/userinfo`)**
2. **Refresh Token Flow (`POST /oauth/v2/token` dengan `grant_type=refresh_token`)**
3. **Single Logout / SLO (`POST /oauth/v2/logout`)**

---

## Ringkasan Fitur yang Diterapkan

### 1. UserInfo Endpoint (`GET /oauth/v2/userinfo`)
- **Alur Kerja:**
  - Setelah mendapatkan `access_token` pada alur login ROPC, `SimrsOidcService` langsung memanggil endpoint `/oauth/v2/userinfo` dengan header `Authorization: Bearer {access_token}`.
  - Data profil pegawai dari SSO (termasuk `nik`, `sip`, `kategori_pegawai`, `is_dpjp`, `kode_dpjp_bpjs`, `unit_id` SIMRS) digabungkan dengan klaim JWT dan disimpan secara terstruktur di `users.simrs_metadata`.
  - Kolom utama pengguna (`name`, `email`, `simrs_sub`, `nip`) disinkronkan secara otomatis.
  - **Integritas SIPAKAR:** Penugasan `users.unit_id` SIPAKAR dan `users.role` tetap aman dan dipertahankan.
- **Fail-Safe Fallback:** Jika pemanggilan UserInfo gagal atau timeout, sistem tetap mengizinkan login menggunakan klaim JWT `id_token` sehingga pengguna tidak terblokir.

---

### 2. Refresh Token Flow & Token Rotation (`POST /oauth/v2/token`)
- **Alur Kerja:**
  - Mendukung pembaruan *access token* via `grant_type=refresh_token`, `client_id`, `client_secret`, dan `refresh_token`.
  - Server SSO memvalidasi, mencabut token lama (*Token Rotation*), dan menerbitkan pasangan token baru.
  - `SimrsOidcService::refreshToken()` memperbarui sesi pengguna:
    - `simrs_access_token`
    - `simrs_refresh_token`
    - `simrs_token_expires_at`
  - Metode `getValidAccessToken()` disediakan untuk memvalidasi dan memperbarui token secara otomatis sebelum expired (dalam 60 detik sebelum masa habis).

---

### 3. Single Logout / SLO (`POST /oauth/v2/logout`)
- **Alur Kerja:**
  - Saat pengguna logout dari SIPAKAR (`POST /logout`), `AuthenticatedSessionController` mengirimkan request revocasi token ke server SSO:
    - `client_id`, `client_secret`, `refresh_token`.
  - Sesi SSO dicabut dan di-blacklist pada server pusat SIMRS.
  - **Non-Blocking Resilience:** Diberikan timeout cepat (3 detik) dengan error trapping agar logout lokal SIPAKAR dijamin 100% selalu berhasil meskipun server SSO sedang offline atau lambat merespons.

---

### 4. File & Modul yang Dimodifikasi

- **[MODIFY] [config/simrs_oidc.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/simrs_oidc.php)**: Menambahkan `userinfo_endpoint` dan `logout_endpoint`.
- **[MODIFY] [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example)** & **[.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env)**.
- **[MODIFY] [SimrsOidcService.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/Auth/Oidc/SimrsOidcService.php)**: Implementasi `fetchUserInfo()`, `refreshToken()`, `getValidAccessToken()`, `revokeToken()`, dan `syncUser()` metadata enrichment.
- **[MODIFY] [SimrsSsoController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/SimrsSsoController.php)**: Integrasi UserInfo fetch pasca login dan session expiry tracking.
- **[MODIFY] [AuthenticatedSessionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Auth/AuthenticatedSessionController.php)**: Integrasi Single Logout revocation saat user mengakhiri sesi.
- **[MODIFY] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)**: Penambahan unit & feature test komprehensif untuk seluruh skenario baru.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests PASS
Seluruh **105 feature & unit tests** pada aplikasi dijalankan dan **PASSED 100% (105 passed, 0 failed, 399 assertions)**:

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
✓ user can login via simrs sso with mocked oidc server                                                         0.07s  
✓ new sso user is provisioned with default operator role                                                       0.03s  
✓ existing supervisor or admin retains role when logging in via sso                                            0.04s  
✓ sso login fails gracefully when simrs returns invalid grant                                                  0.04s  
✓ sso login fails gracefully when simrs server is down or timeouts                                             0.03s  
✓ local login remains fully functional when sso is enabled                                                     0.04s  
✓ login screen renders with vite assets and tabs properly                                                      0.03s  
✓ sso user without unit id can access submissions index without error                                          0.04s  
✓ sso login fetches and persists userinfo profile                                                              0.03s  
✓ sso service can refresh token via token rotation                                                             0.02s  
✓ user logout triggers single logout revocation at sso server                                                  0.03s  
✓ user logout succeeds even if sso server is down                                                              0.03s  
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

Tests:    105 passed (399 assertions)
Duration: 37.62s
```

### 2. Frontend Assets Build (Bun) PASS
Asset frontend berhasil dikompilasi menggunakan `bun run build`:
- `public/build/assets/app-A9zDAnw6.css` (81.23 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.13s**
