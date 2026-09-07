# Implementation Plan: Fitur Pengumuman Multi-Target, Rentang Waktu Fleksibel, & Kendali Visibilitas dari Administrator

Fitur ini memungkinkan Administrator untuk mengelola dan mempublikasikan **Pengumuman Sistem (Announcements)** dengan fleksibilitas penuh terkait sasaran pengguna (*target audience*), rentang waktu penayangan (*durasi detik/menit/jam/hari/bulan/tahun*), serta kontrol penuh untuk menyembunyikan (*hide*) maupun mengakhiri penayangan secara paksa (*force end*).

---

## 1. Kebutuhan & Spesifikasi Fitur

### A. Manajemen Pengumuman oleh Administrator
- Antarmuka CRUD Pengumuman di panel Administrator:
  - Melihat daftar seluruh pengumuman (status aktif/tayang, terjadwal, berakhir, disembunyikan).
  - Membuat pengumuman baru.
  - Mengubah pengumuman yang sudah ada.
  - Menghapus pengumuman.
  - Menu navigasi baru **"📢 Pengumuman"** di navbar utama Administrator (desktop & mobile).

### B. Fleksibilitas Sasaran Pengguna (*Target Audience*)
Admin dapat menentukan kepada siapa pengumuman ditujukan:
1. **Semua Pengguna (Global)**: Seluruh pengguna sistem (Administrator, Supervisor, dan Operator).
2. **Semua Supervisor**: Khusus pengguna dengan role Supervisor di seluruh unit kerja.
3. **Semua Operator**: Khusus pengguna dengan role Operator di seluruh unit kerja.
4. **Pengguna Spesifik / Kombinasi**: Memilih satu atau beberapa Supervisor dan/atau Operator tertentu secara granular (misal: hanya Supervisor Unit Rawat Inap dan Operator Farmasi).

### C. Fleksibilitas Rentang Waktu Penayangan (*Scheduling & Duration*)
- Waktu Mulai (`start_at`): Pengumuman dapat langsung tayang sekarang atau dijadwalkan di masa depan.
- Waktu Selesai (`end_at`):
  - Dapat diisi rentang tanggal dan jam/menit/detik secara spesifik.
  - Dapat disetel **Tanpa Batas Waktu (Open-ended)** sampai diakhiri secara manual oleh Admin.
  - Dilengkapi tombol pembantu cepat (*Quick Duration Presets*): **+15 Menit**, **+1 Jam**, **+3 Jam**, **+1 Hari**, **+3 Hari**, **+1 Minggu**, **+1 Bulan**, **+1 Tahun** untuk kemudahan pengaturan durasi tanpa perlu menghitung tanggal manual.

### D. Kendali Status & Visibilitas
- **Sembunyikan / Tampilkan (*Toggle Active / Hide*)**:
  Admin dapat mematikan sementara visibilitas pengumuman (`is_active = false`) tanpa menghapus datanya.
- **Akhiri Paksa (*Force End*)**:
  Admin dapat menghentikan pengumuman yang sedang tayang seketika itu juga dengan satu klik tombol. Sistem otomatis menyetel `end_at = now()`, sehingga pengumuman langsung berhenti tayang bagi pengguna sasaran dan beralih ke status "Telah Berakhir".

### E. Tipe & Tingkat Kepentingan Pengumuman
- `info`: Informasi Umum (Biru / Netral)
- `warning`: Peringatan / Batas Waktu Penginputan (Kuning-Amber / Jam)
- `danger`: Penting / Pemeliharaan / Mendesak (Merah)
- `success`: Keberhasilan / Pemberitahuan Positif (Hijau)

