# Implementation Plan - Operator Dashboard RBA List and Pie Chart Detail View

Implementasi daftar RBA historis (diurutkan dari yang terbaru) pada Dashboard Operator beserta tampilan detail usulan per unit yang dapat beralih antara format Tabel dan Chart Pie.

## User Review Required

> [!IMPORTANT]
> - **Operator Dashboard (`/operator/dashboard`)**: Menampilkan daftar seluruh `RbaHeader` (Tahun RBA, Tipe RBA/Periode, Total Usulan Belanja Seluruh Unit, dan Total Pagu Global) yang diurutkan dari yang paling baru.
> - **Detail RBA**: Mengklik item RBA pada daftar akan membuka tampilan detail berupa usulan RBA per unit (Total Usulan Belanja per Unit & Pagu Unit).
> - **Format Tampilan Detail**: Menyediakan toggle interaktif untuk berpindah antara tampilan **Tabel** dan **Chart Pie** (menggunakan Chart.js v4 via CDN).

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Perhitungan pagu unit diselaraskan dengan standar aplikasi, yaitu akumulasi nominal pagu dari kode rekening yang diusulkan oleh unit tersebut dalam RBA Header terkait.

## Proposed Changes

### Controller Layer

#### [NEW] [DashboardController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DashboardController.php)
- Membuat `App\Http\Controllers\Operator\DashboardController`.
- Mengambil daftar `RbaHeader` beserta relasi `period`, `submissions.unit`, `submissions.details`, dan `accountPagus`.
- Mengurutkan `RbaHeader` terbaru di atas (`orderByDesc('year')`, `orderByDesc('id')`).
- Menghitung agregat data:
  - Tahun RBA
  - Tipe RBA (`period->name`)
  - Total usulan belanja dari semua unit
  - Pagu global RBA
  - Breakdown usulan & pagu per unit untuk modal/detail

### Routing Layer

#### [MODIFY] [web.php](file:///c:/Users/PC12/Project/rbakardinah/routes/web.php)
- Mengubah route `operator.dashboard` agar menggunakan `[DashboardController::class, 'index']`.

### View Layer & Frontend

#### [MODIFY] [dashboard.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- Menambahkan script CDN Chart.js.
- Mengintegrasikan Alpine.js state (`selectedRba`, `viewMode: 'table'|'chart'`).
- Membuat card/tabel daftar RBA historis dengan indikator visual dan tombol detail.
- Membuat modal/section detail RBA yang menampilkan daftar unit, total usulan per unit, dan pagu unit.
- Menambahkan tab switcher antara format Tabel dan Chart Pie.
- Merender Chart Pie responsif menggunakan Chart.js yang menampilkan distribusi usulan belanja per unit dengan format nominal Rupiah (Rp).

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan semua fitur tetap aman.
- Membuat unit/feature test `tests/Feature/Operator/OperatorDashboardTest.php` untuk memvalidasi akses route dashboard operator dan struktur data yang dikirim.

### Manual Verification
- Login sebagai akun Operator.
- Masuk ke Dashboard Operator.
- Memastikan daftar RBA ditampilkan dari tahun terbaru ke paling lama.
- Memastikan angka Total Usulan Semua Unit dan Pagu Global sesuai.
- Klik item RBA untuk melihat Detail Usulan per Unit.
- Uji perpindahan mode dari Tabel ke Chart Pie dan sebaliknya.
