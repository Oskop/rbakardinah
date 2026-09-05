# Walkthrough: Tombol Refresh Status Unit pada Monitoring Penginputan Unit & Progres RBA (Admin)

## 1. Ringkasan Implementasi

Telah berhasil ditambahkan fitur **Tombol Refresh Status Unit** pada bagian *Monitoring Penginputan Unit dan Progres RBA* di halaman Administrator (`/admin/headers/{header}`). Fitur ini memberikan kendali langsung kepada Administrator untuk menyinkronkan dan memeriksa ulang status makro (`Draft`, `Pending Supervisor`, `Validated`) seluruh unit kerja maupun unit individual secara instan tanpa perlu menunggu aksi pemicu dari operator maupun supervisor.

---

## 2. Perubahan Komponen & Berkas

### A. Routing Web ([`routes/web.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php))
Menambahkan dua rute POST baru di bawah grup middleware `role:Administrator`:
- `POST /admin/headers/{header}/sync-unit-statuses` (`admin.headers.sync-unit-statuses`): Menyinkronkan seluruh status unit yang berada di bawah RBA Header terkait.
- `POST /admin/submissions/{submission}/sync-status` (`admin.submissions.sync-status`): Menyinkronkan status unit untuk submission individual tertentu.

```php
// Sinkronisasi status submission / unit kerja secara manual oleh Admin
Route::post('/headers/{header}/sync-unit-statuses', [RbaHeaderController::class, 'syncUnitStatuses'])
    ->name('headers.sync-unit-statuses');
Route::post('/submissions/{submission}/sync-status', [RbaHeaderController::class, 'syncSingleSubmissionStatus'])
    ->name('submissions.sync-status');
```

### B. Controller Logic ([`app/Http/Controllers/RbaHeaderController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php))
Mengimplementasikan dua method controller:
1. `syncUnitStatuses(RbaHeader $header)`:
   - Mengambil seluruh `RbaSubmission` aktif di bawah header.
   - Menjalankan method `$submission->syncValidationStatus()` yang mengevaluasi seluruh rincian belanja (`RbaDetail`) terkait.
   - Mengembalikan respon *redirect back* dengan pesan feedback: *"Status seluruh unit kerja (X unit) berhasil disinkronisasi ulang."*
2. `syncSingleSubmissionStatus(RbaSubmission $submission)`:
   - Memanggil `$submission->syncValidationStatus()`.
   - Mengembalikan respon *redirect back* dengan pesan feedback status terkini unit tersebut.

### C. Tampilan Antarmuka Admin ([`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php))
1. **Banner Alert Notifikasi**:
   - Menambahkan notifikasi flash message `session('success')` dan `session('error')` di bagian atas halaman dengan dukungan auto-dismiss atau penutupan manual berbasis Alpine.js.
2. **Tombol Global "Refresh Status Unit"**:
   - Ditempatkan di baris kanan toolbar panel *Monitoring Penginputan Unit dan Progres RBA* berdampingan dengan badge ringkasan status unit.
   - Dilengkapi icon putar sinkronisasi, indikator loading spinner state (`submitting: false`), dan tooltip penjelasan.
3. **Tombol Refresh Individual Per-Unit**:
   - Ditambahkan di samping badge status unit pada kartu masing-masing unit kerja.
   - Diberi atribut `@click.stop` agar tidak memicu pembukaan/penutupan accordion kartu unit kerja ketika diklik.
   - Menampilkan tooltip title deskriptif (*"Sinkronkan status unit ini"*).

---

## 3. Hasil Pengujian & Verifikasi

### A. Pengujian Otomatis Fitur Admin (Feature Test)
Menambahkan 3 pengujian baru pada [`tests/Feature/Admin/AdminDashboardTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php):
1. `test_admin_can_sync_all_unit_statuses_under_header`: Memvalidasi tombol global sinkronisasi berhasil memperbarui status seluruh unit di bawah header menjadi `Validated` jika rincian belanjanya sudah divalidasi.
2. `test_admin_can_sync_single_submission_status`: Memvalidasi tombol refresh individual berhasil memperbarui status submission unit tertentu.
3. `test_non_admin_cannot_access_sync_endpoints`: Memastikan proteksi otorisasi aman (non-admin mendapat respon HTTP 403 Forbidden).

Hasil eksekusi:
```text
PASS  Tests\Feature\Admin\AdminDashboardTest
✓ admin can access dashboard and see rba list
✓ admin can preview print report with unit and operator filters
✓ admin can preview rba final print report with pagu and unit operator filters
✓ admin can view unit monitoring with supervisor and operator progress
✓ admin can view document and proposal pdf modals with versioning
✓ admin can sync all unit statuses under header
✓ admin can sync single submission status
✓ non admin cannot access sync endpoints

Tests:    8 passed (67 assertions)
Duration: 2.07s
```

### B. Kompilasi Aset Frontend (Vite)
Kompilasi asset dijalankan dengan sukses tanpa issue:
```text
✓ built in 1.95s
public/build/manifest.json             0.33 kB │ gzip:  0.17 kB
public/build/assets/app-K2FiMuoI.css  88.35 kB │ gzip: 13.60 kB
public/build/assets/app-CBbTb_k3.js   83.04 kB │ gzip: 30.88 kB
```

### C. Full Test Suite Regression Test
Menjalankan seluruh rangkaian tes aplikasi:
```text
Tests:    148 passed (697 assertions)
Duration: 55.80s
```
Seluruh 148 tes lulus 100% tanpa adanya regresi sistem.

---

## 4. Kesimpulan
Fitur Tombol Refresh Status Unit (baik secara keseluruhan maupun individual per-unit kerja) telah selesai diimplementasikan, aman dari segi otorisasi role, terintegrasi secara harmonis dengan UI Blade & Alpine.js yang ada, serta terverifikasi penuh oleh pengujian otomatis.
