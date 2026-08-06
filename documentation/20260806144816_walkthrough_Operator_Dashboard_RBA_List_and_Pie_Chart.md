# Walkthrough - Operator Dashboard RBA List and Pie Chart Detail View

Implementasi daftar RBA historis (diurutkan terbaru ke tertua) pada Dashboard Operator beserta tampilan detail usulan per unit yang dapat beralih secara interaktif antara format **Tabel** dan **Chart Pie**.

## Changes Made

### Controller Layer
- **`app/Http/Controllers/Operator/DashboardController.php`**:
  - Mengambil daftar `RbaHeader` beserta relasi `period`, `submissions.unit`, `submissions.details`, dan `accountPagus`.
  - Mengurutkan `RbaHeader` secara descending (`year` desc, `id` desc) sehingga RBA terbaru selalu muncul paling atas.
  - Menghitung agregat `total_usulan_global`, `total_pagu_global`, serta rincian usulan & pagu per unit untuk setiap header RBA.

### Routing Layer
- **`routes/web.php`**:
  - Mengarahkan `route('operator.dashboard')` ke `App\Http\Controllers\Operator\DashboardController@index`.
  - Memperbarui route `/dashboard` agar secara otomatis mengarahkan peran (role) Operator ke Dashboard Operator.

### View Layer & Frontend
- **`resources/views/operator/dashboard.blade.php`**:
  - Menambahkan CDN Chart.js v4.x.
  - Mengintegrasikan state Alpine.js (`selectedRba`, `viewMode: 'table'|'chart'`).
  - **Daftar RBA Historis**: Kartu/list interaktif menampilkan Tahun RBA, Tipe RBA, Total Usulan (Semua Unit), Pagu Global, dan status global.
  - **Detail Usulan RBA**: Panel detail yang menampilkan nama unit, status submisi unit, total usulan belanja per unit, dan pagu unit.
  - **View Mode Switcher**: Toggle interaktif yang dapat berpindah secara mulus antara tampilan **Tabel** dan **Chart Pie**.
  - **Chart Pie**: Diagram lingkaran interaktif (Chart.js) dengan palet warna modern, persentase proporsi, serta tooltip berformat Rupiah (`Rp`).

### Automated Testing
- **`tests/Feature/Operator/OperatorDashboardTest.php`**:
  - `test_operator_can_access_dashboard_and_see_rba_list`: Memvalidasi akses HTTP 200, view template, serta pengiriman variabel `rbaData`.
  - `test_generic_dashboard_route_redirects_operator_to_operator_dashboard`: Memvalidasi pengalihan otomatis role Operator.

---

## Verification Results

### Automated Tests
Menjalankan `php artisan test`:
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Feature\Admin\PaguTest
   PASS  Tests\Feature\Auth\AuthenticationTest
   PASS  Tests\Feature\Auth\EmailVerificationTest
   PASS  Tests\Feature\Auth\PasswordConfirmationTest
   PASS  Tests\Feature\Auth\PasswordResetTest
   PASS  Tests\Feature\Auth\PasswordUpdateTest
   PASS  Tests\Feature\Auth\RegistrationTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\General\HistoryTest
   PASS  Tests\Feature\Operator\OperatorDashboardTest
  ✓ operator can access dashboard and see rba list
  ✓ generic dashboard route redirects operator to operator dashboard
   PASS  Tests\Feature\Operator\RbaDetailFeaturesTest
   PASS  Tests\Feature\Operator\RbaDetailTest
   PASS  Tests\Feature\ProfileTest
   PASS  Tests\Feature\Supervisor\ReviewTest

  Tests:    46 passed (126 assertions)
  Duration: 17.00s
```
 Seluruh 46 pengujian lulus 100% tanpa kendala.
