# Walkthrough - Penerapan DataTables dan Filter Kolom pada Menu Units Administrator

Penerapan library **DataTables v2 (TailwindCSS)** serta **Toolbar Filter Kolom Interaktif** pada halaman Manajemen Unit Kerja Administrator (`admin.units.index`) telah selesai diimplementasikan, diverifikasi, dan diuji secara menyeluruh.

---

## Ringkasan Fitur yang Diterapkan

### 1. Integrasi DataTables Modern (TailwindCSS)
- Tabel unit kerja (`#units-table`) kini menggunakan **DataTables 2.0.3** dengan gaya TailwindCSS yang responsif dan konsisten dengan menu Users.
- **Pencarian Bebas Global (*Instant Search*):** Administrator dapat mengetik nama atau kode unit di kotak pencarian untuk menyaring data secara langsung di browser tanpa reload.
- **Paginasi & Ukuran Tampilan Data:** Mendukung pemilihan ukuran per halaman (10, 25, 50, 100 unit kerja) dengan tombol navigasi yang rapi.
- **Pengurutan Kolom (*Sorting*):** Mendukung pengurutan asc/desc pada Kode Unit, Nama Unit Kerja, Jumlah Pengguna, dan Status (kolom Aksi dinonaktifkan dari pengurutan).
- **Pelokalan Bahasa Indonesia:** Teks instruksi disesuaikan menjadi Bahasa Indonesia: *"Cari Bebas:"*, *"Menampilkan _START_ sampai _END_ dari _TOTAL_ unit kerja"*.

### 2. Dedicated Column Filter Toolbar
- Ditambahkan card toolbar di atas tabel dengan opsi filter spesifik:
  1. **Filter Status Unit:** Dropdown pilihan `Semua Status`, `Active`, dan `Inactive` yang bekerja dengan regex exact match (`^Active$` / `^Inactive$`).
  2. **Filter Keterikatan Pengguna:** Dropdown untuk memfilter unit yang sudah memiliki pegawai terdaftar vs unit yang belum memiliki pegawai (`Memiliki Pengguna Terdaftar` vs `Belum Ada Pengguna (0)`).
  3. **Tombol Reset:** Tombol `🔄 Reset Semua Filter` untuk mengembalikan seluruh filter dan pencarian global ke kondisi awal secara instan.
- Setiap elemen `<td>` dilengkapi atribut HTML5 `data-search` dan `data-filter` murni agar pencarian DataTables akurat dan tidak terdistorsi oleh markup badge atau ikon.

### 3. Kolom Pengguna Terdaftar (`withCount('users')`)
- Pada controller `UnitController@index`, query unit ditambahkan dengan `withCount('users')`.
- Ditampilkan kolom baru **Pengguna Terdaftar** dengan badge informatif (misal: `👥 3 Pengguna`). Informasi ini sangat membantu Administrator mengetahui berapa banyak akun (Supervisor/Operator) yang bernaung di bawah unit tersebut sebelum memutuskan untuk menonaktifkannya.

---

## File yang Dimodifikasi

1. **[MODIFY] [UnitController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/UnitController.php)**:
   - Menambahkan eager calculation `withCount('users')` pada method `index()`.
2. **[MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/units/index.blade.php)**:
   - Menambahkan Card Toolbar Filter Kolom Unit Kerja di atas tabel.
   - Mengintegrasikan tabel `#units-table` dengan kolom Kode, Nama, Pengguna Terdaftar, Status, dan Aksi.
   - Memuat library DataTables v2 TailwindCSS dan script JavaScript regex filtering.
3. **[MODIFY] [UnitManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/UnitManagementTest.php)**:
   - Memperbarui pengujian otomatis untuk memverifikasi inisialisasi DataTables, filter toolbar, atribut `data-search`/`data-filter`, dan kolom jumlah pengguna.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests (PHPUnit)
Seluruh **118 test cases (513 assertions)** pada aplikasi lulus 100% tanpa kegagalan:
```text
PASS  Tests\Feature\Admin\UnitManagementTest
✓ admin can view units index with status column                                 1.27s  
✓ admin can deactivate unit instead of deleting                                 0.04s  
✓ admin can reactivate unit                                                     0.03s  
✓ unit deactivation is recorded in activity log                                 0.03s  
✓ non admin cannot manage units                                                 0.04s  

PASS  Tests\Feature\Admin\AdminDashboardTest (4 passed, 45 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\ReviewTest (11 passed, 70 assertions)
PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)

Tests:    118 passed (513 assertions)
Duration: 38.08s
```

### 2. Kompilasi Frontend Assets (Bun)
Asset frontend dikompilasi menggunakan Vite melalui `bun run build`:
- `public/build/assets/app-DyG4jMwx.css` (84.10 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.28s**
