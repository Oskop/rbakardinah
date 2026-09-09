# Walkthrough: Pembuatan REST API Endpoint Pagu RBA & Autentikasi Aman

Layanan **REST API Endpoint Pagu RBA** (`GET /api/v1/pagu`) yang dilengkapi sistem autentikasi aman berstandar industri (API Key / Bearer Token), filter parameter lengkap, pagination, proteksi rate limiting, serta perintah manajemen CLI telah selesai diimplementasikan dengan sukses dan diverifikasi 100%.

---

## 1. Ringkasan Implementasi

### 1.1 Endpoint Data Pagu (`GET /api/v1/pagu`)
Menyediakan seluruh 8 rincian data pagu yang diminta dalam format JSON:
1. `year` (`tahun`): Tahun RBA (e.g. `2026`)
2. `period` (`periode`): Nama periode RBA (e.g. `"Murni"`, `"Perubahan"`)
3. `status_rba`: Status global RBA (e.g. `"Draft"`, `"Approved"`, `"Open"`)
4. `kode_kelompok_belanja`: Kode kelompok belanja (e.g. `"KB01"`, `"5.1.02"`)
5. `nama_kelompok_belanja`: Nama kelompok belanja (e.g. `"Belanja Operasi"`)
6. `kode_rekening`: Kode nomor rekening belanja (e.g. `"5.1.02.02.02.0005"`)
7. `nama_rekening`: Uraian / nama nomor rekening (e.g. `"Belanja Iuran Jaminan Kesehatan bagi Non ASN"`)
8. `nominal_pagu`: Nilai numerik pagu (e.g. `100000000`) dan format IDR (`nominal_pagu_formatted`: `"Rp 100.000.000"`).

### 1.2 Dukungan Parameter Filter Komprehensif
Mendukung filter query string URL untuk seluruh atribut:
- `?year=2026` & `?period=Murni`
- `?status=Approved`
- `?kode_kelompok_belanja=KB01` & `?nama_kelompok_belanja=Operasi`
- `?kode_rekening=5.1.02` & `?nama_rekening=Jaminan`
- `?min_pagu=50000000` & `?max_pagu=500000000` & `?exact_pagu=100000000`
- `?q=Gaji` (pencarian cepat)
- `?per_page=50&page=1` (pagination) dan `?all=true` (ambil seluruh data).

### 1.3 Sistem Autentikasi & Keamanan (API Key / Bearer Token)
- Mendukung header standar `Authorization: Bearer <API_KEY>` atau `X-API-KEY: <API_KEY>`.
- Token berformat `rba_live_<random_48_karakter>`.
- Token disimpan secara aman dalam bentuk hash SHA-256 di tabel `api_clients` (tidak bisa dicuri jika database bocor).
- Dilengkapi rate limiting bawaan (`throttle:60,1`) untuk mencegah flooding.

### 1.4 Artisan CLI Tools untuk Administrator
- `php artisan api:client-create "Nama Aplikasi Klien" [--expires-in-days=365]`: Otomatis men-generate token aman untuk diserahkan ke rekanan.
- `php artisan api:client-list`: Menampilkan tabel daftar klien dan tanggal pemanggilan terakhir.
- `php artisan api:client-revoke {id}`: Mencabut / menonaktifkan akses klien seketika.

---

## 2. Berkas-Berkas Baru & Modifikasi

| Tipe | Berkas | Deskripsi |
| :--- | :--- | :--- |
| **Migration** | [database/migrations/2026_09_09_000001_create_api_clients_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_09_000001_create_api_clients_table.php) | Skema tabel penyimpanan hash API Key |
| **Model** | [app/Models/ApiClient.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/ApiClient.php) | Model Eloquent dengan method helper `createWithToken` & `findByPlainToken` |
| **Middleware** | [app/Http/Middleware/VerifyApiKey.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Middleware/VerifyApiKey.php) | Verifikasi Bearer token / X-API-KEY, logging last_used_at, error 401/403 |
| **Controller** | [app/Http/Controllers/Api/V1/PaguApiController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Api/V1/PaguApiController.php) | Endpoint controller `index()` dengan filter komprehensif & aggregasi |
| **Routes** | [routes/api.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/api.php) | Definisi rute API v1 terproteksi `api.key` dan `throttle:60,1` |
| **Bootstrap** | [bootstrap/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/bootstrap/app.php) | Pendaftaran routing API dan alias middleware `'api.key'` |
| **CLI Commands** | [app/Console/Commands/CreateApiClientCommand.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/CreateApiClientCommand.php)<br>[app/Console/Commands/ListApiClientsCommand.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/ListApiClientsCommand.php)<br>[app/Console/Commands/RevokeApiClientCommand.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/RevokeApiClientCommand.php) | Alat baris perintah untuk generate, list, dan revoke token API |
| **Testing** | [tests/Feature/Api/PaguApiTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Api/PaguApiTest.php) | 12 test cases menyeluruh mencakup otentikasi, filter, pagination, commands |
| **Dokumentasi** | [documentation/API_PAGU_INTEGRATION_GUIDE.md](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/documentation/API_PAGU_INTEGRATION_GUIDE.md) | Panduan integrasi cURL, JS, PHP, Python untuk teman pengguna |

---

## 3. Hasil Pengujian & Verifikasi

- **Automated Feature Tests API (`PaguApiTest`)**:
  - `✓ public ping endpoint returns ok`: ✅ **PASS**
  - `✓ protected endpoint without key returns 401`: ✅ **PASS**
  - `✓ protected endpoint with invalid key returns 401`: ✅ **PASS**
  - `✓ protected endpoint with inactive key returns 403`: ✅ **PASS**
  - `✓ protected endpoint with expired key returns 403`: ✅ **PASS**
  - `✓ protected endpoint with valid bearer token returns all 8 attributes`: ✅ **PASS**
  - `✓ protected endpoint with x api key header succeeds`: ✅ **PASS**
  - `✓ can filter pagus by year period and status`: ✅ **PASS**
  - `✓ can filter pagus by account code and kelompok belanja`: ✅ **PASS**
  - `✓ can filter pagus by amount range and search`: ✅ **PASS**
  - `✓ pagination and all flag works`: ✅ **PASS**
  - `✓ artisan commands for api client management`: ✅ **PASS**
  - **Hasil**: **12 passed (104 assertions)**.
- **Rangkaian Lengkap Pengujian Sistem (`php artisan test`)**:
  - **182 passed (916 assertions)**, 100% sukses tanpa kegagalan!
