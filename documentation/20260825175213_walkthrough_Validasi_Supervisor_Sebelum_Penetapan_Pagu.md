# Walkthrough - Validasi Supervisor Sebagai Prasyarat Penetapan Pagu Rekening

Fitur validasi prasyarat penetapan pagu oleh **Administrator** telah berhasil diimplementasikan dan diverifikasi secara penuh. Administrator dicegah menetapkan pagu apabila masih terdapat usulan rincian belanja pada nomor rekening terkait yang belum divalidasi oleh Supervisor, dilengkapi dengan **pesan penolakan informatif yang merinci nama Operator pengusul dan nama Supervisor penanggung jawab**.

---

## Ringkasan Perubahan

### 1. Backend Controller (`app/Http/Controllers/Admin/RbaAccountPaguController.php`)
- **[MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)**
  - **Method `index()`**:
    - Menghitung statistik rincian belanja per rekening (`total_nominal`, `total_count`, `validated_count`, `unvalidated_count`) menggunakan agregasi SQL efisien.
    - Mengambil data rincian usulan pending validasi beserta relasi Operator (`creator`), unit kerja (`submission.unit`), dan Supervisor dari masing-masing unit kerja.
    - Meneruskan variabel data ke view `admin.headers.pagu`.
  - **Method `store()`**:
    - Sebelum menyimpan `RbaAccountPagu`, memeriksa apakah terdapat usulan rincian belanja yang belum divalidasi (`is_validated = false`).
    - Jika ditemukan usulan belum divalidasi, membatalkan penyimpanan dan mengembalikan pesan error informatif yang mencantumkan:
      - Deskripsi & nominal rincian usulan.
      - Nama Operator pengusul beserta unit kerja.
      - Nama Supervisor yang berwenang memvalidasi usulan tersebut.
    - Jika seluruh usulan sudah divalidasi atau tidak ada usulan sama sekali, pagu berhasil disimpan.

### 2. Antarmuka Administrator (`resources/views/admin/headers/pagu.blade.php`)
- **[MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)**
  - Menampilkan alert box error yang mendukung format HTML rapi untuk rincian usulan pending validasi.
  - Menambahkan kolom **Status Validasi Supervisor** pada tabel:
    - `Belum Ada Usulan` (Abu-abu) jika belum ada rincian belanja dari operator.
    - `✅ Divalidasi (X/X)` (Hijau) jika seluruh usulan telah divalidasi.
    - `⚠️ X Usulan Belum Divalidasi` (Merah/Rose) yang langsung menampilkan detail kartu Operator & Supervisor penanggung jawab di baris tabel terkait.
  - Memberikan indikator visual dan tombol berwarna amber bertuliskan peringatan jika rekening masih memerlukan validasi dari Supervisor.

---

## Verifikasi & Hasil Pengujian

### 1. Automated Tests PASS
Seluruh 63 unit & feature tests aplikasi telah dijalankan dan **PASSED 100% (63 passed, 213 assertions)**:

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
PASS  Tests\Feature\ProfileTest
PASS  Tests\Feature\Supervisor\ReviewTest
PASS  Tests\Feature\Supervisor\SupervisorDashboardTest

Tests:    63 passed (213 assertions)
Duration: 5.60s
```

### 2. Skenario Pengujian yang Teruji:
1. **Penolakan saat Usulan Belum Divalidasi**: Ketika Administrator mencoba menyimpan pagu pada rekening yang memiliki usulan operator berstatus `is_validated = false`, request ditolak dengan pesan yang memuat nama Operator, nama Unit, deskripsi usulan, dan nama Supervisor. Data pagu tidak tersimpan ke database.
2. **Persetujuan setelah Divalidasi**: Ketika Supervisor telah memvalidasi seluruh rincian belanja (`is_validated = true`), Administrator dapat menyimpan pagu dengan sukses.
3. **Rekening Tanpa Usulan**: Rekening yang belum memiliki usulan rincian belanja dapat langsung ditetapkan pagunya oleh Administrator.
