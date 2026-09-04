# Implementation Plan: Menu Laporan RBA Terintegrasi Multi-Role

Pembuatan menu baru **Laporan (Reports)** yang dapat diakses oleh seluruh tingkatan peran pengguna (**Administrator**, **Supervisor**, dan **Operator**) untuk mencetak dokumen RBA (Usulan Rincian Belanja & RBA Final dengan Pagu) secara terpusat dengan opsi filter yang disesuaikan secara otomatis menurut peran masing-masing, serta terintegrasi penuh (*single source of truth*) dengan fitur cetak yang sudah ada di setiap halaman periode RBA.

---

## 1. Konteks & Analisis Arsitektur

Saat ini, fitur cetak RBA sudah tersedia di masing-masing halaman kerja per periode:
1. **Administrator** (`/admin/headers/{header}`): Modal konfigurasi cetak dengan pilihan Jenis Laporan (Usulan / Final), Latar Belakang (Ya / Tidak), dan Scope Cetak (Seluruh RSUD, Filter Unit, Filter Operator) yang mengarah ke endpoint `admin.headers.print-preview` dan `admin.headers.print-preview-final`.
2. **Supervisor** (`/supervisor/submissions/{submission}`): Modal konfigurasi cetak dengan pilihan Jenis Laporan (Usulan / Final), Latar Belakang (Ya / Tidak), dan Filter Operator Penyusun di unitnya yang mengarah ke endpoint `supervisor.submissions.print-preview` dan `supervisor.submissions.print-preview-final`.
3. **Operator** (`/operator/submissions/{submission}`): Pilihan cetak Usulan / Final dengan/tanpa latar belakang yang mengarah ke endpoint `operator.submissions.print-preview` dan `operator.submissions.print-preview-final`.

### Kebutuhan Pengguna
- Menambahkan menu baru **Laporan** pada navigasi utama untuk semua level user.
- Memungkinkan user memilih periode RBA dan mencetak dengan opsi yang sesuai dengan hak akses level user tersebut.
- **Prinsip Terintegrasi & Tersinkronisasi**:
  Menu Laporan tidak membuat duplikasi logika query atau duplikasi tampilan template cetak. Sebaliknya, Menu Laporan memanfaatkan langsung (*single source of truth*) endpoint dan view cetak yang sudah ada (`admin.headers.print-preview[-final]`, `supervisor.submissions.print-preview[-final]`, `operator.submissions.print-preview[-final]`). Jika di masa depan dilakukan penyesuaian template cetak (misalnya kop surat, tanda tangan, layout tabel landscape, atau rumus), perubahannya langsung berlaku secara identik baik dari halaman RBA periode maupun dari menu Laporan.

---

## 2. Rincian Hak Akses & Perilaku per Role pada Menu Laporan

| Peran Pengguna | Pilihan Periode RBA | Opsi Dokumen & Latar Belakang | Opsi Filter Scope | Target Endpoint Cetak |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Seluruh Header/Periode RBA di RSUD Kardinah (aktif maupun riwayat) | - Usulan Belanja vs RBA Final (Pagu)<br>- Dengan / Tanpa Latar Belakang | - Seluruh RSUD (Semua Unit & Op)<br>- Filter Unit Kerja Tertentu<br>- Filter Operator Tertentu | `admin.headers.print-preview`<br>`admin.headers.print-preview-final` |
| **Supervisor** | Periode RBA yang memiliki berkas *submission* di unit kerja supervisor terkait | - Usulan Belanja vs RBA Final (Pagu)<br>- Dengan / Tanpa Latar Belakang | - Semua Operator (Akumulasi Unit)<br>- Pilih Operator Tertentu (Multi-select) | `supervisor.submissions.print-preview`<br>`supervisor.submissions.print-preview-final` |
| **Operator** | Periode RBA yang memiliki berkas *submission* di unit kerja operator terkait | - Usulan Belanja vs RBA Final (Pagu)<br>- Dengan / Tanpa Latar Belakang | Usulan diri sendiri di unit kerjanya (otomatis terikat dengan akun login) | `operator.submissions.print-preview`<br>`operator.submissions.print-preview-final` |

---

## 3. Rencana Perubahan Komponen

### A. Routing & Controller Baru
#### [NEW] [app/Http/Controllers/ReportController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/ReportController.php)
- Controller universal yang menangani halaman menu Laporan (`/reports`).
- Method `index(Request $request)`:
  - Memeriksa role pengguna aktif (`Administrator`, `Supervisor`, `Operator`).
  - Mengambil data periode yang relevan untuk role tersebut.
  - Untuk Administrator: Mengambil data `RbaHeader` (terurut tahun & id desc), daftar `Unit`, dan daftar `User` (Operator aktif).
  - Untuk Supervisor: Mengambil data `RbaSubmission` milik `unit_id` supervisor, serta daftar `User` (Operator aktif) di unit tersebut.
  - Untuk Operator: Mengambil data `RbaSubmission` milik `unit_id` operator.
  - Mendukung pre-selection periode jika URL memiliki parameter query `?header_id=...` atau `?submission_id=...`.

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Mendaftarkan route:
  ```php
  Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])
      ->middleware(['auth'])
      ->name('reports.index');
  ```

