# Implementation Plan - Revisi UI/UX Detail RBA Dashboard Operator (Breakdown per Operator)

Rencana ini merevisi tata letak dan pengalaman pengguna (UI/UX) pada Dashboard Operator agar penyajian daftar RBA dan detail usulan per **Operator** terasa sangat intuitif, nyaman, dan modern.

---

## User Review Required

> [!IMPORTANT]
> - **Breakdown per Operator**: Detail usulan RBA menampilkan akumulasi usulan belanja per **Operator** (`created_by` / pembuat rincian usulan) beserta informasi unitnya.
> - **Pengalaman Pengguna (UI/UX) Intuitif**:
>   1. **Master-Detail Workspace Layout**: RBA historis ditampilkan dalam bentuk kartu ringkasan visual yang responsif. Mengklik salah satu kartu RBA akan secara halus (*smooth scroll & focus animation*) membuka **Workspace Detail RBA**.
>   2. **Top Metric Cards & Progress Share**: Pada detail RBA, ditampilkan ringkasan total usulan seluruh operator, total pagu global, jumlah operator berkontribusi, serta persentase kontribusi usulan per operator.
>   3. **Pill-Style Segmented Control**: Tombol switch antara **Tabel Operator** dan **Chart Pie** dibuat dalam format *Segmented Control* yang modern dan responsif.
>   4. **Format Tabel Intuitive**: Tabel dilengkapi dengan avatar inisial operator, badge unit, jumlah item, total usulan, pagu, dan *progress bar* proporsi usulan.
>   5. **Chart Pie Responsive**: Diagram pie interaktif Chart.js yang dilengkapi legend informasi persentase dan format nominal Rupiah (Rp).

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Data agregasi dikelompokkan berdasarkan `created_by` (ID Pengguna Operator) dalam header RBA terkait.

---

## Proposed Changes

### Controller Layer

#### [MODIFY] [DashboardController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DashboardController.php)
- Memuat relasi `details.creator.unit` dan `accountPagus` pada `RbaHeader`.
- Mengelompokkan `details` berdasarkan `created_by` (ID Operator) untuk setiap RBA Header.
- Menghitung agregat data per Operator:
  - `operator_id` & `operator_name`
  - `unit_name` & `unit_code`
  - `total_usulan` (total nominal usulan belanja operator ini)
  - `total_pagu` (total nominal pagu akun yang diusulkan oleh operator ini)
  - `item_count` (jumlah rincian belanja)
  - `percentage_share` (persentase terhadap total usulan RBA)

---

### View Layer & Frontend UI/UX

#### [MODIFY] [dashboard.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- **Desain Layout Dashboard & Interaktivitas**:
  - **Banner / Welcome Section**: Ringkasan status dan panduan singkat navigasi.
  - **Grid Kartu RBA Historis**: Kartu RBA dengan badge status (`Draft`/`Locked`), indikator nominal usulan & pagu global, serta tombol pemicu *"Lihat Breakdown Operator"*.
  - **Focus Workspace Detail RBA**:
    - Memiliki indikator RBA aktif yang sedang dilihat.
    - Dilengkapi tombol navigasi cepat (Tabel vs Chart Pie) menggunakan Alpine.js.
    - **Tampilan Tabel Operator**:
      - Kolom: Operator (Avatar Inisial + Nama), Unit, Jumlah Item, Total Usulan Belanja, Pagu terkait, dan visual *Progress Bar* kontribusi usulan.
    - **Tampilan Chart Pie**:
      - Diagram lingkaran interaktif Chart.js yang menampilkan distribusi usulan per Operator dengan tooltip nominal Rupiah (Rp) dan persentase kontribusi.

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan semua pengujian (termasuk `OperatorDashboardTest`) berjalan 100% lulus.

### Manual Verification
- Login sebagai pengguna Operator.
- Buka `/operator/dashboard`.
- Klik salah satu kartu RBA: pastikan halaman berpindah/berfokus secara mulus ke detail workspace RBA tersebut.
- Uji perpindahan tab antara tampilan **Tabel Operator** dan **Chart Pie**.
- Verifikasi akurasi angka total usulan per Operator, persentase kontribusi, dan format nominal Rupiah (Rp).
