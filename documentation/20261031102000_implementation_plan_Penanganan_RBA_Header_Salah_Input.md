# Implementation Plan: Sistem Hard Delete Terkontrol dengan Dedicated Archive Table & Mekanisme 100% Full Restore

Dokumen ini merevisi rencana implementasi sebelumnya dengan mengintegrasikan kebutuhan **Jaring Pengaman Pemulihan (*Fail-Safe Restoration*)**:
1. Menghapus data RBA Header yang salah dari tabel operasional agar database operasional tetap bersih, tertib, dan tidak memicu pertanyaan BPK.
2. Menyimpan seluruh snapshot data yang dihapus beserta seluruh pohon relasi anaknya ke dalam **tabel arsip khusus (`hard_delete_archives`)**.
3. Menyediakan **mekanisme pemulihan (*restore*) 100% presisi** yang mampu mengembalikan data ke tabel aslinya persis seperti sedia kala (termasuk Primary Key ID, Foreign Key, dan file lampiran).
4. Arsitektur tabel arsip dirancang **generik / polimorfik**, sehingga di masa depan dapat menampung penghapusan entitas lain selain `rba_headers` (misal: `master_barangs`, `units`, `account_codes`, dll).
5. **Nol Dampak / Zero Impact** ke 14+ controller yang sudah ada (tidak perlu mengubah query operasional).

---

## 1. Arsitektur Solusi: Hybrid Clean Hard Delete + Dedicated Archive

```
================================ ALUR OPERASIONAL NORMAL ================================
Tabel Operasional (rba_headers, rba_submissions, rba_details, dll)
  ├── Hanya memuat data sah yang aktif digunakan
  ├── Zero query modification di 14+ controller (tanpa perlu where('is_active', true))
  └── Bebas dari anomali temuan BPK (tidak ada RBA ganda/menggantung di tahun yang sama)

================================ AKSI PENGHAPUSAN (DELETE) ===============================
Admin klik "Hapus RBA" (dengan alasan & konfirmasi)
  │
  ├── 1. DB::transaction dimulai
  ├── 2. Capture Snapshot Utuh:
  │      - Root Data: record rba_headers lengkap dengan ID & timestamp
  │      - Related Data: seluruh submissions, pagus, details, attachments, documents, backgrounds
  ├── 3. Simpan Snapshot ke tabel `hard_delete_archives`
  ├── 4. Hapus fisik dari tabel operasional (cascade delete berjenjang)
  ├── 5. Catat ke `activity_logs` (Audit Trail)
  └── 6. DB::commit selesai

================================ AKSI PEMULIHAN (RESTORE) ===============================
Admin klik "Pulihkan / Restore" di Menu Arsip Sampah (Recycle Bin)
  │
  ├── 1. Validasi: Pastikan ID asli belum dipakai record baru
  ├── 2. DB::transaction dimulai
  ├── 3. Rekonstruksi Data Asli (Preserving Original IDs & Foreign Keys):
  │      - Insert rba_headers (ID asli tetap sama)
  │      - Insert rba_submissions (ID asli tetap sama)
  │      - Insert rba_account_pagus & backgrounds
  │      - Insert rba_details, attachments, dan documents
  ├── 4. Update status arsip: `restored_at = now()`, `restored_by = admin_id`
  ├── 5. Catat ke `activity_logs` (Audit Trail)
  └── 6. Data kembali aktif 100% di tabel operasional persis sebelum dihapus
```

---

## 2. Desain Skema Database: `hard_delete_archives`

Tabel baru ini dibuat melalui migrasi database khusus.

### 2.1 Struktur Tabel `hard_delete_archives`

| Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | `bigint unsigned auto_increment` | Primary key arsip |
| `model_type` | `string` | FQCN Model (contoh: `App\Models\RbaHeader`) |
| `model_id` | `unsignedBigInteger` | ID asli record saat berada di tabel operasional |
| `label` | `string` | Judul ringkas yang mudah dibaca manusia (contoh: *"RBA Header 2026 - RBA Murni (ID #3)"*) |
| `reason` | `text` (nullable) | Alasan penghapusan yang diinput oleh Admin |
| `root_data` | `json` / `longText` | Seluruh atribut baris utama dalam bentuk JSON lengkap |
| `related_data` | `json` / `longText` (nullable) | Seluruh pohon anak relasi (submissions, details, pagu, attachment, dll) dalam bentuk JSON berstruktur |
| `metadata` | `json` (nullable) | Info tambahan (contoh: daftar path file PDF yang di-preserve di storage) |
| `deleted_by` | `foreignId('users')` | ID Admin yang melakukan hard delete |
| `deleted_at` | `timestamp` | Waktu penghapusan |
| `restored_at` | `timestamp` (nullable) | Waktu pemulihan (null jika belum di-restore) |
| `restored_by` | `foreignId('users')` (nullable) | ID Admin yang melakukan restore |
| `timestamps` | `created_at, updated_at` | Standar Laravel |

