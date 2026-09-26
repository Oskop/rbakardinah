# Walkthrough: Opsi Pengurutan Kolom pada Cetak Rincian Belanja & Pagu Final (RBA)

Dokumen ini memverifikasi dan mendokumentasikan implementasi fitur pemilihan pengurutan kolom (*column sorting*) pada fitur cetak **Rincian Belanja** dan **Pagu Final (RBA)** di seluruh level pengguna (**Operator**, **Supervisor**, **Admin**) dan pada **Menu Laporan**.

---

## 1. Ringkasan Perubahan

Fitur pengurutan kolom telah berhasil diintegrasikan dengan arsitektur terpusat, fleksibel, dan memiliki jaminan **Zero Regression** (tidak merusak alur cetak dan filter yang sudah ada).

### A. Backend Architecture & Service Terpusat
Dibuat kelas layanan baru:
- [`app/Services/ReportSortingService.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/ReportSortingService.php)
  - **`sortDetails($details, $sortBy, $sortDir)`**: Mengurutkan koleksi rincian belanja (`RbaDetail`) dengan penanganan komparasi numerik vs string yang presisi.
  - **`sortGroupedDetails($groupedDetails, $sortBy, $sortDir)`**: Mengurutkan koleksi grup akun dan baris rincian di dalam masing-masing grup (format terkelompok).
  - **`sortAccountCodes($accountCodes, $sortBy, $sortDir)`**: Mengurutkan master kode akun pada tampilan agregat Admin RBA Final.
  - **`getSortLabel($sortBy, $sortDir)`**: Menghasilkan deskripsi label pengurutan ramah pengguna (misalnya: *"Nomor Rekening Belanja (A - Z / Terkecil ke Terbesar)"*).

### B. Controller Integration
Ketiga controller cetak telah diselaraskan untuk menerima query parameter `sort_by` dan `sort_dir`, dengan nilai default `account_code` ASC:
1. [`app/Http/Controllers/Operator/SubmissionController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php) (`printPreview` & `printPreviewFinal`)
2. [`app/Http/Controllers/Supervisor/ReviewController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php) (`printPreview` & `printPreviewFinal`)
3. [`app/Http/Controllers/RbaHeaderController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php) (`printPreview` & `printPreviewFinal`)

---

## 2. Opsi Kolom & Arah Pengurutan yang Didukung

| Parameter `sort_by` | Nama Kolom Tampilan | Tipe Data Pengurutan | Keterangan |
| :--- | :--- | :--- | :--- |
| **`account_code`** *(Default)* | Nomor Rekening Belanja | Natural String (`strnatcasecmp`) | Sesuai permintaan pengguna sebagai default |
| **`account_name`** | Nama Rekening Belanja | String (`strcasecmp`) | Mengurutkan berdasarkan nama akun |
| **`description`** | Uraian Belanja | String (`strcasecmp`) | Mengurutkan berdasarkan rincian usulan |
| **`total_request`** | Total Usulan (Rp) | Numerik (`floatval`) | Berdasarkan nilai nominal ajuan belanja |
| **`final_pagu`** | Pagu Final / Nominal Rekomendasi (Rp) | Numerik (`floatval`) | Digunakan pada cetak RBA Final |
| **`unit_price`** | Harga Satuan (Rp) | Numerik (`floatval`) | Berdasarkan harga per satuan |
| **`volume`** | Volume | Numerik (`floatval`) | Berdasarkan jumlah kuantitas usulan |
| **`operator`** | Operator / Pengusul | String (`strcasecmp`) | Berguna pada view multi-operator |
| **`unit`** | Unit Kerja | String (`strcasecmp`) | Berguna pada cetak kompilasi Admin |

Arah Pengurutan (`sort_dir`):
- `asc` *(Default)*: Terkecil ke Terbesar / A ke Z
- `desc`: Terbesar ke Terkecil / Z ke A

---

