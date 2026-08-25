# Walkthrough - Reset Status Usulan Menjadi Draft Saat Operator Melakukan Edit

Fitur reset otomatis status usulan rincian belanja (`RbaDetail`) kembali menjadi **Draft** saat Operator melakukan perubahan/edit pasca-validasi oleh Supervisor telah selesai diimplementasikan dan terverifikasi secara penuh.

---

## Ringkasan Perubahan

### 1. Kebijakan Otorisasi (`app/Policies/RbaDetailPolicy.php`)
- **[MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)**
  - Mengizinkan Operator pemilik rincian belanja untuk melakukan pengeditan usulan selama nomor rekening terkait belum ditetapkan pagunya oleh Administrator (`!$this->isPaguIssued()`).
  - Menghapus pembatasan statis yang sebelumnya mengunci pengeditan ketika usulan telah diajukan atau divalidasi, karena proses update kini secara otomatis mengembalikan status rincian ke Draft.

### 2. Controller Rincian Belanja Operator (`app/Http/Controllers/Operator/DetailController.php`)
- **[MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**
  - **Method `update()`**:
    - Setiap kali Operator menyimpan perubahan data rincian belanja (volume, harga satuan, deskripsi, rekening), sistem secara otomatis mereset seluruh atribut validasi dan pengajuan:
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
    - Notifikasi sukses diperbarui menjadi: `"RBA Detail berhasil diperbarui dan status kembali menjadi Draft (perlu diajukan dan divalidasi ulang oleh Supervisor)."`.
  - **Method `uploadVersion()`**:
    - Jika Operator mengunggah revisi PDF baru saat pagu belum ditetapkan, status validasi juga direset ke Draft agar Supervisor memvalidasi file PDF terbaru tersebut.

### 3. Antarmuka Operator (`resources/views/operator/submissions/show.blade.php`)
- **[MODIFY] [operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)**
  - Tombol aksi **Edit**, **Ajukan**, dan **Hapus** ditampilkan secara responsif untuk rincian belanja selama rekening belum dikunci pagu (`!$isItemLockedByPagu`).
  - Setelah diedit, badge status rincian langsung berubah kembali menjadi **Draft** (abu-abu), dan tombol **Ajukan** (hijau) muncul agar Operator dapat mengajukannya kembali ke Supervisor.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh 65 unit & feature tests aplikasi telah dijalankan dan **PASSED 100% (65 passed, 228 assertions)**:

```text
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\KelompokBelanjaTest
PASS  Tests\Feature\Admin\PaguTest
✓ admin can set pagu per account code                                                                          0.04s  
✓ admin can set pagu zero and it is considered established                                                     0.03s  
✓ setting pagu zero locks operator from creating detail                                                        0.05s  
✓ admin cannot set pagu if operator details not validated by supervisor                                        0.03s  
✓ admin can set pagu when all operator details are validated                                                   0.03s  
✓ admin can cancel pagu for account code                                                                       0.03s  
✓ admin cannot set pagu after operator edits validated detail until revalidated                                0.04s  
PASS  Tests\Feature\Auth\AuthenticationTest
PASS  Tests\Feature\Auth\EmailVerificationTest
PASS  Tests\Feature\Auth\PasswordConfirmationTest
PASS  Tests\Feature\Auth\PasswordResetTest
PASS  Tests\Feature\Auth\PasswordUpdateTest
PASS  Tests\Feature\Auth\RegistrationTest
PASS  Tests\Feature\ExampleTest
PASS  Tests\Feature\General\HistoryTest
PASS  Tests\Feature\Operator\OperatorDashboardTest
PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
PASS  Tests\Feature\Operator\RbaDetailTest
✓ editing validated detail resets status to draft                                                              0.04s  
PASS  Tests\Feature\ProfileTest
PASS  Tests\Feature\Supervisor\ReviewTest
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    65 passed (228 assertions)
Duration: 5.42s
```

### 2. Skenario yang Terverifikasi:
1. **Reset ke Draft saat Diedit**: Operator mengedit rincian usulan yang sebelumnya sudah divalidasi oleh Supervisor (`is_validated = true`). Setelah disimpan, rincian tersebut berhasil berstatus Draft (`is_validated = false`, `is_submitted = false`).
2. **Pencegahan Penetapan Pagu oleh Admin**: Saat Operator mengedit rincian yang sudah divalidasi, Admin yang mencoba menetapkan pagu untuk rekening tersebut langsung ditolak dengan pesan informatif bahwa usulan tersebut belum divalidasi kembali oleh Supervisor.
3. **Siklus Lengkap Validasi Ulang**: Operator mengajukan kembali usulan hasil edit -> Supervisor memvalidasi ulang usulan tersebut -> Admin berhasil menyimpan pagu.