> [!NOTE]
> **Keunggulan Desain Generic / Polymorphic**:
> Kolom `model_type` dan `model_id` membuat tabel ini berlaku seperti "Time Capsule / Recycle Bin Universal". Jika di kemudian hari ada kebutuhan hard delete untuk master barang, unit, rekening, atau modul lainnya, tabel ini langsung dapat digunakan kembali tanpa migrasi tambahan.

---

## 3. Detail Mekanisme 100% Faithful Restoration

Agar data pulih **100% identik tanpa kehilangan integritas**:

1. **Preservasi Primary Key Asli**:
   - Pemulihan tidak menggunakan `Model::create()` biasa yang membuat ID baru, melainkan menggunakan `DB::table('table_name')->insert($data)` yang secara eksplisit menyertakan kolom `id` asli dan `created_at` / `updated_at` asli.
   - Hal ini menjamin seluruh referensi foreign key antar tabel (`submission_id`, `rba_header_id`, `detail_id`) tetap bertaut sempurna 100% seperti sediakala.
2. **Preservasi File Fisik (PDF Lampiran)**:
   - Saat hard delete dilakukan, file fisik PDF lampiran di `storage/app/public/...` **TIDAK DIHAPUS**, melainkan tetap dipertahankan (atau dipindahkan ke direktori arsip yang terisolasi).
   - Saat di-restore, URL lampiran langsung aktif kembali dan dapat diunduh tanpa kendala *404 File Not Found*.
3. **Pengecekan Konflik ID (*Collision Guard*)**:
   - Sebelum restore dijalankan, sistem memastikan bahwa tidak ada record aktif di tabel `rba_headers` yang menggunakan ID yang sama atau kombinasi unik yang sama (`period_id` + `year`).
   - Jika ada konflik (misalnya periode dan tahun tersebut sudah diisi RBA lain yang sah), sistem akan menolak proses restore dengan pesan error yang jelas dan aman.

---

## 4. Antarmuka Pengguna (UI/UX) & Kontrol Admin

1. **Tombol Hapus pada Menu Admin Headers (`/admin/headers`)**:
   - Di samping tombol "Lock RBA" dan "View Submissions", ditambahkan tombol merah **"Hapus RBA"**.
   - Ketika diklik, muncul modal dialog konfirmasi yang meminta Admin menginput **Alasan Penghapusan** (wajib diisi, misal: *"Salah input periode, digantikan oleh RBA ID #4"*).
2. **Menu Khusus "Arsip Data Dihapus / Recycle Bin" (`/admin/archives`)**:
   - Menyediakan daftar seluruh data yang pernah di-hard-delete.
   - Menampilkan tanggal hapus, admin pembuat aksi, alasan, dan status (`Terhapus` atau `Sudah Dipulihkan`).
   - Tombol **"Lihat Detail Snapshot"** untuk menginspeksi data JSON yang tersimpan.
   - Tombol hijau **"Pulihkan Data (Restore)"** dengan dialog konfirmasi aman.
3. **Dukungan Artisan Command (CLI)**:
   - Untuk skenario dev/maintenance atau eksekusi cepat terminal:
     - `php artisan rba:delete-header {id} --reason="Alasan..."`
     - `php artisan rba:restore-header {archive_id}`

---

## 5. File yang Akan Dibuat & Dimodifikasi

