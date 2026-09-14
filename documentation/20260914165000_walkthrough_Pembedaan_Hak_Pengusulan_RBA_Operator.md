# Walkthrough: Pembedaan Hak Pengusulan RBA Operator (Pengusul vs Viewer)

Implementasi fitur **Pembedaan Hak Pengusulan RBA pada Akun Operator** telah selesai dieksekusi secara menyeluruh dengan status pengujian **100% PASS (217 test cases)** tanpa regresi (*Zero Regression*).

Fitur ini membagi peran Operator menjadi dua kategori:
1. **Operator Pengusul (PIC RBA)**: Berwenang menginput rincian belanja, mengisi latar belakang spesifik, mengunggah dokumen (KAK/RAK/RTP), dan mengajukan usulan ke Supervisor.
2. **Operator Peninjau (Viewer / Non-Pengusul)**: Berwenang memantau progres usulan, melihat status validasi pagu dan review supervisor, serta mencetak laporan sub-unit/unitnya, namun seluruh tombol aksi penambahan, pengubahan, penghapusan, dan pengunggahan diblokir baik di UI maupun di level Policy/Gate backend (HTTP 403).

---

## 1. Perubahan Struktur Data & Database

### A. Migrasi Database
- File: [`database/migrations/2026_09_14_000001_add_can_propose_to_users_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_14_000001_add_can_propose_to_users_table.php)
- Menambahkan kolom boolean `can_propose` pada tabel `users` dengan default `true` (setelah kolom `jabatan`):
  ```php
  $table->boolean('can_propose')->default(true)->after('jabatan');
  ```
- Migrasi telah berhasil dieksekusi via `php artisan migrate`.

### B. Model User
- File: [`app/Models/User.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/User.php)
- Menambahkan `'can_propose'` ke dalam `$fillable`.
- Menambahkan cast `'can_propose' => 'boolean'`.
- Menambahkan helper method `isProposer()`:
  ```php
  public function isProposer(): bool
  {
      return $this->role !== 'Operator' || (bool) $this->can_propose;
  }
  ```
  *(Catatan: Akun Administrator & Supervisor selalu memiliki hak operasional tingkat atas, sehingga `isProposer()` otomatis bernilai `true`).*

---

## 2. Keamanan & Otorisasi Backend (Layer Security)

