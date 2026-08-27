# Implementation Plan - Menu Dokumentasi & Manual Book Bergaya GoFiber Docs dengan Versioning (HTML & PDF)

Menerapkan sistem **Dokumentasi & Manual Book (Buku Panduan)** modern pada aplikasi RBA RSUD Kardinah dengan tampilan dan arsitektur mirip **Dokumentasi Package Modern (GoFiber Docs / Docusaurus / VitePress)**. Dokumentasi menyediakan dua format: **Panduan Web Interaktif (HTML)** berstruktur bab/artikel dengan navigasi lengkap, serta **Buku Panduan PDF**. Keduanya mendukung sistem **Versioning** dan seluruh kontennya dapat dikelola secara dinamis oleh **Administrator**.

---

## Konsep Desain & Fitur Tampilan (GoFiber Style)

```
┌─────────────────────────────────────────────────────────────────────────────────────────────────┐
│ 📖 Dokumentasi RBA RSUD Kardinah   [🔍 Cari Dokumen... Ctrl+K]   [v1.0.0 ▼]  [📄 PDF] [⚙️ Admin] │
├───────────────────────────────┬───────────────────────────────────────────┬─────────────────────┤
│ 📂 PENGENALAN                 │ 🏠 Dokumentasi > Operator > Input Rincian │ ON THIS PAGE        │
│   🚀 Tentang Aplikasi         │                                           │ • Formulir Usulan   │
│   🔑 Hak Akses & Alur Bisnis  │ # Penginputan Rincian Usulan Belanja      │ • Upload PDF        │
│ 📂 PANDUAN OPERATOR           │ Terakhir diperbarui: 27 Agustus 2026      │ • Validasi Nominal  │
│   📝 Latar Belakang Unit      │                                           │ • Tips & Catatan    │
│   ✍️ Input Rincian Usulan     │ [💡 TIP: Pastikan latar belakang sudah... │                     │
│   📤 Pengajuan Usulan         │                                           │                     │
│   🔄 Revisi Usulan Ditolak    │ 1. Pilih nomor rekening belanja           │                     │
│   🖨️ Cetak & Ekspor PDF       │ 2. Masukkan deskripsi rincian belanja     │                     │
│ 📂 PANDUAN SUPERVISOR         │ 3. Unggah berkas PDF pendukung            │                     │
│   🔍 Review & Validasi        │                                           │                     │
│   📊 Cetak RBA Final          │ ┌───────────────────────────────────────┐ │                     │
│ 📂 PANDUAN ADMINISTRATOR      │ │ ← Sebelumnya             Selanjutnya → │ │                     │
│   💰 Penetapan Pagu           │ │   Latar Belakang         Pengajuan    │ │                     │
│   📋 Log Data & Audit         │ └───────────────────────────────────────┘ │                     │
└───────────────────────────────┴───────────────────────────────────────────┴─────────────────────┘
```

> [!IMPORTANT]
> **Karakteristik Utama Desain GoFiber Docs:**
> 1. **Bilah Navigasi Kiri (Left Sidebar Tree):**
>    - Pengelompokan bab terstruktur (*Categories & Articles*): *Pengenalan*, *Panduan Operator*, *Panduan Supervisor*, *Panduan Administrator*, *FAQ & Troubleshooting*.
>    - Ikon topik, indikator halaman aktif (*active pill highlight*), dan responsif *mobile drawer*.
> 2. **Pencarian Cepat Instan (*Instant Search Modal - Ctrl+K*):**
>    - Shortcut keyboard `Ctrl + K` untuk membuka modal pencarian cepat di seluruh artikel dokumentasi secara *real-time*.
> 3. **Area Baca Artikel (Center Content Reader):**
>    - Breadcrumb navigasi, judul artikel, estimasi baca / tanggal update.
>    - Format konten kaya: *Callout Admonitions* (💡 *Tip*, ℹ️ *Info*, ⚠️ *Warning*, ⛔ *Danger*), *Step-by-step numbering cards*, tabel terformat rapi, dan tombol navigasi *Previous / Next Article* di bagian bawah artikel.
> 4. **Bilah Daftar Isi Kanan (*Right Sidebar - On This Page TOC*):**
>    - Mendeteksi sub-heading (H2, H3) pada artikel secara otomatis dengan efek *Scrollspy* (mengikuti posisi scroll layar) dan *smooth scrolling* saat diklik.
> 5. **Pengalih Versi (*Version Selector Dropdown*):**
>    - Memilih versi dokumentasi (misal `v1.0.0 (Terbaru)`, `v0.9.0 (Arsip)`) secara instan.
> 6. **Tab Buku Panduan PDF:**
>    - Menyediakan download resmi file PDF versi aktif, inline PDF preview di browser, serta tabel arsip versi PDF terdahulu.

