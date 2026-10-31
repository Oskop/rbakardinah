# Walkthrough: Fitur Hard Delete Archive & 100% Faithful Restoration RBA Header

## 1. Ringkasan Eksekutif & Latar Belakang

Sistem RBA RSUD Kardinah sebelumnya belum memiliki mekanisme penghapusan (*delete*) maupun penonaktifan (*is_active* / *soft delete*) untuk `rba_headers`. Ketika terdapat kesalahan input nama atau periode RBA oleh Admin dan Admin sudah menginput data yang benar, data RBA yang keliru tersebut tetap menggantung di tabel operasional. Hal ini dapat menimbulkan keraguan dan pertanyaan kritis saat pemeriksaan atau audit oleh BPK.

Pengguna menginginkan solusi pembersihan data RBA yang salah dengan kriteria ketat:
1. **Zero Regression**: Tidak merusak atau mengubah puluhan kode/query yang sudah berjalan di 14+ *controller* operasional.
2. **Kepatuhan Audit BPK**: Data operasional bersih dari data keliru tanpa menyisakan relasi yatim (*orphaned records*), namun seluruh rekam jejak audit tersimpan rapi (*audit trail*).
3. **Dedicated Archive (`hard_delete_archives`)**: Data yang dihapus tidak lenyap begitu saja, melainkan diabadikan secara terstruktur ke dalam tabel arsip polimorfik yang dapat menampung data `rba_headers` maupun entitas model lainnya di masa mendatang.
4. **100% Faithful Restoration**: Tersedia mekanisme pemulihan instan yang mampu mengembalikan data RBA Header beserta seluruh cabang relasinya (Submissions, Details, Pagus, Attachments, Documents, Backgrounds) dengan mempertahankan **Primary Key (ID) dan Foreign Key yang sama persis (100% identik)**.

---

## 2. Mengapa Memilih Dedicated Archive vs Soft Delete / is_active?

| Parameter | Opsi Soft Delete / is_active | Opsi Dedicated Archive (`hard_delete_archives`) |
| :--- | :--- | :--- |
| **Dampak ke Kode Lain** | **Tinggi (Berbahaya)**. Model `RbaSubmission` memiliki relasi `belongsTo(RbaHeader)`. Jika header di-*soft-delete* atau difilter, pemanggilan `$submission->header` akan mengembalikan `null`, memicu *Fatal Crash* `Attempt to read property on null` pada 14+ controller operator dan supervisor. | **Nol (Zero Impact)**. Tabel operasional tetap bersih dan murni. Query operasional tidak perlu diubah sama sekali. |
| **Kepatuhan Audit BPK** | Meragukan jika ada data berstatus *soft deleted* menggantung di tabel utama yang sama saat dump database diekstraksi. | **Sangat Baik**. Tabel operasional hanya berisi data valid. Data terhapus dicatat terpisah dengan alasan penghapusan, user penghapus, dan waktu penghapusan. |
| **Fleksibilitas Masa Depan** | Hanya berlaku untuk tabel yang ditambahkan kolom. | **Polimorfik Generik**. Tabel `hard_delete_archives` dirancang menampung entitas model apa pun via `model_type` dan `model_id`. |
| **Kemampuan Restore** | Cukup `restore()`, namun data usang dapat bercampur kembali dengan data baru jika sudah ada duplikasi. | **Presisi 100%**. Dilengkapi pemeriksaan tabrakan (*collision guard*), validasi integritas ID, dan transaksi basis data atomik. |

---

## 3. Komponen yang Telah Diimplementasikan

### 3.1. Database Migration & Model Arsip
- **Migration**: [2026_10_31_104000_create_hard_delete_archives_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_10_31_104000_create_hard_delete_archives_table.php)
  - Kolom: `model_type`, `model_id`, `label`, `root_data` (JSON), `related_data` (JSON), `reason`, `deleted_by`, `deleted_at`, `restored_by`, `restored_at`.
- **Model**: [`App\Models\HardDeleteArchive`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/HardDeleteArchive.php)
  - Otomatis melakukan *casting* JSON ke array, relasi ke `User` (`deletedBy`, `restoredBy`), dan helper `isRestored()`, `scopeUnrestored()`.

### 3.2. Core Engine: HardDeleteArchiveService
- **Service**: [`App\Services\HardDeleteArchiveService`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/HardDeleteArchiveService.php)
  - `captureRbaHeaderTree(RbaHeader $header)`: Mengambil snapshot mendalam dari seluruh pohon data:
    - Root: `rba_headers`
    - Level 1: `rba_submissions`, `rba_account_pagus`, `rba_backgrounds`, `rba_documents`
    - Level 2: `rba_details`, `rba_attachments`
  - `deleteRbaHeader(RbaHeader $header, string $reason, int $userId)`: Menjalankan transaksi DB atomik untuk menyimpan snapshot ke `hard_delete_archives`, mencatat `ActivityLog`, dan menghapus bersih seluruh record dari tabel operasional.
  - `restoreRbaHeader(HardDeleteArchive $archive, int $userId)`:
    - Memverifikasi tidak ada benturan duplikasi (*collision check*) periode dan tahun pada tabel operasional.
    - Menginjeksi kembali seluruh data menggunakan `DB::table()->insert()` sehingga Primary Key asli tidak bergeser sedikit pun.
    - Memperbarui status arsip menjadi `restored_at` dan mencatat `ActivityLog`.

