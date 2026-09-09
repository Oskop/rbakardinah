# Implementation Plan - REST API Endpoint Pagu RBA & Autentikasi Aman

Rencana implementasi pembuatan layanan REST API untuk menyediakan data Pagu RBA Rumah Sakit yang akan diakses oleh aplikasi pihak ketiga (aplikasi buatan rekanan/teman pengguna), lengkap dengan sistem autentikasi aman, filter parameter lengkap, pagination, dan dokumentasi integrasi.

---

## 1. Spesifikasi Teknis Endpoint Pagu

### 1.1 Endpoint Overview
- **Method**: `GET`
- **Path**: `/api/v1/pagu`
- **Autentikasi**: `Bearer Token` atau Header `X-API-KEY`
- **Rate Limit**: 60 requests/minute (Throttle protection)
- **Format Data**: JSON (UTF-8)

### 1.2 Rincian Data Response (8 Atribut Wajib Sesuai Permintaan)
Setiap item data pagu yang dikembalikan akan menyajikan 8 atribut rincian berikut:
1. `year` (`tahun`): Tahun anggaran RBA (e.g. `2026`)
2. `period` (`periode`): Nama periode RBA (e.g. `"Murni"`, `"Perubahan"`)
3. `status_rba`: Status global RBA (e.g. `"Draft"`, `"Approved"`, `"Open"`)
4. `kode_kelompok_belanja`: Kode kelompok belanja (e.g. `"KB01"`, `"5.1.02"`)
5. `nama_kelompok_belanja`: Nama kelompok belanja (e.g. `"Belanja Operasi"`)
6. `kode_rekening`: Kode nomor rekening belanja (e.g. `"5.1.02.02.02.0005"`)
7. `nama_rekening`: Uraian / nama nomor rekening (e.g. `"Belanja Iuran Jaminan Kesehatan bagi Non ASN"`)
8. `nominal_pagu`: Nilai nominal pagu dalam angka integer (e.g. `100000000`), dilengkapi dengan `nominal_pagu_formatted` (`"Rp 100.000.000"`).

### 1.3 Struktur Response JSON Standar
```json
{
  "success": true,
  "message": "Data pagu RBA berhasil diambil.",
  "meta": {
    "total_records": 66,
    "total_nominal_pagu": 177000000000,
    "total_nominal_pagu_formatted": "Rp 177.000.000.000",
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total_pages": 2,
      "has_more": true
    },
    "filters_applied": {
      "year": 2026,
      "period": "Murni"
    }
  },
  "data": [
    {
      "id": 44,
      "year": 2026,
      "period": "Murni",
      "status_rba": "Draft",
      "kode_kelompok_belanja": "KB01",
      "nama_kelompok_belanja": "Belanja Operasi",
      "kode_rekening": "5.1.02.02.02.0005",
      "nama_rekening": "Belanja Iuran Jaminan Kesehatan bagi Non ASN",
      "nominal_pagu": 100000000,
      "nominal_pagu_formatted": "Rp 100.000.000",
      "updated_at": "2026-09-08T04:20:00Z"
    }
  ]
}
```

---

## 2. Parameter Filter yang Didukung

API akan mendukung parameter filter melalui query string pada URL untuk seluruh atribut data yang diminta:

