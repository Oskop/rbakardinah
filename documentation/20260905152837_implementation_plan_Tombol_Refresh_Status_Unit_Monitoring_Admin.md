# Implementation Plan: Tombol Refresh Status Unit pada Panel Monitoring Administrator

Menambahkan fitur dan tombol **Refresh / Sinkronisasi Status Unit** pada bagian *Monitoring Penginputan Unit dan Progres RBA* di halaman Administrator (`/admin/headers/{header}`), sehingga Administrator dapat memperbarui dan memeriksa status makro seluruh unit kerja (atau unit tertentu) secara langsung dan instan sesuai dengan kondisi rincian belanja terkini tanpa harus menunggu pemicu dari operator maupun supervisor.

---

## 1. Analisis Kebutuhan & Latar Belakang

### Kebutuhan
- Setelah sistem otomatisasi status unit diterapkan (di mana status unit `Validated` ditentukan oleh kelengkapan validasi rincian belanja), terdapat kemungkinan adanya berkas unit lama yang statusnya belum tersinkronisasi, atau Administrator ingin memutakhirkan status seluruh unit secara on-demand.
- Administrator membutuhkan kendali langsung untuk me-refresh status seluruh unit kerja dalam satu klik, serta melihat laporan feedback berapa unit yang statusnya diperbarui.

### Solusi yang Diusulkan
1. **Tombol Refresh Global (Seluruh Unit)**:
   - Ditempatkan pada toolbar panel *Monitoring Penginputan Unit dan Progres RBA* (berdampingan dengan tombol *Buka Semua* dan *Tutup Semua*).
   - Menjalankan sinkronisasi status (`syncValidationStatus()`) ke seluruh `RbaSubmission` di bawah header tersebut.
   - Memberikan notifikasi feedback yang informatif (contoh: *"Berhasil menyinkronkan status unit. Sebanyak X unit diperbarui ke status terkini."*).
2. **Tombol Refresh Individual (Per Unit Kerja)**:
   - Ditempatkan berupa tombol icon refresh kecil di samping badge status unit pada baris kartu masing-masing unit kerja.
   - Memudahkan Administrator yang hanya ingin memutakhirkan status satu unit kerja spesifik yang baru saja ditinjau.
3. **Banner Notifikasi Flash Message**:
   - Menambahkan komponen alert `session('success')` dan `session('error')` pada halaman `/admin/headers/{header}` agar hasil sinkronisasi status terlihat jelas oleh Administrator.

---

## 2. Rencana Perubahan Komponen

### A. Routing & Controller
#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan route baru di dalam grup middleware `Administrator`:
  ```php
  // Global sync all unit statuses under this header
  Route::post('headers/{header}/sync-unit-statuses', [\App\Http\Controllers\RbaHeaderController::class, 'syncUnitStatuses'])
      ->name('headers.sync-unit-statuses');

  // Single unit submission sync
  Route::post('submissions/{submission}/sync-status', [\App\Http\Controllers\RbaHeaderController::class, 'syncSingleSubmissionStatus'])
      ->name('submissions.sync-status');
  ```

#### [MODIFY] [app/Http/Controllers/RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Menambahkan method `syncUnitStatuses(RbaHeader $header)`:
  - Melakukan iterasi ke seluruh `$header->submissions` dengan relasi `details`.
  - Memanggil `$submission->syncValidationStatus()`.
  - Menghitung berapa unit yang mengalami perubahan status (`$oldStatus !== $newStatus`).
  - Mengembalikan `back()->with('success', ...)` dengan pesan informatif.
- Menambahkan method `syncSingleSubmissionStatus(RbaSubmission $submission)`:
  - Memanggil `$submission->syncValidationStatus()`.
  - Mengembalikan `back()->with('success', "Status unit {$submission->unit->name} berhasil diperbarui menjadi {$submission->status_submission}.")`.

---

### B. Antarmuka Pengguna (UI / Views)
#### [MODIFY] [resources/views/admin/headers/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
1. **Banner Alert Notifikasi**:
   - Menambahkan blok penampil alert `session('success')` dan `session('error')` di bagian atas konten halaman RBA Admin.
2. **Tombol Refresh Global pada Toolbar Monitoring**:
   - Menambahkan tombol form POST dengan icon refresh dan tooltip di samping tombol *Buka Semua* / *Tutup Semua*:
     - Teks: **🔄 Refresh Status Unit**
     - Desain: Tombol bergradien emerald/slate dengan efek putar icon saat di-hover.
3. **Tombol Refresh Individual pada Header Kartu Unit**:
   - Menambahkan tombol icon refresh kecil di samping badge status unit di setiap kartu unit:
     - Icon refresh berukuran ringkas dengan tooltip *"Sinkronkan status unit ini"*, memicu submit form POST individual ke `admin.submissions.sync-status`.

---

### C. Pengujian & Otomasi (Tests)
#### [MODIFY] [tests/Feature/Admin/AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)
- Menambahkan test case:
  1. `test_admin_can_sync_all_unit_statuses_under_header`:
     - Menyiapkan submission unit dengan rincian belanja yang sudah divalidasi, namun status submission masih `Pending Supervisor` atau `Draft`.
     - Admin mengirim POST ke route `admin.headers.sync-unit-statuses`.
     - Memverifikasi redirect dan assert bahwa status submission unit otomatis berubah menjadi `Validated`.
  2. `test_admin_can_sync_single_submission_status`:
     - Admin mengirim POST ke route `admin.submissions.sync-status`.
     - Memverifikasi bahwa status unit spesifik tersebut berhasil dimutakhirkan.
  3. `test_non_admin_cannot_access_sync_endpoints`:
     - Memastikan Operator atau Supervisor tidak dapat memanggil endpoint admin ini (HTTP 403 Forbidden).

---

## 3. Rencana Verifikasi

1. **Pengujian Fungsional Otomatis**:
   - Menjalankan `php artisan test --filter=AdminDashboardTest` untuk memverifikasi endpoint sinkronisasi status.
   - Menjalankan seluruh test suite aplikasi (`php artisan test`) untuk memastikan seluruh 145+ test cases tetap lulus 100%.
2. **Kompilasi Aset Frontend**:
   - Menjalankan `bun run build` untuk memvalidasi markup Blade baru.
3. **Simulasi Manual**:
   - Memastikan tombol "Refresh Status Unit" muncul di toolbar panel monitoring Admin.
   - Memastikan saat diklik, sistem mengeksekusi sinkronisasi dan menampilkan pesan banner hijau sukses di atas halaman.

---

## 4. Persetujuan Pengguna (User Review)

Rencana di atas telah disusun untuk mengakomodasi kebutuhan tombol refresh status unit (baik secara menyeluruh untuk seluruh unit maupun per unit kerja spesifik).

Mohon konfirmasi dan persetujuan (approval) untuk memulai pengerjaan.
