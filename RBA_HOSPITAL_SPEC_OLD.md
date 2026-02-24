# SYSTEM REQUIREMENTS SPECIFICATION (SRS) - RBA HOSPITAL
**Project Name:** Aplikasi RBA (Rancangan Belanja & Anggaran) Rumah Sakit
**Target Framework:** Laravel 11.x
**Focus:** Budgeting Workflow, Audit Trail, & Versioned Attachments

---

## 1. USER ROLES & ACCESS CONTROL
| Role | Responsibility |
| :--- | :--- |
| **Administrator** | Master Data management (Users, Accounts, Periods), Setting Budget Caps (Pagu). |
| **Supervisor** | Head of Department/Unit. Verifies (Approves/Rejects) Operator's submissions. |
| **Operator** | Unit member. Inputs budget items, amounts, and uploads PDF details. |

---

## 2. MASTER DATA (ADMIN ONLY)
- **Account Codes (Rekening Belanja):** 
    - **Table Name:** `account_codes`
    - **Fields:** 
        - `id`: Primary Key
        - `code`: String (e.g., "5.1.02.x")
        - `name`: String (e.g., "Belanja Bahan Medis")
        - `group`: String (e.g., "Belanja Barang & Jasa")
    - **Role:** Digunakan sebagai referensi utama untuk pengelompokan item belanja dan penetapan Pagu oleh Administrator.
- **RBA Periods:** Supports 6 specific types:
  1. Perencanaan Murni
  2. Penganggaran Murni
  3. Pergeseran Murni
  4. Perencanaan Perubahan
  5. Penganggaran Perubahan
  6. Pergeseran Perubahan
- **User Management:** Linked to Unit ID and Role.

---

## 3. WORKFLOW & STATE MACHINE
1. **DRAFT (Operator):** Operator creates RBA. Can Add/Edit/Delete budget items.
2. **PENDING_SUPERVISOR:** Submitted for approval. Operator locked from editing.
3. **REJECTED:** Returned by Supervisor. Operator can edit again.
4. **PENDING_ADMIN:** Approved by Supervisor, waiting for Budget Cap (Pagu).
5. **PAGU_SET (Admin):** Admin inputs `nominal_pagu`.
   - **Constraint:** System locks `nominal_request` (Read-only).
   - **Requirement:** Operator MUST upload a new PDF revision matching the `nominal_pagu`.
6. **FINALIZED:** After the new PDF is uploaded, the RBA cycle for that period is complete.

---

## 4. DATABASE ARCHITECTURE (LARAVEL ELOQUENT)

### Core Tables:
- `rba_headers`: `id, unit_id, period_id, status, total_request, total_pagu`
- `rba_details`: `id, rba_header_id, account_id, description, nominal_request, nominal_pagu`
- `rba_attachments`: `id, rba_detail_id, file_path, version_number, uploaded_by, created_at`

### Audit Trail Logic:
- **No Delete Policy:** For `rba_attachments`, files are never deleted. Use `version_number` to track history.
- **Pagu Lock:** Implementation of Laravel Policies/Gates to prevent `nominal_request` updates once `status == PAGU_SET`.

---

## 5. UI/UX GUIDELINES
- **Dashboard:** Show comparison between Requested vs Approved (Pagu).
- **History View:** A timeline/table showing all uploaded PDF versions for each budget item.
- **Validation:** Visual indicator if the latest PDF total doesn't match the Admin's Pagu.