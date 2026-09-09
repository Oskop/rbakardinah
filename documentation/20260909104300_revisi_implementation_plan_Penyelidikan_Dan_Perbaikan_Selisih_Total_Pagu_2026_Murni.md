# Implementation Plan - Penyelidikan & Perbaikan Selisih Total Pagu RBA 2026 Murni (Revisi)

Investigasi mendalam dan perbaikan ketidaksesuaian total pagu tahun anggaran **2026 Murni** antara tampilan Administrator (**Rp 177.000.000.000**) dengan dokumen cetak RBA Final (**Rp 176.900.000.000**), dengan selisih sebesar **Rp 100.000.000**.

---

## 1. Hasil Penyelidikan & Analisis Akar Masalah (Root Cause Analysis)

### 1.1 Temuan Investigasi Database
Berdasarkan penelusuran langsung pada database SIPAKAR RSUD Kardinah untuk Header ID 1 (**Tahun 2026, Periode Murni**):
1. **Total Pagu di Database (`rba_account_pagus`)**:
   - Total nominal pagu yang ditetapkan oleh Administrator: **Rp 177.000.000.000**
   - Tersebar pada **66 Kode Rekening Belanja**.
2. **Total Usulan Belanja (`rba_details`)**:
   - Total nominal usulan: **Rp 177.775.000.000** (67 item usulan belanja).
   - Item-item belanja ini diajukan hanya untuk **65 Kode Rekening Belanja**.
3. **Rekening Penyebab Selisih Rp 100.000.000**:
   - Terdapat tepat **1 Kode Rekening** yang telah ditetapkan pagunya di `rba_account_pagus`, namun **BELUM PERNAH diinputkan rincian usulan belanja sama sekali oleh unit kerja/operator** (`rba_details` count = 0):
     - **ID Rekening**: `44`
     - **Kode Rekening**: `5.1.02.02.02.0005`
     - **Nama Rekening**: `Belanja Iuran Jaminan Kesehatan bagi Non ASN`
     - **Nominal Pagu**: **Rp 100.000.000**
     - **Jumlah Usulan Belanja**: **0 item (Rp 0)**

### 1.2 Mengapa Total Pagu Berbeda antara Admin dan Hasil Cetak?
- **Pada Tampilan Admin** ([`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php) & [`pagu.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)):
  Total Pagu Global dihitung langsung dari seluruh record tabel pagu:
  ```php
  $totalPagu = $pagus->sum('nominal_pagu'); // Hasil: Rp 177.000.000.000
  ```
  Sehingga rekening `5.1.02.02.02.0005` (Rp 100.000.000) terhitung penuh di ringkasan admin.

