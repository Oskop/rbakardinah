# Implementation Plan - Audit Logging Transaksi Database & Menu "Log Data" Administrator

Menerapkan sistem pencatatan riwayat transaksi database (*Audit Logging*) secara otomatis untuk seluruh aktivitas mutasi data (Create, Update, Delete, Restore) oleh seluruh pengguna di semua level peran (**Administrator**, **Supervisor**, **Operator**, dan Sistem), serta menyediakan menu baru **Log Data** yang hanya dapat diakses oleh **Administrator**.

---

## User Review Required

> [!IMPORTANT]
> **Cakupan Pencatatan Audit Log:**
> 1. **Otomatisasi Penuh:** Seluruh transaksi data database pada 12 model aplikasi (`AccountCode`, `KelompokBelanja`, `RbaAccountPagu`, `RbaAttachment`, `RbaDetail`, `RbaHeader`, `RbaPeriod`, `RbaSubmission`, `RbaSubmissionDocument`, `RbaSubmissionDocumentVersion`, `Unit`, `User`) akan dicatat secara otomatis saat terjadi penambahan (*created*), perubahan (*updated*), penghapusan (*deleted*), atau pemulihan (*restored*).
> 2. **Informasi Detail yang Disimpan:**
>    - **Pengguna:** ID, Nama, dan Peran saat transaksi dilakukan.
>    - **Aksi & Objek:** Jenis aksi (*CREATE*, *UPDATE*, *DELETE*, *RESTORE*), tipe Model, dan ID data terkait.
>    - **Perubahan Nilai (Diff):** Nilai sebelum perubahan (*old values*) dan nilai setelah perubahan (*new values*) dalam format terstruktur (JSON).
>    - **Konteks:** Deskripsi singkat yang mudah dibaca, IP Address, User Agent, dan waktu transaksi (*timestamp*).
> 3. **Hak Akses Eksklusif:** Menu dan halaman **Log Data** diproteksi oleh middleware `auth` dan `role:Administrator` sehingga hanya dapat dilihat oleh Administrator.

---

## Proposed Changes

### 1. Database & Model `ActivityLog`

#### [NEW] [create_activity_logs_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/)
- Membuat tabel `activity_logs` dengan kolom:
  - `id` (bigIncrements)
  - `user_id` (foreignId to users, nullable, nullOnDelete)
  - `user_name` (string, nullable)
  - `user_role` (string, nullable)
  - `action` (string) - `created`, `updated`, `deleted`, `restored`
  - `model_type` (string)
  - `model_id` (unsignedBigInteger, nullable)
  - `description` (text, nullable)
  - `old_values` (json, nullable)
  - `new_values` (json, nullable)
  - `ip_address` (string, nullable)
  - `user_agent` (text, nullable)
  - `created_at`, `updated_at` (timestamps)

#### [NEW] [ActivityLog.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/ActivityLog.php)
- Model `ActivityLog` dengan relasi `user()`, casts array untuk `old_values` & `new_values`, dan helper query scope.

---

### 2. Trait Audit Logging (`app/Traits/LogsActivity.php`)

#### [NEW] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)
- Trait Eloquent yang secara otomatis mengaitkan event lifecycle model (`created`, `updated`, `deleted`, `restored`) untuk mencatat log aktivitas database ke tabel `activity_logs`.
- Menghindari logging field sensitif seperti `password` dan `remember_token`.

#### [MODIFY] Seluruh 12 Model Eloquent:
- Menyematkan `use LogsActivity;` pada:
  - [AccountCode.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/AccountCode.php)
  - [KelompokBelanja.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/KelompokBelanja.php)
  - [RbaAccountPagu.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaAccountPagu.php)
  - [RbaAttachment.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaAttachment.php)
  - [RbaDetail.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDetail.php)
  - [RbaHeader.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaHeader.php)
  - [RbaPeriod.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaPeriod.php)
  - [RbaSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmission.php)
  - [RbaSubmissionDocument.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmissionDocument.php)
  - [RbaSubmissionDocumentVersion.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmissionDocumentVersion.php)
  - [Unit.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/Unit.php)
  - [User.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/User.php)

---

### 3. Controller & Views Admin Log Data

#### [NEW] [ActivityLogController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/ActivityLogController.php)
- Method `index()`: Menampilkan ringkasan statistik (total log, create, update, delete, role breakdown) dan daftar log dengan filter (role, action, model, date range, search query).
- Method `show()`: Menampilkan modal/detail perubahan nilai lama vs baru.

#### [NEW] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/logs/index.blade.php)
- Halaman UI modern dan interaktif:
  - Kartu statistik aktivitas transaksi harian dan akumulatif.
  - Toolbar filter pencarian, filter role, filter model, filter aksi, dan rentang tanggal.
  - Tabel log dengan badge aksi warna-warni, data pengusul, IP, dan tombol **Detail Perubahan** dengan modal viewer diff JSON.

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php) & [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan menu **Log Data** pada navigasi Administrator dan mendaftarkan route `admin.logs.index` serta `admin.logs.show`.

---

### 4. Pengujian Otomatis (`tests/Feature/Admin/ActivityLogTest.php`)

#### [NEW] [ActivityLogTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/ActivityLogTest.php)
- Menguji:
  1. `test_activity_is_logged_when_models_are_created_updated_deleted`: Verifikasi pencatatan log otomatis saat transaksi dilakukan oleh Operator, Supervisor, dan Admin.
  2. `test_admin_can_access_log_data_menu`: Verifikasi Admin dapat mengakses halaman Log Data.
  3. `test_supervisor_and_operator_cannot_access_log_data_menu`: Verifikasi Supervisor dan Operator diblokir (403 Forbidden).
  4. `test_admin_can_filter_logs`: Verifikasi filter berdasarkan role, aksi, dan model.

---

## Verification Plan

### Automated Tests
- Jalankan migration:
  `php artisan migrate`
- Jalankan test suite Log Data:
  `php artisan test --filter=ActivityLogTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator**, buat usulan rincian belanja baru, lalu edit nilainya.
2. Login sebagai **Supervisor**, validasi usulan rincian belanja tersebut.
3. Login sebagai **Administrator**, tetapkan pagu untuk nomor rekening tersebut.
4. Buka menu baru **Log Data** di navbar Admin:
   - Verifikasi seluruh aksi Operator (create, update), Supervisor (validate/update), dan Admin (create pagu) tercatat rapi beserta nama pengguna, peran, waktu, IP, dan detail nilai lama vs baru.
   - Uji filter pencarian, filter role (Operator/Supervisor/Admin), filter aksi, dan buka modal "Detail Perubahan".
5. Coba akses URL `/admin/logs` saat login sebagai **Operator** atau **Supervisor** untuk memastikan proteksi 403 Forbidden berfungsi dengan tepat.