---

### B. Antarmuka Pengguna (UI / Views)
#### [NEW] [resources/views/reports/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/index.blade.php)
- Halaman antarmuka Pusat Laporan & Cetak RBA yang modern dan elegan:
  - **Banner / Header**: Judul halaman "Pusat Laporan & Cetak RBA" dengan indikator peran aktif user.
  - **Layout 2-Kolom / Grid Interaktif**:
    - **Kolom Kiri - Pemilihan Periode RBA**:
      - Daftar kartu periode RBA atau dropdown interaktif Alpine.js.
      - Menampilkan Tahun Anggaran, Nama Periode (Murni/Perubahan), Tanggal/Status.
      - Tombol cepat / link: "Lihat Detail RBA" (langsung membuka halaman detail RBA periode tersebut jika ingin melihat rincian sebelum mencetak).
    - **Kolom Kanan - Panel Konfigurasi Cetak (Reaktif Alpine.js)**:
      - Form dinamis yang target `:action`-nya berubah secara otomatis sesuai role, periode yang dipilih, dan tipe cetak (`usulan` atau `final`).
      - Pilihan Jenis Laporan: Usulan Rincian Belanja vs RBA Final (dengan Pagu).
      - Pilihan Latar Belakang: Dengan Latar Belakang vs Tanpa Latar Belakang.
      - Bagian Filter Scope:
        - **Admin**: Radio scope All / Unit / Operator beserta checklist dinamis.
        - **Supervisor**: Radio scope Semua Operator / Operator Tertentu beserta checklist dinamis operator unit.
        - **Operator**: Info alert bahwa dokumen dicetak khusus usulan operator yang bersangkutan.
      - Tombol Aksi: **🖨️ Buka Pratinjau Cetak** (membuka jendela cetak HTML di tab baru `target="_blank"`).

#### [MODIFY] [resources/views/layouts/navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Menambahkan tautan menu **Laporan** pada navigasi desktop:
  - Administrator: Berada di antara *RBA Headers* dan *Log Data*.
  - Supervisor: Berada di antara *Review RBA* dan *Users*.
  - Operator: Berada di antara *Workboard RBA* dan *Dokumentasi*.
- Menambahkan tautan menu **Laporan** pada navigasi mobile responsive untuk ketiga role tersebut.
- Penanda link aktif menggunakan `request()->routeIs('reports.*')`.

#### [MODIFY] [resources/views/operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Menyelaraskan tombol opsi cetak pada halaman RBA Operator agar menggunakan modal konfigurasi modern (Alpine.js) yang konsisten dengan modal Admin dan Supervisor, serta menyertakan tautan cepat ke Menu Laporan.

---

### C. Pengujian & Otomasi (Tests)
#### [NEW] [tests/Feature/General/ReportMenuTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/ReportMenuTest.php)
- Test case 1: Pengunjung tanpa autentikasi (guest) otomatis diarahkan ke halaman login saat membuka `/reports`.
- Test case 2: Administrator dapat membuka `/reports` dan melihat daftar header RBA serta opsi filter seluruh unit dan operator.
- Test case 3: Supervisor dapat membuka `/reports` dan melihat daftar submission unitnya serta daftar operator di unitnya.
- Test case 4: Operator dapat membuka `/reports` dan melihat daftar submission unitnya.
- Test case 5: Form submission dari menu laporan dapat mengakses endpoint cetak (`print-preview` dan `print-preview-final`) dengan sukses (status 200) untuk masing-masing role.

---

## 4. Rencana Verifikasi

1. **Pengujian Fungsional Web**:
   - Memastikan navigasi menu **Laporan** muncul di navbar desktop dan responsive mobile untuk role Administrator, Supervisor, dan Operator.
   - Memastikan saat menu diklik, halaman `/reports` terbuka dengan opsi yang sesuai role.
   - Menguji pemilihan periode dan pemilihan opsi cetak (Usulan vs Final, Dengan/Tanpa Latar Belakang, Filter Scope) lalu mengklik tombol "Buka Pratinjau Cetak".
   - Memastikan dokumen cetak terbuka dengan sempurna di tab baru (`target="_blank"`) tanpa mengubah atau merusak tata letak cetak yang sudah ada.
2. **Kompilasi Aset Frontend**:
   - Menjalankan `bun run build` untuk memvalidasi sintaks Blade dan Vite assets.
3. **Automated Unit & Feature Tests**:
   - Menjalankan `php artisan test --filter=ReportMenuTest`.
   - Menjalankan keseluruhan test suite aplikasi (`php artisan test`) untuk memastikan seluruh 139+ test cases lulus 100%.

---

## 5. Pertanyaan & Masukan Pengguna (User Review)

Rencana implementasi di atas telah dirancang untuk memenuhi seluruh kebutuhan:
- Menu baru "Laporan" dapat diakses semua level user.
- Mengadopsi opsi-opsi cetak yang sesuai dengan peran masing-masing.
- Terintegrasi penuh (*single source of truth*) dengan alur cetak yang ada di halaman RBA periode.

Mohon konfirmasi dan persetujuan untuk melanjutkan pengerjaan sesuai rencana ini.
