# Implementation Plan - Monitoring Penginputan Unit (Level Supervisor & Operator) pada Halaman RBA Administrator

Mengembangkan bagian **Status Unit** pada halaman detail RBA periode tertentu di sisi Administrator (`admin.headers.show`) menjadi sebuah **Panel Monitoring Progres Penginputan Komprehensif** yang memantau kesiapan penginputan dari tingkat Supervisor hingga tingkat Operator secara transparan dan detail.

---

## User Review Required

> [!IMPORTANT]
> **Kriteria Monitoring yang Disajikan:**
> 1. **Level Unit & Supervisor:**
>    - **Nama Unit & Status Pengajuan:** `Draft`, `Pending Supervisor`, atau `Validated`.
>    - **Supervisor Unit:** Nama-nama Supervisor aktif yang bertugas di unit tersebut.
>    - **Total Usulan per Supervisor / Unit:** Akumulasi nominal rupiah seluruh usulan belanja yang diajukan di unit tersebut (`Rp ...`).
>    - **Status Validasi Supervisor:** Jumlah rincian belanja yang sudah divalidasi, ditolak, atau masih pending review.
> 2. **Level Operator (Detail per Operator Aktif):**
>    - **Identitas Operator:** Nama Operator, NIP, dan email.
>    - **Status Latar Belakang:** Status apakah operator sudah menginput data latar belakang usulan (`✓ Sudah Diisi` vs `⚠️ Belum Diisi`).
>    - **Besar Nominal Usulan Operator:** Total nominal usulan belanja yang diajukan oleh operator tersebut beserta jumlah item rinciannya.
>    - **Kelengkapan Dokumen PDF:**
>      - **Dokumen Wajib (KAK, RAK, RTP):** Status keterisian berkas KAK, RAK, dan RTP untuk operator bersangkutan (disertai indikator badge per dokumen).
>      - **PDF Lampiran Usulan Belanja:** Rasio rincian belanja milik operator yang telah dilengkapi file PDF lampiran (misal: `3/3 PDF Terunggah`).
>      - **Status Kelengkapan Operator:** Badge `✓ Lengkap` atau `⚠️ Belum Lengkap`.
> 3. **Interaktivitas & Efisiensi Tampilan:**
>    - Dilengkapi pencarian nama unit/supervisor/operator.
>    - Mekanisme **Accordion / Buka-Tutup per Unit** serta tombol **"Buka Semua"** dan **"Tutup Semua"** agar halaman tidak terlalu panjang.

---

## Proposed Changes

### 1. Controller Layer

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Pada method `show(\App\Models\RbaHeader $header)`:
  - Melakukan eager loading teroptimasi (*single query batch*, bebas N+1 query):
    - `submissions.unit.users` (difilter hanya `is_active = true`)
    - `submissions.operatorBackgrounds`
    - `submissions.documents.latestVersion`
    - `submissions.details.attachments`
  - Menyusun struktur data `$unitMonitoring` untuk setiap submission:
    - Informasi Supervisor aktif di unit.
    - Total nominal usulan unit dan progres validasi review supervisor.
    - Rincian metrik per Operator aktif:
      - Status latar belakang (`has_background`)
      - Total nominal usulan operator (`nominal_usulan` dan `item_count`)
      - Status dokumen wajib KAK, RAK, RTP (`has_kak`, `has_rak`, `has_rtp`)
      - Rasio lampiran PDF usulan belanja (`details_with_pdf_count` dari `total_details_count`)
      - Status kelengkapan akumulatif (`is_all_complete`)
  - Mengirimkan variabel `$unitMonitoring` ke view `admin.headers.show`.

---

### 2. View Layer

#### [MODIFY] [show.blade.php (Administrator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Mengganti bagian sederhana `Status Unit` (baris 187-198) dengan **Panel Monitoring Progres Penginputan Unit**:
  - **Overview KPI Cards:** Menampilkan ringkasan Total Unit, Unit Validated, Unit Pending, dan Unit Draft.
  - **Toolbar Interaktif:** Input pencarian cepat unit/nama pengguna, serta tombol aksi **Buka Semua Unit** dan **Tutup Semua Unit**.
  - **Accordion List per Unit Kerja:**
    - Baris Header Unit menampilkan nama unit, badge status submission, nama supervisor, total usulan (Rp), status validasi review, dan tombol dropdown detail operator.
    - Sub-tabel / Drawer Operator menampilkan baris untuk setiap operator aktif dengan kolom:
      1. Operator (Nama & NIP)
      2. Status Latar Belakang (Badge + cuplikan)
      3. Nominal Usulan (Rp & jumlah item)
      4. Kelengkapan PDF Dokumen (Badge KAK, RAK, RTP)
      5. PDF Lampiran Rincian (Rasio terunggah)
      6. Status Kelengkapan Akhir

---

### 3. Automated Tests Layer

#### [MODIFY] [AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)
- Menambahkan test case:
  - `test_admin_can_view_unit_monitoring_with_supervisor_and_operator_progress()`:
    - Membuat data header, unit, supervisor, dan operator.
    - Menguji bahwa halaman `admin.headers.show` memuat panel monitoring, nama supervisor, total usulan unit, status latar belakang operator, nominal usulan operator, dan indikator kelengkapan PDF (KAK/RAK/RTP).

---

## Verification Plan

### Automated Tests
- Menjalankan test suite Admin Dashboard & Header:
  `php artisan test --filter=AdminDashboardTest`
- Menjalankan seluruh test suite aplikasi untuk memastikan tidak ada regresi:
  `php artisan test`

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke menu RBA, lalu buka salah satu RBA periode aktif (`/admin/headers/{id}`).
3. Periksa panel **Monitoring Progres Penginputan Unit**:
   - Pastikan seluruh unit terdaftar muncul dengan status pengajuan yang sesuai.
   - Periksa kolom Supervisor: muncul nama supervisor aktif dan total usulan belanja unit.
   - Klik salah satu unit untuk membuka rincian operator:
     - Verifikasi status pengisian Latar Belakang masing-masing operator.
     - Verifikasi nominal usulan masing-masing operator.
     - Verifikasi badge dokumen PDF (KAK, RAK, RTP) dan rasio PDF lampiran usulan.
   - Uji tombol **"Buka Semua"** dan **"Tutup Semua"** serta filter pencarian unit.
