# Implementation Plan - Soft Delete (Nonaktifkan) pada Menu Kelompok Belanja dan Pencatatan Log Aktivitas

Menerapkan mekanisme **soft delete (nonaktifkan / *deactivate*, bukan hapus permanen)** pada menu **Kelompok Belanja** Administrator (`admin.kelompok-belanja.*`). Kebijakan ini bertujuan menjaga integritas data induk (*parent data*) terhadap data akun rekening belanja (`account_codes`) dan transaksi pengajuan anggaran RBA, serta memastikan setiap tindakan aktivasi/deaktivasi tercatat secara otomatis ke dalam **Audit Log Data (`activity_logs`)**.

---

## User Review Required

> [!IMPORTANT]
> **Keputusan Desain & Integritas Relasi Kelompok Belanja:**
> 1. **Peniadaan Tombol Delete (Ganti dengan Toggle Nonaktifkan/Aktifkan):**
>    - Tombol "Delete" dihapus seluruhnya dari antarmuka Kelompok Belanja.
>    - Digantikan dengan tombol status **"Nonaktifkan"** (warna amber) dan **"Aktifkan"** (warna hijau) dengan dialog konfirmasi yang jelas.
>    - Data kelompok belanja tidak akan pernah dihapus dari tabel database `kelompok_belanjas`, sehingga tidak ada risiko *broken foreign key* pada nomor rekening belanja (`account_codes`) maupun data anggaran historis.
> 2. **Pencatatan Otomatis ke Audit Log Data (`activity_logs`):**
>    - Trait `LogsActivity` pada model `KelompokBelanja` disesuaikan sehingga mencatat narasi otomatis:
>      - *"Administrator menonaktifkan Kelompok Belanja: '5.1.02 - Belanja Barang dan Jasa'"*
>      - *"Administrator mengaktifkan Kelompok Belanja: '5.1.02 - Belanja Barang dan Jasa'"*
> 3. **Peningkatan UX & DataTables Filter:**
>    - Mengadopsi standar yang telah sukses diterapkan pada menu Users dan Units:
>      - Tabel DataTables modern (TailwindCSS) dengan pencarian bebas, paginasi instan, dan pengurutan dinamis.
>      - Toolbar Filter Kolom: Filter Status (`Active` vs `Inactive`) dan Keterikatan Rekening.
>      - Kolom informatif **Rekening Terkait** (`withCount('accountCodes')`), sehingga Administrator mengetahui berapa banyak nomor rekening belanja yang menginduk pada kelompok tersebut.
> 4. **Pencegahan Pemilihan pada Rekening Baru:**
>    - Pada form pembuatan Nomor Rekening Belanja baru (`AccountCodeController@create`), hanya kelompok belanja yang **aktif** yang dapat dipilih.

---

## Proposed Changes

### 1. Database Layer

#### [NEW] [2026_09_03_095500_add_is_active_to_kelompok_belanjas_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_095500_add_is_active_to_kelompok_belanjas_table.php)
- Menambahkan kolom `is_active` bertipe boolean dengan default `true` pada tabel `kelompok_belanjas`.

---

### 2. Model & Audit Trait Layer

#### [MODIFY] [KelompokBelanja.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/KelompokBelanja.php)
- Menambahkan `is_active` ke `$fillable`.
- Menambahkan method `casts()` dengan `'is_active' => 'boolean'`.

#### [MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)
- Menambahkan penanganan khusus pada `generateActivityDescription` untuk model `KelompokBelanja`:
  - Jika terjadi perubahan pada kolom `is_active`: menghasilkan narasi *"menonaktifkan Kelompok Belanja: '[kode] - [name]'"* atau *"mengaktifkan Kelompok Belanja: '[kode] - [name]'"*.

---

### 3. Controller Layer

#### [MODIFY] [KelompokBelanjaController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/KelompokBelanjaController.php)
- Pada `index()`:
  - Memuat data kelompok belanja dengan hitungan relasi rekening:
    ```php
    $groups = \App\Models\KelompokBelanja::withCount('accountCodes')->orderBy('kode')->get();
    ```
- Pada `store()` & `update()`:
  - Mendukung validasi dan penyimpanan field `is_active`.
