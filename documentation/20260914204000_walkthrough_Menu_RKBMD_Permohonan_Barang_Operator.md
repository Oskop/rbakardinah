# Walkthrough: Modul Rencana Kebutuhan Barang Milik Daerah (RKBMD) Permohonan Barang Antar-Operator

Implementasi modul **Rencana Kebutuhan Barang Milik Daerah (RKBMD)** pada SIPAKAR RSUD Kardinah Kota Tegal telah selesai dieksekusi secara menyeluruh dan seluruh pengujian otomatis lulus **100% PASS (224 test cases, 1108 assertions)** tanpa regresi (*Zero Regression*).

Modul ini memfasilitasi seluruh user level Operator (baik staf non-pengusul/viewer maupun pengusul) untuk mengajukan permohonan kebutuhan barang kepada Operator Pengusul RBA (`can_propose = 1`), lengkap dengan penanganan **Dua Skenario Tindak Lanjut** (Balasan Keputusan vs Pengalihan Berantai Berita Acara), visualisasi **Timeline Riwayat Lengkap**, katalog **Master Barang BMD Permendagri No. 108 Tahun 2016**, kolom audit lengkap (`created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`), serta pencatatan otomatis ke **Menu Log Data (`activity_logs`)**.

---

## 1. Ringkasan Fitur yang Telah Dibangun

### 1.1 Asal Sub-Unit Terisi Otomatis & Terkunci
- Pada formulir pengajuan permohonan (`/operator/rkbmd/create`), kolom **Asal Sub-Unit Pemohon** otomatis terisi dari profil user yang login (`Auth::user()->sub_unit_id` atau unit induk jika tanpa sub-unit) dan dikunci (*readonly/disabled*).
- Hal ini menjamin akuntabilitas bahwa setiap permohonan merepresentasikan kebutuhan riil dari unit kerja/sub-unit operasional pemohon.

### 1.2 Pilihan Operator Tujuan Khusus Pengusul (`can_propose = 1`)
- Dropdown operator tujuan difilter secara ketat: hanya menampilkan user dengan `role = 'Operator'`, `can_propose = true`, `is_active = true`, dan bukan diri sendiri.
- Dilengkapi penamaan lengkap: Nama Pegawai, NIP, Unit Induk, Sub-Unit, dan Jabatan Kedinasan.

### 1.3 Katalog Barang Permendagri No. 108 Tahun 2016 & Multi-Item Dinamis
- Disediakan tabel `master_barangs` yang memuat kodefikasi barang milik daerah sesuai Permendagri No. 108 Tahun 2016 (menyimpan `id`, `kode_barang`, `nama_barang`, `satuan`, `deskripsi`, `is_active`, dan kolom pelengkap audit).
- Seeder awal [`MasterBarangSeeder.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/MasterBarangSeeder.php) telah memuat 20 sampel kodefikasi barang umum rumah sakit (komputer/IT, alat kedokteran, ranjang pasien, mebeuler, kulkas reagen/vaksin, AC, dan ATK).
- Form input barang menggunakan komponen dinamis (Alpine.js) untuk menambah dan menghapus baris item barang secara leluasa.
- Pemohon wajib mengunggah file **PDF Memo Intern / Nota Dinas** (maksimum 10MB) yang disimpan di storage aplikasi.

### 1.4 Dua Skenario Tindak Lanjut Pasca Pengajuan
Operator tujuan yang memegang berkas memiliki dua opsi penanganan:
1. **Skenario 1 (Pemberian Balasan / Keputusan)**:
   - Memilih salah satu status keputusan resmi:
     - `Dipenuhi` (Diakomodir penuh dalam usulan belanja)
     - `Dipenuhi Sebagian` (Diakomodir sebagian volume/item)
     - `Substitusi` (Dipenuhi dengan barang pengganti/alternatif)
     - `Optimalisasi` (Dipenuhi melalui pemanfaatan/mutasi aset BMD yang sudah ada)
     - `Ditolak` (Kebutuhan belum dapat dipenuhi)
   - Mengisi catatan teks bebas balasan yang menjelaskan rincian keputusan/arahan.
2. **Skenario 2 (Pengalihan Berantai / Multi-Tier Forwarding)**:
   - Jika kebutuhan barang berada di bawah wewenang pengusul lain (misal: permohonan printer diajukan ke Farmasi, lalu dialihkan ke PIC IT/SIMRS), operator tujuan dapat mengalihkan berkas ke operator pengusul (`can_propose = 1`) lain.
   - Status permohonan diperbarui menjadi `Dialihkan` dan pemegang berkas berpindah ke penerima baru.
   - Operator pengalih **wajib mengisi alasan pengalihan**.
   - Catatan pengalihan ini dapat dibaca oleh: **Operator Pemohon**, **Operator Pengalih**, dan **Operator Penerima Baru**.
   - Operator penerima baru kemudian dapat membalas (Skenario 1) atau mengalihkan kembali ke pengusul lain jika diperlukan (Skenario 2 berulang).

### 1.5 Timeline Riwayat Pengalihan & Status (Audit Trail Transparan)
- Seluruh mutasi berkas dicatat di tabel `rkbmd_histories` (`Pengajuan`, `Pengalihan`, `Balasan`).
- Disajikan dalam bentuk **Visual Timeline Interaktif** di halaman detail (`/operator/rkbmd/{id}`), memperlihatkan kronologi lengkap: siapa yang mengajukan, kapan dialihkan, oleh siapa ke siapa, alasan pengalihan, hingga balasan keputusan akhir.

### 1.6 Kolom Pelengkap Audit & Integrasi Log Data
- Seluruh tabel baru (`master_barangs`, `rkbmd_submissions`, `rkbmd_items`, `rkbmd_histories`) memiliki kolom:
  - `created_at` & `created_by` (FK `users.id`)
  - `updated_at` & `updated_by` (FK `users.id`)
  - `deleted_at` & `deleted_by` (FK `users.id`, soft deletes)
- Dibuat trait [`App\Traits\TracksUserAudit`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/TracksUserAudit.php) untuk mengisi kolom audit secara otomatis saat event Eloquent dipicu.
- Menggunakan trait [`App\Traits\LogsActivity`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php) sehingga setiap pembuatan, pembaruan, pengalihan, balasan, dan penghapusan otomatis tercatat di tabel `activity_logs` (Menu **Log Data** Administrator).

---

## 2. Rincian Perubahan Berkas Kode

### Database & Migrations
1. [`database/migrations/2026_09_14_100001_create_master_barangs_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_14_100001_create_master_barangs_table.php): Tabel katalog barang Permendagri 108/2016 dengan kolom audit lengkap.
2. [`database/migrations/2026_09_14_100002_create_rkbmd_submissions_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_14_100002_create_rkbmd_submissions_table.php): Tabel tiket header permohonan RKBMD.
3. [`database/migrations/2026_09_14_100003_create_rkbmd_items_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_14_100003_create_rkbmd_items_table.php): Tabel rincian item barang yang dimohonkan.
4. [`database/migrations/2026_09_14_100004_create_rkbmd_histories_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_14_100004_create_rkbmd_histories_table.php): Tabel riwayat pengalihan dan audit trail permohonan.
5. [`database/seeders/MasterBarangSeeder.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/MasterBarangSeeder.php): Seeder awal sampel kodefikasi barang RSUD.

