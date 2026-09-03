# Implementation Plan - Nonaktifkan Unit (Bukan Hapus) dan Pencatatan Log Aktivitas

Karena data Unit merupakan data induk (*parent data*) yang terhubung langsung dengan berbagai relasi krusial (seperti akun pengguna `users`, pengajuan anggaran `rba_submissions`, dan dokumen belanja), maka fitur penghapusan permanen (*hard delete*) pada menu Unit Administrator akan **ditiadakan dan diganti dengan mekanisme Nonaktifkan / Aktifkan (*deactivate/activate*)**. Setiap tindakan perubahan status unit ini **wajib tercatat secara otomatis ke dalam Audit Log Data (`activity_logs`)**.

---

## User Review Required

> [!IMPORTANT]
> **Keputusan Desain & Integritas Data Parent:**
> 1. **Peniadaan Tombol Delete:**
>    - Tombol "Delete" dihapus seluruhnya dari antarmuka menu Units.
>    - Digantikan dengan tombol status **"Nonaktifkan" (Deactivate)** dan **"Aktifkan" (Activate)**.
> 2. **Integritas Foreign Key & Riwayat Anggaran:**
>    - Unit tidak akan pernah terhapus dari tabel database `units`, sehingga integritas data pengajuan historis (`rba_submissions`) dan akun pengguna (`users`) tetap 100% aman dan konsisten.
> 3. **Pencatatan ke Audit Log Data (`activity_logs`):**
>    - Trait `LogsActivity` pada model `Unit` disempurnakan sehingga ketika status unit diubah, log akan mencatat secara deskriptif:
>      - *"Nama Admin (Administrator) menonaktifkan Unit Kerja: 'Nama Unit'"*
>      - *"Nama Admin (Administrator) mengaktifkan Unit Kerja: 'Nama Unit'"*
>      - Menyimpan nilai lama dan baru (`old_values` dan `new_values`) serta alamat IP dan User Agent.

---

## Proposed Changes

### 1. Database Layer

#### [NEW] [2026_09_03_092500_add_is_active_to_units_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_092500_add_is_active_to_units_table.php)
- Menambahkan kolom `is_active` bertipe boolean dengan default `true` pada tabel `units`.

---

### 2. Model & Audit Trait Layer

#### [MODIFY] [Unit.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/Unit.php)
- Menambahkan `is_active` ke properti `$fillable`.
- Menambahkan cast `'is_active' => 'boolean'`.

#### [MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)
- Menyesuaikan method `generateActivityDescription` khusus untuk model `Unit`:
  - Jika terjadi `updated` pada kolom `is_active`: menghasilkan narasi otomatis *"menonaktifkan Unit Kerja: '{unitName}'"* atau *"mengaktifkan Unit Kerja: '{unitName}'"*.

---

### 3. Controller Layer

#### [MODIFY] [UnitController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/UnitController.php)
- Mengubah fungsi `destroy(\App\Models\Unit $unit)`:
  - Mengganti pemanggilan `$unit->delete()` menjadi pembaruan status:
    ```php
    $unit->update(['is_active' => !$unit->is_active]);
    ```
  - Mengembalikan pesan notifikasi sukses: *"Unit {name} berhasil dinonaktifkan"* atau *"Unit {name} berhasil diaktifkan"*.
- Memperbarui method `store` dan `update` untuk mendukung field status `is_active`.

---

### 4. View Layer

#### [MODIFY] [index.blade.php (Units)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/index.blade.php)
- Menambahkan kolom **Status** pada tabel unit:
  - Badge `Active` (hijau) jika unit aktif.
  - Badge `Inactive` (abu-abu) jika unit nonaktif.
- Mengganti tombol `Delete` dengan tombol aksi toggle:
  - Jika unit aktif: tombol merah/amber **"Nonaktifkan" / "Deactivate"** disertai dialog konfirmasi: *"Apakah Anda yakin ingin menonaktifkan unit ini?"*.
  - Jika unit nonaktif: tombol hijau **"Aktifkan" / "Activate"** disertai dialog konfirmasi: *"Apakah Anda yakin ingin mengaktifkan unit ini?"*.

#### [MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/edit.blade.php)
- Menambahkan input checkbox / pilihan status `is_active` pada formulir tambah & edit unit.

---

### 5. Automated Tests Layer

#### [NEW] [UnitManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UnitManagementTest.php)
- Menulis unit/feature test:
  1. `test_admin_can_view_units_index_with_status_column()`: Memastikan daftar unit memuat kolom status dan badge Active/Inactive.
  2. `test_admin_can_deactivate_unit_instead_of_deleting()`: Memastikan pemanggilan action menonaktifkan unit tanpa menghapus baris dari database (`assertDatabaseHas('units', ['id' => $unit->id, 'is_active' => false])`).
  3. `test_admin_can_reactivate_unit()`: Memastikan unit yang nonaktif dapat diaktifkan kembali.
  4. `test_unit_deactivation_is_recorded_in_activity_log()`: Memastikan entri riwayat tercatat di tabel `activity_logs` dengan deskripsi *"menonaktifkan Unit Kerja"*.
  5. `test_non_admin_cannot_manage_units()`: Memastikan otorisasi admin tetap terjaga (403 untuk non-admin).

---

## Verification Plan

### Automated Tests
1. Menjalankan migrasi database:
   ```powershell
   php artisan migrate
   ```
2. Menjalankan test suite Unit Management:
   ```powershell
   php artisan test --filter=UnitManagementTest
   ```
3. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke menu **Units** (`/admin/units`).
3. Periksa tabel Unit:
   - Pastikan terdapat kolom **Status** dengan badge `Active` / `Inactive`.
   - Pastikan tombol **Delete** sudah tidak ada, berganti menjadi **Nonaktifkan** atau **Aktifkan**.
4. Klik tombol **Nonaktifkan** pada salah satu unit:
   - Verifikasi status unit berubah menjadi `Inactive`.
   - Verifikasi data unit tetap ada di database (tidak terhapus).
5. Buka menu **Log Data / Activity Log** (`/admin/logs`):
   - Verifikasi terdapat catatan baru: *"Administrator menonaktifkan Unit Kerja: '[Nama Unit]'"*.
6. Kembali ke menu Units dan klik **Aktifkan**:
   - Verifikasi status kembali menjadi `Active` dan log data mencatat *"Administrator mengaktifkan Unit Kerja: '[Nama Unit]'"*.
