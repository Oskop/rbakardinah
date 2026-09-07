# Rencana Implementasi: Fitur Munculkan Ulang Pengumuman (Admin Re-broadcast & User Floating Restore)

Fitur ini menyediakan mekanisme dua arah yang komprehensif:
1. **Sisi Administrator (*Admin Re-broadcast / Pertegas Pengumuman*)**: Admin dapat memunculkan ulang pengumuman yang sedang tayang, sehingga seluruh pengguna sasaran yang sebelumnya telah menutup/men-dismiss pengumuman tersebut akan otomatis melihat pengumuman tersebut muncul kembali di layarnya.
2. **Sisi Pengguna (*Client-side Floating Restore*)**: Pengguna yang secara sengaja menutup pengumuman tetap memiliki akses tombol melayang (*floating action pill*) di sudut kanan bawah untuk memunculkan kembali pengumuman tersebut kapan saja secara mandiri.

---

## 1. Analisis Kebutuhan & Desain Arsitektur Solusi

### A. Mekanisme Munculkan Ulang dari Sisi Admin (Admin Re-broadcast)
- **Tantangan**: Saat ini status dismissal pengumuman disimpan di sisi klien pada `sessionStorage` pengguna (`announcement_dismissed_{id}`). Jika admin ingin mempertegas pengumuman penting (misalnya deadline RBA semakin dekat), admin memerlukan tombol untuk me-reset status tutup tersebut secara terpusat dari server.
- **Solusi Teknis Handal & Efisien**:
  1. Tambahkan kolom `reshown_at` (timestamp, nullable) pada tabel `announcements`.
  2. Saat Admin menekan tombol **"Munculkan Ulang"** (*Reshow / Pertegas*) pada daftar pengumuman di panel admin:
     - Sistem memperbarui kolom `reshown_at = now()` dan memastikan `is_active = true`.
     - Dicatat dalam `ActivityLog` bahwa admin telah memunculkan ulang pengumuman tersebut.
  3. Kunci penyimpanan penutupan di sisi pengguna diubah menjadi versi bertimestamp (*timestamped cache busting*):
     `announcement_dismissed_{id}_{reshown_timestamp}`.
  4. Begitu timestamp `reshown_at` berubah di server, kunci lama di `sessionStorage` pengguna tidak lagi cocok, sehingga **seluruh pengguna sasaran yang sebelumnya telah menutup pengumuman seketika akan melihat pengumuman tersebut muncul kembali di halaman mereka tanpa perlu membuka sesi baru**.

### B. Mekanisme Pemunculan Kembali Mandiri di Sisi Pengguna (User Floating Restore)
- Ketika seorang pengguna menutup banner pengumuman, container banner utama tetap menutup rapat (`display: none`) agar tidak memakan ruang di antara bilah navigasi dan judul halaman.
- Sebagai gantinya, tombol melayang (*floating badge pill*) muncul secara elegan di sudut kanan bawah (`fixed bottom-6 right-6 z-40`):
  `📢 Munculkan Kembali Pengumuman` (dilengkapi jumlah pengumuman yang ditutup).
- Pengguna dapat mengklik tombol ini kapan saja untuk membaca ulang isi pengumuman tersebut secara sukarela.

---

## 2. Rincian Perubahan Berkas (*Proposed Changes*)

### Database & Backend

#### [NEW] [database/migrations/2026_09_07_143000_add_reshown_at_to_announcements_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_07_143000_add_reshown_at_to_announcements_table.php)
- Menambahkan kolom `reshown_at` (timestamp nullable) pada tabel `announcements`.

#### [MODIFY] [app/Models/Announcement.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/Announcement.php)
- Menambahkan `'reshown_at'` ke properti `$fillable` dan casts datetime.
- Menambahkan accessor `getDismissKeyAttribute(): string` untuk menghasilkan kunci berbasis timestamp versi pengumuman.
- Menambahkan method `reshow(): void`.

#### [MODIFY] [app/Http/Controllers/Admin/AnnouncementController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/AnnouncementController.php)
- Menambahkan method `reshow(Announcement $announcement)`:
  - Memperbarui `reshown_at = now()` dan `is_active = true`.
  - Mengembalikan redirect dengan flash message sukses: *"Pengumuman '[Judul]' berhasil dimunculkan ulang kepada seluruh pengguna sasaran."*

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute POST `admin/announcements/{announcement}/reshow` dengan nama `admin.announcements.reshow`.

---

### Tampilan Antarmuka (*Views*)

#### [MODIFY] [resources/views/admin/announcements/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/announcements/index.blade.php)
- Menambahkan tombol aksi **"Munculkan Ulang"** (ikon megaphone/rotate berwarna indigo/sky dengan tooltip *"Munculkan Ulang ke Semua Pengguna Sasaran"*).
- Menampilkan dialog konfirmasi: *"Munculkan ulang pengumuman ini ke seluruh pengguna sasaran? Pengguna yang sudah menutupnya akan melihatnya kembali."*
- Menampilkan indikator riwayat penegasan jika pengumuman pernah dimunculkan ulang (`Ditegaskan: [waktu]`).

#### [MODIFY] [resources/views/components/announcement-banner.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/announcement-banner.blade.php)
- Menggunakan `announcement->dismiss_key` yang otomatis berubah saat admin melakukan reshow.
- Menambahkan tombol melayang (*floating restore action pill*) di sudut kanan bawah layar yang aktif saat `hasDismissed` bernilai true, dengan method `restoreAll()`.

---

## 3. Rencana Verifikasi (*Verification Plan*)

### Automated Tests
1. **Feature Test Admin Reshow**:
   - Tambahkan test di [AnnouncementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AnnouncementTest.php):
     - Admin memanggil POST `admin.announcements.reshow`.
     - Memastikan `reshown_at` terisi timestamp terkini dan `is_active` bernilai true.
     - Memastikan dismiss key berubah sehingga banner kembali muncul di view.
2. **Feature Test Hak Akses**:
   - Memastikan non-admin tidak dapat memanggil endpoint `reshow` (403 Forbidden).
3. **Automated Suite**:
   - `php artisan test --filter=AnnouncementTest`
   - `php artisan test` (seluruh 159+ test lulus).
   - `bun run build` (aset Vite frontend terkompilasi bersih).

### Manual Verification
1. Login sebagai Operator/Supervisor, lihat banner pengumuman lalu klik tutup ("x").
2. Verifikasi tombol floating `📢 Munculkan Kembali Pengumuman` muncul di kanan bawah dan dapat diklik untuk memunculkan kembali.
3. Klik tutup kembali ("x").
4. Di jendela/tab lain, login sebagai Admin, buka menu Pengumuman, lalu klik tombol **"Munculkan Ulang"**.
5. Buka kembali halaman Operator/Supervisor dan refresh: verifikasi banner pengumuman otomatis muncul kembali meskipun sebelumnya telah ditutup oleh operator tersebut!

---

## 4. Lokasi Penyimpanan Dokumentasi
Rencana implementasi ini disimpan di:
- `documentation/20260907142600_implementation_plan_Fitur_Munculkan_Ulang_Pengumuman_Admin_Dan_User.md`
