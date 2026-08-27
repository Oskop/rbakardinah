# Implementation Plan - Menu Dokumentasi & Manual Book dengan Versioning (HTML & PDF)

Menerapkan modul **Dokumentasi / Manual Book (Buku Panduan)** terpadu pada aplikasi RBA RSUD Kardinah yang menyediakan panduan operasional dalam 2 format: **Halaman Web Interaktif (HTML)** dan **Dokumen PDF Resmi**. Kedua format dilengkapi dengan sistem **Versioning** (riwayat rilis, nomor versi, tanggal rilis, catatan pembaruan) dan seluruh kontennya dapat dikelola secara mandiri oleh **Administrator**.

---

## User Review Required

> [!IMPORTANT]
> **Arsitektur & Alur Kerja Menu Dokumentasi:**
> 1. **Akses Pengguna (Semua Role - Administrator, Supervisor, Operator):**
>    - Terdapat menu **"Dokumentasi"** (📖) pada bilah navigasi utama (*navbar*).
>    - Membuka halaman pembaca (*Reader View*) di `/documentation` dengan 2 tab utama:
>      - **📖 Panduan Interaktif Web (HTML):** Halaman artikel berstruktur dengan *Table of Contents (TOC)* di bilah samping (*sticky sidebar*), panduan langkah demi langkah (alur Operator, Supervisor, Administrator), format tipografi yang rapi (*alerts*, *badges*, *steps*), dan selector riwayat versi panduan lampau.
>      - **📄 Dokumen Panduan PDF:** Informasi rilis versi PDF aktif, tombol **Lihat / Preview PDF** langsung di browser, tombol **Unduh Dokumen PDF**, serta tabel **Riwayat Versi PDF Terdahulu** (arsip rilis lama).
> 2. **Manajemen oleh Administrator:**
>    - Administrator memiliki menu khusus / tombol akses cepat **⚙️ Kelola Dokumentasi** di `/admin/documentation`.
>    - Administrator dapat membuat versi baru, mengedit konten HTML/Markdown, mengunggah file PDF versi baru, mengaktifkan versi tertentu sebagai versi live (*Active Version*), dan menghapus arsip versi.
> 3. **Integrasi Audit Log:**
>    - Setiap aktivitas penambahan versi, pengubahan isi panduan, pengunggahan file PDF, dan perubahan status aktif otomatis tercatat dalam **Log Data** sistem melalui trait `LogsActivity`.

---

## Proposed Changes

### 1. Database & Model

#### [NEW] [2026_08_27_000000_create_documentation_versions_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_08_27_000000_create_documentation_versions_table.php)
- Membuat tabel `documentation_versions`:
  - `id` (bigIncrements)
  - `type` (enum: `'html'`, `'pdf'`)
  - `version` (string, misal: `'v1.0.0'`)
  - `title` (string, misal: `'Buku Panduan Penggunaan Sistem RBA RSUD Kardinah'`)
  - `content` (longText, nullable - untuk HTML/Markdown konten web)
  - `file_path` (string, nullable - path file storage untuk PDF)
  - `file_size` (unsignedBigInteger, nullable - ukuran file dalam bytes)
  - `release_notes` (text, nullable - ringkasan pembaruan/changelog)
  - `released_at` (date / datetime)
  - `is_active` (boolean, default false)
  - `created_by` (foreignId to `users`, nullable)
  - `updated_by` (foreignId to `users`, nullable)
  - `timestamps`

#### [NEW] [DocumentationVersion.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/DocumentationVersion.php)
- Model Eloquent dengan trait `LogsActivity`.
- Scope queries: `scopeHtml()`, `scopePdf()`, `scopeActive()`.
- Helper attributes: `formatted_file_size`, `released_at_formatted`.

#### [NEW] [DocumentationVersionSeeder.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/DocumentationVersionSeeder.php)
- Menyiapkan konten awal panduan resmi (Versi 1.0.0) yang komprehensif mencakup panduan alur login, alur kerja Operator (input latar belakang, usulan belanja, upload PDF, revisi), alur kerja Supervisor (review & validasi/tolak), serta alur kerja Administrator (penetapan pagu, master data, log data).

---

### 2. Controllers & Routing

#### [NEW] [DocumentationController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/General/DocumentationController.php)
- Method `index(Request $request)`: Menampilkan antarmuka pembaca dokumentasi (tab HTML dan PDF) untuk versi aktif atau versi terpilih dari riwayat.
- Method `previewPdf(DocumentationVersion $version)`: Menyajikan file PDF langsung di browser (inline display).
- Method `downloadPdf(DocumentationVersion $version)`: Mengunduh file PDF resmi ke perangkat pengguna.

