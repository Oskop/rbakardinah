# SYSTEM REQUIREMENTS SPECIFICATION (SRS) - RBA HOSPITAL (FINAL)
**Project Name:** Aplikasi Rancangan Belanja & Anggaran (RBA) Rumah Sakit Pemerintah
**Target Framework:** Laravel 11.x
**Database:** MySQL / PostgreSQL
**Core Logic:** Global Budget Ceiling (Pagu Global) with Unit-based Submissions.

---

## 1. USER ROLES & PERMISSIONS
| Role | Responsibility |
| :--- | :--- |
| **Administrator** | Inisiasi Header RBA, Kelola Master Data, Menetapkan Pagu Global per Nomor Rekening. |
| **Supervisor** | Kepala Unit. Memverifikasi/Validasi usulan dari seluruh Operator di unitnya (Gatekeeper). |
| **Operator** | Anggota Unit. Menginput rincian belanja, nominal usulan, dan mengunggah PDF rincian belanja. |

---

## 2. MASTER DATA STRUCTURE

### A. RBA Periods (`rba_periods`)
Sistem mendukung 6 fase periode RBA:
1. Perencanaan Murni 
2. Penganggaran Murni
3. Pergeseran Murni
4. Perencanaan Perubahan
5. Penganggaran Perubahan
6. Pergeseran Perubahan

### B. Account Codes (`account_codes`)
Daftar Rekening Belanja (COA):
- `code`: Kode rekening (Contoh: 5.1.02.01.0001)
- `name`: Nama rekening (Contoh: Belanja Alat Tulis Kantor)
- `group`: Kelompok belanja (Barang & Jasa, Modal, dsb)

---

## 3. CORE DATABASE ARCHITECTURE

### Global Container (Admin)
- **`rba_headers`**: `id, period_id, admin_id (account_id), status_global`
  - *Note:* Kontainer utama per tahun/periode. Hanya dibuat oleh Admin.

### Unit Submissions (Supervisor)
- **`rba_submissions`**: `id, rba_header_id, unit_id, status_submission, supervisor_note`
  - *Status:* `Draft`, `Pending Supervisor`, `Validated`, `Pagu Issued`.
  - *Logic:* Mengelompokkan seluruh usulan dari satu unit.

### Global Budget Ceiling (Admin)
- **`rba_account_pagu`**: `id, rba_header_id, account_code_id, nominal_pagu`
  - *Logic:* Pagu ditetapkan secara **GLOBAL** untuk satu nomor rekening. 
  - *Constraint:* `UNIQUE(rba_header_id, account_code_id)`. Pagu ini mencakup total usulan dari seluruh unit di RS untuk rekening tersebut.

### Transactional Details (Operator)
- **`rba_details`**: `id, rba_submission_id, account_code_id, description, nominal_request, created_by`
  - *Note:* Berisi rincian pekerjaan/belanja. Banyak operator bisa mengisi satu nomor rekening yang sama.

### Audit Trail (Attachments)
- **`rba_attachments`**: `id, rba_submission_id, account_code_id, file_path, version_number, uploaded_by`
  - *Logic:* PDF rincian dikelola per Nomor Rekening di tiap Unit.
  - *No-Delete:* File tidak boleh dihapus, hanya versi (`version_number`) yang bertambah.

---

## 4. WORKFLOW & LOGIC RULES

### Tahap 1: Pengisian Unit (Operator & Supervisor)
1. Operator di berbagai unit menginput item belanja ke `rba_details`.
2. Operator mengunggah PDF Rincian Belanja awal (Versi 1) per Nomor Rekening.
3. Supervisor meninjau seluruh item unitnya. Jika OK, Supervisor melakukan **"Validasi"**. Data yang sudah divalidasi akan muncul di dashboard Admin.

### Tahap 2: Penetapan Pagu Global (Admin)
1. Admin melihat akumulasi total usulan dari **seluruh unit** untuk tiap Nomor Rekening.
2. Admin menginput **Nominal Pagu Global** pada rekening tersebut.
3. **Locking Mechanism:** Begitu Pagu Global disimpan, seluruh `rba_details` di seluruh unit yang menggunakan nomor rekening tersebut akan **terkunci (Read-Only)** untuk kolom `nominal_request`.

### Tahap 3: Penyesuaian Akhir (Operator)
1. Unit melihat Pagu Global yang tersedia.
2. Operator wajib mengunggah **PDF Rincian Belanja Baru** (Versi 2) yang totalnya disesuaikan agar sesuai dengan Pagu Global yang ditetapkan Admin.
3. Sistem memvalidasi agar total nominal di PDF baru selaras dengan Pagu.

---

## 5. UI/UX GUIDELINES

### Dashboard Admin
- **Global Recaps:** Tabel akumulasi usulan dari semua unit per nomor rekening secara *real-time*.
- **Pagu Controller:** Input tunggal untuk menetapkan plafon anggaran per rekening belanja.

### Dashboard Supervisor
- **Unit Verification:** Panel untuk menyetujui atau mengembalikan (Reject) seluruh usulan satu unit.

### Dashboard Operator
- **Entry Form:** Input rincian pekerjaan dan pemilihan kode rekening.
- **Pagu Notification:** Indikator visual jika Pagu Global sudah ditetapkan, mengunci input nominal, dan instruksi unggah PDF revisi.
- **Document History:** List timeline untuk melihat PDF versi lama vs versi terbaru.

---

## 6. TECHNICAL CONSTRAINTS (LARAVEL)
1. **Security:** Implementasikan Laravel Policies. Operator hanya bisa `Update` jika `status_submission` masih `Draft`.
2. **Integrity:** Gunakan Database Transactions saat menyimpan Pagu Global agar konsisten di seluruh unit.
3. **File Storage:** Gunakan `Storage::disk('local')` atau `S3`. Link PDF tidak boleh publik, harus melalui Controller (Stream/Temporary URL).