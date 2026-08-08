# Walkthrough - Fitur Cetak Rincian Belanja & Pagu (RBA Final) Tampilan Operator

Penerapan fitur **Cetak Rincian Belanja dengan Pagunya (RBA Final)** pada halaman Workboard Usulan RBA Operator (`/operator/submissions/{submission}`).

---

## Changes Made

### 1. Controller & Routing Layer

#### [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak RBA Final Operator: `GET /operator/submissions/{submission}/print-preview-final` (`operator.submissions.print-preview-final`).

#### [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Menambahkan method `printPreviewFinal(Request $request, RbaSubmission $submission)` untuk:
  - Memuat data rincian usulan belanja Operator.
  - Memuat data Pagu Global / Pagu Penetapan (`RbaAccountPagu`) untuk RBA Header tersebut.
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Me-render view `reports.operator_rba_final_print`.

---

### 2. View Layer (Operator Submissions Workboard)

#### [show.blade.php (Operator Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Memperbarui dropdown **"🖨️ Cetak Rincian"** pada header Workboard Operator dengan 2 kategori utama:
  1. **Usulan Rincian Belanja (Draft/Usulan)**:
     - *📄 Cetak Dengan Latar Belakang*
     - *📄 Cetak Tanpa Latar Belakang*
  2. **Rincian Belanja & Pagu (RBA Final)**:
     - *📊 Cetak RBA Final (Dengan Latar Belakang)*
     - *📊 Cetak RBA Final (Tanpa Latar Belakang)*

---

### 3. Report Template Layer

#### [operator_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_final_print.blade.php)
- Membuat Blade template terstruktur khusus RBA Final dengan Pagu:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi: `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)`.
  - Informasi Metadata Grid (Unit Kerja, Periode RBA, Operator Pembuat, Status Submisi, Opsi Cetak, Tanggal Cetak).
  - Section I Latar Belakang Sub-Unit (Kondisional).
  - Section II Tabel Rincian Belanja & Pagu Final dengan urutan kolom persis sesuai spesifikasi:
    1. `NO`
    2. `KODE REKENING`
    3. `URAIAN & SPESIFIKASI BELANJA`
    4. `AWAL (Rp)`
    5. `VOL`
    6. `SATUAN`
    7. `HARGA SATUAN (Rp)`
    8. `TOTAL USULAN (Rp)`
    9. `PAGU FINAL (Rp)` *(Terletak di antara TOTAL USULAN dan STATUS)*
    10. `STATUS`
  - Lembar Pengesahan Tanda Tangan Supervisor & Operator.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

## Verification Results

### Automated Tests
Jalankan pengujian fitur Operator via Artisan Test:
```powershell
php artisan test --filter=RbaDetailFeaturesTest
```

**Hasil Pengujian:**
```text
   PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
  ✓ operator can view their submissions                                                                          0.04s  
  ✓ operator can create rba detail                                                                               0.03s  
  ✓ operator can create rba detail with long description                                                         0.04s  
  ✓ operator can submit item                                                                                     0.04s  
  ✓ operator can soft delete item                                                                                0.03s  
  ✓ operator can preview print report with and without background                                                0.05s  
  ✓ operator can export pdf report                                                                               0.05s  
  ✓ operator can preview rba final print report                                                                  0.04s  

  Tests:    8 passed (21 assertions)
  Duration: 1.53s
```

### Manual Verification
1. Login sebagai Operator dan buka halaman Workboard Usulan Belanja (`/operator/submissions/{id}`).
2. Klik dropdown **"🖨️ Cetak Rincian"**:
   - Pilih **2. Rincian Belanja & Pagu (RBA Final)** $\rightarrow$ **Cetak RBA Final (Dengan Latar Belakang)**.
   - Laporan cetak RBA Final terbuka di tab baru.
   - Memastikan judul `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN DAN PAGU FINAL (RBA FINAL)` tampil.
   - Memastikan kolom **PAGU FINAL (Rp)** muncul di antara kolom **TOTAL USULAN (Rp)** dan **STATUS**.
3. Klik **Cetak Dokumen (Ctrl+P)** pada pratinjau cetak:
   - Memastikan cetakan kertas A4 Landscape rapi, logo kop surat muncul, dan toolbar tersembunyi.
