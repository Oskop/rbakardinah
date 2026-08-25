# Implementation Plan - Reset Status Usulan Menjadi Draft Saat Operator Melakukan Edit

Memastikan bahwa setiap kali Operator melakukan perubahan/edit pada usulan rincian belanja (`RbaDetail`) yang sebelumnya telah divalidasi oleh Supervisor, sistem secara otomatis **mereset status usulan tersebut kembali menjadi Draft** (`is_validated = false`, `is_submitted = false`, `validated_at = null`, `validated_by = null`). Dengan demikian, Supervisor diwajibkan memvalidasi ulang usulan *pasca-edit*, dan Administrator dicegah menetapkan pagu sebelum validasi ulang tersebut selesai.

---

## User Review Required

> [!IMPORTANT]
> **Alur Bisnis Pasca-Edit Usulan:**
> 1. Operator dapat mengedit usulan rincian belanja selama nomor rekening tersebut **belum ditetapkan pagunya** oleh Administrator (`!$isItemLockedByPagu`).
> 2. Ketika Operator menyimpan hasil edit pada rincian belanja (yang sebelumnya telah divalidasi), status usulan **otomatis kembali menjadi Draft**.
> 3. Operator wajib mengajukan kembali usulan tersebut (klik tombol **Ajukan**), dan Supervisor dari unit terkait wajib memvalidasi ulang rincian belanja hasil edit tersebut.
> 4. Selama belum divalidasi ulang oleh Supervisor, Administrator tidak dapat menyimpan/menetapkan pagu untuk nomor rekening tersebut.

---

## Proposed Changes

### 1. Kebijakan Otorisasi (`app/Policies/RbaDetailPolicy.php`)

#### [MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)
- Memperbarui method `update(User $user, RbaDetail $rbaDetail)`:
  - Memastikan Operator pemilik usulan dapat mengedit rincian belanja selama pagu untuk nomor rekening tersebut belum ditetapkan (`!$this->isPaguIssued()`).
  - Menghapus pembatasan yang mengunci pengeditan saat `is_submitted = true`, karena proses update akan mereset status kembali ke Draft.

---

### 2. Operator Detail Controller (`app/Http/Controllers/Operator/DetailController.php`)

#### [MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- **Method `update(Request $request, RbaDetail $detail)`**:
  - Saat menyimpan perubahan rincian belanja, sertakan reset atribut validasi dan pengajuan:
    ```php
    $validated['is_validated'] = false;
    $validated['validated_at'] = null;
    $validated['validated_by'] = null;
    $validated['is_submitted'] = false;
    $validated['is_rejected'] = false;
    $validated['rejected_at'] = null;
    $validated['rejected_by'] = null;
    $validated['rejection_reason'] = null;
    ```
  - Perbarui pesan sukses: `"RBA Detail berhasil diperbarui dan status kembali menjadi Draft (perlu diajukan dan divalidasi ulang oleh Supervisor)."`.
- **Method `uploadVersion(Request $request, RbaDetail $detail)`**:
  - Jika pagu belum ditetapkan (`!$this->isPaguIssued(...)`), unggah revisi PDF baru juga mereset `is_validated = false` agar Supervisor memvalidasi dokumen versi terbaru.

---

### 3. Antarmuka Operator (`resources/views/operator/submissions/show.blade.php`)

#### [MODIFY] [operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Memastikan tombol aksi **Edit**, **Ajukan**, dan **Hapus** ditampilkan untuk rincian belanja selama rekening belum ditetapkan pagunya (`!$isItemLockedByPagu`).
- Menampilkan badge status **Draft** setelah usulan berhasil diedit.

---

### 4. Pengujian Otomatis (`tests/Feature/Operator/RbaDetailTest.php` & `tests/Feature/Admin/PaguTest.php`)

#### [MODIFY] [RbaDetailTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailTest.php)
- Menambahkan pengujian:
  - `test_editing_validated_detail_resets_status_to_draft`: Memastikan ketika rincian yang berstatus `is_validated = true` diedit oleh Operator, atribut `is_validated`, `is_submitted`, `validated_at`, dan `validated_by` ter-reset menjadi status Draft.

#### [MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)
- Menambahkan pengujian:
  - `test_admin_cannot_set_pagu_after_operator_edits_validated_detail_until_revalidated`: Memastikan Admin dicegah menetapkan pagu setelah Operator mengedit usulan, hingga Supervisor memvalidasi ulang usulan tersebut.

---

## Verification Plan

### Automated Tests
- Jalankan test suite Operator Detail:
  `php artisan test --filter=RbaDetailTest`
- Jalankan test suite Pagu Admin:
  `php artisan test --filter=PaguTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Supervisor**, lakukan validasi (`is_validated = true`) pada salah satu rincian usulan Operator.
2. Login sebagai **Operator**, buka halaman detail usulan dan klik **Edit** pada rincian yang telah divalidasi tersebut. Ubah volume atau harga satuan, lalu simpan.
3. Pastikan status rincian tersebut kembali menjadi badge **Draft** (`is_validated = false`).
4. Login sebagai **Administrator**, coba simpan pagu pada rekening tersebut.
   - **Hasil yang diharapkan:** Admin ditolak dengan peringatan bahwa usulan tersebut belum divalidasi oleh Supervisor.
5. Login kembali sebagai **Operator**, klik **Ajukan**.
6. Login sebagai **Supervisor**, validasi kembali rincian usulan hasil edit tersebut.
7. Login sebagai **Administrator**, simpan pagu pada rekening tersebut.
   - **Hasil yang diharapkan:** Pagu berhasil disimpan.
