# Implementation Plan - Soft Delete (Nonaktifkan) pada Menu Periode RBA dan Pencatatan Log Aktivitas

Menerapkan mekanisme **soft delete (nonaktifkan / *deactivate*, bukan hapus permanen)** pada menu **Periode RBA** Administrator (`admin.periods.*`). Kebijakan ini krusial untuk menjaga integritas data induk (*parent data*) terhadap dokumen pengajuan dan struktur anggaran RBA (`rba_headers`), serta memastikan setiap tindakan aktivasi/deaktivasi tercatat secara otomatis ke dalam **Audit Log Data (`activity_logs`)**.

---

## User Review Required

> [!IMPORTANT]
> **Keputusan Desain & Integritas Relasi Periode RBA:**
> 1. **Peniadaan Tombol Delete (Ganti dengan Toggle Nonaktifkan/Aktifkan):**
>    - Tombol "Delete" dihapus seluruhnya dari antarmuka menu Periode.
>    - Digantikan dengan tombol status **"Nonaktifkan"** (warna amber) dan **"Aktifkan"** (warna hijau) dengan dialog konfirmasi yang jelas.
>    - Data periode tidak akan pernah dihapus dari tabel database `rba_periods`, sehingga tidak ada risiko error *foreign key constraint* pada data anggaran utama rumah sakit (`rba_headers`).
> 2. **Pencatatan Otomatis ke Audit Log Data (`activity_logs`):**
>    - Trait `LogsActivity` disempurnakan sehingga menghasilkan narasi log yang jelas dan informatif:
>      - *"Administrator menonaktifkan Periode RBA: 'Perencanaan Murni'"*
>      - *"Administrator mengaktifkan Periode RBA: 'Perencanaan Murni'"*
>      - Menyimpan nilai lama dan baru (`old_values` & `new_values`), IP address, dan user agent.
> 3. **Peningkatan UX & DataTables Filter Kolom:**
>    - Mengadopsi standar yang telah sukses diterapkan pada menu Users, Units, Kelompok Belanja, dan Nomor Rekening:
>      - Tabel DataTables modern (TailwindCSS) dengan pencarian bebas (*instant search*), paginasi instan (10, 25, 50, 100), dan pengurutan dinamis.
>      - Toolbar Filter Kolom: Filter Status (`Active` vs `Inactive`) dan Filter Keterikatan RBA Header.
>      - Kolom informatif **RBA Terdaftar** (`withCount('headers')`), sehingga Administrator mengetahui berapa banyak dokumen RBA yang menggunakan periode tersebut sebelum memutuskan untuk menonaktifkannya.
> 4. **Proteksi Pembuatan RBA Baru:**
>    - Pada form pembuatan RBA Header baru (`RbaHeaderController@create`), hanya periode yang berstatus **aktif** yang dapat dipilih.

---

## Proposed Changes

### 1. Database Layer

#### [NEW] [2026_09_03_113500_add_is_active_to_rba_periods_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_03_113500_add_is_active_to_rba_periods_table.php)
- Menambahkan kolom `is_active` bertipe boolean dengan default `true` pada tabel `rba_periods`.

---

### 2. Model & Audit Trait Layer

#### [MODIFY] [RbaPeriod.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaPeriod.php)
- Menambahkan `is_active` ke properti `$fillable`.
- Menambahkan method `casts()` dengan `'is_active' => 'boolean'`.
- Menambahkan relasi `headers()` ke model `RbaHeader`:
  ```php
  public function headers(): \Illuminate\Database\Eloquent\Relations\HasMany
  {
      return $this->hasMany(RbaHeader::class, 'period_id');
  }
  ```

#### [MODIFY] [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php)
- Menyesuaikan method `generateActivityDescription` untuk model `RbaPeriod`:
  - Jika terjadi perubahan status pada kolom `is_active`: menghasilkan narasi *"menonaktifkan Periode RBA: '{periodName}'"* atau *"mengaktifkan Periode RBA: '{periodName}'"*.

---

### 3. Controller Layer

#### [MODIFY] [RbaPeriodController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaPeriodController.php)
- Pada method `index()`:
  - Mengambil data periode lengkap dengan hitungan relasi RBA header:
    ```php
    $periods = \App\Models\RbaPeriod::withCount('headers')->orderBy('name')->get();
    ```
