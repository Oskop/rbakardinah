# Implementation Plan - Penetapan Pagu Per Nomor Rekening & Restriksi Pengusulan Operator

Menyesuaikan proses bisnis penetapan pagu pada peran **Administrator** dari penetapan sekaligus menjadi **per nomor rekening**, di mana setiap nomor rekening memiliki status penetapan dan tombol simpan tersendiri. Penetapan pagu (termasuk yang bernilai Rp 0) akan langsung mengunci (*lock*) hak pengusulan rincian belanja Operator untuk nomor rekening terkait.

---

## User Review Required

> [!IMPORTANT]
> **Perubahan Alur Bisnis Pagu:**
> 1. Administrator kini menetapkan pagu secara individual per nomor rekening (tersedia tombol **Simpan** di setiap baris rekening).
> 2. Meskipun nominal pagu diinput **0 (nol)**, jika Administrator menekan tombol Simpan, rekening tersebut **resmi berstatus Sudah Ditetapkan**.
> 3. Rekening yang sudah berstatus "Sudah Ditetapkan" akan **langsung terkunci bagi Operator** (tidak bisa menambah usulan baru `create`, tidak bisa `edit`, dan tidak bisa `delete`).

---

## Proposed Changes

### 1. Backend Controller & Routing (Admin Pagu)

#### [MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)
- Memperbarui method `store(Request $request, RbaHeader $header)`:
  - Menerima `account_code_id` dan `nominal_pagu` untuk menyimpan/memperbarui pagu per rekening spesifik.
  - Mendukung input nominal `0` sebagai pagu yang sah dan berstatus ditetapkan.
- Menambahkan method `destroy(Request $request, RbaHeader $header, AccountCode $accountCode)` (opsional):
  - Memungkinkan Administrator membatalkan (*reset/un-set*) status penetapan pagu sebuah rekening jika diperlukan.

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Mendaftarkan route untuk pembatalan penetapan pagu per rekening:
  `DELETE admin/headers/{header}/pagu/{accountCode}` -> `RbaAccountPaguController@destroy`

---

### 2. Antarmuka Administrator (Admin Views)

#### [MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- Memperbarui tata letak tabel penetapan pagu:
  - **Kolom Rekening**: Kode & Nama Rekening, Kelompok Belanja.
  - **Kolom Total Usulan**: Akumulasi usulan Operator untuk rekening tersebut.
  - **Kolom Status Penetapan**:
    - `✅ Sudah Ditetapkan` (Badge Hijau) jika terdapat data pagu.
    - `⏳ Belum Ditetapkan` (Badge Abu-abu) jika belum ditetapkan.
  - **Kolom Input Pagu**: Field input angka `nominal_pagu` per baris.
  - **Kolom Aksi**:
    - Tombol **Simpan** per baris rekening.
    - Tombol **Batal Penetapan** (jika rekening sudah ditetapkan dan ingin dibuka kembali).

---

### 3. Logika Restriksi Operator & Policy

#### [MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)
- Memperbarui helper method `isPaguIssued(int $headerId, int $accountCodeId)`:
  - Menghapus syarat `nominal_pagu > 0`, sehingga keberadaan record di `rba_account_pagus` (termasuk `nominal_pagu = 0`) otomatis menandakan pagu telah ditetapkan.

#### [MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Memperbarui method `create()` dan `edit()`:
  - Mengambil daftar `$lockedAccountIds` dari `RbaAccountPagu` tanpa filter `nominal_pagu > 0`.
  - Rekening yang telah ditetapkan pagunya tidak akan muncul pada dropdown pilihan tambah rincian baru.

#### [MODIFY] [RbaDetail.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDetail.php)
- Memperbarui method `isExceedingPagu()`:
  - Jika pagu ada dan bernilai 0 sementara usulan > 0, status usulan tetap terdeteksi melebihi pagu (*exceeding pagu*).

---

### 4. Antarmuka Operator & Supervisor (Views)

#### [MODIFY] [operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Memperbarui status kolom Pagu Global dan penguncian tombol Aksi (Edit, Hapus, Ajukan):
  - Menggunakan pengecekan keberadaan record pagu `isset($pagus[$detail->account_code_id])`.
  - Jika pagu bernilai 0 dan sudah ditetapkan, menampilkan `Rp 0` dan mengunci usulan rincian belanja.

#### [MODIFY] [supervisor/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Menampilkan `Rp 0` untuk pagu yang bernilai 0 yang sudah ditetapkan, serta status `⚠️ OVER` jika usulan > 0.

---

### 5. Automated Tests

#### [MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)
- Menguji penetapan pagu per nomor rekening.
- Menguji penetapan pagu dengan nominal Rp 0 dan memastikan statusnya tercatat sebagai "Sudah Ditetapkan".
- Menguji penguncian (*restriction*) Operator saat pagu nominal 0 sudah ditetapkan.
- Menguji pembatalan penetapan pagu (*destroy/reset*).

---

## Verification Plan

### Automated Tests
- Jalankan test suite penetapan pagu:
  `php artisan test --filter=PaguTest`
- Jalankan test suite rincian belanja Operator:
  `php artisan test --filter=RbaDetailTest`
  `php artisan test --filter=RbaDetailFeaturesTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Administrator** dan akses halaman **Set Pagu Global** (`/admin/headers/{header}/pagu`).
2. Masukkan nominal pada salah satu rekening (misal Rekening A = `10.000.000` dan Rekening B = `0`), lalu klik tombol **Simpan** pada baris masing-masing rekening.
3. Pastikan status baris Rekening A dan B berubah menjadi `✅ Sudah Ditetapkan`.
4. Login sebagai **Operator**, buka form **Tambah Rincian Belanja** (`/operator/details/create`).
5. Pastikan Rekening A dan B **tidak muncul** dalam dropdown pilihan rekening karena telah ditetapkan pagunya.
6. Pada halaman detail usulan Operator, pastikan rincian belanja di bawah Rekening A dan B terkunci dari tindakan Edit/Hapus.
