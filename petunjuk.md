System Requirements Specification (SRS) - RBA Hospital Application
1. Project Overview
Aplikasi Perencanaan Anggaran (RBA) untuk Rumah Sakit Pemerintah. Fokus utama adalah alur pengajuan belanja dari unit (Operator) ke jenjang verifikasi (Supervisor) hingga penetapan pagu (Administrator).

2. Technical Stack (Target)
Framework: Laravel 11.x

Database: PostgreSQL / MySQL

Auth: Laravel Breeze/Filament (Role-based Access Control)

File Storage: Laravel Storage (S3 or Local) with versioning logic.

3. Database Schema (Laravel Migrations Logic)
Master Tables
users: id, name, email, password, role_id, unit_id

roles: id, name (admin, supervisor, operator)

units: id, name (Instalasi Farmasi, Gizi, dsb)

rba_periods:

id, name, year, status (open/closed)

Note: Berisi 6 tipe: Perencanaan Murni, Penganggaran Murni, Pergeseran Murni, dll.

account_codes (Rekening Belanja):

id, code, name, group_name

Transactional Tables
rba_headers:

id, unit_id, rba_period_id, status

status_list: draft, submitted_to_supervisor, submitted_to_admin, pagu_set, finalized.

rba_details:

id, rba_header_id, account_code_id, description, nominal_request, nominal_pagu (nullable)

rba_attachments:

id, rba_detail_id, file_path, version_number, uploaded_by, created_at

Constraint: No Delete, Only Insert (Audit Trail).

4. System Workflow & State Machine
A. Role: Administrator
Setup Data Master: Menginput account_codes dan membuka rba_periods.

Final Review: Menerima RBA yang sudah disetujui Supervisor.

Action "Set Pagu":

Input nominal_pagu pada rba_details.

Mengubah status rba_header menjadi pagu_set.

Trigger: Mengunci field nominal_request (Read-only for Operator).

B. Role: Supervisor
Verification: Review daftar RBA dari unit di bawahnya.

Approve: Mengirim ke Administrator.

Reject: Mengembalikan ke Operator (status kembali ke draft) dengan catatan revisi.

C. Role: Operator
Initial Input: Membuat RBA, memilih rekening, mengisi nominal, dan upload PDF pertama.

Post-Pagu Revision:

Setelah status pagu_set, Operator tidak bisa ubah nominal.

Wajib upload PDF baru sesuai nominal_pagu.

Sistem menyimpan PDF baru sebagai version_number berikutnya.

5. Laravel Logic Implementation (Pseudo-Code)
Policy & Middleware
PHP
// Constraint: Operator cannot edit nominal if Pagu is set
public function update(User $user, RbaDetail $detail) {
    return $detail->header->status !== 'pagu_set';
}
Attachment Logic (Anti-Delete)
PHP
// Controller logic for file upload
public function storeAttachment(Request $request, $detailId) {
    $latestVersion = RbaAttachment::where('rba_detail_id', $detailId)->max('version_number');
    
    RbaAttachment::create([
        'rba_detail_id' => $detailId,
        'file_path' => $request->file('pdf')->store('rba_docs'),
        'version_number' => $latestVersion + 1,
        'uploaded_by' => auth()->id()
    ]);
}
6. Security Requirements
Audit Trail: Setiap perubahan status RBA harus dicatat di tabel activity_log.

File Protection: File PDF rincian belanja tidak boleh diakses publik (Gunakan Storage::temporaryUrl atau Response Stream).

Validation: nominal_pagu tidak boleh lebih besar dari nominal_request (opsional, tergantung kebijakan RS).

7. UI/UX Expectation
Dashboard: Menampilkan grafik sisa kuota anggaran per unit.

Timeline View: Menampilkan riwayat PDF rincian belanja (dari versi 1 ke versi terbaru).

Status Badge: Warna berbeda untuk tiap status RBA (Kuning: Pending, Hijau: Approved, Biru: Pagu Set).