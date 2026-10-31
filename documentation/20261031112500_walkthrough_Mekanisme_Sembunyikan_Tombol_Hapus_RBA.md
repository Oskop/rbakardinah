# Walkthrough: Mekanisme Kontrol Visibilitas & Proteksi Tombol Hapus RBA Header

## 1. Ringkasan Eksekutif & Latar Belakang

Setelah fitur pembersihan dan pengarsipan data (*Hard Delete Archive & Safe Restore*) aktif, pengguna menghendaki agar tombol **"Hapus RBA"** tidak terpampang terus-menerus di halaman daftar RBA Header (`/admin/headers`). Tombol penghapusan merupakan aksi kritis berisiko tinggi (*high-risk destructive action*), sehingga diperlukan mekanisme untuk menyembunyikan tombol tersebut dan hanya menampilkannya kembali saat administrator secara sengaja membuka izinnya melalui **Halaman Pengaturan Khusus Admin** (`/admin/settings`).

Tujuan utama:
1. **Pemisahan Konteks**: Pengaturan visibilitas tombol hapus dikelola di halaman Pengaturan Sistem, terpisah total dari tabel daftar RBA operasional.
2. **Keamanan Berlapis (Dual-Layer Protection)**:
   - **Lapisan Tampilan (Blade)**: Jika pengaturan dinonaktifkan, tombol "Hapus RBA" sama sekali tidak di-render di dokumen HTML.
   - **Lapisan Server (Controller Guard)**: Endpoint `DELETE /admin/headers/{id}` secara ketat menolak segala permintaan penghapusan (baik melalui inspect element, cURL, maupun Postman) jika status pengaturan sedang dikunci.
3. **Kepatuhan Audit BPK (Audit Trail)**: Setiap perubahan status (mengaktifkan/mengunci tombol) dicatat secara otomatis ke dalam `activity_logs` beserta identitas Administrator, waktu, dan alamat IP.
4. **Fleksibilitas CLI untuk Sysadmin**: Tersedia perintah `php artisan rba:toggle-deletion` untuk kemudahan tim teknis yang mengelola server via terminal.

---

## 2. Arsitektur & Komponen yang Diimplementasikan

### 2.1. Tabel Basis Data & Model `SystemSetting`
- **Migration**: [2026_10_31_111500_create_system_settings_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_10_31_111500_create_system_settings_table.php)
  - Menyimpan konfigurasi global sistem dengan kolom: `key`, `value`, `type`, `group`, `label`, `description`, `updated_by`, `timestamps`.
  - Menginisialisasi pengaturan default: `allow_rba_deletion = 0` (secara default tombol hapus berstatus terkunci/tersembunyi).
- **Model**: [`App\Models\SystemSetting`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/SystemSetting.php)
  - Method `SystemSetting::get(key, default)` dan `SystemSetting::set(key, value, userId)`.
  - Helper utama `SystemSetting::isRbaDeletionAllowed(): bool` dengan mekanisme *fallback* ke `env('ENABLE_RBA_DELETION', false)`.
- **Seeder**: [database/seeders/SystemSettingSeeder.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/SystemSettingSeeder.php) terdaftar di `DatabaseSeeder`.

### 2.2. Controller & Routing Pengaturan Admin
- **Controller**: [`App\Http\Controllers\Admin\SystemSettingController`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/SystemSettingController.php)
  - `index()`: Merender tampilan pengaturan sistem beserta daftar riwayat perubahan terakhir (*audit trail*).
  - `update()`: Memvalidasi masukan, menyimpan status baru, dan mencatat event ke tabel `activity_logs`.