| No | Berkas | Tipe | Deskripsi |
|---|---|---|---|
| 1 | `database/migrations/xxxx_create_hard_delete_archives_table.php` | **Migration** | Membuat tabel `hard_delete_archives` dengan kolom polimorfik, JSON payload, dan tracking restore. |
| 2 | `app/Models/HardDeleteArchive.php` | **Model** | Model Eloquent dengan casts JSON, helper methods (`isRestored()`, `scopeUnrestored()`, dll). |
| 3 | `app/Services/HardDeleteArchiveService.php` | **Service** | Logika inti: Snapshotting pohon data RBA, cascading delete di dalam `DB::transaction`, dan rekonstruksi restore 100% presisi. |
| 4 | [app/Http/Controllers/RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php) | **Controller** | Mengisi fungsi `destroy()` menggunakan `HardDeleteArchiveService`. |
| 5 | `app/Http/Controllers/Admin/HardDeleteArchiveController.php` | **Controller** | Mengelola tampilan daftar arsip terhapus (`index`), detail snapshot (`show`), dan aksi restore (`restore`). |
| 6 | [resources/views/admin/headers/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/index.blade.php) | **Blade View** | Menambahkan tombol modal "Hapus RBA" dengan input alasan penghapusan. |
| 7 | `resources/views/admin/archives/index.blade.php` | **Blade View** | Halaman Recycle Bin / Arsip Sampah untuk memonitor data terhapus dan melakukan restore. |
| 8 | `app/Console/Commands/DeleteRbaHeaderCommand.php` | **Artisan Command** | Command CLI untuk hapus aman via terminal. |
| 9 | `app/Console/Commands/RestoreRbaHeaderCommand.php` | **Artisan Command** | Command CLI untuk restore via terminal. |
| 10 | `tests/Feature/Admin/HardDeleteAndRestoreRbaTest.php` | **Feature Test** | Pengujian menyeluruh: hard delete, integritas snapshot JSON, cascading delete, pemulihan 100% presisi, dan proteksi BPK audit log. |

---

## 6. Tahapan Eksekusi

```
Step 1: Migrasi Database & Model hard_delete_archives
  └─ Jalankan migrasi tabel hard_delete_archives
  └─ Buat model HardDeleteArchive dengan konfigurasi cast json

Step 2: Pembangunan Service HardDeleteArchiveService
  └─ Method captureRbaHeader(RbaHeader $header): ekstrak root dan semua relasi (submissions, details, pagus, docs, dll)
  └─ Method deleteRbaHeader(RbaHeader $header, string $reason, int $userId): simpan arsip + hapus DB operasional
  └─ Method restoreRbaHeader(HardDeleteArchive $archive, int $userId): rekonstruksi urutan tabel dengan ID asli 100%

Step 3: Integrasi RbaHeaderController & Web UI
  └─ Update RbaHeaderController@destroy untuk memanggil HardDeleteArchiveService
  └─ Buat HardDeleteArchiveController (index, show, restore) & routes di web.php
  └─ Tambahkan tombol hapus & modal alasan di admin/headers/index.blade.php
  └─ Buat tampilan admin/archives/index.blade.php

Step 4: Artisan Commands & Helper CLI
  └─ Buat rba:delete-header dan rba:restore-header

Step 5: Eksekusi Penghapusan RBA Header yang Salah
  └─ Identifikasi ID RBA Header salah input yang dimaksud pengguna
  └─ Eksekusi penghapusan: data masuk ke hard_delete_archives, tabel rba_headers bersih
  └─ Uji coba simulasi restore untuk memastikan kehandalan pemulihan 100%

Step 6: Verifikasi & Testing Komprehensif
  └─ Jalankan php artisan test
  └─ Verifikasi tidak ada error di dashboard operator, supervisor, maupun laporan
```

---

## 7. Kriteria Keberhasilan (Success Criteria)

1. **Tabel Operasional Bersih**: RBA Header yang salah tidak lagi muncul di halaman manapun pada alur operasional.
2. **Kesiapan Audit BPK**: Jika BPK mempertanyakan penghapusan, Admin memiliki bukti rekaman sah di `hard_delete_archives` dan `activity_logs` lengkap dengan tanggal, pelaku, dan alasan.
3. **Mekanisme Restore 100% Berfungsi**: Menjalankan fungsi restore mengembalikan RBA Header beserta seluruh submissions, details, pagus, dan lampirannya ke tabel operasional tanpa ada data yang korup atau berubah ID-nya.
4. **Zero Regression**: Seluruh 282 pengujian otomatis aplikasi tetap berstatus **PASS** (100% hijau).
