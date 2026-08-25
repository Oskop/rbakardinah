# Walkthrough - Penetapan Pagu Per Nomor Rekening & Restriksi Pengusulan Operator

Implementasi perubahan proses bisnis penetapan pagu oleh **Administrator** menjadi **per nomor rekening** (individual) serta penerapan restriksi pengusulan rincian belanja **Operator** pada rekening yang telah ditetapkan (termasuk jika nominal pagu Rp 0) telah selesai dilaksanakan dan terverifikasi secara penuh.

---

## Ringkasan Perubahan

### 1. Backend Controller & Routing (Admin Pagu)
- **[MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)**
  - Menyesuaikan method `store()` untuk menerima `account_code_id` dan `nominal_pagu`, menyimpan atau memperbarui pagu per nomor rekening individual, serta mendukung nominal `0` sebagai pagu yang sah dan berstatus ditetapkan.
  - Menambahkan method `destroy()` untuk memberikan fleksibilitas kepada Administrator apabila ingin membatalkan/mereset status penetapan pagu sebuah rekening.
- **[MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)**
  - Menambahkan route `DELETE admin/headers/{header}/pagu/{accountCode}` (`headers.pagu.destroy`).

### 2. Antarmuka Administrator (Admin Views)
- **[MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)**
  - Menghadirkan ringkasan kartu statistik di bagian atas (Total Rekening, Sudah Ditetapkan, Belum Ditetapkan, Total Pagu Ditetapkan).
  - Setiap baris rekening kini memiliki:
    - Informasi nomor rekening, nama, dan kelompok belanja.
    - Akumulasi total usulan Operator.
    - Status penetapan: badge `✅ Sudah Ditetapkan` (dengan timestamp terakhir update) atau `⏳ Belum Ditetapkan`.
    - Input nominal pagu dan tombol **💾 Simpan** khusus untuk baris rekening tersebut.
    - Tombol aksi **Batal** untuk mereset status penetapan rekening jika sudah ditetapkan.

### 3. Logika Restriksi Operator & Policy
- **[MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)**
  - Memperbarui helper `isPaguIssued()` agar memeriksa keberadaan record pagu tanpa membatasi `nominal_pagu > 0`. Rekening dengan pagu Rp 0 yang telah disimpan Administrator langsung dianggap berstatus ditetapkan dan terkunci dari penambahan usulan baru, pengubahan nominal, maupun penghapusan rincian.
- **[MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**
  - Memperbarui query `$lockedAccountIds` pada `create()` dan `edit()` agar rekening yang telah ditetapkan pagunya (termasuk Rp 0) otomatis dikeluarkan dari daftar pilihan dropdown rekening.
- **[MODIFY] [RbaDetail.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDetail.php)**
  - Memperbarui `isExceedingPagu()` sehingga usulan yang memiliki pagu Rp 0 tetap terdeteksi melebihi pagu (*over*) dan mewajibkan revisi/PDF penyesuaian jika usulan > 0.

### 4. Tampilan Operator & Supervisor
- **[MODIFY] [operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)** & **[supervisor/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)**
  - Menggunakan status penetapan pagu `isPaguEstablished` berbasis keberadaan data pagu per rekening. Menampilkan nominal `Rp 0` (bukan tanda strip `-`) jika pagu ditetapkan Rp 0, serta mengunci aksi edit/hapus/pengajuan usulan pada rekening yang sudah ditetapkan.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh 61 unit & feature tests aplikasi telah dijalankan dan **PASSED 100% (61 passed, 206 assertions)**:

```text
PASS  Tests\Feature\Admin\AdminDashboardTest
PASS  Tests\Feature\Admin\KelompokBelanjaTest
PASS  Tests\Feature\Admin\PaguTest
✓ admin can set pagu per account code                                                                          0.03s  
✓ admin can set pagu zero and it is considered established                                                     0.03s  
✓ setting pagu zero locks operator from creating detail                                                        0.06s  
✓ admin can cancel pagu for account code                                                                       0.03s  
PASS  Tests\Feature\Auth\AuthenticationTest
PASS  Tests\Feature\Auth\EmailVerificationTest
PASS  Tests\Feature\Auth\PasswordConfirmationTest
PASS  Tests\Feature\Auth\PasswordResetTest
PASS  Tests\Feature\Auth\PasswordUpdateTest
PASS  Tests\Feature\Auth\RegistrationTest
PASS  Tests\Feature\General\HistoryTest
PASS  Tests\Feature\Operator\OperatorDashboardTest
PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
PASS  Tests\Feature\Operator\RbaDetailTest
PASS  Tests\Feature\ProfileTest
PASS  Tests\Feature\Supervisor\ReviewTest
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    61 passed (206 assertions)
Duration: 26.66s
```

### 2. Skenario Pengujian Berhasil:
- **Penetapan Pagu Per Rekening**: Administrator dapat menyimpan pagu pada salah satu atau beberapa rekening secara mandiri tanpa harus mengisi seluruh rekening sekaligus.
- **Pagu Bernilai 0**: Administrator menginput nominal `0` dan menekan Simpan; sistem mencatat status rekening tersebut sebagai `✅ Sudah Ditetapkan`.
- **Restriksi Operator**: Operator yang mencoba menambah rincian baru pada rekening yang sudah berstatus ditetapkan (termasuk Rp 0) diblokir oleh sistem (tidak muncul di dropdown dan mengembalikan response 403 Forbidden bila dipaksa via request).
- **Pembatalan Penetapan**: Administrator dapat membatalkan penetapan rekening, sehingga statusnya kembali menjadi `⏳ Belum Ditetapkan` dan dapat diusulkan kembali oleh Operator.
