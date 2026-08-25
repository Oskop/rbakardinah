# Implementation Plan - Validasi Supervisor & Informasi Lengkap Pengusul Sebelum Penetapan Pagu Rekening

Menambahkan aturan validasi prasyarat serta **pesan penolakan informatif yang merinci nama Operator pengusul dan nama Supervisor yang berwenang memvalidasi** pada fitur **Penetapan Pagu Per Nomor Rekening** oleh **Administrator**. Seluruh usulan rincian belanja dari Operator pada nomor rekening terkait wajib divalidasi terlebih dahulu oleh Supervisor masing-masing (`is_validated = true`) sebelum Administrator dapat menyimpan/menetapkan pagunya.

---

## User Review Required

> [!IMPORTANT]
> **Detail Informasi Penolakan Penetapan Pagu:**
> 1. Apabila Administrator mencoba menyimpan pagu pada rekening yang memiliki usulan belum divalidasi (`is_validated = false`), sistem **menolak penyimpanan** dan menampilkan pesan peringatan yang mencantumkan:
>    - **Deskripsi Usulan & Nominal** yang belum divalidasi.
>    - **Nama Operator** yang mengusulkan rincian belanja tersebut.
>    - **Unit Kerja** tempat usulan tersebut diajukan.
>    - **Nama Supervisor** dari unit kerja tersebut yang berkewajiban memvalidasi usulan.
> 2. Pada antarmuka Admin (`pagu.blade.php`), ditambahkan rincian langsung status operator dan supervisor penanggung jawab pada rekening yang masih berstatus pending validasi.

---

## Proposed Changes

### 1. Backend Controller (`app/Http/Controllers/Admin/RbaAccountPaguController.php`)

#### [MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)
- **Method `index()`**:
  - Mengambil data rincian belanja per rekening beserta relasi pengusul (`creator`), unit kerja (`submission.unit`), dan data supervisor dari unit kerja tersebut.
  - Meneruskan ringkasan statistik validasi dan daftar usulan pending ke view `admin.headers.pagu`.
- **Method `store()`**:
  - Memeriksa apakah terdapat usulan rincian belanja pada rekening tersebut yang belum divalidasi (`is_validated = false`).
  - Jika ditemukan usulan belum divalidasi:
    - Mengumpulkan daftar detail usulan: nama Operator pengusul, nama unit kerja, deskripsi usulan, dan nama Supervisor unit terkait.
    - Mengembalikan pesan error informatif:
      `"Pagu untuk rekening {kode} ({nama}) tidak dapat ditetapkan karena terdapat usulan rincian belanja yang belum divalidasi oleh Supervisor: • Usulan: '{deskripsi}' oleh Operator: {nama_operator} (Unit: {nama_unit}) - Wajib divalidasi oleh Supervisor: {nama_supervisor}."`
  - Jika seluruh usulan sudah divalidasi (atau tidak ada usulan):
    - Menyimpan data `RbaAccountPagu` dengan notifikasi sukses.

---

### 2. Antarmuka Administrator (`resources/views/admin/headers/pagu.blade.php`)

#### [MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- **Rendering Notifikasi Error**:
  - Memastikan alert box error dapat merender daftar detail usulan pending dengan format yang rapi dan mudah dibaca oleh Administrator.
- **Kolom Status Validasi Supervisor**:
  - Menampilkan badge status:
    - `Belum Ada Usulan` (Abu-abu) jika belum ada usulan rincian dari operator.
    - `✅ Divalidasi Supervisor (X/X)` (Hijau) jika seluruh usulan telah divalidasi.
    - `⚠️ X Usulan Menunggu Validasi` (Merah/Amber) jika masih ada usulan belum divalidasi.
  - Untuk rekening yang memiliki usulan belum divalidasi, disediakan daftar ringkas nama Operator & nama Supervisor penanggung jawab langsung pada baris tabel agar Admin dapat segera berkoordinasi.

---

### 3. Pengujian Otomatis (`tests/Feature/Admin/PaguTest.php`)

#### [MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)
- Menambahkan pengujian:
  1. `test_admin_cannot_set_pagu_if_operator_details_not_validated_by_supervisor`: Memverifikasi pesan error memuat nama Operator dan nama Supervisor yang bersangkutan.
  2. `test_admin_can_set_pagu_when_all_operator_details_are_validated`: Memverifikasi suksesnya penetapan pagu setelah Supervisor melakukan validasi.
  3. `test_admin_can_set_pagu_when_account_has_no_submissions`: Memverifikasi suksesnya penetapan pagu pada rekening tanpa usulan.

---

## Verification Plan

### Automated Tests
- Jalankan test suite Pagu:
  `php artisan test --filter=PaguTest`
- Jalankan test suite Review Supervisor:
  `php artisan test --filter=ReviewTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator A** (Unit Farmasi, Supervisor: **Budi**), buat usulan rincian belanja pada Rekening `5.1.02` (misal: "Pengadaan Obat A").
2. Login sebagai **Administrator**, coba simpan pagu pada Rekening `5.1.02`.
   - **Hasil yang diharapkan:** Admin ditolak dengan pesan informatif:
     `"Pagu untuk rekening 5.1.02 tidak dapat ditetapkan karena terdapat usulan rincian belanja yang belum divalidasi: • Usulan: 'Pengadaan Obat A' oleh Operator: Operator A (Unit: Farmasi) - Wajib divalidasi oleh Supervisor: Budi"`.
3. Login sebagai Supervisor **Budi**, validasi rincian usulan tersebut.
4. Login kembali sebagai **Administrator**, simpan pagu pada Rekening `5.1.02`.
   - **Hasil yang diharapkan:** Pagu berhasil disimpan.
