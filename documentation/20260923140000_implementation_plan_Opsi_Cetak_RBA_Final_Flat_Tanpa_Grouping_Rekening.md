# Penambahan Format Opsi Cetak RBA Final: Per Baris Usulan (Flat / Tanpa Grouping Rekening)

Dokumen rencana implementasi ini menyajikan rancangan teknis penambahan opsi format cetak pada fitur **Cetak RBA Final** di SIPAKAR RSUD Kardinah Kota Tegal. Format baru ini menyediakan tata letak **Per Baris Usulan (Flat / Datar)** di mana usulan belanja tidak dikelompokkan ke dalam *group header* atau *subtotal* kode rekening, melainkan setiap usulan ditampilkan 1 baris dengan kolom **Nomor Rekening** dan **Nama Rekening** yang tercetak pada tiap baris usulan (sehingga akan tampak berulang jika beberapa usulan memiliki rekening yang sama).

---

## User Review Required

> [!IMPORTANT]
> **Format Eksisting Tetap Dipertahankan (Non-Breaking)**
> Penambahan ini berstatus sebagai opsi tambahan (**Opsional / Alternatif**). Format cetak hierarkis terkelompok (*Grouped by Account Code*) yang telah digunakan saat ini tetap menjadi **pilihan bawaan (default)**. Alur kerja dan cetakan lama tidak mengalami perubahan perilaku (100% backward compatible).

> [!NOTE]
> **Skema Parameter URL & State**
> Parameter query baru yang digunakan adalah `grouping`:
> - `grouping=account` (Default): Format hierarkis terkelompok per kode rekening dengan subtotal per rekening.
> - `grouping=flat`: Format per baris usulan datar (1 usulan = 1 baris, kolom nomor rekening & nama rekening eksplisit, tanpa pemisah subtotal).
> Pada layar pratinjau cetak (*screen view*), disediakan pula tombol beralih cepat (*quick toggle*) pada toolbar atas (`.no-print-bar`) agar pemeriksa/atasan dapat berpindah format secara instan tanpa perlu menutup tab preview.

---

## Open Questions

Tidak ada pertanyaan mendesak yang memblokir. Seluruh kebutuhan bisnis telah terdefinisi secara jelas:
1. Satu usulan = satu baris datar.
2. Kolom Nomor Rekening & Nama Rekening tampil eksplisit di setiap baris.
3. Tetap memiliki baris total akumulasi (Grand Total) di akhir tabel.
4. Opsi dapat diakses oleh semua tingkatan pengguna yang memiliki hak cetak RBA Final (Operator, Supervisor, Administrator, dan Menu Pusat Laporan).

---

## Proposed Changes

### Controller Layer

Menangkap parameter `grouping` dari request URL (default: `'account'`), memvalidasi nilainya, dan meneruskannya ke view laporan cetak RBA Final.

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Pada method `printPreviewFinal(Request $request, RbaSubmission $submission)`:
  - Ambil parameter `$grouping = $request->get('grouping', 'account');` (hanya terima `'account'` atau `'flat'`, default `'account'`).
  - Teruskan variabel `grouping` ke view `reports.operator_rba_final_print`.

#### [MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Pada method `printPreviewFinal(Request $request, RbaSubmission $submission)`:
  - Ambil parameter `$grouping = $request->get('grouping', 'account');`.
  - Teruskan variabel `grouping` ke view `reports.supervisor_rba_final_print`.

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Pada method `printPreviewFinal(Request $request, \App\Models\RbaHeader $header)`:
  - Ambil parameter `$grouping = $request->get('grouping', 'account');`.
  - Teruskan variabel `grouping` ke view `reports.admin_rba_final_print`.

---

### View Print Template Layer

Mengadaptasi template cetak RBA Final agar dapat menampilkan format terkelompok maupun format flat per baris usulan secara dinamis sesuai nilai `$grouping`.

#### [MODIFY] [operator_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_final_print.blade.php)
- **Web Toolbar (`.no-print-bar`)**:
  - Tambahkan tombol selector format: `📑 Terkelompok Rekening` vs `📋 Per Baris Usulan (Flat)`.
- **Metadata Card**:
  - Tampilkan informasi format cetak: `Terkelompok Rekening` atau `Per Baris Usulan (Flat)`.
