# Walkthrough - Fitur Cetak Rincian Belanja Usulan Tampilan Supervisor & Filter Operator

Penerapan fitur **Cetak Rincian Belanja Usulan pada Tampilan Supervisor** dengan opsi pencetakan fleksibel untuk menyajikan akumulasi RBA seluruh Operator atau difilter berdasarkan 1 maupun beberapa Operator terpilih.

---

## Changes Made

### 1. Controller & Routing Layer

#### [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak khusus Supervisor: `GET /supervisor/submissions/{submission}/print-preview` (`supervisor.submissions.print-preview`).

#### [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Menambahkan method `printPreview(Request $request, RbaSubmission $submission)` untuk:
  - Memverifikasi otorisasi unit Supervisor.
  - Memproses filter Latar Belakang (`include_background`).
  - Memproses filter array `operator_ids` (mencetak semua operator jika kosong/pilih semua, atau memfilter rincian hanya dari operator terpilih).
  - Menyusun label metadata filter operator untuk bagian header laporan.
  - Me-render view laporan `reports.supervisor_rba_print`.

---

### 2. View Layer (Supervisor Workboard)

#### [show.blade.php (Supervisor Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Menambahkan tombol **"🖨️ Cetak Rincian Usulan"** dan **Modal Konfigurasi Cetak Supervisor** berbasis Alpine.js:
  - Opsi 1: Latar Belakang Sub-Unit (Radio: Dengan / Tanpa Latar Belakang).
  - Opsi 2: Filter Operator Penyusun (Radio: Cetak Semua Operator vs Pilih Operator Spesifik).
  - List Checkbox Operator under Unit dengan shortcut interaktif *Pilih Semua* & *Reset*.

---

### 3. Report Template Layer

#### [supervisor_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_print.blade.php)
- Membuat Blade template cetak khusus tampilan Supervisor:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN (RBA)`.
  - Informasi Metadata Grid (Unit Kerja, Supervisor Reviu, Tanggal Cetak, Filter Operator, Opsi Cetak, Status Submisi).
  - Section I Latar Belakang Sub-Unit (Kondisional).
  - Section II Tabel Rincian Belanja dengan kolom khusus **OPERATOR PEMBUAT**.
  - Total Akumulasi Belanja & Pagu AWAL.
  - Lembar Pengesahan Tanda Tangan Supervisor & Penyusun RBA Sub-Unit.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

## Verification Results

### Automated Tests
Jalankan pengujian fitur Supervisor via Artisan Test:
```powershell
php artisan test --filter=ReviewTest
```

**Hasil Pengujian:**
```text
   PASS  Tests\Feature\Supervisor\ReviewTest
  ✓ supervisor can view their unit submissions                                                                   1.08s  
  ✓ supervisor can validate submission                                                                           0.03s  
  ✓ supervisor can see previous period pagu in awal column                                                       0.06s  
  ✓ supervisor can preview print report with operator filters                                                    0.28s  

  Tests:    4 passed (16 assertions)
  Duration: 1.68s
```

### Manual Verification
1. Login sebagai **Supervisor** dan masuk ke halaman Workboard Review Submisi (`/supervisor/submissions/{id}`).
2. Klik tombol **"🖨️ Cetak Rincian Usulan"**:
   - **Uji Cetak Semua Operator**: Pilih radio *Cetak Semua Operator*, klik *Buka Pratinjau Cetak*. Pastikan seluruh rincian belanja dari semua operator unit tampil lengkap.
   - **Uji Cetak 1 Operator**: Pilih radio *Pilih Operator Spesifik*, centang 1 operator saja, klik *Buka Pratinjau Cetak*. Pastikan hanya rincian dari operator terpilih yang muncul, dan header menampilkan nama operator tersebut.
   - **Uji Cetak Banyak Operator**: Centang beberapa operator spesifik. Pastikan rincian belanja terakumulasi dari operator yang dicentang.
3. Klik **Cetak Dokumen (Ctrl+P)** pada pratinjau cetak:
   - Memastikan tampilan cetak kertas A4 Landscape rapi, logo kop surat muncul, dan toolbar peramban otomatis tersembunyi dari cetakan.
