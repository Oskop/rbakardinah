# Implementation Plan - Perbaikan Filter Status Akun & Peran / Role pada Manajemen User

Memperbaiki *bug* pada halaman User Management Administrator (`/admin/users`) dan Supervisor (`/supervisor/users`) di mana filter **Status Akun** dan **Peran / Role** tidak menampilkan data yang sesuai ketika dipilih.

---

## User Review Required

> [!IMPORTANT]
> **Akar Masalah (Root Cause):**
> 1. **Whitespace & Newline pada Konten HTML Cell (`<td>`):**
>    - Di dalam template Blade, teks role dan status dicetak di dalam tag `<span>` dengan indentasi dan baris baru:
>      ```blade
>      <td class="...">
>          <span class="...">
>              {{ $user->role }}
>          </span>
>      </td>
>      ```
>    - Ketika DataTables membaca konten teks cell, teks yang dibaca mengandung *newline* dan *spacing* (misalnya: `"\n        Operator\n    "`).
> 2. **Kegagalan Regex Boundary (`^` dan `$`) Tanpa Penanganan Whitespace:**
>    - Script JavaScript memfilter dengan regex batas awal-akhir ketat: `^Operator$`.
>    - Regex `^Operator$` mengharuskan string dimulai langsung dengan huruf `O` dan diakhiri dengan `r`, sehingga **gagal total** mencocokkan `"\n   Operator\n"`. Akibatnya, saat filter dipilih, tabel menjadi kosong.
>    - Hal yang sama terjadi pada Status Akun (`^Active$` dan `^Inactive$`).

---

## Proposed Changes

### 1. View User Management Administrator (`resources/views/admin/users/index.blade.php`)

#### [MODIFY] [index.blade.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php)
- **Menambahkan atribut `data-search` & `data-filter` murni pada setiap elemen `<td>`:**
  - Column 2 (Role): `<td data-search="{{ $user->role }}" data-filter="{{ $user->role }}" ...>`
  - Column 3 (Unit): `<td data-search="{{ $user->unit ? $user->unit->name : 'Belum Ditugaskan' }}" ...>`
  - Column 4 (Status): `<td data-search="{{ $user->is_active ? 'Active' : 'Inactive' }}" ...>`
  - Column 5 (Tipe Akun): `<td data-search="{{ $user->auth_provider === 'simrs_oidc' ? 'SSO SIMRS' : 'Akun Lokal' }}" ...>`
- **Menghilangkan whitespace berlebih di dalam tag `<span>`:**
  - `<span class="...">{{ $user->role }}</span>`
  - `<span class="...">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>`
- **Memperbarui Script Regex Search DataTables:**
  - Menggunakan helper `escapeRegex(val)` dan toleransi whitespace regex:
    ```javascript
    const escapeRegex = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    // Filter Role (Column 2)
    $('#filter-role').on('change', function() {
        const val = $(this).val();
        table.column(2).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
    });

    // Filter Status (Column 4)
    $('#filter-status').on('change', function() {
        const val = $(this).val();
        table.column(4).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
    });
    ```

---

### 2. View User Management Supervisor (`resources/views/supervisor/users/index.blade.php`)

#### [MODIFY] [index.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/users/index.blade.php)
- Menerapkan atribut `data-search` dan pembersihan whitespace yang sama pada cell Role, Status, dan Tipe Akun.
- Memperbarui script regex filter:
  - Filter Role (Column 2): `table.column(2).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();`
  - Filter Status (Column 3): `table.column(3).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();`
  - Filter Tipe Akun (Column 4): `table.column(4).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();`

---

### 3. Pengujian Otomatis (`tests/Feature/Admin/UserManagementTest.php` & `tests/Feature/Supervisor/UserManagementTest.php`)

#### [MODIFY] [UserManagementTest.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UserManagementTest.php)
- Memverifikasi keberadaan atribut `data-search` untuk role, status, unit, dan provider pada baris tabel pengguna Admin.

#### [MODIFY] [UserManagementTest.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/UserManagementTest.php)
- Memverifikasi keberadaan atribut `data-search` untuk role, status, dan provider pada baris tabel pengguna Supervisor.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite user management:
  `php artisan test --filter=UserManagementTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Buka browser pada `/admin/users`:
   - Pilih filter **Peran / Role** = `Administrator` -> Verifikasi hanya baris Administrator yang tampil.
   - Pilih filter **Peran / Role** = `Supervisor` -> Verifikasi hanya baris Supervisor yang tampil.
   - Pilih filter **Peran / Role** = `Operator` -> Verifikasi hanya baris Operator yang tampil.
   - Pilih filter **Status Akun** = `Active` -> Verifikasi hanya user Active yang tampil.
   - Pilih filter **Status Akun** = `Inactive` -> Verifikasi hanya user Inactive yang tampil (tidak tercampur dengan Active).
   - Klik **Reset Semua Filter** -> Verifikasi seluruh data kembali tampil.
2. Buka browser pada `/supervisor/users`:
   - Uji filter Role dan Status pada unit kerja supervisor.
