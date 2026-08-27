# Implementation Plan - Penanganan Null Unit pada Halaman Operator Submissions & Sinkronisasi Unit SSO

Memperbaiki *error* `Attempt to read property "name" on null` pada `resources/views/operator/submissions/index.blade.php:4` saat pengguna yang baru pertama kali login via SIMRS SSO (dengan nilai `unit_id` masih `null`) mengakses halaman **Daftar Usulan RBA / Workboard RBA**, serta menambahkan mekanisme *auto-matching* unit kerja dari klaim token SIMRS jika tersedia.

---

## User Review Required

> [!IMPORTANT]
> **Akar Masalah & Rencana Penanganan:**
> 1. **Akar Masalah (*Root Cause*):**
>    - Pengguna baru hasil *Just-In-Time (JIT) Provisioning* SSO SIMRS memiliki `unit_id = null` (karena belum dipetakan ke unit kerja RSUD Kardinah).
>    - Pada `resources/views/operator/submissions/index.blade.php:4`, kode memanggil `Auth::user()->unit->name` secara langsung tanpa operator null-safe (`?->`), sehingga PHP melempar *fatal error* saat `unit` bernilai `null`.
> 2. **Langkah Solusi:**
>    - **Null-Safe Header & Banner Informasi:**
>      - Mengubah header menjadi `{{ Auth::user()->unit?->name ?? 'Belum Ditugaskan ke Unit' }}`.
>      - Menampilkan kartu peringatan ramah bagi pengguna tanpa unit: *"Akun Anda belum terhubung ke Unit Kerja. Silakan hubungi Administrator untuk menetapkan Unit Kerja Anda sebelum menginput usulan RBA."*
>      - Menggunakan `@forelse` pada tabel usulan agar menampilkan pesan status yang jelas ketika daftar usulan kosong.
>    - **Peningkatan Service SSO (`SimrsOidcService`):**
>      - Menambahkan logika *auto-matching* unit pada saat login SSO: jika klaim token SIMRS (`id_token` / payload) memuat informasi unit (`unit`, `unit_name`, `kode_unit`, `nama_unit`, `departemen`, `instalasi`, `kd_unit`), sistem akan otomatis mencari unit yang cocok di SIPAKAR dan langsung mengaitkan `unit_id`.
>    - **Null-Safe Audit di Seluruh View Terkait:**
>      - Memastikan seluruh view pendukung (seperti riwayat dokumen dan laporan) menggunakan null-safe operator `?->` pada relasi `unit`.

---

## Proposed Changes

### 1. Tampilan Operator Submissions (`resources/views/operator/submissions/index.blade.php`)

#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/index.blade.php)
- Mengubah baris 4:
  ```blade
  {{ __('Daftar Usulan RBA') }} - {{ Auth::user()->unit?->name ?? 'Belum Ditugaskan ke Unit' }}
  ```
- Menambahkan kartu *alert* pemberitahuan di atas tabel jika `!Auth::user()->unit_id`:
  ```blade
  @if(!Auth::user()->unit_id)
      <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl text-amber-800 text-xs shadow-sm">
          <div class="flex items-start gap-2.5">
              <span class="text-xl">⚠️</span>
              <div>
                  <h3 class="font-bold text-amber-900 text-sm">Akun Anda Belum Terhubung ke Unit Kerja</h3>
                  <p class="mt-1 leading-relaxed text-amber-700">
                      Akun Anda aktif, namun Administrator belum menetapkan penugasan <strong>Unit Kerja</strong> untuk Anda di SIPAKAR. Silakan hubungi Administrator untuk mengatur unit kerja Anda agar dapat mengelola usulan RBA.
                  </p>
              </div>
          </div>
      </div>
  @endif
  ```
- Mengubah perulangan `@foreach` menjadi `@forelse` dengan pesan kosong yang deskriptif.

---

### 2. Service Layer SSO (`app/Services/Auth/Oidc/SimrsOidcService.php`)

#### [MODIFY] [SimrsOidcService.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Services/Auth/Oidc/SimrsOidcService.php)
- Menambahkan logika *Auto Unit Resolver*:
  - Memeriksa apakah klaim OIDC memuat nama/kode unit.
  - Jika ada, mencari kecocokan pada model `Unit` (`where('code', $unitClaim)->orWhere('name', 'like', "%{$unitClaim}%")->first()`).
  - Mengaitkan `unit_id` secara otomatis jika ditemukan.

---

### 3. Tampilan Lain Terkait Unit (`resources/views/operator/documents/history.blade.php`)

#### [MODIFY] [history.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/documents/history.blade.php)
- Memastikan null-safe `{{ $submission->unit?->name ?? 'Unit' }}`.

---

### 4. Pengujian Otomatis (`tests/Feature/Operator/RbaDetailTest.php` & `tests/Feature/Auth/SimrsSsoTest.php`)

#### [MODIFY] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)
- Menambahkan pengujian:
  1. `test_sso_user_without_unit_id_can_access_submissions_index_without_error`: Memastikan user SSO dengan `unit_id = null` dapat mengakses `GET /operator/submissions` tanpa error HTTP 500 (mengembalikan HTTP 200 OK dengan alert belum terhubung unit).
  2. `test_sso_user_with_unit_claim_is_automatically_mapped_to_unit`: Memastikan klaim unit SIMRS otomatis memetakan `unit_id` yang sesuai di database.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite SSO OIDC & Operator:
  `php artisan test --filter=SimrsSsoTest`
  `php artisan test --filter=RbaDetailTest`
- Menjalankan keseluruhan test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login dengan akun SSO tanpa penugasan unit:
   - Akses URL `/operator/submissions`.
   - Pastikan halaman terbuka dengan mulus (HTTP 200), header menampilkan `Daftar Usulan RBA - Belum Ditugaskan ke Unit`, dan muncul kartu peringatan yang ramah.
2. Login sebagai Administrator:
   - Buka menu **Users**, edit akun pegawai SSO tersebut dan pilih Unit Kerjanya (misal: Instalasi Rawat Jalan).
   - Login kembali sebagai pegawai SSO tersebut dan buka `/operator/submissions`.
   - Pastikan header menampilkan nama unit kerja yang benar dan daftar usulan RBA unit tersebut muncul.
