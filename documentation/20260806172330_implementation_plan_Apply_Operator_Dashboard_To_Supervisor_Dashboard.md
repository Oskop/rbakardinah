# Implementation Plan - Penerapan Daftar RBA Historis dan Detail RBA pada Dashboard Supervisor

Menerapkan secara sempurna fitur **Daftar RBA Historis** (Mode Daftar dengan pencarian & Mode Grafik Batang) serta **Detail Workspace RBA** (Tabel Breakdown Operator, Summary Cards Horizontal, dan Diagram Chart Pie) dari Dashboard Operator ke Dashboard Supervisor (`supervisor.dashboard`).

## User Review Required

> [!IMPORTANT]
> - **Controller Layer (`DashboardController` untuk Supervisor)**:
>   - Membuat `App\Http\Controllers\Supervisor\DashboardController.php` (atau mengarahkan rute `supervisor.dashboard` ke controller tersebut) untuk mengambil seluruh `$rbaData` (RBA Header, periode, pagu global, akumulasi usulan belanja per operator, dan proporsi usulan).
> - **View Layer (`resources/views/supervisor/dashboard.blade.php`)**:
>   - Menerapkan komponen **Daftar RBA Historis**:
>     - Mode Switcher (Mode Daftar vs Mode Grafik Batang Historis).
>     - Search box RBA dengan ikon 16px presisi dan scrollable container `max-h-[500px]`.
>     - Grouped Bar Chart (Total Usulan vs Total Pagu Global) berformat Rupiah.
>   - Menerapkan komponen **Detail Workspace RBA**:
>     - Header Info RBA terpilih dengan tombol navigasi cepat (Review Usulan & Kelola User).
>     - **Summary Cards Horizontal** (`grid-cols-1 sm:grid-cols-3`): Total Usulan Global, Total Pagu Global, dan Jumlah Operator Berkontribusi.
>     - Segmented Toggle Mode View (Tabel Breakdown Operator vs Diagram Chart Pie).
>     - Tabel 6 Kolom dengan akumulasi `tfoot` dan Diagram Pie proporsi usulan per operator.

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Data RBA yang ditampilkan pada dashboard supervisor diselaraskan sepenuhnya dengan struktur data RBA operator.

## Proposed Changes

### Controller Layer

#### [NEW] [DashboardController.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/DashboardController.php)
- Membawa logika pemrosesan `$rbaData` (mengambil seluruh header, menghitung total usulan global, total pagu global, breakdown usulan per operator beserta persentase kontribusi).
- Mengirim data `$rbaData` ke view `supervisor.dashboard`.

#### [MODIFY] [web.php (Routes)](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Memperbarui rute `supervisor.dashboard` untuk memanggil `[\App\Http\Controllers\Supervisor\DashboardController::class, 'index']`.

### View Layer

#### [MODIFY] [dashboard.blade.php (Supervisor Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/dashboard.blade.php)
- Menyusun ulang halaman dengan komponen Alpine.js dan Chart.js lengkap persis seperti Dashboard Operator:
  - Header Slot dengan tombol "Review Usulan RBA" & "Kelola User".
  - Banner Selamat Datang Supervisor.
  - Sisi Kiri (4 Cols): Daftar RBA Historis (Mode Daftar + Search Box `max-h-[500px]` & Mode Grafik Batang).
  - Sisi Kanan (8 Cols): Detail RBA Workspace (Header RBA, 3 Summary Cards Horizontal `sm:grid-cols-3`, Mode Switcher Tabel vs Chart Pie, Tabel Breakdown 6 Kolom + Footer Akumulasi, dan Pie Chart).

### Test Layer

#### [NEW] [SupervisorDashboardTest.php](file:///c:/Users/PC12/Project/rbakardinah/tests/Feature/Supervisor/SupervisorDashboardTest.php)
- Menambahkan pengujian `SupervisorDashboardTest` untuk memverifikasi supervisor dapat mengkases dashboard, melihat daftar RBA historis, dan breakdown usulan operator.

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan 100% test suite lulus tanpa hambatan.

### Manual Verification
- Login sebagai Supervisor.
- Akses Dashboard Supervisor (`/supervisor/dashboard`).
- Pastikan tampilan **Daftar RBA Historis** (sisi kiri) dan **Detail RBA** (sisi kanan) identik secara visual dan fungsional dengan Dashboard Operator (termasuk mode daftar pencarian RBA, grafik batang historis, 3 summary cards horizontal, dan diagram chart pie).