---

## Proposed Changes

### 1. Database & Model

#### [NEW] [2026_08_27_000000_create_documentation_tables.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_08_27_000000_create_documentation_tables.php)
- **Tabel `documentation_versions`**:
  - `id` (bigIncrements)
  - `type` (enum: `'html'`, `'pdf'`)
  - `version` (string, misal: `'v1.0.0'`)
  - `title` (string, misal: `'Dokumentasi Resmi RBA RSUD Kardinah'`)
  - `file_path` (string, nullable - path PDF)
  - `file_size` (unsignedBigInteger, nullable - ukuran file PDF)
  - `release_notes` (text, nullable - changelog/ringkasan pembaruan)
  - `released_at` (date / datetime)
  - `is_active` (boolean, default false)
  - `created_by` (foreignId to `users`, nullable)
  - `updated_by` (foreignId to `users`, nullable)
  - `timestamps`

- **Tabel `documentation_articles`** (untuk dokumen HTML gaya GoFiber):
  - `id` (bigIncrements)
  - `documentation_version_id` (foreignId to `documentation_versions`, cascade on delete)
  - `category` (string, misal: `'Pengenalan'`, `'Panduan Operator'`, `'Panduan Supervisor'`, `'Panduan Administrator'`, `'FAQ & Troubleshooting'`)
  - `title` (string, misal: `'Penginputan Rincian Usulan Belanja'`)
  - `slug` (string, misal: `'penginputan-rincian-usulan'`)
  - `icon` (string, nullable, misal: `'📝'`, `'🚀'`, `'🔍'`, `'👑'`, `'💡'`)
  - `order` (integer, default 0 - urutan bab di sidebar)
  - `content` (longText - konten HTML/Markdown artikel)
  - `timestamps`

#### [NEW] [DocumentationVersion.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/DocumentationVersion.php)
- Model Eloquent dengan relasi `hasMany(DocumentationArticle::class)` dan trait `LogsActivity`.
- Scopes: `scopeHtml()`, `scopePdf()`, `scopeActive()`.

#### [NEW] [DocumentationArticle.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/DocumentationArticle.php)
- Model Eloquent dengan relasi `belongsTo(DocumentationVersion::class)` dan trait `LogsActivity`.

#### [NEW] [DocumentationSeeder.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/DocumentationSeeder.php)
- Seeder komprehensif berisi 16 artikel lengkap yang terstruktur rapi mencakup seluruh alur operasional:
  - *Pengenalan*: Tentang Aplikasi RBA, Hak Akses & Alur Bisnis, Autentikasi & Pengaturan Profil.
  - *Panduan Operator*: Pengisian Latar Belakang, Input Rincian Belanja, Pengajuan Usulan, Penanganan Usulan Ditolak & Revisi PDF, Cetak & Ekspor.
  - *Panduan Supervisor*: Review Usulan Unit, Validasi & Penolakan Usulan, Cetak RBA Final.
  - *Panduan Administrator*: Pengelolaan Master Data, Penetapan Pagu Rekening, Audit Trail & Log Data, Pengelolaan Dokumentasi.
  - *FAQ & Troubleshooting*: Solusi kendala umum.

---

### 2. Controllers & Routing

#### [NEW] [DocumentationController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/General/DocumentationController.php)
- Method `index(Request $request)`:
  - Mengambil versi aktif (atau versi dari dropdown selector).
  - Menampilkan pembaca GoFiber Docs dengan sidebar bab, artikel aktif, TOC kanan, dan modal pencarian instan.
- Method `article(string $version, string $slug)`:
  - Menampilkan artikel spesifik dengan URL bersih dan bookmarkable.
- Method `previewPdf(DocumentationVersion $version)`:
  - Menampilkan berkas PDF langsung di browser.
- Method `downloadPdf(DocumentationVersion $version)`:
  - Mengunduh berkas PDF resmi ke komputer pengguna.