| Parameter | Alias Parameter | Tipe Data | Keterangan | Contoh |
| :--- | :--- | :--- | :--- | :--- |
| `year` | `tahun` | Integer | Filter tahun RBA spesifik | `?year=2026` |
| `period` | `periode` | String | Filter nama periode RBA | `?period=Murni` |
| `period_id` | - | Integer | Filter ID periode | `?period_id=1` |
| `status` | `status_rba` | String | Filter status global RBA | `?status=Draft` |
| `kode_kelompok_belanja` | `kelompok_belanja_kode` | String | Filter kode kelompok belanja | `?kode_kelompok_belanja=KB01` |
| `nama_kelompok_belanja` | `kelompok_belanja_name` | String | Filter nama kelompok belanja (search partial/like) | `?nama_kelompok_belanja=Operasi` |
| `kode_rekening` | `account_code` | String | Filter kode rekening (exact atau prefix) | `?kode_rekening=5.1.02` |
| `nama_rekening` | `account_name` | String | Filter uraian rekening (search partial/like) | `?nama_rekening=Jaminan Kesehatan` |
| `min_pagu` | - | Numeric | Filter nominal pagu minimal | `?min_pagu=50000000` |
| `max_pagu` | - | Numeric | Filter nominal pagu maksimal | `?max_pagu=500000000` |
| `q` | `search` | String | Pencarian global cepat (mencari pada nama rekening & kelompok belanja) | `?q=Gaji` |
| `per_page` | `limit` | Integer | Jumlah data per halaman (default: 50, max: 200) | `?per_page=100` |
| `page` | - | Integer | Nomor halaman pagination | `?page=2` |
| `all` | `paginate=false` | Boolean | Mengambil seluruh data tanpa pagination | `?all=true` |
| `sort_by` | - | String | Kolom pengurutan (`code`, `name`, `nominal_pagu`, `year`) | `?sort_by=code` |
| `sort_dir` | - | String | Arah urutan (`asc` atau `desc`, default `asc`) | `?sort_dir=asc` |

---

## 3. Sistem Keamanan & Autentikasi (API Key / Bearer Token)

Untuk integrasi antar aplikasi (Server-to-Server / M2M), metode paling aman, cepat, dan standar adalah **API Key / Secret Token Authentication**:

### 3.1 Skema Autentikasi
1. Klien mengirimkan API Key pada setiap request melalui salah satu cara:
   - Header HTTP Authorization: `Authorization: Bearer <API_KEY>` (Rekomendasi standar REST API)
   - Atau Header Kustom: `X-API-KEY: <API_KEY>`
2. Format API Key: Menggunakan format terstruktur ber-prefix:
   `rba_live_<random_48_characters_hex/alphanumeric>`
3. **Penyimpanan Aman di Database**:
   - Token plain-text hanya ditampilkan **satu kali** saat dibuat.
   - Di database, yang disimpan adalah hash SHA-256 (`hash('sha256', $plainToken)`) dan prefix identifikasi (`rba_live_a1b2c3d4...`).
   - Jika database dicadangkan atau dibaca pihak ketiga, kunci token tidak dapat didekripsi.

### 3.2 Tabel Master `api_clients`
Tabel baru untuk mencatat aplikasi rekanan yang diberi izin akses:
- `id` (Primary Key)
- `name` (String, e.g. "Aplikasi SIMRS Teman", "Aplikasi Eksternal A")
- `key_prefix` (String, 16 karakter pertama untuk identifikasi)
- `api_key_hash` (String, SHA-256 hash)
- `is_active` (Boolean, default true)
- `expires_at` (Timestamp, nullable)
- `last_used_at` (Timestamp, nullable)
- `last_used_ip` (String, nullable)
- `created_at`, `updated_at`

### 3.3 Command Artisan untuk Administrator
Disediakan command CLI sederhana untuk Administrator RSUD mengelola API Key:
1. **Membuat Klien Baru**:
   ```bash
   php artisan api:client-create "Aplikasi SIMRS Teman"
   ```
   *Output*: Menghasilkan API Key rahasia yang langsung bisa diberikan ke teman.
2. **Melihat Daftar Klien & Status Akses**:
   ```bash
   php artisan api:client-list
   ```
   *Output*: Tabel daftar klien, status aktif, tanggal pembuatan, dan kapan terakhir kali mengakses API.
3. **Mencabut / Menonaktifkan Akses Klien**:
   ```bash
   php artisan api:client-revoke {id_atau_prefix}
   ```

---

## 4. Proposed Changes

### Database & Migrations

#### [NEW] `database/migrations/2026_09_09_000001_create_api_clients_table.php`
- Membuat skema tabel `api_clients`.

