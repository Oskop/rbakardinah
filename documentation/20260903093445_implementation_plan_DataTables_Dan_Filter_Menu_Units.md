# Implementation Plan - Penerapan DataTables dan Filter Kolom pada Menu Units Administrator

Mengintegrasikan pustaka **DataTables v2 (TailwindCSS)** serta **Toolbar Filter Kolom Interaktif** pada halaman Manajemen Unit Kerja Administrator (`admin.units.index`), mengadopsi standar dan arsitektur yang telah sukses diterapkan pada menu Users.

---

## User Review Required

> [!IMPORTANT]
> **Fitur DataTables & Filter yang Diterapkan pada Menu Units:**
> 1. **DataTables Modern (TailwindCSS Adapter):**
>    - Paginasi instan (*client-side pagination* dengan pilihan 10, 25, 50, 100 data).
>    - Pengurutan dinamis (*sorting*) per kolom (Kode Unit, Nama Unit, Status, dan Jumlah Pengguna).
>    - Pencarian bebas global (*instant search bar*) untuk pencarian cepat nama atau kode unit.
>    - Informasi jumlah data dalam Bahasa Indonesia: *"Menampilkan _START_ sampai _END_ dari _TOTAL_ unit kerja"*.
> 2. **Toolbar Filter Khusus Kolom Unit Kerja:**
>    - **Filter Status:** Dropdown pilihan `Semua Status`, `Active`, dan `Inactive` menggunakan exact regex match (`^Active$` / `^Inactive$`).
>    - **Filter Keterkaitan Pengguna:** Dropdown untuk menyaring unit yang telah memiliki pengguna terdaftar atau unit kosong (`Semua`, `Memiliki Pengguna`, `Belum Ada Pengguna`).
>    - **Tombol Reset Filter:** Mengosongkan seluruh filter dan pencarian sekaligus dengan sekali klik (`🔄 Reset Semua Filter`).
> 3. **Penyajian Data Tambahan (Informatif):**
>    - Menampilkan kolom **Jumlah Pengguna Terdaftar** (`withCount('users')`), sehingga Administrator dapat langsung melihat berapa banyak akun (Supervisor/Operator) yang bernaung di bawah unit tersebut sebelum memutuskan menonaktifkan unit.

---

## Proposed Changes

### 1. Controller Layer

#### [MODIFY] [UnitController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/UnitController.php)
- Pada method `index()`:
  - Memuat data unit lengkap dengan hitungan relasi pengguna:
    ```php
    $units = \App\Models\Unit::withCount('users')->orderBy('name')->get();
    ```

---

### 2. View Layer

#### [MODIFY] [index.blade.php (Units)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/index.blade.php)
- Menambahkan card **Toolbar Filter Kolom Unit Kerja**:
  - Filter Status (`#filter-status`): `Active` vs `Inactive`.
  - Filter Pengguna (`#filter-user-count`): `Semua`, `Ada Pengguna`, `Tanpa Pengguna`.
  - Tombol Reset Filter (`#btn-reset-filters`).
- Mengadaptasi tabel `#units-table`:
  - Kolom 1: **Kode Unit** (font mono tebal).
  - Kolom 2: **Nama Unit Kerja**.
  - Kolom 3: **Pengguna Terdaftar** (badge jumlah pengguna, e.g. `3 Pengguna`).
  - Kolom 4: **Status** (badge `Active` / `Inactive` dengan atribut `data-search` dan `data-filter`).
  - Kolom 5: **Aksi** (Edit & Nonaktifkan/Aktifkan, dengan proteksi `orderable: false, searchable: false`).
- Menambahkan CSS & JavaScript DataTables pada `@push('styles')` dan `@push('scripts')`:
  - Memuat jQuery 3.7.1, DataTables 2.0.3 core, dan adapter TailwindCSS.
  - Inisialisasi DataTable dengan pelokalan Bahasa Indonesia dan event listener filter berbasis regex.

---

### 3. Automated Tests Layer

#### [MODIFY] [UnitManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UnitManagementTest.php)
- Memperbarui pengujian pada `test_admin_can_view_units_index_with_status_column`:
  - Memverifikasi keberadaan elemen UI DataTables (`#units-table`).
  - Memverifikasi filter toolbar (`#filter-status`, `#btn-reset-filters`).
  - Memverifikasi atribut `data-search` dan `data-filter` pada baris unit.
  - Memverifikasi kolom jumlah pengguna terdaftar.

---

## Verification Plan

### Automated Tests
1. Menjalankan test suite Unit Management:
   ```powershell
   php artisan test --filter=UnitManagementTest
   ```
2. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke menu **Units** (`/admin/units`).
3. Verifikasi tampilan:
   - Terdapat **Toolbar Filter Kolom Unit Kerja** di atas tabel.
   - Tabel menampilkan kontrol DataTables: pilihan jumlah data (*length menu*), kotak *Cari Bebas*, dan penomoran halaman (*pagination*).
4. Uji filter:
   - Pilih filter status `Active` $\rightarrow$ hanya unit aktif yang tampil.
   - Pilih filter status `Inactive` $\rightarrow$ hanya unit nonaktif yang tampil.
   - Ketik kode atau nama unit di kotak "Cari Bebas" $\rightarrow$ hasil tersaring instan.
   - Klik **Reset Semua Filter** $\rightarrow$ seluruh data unit kembali tampil normal.
