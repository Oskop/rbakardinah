# Implementation Plan - Sistem Logging & Monitoring Akses REST API

Dokumen perencanaan teknis untuk membangun sistem pencatatan (**Logging**) dan pemantauan (**Monitoring**) pengaksesan REST API RSUD Kardinah, terintegrasi dengan panel Administrator SIPAKAR.

---

## 1. Analisis Kondisi Saat Ini & Rekomendasi Solusi

### 1.1 Apakah pengaksesan API saat ini sudah tercatat log-nya?
- **Saat ini baru tercatat sebatas ringkasan agregat di tabel `api_clients`**:
  - Kolom `last_used_at` (waktu pemanggilan terakhir)
  - Kolom `last_used_ip` (alamat IP pemanggil terakhir)
- **Yang BELUM tercatat**:
  - Riwayat histori per request secara mendetail (kapan saja diakses).
  - Parameter filter apa yang dicari oleh aplikasi rekanan (tahun berapa, periode apa, rekening apa).
  - Berapa status HTTP code responnya (`200 OK`, `401 Unauthorized`, `403 Forbidden`, atau `429 Throttle`).
  - Berapa durasi waktu respon / latensi server dalam milidetik (`ms`).
  - Rekam jejak request yang gagal / ditolak karena token tidak valid atau kadaluwarsa.

