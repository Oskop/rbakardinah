# Walkthrough: Opsi Cetak RBA Final Format Per Baris Usulan (Flat / Tanpa Grouping Rekening)

Fitur cetak RBA Final telah berhasil ditingkatkan dengan menambahkan format opsi baru: **Per Baris Usulan (Flat / Datar Tanpa Grouping Rekening)**, sesuai dengan instruksi dan kebutuhan pimpinan/atasan.

---

## 🎯 Ringkasan Hasil Pekerjaan

| Aspek | Format 1 (Terkelompok Rekening - Default) | Format 2 (Per Baris Usulan / Flat - Opsi Baru) |
|---|---|---|
| **Struktur Data** | Dikelompokkan per akun belanja dengan *Group Header Row* dan *Subtotal Row*. | 1 Usulan Belanja = 1 Baris data datar (*flat*). |
| **Kolom Akun** | Kode & nama rekening tercetak sekali pada baris header grup rekening. | Kolom **NOMOR REKENING** dan **NAMA REKENING** tampil eksplisit pada setiap baris usulan (berulang jika rekening sama). |
| **Pemisah Subtotal** | Terdapat baris subtotal untuk setiap kode rekening. | **Tidak ada** pemisah subtotal antar rekening (hanya *Grand Total* di akhir tabel). |
| **Parameter URL** | `grouping=account` (atau tanpa parameter / default). | `grouping=flat` |
| **Akses Pengguna** | Operator, Supervisor, Administrator, Menu Laporan. | Operator, Supervisor, Administrator, Menu Laporan. |
| **Peralihan Cepat** | Tersedia tombol beralih cepat di toolbar preview web (`.no-print-bar`). | Tersedia tombol beralih cepat di toolbar preview web (`.no-print-bar`). |

---

## 🛠️ Rincian Perubahan File

### 1. Controller Layer
- [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php):
  - Menangkap parameter `$grouping` (`'account'` atau `'flat'`, default `'account'`) pada method `printPreviewFinal` dan meneruskannya ke view `reports.operator_rba_final_print`.
- [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php):
  - Menangkap parameter `$grouping` pada method `printPreviewFinal` dan meneruskannya ke view `reports.supervisor_rba_final_print`.
- [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php):
  - Menangkap parameter `$grouping` pada method `printPreviewFinal` dan meneruskannya ke view `reports.admin_rba_final_print`.

### 2. View Print Templates
- [operator_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_final_print.blade.php):
  - Toolbar: Menambahkan switcher `📑 Terkelompok Rekening` vs `📋 Per Baris Usulan (Flat)`.
  - Meta Card: Menampilkan opsi format yang aktif.
  - Tabel: Mendukung mode flat (11 kolom: `NO`, `NOMOR REKENING`, `NAMA REKENING`, `URAIAN & SPESIFIKASI BELANJA`, `AWAL (Rp)`, `VOL`, `SATUAN`, `HARGA SATUAN (Rp)`, `TOTAL USULAN (Rp)`, `PAGU FINAL (Rp)`, `STATUS`).
- [supervisor_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php):
  - Toolbar: Menambahkan switcher cepat antar format cetak.
  - Tabel: Mendukung mode flat (12 kolom, menyertakan kolom `OPERATOR`).
- [admin_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php):
  - Toolbar: Menambahkan switcher cepat antar format cetak.
  - Tabel: Mendukung mode flat (13 kolom, menyertakan kolom `UNIT KERJA` dan `OPERATOR`).

### 3. UI Trigger & Modal Konfigurasi Cetak
- [show.blade.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php):
  - Dropdown Cetak Dokumen RBA menyediakan 2 opsi baru: Cetak RBA Final Flat (Dengan Background) & Cetak RBA Final Flat (Tanpa Background).
- [show.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php):
  - Modal cetak menampilkan radio pilihan format tata letak rekening saat jenis laporan dipilih RBA Final (`final`).
- [show.blade.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php):
  - Modal cetak menampilkan radio pilihan format tata letak rekening saat jenis laporan dipilih RBA Final (`final`).
- [index.blade.php (Pusat Laporan)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/index.blade.php):
  - Formulir konfigurasi cetak memuat pilihan format rekening saat `printType === 'final'`.

---

## 🧪 Hasil Pengujian (Automated Test Suite)

Pengujian otomatis dijalankan secara menyeluruh untuk memastikan tidak ada regresi dan seluruh format berjalan dengan sempurna:

```powershell
php artisan test
```

**Hasil:**
- **260 tests passed** (1324 assertions)
- **0 failure** (100% lulus)

Pengujian yang mencakup fitur baru ini meliputi:
1. `Tests\Feature\Operator\RbaDetailFeaturesTest::test_operator_can_access_print_preview_final_flat_mode` ✓
2. `Tests\Feature\Supervisor\ReviewTest::test_supervisor_preview_rba_final_print_report_with_pagu_and_operator_filters` (mencakup mode flat) ✓
3. `Tests\Feature\Admin\AdminDashboardTest::test_admin_preview_rba_final_print_report_with_pagu_and_unit_operator_filters` (mencakup mode flat) ✓
4. `Tests\Feature\General\ReportMenuTest::test_user_can_access_print_preview_endpoints_from_reports_menu` (mencakup verifikasi flat mode admin, supervisor, dan operator) ✓
