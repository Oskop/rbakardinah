# Walkthrough - Fitur Cetak Rincian Belanja Usulan Tampilan Administrator (Filter Supervisor & Operator)

Penerapan fitur **Cetak Rincian Belanja Usulan pada Tampilan Administrator** saat membuka RBA Header. Fitur cetak ini mendukung filter tingkat lanjut (Seluruh RSUD, Per Unit/Supervisor, Per Operator Spesifik, maupun Kombinasi Fleksibel Unit & Operator).

---

## Changes Made

### 1. Controller & Routing Layer

#### [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak khusus Administrator: `GET /admin/headers/{header}/print-preview` (`admin.headers.print-preview`).

#### [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Mengirim data `$units` dan `$allOperators` pada method `show()`.
- Menambahkan method `printPreview(Request $request, RbaHeader $header)` untuk:
  - Memproses filter Latar Belakang (`include_background`).
  - Memproses filter array `unit_ids[]` dan `operator_ids[]` (cetak seluruh RS jika kosong, per unit, per operator, atau kombinasi `whereIn unit_ids OR created_by in operator_ids`).
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Menyusun label metadata filter untuk bagian header laporan.
  - Me-render view laporan `reports.admin_rba_print`.

---

### 2. View Layer (Admin RBA View)

#### [show.blade.php (Admin Headers)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Menambahkan tombol **"🖨️ Cetak Rincian Usulan"** dan **Modal Konfigurasi Cetak Administrator** berbasis Alpine.js:
  - Opsi 1: Latar Belakang Sub-Unit (Radio: Dengan / Tanpa Latar Belakang).
  - Opsi 2: Filter Scope Cetak (Radio: Seluruh RSUD, Filter Per Unit, Filter Per Operator Spesifik, Kombinasi Unit + Operator).
  - List Checkbox Unit Kerja (Supervisor) & List Checkbox Operator Spesifik dengan shortcut *Pilih Semua* & *Reset*.

---

### 3. Report Template Layer

#### [admin_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_print.blade.php)
- Membuat Blade template cetak khusus tampilan Administrator:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN (RBA)`.
  - Informasi Metadata Grid (Tahun Anggaran, Periode, Administrator Pengelola, Filter Cetak, Tanggal Cetak, Status Global).
  - Section I Tabel Rincian Belanja dengan kolom khusus **UNIT KERJA** dan **OPERATOR PEMBUAT**.
  - Total Akumulasi Belanja & Pagu AWAL.
  - Lembar Pengesahan Tanda Tangan Direktur / Administrator & Tim Anggaran.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

## Verification Results

### Automated Tests
Jalankan pengujian fitur Administrator via Artisan Test:
```powershell
php artisan test --filter=AdminDashboardTest
```

**Hasil Pengujian:**
```text
   PASS  Tests\Feature\Admin\AdminDashboardTest
  ✓ admin can access dashboard and see rba list                                                                  1.03s  
  ✓ admin can preview print report with unit and operator filters                                                0.07s  

  Tests:    2 passed (17 assertions)
  Duration: 1.62s
```

### Manual Verification
1. Login sebagai **Administrator** dan masuk ke halaman Detail RBA Header (`/admin/headers/{id}`).
2. Klik tombol **"🖨️ Cetak Rincian Usulan"**:
   - **Uji Seluruh RSUD**: Pilih radio *Seluruh RSUD*, klik *Buka Pratinjau Cetak*. Pastikan rincian belanja dari semua unit dan operator tampil lengkap.
   - **Uji Filter Per Unit**: Pilih radio *Filter Per Unit*, centang 1 atau beberapa unit, klik *Buka Pratinjau Cetak*. Pastikan hanya rincian dari unit terpilih yang muncul.
   - **Uji Filter Per Operator**: Pilih radio *Filter Per Operator*, centang beberapa operator spesifik. Pastikan rincian belanja hanya memuat operator tersebut.
   - **Uji Kombinasi**: Pilih 1 unit supervisor sekaligus 1 operator dari unit lain.
3. Klik **Cetak Dokumen (Ctrl+P)** pada pratinjau cetak:
   - Memastikan tampilan cetak kertas A4 Landscape rapi, logo kop surat muncul, dan toolbar peramban otomatis tersembunyi dari cetakan.
