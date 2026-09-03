# Implementation Plan - Soft Delete (Nonaktifkan) pada Menu Nomor Rekening (Account Code) dan Pencatatan Log Aktivitas

Menerapkan mekanisme **soft delete (nonaktifkan / *deactivate*, bukan hapus permanen)** pada menu **Nomor Rekening (Account Code)** Administrator (`admin.account-codes.*`). Kebijakan ini krusial untuk menjaga integritas data induk (*parent data*) terhadap rincian usulan belanja unit (`rba_details`) dan penetapan pagu rekening (`rba_account_pagus`), serta memastikan setiap tindakan aktivasi/deaktivasi tercatat secara otomatis ke dalam **Audit Log Data (`activity_logs`)**.

---

## User Review Required

> [!IMPORTANT]
> **Keputusan Desain & Integritas Relasi Nomor Rekening (Account Code):**
> 1. **Peniadaan Tombol Delete (Ganti dengan Toggle Nonaktifkan/Aktifkan):**
>    - Tombol "Delete" dihapus seluruhnya dari antarmuka menu Nomor Rekening.
>    - Digantikan dengan tombol status **"Nonaktifkan"** (warna amber) dan **"Aktifkan"** (warna hijau) dengan dialog konfirmasi yang jelas.
>    - Rekening tidak akan pernah dihapus dari tabel database `account_codes`, sehingga tidak ada risiko error *foreign key constraint* pada data usulan belanja (`rba_details`) maupun penetapan pagu (`rba_account_pagus`).
> 2. **Pencatatan Otomatis ke Audit Log Data (`activity_logs`):**
>    - Trait `LogsActivity` disempurnakan sehingga menghasilkan narasi log yang jelas dan informatif:
>      - *"Administrator menonaktifkan Nomor Rekening: '5.1.02.01.01.0024 - Belanja Alat/Bahan untuk Kegiatan Kantor'"*
>      - *"Administrator mengaktifkan Nomor Rekening: '5.1.02.01.01.0024 - Belanja Alat/Bahan untuk Kegiatan Kantor'"*
> 3. **Peningkatan UX & DataTables Filter Kolom:**
>    - Mengadopsi standar yang telah diterapkan pada menu Users, Units, dan Kelompok Belanja:
>      - Tabel DataTables modern (TailwindCSS) dengan pencarian bebas (*instant search*), paginasi instan (10, 25, 50, 100), dan pengurutan dinamis.
>      - Toolbar Filter Kolom: Filter Status (`Active` vs `Inactive`) dan Filter Kelompok Belanja.
> 4. **Proteksi Penginputan Usulan Belanja Operator:**
>    - Pada form tambah rincian usulan RBA operator (`DetailController@create`), nomor rekening yang berstatus **nonaktif** otomatis disaring sehingga operator tidak dapat memilih rekening yang sudah dinonaktifkan.
>    - Pada usulan yang sudah ada sebelumnya, data tetap dapat ditampilkan secara utuh (*backward compatible*).

---

## Proposed Changes

### 1. Database Layer

#### [NEW] [2026_09_03_103000_add_is_active_to_account_codes_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_103000_add_is_active_to_account_codes_table.php)
- Menambahkan kolom `is_active` bertipe boolean dengan default `true` pada tabel `account_codes`.

---

### 2. Model & Audit Trait Layer

#### [MODIFY] [AccountCode.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/AccountCode.php)
- Menambahkan `is_active` ke properti `$fillable`.
- Menambahkan method `casts()` dengan `'is_active' => 'boolean'`.

#### [MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)
- Menyesuaikan method `generateActivityDescription` untuk model `AccountCode`:
  - Jika terjadi perubahan status pada kolom `is_active`: menghasilkan narasi *"menonaktifkan Nomor Rekening: '[kode] - [name]'"* atau *"mengaktifkan Nomor Rekening: '[kode] - [name]'"*.

---

### 3. Controller Layer

#### [MODIFY] [AccountCodeController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/AccountCodeController.php)
- Pada method `index()`:
  - Mengambil data rekening lengkap dengan relasi kelompok belanja dan pengurutan kode:
    ```php
    $accountCodes = \App\Models\AccountCode::with('kelompokBelanja')->orderBy('code')->get();
    $kelompokBelanjas = \App\Models\KelompokBelanja::orderBy('kode')->get();
    ```