---

### Models & Middleware

#### [NEW] `app/Models/ApiClient.php`
- Model Eloquent untuk data klien API.
- Helper method `verifyKey(string $plainKey): bool` dan `generateKey(string $name): array`.

#### [NEW] `app/Http/Middleware/VerifyApiKey.php`
- Middleware untuk memeriksa validitas Bearer token / `X-API-KEY`.
- Memberikan response `401 Unauthorized` terstruktur jika token tidak ada atau tidak valid.
- Memberikan response `403 Forbidden` jika klien dinonaktifkan (`is_active = false`) atau kedaluwarsa.

#### [MODIFY] [`bootstrap/app.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/bootstrap/app.php)
- Daftarkan routing API: `api: __DIR__ . '/../routes/api.php'`.
- Daftarkan alias middleware `'api.key' => \App\Http\Middleware\VerifyApiKey::class`.

---

### Controller & Routing

#### [NEW] `app/Http/Controllers/Api/V1/PaguApiController.php`
- Endpoint controller method `index(Request $request)`.
- Eager loading relasi: `header.period`, `accountCode.kelompokBelanja`.
- Implementasi seluruh parameter filter, search, paginasi, dan kalkulasi total nominal pagu.

#### [NEW] `routes/api.php`
- Mendefinisikan prefix route `v1`:
  ```php
  Route::prefix('v1')->middleware(['api.key', 'throttle:60,1'])->group(function () {
      Route::get('/pagu', [\App\Http\Controllers\Api\V1\PaguApiController::class, 'index']);
  });
  ```

---

### Artisan CLI Tools

#### [NEW] `app/Console/Commands/CreateApiClientCommand.php`
- Command `api:client-create {name}`.

#### [NEW] `app/Console/Commands/ListApiClientsCommand.php`
- Command `api:client-list`.

#### [NEW] `app/Console/Commands/RevokeApiClientCommand.php`
- Command `api:client-revoke {id}`.

---

### Documentation for External Developers

#### [NEW] `documentation/API_PAGU_INTEGRATION_GUIDE.md`
- Panduan lengkap integrasi API untuk teman pengguna:
  - Base URL & Autentikasi Header
  - Parameter filter lengkap dengan contoh URL
  - Contoh Request: cURL, Javascript (Fetch/Axios), PHP (Guzzle/cURL), Python
  - Contoh JSON Response sukses dan error codes (401, 403, 404, 429, 500).

---

## 5. Verification Plan

### Automated Tests (`tests/Feature/Api/PaguApiTest.php`)
1. **Otentikasi**:
   - Request tanpa token menghasilkan `401 Unauthorized`.
   - Request dengan token salah menghasilkan `401 Unauthorized`.
   - Request dengan client non-aktif menghasilkan `403 Forbidden`.
   - Request dengan Bearer token yang valid menghasilkan `200 OK`.
2. **Kesesuaian Data**:
   - Memastikan response memuat seluruh 8 atribut data wajib (`year`, `period`, `status_rba`, `kode_kelompok_belanja`, `nama_kelompok_belanja`, `kode_rekening`, `nama_rekening`, `nominal_pagu`).
3. **Filter**:
   - Test filter berdasarkan `year`, `period`, `status_rba`, `kode_kelompok_belanja`, `nama_kelompok_belanja`, `kode_rekening`, `nama_rekening`, dan `nominal_pagu`.
4. **Pagination**:
   - Test respon pagination membatasi jumlah data sesuai `per_page`.
   - Test opsi `all=true` mengembalikan seluruh data.
5. **CLI Command**:
   - Test `api:client-create` sukses men-generate token.

### Manual Verification
1. Menjalankan command `php artisan api:client-create "Aplikasi Rekanan Test"`.
2. Menguji endpoint via HTTP request simulasi (cURL / script) menggunakan token yang dihasilkan.
3. Memastikan semua pengujian otomatis `php artisan test` tetap lulus 100%.
