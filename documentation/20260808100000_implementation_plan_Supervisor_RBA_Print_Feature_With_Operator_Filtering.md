# Implementation Plan - Fitur Cetak Rincian Belanja Usulan Tampilan Supervisor & Filter Operator

Menambahkan fitur **Cetak Rincian Belanja Usulan pada Tampilan Supervisor** dengan opsi pencetakan fleksibel yang memungkinkan Supervisor mencetak usulan RBA berdasarkan filter:
1. **Opsi Latar Belakang**: Dengan Latar Belakang vs Tanpa Latar Belakang.
2. **Opsi Filter Operator**:
   - **Semua Operator**: Mencetak akumulasi seluruh usulan rincian belanja dari semua Operator di bawah Supervisor tersebut.
   - **Pilih 1 Operator**: Mencetak usulan rincian belanja khusus dari 1 Operator tertentu.
   - **Pilih Banyak Operator (Multiple Operators)**: Mencetak usulan rincian belanja dari beberapa Operator terpilih.

---

## User Review Required

> [!IMPORTANT]
> **Detail Alur & Desain Tampilan Cetak Supervisor**:
> 1. **Modal Konfigurasi Cetak (Supervisor Workboard)**:
>    - Pada halaman `/supervisor/submissions/{id}`, disediakan tombol modal interaktif **"🖨️ Cetak Rincian Usulan"**.
>    - Form Modal menyediakan:
>      - Radio Button Opsi Latar Belakang (Dengan / Tanpa Latar Belakang).
>      - Radio Button Opsi Filter Operator ("Semua Operator" vs "Pilih Operator Tertentu").
>      - Checkbox list daftar nama Operator aktif di unit kerja tersebut (dengan tombol *Select All* / *Deselect All*).
> 2. **Layout & Informasi Laporan Cetak Supervisor**:
>    - **Kop Surat Resmi RSUD Kardinah**: Menampilkan Logo SIPAKAR / RSUD Kardinah (`asset('images/LogoSipakar.png')`).
>    - **Informasi Metadata Grid**: Menampilkan Unit Kerja, Periode RBA, Nama Supervisor, Filter Operator yang Dicetak (misal: *"Semua Operator (3 Operator)"* atau *"Dwi, Ahmad"*), Status Submisi, dan Tanggal Cetak.
>    - **Kolom Tabel Rincian Belanja**: Menambahkan kolom **OPERATOR / PEMBUAT** pada tabel data rincian belanja agar Supervisor dapat membedakan asal usulan belanja dari tiap Operator secara transparan.
>    - **Pengesahan / Sign-off**: Kolom tanda tangan resmi Supervisor / Atasan Sub-Unit dan Operator Penyusun RBA.
> 3. **Pratinjau Cetak Browser Interaktif**:
>    - Memanfaatkan sistem **Web Print Preview (`window.print()`)** yang responsif A4 Landscape, otomatis rapi, dan bersih dari toolbar peramban saat dicetak ke kertas / disimpan ke PDF.

---

## Proposed Changes

### Controller & Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak untuk Supervisor di bawah middleware `auth` dan `role:Supervisor`:
  - `GET /supervisor/submissions/{submission}/print-preview` $\rightarrow$ `Supervisor\ReviewController@printPreview`

#### [MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Menambahkan method `printPreview(Request $request, RbaSubmission $submission)`:
  - Memverifikasi otorisasi `unit_id` Supervisor.
  - Memproses parameter `include_background` (`1` atau `0`).
  - Memproses parameter `operator_ids` (array ID operator yang dipilih). Jika tidak diisi / memilih "Semua Operator", maka seluruh rincian belanja unit diikutsertakan.
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Menyusun label metadata filter operator yang dipilih.
  - Me-render view `reports.supervisor_rba_print`.

---

### View Layer (Supervisor Workboard)

#### [MODIFY] [show.blade.php (Supervisor Submissions)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Menambahkan tombol dan Modal Interaktif (Alpine.js) **"🖨️ Cetak Rincian Usulan"** pada header workboard Supervisor.
- Modal berisi:
  - Pilihan Latar Belakang (Radio Button: Ya / Tidak).
  - Pilihan Filter Operator (Radio Button: Semua Operator / Pilih Spesifik).
  - List Checkbox Operator under Unit dengan *Select All* interaktif.
  - Tombol **"🌐 Buka Pratinjau Cetak"** yang membuka hasil laporan di tab baru (`target="_blank"`).

---

### Report Template Layer

#### [NEW] [supervisor_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_print.blade.php)
- Membuat template laporan cetak khusus Supervisor:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Metadata laporan mencakup Unit Kerja, Periode, Supervisor, Daftar Filter Operator yang dicetak, Tanggal Cetak.
  - Section I Latar Belakang Sub-Unit (Kondisional).
  - Section II Tabel Rincian Belanja dengan kolom tambahan **OPERATOR PEMBUAT**.
  - Total Akumulasi Belanja & Pagu AWAL.
  - Lembar Pengesahan Tanda Tangan Supervisor & Operator.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

## Verification Plan

### Automated Tests
- Menambahkan test case baru pada `tests/Feature/Supervisor/SupervisorSubmissionsTest.php` atau `RbaDetailFeaturesTest.php`:
  - Memvalidasi Supervisor dapat meng-akses rute `supervisor/submissions/{submission}/print-preview`.
  - Memvalidasi filter `include_background=1` dan `include_background=0`.
  - Memvalidasi filter `operator_ids[]` (cetak 1 operator, cetak beberapa operator, dan cetak semua operator).
- Menjalankan tes Artisan:
  ```powershell
  php artisan test --filter=Supervisor
  ```

### Manual Verification
1. Login sebagai Supervisor.
2. Masuk ke halaman Workboard Review Submisi (`/supervisor/submissions/{id}`).
3. Klik tombol **"🖨️ Cetak Rincian Usulan"**:
   - Uji Opsi **Semua Operator**: Pastikan seluruh rincian dari semua operator di unit tampil di tabel.
   - Uji Opsi **Pilih 1 Operator**: Pilih 1 nama operator, pastikan hanya rincian belanja operator tersebut yang muncul.
   - Uji Opsi **Pilih Banyak Operator**: Centang 2 dari 3 operator, pastikan hanya rincian belanja dari operator terpilih yang muncul.
4. Klik **Cetak Dokumen (Ctrl+P)**:
   - Memastikan tampilan cetak kertas A4 Landscape rapi, logo kop surat muncul, dan toolbar tersembunyi.
