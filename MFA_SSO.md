# SSO SIMRS Identity Server (TemanSatu Health Platform)

![Go](https://img.shields.io/badge/Go-1.26+-00ADD8?style=flat&logo=go)
![OIDC Compliance](https://img.shields.io/badge/OIDC-1.0%20%26%20OAuth%202.0-blue)
![PostgreSQL](https://img.shields.io/badge/Database-PostgreSQL-336791?logo=postgresql)
![SvelteKit](https://img.shields.io/badge/Admin%20UI-SvelteKit-FF3E00?logo=svelte)

Sistem **Single Sign-On (SSO) & Centralized Identity Server** berstandar industri **OpenID Connect (OIDC) 1.0 & OAuth 2.0** yang dirancang khusus untuk ekosistem **Sistem Informasi Manajemen Rumah Sakit (SIMRS)**.

Aplikasi ini mengintegrasikan otentikasi staf medis, perawat, dan administrasi dari database SIMRS utama (PostgreSQL, MySQL/MariaDB, MS SQL Server) secara terpusat tanpa perlu menduplikasi kredensial pengguna.

---

## 📑 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Arsitektur & Spesifikasi Teknis](#-arsitektur--spesifikasi-teknis)
- [Multi-Factor Authentication (MFA)](#-multi-factor-authentication-mfa)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Konfigurasi Environment Variables](#-konfigurasi-environment-variables)
- [Cara Install & Penggunaan](#-cara-install--penggunaan)
- [Daftar Endpoint API Lengkap](#-daftar-endpoint-api-lengkap)
  - [1. OpenID Connect & OAuth 2.0 Standard](#1-openid-connect--oauth-20-standard)
  - [2. Multi-Factor Authentication (MFA) Endpoints](#2-multi-factor-authentication-mfa-endpoints)
  - [3. System & Health Check](#3-system--health-check)
  - [4. Admin Management REST API](#4-admin-management-rest-api)
  - [5. Web Admin Dashboard](#5-web-admin-dashboard)
- [Panduan Integrasi Aplikasi Client (OIDC / OAuth2)](#-panduan-integrasi-aplikasi-client-oidc--oauth2)
- [Klaim Profil Pegawai & Pemetaan Token (User Claims)](#-klaim-profil-pegawai--pemetaan-token-user-claims)

---

## 🚀 Fitur Utama

- **Standard OIDC 1.0 & OAuth 2.0 Compliance**: Menyiapkan Discovery Endpoint (`/.well-known/openid-configuration`), JWKS Key Set (`/.well-known/jwks.json`), PKCE S256, UserInfo, Token Revocation, dan Single Logout.
- **Multi-Driver SIMRS Database Support**: Mendukung koneksi dinamis *read-only* ke database SIMRS utama berbasis **PostgreSQL**, **MySQL / MariaDB**, maupun **MS SQL Server**.
- **Multi-Algorithm Password Verification**: Mendukung hashing modern (**Argon2id**, **Bcrypt**) hingga algoritma *legacy* SIMRS (**PBKDF2**, **MD5**, **SHA256** dengan/tanpa salt).
- **Session Continuity (Stateless JWT)**: Karena token ditandatangani secara asimetris (Ed25519 / RS256), aplikasi *client* dapat mevalidasi signature token secara lokal via JWKS. Jika DB SIMRS utama *down*, sesi pengguna yang aktif tetap berjalan normal sampai batas kadaluarsa token (`exp`).
- **Multi-Factor Authentication (MFA / 2FA)**:
  - **Multi-Channel Delivery**: Pengiriman OTP otomatis via WhatsApp Gateway dan Telegram Bot terintegrasi melalui service **TemanErat**.
  - **TOTP Authenticator**: Kompatibel dengan aplikasi standar TOTP (Google Authenticator, Microsoft Authenticator, 2FAS).
  - **Zero-Width Space Salt**: Proteksi anti-tampering dan anti-forwarding pesan OTP dengan injeksi karakter salt transparan.
  - **Flexible Client MFA Policy**: Pengaturan kebijakan per-aplikasi client (`disabled`, `optional`, `enforced`).
  - **Remember Trusted Device**: Kemampuan menyimpan perangkat terpercaya dengan cookie bertanda tangan HMAC.
- **Security Hardening**:
  - Rate limiting & brute-force lockout menggunakan **Exponential Backoff with Cryptographic Jitter**.
  - Hash kunci rahasia aplikasi (*client_secret*) menggunakan **Argon2id**.
  - Enkripsi password koneksi DB SIMRS menggunakan **AES-256-GCM** dengan Master Key.
  - Sesi Admin terlindungi dengan **HMAC-SHA256 Signed Session Token** & Cookie HTTP-Only.
- **Asynchronous Audit Logger**: Penulisan log audit berbasis *worker pool* berkinerja tinggi (non-blocking) dengan fitur pembersihan otomatis (*log retention cleanup*) dan pembersihan buffer aman saat shutdown (*graceful shutdown*).
- **Automated Hot Backup**: Backup online PostgreSQL otomatis setiap 24 jam tanpa *downtime* serta eksekusi backup manual via Admin API.
- **Embedded Web Admin Dashboard**: SPA SvelteKit yang di-embed langsung ke dalam single executable binary Go pada rute `/admin`.

---

## 📐 Arsitektur & Spesifikasi Teknis

```text
                                ┌───────────────────────────────────┐
                                │       SvelteKit Admin UI          │
                                └─────────────────┬─────────────────┘
                                                  │
                                            REST API (Admin)
                                                  │
                                                  v
 ┌────────────────┐ 1. OIDC Req  ┌───────────────────┐ 2. Read Auth ┌──────────────────────────┐
 │ Client App     ├─────────────>│ Golang SSO Engine ├─────────────>│ External SIMRS PrimaryDB │
 │ (OIDC Library) │              │ (OIDC Core)       │              │ (Postgres/MySQL/MSSQL)   │
 └────────────────┘<─────────────└─────────┬─────────┘              └──────────────────────────┘
                    4. ID/Access Token     │
                                           │ 3. MFA Challenge (If required/enforced)
                                           │    ┌──────────────────────────────────┐
                                           ├───>│ TemanErat Gateway (WA / Telegram)│
                                           │    │ & Local TOTP Engine              │
                                           │    └──────────────────────────────────┘
                                           │
                                           │ 5. Async Worker Pool (Graceful Flush)
                                           v
                                 ┌───────────────────┐
                                 │    PostgreSQL     │
                                 │ (Clients, Config, │
                                 │  MFA, Sessions)   │
                                 └─────────┬─────────┘
                                           │
                                     6. Hot-Backup
                                           v
                                 ┌───────────────────┐
                                 │ sso_backup.dump   │
                                 └───────────────────┘
```

---

## 🛡️ Multi-Factor Authentication (MFA)

TemanSatu dilengkapi dengan engine **MFA / 2FA terintegrasi** yang dirancang untuk kebutuhan instansi kesehatan & rumah sakit:

### 1. Saluran Verifikasi (Channels)
- **WhatsApp (TemanErat WA Gateway)**: Mengirimkan kode OTP numerik (default 6 digit) secara instan ke nomor WhatsApp staf medis/pegawai.
- **Telegram (TemanErat Telegram Gateway)**: Mengirimkan kode OTP ke akun Telegram pengguna melalui bot identifier spesifik yang dapat dikonfigurasi via Admin (`mfa_telegram_bot_identifier`, default `sigita-tg-1`).
- **TOTP Authenticator**: Verifikasi berbasis waktu (RFC 6238) menggunakan aplikasi authenticator standar (Google Authenticator, Microsoft Authenticator, Aegis, Bitwarden).
- **Fallback / Channel Switch**: Pengguna dapat berpindah saluran pengiriman OTP secara fleksibel pada halaman tantangan login (`/oauth/v2/mfa/switch`).

### 2. Fitur Keamanan Lanjutan MFA
- **Zero-Width Space Salt**: Menginjeksi karakter spasi tak terlihat (zero-width characters) secara deterministik ke dalam pesan OTP yang dikirim ke WhatsApp/Telegram untuk mencegah scraping teks otomatis dan membedakan duplikasi pesan.
- **Enkripsi Kunci Rahasia TOTP**: Secret TOTP disimpan dalam database PostgreSQL SSO dalam kondisi terenkripsi menggunakan algoritma **AES-256-GCM** berbasis `MASTER_KEY`.
- **Remember Trusted Device**: Pengguna dapat menandai perangkat sebagai tepercaya (`remember_device=true`), menghasilkan cookie aman bertanda tangan HMAC yang membebaskan tantangan MFA selama periode yang ditentukan (default 30 hari).
- **Kebijakan Per-Client (Client MFA Policy)**:
  - `disabled`: Tidak pernah menanyakan MFA untuk client ini.
  - `optional`: Meminta MFA hanya jika user telah mengaktifkan MFA pada akunnya.
  - `enforced`: Wajib MFA untuk seluruh login ke aplikasi client ini (pengguna wajib menyelesaikan atau mendaftarkan MFA).

---

---

## 📋 Persyaratan Sistem

- **Go**: Version `1.22+` / `1.26+`
- **Database Local (SSO Store)**: PostgreSQL 13+
- **Database SIMRS Utama (Target Auth)**: PostgreSQL / MySQL / MariaDB / MS SQL Server
- **Node.js** *(Optional, hanya untuk membangun ulang asset SvelteKit Admin)*: Node.js 18+

---

## ⚙️ Konfigurasi Environment Variables

Buat file `.env` di direktori utama atau set variabel berikut:

| Variabel | Deskripsi | Default |
| :--- | :--- | :--- |
| `PORT` | Port HTTP Server SSO | `8080` |
| `OIDC_ISSUER` | Base URL Issuer OIDC (tanpa trailing slash) | `http://localhost:8080` |
| `MASTER_KEY` | Kunci hex 64 karakter (32-byte) untuk enkripsi AES-256 & HMAC Admin | *(Generated random jika kosong)* |
| `DB_HOST` | Host Database PostgreSQL SSO | `localhost` |
| `DB_PORT` | Port Database PostgreSQL SSO | `5432` |
| `DB_USER` | Username PostgreSQL SSO | `sso_user` |
| `DB_PASSWORD` | Password PostgreSQL SSO | `sso_password` |
| `DB_NAME` | Nama Database PostgreSQL SSO | `sso_db` |
| `DB_SSLMODE` | SSL Mode PostgreSQL (`disable`, `require`, `verify-full`) | `disable` |
| `ADMIN_USER` | Username default Super Admin | `admin` |
| `ADMIN_PASSWORD` | Password fallback default Super Admin | `admin123` |

---

## 🛠️ Cara Install & Penggunaan

### 1. Menjalankan Secara Lokal (Native Go)

1. **Pastikan PostgreSQL sudah berjalan** dan database `sso_db` sudah dibuat:
   ```bash
   createdb -U postgres sso_db
   ```

2. **Jalankan Aplikasi Go**:
   ```bash
   go run ./cmd/server
   ```
   Aplikasi akan melakukan migrasi skema tabel PostgreSQL secara otomatis dan mendengarkan di `http://localhost:8080`.

### 2. Menjalankan Mode Development Hot-Reload (Windows PowerShell)

Proyek ini telah dilengkapi script `dev.ps1` menggunakan Air hot-reloader:

```powershell
.\dev.ps1
```

### 3. Menjalankan via Docker Compose (Dev Container)

```bash
docker-compose -f docker-compose.dev.yml up --build
```

Dashboard Admin dapat diakses langsung via browser di: `http://localhost:8080/admin`

---

## 🌐 Daftar Endpoint API Lengkap

### 1. OpenID Connect & OAuth 2.0 Standard

Standard OpenID Connect 1.0 & OAuth 2.0 endpoints yang digunakan oleh SDK/Library OIDC di aplikasi client.

| Method | Endpoint | Deskripsi | Authentication |
| :--- | :--- | :--- | :--- |
| `GET` | `/.well-known/openid-configuration` | OpenID Discovery Metadata (Issuer, Endpoints, Scopes, Algs) | Public |
| `GET` | `/.well-known/jwks.json` | JSON Web Key Set (Public Keys Ed25519 & RS256) | Public |
| `GET` / `POST` | `/oauth/v2/authorize` | Authorization Endpoint (Mendukung PKCE `S256` & Login Form) | Public |
| `POST` | `/oauth/v2/token` | Token Exchange Endpoint (`grant_type`: `authorization_code`, `refresh_token`, `password`) | Client Secret / Basic Auth |
| `GET` | `/oauth/v2/userinfo` | Mengambil Profil & Claims Pegawai | Bearer Access Token |
| `POST` | `/oauth/v2/revoke` | Revoke Refresh Token / Access Token | Public / Client Auth |
| `POST` | `/api/v1/auth/logout` | Single Logout (SLO) / End Session | Public / Client Auth |

#### Detail Parameter Request `/oauth/v2/authorize`:
- `client_id`: ID Aplikasi Client yang terdaftar (contoh: `app_absensi`)
- `redirect_uri`: URI Pengalihan (wajib cocok dengan Whitelist Client)
- `response_type`: `code`
- `scope`: `openid profile email simrs_medis`
- `state`: String acak pelindung CSRF
- `code_challenge`: Hash SHA-256 dari `code_verifier` (PKCE)
- `code_challenge_method`: `S256`

#### Detail Parameter Request `/oauth/v2/token`:
- `grant_type`: `authorization_code` \| `refresh_token` \| `password`
- `client_id`: ID Aplikasi Client
- `client_secret`: Client Secret Aplikasi
- `code`: Kode Otorisasi (jika `grant_type=authorization_code`)
- `redirect_uri`: URI pengalihan yang sama dengan rute authorize
- `code_verifier`: Verifier PKCE plaintext (jika menggunakan PKCE)
- `refresh_token`: Token refresh (jika `grant_type=refresh_token`)

---

### 2. Multi-Factor Authentication (MFA) Endpoints

Endpoint interaksi alur MFA saat proses login OIDC:

| Method | Endpoint | Form / Query Payload | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/oauth/v2/mfa/verify` | `session_token`, `channel`, `code`, `remember_device` | Memvalidasi kode OTP (WA/Telegram) atau kode TOTP dan meneruskan alur login |
| `GET` | `/oauth/v2/mfa/switch` | `session_token`, `channel` | Beralih saluran pengiriman OTP (`whatsapp`, `telegram`, `totp`) dan memicu pengiriman OTP baru |

---

### 3. System & Health Check

| Method | Endpoint | Deskripsi | Authentication |
| :--- | :--- | :--- | :--- |
| `GET` | `/healthz` | Health check ketersediaan DB Local, DB SIMRS Utama, & Async Audit Queue | Public |
| `GET` | `/` | Auto-redirect ke Dashboard Admin (`/admin`) | Public |

**Contoh Response `GET /healthz`:**
```json
{
  "status": "healthy",
  "timestamp": "2026-08-15T10:00:00Z",
  "checks": {
    "postgres_local": "ok",
    "simrs_primary_db": "online",
    "audit_queue_size": 0
  },
  "session_continuity": false
}
```

---

### 4. Admin Management REST API

Semua endpoint dilindungi oleh Middleware Autentikasi Admin (`X-Admin-Token` Header atau Cookie Session `sso_admin_session`), kecuali `/login` dan `/logout`.

#### 🔐 Auth Admin
| Method | Endpoint | Body Payload | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/admin/login` | `{"username":"admin","password":"..."}` | Login Admin & dapatkan HMAC Session Token/Cookie |
| `POST` | `/api/v1/admin/logout` | `-` | Logout Admin & Hapus Cookie Session |
| `GET` | `/api/v1/admin/me` | `-` | Dapatkan status profil admin yang sedang aktif |
| `POST` | `/api/v1/admin/change-password` | `{"current_password":"...","new_password":"..."}` | Ganti Password Dashboard Admin |

#### 📱 Manajemen Application Clients (OIDC Clients)
| Method | Endpoint | Body Payload | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/clients` | `-` | Mengambil daftar seluruh OIDC Client yang terdaftar |
| `POST` | `/api/v1/admin/clients` | `{"id":"app1","name":"App Absensi","redirect_uris":["https://..."],"mfa_policy":"optional"}` | Mendaftarkan OIDC Client baru (menghasilkan Secret Argon2id) |
| `PUT` | `/api/v1/admin/clients/{id}` | `{"name":"App Absensi Updated","redirect_uris":["https://..."],"mfa_policy":"enforced"}` | Perbarui nama, Whitelist Redirect URIs, & Kebijakan MFA Client |
| `PATCH` | `/api/v1/admin/clients/{id}/status` | `{"is_active":1}` | Mengaktifkan (1) atau Menonaktifkan (0) OIDC Client |
| `POST` | `/api/v1/admin/clients/{id}/rotate-secret` | `-` | Regenerasi Client Secret baru |
| `POST` | `/api/v1/admin/clients/{id}/refresh-token` | `{"user_id":"pegawai-1","nama":"Dr. Andi"}` | Generasi Token Pengujian (Access Token, ID Token, Refresh Token) |

#### 🛡️ Multi-Factor Authentication (MFA) Settings & Gateway
| Method | Endpoint | Body Payload | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/mfa-settings` | `-` | Mengambil konfigurasi engine MFA global, setting OTP, dan gateway TemanErat |
| `POST` | `/api/v1/admin/mfa-settings` | `MFASettings` JSON | Menyimpan konfigurasi MFA global, masa berlaku OTP, salt, dan gateway TemanErat |

**Daftar Parameter `MFASettings`:**
- `mfa_global_enabled`: boolean (saklar utama MFA)
- `mfa_default_channel`: string (`whatsapp`, `telegram`, atau `totp`)
- `mfa_otp_length`: integer (panjang kode OTP, default 6)
- `mfa_otp_expiry_minutes`: integer (masa berlaku OTP, default 5)
- `mfa_max_attempts`: integer (maksimal percobaan verifikasi salah sebelum di-lock)
- `mfa_otp_zerowidth_salt_enabled`: boolean (injeksi karakter salt transparan)
- `mfa_temanerat_api_url`: string (URL API gateway TemanErat)
- `mfa_temanerat_api_key`: string (API key / bearer token TemanErat)
- `mfa_telegram_bot_identifier`: string (identifier bot Telegram TemanErat, default `sigita-tg-1`)
- `mfa_otp_template`, `mfa_whatsapp_template`, `mfa_telegram_template`: template teks pesan notifikasi

#### 🔌 SIMRS Database Connector Settings
| Method | Endpoint | Body Payload | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/simrs-config` | `-` | Mengambil konfigurasi koneksi DB SIMRS Utama saat ini |
| `POST` | `/api/v1/admin/simrs-config` | `SIMRSConfig` JSON *(password terenkripsi AES-256)* | Menyimpan konfigurasi koneksi DB SIMRS Utama |
| `POST` | `/api/v1/admin/simrs-config/test` | `SIMRSConfig` JSON | Uji koneksi langsung ke DB SIMRS Utama |
| `POST` | `/api/v1/admin/simrs-config/inspect-columns`| `{"username":"nip123","password":"..."}` | Inspeksi kolom query DB SIMRS & pemetaan claims |

#### 🛡️ Keamanan, Audit Logs & Pemeliharaan System
| Method | Endpoint | Query / Body Payload | Deskripsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/admin/sessions` | `?page=1&limit=25&search=...&status=active&start_date=2026-08-01&end_date=2026-08-15` | Mengambil daftar sesi & refresh token (Mendukung Filter Periode, Status, Pagination) |
| `POST` | `/api/v1/admin/sessions/revoke` | `{"id":"rt_..."}` | Mencabut (revoke) sesi refresh token spesifik |
| `GET` | `/api/v1/admin/security-settings` | `-` | Ambil pengaturan expiry token & exponential backoff |
| `POST` | `/api/v1/admin/security-settings` | `{"jwt_expiry_minutes":15,"refresh_expiry_days":7,...}` | Simpan pengaturan keamanan & masa aktif token |
| `POST` | `/api/v1/admin/force-logout` | `{"user_id":"pegawai-1"}` | Global Force Logout (mencabut seluruh sesi & refresh token user) |
| `POST` | `/api/v1/admin/backup` | `-` | Pemicu Instant Online Hot Backup PostgreSQL |
| `GET` | `/api/v1/admin/audit-logs` | `?page=1&limit=25&search=...&start_date=2026-08-01&end_date=2026-08-15&format=csv` | Mengambil daftar Audit Logs (Mendukung Filter Periode, Category, Severity, Pagination & Ekspor CSV) |
| `POST` | `/api/v1/admin/simulate-auth` | `{"client_id":"...","username":"...","password":"..."}` | Simulasi alur autentikasi OIDC & pengujian kredensial SIMRS |

---

### 5. Web Admin Dashboard

- **Rute**: `/admin`
- **Tampilan**: Single Page Application (SPA) berbasis SvelteKit & Tailwind CSS yang di-embed secara langsung ke dalam binary aplikasi Go.
- **Fitur Interface**:
  - Dashboard Overview Metrics & System Health Indicator.
  - Client Manager (Tambah Client, Set Whitelist Redirect URIs, Atur Client MFA Policy, Copy Secret, Rotate Secret).
  - MFA Configuration Manager (Saklar Global MFA, Pengaturan OTP Length/Expiry, Gateway TemanErat WA/Telegram, Template Pesan, Bot Identifier `sigita-tg-1`).
  - SIMRS Database Connector (Form Driver Postgres/MySQL/MSSQL, Test Connection, Custom Query Inspector).
  - Simulator Auth & Token Inspection.
  - Sesi & Token Aktif (Real-time Active Session Monitor & Revocation).
  - Security Settings & Global Force Logout.
  - Audit Trail Viewer & Export to CSV.

---

## 🔑 Panduan Integrasi Aplikasi Client (OIDC / OAuth2)

### Contoh Alur Autentikasi (Authorization Code + PKCE)

#### 1. Arahkan Pengguna ke Halaman Authorize
Aplikasi client mengarahkan browser pengguna ke endpoint `/oauth/v2/authorize`:

```text
GET http://localhost:8080/oauth/v2/authorize?
  response_type=code&
  client_id=app_absensi_rs&
  redirect_uri=https%3A%2F%2Fabsensi.rs.id%2Fcallback&
  scope=openid%20profile%20simrs_medis&
  state=xyz123&
  code_challenge=E9Mel-2ug26G964a05C_Z_GG_Z8358483_23&
  code_challenge_method=S256
```

#### 2. Penukaran Code Menjadi Token (`POST /oauth/v2/token`)

Setelah pengguna berhasil login, SSO akan mengarahkan kembali ke `redirect_uri` dengan membawa `code`. Client melakukan POST dari backend client:

```bash
curl -X POST http://localhost:8080/oauth/v2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=authorization_code" \
  -d "client_id=app_absensi_rs" \
  -d "client_secret=secret_app_absensi" \
  -d "code=AUTHORIZATION_CODE_DARI_REDIRECT" \
  -d "redirect_uri=https://absensi.rs.id/callback" \
  -d "code_verifier=VERIFIER_PLAIN_PKCE"
```

**Response Token:**
```json
{
  "access_token": "eyJhbGciOiJFZERTQSI...",
  "token_type": "Bearer",
  "expires_in": 900,
  "refresh_token": "rf_8f9a2b...",
  "id_token": "eyJhbGciOiJFZERTQSI...",
  "scope": "openid profile simrs_medis"
}
```

#### 3. Mengambil Profil User (`GET /oauth/v2/userinfo`)

```bash
curl -X GET http://localhost:8080/oauth/v2/userinfo \
  -H "Authorization: Bearer ACCESS_TOKEN_KLIEN"
```

---

## 👤 Klaim Profil Pegawai & Pemetaan Token (User Claims)

TemanSatu mendukung konfigurasi **Field Mapping** fleksibel dari kolom database SIMRS ke klaim token OIDC. Melalui antarmuka Admin UI atau konfigurasi konektor SIMRS, setiap kolom yang diambil dari SIMRS dapat dipetakan dan dikonfigurasi target penyimpanannya:
- **ID Token (`in_id_token`)**: Klaim disematkan ke dalam payload JWT `id_token`. Cocok untuk data identitas pengguna pada aplikasi client/frontend.
- **Access Token (`in_access_token`)**: Klaim disematkan ke dalam payload JWT `access_token`. Memungkinkan Resource Server / Microservices backend melakukan otorisasi tanpa perlu memanggil endpoint `/userinfo`.
- **UserInfo (`in_userinfo`)**: Klaim dikembalikan saat client memanggil endpoint `GET /oauth/v2/userinfo`.

### 1. Contoh Payload ID Token (`id_token`)
ID Token memuat klaim standar OIDC beserta klaim kustom SIMRS yang dicentang `ID Token`:
```json
{
  "sub": "PEG-10293",
  "iss": "http://localhost:8080",
  "aud": "app_absensi_rs",
  "exp": 1786712400,
  "iat": 1786708800,
  "jti": "b52d9aef-89a1-4322-91f1-33e9d89a291f",
  "username": "dr_andi",
  "nama": "dr. Andi Pratama, Sp.PD",
  "nik": "3271011234560001",
  "sip": "SIP-DOKTER/2026/08/1002",
  "kategori_pegawai": "DOKTER",
  "unit_id": "UNIT-POLI-DALAM",
  "is_dpjp": true
}
```

### 2. Contoh Payload Access Token JWT (`access_token`)
Access Token bertipe JWT yang ditandatangani asimetris sehingga Resource Server dapat memvalidasinya secara stateless via JWKS (`/.well-known/jwks.json`). Klaim yang dicentang `Access Token (JWT)` akan ikut disematkan ke dalamnya:
```json
{
  "sub": "PEG-10293",
  "iss": "http://localhost:8080",
  "aud": "app_absensi_rs",
  "client_id": "app_absensi_rs",
  "token_use": "access_token",
  "scope": "openid profile simrs_medis",
  "exp": 1786712400,
  "iat": 1786708800,
  "jti": "e9b41dc2-358b-4ef9-bb20-1a73ca591f42",
  "kategori_pegawai": "DOKTER",
  "unit_id": "UNIT-POLI-DALAM",
  "is_dpjp": true
}
```

---

## 📜 Lisensi & Pengembang

Dikembangkan oleh **TemanSatu Health Platform**. All Rights Reserved.
