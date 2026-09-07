# Walkthrough: Fitur Manajemen Pengumuman Multi-Target dari Admin

Fitur **Manajemen Pengumuman (Announcements)** multi-target dan rentang waktu fleksibel telah selesai diimplementasikan, diverifikasi melalui automated feature testing (11 tests / 57 assertions passed), dan seluruh test suite regresi aplikasi (159 tests / 754 assertions) lulus 100%.

---

## 1. Ringkasan Perubahan & Fitur yang Dibangun

### A. Skema Database & Relasi Multi-Target
1. **Migration `database/migrations/2026_09_07_110500_create_announcements_tables.php`**:
   - Tabel `announcements`:
     - `title`, `content`, `type` (`info`, `warning`, `danger`, `success`)
     - `target_type` (`all`, `all_supervisors`, `all_operators`, `specific_users`)
     - `start_at`, `end_at` (nullable untuk open-ended / tanpa batas waktu)
     - `is_active` (boolean, default true)
     - `created_by` (foreign key ke `users.id`)
   - Tabel pivot `announcement_user`:
     - Relasi many-to-many untuk pemetaan pengguna spesifik jika `target_type === 'specific_users'`.

### B. Model & Logika Query (`Announcement.php` & `User.php`)
- **Traits & Activity Logging**: Memanfaatkan trait `LogsActivity` untuk melacak audit aktivitas pembuatan, perubahan, dan penghapusan pengumuman oleh admin.
- **Scope `scopeActiveForUser($query, User $user)`**:
  Menyaring pengumuman yang sedang aktif (`is_active = true`), dalam jendela waktu tayang (`start_at <= now() && (end_at is null || end_at >= now())`), dan menyaring kecocokan sasaran:
  - `all` (seluruh pengguna sistem)
  - `all_supervisors` (jika role user = Supervisor)
  - `all_operators` (jika role user = Operator)
  - `specific_users` (jika user ID terdaftar pada pivot `announcement_user`).
- **Accessors**:
  - `status`: Menghitung status dinamis (`running`, `scheduled`, `expired`, `inactive`).
  - `status_info`: Mengembalikan label status, badge styling, dan warna dot indikator pulsasi.
  - `isCurrentlyActive()`: Helper boolean status keaktifan tayang.

### C. Controller & Endpoint Admin (`AnnouncementController.php` & `web.php`)
- **CRUD Pengumuman**: `index`, `create`, `store`, `edit`, `update`, `destroy`.
- **Tabs Filter Status**: Menampilkan tabs (Semua, Sedang Tayang, Terjadwal, Telah Berakhir, Disembunyikan) dengan counter badges dan filter pencarian teks.
- **Toggle Status Sembunyikan/Aktifkan (`toggle-active`)**: Memungkinkan admin menyembunyikan sementara pengumuman atau menampilkannya kembali kapan saja.
- **Akhiri Paksa Seketika (`force-end`)**: Memotong masa tayang pengumuman dan mengeset `end_at = now()`.

### D. Tampilan Antarmuka (Views & Components)
1. **Menu Navigasi Admin (`layouts/navigation.blade.php`)**:
   - Ditambahkan tautan menu `📢 Pengumuman` di desktop navigation dan mobile menu dengan status active highlighter.
2. **Halaman Daftar Pengumuman (`admin/announcements/index.blade.php`)**:
   - Tabs status interaktif dengan indikator visual dan penghitung jumlah pengumuman.
   - Tabel responsif berisi judul, tipe pesan, sasaran penerima (dengan avatar counter), jadwal waktu (format tanggal & relative time), badge status, dan tombol aksi cepat (Edit, Toggle Sembunyi, Akhiri Paksa, Hapus).
3. **Formulir Create & Edit (`admin/announcements/create.blade.php` & `edit.blade.php`)**:
   - **Real-time Live Preview**: Banner simulasi berubah langsung saat judul, tipe visual, atau teks pesan diketik.
   - **Pilihan Sasaran Interaktif**: Radio cards untuk target sasaran. Pilihan `specific_users` membuka accordion unit kerja, search nama/NIP, tombol bantuan "Pilih Semua Spv", "Pilih Semua Opr", dan toggle unit.
   - **Pintasan Durasi Cepat (Quick Presets)**: Tombol `+15 Mnt`, `+30 Mnt`, `+1 Jam`, `+3 Jam`, `+6 Jam`, `+12 Jam`, `+1 Hari`, `+3 Hari`, `+1 Minggu`, `+1 Bulan`, `+1 Tahun` yang otomatis menghitung `end_at` dari `start_at`.
   - **Opsi Tanpa Batas Waktu**: Checkbox untuk mematikan `end_at` agar pengumuman tayang berkelanjutan.
