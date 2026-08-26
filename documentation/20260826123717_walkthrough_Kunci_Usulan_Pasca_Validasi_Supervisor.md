# Walkthrough - Penguncian Usulan Rincian Belanja Pasca Validasi Supervisor

Fitur **Penguncian Usulan Rincian Belanja Pasca Validasi Supervisor** telah selesai diimplementasikan dan terverifikasi secara menyeluruh. Usulan rincian belanja yang telah disetujui/divalidasi oleh Supervisor kini terkunci penuh sehingga Operator tidak dapat lagi melakukan pengeditan data, pengunggahan revisi lampiran PDF, maupun penghapusan.

---

## Ringkasan Perubahan

### 1. Otorisasi Policy (`app/Policies/RbaDetailPolicy.php`)
- **[MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)**
  - **Method `update()`**: Ditambahkan pengecekan `$rbaDetail->is_validated`. Jika `true`, permintaan ditolak (*HTTP 403 Forbidden*) dengan pesan: *"Usulan rincian belanja yang sudah divalidasi oleh Supervisor tidak dapat diedit."*.
  - **Method `uploadVersion()`**: Ditambahkan pengecekan `$rbaDetail->is_validated`. Jika `true`, permintaan ditolak (*HTTP 403 Forbidden*) dengan pesan: *"Usulan rincian belanja yang sudah divalidasi oleh Supervisor tidak dapat diunggah revisi PDF."*.
  - **Method `delete()`**: Menolak penghapusan (*HTTP 403 Forbidden*) untuk usulan yang sudah divalidasi.

---

### 2. Antarmuka Operator (`resources/views/operator/submissions/show.blade.php`)
- **[MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)**
  - Pada baris rincian belanja yang berstatus **Valid (`$detail->is_validated == true`)**:
    - Menampilkan status badge indikator:
      ```blade
      <div class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded justify-center">
          <span>🔒</span>
          <span>Tervalidasi (Terkunci)</span>
      </div>
      ```
    - Menyembunyikan tombol **Edit**, tombol **Hapus**, dan form **Upload Revisi PDF**.
  - Untuk usulan berstatus **Draft** atau **Ditolak**:
    - Tombol **Edit**, **Hapus**, **Ajukan**, dan form **Revisi PDF** tetap tersedia secara normal.

---

### 3. Pengujian Fitur (`tests/Feature/`)
- **[MODIFY] [RbaDetailTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailTest.php)**
  - `test_operator_cannot_edit_or_upload_revision_on_validated_detail`
  - `test_uploading_revision_pdf_on_rejected_detail_resets_status_to_draft`
- **[MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)**
  - `test_detail_disappears_from_supervisor_when_rejected_detail_is_edited_and_reappears_when_resubmitted`
- **[MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)**
  - `test_admin_cannot_set_pagu_when_operator_has_rejected_or_unvalidated_detail_until_validated`

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh 80 feature & unit tests pada aplikasi telah dijalankan dan **PASSED 100% (80 passed, 277 assertions)**:

```text
PASS  Tests\Feature\Admin\ActivityLogTest
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\KelompokBelanjaTest
PASS  Tests\Feature\Admin\PaguTest
✓ admin can set pagu per account code                                                                          1.05s  
✓ admin can set pagu zero and it is considered established                                                     0.03s  
✓ setting pagu zero locks operator from creating detail                                                        0.07s  
✓ admin cannot set pagu if operator details not validated by supervisor                                        0.04s  
✓ admin can set pagu when all operator details are validated                                                   0.04s  
✓ admin can cancel pagu for account code                                                                       0.04s  
✓ admin cannot set pagu when operator has rejected or unvalidated detail until validated                       0.05s  
✓ admin can save pagu via ajax without page reload                                                             0.04s  
✓ admin can delete pagu via ajax without page reload                                                           0.03s  
✓ ajax save pagu fails with 422 when unvalidated details exist                                                 0.04s  
PASS  Tests\Feature\Auth\AuthenticationTest
PASS  Tests\Feature\Auth\EmailVerificationTest
PASS  Tests\Feature\Auth\PasswordConfirmationTest
PASS  Tests\Feature\Auth\PasswordResetTest
PASS  Tests\Feature\Auth\PasswordUpdateTest
PASS  Tests\Feature\Auth\RegistrationTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\General\HistoryTest
PASS  Tests\Feature\General\StorageTest
PASS  Tests\Feature\Operator\OperatorDashboardTest
PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
PASS  Tests\Feature\Operator\RbaDetailTest
✓ operator cannot edit or upload revision on validated detail                                                  0.06s  
✓ uploading revision pdf on rejected detail resets status to draft                                             0.06s  
PASS  Tests\Feature\ProfileTest
PASS  Tests\Feature\Supervisor\ReviewTest
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    80 passed (277 assertions)
Duration: 17.60s
```

### 2. Frontend Build (Bun) PASS
Kompilasi asset frontend menggunakan `bun run build` sukses:
- `public/build/assets/app-BKHAT69J.css` (77.38 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **1.95s**
