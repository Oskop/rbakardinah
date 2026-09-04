# Walkthrough: Menu Laporan RBA Terintegrasi Multi-Role

Fitur baru **Menu Laporan (Reports)** yang dapat diakses oleh seluruh tingkatan pengguna (**Administrator**, **Supervisor**, dan **Operator**) untuk mencetak dokumen resmi RBA (Usulan Rincian Belanja & RBA Final dengan Pagu) secara terpusat dengan opsi filter yang disesuaikan secara otomatis menurut peran masing-masing, serta terintegrasi penuh (*single source of truth*) dengan fitur cetak yang sudah ada di setiap halaman periode RBA telah berhasil diimplementasikan dan diverifikasi secara menyeluruh.

---

## 1. Ringkasan Implementasi

### A. Routing & Controller Terpadu (`ReportController.php`)
- **File**: `app/Http/Controllers/ReportController.php` & `routes/web.php`
- Route universal: `GET /reports` (`reports.index`) yang diproteksi oleh middleware `auth`.
- Method `index()` mendeteksi peran (`role`) pengguna yang sedang login:
  - **Administrator**: Memuat seluruh data `RbaHeader` (terurut tahun dan ID secara descending), seluruh data `Unit` aktif, serta daftar `User` (Operator aktif dengan unit kerjanya) untuk keperluan filter scope cetak.
  - **Supervisor**: Memuat berkas `RbaSubmission` milik unit kerja supervisor (`unit_id`), serta daftar `User` (Operator aktif) di unit terkait.
  - **Operator**: Memuat berkas `RbaSubmission` milik unit kerja operator (`unit_id`).
- Mendukung pre-selection periode jika URL memiliki parameter query `?header_id=...` atau `?submission_id=...`.

### B. Antarmuka Pengguna Pusat Laporan (`resources/views/reports/index.blade.php`)
- Mengusung tata letak dua kolom responsif dan modern dengan Alpine.js:
  1. **Kolom Kiri (Pilih Periode RBA)**:
     - Daftar pilihan periode RBA interaktif (dengan radio selector yang otomatis menyorot periode terpilih).
     - Menampilkan Tahun Anggaran, Nama Periode (Murni/Perubahan), Status (Aktif/Terkunci untuk Admin; Divalidasi/Submitted/Draft untuk Supervisor & Operator).
     - Tombol cepat **"Buka RBA Periode Ini"** untuk meninjau langsung rincian berkas RBA sebelum mencetak.
     - Kotak tips & panduan pencetakan dokumen resmi landscape A4.
  2. **Kolom Kanan (Konfigurasi Cetak)**:
     - Form reaktif yang action URL-nya berubah secara dinamis (`getActionUrl()`) mengarah langsung ke endpoint cetak resmi yang sudah ada:
       - **Administrator**: `admin.headers.print-preview` & `admin.headers.print-preview-final`
       - **Supervisor**: `supervisor.submissions.print-preview` & `supervisor.submissions.print-preview-final`
       - **Operator**: `operator.submissions.print-preview` & `operator.submissions.print-preview-final`
     - Pilihan Dokumen: **Usulan Rincian Belanja** vs **Rincian Belanja & Pagu (RBA Final)**.
     - Pilihan Latar Belakang: **Dengan Latar Belakang** vs **Tanpa Latar Belakang**.
     - Filter Scope Dinamis:
       - *Admin*: Seluruh RSUD / Filter Unit / Filter Operator (dengan checklist interaktif, tombol Pilih Semua & Reset).
       - *Supervisor*: Semua Operator Unit / Operator Spesifik Tertentu (dengan checklist interaktif, tombol Pilih Semua & Reset).
       - *Operator*: Banner penjelasan otomatis bahwa laporan dicetak khusus usulan operator di unitnya.
     - Tombol Aksi: **🖨️ Buka Pratinjau Cetak** (membuka pratinjau cetak di tab baru `target="_blank"`).

### C. Integrasi & Sinkronisasi Dua Arah (*Single Source of Truth*)
- **Navigasi Utama (`navigation.blade.php`)**:
  - Menu **Laporan** ditambahkan pada navigasi desktop dan mobile responsive untuk Administrator, Supervisor, dan Operator.
  - Indikator tautan aktif menggunakan `request()->routeIs('reports.*')`.
- **Tautan Balik dari Halaman RBA**:
  - Pada halaman detail Operator (`operator/submissions/show.blade.php`), dropdown cetak ditambahkan opsi *"📑 Buka di Menu Laporan"*.
  - Pada modal cetak Supervisor (`supervisor/submissions/show.blade.php`), footer modal ditambahkan tombol *"📑 Buka di Menu Laporan"*.
  - Pada modal cetak Administrator (`admin/headers/show.blade.php`), footer modal ditambahkan tombol *"📑 Buka di Menu Laporan"*.
- **Prinsip Sinkronisasi**: Jika di masa mendatang dilakukan penyesuaian pada format cetakan (kop surat, format tabel, tanda tangan, layout landscape), perubahan tersebut langsung tersinkronisasi otomatis baik saat dicetak dari halaman kerja RBA maupun dari Menu Laporan.

---

## 2. Berkas yang Dibuat dan Dimodifikasi

1. **[NEW]** [app/Http/Controllers/ReportController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/ReportController.php)
2. **[NEW]** [resources/views/reports/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/index.blade.php)
3. **[NEW]** [tests/Feature/General/ReportMenuTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/ReportMenuTest.php)
4. **[MODIFY]** [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
5. **[MODIFY]** [resources/views/layouts/navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
6. **[MODIFY]** [resources/views/operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
7. **[MODIFY]** [resources/views/supervisor/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
8. **[MODIFY]** [resources/views/admin/headers/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)

---

## 3. Hasil Pengujian & Verifikasi

- **Vite Asset Compilation**:
  `bun run build` sukses mengompilasi CSS dan JS (`public/build/assets/app-ItiKn-1H.css` & `app-CBbTb_k3.js`).
- **Feature Tests Menu Laporan**:
  `Tests\Feature\General\ReportMenuTest` lulus 100% (5 passed, 27 assertions):
  - `unauthenticated guest is redirected to login`
  - `administrator can access reports and see headers and options`
  - `supervisor can access reports and see their unit submissions and operators`
  - `operator can access reports and see their unit submissions`
  - `user can access print preview endpoints from reports menu`
- **Keseluruhan Test Suite Aplikasi**:
  `php artisan test` berhasil mengeksekusi seluruh 144 test cases tanpa ada satupun kegagalan (`144 passed, 684 assertions, 100% PASS`).