- **Pada Versi Cetak RBA Final** ([`resources/views/reports/admin_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)):
  Logika tabel cetak me-loop data berdasarkan rincian usulan belanja (`$details`):
  ```blade
  @php
      $groupedDetails = $details->groupBy('account_code_id');
      $grandTotalPaguFinal = 0;
  @endphp

  @forelse($groupedDetails as $accountCodeId => $itemDetails)
      @php
          $paguFinal = $pagus[$accountCodeId]->nominal_pagu ?? 0;
          $grandTotalPaguFinal += $paguFinal;
      @endphp
      ...
  @endforelse
  ```
  Karena rekening `5.1.02.02.02.0005` tidak memiliki usulan belanja (`$itemDetails`), rekening ini **tidak ada di dalam `$groupedDetails`**.
  Akibatnya:
  1. Rekening `5.1.02.02.02.0005` sama sekali **tidak dicetak** di tabel dokumen RBA Final.
  2. Nilai pagunya sebesar Rp 100.000.000 **tidak pernah ditambahkan** ke `$grandTotalPaguFinal`.
  3. Total di footer cetak hanya menjumlahkan 65 rekening lainnya, yaitu:
     **Rp 177.000.000.000 - Rp 100.000.000 = Rp 176.900.000.000**.

---

## 2. Analisis Dampak terhadap Pemilihan Opsi Cetak Dokumen RBA

Pada sistem SIPAKAR, Administrator memiliki modal konfigurasi cetak dengan 3 opsi utama. Berikut adalah analisis dampak penerapan perbaikan ini terhadap setiap opsi cetak:

### 2.1 Opsi 1: Jenis Dokumen Laporan
- **Pilihan "Usulan Rincian Belanja" (`printPreview` / `admin_rba_print.blade.php`)**:
  - **TIDAK TERPENGARUH SAMA SEKALI**.
  - Dokumen ini berfokus murni pada rekapitulasi daftar item usulan belanja riil yang diajukan oleh unit kerja/operator (tidak memuat kolom Pagu Final tahun berjalan). Perbaikan ini tidak menyentuh kode pada laporan usulan murni.
- **Pilihan "Rincian Belanja & Pagu (RBA Final)" (`printPreviewFinal` / `admin_rba_final_print.blade.php`)**:
  - **MENJADI SASARAN PERBAIKAN**.
  - Dokumen ini adalah dokumen resmi gabungan Alokasi Pagu Anggaran dan Rincian Usulan Belanja. Dengan perbaikan ini, rekening berpagu yang belum diinput rinciannya oleh unit kerja akan tetap tercetak rapi, dan total pagu di footer menjadi genap **Rp 177.000.000.000**.

### 2.2 Opsi 2: Latar Belakang Sub-Unit
- **Pilihan "Dengan Latar Belakang" vs "Tanpa Latar Belakang"**:
  - **TIDAK TERPENGARUH SAMA SEKALI**.
  - Logika opsi latar belakang (`$includeBackground`) tetap bekerja seperti biasa untuk menampilkan atau menyembunyikan Section Latar Belakang Sub-Unit di bagian atas dokumen.

### 2.3 Opsi 3: Filter Scope Cetak (SANGAT PENTING)
- **Pilihan "Seluruh RSUD (Semua Unit & Op)" (Default / Tanpa Filter)**:
  - Ini adalah dokumen konsolidasi RSUD Kardinah secara utuh.
  - Rekening yang memiliki pagu (seperti `5.1.02.02.02.0005` sebesar Rp 100.000.000) **akan ikut tercantum**, sehingga total pagu final konsolidasi rumah sakit menjadi **Rp 177.000.000.000** (sinkron 100% dengan penetapan pagu global admin).
- **Pilihan "Filter Per Unit (Supervisor)"**:
  - **INTEGRITAS FILTER TETAP TERJAGA**.
  - Jika Administrator memfilter unit tertentu (misalnya *Unit Farmasi* saja), sistem **HANYA** akan menampilkan rekening-rekening yang memang memiliki usulan belanja dari Unit Farmasi!
  - Rekening `5.1.02.02.02.0005` (yang tidak pernah diusulkan oleh Unit Farmasi) **TIDAK AKAN disisipkan secara paksa** ke dalam cetakan Unit Farmasi. Dengan demikian, laporan per unit tidak akan tercemar oleh rekening kosong milik unit lain.
- **Pilihan "Filter Per Operator Spesifik" & "Kombinasi Unit + Operator"**:
  - Sama dengan filter per unit, hanya rekening yang relevan dengan operator/kombinasi terpilih yang akan ditampilkan.

---

## 3. Solusi Arsitektural yang Diusulkan

### 3.1 Alur Logika Controller:
Pada [`RbaHeaderController::printPreviewFinal`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php):
```php
$isFiltered = !empty($selectedUnitIds) || !empty($selectedOperatorIds);

// 1. Ambil seluruh ID rekening dari usulan belanja yang terfilter
$accountCodeIds = $details->pluck('account_code_id')->toArray();

// 2. HANYA JIKA scope adalah "Seluruh RSUD" (tanpa filter unit/operator),
// gabungkan juga rekening-rekening yang memiliki pagu final > 0
if (!$isFiltered) {
    $paguAccountIds = $pagus->filter(fn($p) => $p->nominal_pagu > 0)->keys()->toArray();
    $accountCodeIds = array_unique(array_merge($accountCodeIds, $paguAccountIds));
}

// 3. Muat master rekening terurut berdasarkan kodenya
$reportAccountCodes = \App\Models\AccountCode::whereIn('id', $accountCodeIds)->orderBy('code')->get();
```

### 3.2 Alur Tampilan View:
Pada [`resources/views/reports/admin_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php):
- Tabel utama mengiterasi `$reportAccountCodes`:
  - Jika rekening memiliki rincian usulan (`$itemDetails->isNotEmpty()`): tampilkan item-item rincian belanja seperti biasa.
  - Jika rekening memiliki pagu namun belum diusulkan (`$itemDetails->isEmpty()`):
    - Tampilkan baris rincian informatif: *(Belum ada rincian usulan belanja yang diinputkan unit kerja)*, volume `-`, total usulan `0`, pagu final `Rp 100.000.000`, dan status `Belum Usul`.
    - Subtotal rekening: Usulan `Rp 0`, Pagu Final `Rp 100.000.000`.
- Footer tabel:
  - Grand Total Usulan: **Rp 177.775.000.000**
  - Grand Total Pagu Final: **Rp 177.000.000.000** (Sinkron sempurna!).

---

## 4. User Review Required

> [!IMPORTANT]
> **Kepastian untuk Pengguna**:
> Perbaikan ini **TIDAK AKAN merusak atau mengubah cara kerja opsi cetak**:
> 1. Opsi Jenis Dokumen (Usulan vs RBA Final) tetap berfungsi normal.
> 2. Opsi Latar Belakang Sub-Unit (Dengan vs Tanpa) tetap berfungsi normal.
> 3. Opsi Filter Scope Cetak (Per Unit / Per Operator) tetap murni menyaring data unit/operator terkait, tanpa tercampur rekening kosong dari unit lain.
> 4. Hanya saat mencetak dokumen konsolidasi **"Seluruh RSUD"**, rekening berpagu yang belum ada usulannya akan dimunculkan agar total pagu dokumen menjadi tepat **Rp 177.000.000.000**.

---

## 5. Proposed Changes

### Backend & Controller

#### [MODIFY] [`RbaHeaderController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Pada method `printPreviewFinal`:
  - Hitung `$reportAccountCodes` dengan mempertimbangkan status filter (`$isFiltered`).
  - Teruskan `$reportAccountCodes` ke view `reports.admin_rba_final_print`.

---

### Frontend / Print Templates

#### [MODIFY] [`admin_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)
- Mengubah loop data table untuk mengiterasi `$reportAccountCodes`.
- Menambahkan penanganan baris informatif elegan untuk rekening berpagu yang belum memiliki `RbaDetail`.
- Memastikan `$grandTotalPaguFinal` mengakumulasi seluruh alokasi pagu pada scope Seluruh RSUD.

---

### Automated Testing

#### [MODIFY] [`AdminDashboardTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)
- Tambahkan unit test untuk:
  - Cetak RBA Final Seluruh RSUD mencakup rekening yang hanya punya pagu (total pagu klop).
  - Cetak RBA Final Filter Unit tertentu tetap hanya menampilkan usulan dari unit tersebut.
#### [MODIFY] [`PerformanceIndicatorTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PerformanceIndicatorTest.php)
- Selaraskan assertion judul halaman index `Target Indikator Kinerja RSUD Kardinah` agar 170 test suite lulus 100%.

---

## 6. Verification Plan

### Automated Tests
1. Jalankan test print preview admin:
   ```bash
   php artisan test --filter=AdminDashboardTest
   ```
2. Jalankan test performance indicator:
   ```bash
   php artisan test --filter=PerformanceIndicatorTest
   ```
3. Jalankan seluruh test suite:
   ```bash
   php artisan test
   ```

### Manual Verification
1. Jalankan script simulasi pratinjau cetak untuk Header 1 (2026 Murni):
   - **Scope Seluruh RSUD**:
     - Total Usulan: Rp 177.775.000.000
     - Total Pagu Final: Rp 177.000.000.000 (Selisih Rp 100.000.000 terselesaikan).
     - Rekening `5.1.02.02.02.0005` tercetak dengan status yang jelas.
   - **Scope Filter Per Unit**:
     - Pastikan rekening unit lain tidak ikut bocor ke cetakan unit tersebut.
