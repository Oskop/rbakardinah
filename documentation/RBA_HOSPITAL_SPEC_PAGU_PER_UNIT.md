# SYSTEM REQUIREMENTS SPECIFICATION (SRS) - RBA HOSPITAL
**Project:** Aplikasi Rancangan Belanja & Anggaran (RBA) Rumah Sakit Pemerintah
**Target Framework:** Laravel 11.x
**Database:** MySQL/PostgreSQL
**Architecture:** Multi-User Workflow with Consolidated Global Header

---

## 1. USER ROLES & PERMISSIONS
| Role | Responsibility |
| :--- | :--- |
| **Administrator** | Mengelola Master Data, menginisiasi Header RBA Global, dan menetapkan Pagu per Nomor Rekening. |
| **Supervisor** | Kepala Unit/Instalasi. Memverifikasi pengajuan dari seluruh Operator di bawah unitnya sebelum diteruskan ke Admin. |
| **Operator** | Anggota Unit. Menginput item rencana belanja, nominal usulan, dan mengunggah dokumen PDF rincian. |

---

## 2. MASTER DATA STRUCTURE

### A. RBA Periods (`rba_periods`)
Sistem wajib mendukung 6 tahapan periode:
1. Perencanaan Murni
2. Penganggaran Murni
3. Pergeseran Murni
4. Perencanaan Perubahan
5. Penganggaran Perubahan
6. Pergeseran Perubahan

### B. Account Codes (`account_codes`)
Master data rekening belanja (COA) yang mencakup:
- `code`: Kode rekening (misal: 5.1.02.x)
- `name`: Nama rekening (misal: Belanja Bahan Medis)
- `group`: Kelompok belanja

### C. Users & Units
- `users`: `id, name, role_id, unit_id`
- `units`: `id, name (e.g., Farmasi, IGD, Gizi, Penunjang)`

---

## 3. CORE DATABASE ARCHITECTURE

### Global Container
- **`rba_headers`**: `id, period_id, admin_id, status_global`
  - *Note:* Dibuat hanya oleh Admin. `admin_id` mencatat siapa pembuat header global ini.

### Unit Batching
- **`rba_submissions`**: `id, rba_header_id, unit_id, status_submission, supervisor_note`
  - *Status:* `Draft`, `Pending Supervisor`, `Validated`, `Pagu Issued`.
  - *Logic:* Menampung semua usulan dari satu unit dalam satu periode.

### Transactional Details
- **`rba_details`**: `id, rba_submission_id, account_code_id, description, nominal_request, created_by`
  - *Note:* `nominal_request` adalah usulan asli dari Operator.

### Pagu & Attachments (Account Level)
- **`rba_account_pagu`**: `id, rba_submission_id, account_code_id, nominal_pagu`
  - *Logic:* Pagu ditetapkan oleh Admin per Nomor Rekening per Unit.
- **`rba_attachments`**: `id, rba_submission_id, account_code_id, file_path, version_number, uploaded_by`
  - *Logic:* PDF dikelola per Nomor Rekening. Riwayat (versioning) wajib tersimpan (Anti-Delete).

---

## 4. WORKFLOW & LOGIC RULES

### Tahap 1: Pengisian (Operator)
- Beberapa Operator dalam satu unit dapat menginput banyak item ke tabel `rba_details` menggunakan Nomor Rekening yang sama atau berbeda.
- Operator mengunggah PDF Rincian Belanja versi awal (V1) per nomor rekening.
- Setelah selesai, Operator melakukan "Submit to Supervisor".

### Tahap 2: Verifikasi (Supervisor)
- Supervisor meninjau seluruh item yang diajukan oleh Operator di unitnya.
- **Action Reject:** Data kembali ke status `Draft` untuk diperbaiki Operator.
- **Action Approve:** Status berubah menjadi `Validated`. Data baru bisa dilihat dan diproses oleh Admin.

### Tahap 3: Penetapan Pagu (Admin)
- Admin menginput `nominal_pagu` pada tabel `rba_account_pagu` untuk tiap nomor rekening yang diajukan unit.
- **Locking Mechanism:** Begitu Pagu disimpan, field `nominal_request` pada `rba_details` di nomor rekening terkait otomatis **Read-Only** (Terkunci).

### Tahap 4: Penyesuaian Akhir (Operator)
- Operator wajib mengunggah PDF Rincian Belanja baru (V2, dst) yang total nominalnya harus sesuai dengan `nominal_pagu` dari Admin.
- Sistem menyimpan semua versi PDF sebagai audit trail.

---

## 5. UI/UX GUIDELINES

### Administrator Dashboard
- Tabel Global: Daftar RBA per periode.
- Detail View: Rekapitulasi usulan per unit yang berstatus `Validated`.
- Pagu Editor: Form input nominal pagu per nomor rekening.

### Supervisor Dashboard
- Inbox Verifikasi: Daftar pengajuan dari unit sendiri.
- Review Panel: List item belanja yang diinput oleh berbagai Operator untuk divalidasi secara kolektif.

### Operator Dashboard
- Unit Workboard: Tabel input item belanja.
- Comparison Tool: Tampilan perbandingan `Total Usulan` vs `Pagu Admin` per nomor rekening.
- Upload Center: Manajemen file PDF dengan tampilan riwayat versi (timeline).

---

## 6. TECHNICAL CONSTRAINTS (FOR LARAVEL)
1. **No Delete Policy:** File pada `rba_attachments` tidak boleh dihapus dari storage maupun database.
2. **Access Control:** Gunakan Laravel Policies untuk memastikan Operator hanya bisa melihat data milik `unit_id` mereka sendiri.
3. **Validation:** System harus memvalidasi bahwa total akumulasi nominal pada PDF terbaru sama dengan `nominal_pagu`.