- **Struktur Tabel**:
  - Jika `$grouping === 'flat'`:
    - Header Kolom:
      1. `NO` (3%)
      2. `NOMOR REKENING` (10%)
      3. `NAMA REKENING` (14%)
      4. `URAIAN & SPESIFIKASI BELANJA` (18%)
      5. `AWAL (Rp)` (9%)
      6. `VOL` (5%)
      7. `SATUAN` (5%)
      8. `HARGA SATUAN (Rp)` (9%)
      9. `TOTAL USULAN (Rp)` (11%)
      10. `PAGU FINAL (Rp)` (10%)
      11. `STATUS` (6%)
    - Baris data: Perulangan langsung `$submission->details` di mana setiap usulan memiliki kolom Nomor Rekening dan Nama Rekening tersendiri.
    - Footer: Grand total usulan dan akumulasi pagu unik rekening terkait.
  - Jika `$grouping === 'account'` (Default):
    - Pertahankan format standar terkelompok rekening.

#### [MODIFY] [supervisor_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php)
- **Web Toolbar (`.no-print-bar`)**:
  - Tambahkan tombol beralih cepat format cetak dengan mempertahankan parameter filter operator dan latar belakang yang aktif.
- **Metadata Card**:
  - Tampilkan status opsi format cetak.
- **Struktur Tabel**:
  - Jika `$grouping === 'flat'`:
    - Header Kolom:
      1. `NO` (3%)
      2. `NOMOR REKENING` (9%)
      3. `NAMA REKENING` (13%)
      4. `URAIAN & SPESIFIKASI BELANJA` (18%)
      5. `OPERATOR` (9%)
      6. `AWAL (Rp)` (8%)
      7. `VOL` (4%)
      8. `SATUAN` (5%)
      9. `HARGA SATUAN (Rp)` (8%)
      10. `TOTAL USULAN (Rp)` (10%)
      11. `PAGU FINAL (Rp)` (8%)
      12. `STATUS` (5%)
    - Menghilangkan baris *group header* dan baris *subtotal* per kode rekening.
    - Menampilkan Nomor Rekening dan Nama Rekening pada setiap baris usulan.
    - Footer: Menampilkan `TOTAL KESELURUHAN RINCIAN BELANJA & PAGU FINAL`.
  - Jika `$grouping === 'account'` (Default):
    - Pertahankan format hierarkis terkelompok dengan group header dan subtotal.

#### [MODIFY] [admin_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)
- **Web Toolbar (`.no-print-bar`)**:
  - Tambahkan tombol beralih cepat format cetak dengan mempertahankan parameter filter unit, operator, dan latar belakang yang aktif.
- **Metadata Card**:
  - Tampilkan status opsi format cetak.
- **Struktur Tabel**:
  - Jika `$grouping === 'flat'`:
    - Header Kolom:
      1. `NO` (3%)
      2. `NOMOR REKENING` (9%)
      3. `NAMA REKENING` (12%)
      4. `URAIAN & SPESIFIKASI BELANJA` (16%)
      5. `UNIT KERJA` (10%)
      6. `OPERATOR` (8%)
      7. `AWAL (Rp)` (8%)
      8. `VOL` (4%)
      9. `SATUAN` (4%)
      10. `HARGA SATUAN (Rp)` (7%)
      11. `TOTAL USULAN (Rp)` (9%)
      12. `PAGU FINAL (Rp)` (6%)
      13. `STATUS` (4%)
    - Menampilkan seluruh detail usulan secara datar (flat), 1 usulan = 1 baris.
    - Footer: Total keseluruhan usulan dan pagu final.
  - Jika `$grouping === 'account'` (Default):
    - Pertahankan format hierarkis terkelompok dengan group header dan subtotal.

---

### UI Trigger & Modal Configuration Layer

Menyediakan opsi pemilihan format pada workboard Operator, modal cetak Supervisor, modal cetak Admin, dan menu Pusat Laporan.

#### [MODIFY] [show.blade.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Pada dropdown "🖨️ Cetak Dokumen RBA" bagian "2. Rincian Belanja & Pagu (RBA Final)":
  - Sediakan 2 sub-kategori opsi:
    - **Format Terkelompok Rekening (Default)**:
      - Cetak RBA Final (Dengan Latar Belakang) `grouping=account`
      - Cetak RBA Final (Tanpa Latar Belakang) `grouping=account`
    - **Format Per Baris Usulan (Flat)**:
      - Cetak RBA Final Flat (Dengan Latar Belakang) `grouping=flat`
      - Cetak RBA Final Flat (Tanpa Latar Belakang) `grouping=flat`