### Models & Traits
1. [`app/Traits/TracksUserAudit.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/TracksUserAudit.php): Otomatisasi pengisian `created_by`, `updated_by`, dan `deleted_by`.
2. [`app/Traits/LogsActivity.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php): Penambahan deskripsi aktivitas kontekstual untuk modul RKBMD dan Master Barang.
3. [`app/Models/MasterBarang.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/MasterBarang.php): Model katalog barang BMD.
4. [`app/Models/RkbmdSubmission.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php): Model tiket permohonan RKBMD beserta relasi dan method `canBeManagedBy()`.
5. [`app/Models/RkbmdItem.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdItem.php): Model item rincian barang.
6. [`app/Models/RkbmdHistory.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdHistory.php): Model riwayat histori pengalihan.

### Controllers & Routes
1. [`app/Http/Controllers/Operator/RkbmdController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php): Controller alur permohonan RKBMD operator (`index`, `create`, `store`, `show`, `reply`, `forward`).
2. [`app/Http/Controllers/Admin/MasterBarangController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php): Controller manajemen katalog master barang administrator.
3. [`routes/web.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php): Pendaftaran route `operator.rkbmd.*` dan `admin.master-barangs.*`.

### Views & Navigation
1. [`resources/views/operator/rkbmd/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php): Halaman utama tab *Permohonan Saya* vs *Permohonan Masuk*.
2. [`resources/views/operator/rkbmd/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php): Formulir permohonan multi-item dengan Alpine.js dan sub-unit terkunci.
3. [`resources/views/operator/rkbmd/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php): Halaman detail permohonan, preview PDF, visual timeline riwayat, modal balasan keputusan, dan modal pengalihan.
4. [`resources/views/admin/master_barangs/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php): Panel DataTables master barang admin.
5. [`resources/views/admin/master_barangs/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/create.blade.php): Form tambah master barang.
6. [`resources/views/admin/master_barangs/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/edit.blade.php): Form edit master barang.
7. [`resources/views/layouts/navigation.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php): Menu **📦 RKBMD** pada navigasi Operator dan **📦 Master Barang BMD** pada dropdown Master Data Admin.

---

## 3. Hasil Pengujian Otomatis (Testing Verification)

Telah dibuat test suite khusus: [`tests/Feature/Operator/RkbmdTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdTest.php) dan dieksekusi bersama seluruh test suite aplikasi:

```bash
php artisan test
```

Hasil Eksekusi:
```text
   PASS  Tests\Feature\Operator\RkbmdTest
  ✓ operator can access rkbmd index and create page                                                              0.13s  
  ✓ operator can submit rkbmd with items and pdf memo                                                            0.07s  
  ✓ audit columns and activity logs are automatically populated                                                  0.07s  
  ✓ target operator can reply with status and free text                                                          0.07s  
  ✓ target operator can forward rkbmd to another proposer and logs history                                       0.11s  
  ✓ viewer operator cannot reply or forward rkbmd                                                                0.09s  
  ✓ admin can manage master barang permendagri 108                                                               0.06s  

  ... seluruh test suite lainnya ...

  Tests:    224 passed (1108 assertions)
  Duration: 73.23s
```

Seluruh **224 skenario pengujian berstatus PASS (100% Hijau)** tanpa regresi.
