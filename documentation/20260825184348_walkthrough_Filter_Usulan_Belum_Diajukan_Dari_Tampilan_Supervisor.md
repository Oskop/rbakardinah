# Walkthrough - Filter Usulan Belum Diajukan (Draft) dari Tampilan Supervisor

Fitur penyaringan visibilitas usulan rincian belanja (`RbaDetail`) agar **usulan yang belum diajukan (Draft / `is_submitted = false`) tidak muncul pada tampilan Supervisor** telah selesai diimplementasikan dan terverifikasi secara penuh.

---

## Ringkasan Perubahan

### 1. Supervisor Review Controller (`app/Http/Controllers/Supervisor/ReviewController.php`)
- **[MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)**
  - **Method `show()`**:
    - Menambahkan filter constraint `where('is_submitted', true)` pada eager loading relasi `details` saat Supervisor membuka halaman review usulan unit.
    - Menghitung akumulasi total usulan per rekening (`$headerTotals`) hanya dari usulan yang berstatus telah diajukan (`is_submitted = true`).
  - **Method `printPreview()` & `printPreviewFinal()`**:
    - Memastikan dokumen pratinjau cetak usulan dan RBA final Supervisor hanya memuat rincian belanja yang telah diajukan oleh Operator (`is_submitted = true`).
  - **Method `toggleDetailValidation()` & `rejectDetail()`**:
    - Menambahkan validasi guard: jika rincian belum diajukan oleh Operator (`!$detail->is_submitted`), aksi validasi atau penolakan langsung dibatalkan dengan pesan `"Usulan rincian belanja ini belum diajukan oleh Operator."`.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh 68 unit & feature tests aplikasi telah dijalankan dan **PASSED 100% (68 passed, 237 assertions)**:

```text
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\KelompokBelanjaTest
PASS  Tests\Feature\Admin\PaguTest
✓ admin can set pagu per account code                                                                          0.04s  
✓ admin can set pagu zero and it is considered established                                                     0.03s  
✓ setting pagu zero locks operator from creating detail                                                        0.06s  
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
✓ supervisor can view their unit submissions                                                                   0.05s  
✓ supervisor can validate submission                                                                           0.03s  
✓ supervisor can see previous period pagu in awal column                                                       0.05s  
✓ supervisor can preview print report with operator filters                                                    0.06s  
✓ supervisor can preview rba final print report with pagu and operator filters                                 0.04s  
✓ supervisor cannot see draft unsubmitted details                                                              0.05s  
✓ detail disappears from supervisor when edited and reappears when resubmitted                                 0.08s  
✓ supervisor cannot validate or reject unsubmitted detail                                                      0.03s  
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    68 passed (237 assertions)
Duration: 5.60s
```

### 2. Skenario yang Terverifikasi:
1. **Usulan Baru Berstatus Draft**: Operator membuat usulan baru (belum klik Ajukan), Supervisor membuka halaman review dan usulan tersebut **tidak muncul**.
2. **Usulan Setelah Diajukan**: Operator mengklik **Ajukan**, Supervisor merefresh halaman dan usulan tersebut **muncul** serta siap untuk divalidasi.
3. **Usulan Pasca-Edit**: Operator mengedit usulan (sehingga statusnya kembali Draft), usulan tersebut **otomatis hilang** dari tampilan review dan cetak Supervisor hingga Operator mengklik **Ajukan** kembali.
4. **Proteksi Validasi**: Supervisor tidak dapat melakukan validasi/penolakan terhadap rincian yang belum diajukan oleh Operator.