### 1.2 Apakah pengaksesan API saat ini masuk ke menu "Log Data"?
- **BELUM masuk ke menu Log Data**.
- Menu **Log Data** ([`resources/views/admin/logs/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/logs/index.blade.php)) saat ini dirancang khusus untuk **Audit Trail Mutasi Database Internal** (`ActivityLog`), yaitu mencatat aksi Create, Update, dan Delete data oleh pengguna (User pegawai RSUD) yang login melalui web form.
- Pengaksesan API adalah **operasi pembacaan data (HTTP GET)** oleh sistem eksternal / Machine-to-Machine.

### 1.3 Rekomendasi Solusi Arsitektural (Best Practice)
> [!TIP]
> **Pemisahan Tabel Log (Separation of Concerns)**:
> Sangat disarankan untuk **TIDAK mencampur** log pembacaan API ke dalam tabel `activity_logs` transaksi database internal.
> 
> **Alasan**:
> 1. Frekuensi panggilan API (GET request) jauh lebih tinggi; jika dicampur, tabel transaksi internal akan cepat membengkak dan memperlambat audit data keuangan RSUD.
> 2. Kebutuhan data log API berbeda: API membutuhkan pencatatan *endpoint, HTTP status, execution time (latency), query parameter, IP, dan token prefix*, sedangkan transaksi database membutuhkan *old_values, new_values, model_type, dan user_id*.
> 
> **Solusi Ideal**:
> Membangun tabel terdedikasi `api_access_logs` dan menyajikan antarmukanya di Panel Admin sebagai **Tab Terpadu** pada menu **Log Data**:
> - Tab 1: `📝 Log Transaksi Database` (Audit Trail mutasi data internal yang sudah ada)
> - Tab 2: `🌐 Log Akses REST API` (Monitoring performa dan lalu lintas akses API rekanan)

---

## 2. Spesifikasi Fitur Monitoring Akses API

### 2.1 Metrik & Informasi yang Dicatat per Request
Setiap kali endpoint `/api/v1/*` dipanggil, middleware logging akan mencatat:
1. `api_client_id` & `client_name`: Aplikasi rekanan mana yang memanggil (e.g. "Aplikasi SIMRS Teman", atau "Unauthenticated Client").
2. `endpoint`: Path endpoint yang diakses (e.g. `/api/v1/pagu`).
3. `method`: HTTP Method (e.g. `GET`).
4. `status_code`: HTTP response code (e.g. `200`, `401`, `403`, `429`, `500`).
5. `query_params`: Seluruh parameter query yang dikirimkan klien (disimpan dalam format JSON, e.g. `{"year": 2026, "period": "Murni", "q": "Gaji"}`).
6. `response_time_ms`: Waktu pemrosesan server dalam satuan milidetik (`ms`).
7. `ip_address`: Alamat IP klien pemanggil.
8. `user_agent`: Informasi sistem/library pemanggil (e.g. Postman, GuzzleHttp, Axios, browser).
9. `error_message`: Pesan kegagalan jika request ditolak (e.g. token expired, token invalid, atau rate limit terlampaui).
10. `created_at`: Waktu tepat request diterima (WIB).

### 2.2 Tampilan Dashboard Monitoring di Panel Administrator
Halaman monitoring baru di admin (`/admin/api-logs`) akan menyediakan:
1. **Summary Cards (Statistik Cepat)**:
   - **Total Panggilan API**: Jumlah total request yang pernah diterima.
   - **Panggilan Hari Ini**: Volume trafik API pada hari berjalan.
   - **Tingkat Keberhasilan (Success Rate)**: Persentase request berstatus 200 OK (e.g. `98.2%`).
   - **Rata-rata Waktu Respon**: Kecepatan pemrosesan server (e.g. `38 ms`).
2. **Filter & Pencarian Interaktif**:
   - Filter berdasarkan Klien API.
   - Filter berdasarkan Status Kode HTTP (Semua, 200 Sukses, 401/403 Ditolak, 429 Throttle).
   - Filter berdasarkan Rentang Tanggal.
   - Pencarian bebas (berdasarkan IP address, endpoint, atau parameter).
3. **Tabel Riwayat Akses Real-time**:
   - Kolom: Waktu (WIB), Klien / Token, Endpoint & Method, Parameter Filter, Status (Badge Berwarna), Kecepatan Respon (ms), IP Address, dan Tombol Aksi Detail.
4. **Modal Detail Request**:
   - Membuka popup dialog interaktif yang menampilkan rincian lengkap parameter yang dikirim rekanan, header user-agent, dan detail pesan error jika gagal.

### 2.3 Pembersihan Log Berkala (Retention / Pruning)
Disediakan command Artisan untuk mencegah database membengkak di masa mendatang:
```bash
php artisan api:logs-prune --days=30
```
*(Menghapus log akses API yang berusia lebih dari 30 hari secara otomatis)*.

---

## 3. Proposed Changes

### Database & Migrations

#### [NEW] `database/migrations/2026_09_09_000002_create_api_access_logs_table.php`
- Membuat skema tabel `api_access_logs` dengan index pada kolom `created_at`, `status_code`, dan `api_client_id`.

---

### Models & Middleware

#### [NEW] `app/Models/ApiAccessLog.php`
- Model Eloquent untuk mencatat data log akses API.
- Relasi `belongsTo` ke `ApiClient`.
- Accessor untuk badge status visual dan format parameter.

#### [NEW] `app/Http/Middleware/LogApiAccess.php`
- Middleware untuk menangkap request dan response API, menghitung durasi respon (`microtime(true)`), mengekstrak data klien, dan menyimpan log ke database.

#### [MODIFY] [`routes/api.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/api.php)
- Pasangkan middleware `LogApiAccess` pada grup rute API v1.

---

### Controller & Routing Panel Admin

#### [NEW] `app/Http/Controllers/Admin/ApiAccessLogController.php`
- Method `index(Request $request)`: Query log akses dengan filter, statistik, dan pagination.
- Method `show(ApiAccessLog $apiLog)`: Mengembalikan data detail log (JSON untuk modal popup).
- Method `clear()`: Opsi menghapus log lama oleh administrator.

#### [MODIFY] [`routes/web.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Daftarkan rute admin:
  ```php
  Route::get('api-logs', [\App\Http\Controllers\Admin\ApiAccessLogController::class, 'index'])->name('admin.api-logs.index');
  Route::get('api-logs/{apiLog}', [\App\Http\Controllers\Admin\ApiAccessLogController::class, 'show'])->name('admin.api-logs.show');
  Route::post('api-logs/prune', [\App\Http\Controllers\Admin\ApiAccessLogController::class, 'prune'])->name('admin.api-logs.prune');
  ```

---

### Frontend Views

#### [NEW] `resources/views/admin/api_logs/index.blade.php`
- Halaman dashboard monitoring akses REST API lengkap dengan kartu metrik, filter interaktif, tabel log, dan modal detail request.

#### [MODIFY] [`resources/views/admin/logs/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/logs/index.blade.php)
- Tambahkan tab navigasi di bagian atas:
  `[ 📝 Log Transaksi Database ]` (Aktif) | `[ 🌐 Log Akses REST API ]` (Link ke `/admin/api-logs`).

#### [MODIFY] [`resources/views/layouts/navigation.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Pastikan menu navigasi `Log Data` tetap aktif (`active`) baik saat admin membuka `admin.logs.*` maupun `admin.api-logs.*`.

---

### Artisan CLI Tools

#### [NEW] `app/Console/Commands/PruneApiLogsCommand.php`
- Command `api:logs-prune {--days=30}` untuk membersihkan arsip log lama.

---

## 4. Verification Plan

### Automated Tests (`tests/Feature/Admin/ApiAccessLogTest.php`)
1. **Pencatatan Otomatis Log Akses**:
   - Request sukses (`200 OK`) ke `/api/v1/pagu` otomatis membuat 1 record di `api_access_logs` dengan status code 200, query params yang sesuai, dan response time > 0 ms.
   - Request gagal tanpa token (`401 Unauthorized`) tetap tercatat di `api_access_logs` dengan status 401 dan error message.
   - Request dengan token tidak aktif (`403 Forbidden`) tetap tercatat dengan status 403.
2. **Dashboard Monitoring Admin**:
   - Admin dapat mengakses `/admin/api-logs` dan melihat statistik metrik serta daftar tabel log.
   - Non-admin (Supervisor / Operator / Guest) dilarang mengakses `/admin/api-logs` (redirect/403).
   - Filter berdasarkan status code, klien, dan tanggal berfungsi dengan benar.
   - Endpoint modal detail `/admin/api-logs/{id}` mengembalikan data JSON lengkap.
3. **Pruning Command**:
   - Command `api:logs-prune` berhasil menghapus data log yang lebih lama dari jumlah hari yang ditentukan.

### Manual Verification
1. Lakukan request ke `/api/v1/pagu` menggunakan cURL / script pengujian dengan parameter `?year=2026&period=Murni&q=Gaji`.
2. Buka browser ke menu Administrator: **Log Data -> Tab Log Akses REST API**.
3. Verifikasi:
   - Permintaan tercatat secara real-time.
   - Nama klien "Aplikasi Teman Rekanan" muncul.
   - Parameter filter terlihat jelas.
   - Durasi respon dalam milidetik tercatat dengan akurat.
