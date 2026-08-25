# Walkthrough - Reset Status Ditolak Menjadi Draft Saat Upload Revisi PDF

Perbaikan logika pada fitur **Upload Revisi PDF** (`DetailController::uploadVersion`) agar **status usulan yang sebelumnya ditolak (`is_rejected = true`) otomatis kembali menjadi Draft** telah selesai diimplementasikan dan terverifikasi secara penuh.

---

## Ringkasan Perubahan

### 1. Operator Detail Controller (`app/Http/Controllers/Operator/DetailController.php`)
- **[MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**
  - **Method `uploadVersion()`**:
    - Setiap kali Operator mengunggah versi dokumen PDF baru (V2, V3, dst.), sistem secara otomatis mereset seluruh atribut penolakan, validasi, dan pengajuan kembali ke kondisi awal (Draft):
      ```php
      $detail->update([
          'is_validated' => false,
          'validated_at' => null,
          'validated_by' => null,
          'is_submitted' => false,
          'is_rejected' => false,
          'rejected_at' => null,
          'rejected_by' => null,
          'rejection_reason' => null,
      ]);
      ```
    - Pesan notifikasi sukses diperbarui menjadi: `"Versi PDF baru (V{$newVersion}) berhasil diunggah. Status usulan kembali menjadi Draft (silakan klik Ajukan ke Supervisor)."`

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh 71 unit & feature tests aplikasi telah dijalankan dan **PASSED 100% (71 passed, 251 assertions)**:

```text
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\KelompokBelanjaTest
PASS  Tests\Feature\Admin\PaguTest
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
✓ operator can view their submissions                                                                          0.05s  
✓ operator can create rba detail with pdf                                                                      0.04s  
✓ operator submission view displays previous period pagu in awal column                                        0.05s  
✓ operator can upload new version of pdf                                                                       0.04s  
✓ operator can submit item to supervisor                                                                       0.04s  
✓ operator can soft delete rba detail                                                                          0.04s  
✓ operator must upload new pdf when nominal exceeds pagu                                                       0.05s  
✓ supervisor cannot validate item exceeding pagu without revision                                              0.06s  
✓ operator cannot add detail if background is empty                                                            0.04s  
✓ operator can save background                                                                                 0.04s  
✓ operator can upload kak rak rtp versioned documents when locked                                              0.05s  
✓ editing validated detail resets status to draft                                                              0.04s  
✓ uploading revision pdf on rejected detail resets status to draft                                             0.05s  
PASS  Tests\Feature\ProfileTest
PASS  Tests\Feature\Supervisor\ReviewTest
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    71 passed (251 assertions)
Duration: 5.70s
```

### 2. Skenario yang Terverifikasi:
1. **Pembersihan Status Ditolak**: Usulan yang ditolak oleh Supervisor memiliki atribut `is_rejected = true` dan `rejection_reason`. Saat Operator mengunggah revisi PDF baru, `is_rejected` langsung berubah menjadi `false` dan `rejection_reason` dibersihkan.
2. **Kembali ke Status Draft**: Badge status berubah dari **"Tolak"** menjadi **"Draft"**.
3. **Pengajuan Ulang**: Tombol **Ajukan** kembali aktif sehingga Operator dapat mengajukan usulan hasil revisi tersebut ke Supervisor.