#### [NEW] [DocumentationManagementController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/DocumentationManagementController.php)
- Khusus Administrator:
  - `index()`: Manajemen daftar versi (HTML & PDF), ringkasan statistik artikel & rilis.
  - `createVersion()` / `storeVersion()`: Buat versi baru (HTML atau PDF).
  - `editVersion()` / `updateVersion()`: Edit metadata versi atau unggah file PDF baru.
  - `setActive(DocumentationVersion $version)`: Mengaktifkan versi sebagai versi live.
  - `destroyVersion(DocumentationVersion $version)`: Hapus versi.
  - `articles(DocumentationVersion $version)`: Daftar artikel dalam versi HTML terpilih.
  - `createArticle(DocumentationVersion $version)` / `storeArticle()`: Tambah artikel baru.
  - `editArticle(DocumentationArticle $article)` / `updateArticle()`: Edit artikel (kategori, judul, slug, urutan, isi konten).
  - `destroyArticle(DocumentationArticle $article)`: Hapus artikel.

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Rute pembaca publik (semua role):
  - `GET /documentation` -> `documentation.index`
  - `GET /documentation/{version}/{slug}` -> `documentation.article`
  - `GET /documentation/pdf/preview/{version}` -> `documentation.pdf.preview`
  - `GET /documentation/pdf/download/{version}` -> `documentation.pdf.download`
- Rute admin panel:
  - `admin/documentation` -> `admin.documentation.*`
  - `admin/documentation/versions/{version}/articles` -> `admin.documentation.articles.*`

---

### 3. Views & Layout

#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Menambahkan menu **Dokumentasi** (📖) di navigasi desktop dan mobile untuk semua pengguna.

#### [NEW] [resources/views/documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/documentation/index.blade.php)
- **Tampilan GoFiber Docs Layout**:
  - Header: Logo, Search Bar (`Ctrl+K`), Version Picker, Tab PDF / Web, dan Tombol Kelola Admin.
  - Left Sidebar: Daftar kategori & artikel (collapsible & reactive).
  - Center: Article Reader (styling tipografi elegan, admonitions callouts, step cards, navigation Next/Prev).
  - Right Sidebar: "On This Page" Table of Contents dengan scrollspy & smooth scrolling.
  - Modal Instant Search (`Ctrl+K`): Pencarian instan seluruh artikel & kata kunci dengan jump to article.
  - Tab PDF Viewer: Tampilan PDF resmi, tombol preview & download, serta arsip versi lama.

#### [NEW] [resources/views/admin/documentation/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/index.blade.php)
#### [NEW] [resources/views/admin/documentation/articles.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/articles.blade.php)
#### [NEW] [resources/views/admin/documentation/article-form.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/article-form.blade.php)
#### [NEW] [resources/views/admin/documentation/version-form.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/documentation/version-form.blade.php)
- Antarmuka manajemen versi dan artikel yang intuitif untuk Administrator.

---

### 4. Automated Tests

#### [NEW] [tests/Feature/General/DocumentationTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/DocumentationTest.php)
- Menguji pembacaan dokumentasi GoFiber-style oleh semua role.
- Menguji navigasi per artikel via slug.
- Menguji pengalihan versi via version selector.
- Menguji preview dan download berkas PDF aktif dan arsip.

#### [NEW] [tests/Feature/Admin/DocumentationManagementTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/DocumentationManagementTest.php)
- Menguji CRUD versi HTML dan PDF.
- Menguji CRUD artikel dokumentasi oleh Admin.
- Menguji pengaktifan versi live (`setActive`).
- Menguji proteksi otorisasi (Operator/Supervisor ditolak 403 saat mengakses manajemen admin).
- Menguji pencatatan aktivitas dokumentasi ke dalam Activity Log.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite dokumentasi:
  `php artisan test --filter=Documentation`
- Menjalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Buka menu **Dokumentasi** di navigasi:
   - Verifikasi tampilan bergaya GoFiber Docs (Left Sidebar, Article Center, Right Sidebar "On This Page").
   - Tekan `Ctrl + K` di keyboard, ketik kata kunci (misal: "revisi", "pagu", "supervisor"), lalu klik hasil pencarian untuk berpindah artikel seketika.
   - Klik sub-heading di bilah "On This Page" untuk menguji smooth scrolling.
   - Buka tab **Dokumen PDF**, uji tombol **Lihat PDF** dan **Unduh PDF**.
2. Masuk sebagai **Administrator** ke panel **Kelola Dokumentasi**:
   - Tambah artikel baru pada kategori tertentu dan verifikasi langsung tampil di sidebar pembaca.
   - Buat rilis versi baru dan uji perpindahan versi pada dropdown Version Selector.
   - Cek menu **Log Data** untuk memastikan aktivitas tercatat dalam audit log.