### F. Penyajian Banner Pengumuman ke Pengguna Sasaran
- Komponen Banner Notifikasi Global terpasang di layout aplikasi ([`resources/views/layouts/app.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/app.blade.php)).
- Hanya muncul bagi user yang sedang login jika:
  1. Pengumuman berstatus aktif (`is_active = true`).
  2. Waktu sekarang telah melewati atau sama dengan waktu mulai (`start_at <= now()`).
  3. Waktu sekarang belum melewati waktu selesai (`end_at IS NULL` atau `end_at >= now()`).
  4. Pengguna yang sedang login masuk dalam target sasaran (*audience match*).
- Dilengkapi tombol penutupan sementara (*Dismiss*) per sesi/localStorage agar tidak mengganggu jika pengguna sudah membaca, namun otomatis muncul kembali jika Admin melakukan pembaharuan (*update*) pada isi pengumuman.

---

## 2. Rencana Arsitektur & Skema Database

### A. Migrasi Database
1. **Tabel `announcements`**:
   - `id`: Bigint, Primary Key, Auto Increment.
   - `title`: String (Judul pengumuman).
   - `content`: Text (Isi narasi pengumuman).
   - `type`: String, default `'info'` (`info`, `warning`, `danger`, `success`).
   - `target_type`: String, default `'all'` (`all`, `all_supervisors`, `all_operators`, `specific_users`).
   - `start_at`: Datetime (Waktu mulai tayang).
   - `end_at`: Datetime, Nullable (Waktu berakhir).
   - `is_active`: Boolean, default `true`.
   - `created_by`: ForeignId ke tabel `users`, Nullable, `onDelete('set null')`.
   - `timestamps()`.

2. **Tabel Pivot `announcement_user`**:
   - `id`: Bigint, Primary Key.
   - `announcement_id`: ForeignId ke `announcements`, `onDelete('cascade')`.
   - `user_id`: ForeignId ke `users`, `onDelete('cascade')`.
   - `timestamps()`.
   - Unique Index: `['announcement_id', 'user_id']`.

### B. Relasi Model Eloquent
- **Model `App\Models\Announcement`**:
  - Menggunakan trait `LogsActivity` untuk audit log otomatis.
  - Relasi `creator()`: `belongsTo(User::class, 'created_by')`.
  - Relasi `targetUsers()`: `belongsToMany(User::class, 'announcement_user')->withTimestamps()`.
  - Scope `scopeActiveForUser($query, User $user)`: Memfilter pengumuman yang aktif dan sesuai target user saat `now()`.
  - Accessor `status_badge`: Mengembalikan label status komputasi (*Sedang Tayang*, *Terjadwal*, *Berakhir*, *Disembunyikan*).
- **Model `App\Models\User`**:
  - Relasi `targetedAnnouncements()`: `belongsToMany(Announcement::class, 'announcement_user')`.

---

## 3. Rencana Berkas yang Dibuat / Dimodifikasi

### Berkas Baru:
1. `database/migrations/2026_09_07_105200_create_announcements_tables.php`: Migrasi tabel `announcements` dan `announcement_user`.
2. `app/Models/Announcement.php`: Model Announcement dengan scope target user & status.
3. `app/Http/Controllers/Admin/AnnouncementController.php`: Controller CRUD, toggle status, dan force end.
4. `resources/views/admin/announcements/index.blade.php`: Halaman daftar pengumuman, status badges, dan tombol aksi Admin.
5. `resources/views/admin/announcements/create.blade.php`: Form pembuatan pengumuman dengan pemilih target & durasi cepat.
6. `resources/views/admin/announcements/edit.blade.php`: Form edit pengumuman.
7. `resources/views/components/announcement-banner.blade.php`: Komponen banner penampil pengumuman di layout pengguna dengan dismissibility.
8. `tests/Feature/Admin/AnnouncementTest.php`: Feature test lengkap (CRUD, filter target, rentang waktu, toggle hide, force end, dan otorisasi).

### Berkas yang Dimodifikasi:
1. `app/Models/User.php`: Menambahkan relasi `targetedAnnouncements()`.
2. `routes/web.php`: Mendaftarkan rute resource dan aksi admin untuk `announcements`.
3. `resources/views/layouts/navigation.blade.php`: Menambahkan tautan menu navigasi **Pengumuman** untuk role Administrator.
4. `resources/views/layouts/app.blade.php`: Menyertakan komponen `<x-announcement-banner />` di bawah bar navigasi.

---

## 4. Rencana Verifikasi & Pengujian

### A. Pengujian Otomatis (Automated Tests)
Membuat file pengujian [`tests/Feature/Admin/AnnouncementTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AnnouncementTest.php) yang mencakup skenario:
1. **CRUD Pengumuman**: Admin dapat membuat, membaca, memperbarui, dan menghapus pengumuman.
2. **Targeting Scope**:
   - Pengumuman bertarget `all` tampil untuk Admin, Supervisor, dan Operator.
   - Pengumuman bertarget `all_supervisors` hanya tampil untuk Supervisor, tidak untuk Operator.
   - Pengumuman bertarget `all_operators` hanya tampil untuk Operator, tidak untuk Supervisor.
   - Pengumuman bertarget `specific_users` hanya tampil untuk user terpilih dalam pivot table.
3. **Validasi Waktu Penayangan**:
   - Pengumuman dengan `start_at` di masa depan tidak tampil untuk user (*scheduled*).
   - Pengumuman dengan `end_at` yang telah lewat tidak tampil untuk user (*expired*).
   - Pengumuman tanpa `end_at` (open-ended) tetap tampil terus menerus.
4. **Sembunyikan & Akhiri Paksa**:
   - Admin dapat mengubah `is_active = false` (sembunyikan) dan pengumuman seketika tidak tampil bagi user.
   - Admin dapat menjalankan `force-end` dan `end_at` seketika menjadi `now()`, menghentikan penayangan bagi user.
5. **Keamanan & Otorisasi**:
   - User non-Admin (Supervisor/Operator/Guest) mendapat respon `403 Forbidden` saat mencoba mengakses rute kelola pengumuman.

### B. Pengujian Regresi & Frontend Assets
1. Menjalankan `php artisan test` untuk memastikan 148+ tes seluruh modul aplikasi lulus 100%.
2. Menjalankan `bun run build` untuk mengonfirmasi kompilasi aset frontend berjalan lancar.