### 3.3. Web UI & Controllers
- **Controller Pengelolaan RBA Header**: [`App\Http\Controllers\RbaHeaderController::destroy`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php#L63-L98)
  - Ditambahkan pengaman: jika terdapat detail belanja yang telah divalidasi (`is_validated = true`), penghapusan dari web UI akan ditolak demi keamanan data anggaran.
- **Controller Tempat Sampah / Arsip**: [`App\Http\Controllers\Admin\HardDeleteArchiveController`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/HardDeleteArchiveController.php)
  - `index()`: Menampilkan daftar arsip terhapus dengan filter status (Semua / Belum Dipulihkan / Sudah Dipulihkan).
  - `show()`: Melakukan inspeksi snapshot JSON root & child relations secara interaktif.
  - `restore()`: Memulihkan kembali data yang terhapus ke tabel operasional.
- **Tampilan Blade**:
  - [resources/views/admin/headers/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/index.blade.php): Tombol **"Arsip Sampah & Restore"** di samping tombol Tambah RBA, serta tombol aksi **"Hapus RBA"** lengkap dengan dialog input alasan.
  - [resources/views/admin/archives/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/archives/index.blade.php): Halaman *Recycle Bin* bergaya modern dengan badge status dan tombol tindakan cepat.
  - [resources/views/admin/archives/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/archives/show.blade.php): Halaman inspeksi detail snapshot data yang diarsipkan.

### 3.4. Artisan CLI Commands
1. **Penghapusan via CLI**:
   ```bash
   php artisan rba:delete-header {id} --reason="Alasan penghapusan" [--force]
   ```
2. **Pemulihan via CLI**:
   ```bash
   php artisan rba:restore-header {archive_id} [--force]
   ```

---

## 4. Panduan Operasional Pengguna

### A. Menghapus RBA Header yang Salah Input (Web Admin)
1. Buka menu **Admin > RBA Headers** (`/admin/headers`).
2. Cari baris RBA Header yang salah input.
3. Pada kolom **Aksi**, klik tombol merah **Hapus**.
4. Sistem akan menampilkan dialog konfirmasi yang meminta input **Alasan Penghapusan** (misal: *"Salah input tahun anggaran/periode oleh admin"*).
5. Klik **Konfirmasi Hapus**.
6. Data RBA beserta seluruh submisi di bawahnya langsung bersih dari tabel operasional, dan snapshot tersimpan di Arsip.

### B. Melihat dan Merestorasi Data (Web Admin)
1. Pada halaman **RBA Headers**, klik tombol **"Arsip Sampah & Restore"** di pojok kanan atas (atau akses `/admin/archives`).
2. Anda akan melihat daftar data yang pernah dihapus lengkap dengan label, alasan, nama penghapus, dan tanggal penghapusan.
3. Klik tombol **Lihat Snapshot** untuk memeriksa seluruh struktur data yang tersimpan di arsip.
4. Klik tombol hijau **Pulihkan Data (Restore)** untuk mengembalikan data tersebut ke tabel operasional dengan ID asli sedia kala.

### C. Menghapus & Merestorasi via CLI (Artisan)
Jika Admin atau Tim Teknis ingin melakukan tindakan langsung via server:
```bash
# Menghapus RBA Header ID #12
php artisan rba:delete-header 12 --reason="Kesalahan input periode oleh operator"

# Menampilkan atau merestorasi kembali Arsip ID #1
php artisan rba:restore-header 1
```

---

## 5. Hasil Verifikasi Pengujian Otomatis

Seluruh alur pengujian otomatis telah dijalankan melalui Feature Test [`Tests\Feature\Admin\HardDeleteAndRestoreRbaTest`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/HardDeleteAndRestoreRbaTest.php):

1. **`test_hard_delete_archives_entire_tree_and_cleans_operational_tables`**:
   - Memverifikasi snapshot JSON lengkap mencakup data root dan child records.
   - Memverifikasi tabel operasional (`rba_headers`, `rba_submissions`, `rba_details`, `rba_account_pagus`) 100% bersih.
   - Memverifikasi pencatatan `ActivityLog` untuk audit trail BPK.
   - Status: **PASSED (Green)**.
2. **`test_restore_reconstructs_exact_ids_and_all_relations_100_percent`**:
   - Memverifikasi data dipulihkan kembali ke tabel operasional dengan ID yang 100% sama dengan saat sebelum dihapus.
   - Memverifikasi relasi integritas antar-tabel tersambung utuh.
   - Memverifikasi status arsip berubah menjadi `restored_at`.
   - Status: **PASSED (Green)**.
3. **`test_restore_fails_gracefully_if_collision_occurs`**:
   - Memverifikasi sistem mencegah tabrakan data jika ada data baru dengan periode/tahun yang sama di tabel operasional.
   - Status: **PASSED (Green)**.
4. **`test_admin_can_delete_header_and_restore_via_web_routes`**:
   - Memverifikasi seluruh alur antarmuka web (Delete -> View Index -> View Detail Snapshot -> Restore).
   - Status: **PASSED (Green)**.
5. **`test_artisan_commands_can_delete_and_restore_header`**:
   - Memverifikasi eksekusi CLI `rba:delete-header` dan `rba:restore-header`.
   - Status: **PASSED (Green)**.

---

## 6. Kesimpulan & Rekomendasi Selanjutnya

Fitur **Hard Delete Archive & 100% Faithful Restoration** telah selesai diimplementasikan secara komprehensif, aman, dan siap digunakan di lingkungan produksi tanpa menimbulkan dampak negatif (*zero regression*) terhadap fitur-fitur yang sudah ada.

Jika Anda ingin langsung melakukan pembersihan data RBA Header yang salah input di basis data, silakan informasikan **ID RBA Header** yang salah tersebut atau Anda dapat langsung menghapusnya melalui tombol **Hapus** di menu **Admin > RBA Headers**.

