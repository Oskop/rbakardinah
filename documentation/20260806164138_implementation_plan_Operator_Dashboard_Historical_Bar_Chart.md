# Implementation Plan - Mode Grafik Batang Historis pada Operator Dashboard

Menambahkan fitur visualisasi **Grafik Batang Historis (Grouped Bar Chart)** pada bagian **Daftar RBA Historis** di Dashboard Operator (`resources/views/operator/dashboard.blade.php`). Fitur ini dilengkapi dengan tombol segmented toggle (*Mode Daftar* dan *Mode Grafik*) untuk memberikan insight mengenai fluktuasi total usulan belanja dan total pagu global antar-periode RBA dari waktu ke waktu.

## User Review Required

> [!IMPORTANT]
> - **Fitur Mode Switcher pada Daftar RBA Historis**:
>   - **Mode Daftar**: Menampilkan daftar kartu RBA Historis (urut terbaru ke tertua) beserta rincian breakdown operator saat diklik (tampilan eksisting).
>   - **Mode Grafik**: Menampilkan diagram grafik batang (*Grouped Bar Chart*) yang membandingkan **Total Usulan Belanja** (warna Indigo) dan **Total Pagu Global** (warna Emerald) untuk setiap periode RBA secara kronologis (dari periode tertua ke terbaru).
> - **Chart.js Bar Chart Integration**: Menggunakan Chart.js dengan formatting sumbu Y dan tooltip berformat mata uang Rupiah (Rp) yang dinamis.

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Data historis diambil dari koleksi `$rbaData` yang sudah ada pada `DashboardController.php`.

## Proposed Changes

### View Layer

#### [MODIFY] [dashboard.blade.php (Operator Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- Memperbarui state Alpine.js dengan menambahkan `historyViewMode: 'list'` dan instance `historyChartInstance: null`.
- Menambahkan method `renderHistoryBarChart()` pada Alpine.js yang mengurutkan data RBA secara kronologis dan menggambar grafik batang ganda (*Grouped Bar Chart*) untuk **Total Usulan Global** vs **Total Pagu Global**.
- Menyisipkan segmented control button (*Mode Daftar* vs *Mode Grafik*) pada header bagian **Daftar RBA Historis**.
- Menambahkan kondisial `x-show="historyViewMode === 'list'"` untuk daftar kartu RBA, dan `x-show="historyViewMode === 'chart'"` yang berisi elemen `<canvas x-ref="historyCanvas">` setinggi `400px`.

---

## Verification Plan

### Automated Tests
- Memperbarui / menambahkan test case pada `tests/Feature/Operator/OperatorDashboardTest.php` untuk memastikan route dashboard memuat data historis dan berjalan tanpa error.
- Menjalankan `php artisan test`.

### Manual Verification
- Login sebagai Operator.
- Buka Operator Dashboard (`/operator/dashboard`).
- Pada bagian **Daftar RBA Historis**, klik tombol mode **Grafik**.
- Pastikan diagram grafik batang ganda (*Bar Chart*) muncul secara responsif, menampilkan perbandingan **Total Usulan** vs **Total Pagu Global** antar-periode RBA dengan label Rupiah yang akurat.
- Klik kembali mode **Daftar** untuk memastikan peralihan tampilan berjalan mulus.