### A. RbaDetailPolicy
- File: [`app/Policies/RbaDetailPolicy.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Policies/RbaDetailPolicy.php)
- Menambahkan method `before()` untuk memblokir seluruh aksi mutasi data belanja (`create`, `update`, `delete`, `uploadVersion`, `submit`) bagi operator non-pengusul:
  ```php
  public function before(User $user, string $ability): ?\Illuminate\Auth\Access\Response
  {
      if ($user->role === 'Operator' && !$user->isProposer()) {
          return \Illuminate\Auth\Access\Response::deny('Anda tidak memiliki hak akses untuk mengusulkan RBA (Mode Peninjau / Viewer).');
      }
      return null;
  }
  ```

### B. Controller Operator
- **[`DetailController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**:
  - `create()`: Ditambahkan pengecekan `!Auth::user()->isProposer() -> abort(403)`.
  - `store()`: Dilindungi oleh `Gate::authorize('create', ...)` yang memicu `RbaDetailPolicy::before()`.
- **[`SubmissionController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)**:
  - `show()`: Mendukung operator peninjau untuk melihat rincian belanja rekan di sub-unitnya / unitnya.
  - `submit()`: Proteksi `!Auth::user()->isProposer() -> abort(403)`.
  - `updateBackground()`: Proteksi `!Auth::user()->isProposer() -> abort(403)`.
  - `printPreview()` & `printPreviewFinal()`: Disesuaikan agar operator peninjau dapat mencetak laporan usulan sub-unitnya.
- **[`DocumentController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DocumentController.php)**:
  - `uploadDocument()`: Proteksi `!Auth::user()->isProposer() -> abort(403)`.

### C. AppServiceProvider
- File: [`app/Providers/AppServiceProvider.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Providers/AppServiceProvider.php)
- Mendefinisikan gate global `propose-rba`:
  ```php
  Gate::define('propose-rba', function (User $user) {
      return $user->isProposer();
  });
  ```

---

## 3. Antarmuka Pengguna (UI / Blade Views)

### A. Operator Submission View
- File: [`resources/views/operator/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- **Header Aksi**: Tombol `+ Tambah Rincian` hanya tampil untuk pengusul (`isProposer()`). Bagi viewer, tampil badge penanda:
  `👁️ Mode Peninjau (Hanya Lihat)`
- **Kartu Latar Belakang**: Form input/textarea latar belakang disembunyikan untuk viewer dan digantikan oleh kartu ringkasan read-only.
- **Tabel Rincian Belanja**: Kolom Aksi bagi viewer menampilkan badge khusus `👁️ Hanya Lihat` menggantikan tombol Edit, Upload PDF, dan Ajukan.
- **Upload Dokumen KAK/RAK/RTP**: Form upload disembunyikan bagi viewer.

### B. Admin & Supervisor User Management Views
- **Admin Create & Edit User**:
  - Files: [`admin/users/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/create.blade.php) & [`admin/users/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/edit.blade.php)
  - Ditambahkan form switch/checkbox `can_propose` lengkap dengan penjelasan fungsional.
  - Checkbox otomatis muncul saat role "Operator" dipilih dan tersembunyi saat role lain dipilih.
  - File [`admin/users/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/users/index.blade.php): Ditambahkan sub-badge status `✍️ Pengusul (PIC)` vs `👁️ Viewer` pada kolom Role.
- **Supervisor Create & Edit User**:
  - Files: [`supervisor/users/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/users/create.blade.php) & [`supervisor/users/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/users/edit.blade.php)
  - Ditambahkan checkbox `can_propose` (default checked pada create, dan mengikuti data user pada edit).
  - File [`supervisor/users/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/users/index.blade.php): Ditambahkan sub-badge status `✍️ Pengusul (PIC)` vs `👁️ Viewer`.

---

## 4. Hasil Verifikasi & Automated Testing

Telah dibuat automated test suite khusus: [`tests/Feature/Operator/OperatorProposerPermissionTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/OperatorProposerPermissionTest.php) yang mencakup 11 skenario pengujian spesifik.

### Ringkasan Eksekusi Pengujian:
```bash
php artisan test
```
```text
   PASS  Tests\Feature\Admin\SubUnitManagementTest (7 tests)
   PASS  Tests\Feature\Admin\UserManagementTest (2 tests)
   ...
   PASS  Tests\Feature\Operator\OperatorDashboardTest (3 tests)
   PASS  Tests\Feature\Operator\OperatorProposerPermissionTest (11 tests)
     ✓ proposer operator can access create detail page
     ✓ viewer operator cannot access create detail page and gets 403
     ✓ proposer operator can store detail
     ✓ viewer operator cannot store detail and gets 403
     ✓ proposer operator can update background
     ✓ viewer operator cannot update background and gets 403
     ✓ proposer operator can submit submission to supervisor
     ✓ viewer operator cannot submit submission and gets 403
     ✓ viewer operator cannot upload document and gets 403
     ✓ viewer operator can view submission and print preview
     ✓ supervisor can create and update operator with can propose flag
   PASS  Tests\Feature\Operator\RbaDetailFeaturesTest (8 tests)
   PASS  Tests\Feature\Operator\RbaDetailTest (13 tests)
   PASS  Tests\Feature\Supervisor\ReviewTest (12 tests)
   PASS  Tests\Feature\Supervisor\UserManagementTest (2 tests)
   ...
  Tests:    217 passed (1069 assertions)
  Duration: 43.09s
```

Seluruh **217 skenario pengujian aplikasi berstatus PASS (100% Hijau)** tanpa regresi.