4. **Komponen Banner Adaptif (`components/announcement-banner.blade.php`)**:
   - Ditempatkan di layout utama `layouts/app.blade.php`.
   - Mengambil pengumuman aktif untuk user yang login secara otomatis.
   - Styling warna elegan disesuaikan tipe urgensi (biru untuk info, kuning untuk warning, merah untuk penting/darurat, hijau untuk sukses).
   - Dilengkapi tombol dismiss/tutup berbasis `sessionStorage` per pengumuman, agar tidak berulang kali mengganggu user saat berpindah halaman pada sesi yang sama.

---

## 2. File yang Dibuat dan Dimodifikasi

| Status | File | Deskripsi |
|---|---|---|
| **NEW** | `database/migrations/2026_09_07_110500_create_announcements_tables.php` | Migrasi tabel `announcements` dan pivot `announcement_user`. |
| **NEW** | `app/Models/Announcement.php` | Model Eloquent pengumuman dengan scope targeting & status accessor. |
| **MODIFY** | `app/Models/User.php` | Relasi `targetedAnnouncements()` ke tabel pivot pengumuman. |
| **NEW** | `app/Http/Controllers/Admin/AnnouncementController.php` | Controller manajemen pengumuman admin. |
| **MODIFY** | `routes/web.php` | Rute resource `announcements`, `toggle-active`, dan `force-end`. |
| **MODIFY** | `resources/views/layouts/navigation.blade.php` | Link menu pengumuman di navbar admin. |
| **NEW** | `resources/views/admin/announcements/index.blade.php` | View index manajemen pengumuman dengan tabs filter dan aksi. |
| **NEW** | `resources/views/admin/announcements/create.blade.php` | View form pembuatan pengumuman dengan live preview dan preset durasi. |
| **NEW** | `resources/views/admin/announcements/edit.blade.php` | View form edit pengumuman dengan pemetaan data existing. |
| **NEW** | `resources/views/components/announcement-banner.blade.php` | Komponen penampil banner pengumuman multi-role di layout. |
| **MODIFY** | `resources/views/layouts/app.blade.php` | Sematan `<x-announcement-banner />` di bawah navigasi. |
| **NEW** | `tests/Feature/Admin/AnnouncementTest.php` | Pengujian fitur pengumuman multi-target, jadwal, dan kontrol admin. |

---

## 3. Hasil Pengujian & Verifikasi

### A. Pengujian Fitur Pengumuman (`AnnouncementTest`)
Menjalankan `php artisan test --filter=AnnouncementTest`:
```text
PASS  Tests\Feature\Admin\AnnouncementTest
✓ admin can view announcements index
✓ admin can filter announcements by status
✓ admin can view create form
✓ admin can create announcement with all target
✓ admin can create announcement with specific users
✓ admin can view and update announcement
✓ admin can toggle active status
✓ admin can force end announcement
✓ admin can delete announcement
✓ non admin cannot access announcements management
✓ announcement banner targeting and scheduling

Tests:    11 passed (57 assertions)
Duration: 1.98s
```

### B. Pengujian Regresi Keseluruhan Aplikasi
Menjalankan `php artisan test`:
```text
Tests:    159 passed (754 assertions)
Duration: 56.00s
Status:   100% Passed (Semua tes hijau tanpa kegagalan)
```

### C. Kompilasi Aset Frontend
Menjalankan `bun run build`:
```text
✓ 54 modules transformed.
public/build/assets/app-DOL1kaPn.css  93.20 kB │ gzip: 14.29 kB
public/build/assets/app-CBbTb_k3.js   83.04 kB │ gzip: 30.88 kB
✓ built in 2.56s
```

---

## 4. Lokasi Dokumentasi

Dokumentasi lengkap ini juga telah disimpan pada folder dokumentasi proyek:
- File Plan: `documentation/20260907105500_implementation_plan_Manajemen_Pengumuman_Multi_Target_Admin.md`
- File Walkthrough: `documentation/20260907111500_walkthrough_Manajemen_Pengumuman_Multi_Target_Admin.md`
