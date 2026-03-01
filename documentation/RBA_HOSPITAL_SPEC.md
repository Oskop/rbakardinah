# SYSTEM REQUIREMENTS SPECIFICATION (SRS) - RBA HOSPITAL (FINAL REVISED)
**Project Name:** Aplikasi Rancangan Belanja & Anggaran (RBA) Rumah Sakit Pemerintah
**Target Framework:** Laravel 11.x
**Database:** MySQL / PostgreSQL
**Core Logic:** Global Budget Ceiling per Account & Item-based PDF Attachments.

---

## 1. USER ROLES & PERMISSIONS
| Role | Responsibility |
| :--- | :--- |
| **Administrator** | Inisiasi Header RBA, Kelola Master Data, Menetapkan Pagu Global per Nomor Rekening. |
| **Supervisor** | Kepala Unit. Verifikator tunggal usulan dari seluruh Operator di unitnya (Gatekeeper). |
| **Operator** | Anggota Unit. Menginput rincian belanja, nominal usulan, dan mengunggah PDF rincian belanja per item. |

---

## 2. MASTER DATA STRUCTURE
- **RBA Periods (`rba_periods`)**: Mendukung 6 fase yaitu:
  1. Perencanaan Murni 
  2. Penganggaran Murni
  3. Pergeseran Murni
  4. Perencanaan Perubahan
  5. Penganggaran Perubahan
  6. Pergeseran Perubahan
- **Account Codes (`account_codes`)**: Master Kode Rekening (COA) sebagai dasar penetapan Pagu.
- **Users & Units**: Manajemen user yang terikat pada unit kerja dan role.

---

## 3. CORE DATABASE ARCHITECTURE

### Global Container (Admin)
- **`rba_headers`**: `id, period_id, admin_id (account_id), year, status_global`
  - *Note:* Kontainer tahunan/periode yang dibuat oleh Admin.

### Unit Submissions (Supervisor)
- **`rba_submissions`**: `id, rba_header_id, unit_id, status_submission, supervisor_note`
  - *Status:* `Draft`, `Pending Supervisor`, `Validated`, `Pagu Issued`.

### Global Budget Ceiling (Admin)
- **`rba_account_pagu`**: `id, rba_header_id, account_code_id, nominal_pagu`
  - *Constraint:* `UNIQUE(rba_header_id, account_code_id)`. Plafon anggaran global untuk satu rekening.

### Transactional Details (Operator)
- **`rba_details`**: `id, rba_submission_id, account_code_id, description, nominal_request, created_by`
  - *Note:* Satu nomor rekening bisa digunakan oleh banyak baris detail dari berbagai unit/operator.

### Audit Trail (Attachments)
- **`rba_attachments`**: `id, rba_detail_id, file_path, version_number, uploaded_by`
  - *Logic:* **1 Detail = 1 PDF**. Jika ada revisi setelah Pagu turun, operator mengunggah versi baru untuk detail tersebut.
  - *No-Delete:* File lama tetap tersimpan sebagai riwayat (History).

---

## 4. WORKFLOW & LOGIC RULES

### Tahap 1: Input Data (Operator)
- Operator menambahkan item belanja di `rba_details`. 
- **Wajib:** Setiap satu baris item yang diinput, Operator harus mengunggah 1 file PDF rincian.
- Operator mengajukan usulan ke Supervisor Unit.

### Tahap 2: Verifikasi Unit (Supervisor)
- Supervisor meninjau setiap item dan PDF-nya.
- Jika divalidasi, pengajuan unit masuk ke dashboard Administrator.

### Tahap 3: Penetapan Pagu Global (Admin)
- Admin melihat total akumulasi usulan dari seluruh RS per nomor rekening.
- Admin menetapkan `nominal_pagu` global untuk nomor rekening tersebut.
- **Locking:** Begitu Pagu disimpan, seluruh item (`rba_details`) yang memakai rekening tersebut di seluruh RS menjadi **Read-Only** untuk field nominal. Operator juga tidak dapat menambahkan item (`rba_details`) baru untuk nomor rekening tersebut pada RBA periode yang sama. Supervisor juga tidak dapat mengubah status `rba_submissions` menjadi `Validated` untuk RBA periode yang sama.

### Tahap 4: Penyesuaian & Riwayat (Operator)
- Operator melihat Pagu Global. Jika total usulan melebihi Pagu, Operator harus menyesuaikan rincian.
- Operator mengunggah **PDF baru (Versi 2)** pada item `rba_details` yang sama sebagai bukti penyesuaian nominal sesuai Pagu.
- Sistem menyimpan semua versi PDF sehingga Riwayat (V1, V2, dst) bisa dilihat kembali.

---

## 5. UI/UX GUIDELINES
- **Admin Dashboard:** Rekapitulasi per rekening untuk input Pagu Global.
- **Supervisor Review:** Tabel detail per item belanja lengkap dengan link download PDF dari Operator.
- **Operator Workboard:** - List item belanja dengan indikator status Pagu.
  - Kolom khusus "Riwayat PDF" untuk setiap baris item belanja.
  - Form upload yang otomatis menambah `version_number` jika file diunggah ulang.

---

## 6. TECHNICAL CONSTRAINTS (LARAVEL)
1. **File Versioning:** Gunakan penamaan file unik (timestamp/UUID) agar versi lama tidak tertimpa.
2. **Read-Only State:** Gunakan *Eloquent Observer* atau *Policy* untuk mencegah perubahan `nominal_request`, mencegah request `POST` ke `rba_details` dan `PATCH/PUT` ke status submission unit, jika `rba_account_pagu` pada periode dan tahun tersebut sudah terisi.
3. **Validation:** Total nominal di semua PDF terbaru dalam satu nomor rekening idealnya tidak melebihi Pagu Global.