# Implementation Plan (Revisi): Delegasi Hak Akses Menu Tambahan bagi User oleh Administrator

Dokumen ini merupakan revisi yang memuat **analisis mendalam mengenai dampak penerapan delegasi hak akses menu terhadap sistem otorisasi eksisting** pada SIPAKAR RSUD Kardinah (khususnya role `Administrator`, `Supervisor`, dan `Operator`).

---

## 1. Analisis Dampak Terhadap Otorisasi Eksisting (Jawaban Atas Pertanyaan Pengguna)

> [!IMPORTANT]
> **Jawaban Tegas**: Penerapan delegasi hak akses menu ini **TIDAK AKAN MERUSAK, MENGGANGGU, ATAU MENGUBAH** sistem otorisasi role yang berjalan saat ini.
> Seluruh aturan otorisasi utama (`role:Administrator`, `role:Supervisor`, `role:Operator`, serta flag `can_propose`) akan **tetap berjalan 100% utuh persis seperti saat ini**.

### Mengapa Otorisasi Eksisting Sama Sekali Tidak Terpengaruh?

1. **Prinsip Arsitektur: *Role-Based Access Control (RBAC)* Tetap Sebagai Pilar Utama**:
   - Kolom `role` pada tabel `users` (`Administrator`, `Supervisor`, `Operator`) **tidak diubah nilainya**.
   - Middleware `CheckRole` (`role:Administrator`, `role:Supervisor`, `role:Operator`) tetap bertugas menjaga seluruh endpoint internal masing-masing role.
   - Akun Operator yang didelegasikan tugas mengisi master barang **tetap berstatus role `Operator`**. Akun tersebut **TIDAK** berubah menjadi Administrator dan **TIDAK** memperoleh hak admin lainnya.

2. **Mencegah Eskalasi Hak Istimewa (*No Privilege Escalation*)**:
   - Operator yang didelegasikan menu `master_barangs`:
     - **BISA**: Membuka, menambah, mengedit, dan mengaktifkan/menonaktifkan katalog Master Barang BMD.
     - **TETAP TIDAK BISA (403 Forbidden)**:
       - Mengakses Dashboard Administrator (`/admin/dashboard`)
       - Mengakses Manajemen Pengguna (`/admin/users`)
       - Mengakses Unit & Sub-Unit (`/admin/units`, `/admin/sub-units`)
       - Mengakses Rekening & Kelompok Belanja (`/admin/account-codes`, `/admin/kelompok-belanja`)
       - Mengakses Penetapan Pagu & Kunci RBA (`/admin/headers/*`)
       - Mengakses Log Data & Log API (`/admin/logs`, `/admin/api-logs`)
       - Mengakses Review RBA Supervisor (`/supervisor/*`)
   - Otorisasi menu-menu sensitif tersebut tetap terkunci rapat oleh middleware `role:Administrator` dan `role:Supervisor`.

3. **Perilaku Role Administrator**:
   - Administrator memegang hak akses absolut (*super-admin*).
   - Pada method `hasMenuPermission()`, sistem mengecek:
     `if ($this->role === 'Administrator') return true;`
   - Sehingga Administrator **secara otomatis memiliki akses penuh ke seluruh menu** tanpa perlu dicentang satu per satu.

4. **Perilaku Role Supervisor**:
   - Alur kerja Supervisor (validasi usulan unit, penolakan, penelaahan dokumen KAK/RTP, pencetakan laporan) tetap berjalan normal tanpa sedikit pun perubahan logika.
   - Supervisor hanya dapat didelegasikan menu jika Admin secara sadar mencentang izin delegasi pada akun Supervisor tersebut. Jika tidak dicentang, perilakunya 100% sama seperti sekarang.

5. **Perilaku Role Operator & Hak Pengusulan (`can_propose`)**:
   - Mekanisme pembedaan Operator Pengusul (`can_propose = 1`) vs Operator Peninjau / Viewer (`can_propose = 0`) tetap berlaku untuk modul Workboard RBA dan Permohonan RKBMD.
   - Hak delegasi menu adalah izin ortogonal (independen): baik operator pengusul maupun non-pengusul dapat didelegasikan tugas pengisian master barang tanpa mengganggu status usulan RBA sub-unitnya.

---

## 2. Matriks Komparasi Hak Akses (Sebelum vs Sesudah)

| Modul / Endpoint | Role Administrator | Role Supervisor | Operator Biasa (Tanpa Delegasi) | Operator Didelegasikan `master_barangs` |
| :--- | :---: | :---: | :---: | :---: |
| **Admin Dashboard & Users** | ✅ Full Akses | ❌ Ditolak (403) | ❌ Ditolak (403) | ❌ Ditolak (403) |
| **Kelompok Belanja & Akun** | ✅ Full Akses | ❌ Ditolak (403) | ❌ Ditolak (403) | ❌ Ditolak (403) |
| **Pagu & Kunci Header RBA** | ✅ Full Akses | ❌ Ditolak (403) | ❌ Ditolak (403) | ❌ Ditolak (403) |
| **Log Data & Audit Sistem** | ✅ Full Akses | ❌ Ditolak (403) | ❌ Ditolak (403) | ❌ Ditolak (403) |
| **Review RBA Supervisor** | ❌ (Khusus SPV) | ✅ Full Akses Unit | ❌ Ditolak (403) | ❌ Ditolak (403) |
| **Workboard & Usulan RBA** | ❌ | ❌ | ✅ Sesuai `can_propose` | ✅ Sesuai `can_propose` |
| **Permohonan RKBMD** | ✅ Monitor | ✅ Monitor | ✅ Ajukan / Tanggapi | ✅ Ajukan / Tanggapi |
| **Master Barang BMD (Katalog)**| ✅ **Full Akses** | ❌ **Ditolak (403)** | ❌ **Ditolak (403)** | ✅ **Full Akses (Didelegasikan)** |