- Pada method `store()` & `update()`:
  - Mendukung validasi dan penyimpanan field `is_active`.
- Pada method `destroy(\App\Models\AccountCode $accountCode)`:
  - Mengganti pemanggilan `$accountCode->delete()` menjadi pembaruan status toggle:
    ```php
    $accountCode->update(['is_active' => !$accountCode->is_active]);
    ```
  - Mengembalikan notifikasi sukses: *"Nomor Rekening {name} berhasil dinonaktifkan"* atau *"Nomor Rekening {name} berhasil diaktifkan"*.

#### [MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Pada method `create()`:
  - Menyaring hanya rekening yang aktif:
    ```php
    $accountCodes = AccountCode::where('is_active', true)->whereNotIn('id', $lockedAccountIds)->get();
    ```
- Pada method `edit()`:
  - Memastikan rekening aktif atau rekening yang sedang diedit tetap dapat diakses.

---

### 4. View Layer

#### [MODIFY] [index.blade.php (Account Codes)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/account-codes/index.blade.php)
- Menambahkan card **Toolbar Filter Kolom Nomor Rekening**:
  - Filter Status (`#filter-status`): `Active` vs `Inactive`.
  - Filter Kelompok Belanja (`#filter-kelompok`): Dropdown nama-nama kelompok belanja.
  - Tombol Reset Filter (`#btn-reset-filters`).
- Mengintegrasikan tabel `#account-codes-table` dengan DataTables v2 TailwindCSS:
  - Kolom 1: **Kode Rekening** (font mono tebal).
  - Kolom 2: **Nama Rekening Belanja**.
  - Kolom 3: **Kelompok Belanja** (nama kelompok belanja terkait).
  - Kolom 4: **Status** (badge `Active` / `Inactive` dengan atribut `data-search` dan `data-filter`).
  - Kolom 5: **Aksi** (Edit dan tombol toggle Nonaktifkan/Aktifkan).
- Menambahkan skrip DataTables dengan pencarian bebas instan, paginasi, dan filter regex.

#### [MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/account-codes/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/account-codes/edit.blade.php)
- Menambahkan kontrol checkbox status aktif (`is_active`).

---

### 5. Automated Tests Layer

#### [NEW] [AccountCodeTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AccountCodeTest.php)
- Menulis unit/feature test:
  1. `test_admin_can_view_account_codes_index_with_status_and_filters()`: Memverifikasi antarmuka DataTables, filter status, filter kelompok belanja, dan peniadaan tombol delete.
  2. `test_admin_can_deactivate_account_code_instead_of_deleting()`: Memverifikasi pemanggilan destroy mengubah status menjadi `is_active = false` tanpa menghapus baris dari database.
  3. `test_admin_can_reactivate_account_code()`: Memverifikasi nomor rekening yang nonaktif dapat diaktifkan kembali.
  4. `test_account_code_deactivation_is_recorded_in_activity_log()`: Memverifikasi tercatat di tabel `activity_logs`.
  5. `test_inactive_account_code_cannot_be_selected_by_operator_for_new_rba_detail()`: Memverifikasi rekening nonaktif tidak muncul pada form input usulan operator.

---

## Verification Plan

### Automated Tests
1. Menjalankan migrasi database:
   ```powershell
   php artisan migrate
   ```
2. Menjalankan test suite Account Code:
   ```powershell
   php artisan test --filter=AccountCodeTest
   ```
3. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke menu **Nomor Rekening** (`/admin/account-codes`).
3. Periksa tabel:
   - Pastikan terdapat toolbar filter dan tabel DataTables interaktif.
   - Pastikan tombol **Delete** sudah ditiadakan, berganti menjadi tombol **Nonaktifkan** / **Aktifkan**.
4. Klik tombol **Nonaktifkan**:
   - Verifikasi status berubah menjadi `Inactive` dan data tidak terhapus.
   - Buka menu **Log Data / Activity Log** (`/admin/logs`) dan periksa catatan riwayat deaktivasinya.
5. Login sebagai **Operator**:
   - Masuk ke penginputan RBA dan klik Tambah Usulan Belanja.
   - Verifikasi nomor rekening yang telah dinonaktifkan tidak muncul dalam pilihan dropdown.