- Pada method `store()` & `update()`:
  - Mendukung validasi dan penyimpanan field `is_active`.
- Pada method `destroy(\App\Models\RbaPeriod $period)`:
  - Mengganti pemanggilan `$period->delete()` menjadi pembaruan status toggle:
    ```php
    $period->update(['is_active' => !$period->is_active]);
    ```
  - Mengembalikan notifikasi sukses: *"Periode RBA {name} berhasil dinonaktifkan"* atau *"Periode RBA {name} berhasil diaktifkan"*.

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Pada method `create()`:
  - Menyaring hanya periode yang aktif:
    ```php
    $periods = \App\Models\RbaPeriod::where('is_active', true)->orderBy('name')->get();
    ```

---

### 4. View Layer

#### [MODIFY] [index.blade.php (Periods)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/periods/index.blade.php)
- Menambahkan card **Toolbar Filter Kolom Periode RBA**:
  - Filter Status (`#filter-status`): `Active` vs `Inactive`.
  - Filter Keterikatan RBA (`#filter-headers`): `Semua Periode`, `Digunakan dalam RBA`, `Belum Digunakan (0)`.
  - Tombol Reset Filter (`#btn-reset-filters`).
- Mengintegrasikan tabel `#periods-table` dengan DataTables v2 TailwindCSS:
  - Kolom 1: **ID**.
  - Kolom 2: **Nama Periode RBA**.
  - Kolom 3: **RBA Terdaftar** (badge jumlah RBA terkait, e.g. `📊 2 RBA`).
  - Kolom 4: **Status** (badge `Active` / `Inactive` dengan atribut `data-search` dan `data-filter`).
  - Kolom 5: **Aksi** (Edit dan tombol toggle Nonaktifkan/Aktifkan).
- Menambahkan skrip DataTables dengan pencarian bebas instan, paginasi, dan filter regex.

#### [MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/periods/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/periods/edit.blade.php)
- Menambahkan kontrol checkbox status aktif (`is_active`).

---

### 5. Automated Tests Layer

#### [NEW] [PeriodManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PeriodManagementTest.php)
- Menulis unit/feature test:
  1. `test_admin_can_view_periods_index_with_status_and_filters()`: Memverifikasi antarmuka DataTables, filter status, filter keterikatan RBA, dan peniadaan tombol delete.
  2. `test_admin_can_deactivate_period_instead_of_deleting()`: Memverifikasi pemanggilan destroy mengubah status menjadi `is_active = false` tanpa menghapus baris dari database.
  3. `test_admin_can_reactivate_period()`: Memverifikasi periode nonaktif dapat diaktifkan kembali.
  4. `test_period_deactivation_is_recorded_in_activity_log()`: Memverifikasi tercatat di tabel `activity_logs`.
  5. `test_inactive_period_cannot_be_selected_when_creating_new_rba_header()`: Memverifikasi periode nonaktif tidak muncul pada form pembuatan RBA Header baru.
  6. `test_admin_can_create_and_update_period_with_status()`: Memverifikasi form create & update mendukung flag `is_active`.

---

## Verification Plan

### Automated Tests
1. Menjalankan migrasi database:
   ```powershell
   php artisan migrate
   ```
2. Menjalankan test suite Period Management:
   ```powershell
   php artisan test --filter=PeriodManagementTest
   ```
3. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke menu **Periode** (`/admin/periods`).
3. Periksa tabel:
   - Pastikan terdapat toolbar filter dan tabel DataTables interaktif.
   - Pastikan tombol **Delete** sudah ditiadakan, berganti menjadi tombol **Nonaktifkan** / **Aktifkan**.
4. Klik tombol **Nonaktifkan**:
   - Verifikasi status berubah menjadi `Inactive` dan data tidak terhapus.
   - Buka menu **Log Data / Activity Log** (`/admin/logs`) dan periksa catatan riwayat deaktivasinya.
5. Buka menu **RBA Headers** $\rightarrow$ Buat RBA Baru:
   - Verifikasi periode yang telah dinonaktifkan tidak muncul dalam pilihan dropdown.