#### [NEW] [DocumentationManagementController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/DocumentationManagementController.php)
- Khusus role Administrator:
  - `index()`: Daftar seluruh versi dokumentasi HTML & PDF beserta statistik.
  - `create()`: Form pembuatan versi baru (HTML atau PDF).
  - `store()`: Menyimpan versi baru dan menangani upload file PDF jika tipe PDF.
  - `edit(DocumentationVersion $version)`: Form edit data versi dan konten.
  - `update()`: Memperbarui data versi atau file PDF.
  - `destroy()`: Menghapus versi dokumentasi.
  - `setActive(DocumentationVersion $version)`: Menjadikan versi tersebut sebagai versi aktif yang tampil ke pengguna.

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute publik terotentikasi:
  - `GET /documentation` -> `documentation.index`
  - `GET /documentation/pdf/preview/{version}` -> `documentation.pdf.preview`
  - `GET /documentation/pdf/download/{version}` -> `documentation.pdf.download`
- Menambahkan resource rute admin:
  - `admin/documentation` -> `admin.documentation.*`
  - `POST admin/documentation/{version}/set-active` -> `admin.documentation.set-active`

---

### 3. Views & Tampilan Antarmuka

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Menambahkan tautan **Dokumentasi** pada menu navigasi desktop dan mobile untuk semua pengguna.

#### [NEW] [resources/views/documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/documentation/index.blade.php)
- **Tampilan Pembaca Modern**:
  - Header dokumentasi dengan badge versi aktif, tanggal rilis, dan tombol riwayat versi (*version switcher modal*).
  - Tab Switcher: **📖 Panduan Interaktif (HTML)** vs **📄 Dokumen PDF**.
  - **Tab HTML**: Sidebar Table of Contents (TOC) yang menempel saat scroll, highlight seksi yang aktif, artikel panduan lengkap dengan formatting card, callout info/warning, dan langkah bergambar/skematis.
  - **Tab PDF**: Kartu informasi versi PDF aktif, preview iframe PDF langsung, tombol unduh file PDF, dan tabel riwayat arsip rilis PDF lama.

#### [NEW] [resources/views/admin/documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/index.blade.php)
- Panel CMS Dokumentasi untuk Administrator:
  - Kartu statistik (Total Versi HTML, Versi HTML Aktif, Total Versi PDF, Versi PDF Aktif).
  - Tabel Daftar Versi HTML dan Tabel Daftar Versi PDF.
  - Aksi: Set Versi Aktif, Preview, Edit, Hapus, dan Tambah Versi Baru.

#### [NEW] [resources/views/admin/documentation/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/create.blade.php)
#### [NEW] [resources/views/admin/documentation/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/edit.blade.php)
- Form pembuatan & pengeditan versi:
  - Pilihan Tipe: Web / HTML vs Dokumen PDF.
  - Nomor Versi, Judul Dokumen, Tanggal Rilis, Ringkasan Catatan Rilis (*Release Notes / Changelog*).
  - Untuk tipe HTML: Editor konten teks berstruktur (HTML/Markdown).
  - Untuk tipe PDF: Input uploader berkas PDF.
  - Checkbox: "Jadikan versi ini sebagai versi aktif".

---

### 4. Automated Tests

#### [NEW] [tests/Feature/General/DocumentationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/DocumentationTest.php)
- Menguji pembacaan dokumentasi HTML oleh semua role (Operator, Supervisor, Admin).
- Menguji pengalihan versi terpilih pada riwayat.
- Menguji preview dan download berkas PDF aktif dan arsip.

#### [NEW] [tests/Feature/Admin/DocumentationManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/DocumentationManagementTest.php)
- Menguji admin dapat membuat versi HTML baru dan versi PDF baru.
- Menguji admin dapat mengedit konten versi.
- Menguji admin dapat mengaktifkan versi tertentu (`setActive`).
- Menguji admin dapat menghapus versi.
- Menguji non-admin (Operator & Supervisor) tidak dapat mengakses panel pengelolaan admin (`403 Forbidden`).
- Menguji pencatatan ke dalam Activity Log saat admin melakukan modifikasi dokumentasi.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite dokumentasi:
  `php artisan test --filter=Documentation`
- Menjalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator**:
   - Klik menu **Dokumentasi** di navigasi atas.
   - Baca panduan interaktif HTML, gunakan Table of Contents untuk berpindah bab.
   - Pindah ke tab **Dokumen PDF**, uji tombol **Lihat PDF** dan **Unduh PDF**.
2. Login sebagai **Administrator**:
   - Masuk ke panel **Kelola Dokumentasi** (`/admin/documentation`).
   - Buat versi HTML baru (misal `v1.1.0`) dengan konten baru, aktifkan versi tersebut.
   - Verifikasi versi baru langsung tampil pada halaman pembaca publik.
   - Buat versi PDF baru dengan mengunggah file PDF, aktifkan versi tersebut.
   - Verifikasi riwayat versi lama tetap tercatat dan dapat diakses di bagian arsip.
   - Buka menu **Log Data** (`/admin/logs`) untuk memverifikasi aktivitas perubahan dokumentasi telah tercatat dalam log.
