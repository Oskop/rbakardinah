# Walkthrough - Perbaikan Filter Status Akun & Peran / Role pada Manajemen User

Perbaikan masalah filter **Status Akun** dan **Peran / Role** pada halaman User Management Administrator (`/admin/users`) dan Supervisor (`/supervisor/users`) telah selesai diimplementasikan dan diverifikasi secara komprehensif.

---

## Akar Masalah & Solusi Teknis

### 1. Mengapa Filter Sebelumnya Gagal?
- **Spasi & Baris Baru pada DOM:**
  Teks peran (`Administrator`, `Supervisor`, `Operator`) dan status (`Active`, `Inactive`) sebelumnya dicetak di dalam tag `<span>` dengan indentasi Blade beberapa baris, menghasilkan konten string DOM dengan *leading/trailing whitespace* dan *newline* (seperti `"\n        Operator\n    "`).
- **Regex Boundary Ketat:**
  Pemanggilan filter DataTables menggunakan pola regex kaku `^Operator$` atau `^Active$`. Karena karakter string diawali oleh `\n` dan spasi, regex batas kata kaku tersebut **gagal mencocokkan baris data manapun**, yang menyebabkan tabel menjadi kosong saat filter dipilih.

### 2. Solusi yang Diterapkan
1. **Penyematan Atribut HTML5 `data-search` & `data-filter`:**
   - Menyematkan atribut langsung pada tag `<td>`:
     - `<td data-search="{{ $user->role }}" data-filter="{{ $user->role }}" ...>`
     - `<td data-search="{{ $user->unit ? $user->unit->name : 'Belum Ditugaskan' }}" data-filter="{{ $user->unit ? $user->unit->name : 'Belum Ditugaskan' }}" ...>`
     - `<td data-search="{{ $user->is_active ? 'Active' : 'Inactive' }}" data-filter="{{ $user->is_active ? 'Active' : 'Inactive' }}" ...>`
     - `<td data-search="{{ $user->auth_provider === 'simrs_oidc' ? 'SSO SIMRS' : 'Akun Lokal' }}" data-filter="{{ $user->auth_provider === 'simrs_oidc' ? 'SSO SIMRS' : 'Akun Lokal' }}" ...>`
   - DataTables 2.0 secara otomatis memprioritaskan nilai `data-search` murni ini untuk pengindeksan pencarian.
2. **Pembersihan Whitespace Template:**
   - Tag teks `<span>{{ $user->role }}</span>` dan `<span>{{ $user->is_active ? 'Active' : 'Inactive' }}</span>` dicetak rapat dalam 1 baris tanpa spasi liar.
3. **Regex Toleran Spasi & Helper `escapeRegex`:**
   - Skrip DataTables diperbarui menggunakan pola `^\\s*...\\s*$`:
     ```javascript
     const escapeRegex = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

     $('#filter-role').on('change', function() {
         const val = $(this).val();
         table.column(2).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
     });

     $('#filter-status').on('change', function() {
         const val = $(this).val();
         table.column(4).search(val ? '^\\s*' + escapeRegex(val) + '\\s*$' : '', true, false).draw();
     });
     ```

---

## File yang Dimodifikasi

- **[MODIFY] [index.blade.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php)**: Penambahan atribut `data-search`, pembersihan whitespace `<span>`, dan regex search toleran spasi.
- **[MODIFY] [index.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/users/index.blade.php)**: Penambahan atribut `data-search`, pembersihan whitespace `<span>`, dan regex search toleran spasi.
- **[MODIFY] [UserManagementTest.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UserManagementTest.php)**: Penambahan pengujian verifikasi atribut `data-search` Role, Status, Unit, dan Tipe Akun.
- **[MODIFY] [UserManagementTest.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/UserManagementTest.php)**: Penambahan pengujian verifikasi atribut `data-search` Role, Status, dan Tipe Akun.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests PASS
Seluruh test suite aplikasi **PASSED 100% (109 passed, 0 failed, 438 assertions)**:

```text
PASS  Tests\Feature\Admin\UserManagementTest
✓ admin can view user management index with filters                                                            1.23s  
✓ non admin cannot access admin user management                                                                0.04s  

PASS  Tests\Feature\Supervisor\UserManagementTest
✓ supervisor can view user management index with filters                                                       0.21s  
✓ operator cannot access supervisor user management                                                            0.03s  

Tests:    109 passed (438 assertions)
Duration: 37.75s
```

### 2. Frontend Assets Build (Bun) PASS
Asset frontend berhasil dikompilasi dengan `bun run build`:
- `public/build/assets/app-2aXSeJYB.css` (81.56 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.19s**
