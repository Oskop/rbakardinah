# Implementation Plan: Fitur Dokumentasi REST API (Swagger UI & Redoc)

Rencana implementasi portal dokumentasi interaktif untuk REST API SIPAKAR RSUD Kardinah, menggabungkan kemampuan **Uji Coba Langsung (Try It Out via Swagger UI)** dan **Tampilan Dokumentasi Lengkap 3-Kolom (Redoc)** berbasis spesifikasi **OpenAPI 3.0**, serta memuat seluruh materi panduan dari `documentation/API_PAGU_INTEGRATION_GUIDE.md`.

---

## 1. Analisis & Jawaban Terkait Keraguan Bagian 7 (Perintah CLI Administrator)

### Rekomendasi Solusi:
> [!IMPORTANT]
> **Bagian 7 (Perintah CLI) SEBAIKNYA TIDAK DIMASUKKAN ke Dokumentasi API Pengembang / Publik.**
> 
> **Alasan**:
> 1. **Keamanan & Prinsip Least Privilege**: Dokumentasi API ditujukan untuk dibaca oleh pengembang pihak ketiga / rekanan. Mereka tidak memiliki akses SSH / terminal ke server RSUD Kardinah. Menampilkan perintah internal server (`php artisan api:client-create`, dll) berpotensi membingungkan pengembang eksternal dan mengekspos mekanisme internal server.
> 2. **Pengalaman Pengembang (DX)**: Yang dibutuhkan oleh pengembang eksternal bukanlah "bagaimana cara server membuat token", melainkan **"bagaimana cara kami (rekanan) mendapatkan token tersebut"**.
> 
> **Solusi Pemisahan yang Tepat**:
> - **Pada Dokumentasi API Pengembang**: Bagian 7 diganti dengan sub-bab **"📋 Prosedur Memperoleh API Key"**:
>   > *"Untuk memperoleh API Key resmi, pengembang aplikasi rekanan dapat mengajukan permohonan ke Tim TI / Bagian Perencanaan RSUD Kardinah dengan menyertakan nama aplikasi dan kontak penanggung jawab teknis."*
> - **Pada Panel Internal Administrator**: Panduan perintah CLI (`api:client-create`, `api:client-list`, `api:client-revoke`, `api:logs-prune`) kami letakkan di dalam kartu panduan cepat (*Quick Reference Card*) pada halaman **Monitoring API Administrator** (`/admin/api-logs`) yang hanya dapat dilihat oleh user ber-role Administrator.

---

## 2. Solusi Arsitektural: Dual-View Portal (Swagger UI + Redoc)

Untuk memenuhi kedua referensi yang diinginkan:
1. **Swagger UI**: Digunakan ketika pengembang ingin melakukan **uji coba pemanggilan API langsung di browser (*live execution / Try it out*)**, menguji token mereka, memasukkan filter query, dan melihat hasil respon JSON seketika.
2. **Redoc**: Digunakan ketika pengembang ingin **membaca dokumentasi secara terstruktur dan komprehensif** dengan layout 3 kolom khas Redocly (Daftar Isi di kiri, Penjelasan Schema di tengah, dan Contoh Kode / Payload di kanan).

Kedua tampilan tersebut akan disatukan dalam **satu portal dokumentasi terpadu** dengan **View Switcher / Tab**:
- `[ ⚡ Swagger UI (Interaktif & Uji Coba) ]`
- `[ 📖 Redoc (Dokumentasi Lengkap 3-Kolom) ]`
- Tombol `[ 📥 Unduh OpenAPI JSON ]` (sehingga rekanan dapat langsung mengimpor spesifikasi API ke Postman, Insomnia, atau Swagger Editor).

Keduanya menggunakan **Single Source of Truth**, yaitu endpoint spesifikasi **OpenAPI 3.0 (`/api/v1/openapi.json`)**, sehingga data selalu sinkron 100%.

---

## 3. Rincian Muatan Konten (Berdasarkan `API_PAGU_INTEGRATION_GUIDE.md`)

Spesifikasi OpenAPI dan dokumentasi akan memuat seluruh komponen dari file panduan yang telah dibuat:
1. **Header & Informasi Sistem**:
   - Judul: REST API Pagu RBA RSUD Kardinah Kota Tegal.
   - Versi: `1.0.0`.
   - Base URL: Dinamis mengikuti server host (e.g. `http://10.102.10.180:8000/api/v1`).
   - Rate Limiting: 60 request/menit per API Key.
2. **Mekanisme Autentikasi**:
   - Skema `BearerAuth` (Header `Authorization: Bearer <token>`).
   - Skema `ApiKeyAuth` (Header `X-API-KEY: <token>`).
3. **Endpoint yang Didokumentasikan**:
   - `GET /api/v1/ping`: Endpoint uji konektivitas publik tanpa autentikasi.
   - `GET /api/v1/pagu`: Endpoint utama data pagu dengan **seluruh 17 parameter filter query** (lengkap dengan tipe data, deskripsi, contoh nilai, dan alias).
4. **Contoh Kode Multibahasa Tersemat**:
   - cURL (Terminal / Bash)
   - JavaScript (Fetch API / Node.js)
   - PHP (GuzzleHttp / cURL)
   - Python (Requests library)