- **Routing**: [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php#L53-L55)
  - `GET  /admin/settings` -> `admin.settings.index`
  - `POST /admin/settings` -> `admin.settings.update`

### 2.3. Antarmuka Web (Views)
- **Halaman Pengaturan Sistem**: [resources/views/admin/settings/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/settings/index.blade.php)
  - Menyajikan panel khusus **"Proteksi Tindakan Hapus RBA Header"** dengan badge status visual:
    - 🔒 **MODE AMAN (TERKUNCI & TERSEMBUNYI)**: Tombol hapus disembunyikan.
    - 🔓 **MODE AKTIF (TOMBOL DITAMPILKAN)**: Tombol hapus ditampilkan dan siap digunakan untuk pembersihan.
  - Tombol interaktif untuk mengubah status lengkap dengan konfirmasi dialog peringatan.
  - Tabel audit trail yang menampilkan log riwayat siapa Administrator yang pernah mengubah pengaturan ini.
- **Menu Navigasi**: [resources/views/layouts/navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
  - Menu **"⚙️ Pengaturan Sistem"** ditambahkan pada dropdown profil (pojok kanan atas) dan responsive mobile menu khusus untuk role `Administrator`.
- **Halaman Daftar RBA Header**: [resources/views/admin/headers/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/index.blade.php)
  - Tombol **"Hapus RBA"** dibungkus dengan `@if(\App\Models\SystemSetting::isRbaDeletionAllowed())`.
  - Jika mode aktif, muncul banner peringatan kuning di bagian atas halaman dengan tautan cepat kembali ke Pengaturan Sistem.

### 2.4. Lapisan Proteksi Server (Backend Controller Guard)
- **Controller Guard**: [`App\Http\Controllers\RbaHeaderController::destroy`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php#L319-L325)
  - Mengecek `!SystemSetting::isRbaDeletionAllowed()`. Jika terkunci, request penghapusan langsung ditolak dan dialihkan kembali dengan pesan error:
    > *"Tindakan penghapusan RBA sedang dinonaktifkan di Pengaturan Sistem. Silakan aktifkan izin terlebih dahulu melalui menu Pengaturan Sistem."*
- **CLI Deletion Guard**: [app/Console/Commands/DeleteRbaHeaderCommand.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/DeleteRbaHeaderCommand.php#L41-L47)
  - Menampilkan peringatan konfirmasi jika setting sistem sedang terkunci, kecuali jika menyertakan opsi `--force`.

### 2.5. Perintah CLI Artisan
- **Command**: [`App\Console\Commands\ToggleRbaDeletionCommand`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/ToggleRbaDeletionCommand.php)
  - Perintah: `php artisan rba:toggle-deletion [enable|disable]`
  - Berguna untuk staf IT/sysadmin yang ingin mengontrol mode pembersihan langsung dari shell terminal.

---

## 3. Panduan Operasional

### Skenario 1: Menghapus RBA Header yang Keliru
1. Klik foto/nama profil Administrator di pojok kanan atas, lalu pilih **⚙️ Pengaturan Sistem** (atau akses rute `/admin/settings`).
2. Pada kartu **Proteksi Tindakan Hapus RBA Header**, klik tombol **"🔓 Buka Kunci / Tampilkan Tombol Hapus"**. Konfirmasi pada dialog peringatan.
3. Buka menu **Daftar RBA Header** (`/admin/headers`). Anda akan melihat banner kuning *"Mode Pembersihan Aktif"* dan tombol merah **"Hapus RBA"** kini telah muncul pada setiap baris data.
4. Klik tombol **Hapus RBA** pada data yang keliru, masukkan alasan penghapusan, dan konfirmasi. Data akan bersih dari operasional dan snapshot tersimpan di Arsip Sampah.
5. **PENTING**: Segera kembali ke **Pengaturan Sistem** dan klik tombol **"🔒 Kunci & Sembunyikan Tombol Hapus"** agar tombol kembali tersembunyi dan sistem kembali dalam mode aman terkunci.

### Skenario 2: Mengontrol via Terminal (Sysadmin)
```bash
# Mengaktifkan mode pembersihan (menampilkan tombol dan mengizinkan hapus)
php artisan rba:toggle-deletion enable

# Menonaktifkan dan mengunci kembali
php artisan rba:toggle-deletion disable

# Menukar status secara langsung (toggle on/off)
php artisan rba:toggle-deletion
```

---

## 4. Hasil Verifikasi Pengujian Otomatis

Pengujian fitur baru dijalankan secara komprehensif pada [`Tests\Feature\Admin\SystemSettingTest`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/SystemSettingTest.php):

| Kasus Uji | Deskripsi Pengujian | Hasil |
| :--- | :--- | :---: |
| `test_admin_can_access_system_settings_page` | Memverifikasi Administrator dapat membuka `/admin/settings` | **PASSED (Green)** |
| `test_non_admin_cannot_access_system_settings_page` | Memverifikasi non-admin (Operator/Supervisor) ditolak 403 Forbidden | **PASSED (Green)** |
| `test_admin_can_toggle_rba_deletion_setting_and_logs_activity` | Memverifikasi toggle setting on/off dan pencatatan audit trail di `ActivityLog` | **PASSED (Green)** |
| `test_rba_headers_index_hides_delete_button_when_deletion_is_disabled` | Memverifikasi tombol "Hapus RBA" hilang dari DOM saat setting nonaktif | **PASSED (Green)** |
| `test_rba_headers_index_shows_delete_button_and_warning_when_deletion_is_enabled` | Memverifikasi tombol "Hapus RBA" dan banner peringatan muncul saat setting aktif | **PASSED (Green)** |
| `test_delete_rba_header_is_blocked_at_controller_when_setting_is_disabled` | Memverifikasi request `DELETE` langsung ditolak server jika setting nonaktif | **PASSED (Green)** |
| `test_delete_rba_header_succeeds_when_setting_is_enabled` | Memverifikasi eksekusi penghapusan berhasil saat setting aktif | **PASSED (Green)** |
| `test_artisan_toggle_command_changes_system_setting` | Memverifikasi perintah `php artisan rba:toggle-deletion` | **PASSED (Green)** |

---

## 5. Kesimpulan

Mekanisme kontrol visibilitas tombol Hapus RBA telah selesai diimplementasikan dengan standar keamanan ganda (tampilan dan server-side guard), memiliki pencatatan audit yang lengkap untuk kebutuhan audit BPK, dan siap digunakan.