- Pada `destroy(\App\Models\KelompokBelanja $kelompokBelanja)`:
  - Mengganti pemanggilan `$kelompokBelanja->delete()` menjadi pembaruan status:
    ```php
    $kelompokBelanja->update(['is_active' => !$kelompokBelanja->is_active]);
    ```
  - Mengembalikan pesan notifikasi sukses: *"Kelompok Belanja {name} berhasil dinonaktifkan"* atau *"Kelompok Belanja {name} berhasil diaktifkan"*.

#### [MODIFY] [AccountCodeController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/AccountCodeController.php)
- Pada `create()`:
  - Hanya memuat kelompok belanja yang aktif: `KelompokBelanja::where('is_active', true)->orderBy('kode')->get();`.
- Pada `edit()`:
  - Memuat kelompok belanja aktif serta kelompok belanja yang saat ini sudah terpasang pada rekening tersebut (agar data historis tidak hilang dari dropdown).

---

### 4. View Layer

#### [MODIFY] [index.blade.php (Kelompok Belanja)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/index.blade.php)
- Menambahkan card **Toolbar Filter Kolom Kelompok Belanja** di atas tabel (Filter Status, Filter Rekening, dan tombol Reset).
- Mengintegrasikan tabel `#kelompok-belanja-table` dengan DataTables v2 TailwindCSS:
  - Kolom 1: **Kode Kelompok Belanja** (font mono tebal).
  - Kolom 2: **Nama Kelompok Belanja**.
  - Kolom 3: **Rekening Terdaftar** (badge jumlah rekening terkait, e.g. `📋 5 Rekening`).
  - Kolom 4: **Status** (badge `Active` / `Inactive` dengan atribut `data-search` dan `data-filter`).
  - Kolom 5: **Aksi** (Edit dan tombol toggle Nonaktifkan/Aktifkan).
- Menambahkan skrip DataTables dengan pencarian bebas, paginasi, dan filter regex.

#### [MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/kelompok-belanjas/edit.blade.php)
- Menambahkan kontrol checkbox status aktif (`is_active`).

---

### 5. Automated Tests Layer

#### [MODIFY] [KelompokBelanjaTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/KelompokBelanjaTest.php)
- Memperluas pengujian:
  1. `test_admin_can_view_kelompok_belanja_index_with_status_and_filters()`: Memverifikasi tampilan tabel, kolom status, kolom rekening terdaftar, dan toolbar filter.
  2. `test_admin_can_deactivate_kelompok_belanja_instead_of_deleting()`: Memverifikasi pemanggilan destroy mengubah status menjadi `is_active = false` tanpa menghapus baris dari database.
  3. `test_admin_can_reactivate_kelompok_belanja()`: Memverifikasi kelompok nonaktif dapat diaktifkan kembali.
  4. `test_kelompok_belanja_deactivation_is_recorded_in_activity_log()`: Memverifikasi tercatat di tabel `activity_logs`.
  5. `test_inactive_kelompok_belanja_cannot_be_selected_for_new_account_code()`: Memverifikasi kelompok nonaktif tidak muncul pada form pembuatan nomor rekening baru.

---

## Verification Plan

### Automated Tests
1. Menjalankan migrasi database:
   ```powershell
   php artisan migrate
   ```
2. Menjalankan test suite Kelompok Belanja:
   ```powershell
   php artisan test --filter=KelompokBelanjaTest
   ```
3. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke menu **Kelompok Belanja** (`/admin/kelompok-belanja`).
3. Periksa tabel:
   - Pastikan terdapat toolbar filter dan tabel DataTables interaktif.
   - Pastikan tombol **Delete** sudah ditiadakan, berganti menjadi tombol **Nonaktifkan** / **Aktifkan**.
4. Klik tombol **Nonaktifkan**:
   - Verifikasi status berubah menjadi `Inactive` dan data tidak terhapus.
   - Buka menu **Log Data / Activity Log** (`/admin/logs`) dan periksa catatan riwayat deaktivasinya.
5. Buka menu **Nomor Rekening** $\rightarrow$ Tambah Rekening Baru:
   - Verifikasi kelompok belanja yang telah dinonaktifkan tidak muncul dalam pilihan dropdown.