---

## 3. Rencana Teknis Implementasi

### A. Database Migration
- Tambahkan kolom `menu_permissions` bertipe `JSON` (*nullable*) pada tabel `users` setelah kolom `can_propose`.
- Nilai default adalah `null` atau `[]`, sehingga akun yang sudah ada otomatis tidak memiliki delegasi menu sebelum diberikan oleh Admin.

### B. Eloquent Model `User`
- Cast: `'menu_permissions' => 'array'`.
- Tambahkan konstanta terpusat daftar menu yang dapat didelegasikan:
  ```php
  public const DELEGATABLE_MENUS = [
      'master_barangs' => [
          'label' => 'Master Barang BMD',
          'icon' => '📦',
          'route' => 'admin.master-barangs.index',
          'description' => 'Pengelolaan katalog Master Barang Milik Daerah (Permendagri No. 108/2016)',
      ],
  ];
  ```
- Method helper:
  - `hasMenuPermission(string $menuKey): bool`:
    - Return `true` jika role adalah `Administrator`.
    - Return `in_array($menuKey, $this->menu_permissions ?? [])` untuk role lainnya.
  - `getDelegatedMenus(): array`: mengembalikan array menu yang diizinkan untuk navigasi dinamis.

### C. Middleware Spesifik Menu (`CheckMenuPermission`)
- Buat middleware baru `App\Http\Middleware\CheckMenuPermission`:
  - Menerima parameter nama izin menu, contoh: `menu_permission:master_barangs`.
  - Memeriksa `$request->user()->hasMenuPermission($menuKey)`.
  - Jika lolos -> `$next($request)`.
  - Jika gagal -> `abort(403, 'Akses Ditolak: Anda tidak memiliki izin untuk mengakses menu ini.')`.
- Daftarkan alias `'menu_permission'` di `bootstrap/app.php`.

### D. Penyesuaian Rute (`routes/web.php`)
- Pindahkan rute `master-barangs` ke grup middleware `menu_permission:master_barangs`:
  ```php
  Route::middleware(['auth', 'menu_permission:master_barangs'])->prefix('admin')->name('admin.')->group(function () {
      Route::resource('master-barangs', \App\Http\Controllers\Admin\MasterBarangController::class);
  });
  ```
- Semua rute admin lainnya (`users`, `units`, `headers`, `logs`, dll.) **tetap berada di dalam grup `role:Administrator`**, menjamin keamanan absolut.

### E. Antarmuka Manajemen User (`admin/users/create.blade.php` & `edit.blade.php`)
- Tambahkan kartu konfigurasi checkbox **"🛡️ Delegasi Hak Akses Menu Tambahan"**.
- Admin dapat mencentang **📦 Master Barang BMD** untuk operator yang ditugaskan.
- Tampilkan badge menu delegasi pada tabel pengguna (`admin/users/index.blade.php`).

### F. Navigasi Pengguna (`navigation.blade.php`)
- Pada navigasi Operator (dan Supervisor), link menu **📦 Master Barang BMD** otomatis muncul di navbar desktop dan menu mobile apabila pengguna memiliki izin `hasMenuPermission('master_barangs')`.

---

## Proposed Changes

### Database & Migrations
#### [NEW] [2026_09_15_180000_add_menu_permissions_to_users_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_15_180000_add_menu_permissions_to_users_table.php)
- Menambahkan kolom `menu_permissions` bertipe JSON (*nullable*) pada tabel `users`.

### Eloquent Model
#### [MODIFY] [User.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/User.php)
- Tambahkan field ke `$fillable`, cast ke `array`, definisikan `DELEGATABLE_MENUS`, `hasMenuPermission()`, dan `getDelegatedMenus()`.

### Middleware & Bootstrap
#### [NEW] [CheckMenuPermission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Middleware/CheckMenuPermission.php)
- Middleware pengecekan hak akses menu delegasi.
#### [MODIFY] [bootstrap/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/bootstrap/app.php)
- Daftarkan alias `'menu_permission'`.

### Routes & Controller
#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Proteksi rute `master-barangs` dengan middleware `menu_permission:master_barangs`.
#### [MODIFY] [UserController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/UserController.php)
- Validasi dan penyimpanan `menu_permissions` pada `store()` dan `update()`.

### Blade Views
#### [MODIFY] [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/edit.blade.php)
- Form input checkbox delegasi menu.
#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php)
- Indikator badge menu delegasi pada tabel user.
#### [MODIFY] [navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
- Tampilkan menu delegasi di navbar Operator/Supervisor jika diizinkan.

---

## Verification Plan

### Automated Tests
1. `test_admin_can_delegate_master_barang_menu_to_operator`: Admin berhasil mendelegasikan izin menu ke akun operator.
2. `test_delegated_operator_can_access_and_manage_master_barangs`: Operator yang diberi izin berhasil membuka dan menginput master barang.
3. `test_operator_without_delegation_cannot_access_master_barangs_and_gets_403`: Operator tanpa izin delegasi tetap diblokir (HTTP 403).
4. `test_delegated_operator_cannot_access_other_admin_menus`: Memastikan operator yang didelegasikan master barang **tetap dilarang keras** membuka `/admin/users`, `/admin/units`, `/admin/logs`, dsb. (HTTP 403) — membuktikan tidak ada eskalasi hak akses role.
5. Menjalankan seluruh test suite aplikasi (227+ tests) untuk membuktikan **zero regression**.
