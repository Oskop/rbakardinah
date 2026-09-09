# Implementation Plan - Penyelidikan & Perbaikan Selisih Total Pagu RBA 2026 Murni

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

## 2. Solusi Arsitektural yang Diusulkan

Dokumen RBA Final Administrator adalah dokumen resmi gabungan Alokasi Pagu Anggaran dan Rincian Usulan Belanja RSUD.
Sebuah rekening yang telah dialokasikan pagu anggarannya oleh Tim Anggaran / Direktur RSUD (misalnya Rp 100.000.000 untuk Iuran Jaminan Kesehatan Non ASN) **tetap harus tercantum di laporan resmi RBA Final** meskipun unit kerja terkait belum/tidak menginput item rincian usulan belanja.

### 2.1 Alur Solusi:
1. **Controller ([`RbaHeaderController::printPreviewFinal`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php))**:
   - Ambil daftar seluruh kode rekening yang relevan:
     - Untuk cetak seluruh RSUD (tanpa filter unit/operator): gabungkan kode rekening yang ada di usulan belanja (`$details`) DENGAN kode rekening yang memiliki Pagu Final (`$pagus`).
     - Muat model `AccountCode` terurut berdasarkan kodenya (`orderBy('code')`).
     - Teruskan variabel `$reportAccountCodes` ke view.
2. **View Cetak ([`resources/views/reports/admin_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php))**:
   - Loop tabel utama diubah dari `@forelse($groupedDetails as ...)` menjadi `@forelse($reportAccountCodes as $accountCode)`.
   - Mengambil rincian belanja untuk rekening tersebut: `$itemDetails = $groupedDetails->get($accountCode->id, collect())`.
   - Menambahkan pagu ke akumulator total: `$grandTotalPaguFinal += $paguFinal`.
   - **Tampilan Baris Tabel**:
     - Jika rekening memiliki usulan belanja (`$itemDetails->isNotEmpty()`): tampilkan baris-baris rincian seperti biasa.
     - Jika rekening memiliki pagu namun belum ada rincian usulan belanja (`$itemDetails->isEmpty()`):
       - Header Group Rekening tetap tampil dengan nomor urut, kode rekening, nama rekening, dan kolom Pagu Final (Rp 100.000.000).
       - Baris rincian menampilkan keterangan informatif: *(Belum ada rincian usulan belanja yang diinputkan unit kerja)*, volume `-`, harga `-`, total usulan `0`, pagu final `Rp 100.000.000`, dan badge status `Belum Ada Usulan`.
       - Subtotal rekening menampilkan Usulan `Rp 0` dan Pagu Final `Rp 100.000.000`.
   - **Footer Grand Total**:
     - Grand Total Usulan: **Rp 177.775.000.000**
     - Grand Total Pagu Final: **Rp 177.000.000.000** (Sinkron 100% dengan tampilan Admin).

---

## 3. User Review Required

> [!NOTE]
> Dengan perbaikan ini, rekening `5.1.02.02.02.0005` (Belanja Iuran Jaminan Kesehatan bagi Non ASN) sebesar Rp 100.000.000 akan otomatis tercetak rapi di dokumen cetak RBA Final dengan status usulan `Belum Ada Usulan`, dan total pagu di footer cetak akan menjadi **Rp 177.000.000.000** persis sama dengan tampilan admin.

---

## 4. Proposed Changes

### Backend & Controller

#### [MODIFY] [`RbaHeaderController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Pada method `printPreviewFinal`:
  - Hitung kumpulan ID rekening:
    ```php
    $accountCodeIds = $details->pluck('account_code_id')->toArray();
    if (empty($selectedUnitIds) && empty($selectedOperatorIds)) {
        $paguAccountIds = $pagus->filter(fn($p) => $p->nominal_pagu > 0)->keys()->toArray();
        $accountCodeIds = array_unique(array_merge($accountCodeIds, $paguAccountIds));
    }
    $reportAccountCodes = \App\Models\AccountCode::whereIn('id', $accountCodeIds)->orderBy('code')->get();
    ```
  - Kirim `$reportAccountCodes` ke view `reports.admin_rba_final_print`.

---

### Frontend / Print Templates

#### [MODIFY] [`admin_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)
- Mengubah loop data table untuk mengiterasi `$reportAccountCodes`.
- Menambahkan penanganan baris kosong elegan untuk rekening berpagu yang belum memiliki `RbaDetail`.
- Memastikan `$grandTotalPaguFinal` mengakumulasi seluruh rekening berpagu.

---

### Automated Testing

#### [MODIFY] [`AdminDashboardTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)
- Tambahkan test case khusus yang menguji:
  - Header dengan rekening yang memiliki Pagu namun tidak memiliki usulan belanja.
  - Pastikan pada route `admin.headers.print-preview-final`, total pagu di cetakan mencakup nominal pagu rekening tersebut.
#### [MODIFY] [`PerformanceIndicatorTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PerformanceIndicatorTest.php)
- Selaraskan assertion judul halaman index `Target Indikator Kinerja RSUD Kardinah` agar 170 test suite lulus 100%.

---

## 5. Verification Plan

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
   - Verifikasi total usulan = Rp 177.775.000.000
   - Verifikasi total pagu final = Rp 177.000.000.000 (Selisih Rp 100.000.000 terselesaikan).
   - Verifikasi rekening `5.1.02.02.02.0005` tercetak dengan status yang jelas.
