# Implementation Plan - Fitur Cetak Rincian Belanja Usulan Tampilan Administrator (Filter Supervisor & Operator)

Menambahkan fitur **Cetak Rincian Belanja Usulan pada Tampilan Administrator** saat membuka RBA Header (`/admin/headers/{header}`). Fitur cetak ini dilengkapi dengan konfigurasi pencetakan tingkat lanjut yang memungkinkan Administrator memfilter usulan rincian belanja berdasarkan:
1. **Opsi Latar Belakang**: Dengan Latar Belakang vs Tanpa Latar Belakang.
2. **Opsi Filter Unit / Supervisor & Operator**:
   - **Cetak Semua (Seluruh Rumah Sakit)**: Mencetak akumulasi seluruh usulan rincian belanja dari semua Unit Kerja, Supervisor, dan Operator.
   - **Filter Berdasarkan Sub-Unit / Supervisor**: Mencetak usulan rincian belanja dari 1 atau beberapa Unit/Supervisor terpilih.
   - **Filter Berdasarkan Operator Spesifik**: Mencetak usulan rincian belanja khusus dari 1 atau beberapa Operator terpilih.
   - **Kombinasi Fleksibel**: Memilih Unit/Supervisor tertentu sekaligus menambahkan Operator spesifik dari luar unit tersebut.

---

## User Review Required

> [!IMPORTANT]
> **Detail Alur & Desain Tampilan Cetak Administrator**:
> 1. **Modal Konfigurasi Cetak (Admin RBA View)**:
>    - Pada halaman `/admin/headers/{id}`, disediakan tombol modal **"🖨️ Cetak Rincian Usulan"**.
>    - Form Modal menyediakan:
>      - Radio Button Opsi Latar Belakang (Dengan / Tanpa Latar Belakang).
>      - Radio Button Mode Filter:
>        - *Seluruh Rumah Sakit (Semua Unit & Operator)*
>        - *Pilih Unit / Supervisor Spesifik*
>        - *Pilih Operator Spesifik*
>        - *Kombinasi Unit & Operator*
>      - Checkbox list Unit Kerja (Supervisor) & Checkbox list Operator dengan tombol *Select All* / *Reset*.
> 2. **Layout & Informasi Laporan Cetak Administrator**:
>    - **Kop Surat Resmi RSUD Kardinah**: Menampilkan Logo SIPAKAR / RSUD Kardinah (`asset('images/LogoSipakar.png')`).
>    - **Informasi Metadata Grid**: Menampilkan Tahun Anggaran RBA, Periode, Administrator Pengelola, Filter Cetak yang Dipilih, Tanggal Cetak, dan Status Global.
>    - **Tabel Rincian Belanja Komprehensif**: Menampilkan kolom **UNIT KERJA** dan **OPERATOR PEMBUAT** agar Administrator dapat memantau asal usulan tiap baris belanja lintas unit secara transparan.
>    - **Pengesahan / Sign-off**: Kolom tanda tangan resmi Direktur / Administrator RSUD Kardinah dan Tim Penyusun RBA.
> 3. **Pratinjau Cetak Browser Interaktif**:
>    - Memanfaatkan sistem **Web Print Preview (`window.print()`)** yang responsif A4 Landscape, rapi, dan otomatis menyembunyikan toolbar peramban saat dicetak ke kertas / disimpan ke PDF.

---

## Proposed Changes

### Controller & Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan rute cetak Administrator di bawah middleware `auth` dan `role:Administrator`:
  - `GET /admin/headers/{header}/print-preview` $\rightarrow$ `RbaHeaderController@printPreview`

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Menambahkan method `printPreview(Request $request, RbaHeader $header)`:
  - Memuat data unit, supervisor, dan operator di bawah header tersebut.
  - Memproses parameter `include_background` (`1` atau `0`).
  - Memproses parameter filter `unit_ids[]` dan `operator_ids[]`.
  - Mengambil data Rincian Belanja (`RbaDetail`) yang sesuai dengan filter (bisa gabungan `unit_ids` dan `operator_ids`).
  - Memuat data pagu AWAL periode sebelumnya (`$previousPagus`).
  - Menyusun label metadata filter yang dipilih untuk bagian header laporan.
  - Me-render view `reports.admin_rba_print`.

---

### View Layer (Admin RBA Header View)

#### [MODIFY] [show.blade.php (Admin Headers)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Menambahkan tombol dan Modal Interaktif (Alpine.js) **"🖨️ Cetak Rincian Usulan"** pada header halaman detail RBA Admin.
- Modal berisi:
  - Pilihan Latar Belakang (Radio Button: Ya / Tidak).
  - Pilihan Mode Filter (Radio Button: Semua / Per Unit / Per Operator / Kombinasi).
  - List Checkbox Unit Kerja (Supervisor) & List Checkbox Operator (dikelompokkan per unit) lengkap dengan shortcut *Pilih Semua* & *Reset*.
  - Tombol **"🌐 Buka Pratinjau Cetak"** yang membuka hasil laporan di tab baru (`target="_blank"`).

---

### Report Template Layer

#### [NEW] [admin_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_print.blade.php)
- Membuat template laporan cetak khusus Administrator:
  - Kop Surat RSUD Kardinah & Logo SIPAKAR (`asset('images/LogoSipakar.png')`).
  - Judul Resmi `USULAN RINCIAN RENCANA BELANJA DAN ANGGARAN (RBA)`.
  - Metadata laporan mencakup Tahun Anggaran, Periode, Administrator, Filter Unit & Operator yang dicetak, Status Global RBA, Tanggal Cetak.
  - Section I Latar Belakang (Kondisional).
  - Section II Tabel Rincian Belanja dengan kolom **UNIT KERJA** dan **OPERATOR PEMBUAT**.
  - Total Akumulasi Belanja & Pagu AWAL Global / Filtered.
  - Lembar Pengesahan Tanda Tangan Direktur / Administrator & Tim Anggaran.
  - Web Print Toolbar (`.no-print-bar`) dengan tombol Kembali dan Cetak Browser (`window.print()`).

---

## Verification Plan

### Automated Tests
- Menambahkan test case baru pada `tests/Feature/Admin/RbaHeaderTest.php` atau `AdminDashboardTest.php`:
  - Memvalidasi Administrator dapat meng-akses rute `admin/headers/{header}/print-preview`.
  - Memvalidasi filter `include_background=1` dan `include_background=0`.
  - Memvalidasi filter `unit_ids[]` dan `operator_ids[]` (cetak seluruh RS, per unit, per operator, dan kombinasi).
- Menjalankan tes Artisan:
  ```powershell
  php artisan test --filter=Admin
  ```

### Manual Verification
1. Login sebagai Administrator.
2. Masuk ke halaman Detail RBA Header (`/admin/headers/{id}`).
3. Klik tombol **"🖨️ Cetak Rincian Usulan"**:
   - Uji Opsi **Seluruh Rumah Sakit**: Pastikan seluruh rincian belanja dari semua unit dan operator tampil.
   - Uji Opsi **Per Unit / Supervisor**: Pilih 1 atau 2 unit, pastikan rincian belanja khusus dari unit terpilih yang muncul.
   - Uji Opsi **Per Operator**: Pilih beberapa nama operator spesifik lintas unit, pastikan rincian belanja operator tersebut yang muncul.
   - Uji Opsi **Kombinasi**: Pilih 1 unit supervisor sekaligus 1 operator dari unit lain.
4. Klik **Cetak Dokumen (Ctrl+P)**:
   - Memastikan tampilan cetak kertas A4 Landscape rapi, logo kop surat muncul, dan toolbar tersembunyi.
