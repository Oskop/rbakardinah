# Walkthrough: Menu Indikator Kinerja & Target 5 Tahunan Administrator

Fitur **Menu Indikator Kinerja** khusus untuk peran **Administrator** telah berhasil diimplementasikan secara menyeluruh. Fitur ini menyediakan pencatatan master indikator kinerja rumah sakit, pengelolaan target tahunan dalam matriks 5 tahun, sistem pelacakan riwayat versi pengeditan target (*audit trail / versioning*), proteksi data **Soft Delete**, serta fitur **Toggle Status Aktif / Nonaktif**.

---

## Ringkasan Fitur yang Diimplementasikan

### 1. Hak Akses & Menu Navigasi
- Menu ini **hanya dapat diakses oleh peran Administrator** (`role:Administrator`).
- Akses oleh peran lain (Supervisor, Operator, Guest) dilindungi oleh middleware dan menghasilkan respon `403 Forbidden` atau redirect login.
- Tautan menu navigasi berikon `🎯 Indikator Kinerja` telah disematkan pada:
  - **Desktop Navigation Bar** di samping menu Pengumuman & RBA Headers.
  - **Responsive Mobile Navigation Bar**.

### 2. Struktur Basis Data & Model (Eloquent)
Tiga tabel baru telah dibuat melalui Laravel migration dengan nama constraint yang ringkas dan aman:
1. **`performance_indicators` (Parent)**:
   - Kolom: `id`, `code`, `name`, `category` (teks bebas), `unit`, `description`, `order`, `is_active` (boolean), `created_by`, `created_at`, `updated_at`, dan `deleted_at` (soft delete).
   - Menggunakan trait `SoftDeletes` dan `LogsActivity`.
2. **`performance_indicator_targets` (Child)**:
   - Kolom: `id`, `performance_indicator_id`, `year`, `target_value` (teks bebas ringkas), `current_version` (integer), `created_by`, `updated_by`, `created_at`, `updated_at`, dan `deleted_at` (soft delete).
   - Constraint unik: satu indikator hanya memiliki satu record target per tahun (`pi_targets_indicator_year_unique`).
3. **`performance_indicator_target_histories` (Riwayat Versi / Audit Trail)**:
   - Kolom: `id`, `target_id`, `performance_indicator_id`, `year`, `version_number`, `old_value`, `new_value`, `change_note`, `user_id`, `created_at`, dan `updated_at`.

### 3. Tampilan Antarmuka Matriks 5 Tahun & Interaktivitas
Halaman di [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/performance-indicators/index.blade.php) didesain dengan Alpine.js:
- **Pills Filter Status**: Tab cepat untuk melihat *Semua*, *🟢 Aktif*, dan *⚪ Nonaktif*.
- **Pemilih Rentang 5 Tahun**:
  - Dropdown tahun awal (misal `2024` s.d. `2028`, `2025` s.d. `2029`, dst.).
  - Tombol cepat navigasi `◀ 5 Tahun Sebelumnya` dan `5 Tahun Berikutnya ▶`.
- **Toolbar Pencarian**: Menemukan indikator berdasarkan nama, kode, kategori teks bebas, atau formulasi.
- **Tabel Matriks 5 Kolom Tahun**:
  - Tiap baris menampilkan data indikator, badge status, kategori, dan 5 kolom tahun berturut-turut.
  - Jika target belum diisi: Tombol `+ Isi` yang membuka dialog pengisian cepat.
  - Jika target sudah terisi: Nilai target ditampilkan dengan badge versi (contoh: `V1` atau `V2 📜`).
- **Modal Dialog Interaktif**:
  1. **Modal Tambah / Edit Indikator Parent**: Dilengkapi input teks bebas untuk kategori/bidang, satuan, dan checkbox status aktif.
  2. **Modal Ubah Target Tahunan (Single Year)**: Menampilkan konteks indikator, nilai saat ini, input nilai target baru, dan catatan perubahan.
  3. **Modal Kelola Target 5 Tahun Sekaligus (Batch Update)**: Memungkinkan pengisian atau pembaruan 5 kolom tahun sekaligus dalam 1 form.
  4. **Modal Linimasa Riwayat Versi (Audit Trail)**: Menampilkan linimasa kronologis Versi 1, Versi 2, dst., dengan perubahan nilai (*old_value* ➔ *new_value*), catatan alasan, waktu lengkap, dan nama admin yang mengubah.

### 4. Fitur Pengaktifan Ulang & Soft Delete
- **Toggle Status**: Admin dapat menonaktifkan indikator kapan saja (tombol kuning `⏸️ Nonaktifkan`) untuk mengarsipkan indikator yang tidak aktif. Indikator yang nonaktif dapat diaktifkan kembali secara instan dengan tombol hijau `▶️ Aktifkan Kembali`.
- **Soft Delete**: Menghapus indikator menggunakan soft delete (kolom `deleted_at` terisi) sehingga data tidak hilang secara permanen dari basis data dan riwayat audit trail tetap utuh.

---

## Verifikasi & Pengujian

### 1. Automated Feature Tests (Pest / PHPUnit)
Pengujian menyeluruh telah dibuat di [PerformanceIndicatorTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PerformanceIndicatorTest.php):
```bash
php artisan test --filter=PerformanceIndicatorTest
```
**Hasil**:
```text
PASS  Tests\Feature\Admin\PerformanceIndicatorTest
✓ non admin cannot access performance indicators
✓ admin can view performance indicators index with matrix
✓ admin can create performance indicator parent
✓ admin can update performance indicator
✓ admin can toggle indicator status
✓ admin can soft delete performance indicator
✓ admin can create and version target with audit trail
✓ admin can batch update targets
✓ admin can fetch target history json

Tests:    9 passed (46 assertions)
Duration: 4.78s
```

### 2. Kompilasi Aset Frontend (Vite)
```bash
bun run build
```
**Hasil**: Berhasil dikompilasi dalam 2.14 detik tanpa error.

### 3. Full Regression Suite (Seluruh Modul Aplikasi)
```bash
php artisan test
```
**Hasil**:
```text
Tests:    170 passed (807 assertions)
Duration: 53.81s
```
Semua 170 tests dari seluruh sistem SIPAKAR lulus 100%.

---

## File yang Dibuat & Dimodifikasi

| Status | File | Deskripsi |
| :--- | :--- | :--- |
| **NEW** | [2026_09_08_100000_create_performance_indicators_tables.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_08_100000_create_performance_indicators_tables.php) | Migrasi 3 tabel: parent, target tahunan, dan histori versi |
| **NEW** | [PerformanceIndicator.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/PerformanceIndicator.php) | Model induk indikator kinerja dengan SoftDeletes & LogsActivity |
| **NEW** | [PerformanceIndicatorTarget.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/PerformanceIndicatorTarget.php) | Model target tahunan dengan SoftDeletes & relasi histori |
| **NEW** | [PerformanceIndicatorTargetHistory.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/PerformanceIndicatorTargetHistory.php) | Model pencatatan riwayat versi target |
| **NEW** | [PerformanceIndicatorController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/PerformanceIndicatorController.php) | Controller admin untuk CRUD, toggle status, versioning, batch target, dan JSON history |
| **NEW** | [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/performance-indicators/index.blade.php) | Tampilan Blade matriks 5 tahun dengan 4 modal dialog |
| **NEW** | [PerformanceIndicatorTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PerformanceIndicatorTest.php) | 9 automated test cases pengujian fitur indikator kinerja |
| **MOD** | [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php) | Pendaftaran route resource & endpoint kustom di grup admin |
| **MOD** | [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php) | Penambahan tautan menu desktop & responsive mobile |