#### [MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Pada modal cetak `openPrintModal`:
  - Tambahkan radio selector format yang muncul kondisional saat `printType === 'final'`:
    - Pilihan 1: 🔘 **Terkelompok Rekening (Hierarkis)** (`grouping=account`, default)
    - Pilihan 2: 🔘 **Per Baris Usulan (Flat / Datar)** (`grouping=flat`)
  - Form action / query parameter menyertakan nilai `grouping`.

#### [MODIFY] [show.blade.php (Admin)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Pada modal cetak Admin `openPrintModal`:
  - Tambahkan radio selector format saat `printType === 'final'`:
    - Pilihan 1: 🔘 **Terkelompok Rekening (Hierarkis)** (`grouping=account`, default)
    - Pilihan 2: 🔘 **Per Baris Usulan (Flat / Datar)** (`grouping=flat`)
  - Form action menyertakan nilai `grouping`.

#### [MODIFY] [index.blade.php (Reports)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/index.blade.php)
- Pada formulir Pusat Laporan & Cetak RBA:
  - Ketika `printType === 'final'`, tampilkan opsi format tabel:
    - 🔘 Terkelompok Rekening (Default)
    - 🔘 Per Baris Usulan (Flat)
  - Parameter `grouping` dikirim ke controller saat form dicetak.

---

### Documentation Layer

#### [NEW] [20260923140000_implementation_plan_Opsi_Cetak_RBA_Final_Flat_Tanpa_Grouping_Rekening.md](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/documentation/20260923140000_implementation_plan_Opsi_Cetak_RBA_Final_Flat_Tanpa_Grouping_Rekening.md)
- Arsip dokumentasi rencana implementasi ke folder `documentation/`.

---

## Verification Plan

### Automated Tests
Jalankan pengujian automated PHPUnit / Pest untuk memastikan endpoint cetak RBA Final berjalan mulus dengan opsi format baru maupun format default:

1. **Operator Cetak RBA Final Flat**:
   - Menambahkan test case di `tests/Feature/Operator/RbaDetailFeaturesTest.php`:
     - Akses `operator.submissions.print-preview-final` dengan parameter `grouping=flat`.
     - Memastikan status HTTP 200.
     - Memastikan response memuat kolom `NOMOR REKENING` dan `NAMA REKENING`.
     - Memastikan data akun dan rincian usulan ter-render per baris.

2. **Supervisor Cetak RBA Final Flat**:
   - Menambahkan test case di `tests/Feature/Supervisor/ReviewTest.php`:
     - Akses `supervisor.submissions.print-preview-final` dengan parameter `grouping=flat`.
     - Memastikan status HTTP 200.
     - Memastikan tidak ada teks `SUBTOTAL KODE REKENING` (karena tidak dikelompokkan).
     - Memastikan usulan yang berbagi kode rekening yang sama menampilkan nomor rekening dan nama rekening pada baris masing-masing usulan.

3. **Admin Cetak RBA Final Flat**:
   - Menambahkan test case di `tests/Feature/Admin/AdminDashboardTest.php`:
     - Akses `admin.headers.print-preview-final` dengan parameter `grouping=flat`.
     - Memastikan status HTTP 200.
     - Memastikan kolom `UNIT KERJA`, `NOMOR REKENING`, dan `NAMA REKENING` muncul.

4. **Pusat Laporan Terintegrasi**:
   - Menambahkan pengujian di `tests/Feature/General/ReportMenuTest.php` untuk memastikan parameter `grouping=flat` dapat diakses dengan respons 200.

Perintah uji:
```powershell
php artisan test --filter=RbaDetailFeaturesTest
php artisan test --filter=ReviewTest
php artisan test --filter=AdminDashboardTest
php artisan test --filter=ReportMenuTest
```

### Manual Verification
1. Login sebagai Operator, buka workboard submission (`/operator/submissions/{id}`). Buka dropdown cetak, pilih format RBA Final Terkelompok dan RBA Final Flat. Periksa tabel di browser.
2. Login sebagai Supervisor, buka workboard submission (`/supervisor/submissions/{id}`). Klik Cetak RBA, pilih opsi RBA Final dan pilih format "Per Baris Usulan (Flat)". Pastikan tabel flat muncul tanpa subtotal per akun dan kolom nomor & nama rekening muncul di tiap baris usulan.
3. Login sebagai Administrator, buka periode RBA (`/admin/headers/{id}`). Buka modal cetak, cetak RBA Final dengan format flat, periksa kebenaran data dan tombol beralih cepat di toolbar web.
4. Coba klik toggle di toolbar pratinjau (`.no-print-bar`) untuk berpindah dari flat ke grouped dan sebaliknya.