5. **Model Skema JSON**:
   - Respon Berhasil HTTP 200 OK (Struktur `meta`, `pagination`, `filters_applied`, dan array `data`).
   - Respon Error HTTP 401 Unauthorized (Token salah/kosong).
   - Respon Error HTTP 403 Forbidden (Token non-aktif/kadaluwarsa).
   - Respon Error HTTP 429 Too Many Requests (Rate limit).
   - Respon Error HTTP 500 Internal Server Error.
6. **Prosedur Pengajuan API Key** (pengganti bagian 7).

---

## 4. Rencana Perubahan Komponen & File

### 4.1 Backend & Controller Baru
#### [NEW] [ApiDocumentationController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Api/ApiDocumentationController.php)
- Method `openapi()`: Menghasilkan spesifikasi standar **OpenAPI 3.0** dalam format JSON yang valid, terstruktur, dan kaya metadata.
- Method `index(Request $request)`: Merender view portal dokumentasi dengan pilihan mode tampilan (`swagger` atau `redoc`).

### 4.2 Tampilan Antarmuka (Views)
#### [NEW] [resources/views/api_documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/api_documentation/index.blade.php)
- Halaman portal dokumentasi API modern yang responsif.
- Bilah atas (*Top Bar*):
  - Logo SIPAKAR & Badge Versi API v1.
  - Switcher View: `[ ⚡ Swagger UI (Uji Coba API) ]` dan `[ 📖 Redoc (Dokumentasi Lengkap) ]`.
  - Tombol aksi: `[ 📥 OpenAPI JSON ]`, `[ 📋 Petunjuk Integrasi ]`, dan `[ 🏠 Kembali ke SIPAKAR ]`.
- Area Konten:
  - **Jika Mode Swagger**: Memuat bundle Swagger UI 5.x via CDN, terkonfigurasi dengan URL spec, mendukung authorize Bearer & X-API-KEY, serta live execution "Try it out".
  - **Jika Mode Redoc**: Memuat Redoc standalone via CDN, menampilkan 3-panel layout GitHub Redocly style yang bersih dan rapi.

#### [MODIFY] [resources/views/documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/documentation/index.blade.php)
- Menambahkan tab navigasi ke-3 pada header dokumentasi sistem yang sudah ada:
  - `[ 📘 Panduan Web ]`
  - `[ 📄 Manual Book PDF ]`
  - `[ 🌐 REST API (Swagger & Redoc) ]` (Mengarahkan langsung ke portal API docs).

#### [MODIFY] [resources/views/admin/api_logs/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/api_logs/index.blade.php)
- Menambahkan link/tombol cepat menuju Dokumentasi API pada panel Monitoring Administrator.
- Menyediakan kartu petunjuk ringkas perintah CLI Administrator (`api:client-create`, `api:client-list`, `api:client-revoke`, `api:logs-prune`) khusus untuk Administrator.

### 4.3 Routing
#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute publik:
  - `GET /api/documentation` -> `ApiDocumentationController@index` (name: `api.documentation`)
  - `GET /api/v1/openapi.json` -> `ApiDocumentationController@openapi` (name: `api.openapi.json`)
  - Alias pendukung jika dibuka dari URL `/documentation/api`.

### 4.4 Automated Testing
#### [NEW] [tests/Feature/Api/ApiDocumentationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Api/ApiDocumentationTest.php)
- Menguji endpoint `/api/v1/openapi.json` mengembalikan status 200 dengan format JSON OpenAPI 3.0 yang valid.
- Menguji spesifikasi OpenAPI memuat seluruh path (`/api/v1/ping`, `/api/v1/pagu`), skema sekuriti, dan parameter filter.
- Menguji portal `/api/documentation` dapat diakses baik dengan parameter `?view=swagger` maupun `?view=redoc`.
- Menguji link tab dokumentasi REST API diakses dengan lancar tanpa error.

---

## 5. Rencana Verifikasi & Pengujian

### 5.1 Automated Tests
Menjalankan feature test khusus dokumentasi dan memastikan seluruh test suite aplikasi tetap 100% lulus:
```bash
php artisan test --filter=ApiDocumentationTest
php artisan test
```

### 5.2 Manual Browser Verification
1. Buka URL `/api/documentation?view=swagger`:
   - Pastikan antarmuka Swagger UI muncul dengan logo dan styling rapi.
   - Klik tombol **"Authorize"**, masukkan Bearer token API.
   - Klik endpoint `GET /api/v1/pagu`, klik tombol **"Try it out"**.
   - Masukkan parameter contoh (misal `year=2026`), klik **"Execute"**.
   - Verifikasi respon `200 OK` dan data JSON muncul langsung di layar.
2. Buka URL `/api/documentation?view=redoc`:
   - Pastikan antarmuka 3-kolom Redoc muncul sempurna.
   - Verifikasi navigasi parameter, contoh kode cURL / PHP / JS / Python, dan skema respon.
3. Buka URL `/documentation`:
   - Pastikan tab navigasi baru `🌐 REST API (Swagger & Redoc)` dapat diklik dan mengarah ke portal dokumentasi.