## 3. Titik Akses Pengguna & Tampilan UI

### 1. Menu Laporan ([`resources/views/reports/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/index.blade.php))
- Ditambahkan kartu form terintegrasi **"Opsi Pengurutan Kolom Data"** dengan icon fa-sort.
- Pilihan Kolom dan Arah Pengurutan disertakan secara otomatis ke form cetak RBA Usulan maupun RBA Final.

### 2. Modal Cetak Supervisor ([`resources/views/supervisor/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php))
- Form modal cetak telah ditambahkan section pilihan kolom dan arah pengurutan dengan default Nomor Rekening (A - Z).

### 3. Modal Cetak Admin ([`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php))
- Form modal cetak Admin (Preview Cetak Dokumen RBA) dilengkapi opsi kolom pengurutan dan arah pengurutan.

### 4. Halaman Operator ([`resources/views/operator/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php))
- **Non-Breaking Quick Links**: Dropdown cetak cepat langsung (RBA & RBA Final) tetap ada dan berfungsi dengan default `account_code` ASC.
- **Modal Konfigurasi Cetak Operator**: Tombol baru *"Cetak dengan Pengurutan Khusus"* yang membuka modal interaktif untuk memilih format (Terkelompok / Rincian Bebas), Kolom Pengurutan, Arah Pengurutan, dan opsi Tampilkan Latar Belakang.

### 5. Template Dokumen Cetak
Seluruh 6 template cetak:
- [`resources/views/reports/operator_rba_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_print.blade.php)
- [`resources/views/reports/operator_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/operator_rba_final_print.blade.php)
- [`resources/views/reports/supervisor_rba_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_print.blade.php)
- [`resources/views/reports/supervisor_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php)
- [`resources/views/reports/admin_rba_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_print.blade.php)
- [`resources/views/reports/admin_rba_final_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_final_print.blade.php)

Sekarang menampilkan metadata informatif di bagian header laporan:
```html
<div class="filter-badge">
    <strong>Urutan Data:</strong> {{ $sortLabel ?? 'Nomor Rekening Belanja (Terkecil ke Terbesar / A - Z)' }}
</div>
```

---

## 4. Hasil Pengujian & Jaminan Zero Regression

Pengujian otomatis mencakup skenario baru dan seluruh fitur yang sudah ada.

### Test Kasus Baru ([`tests/Feature/General/ReportSortingTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/ReportSortingTest.php))
1. `✓ default sorting is account code asc` - Memastikan saat tidak ada parameter sort, urutan otomatis sesuai `account_code` ASC.
2. `✓ sorting by nominal request desc` - Memastikan pengurutan berdasarkan `total_request` DESC meletakkan nominal terbesar di awal.
3. `✓ sorting by description asc` - Memastikan pengurutan alfabetis uraian belanja bekerja dengan benar.
4. `✓ sorting by pagu final desc in rba final` - Memastikan dokumen RBA Final terurut sesuai nominal pagu rekomendasi terbesar ke terkecil.
5. `✓ all pages contain sorting controls` - Memastikan input selector `sort_by` dan `sort_dir` tersedia di menu laporan, modal supervisor, modal admin, dan modal operator.

### Hasil Eksekusi Regression Test Suite
```bash
php artisan test --filter="ReviewTest|RbaDetailFeaturesTest|AdminDashboardTest|ReportMenuTest|ReportSortingTest"
```
**Hasil**: **39 PASSED (249 assertions)**.
- `Tests\Feature\Admin\AdminDashboardTest`: 8 passed
- `Tests\Feature\General\ReportMenuTest`: 5 passed
- `Tests\Feature\General\ReportSortingTest`: 5 passed
- `Tests\Feature\Operator\RbaDetailFeaturesTest`: 9 passed
- `Tests\Feature\Supervisor\ReviewTest`: 12 passed

Seluruh fungsionalitas eksisting berjalan 100% normal tanpa ada efek samping.
