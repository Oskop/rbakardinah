# Implementation Plan - Penanganan Null Unit pada Halaman Operator Submissions (Revisi)

Memperbaiki *error* `Attempt to read property "name" on null` pada `resources/views/operator/submissions/index.blade.php:4` saat pengguna yang baru pertama kali login via SIMRS SSO (dengan nilai `unit_id` masih `null`) mengakses halaman **Daftar Usulan RBA / Workboard RBA**, serta menampilkan instruksi ramah bagi pengguna untuk menghubungi Administrator agar ditetapkan unit kerjanya.

---

## User Review Required

> [!IMPORTANT]
> **Alur Penugasan Unit Kerja Pegawai Baru:**
> 1. **Kebijakan Penugasan Unit:**
>    - Sesuai arahan pengguna, *auto-matching* unit dari SSO **ditiadakan**.
>    - Pegawai baru yang pertama kali login via SSO akan dibuatkan akun dengan `unit_id = null`.
>    - Pegawai akan melihat banner informasi di halaman usulan untuk **menghubungi Administrator**.
>    - Administrator kemudian menetapkan unit kerja pegawai secara manual dan terverifikasi melalui menu **Users** (`/admin/users`).
> 2. **Penyelesaian Error (*Null-Safety*):**
>    - Seluruh pemanggilan properti relasi `unit` pada view diubah menjadi aman dengan operator null-safe (`?->`) dan nilai fallback yang jelas:
>      - `Auth::user()->unit?->name ?? 'Belum Ditugaskan ke Unit'`
>    - Menampilkan kartu informasi peringatan ramah saat `!Auth::user()->unit_id`.
>    - Menggunakan `@forelse` pada tabel usulan dengan pesan kosong yang informatif.

---

## Proposed Changes

### 1. Tampilan Operator Submissions (`resources/views/operator/submissions/index.blade.php`)

#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/index.blade.php)
- Mengubah baris 4:
  ```blade
  {{ __('Daftar Usulan RBA') }} - {{ Auth::user()->unit?->name ?? 'Belum Ditugaskan ke Unit' }}
  ```
- Menambahkan kartu informasi peringatan di atas tabel saat `!Auth::user()->unit_id`:
  ```blade
  @if(!Auth::user()->unit_id)
      <div class="mb-6 p-5 bg-amber-50 border-l-4 border-amber-500 rounded-r-2xl shadow-sm text-amber-900">
          <div class="flex items-start gap-3">
              <span class="text-2xl">⚠️</span>
              <div>
                  <h3 class="font-bold text-sm text-amber-900">Akun Anda Belum Terhubung ke Unit Kerja</h3>
                  <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                      Akun Anda telah berhasil diaktifkan, namun Administrator belum menetapkan <strong>Unit Kerja</strong> untuk Anda di sistem SIPAKAR. Silakan hubungi Administrator untuk menetapkan penugasan unit kerja Anda agar dapat mulai menginput dan mengelola usulan rincian belanja RBA.
                  </p>
              </div>
          </div>
      </div>
  @endif
  ```
- Mengubah perulangan tabel `@foreach` menjadi `@forelse` agar menampilkan baris kosong informatif ketika pengguna belum memiliki penugasan unit atau belum ada usulan yang tersedia.

---

### 2. Tampilan Terkait Lainnya (Audit Null-Safety)

#### [MODIFY] [history.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/documents/history.blade.php)
- Memastikan null-safe: `{{ $submission->unit?->name ?? 'Unit' }}`.

#### [MODIFY] [show.blade.php (Admin Headers)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Memastikan null-safe pada rendering unit operator: `{{ $submission->unit?->name ?? '-' }}`.

---

### 3. Pengujian Otomatis (`tests/Feature/Auth/SimrsSsoTest.php`)

#### [MODIFY] [SimrsSsoTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Auth/SimrsSsoTest.php)
- Menambahkan pengujian:
  - `test_sso_user_without_unit_id_can_access_submissions_index_without_error`: Memastikan pengguna SSO baru dengan `unit_id = null` dapat membuka halaman `GET /operator/submissions` dengan status HTTP 200 OK (bebas dari error 500 null pointer) serta melihat banner peringatan penugasan unit.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite autentikasi SSO:
  `php artisan test --filter=SimrsSsoTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login dengan akun SSO baru (yang belum memiliki unit):
   - Buka menu **Workboard RBA** (`/operator/submissions`).
   - Pastikan halaman terbuka dengan mulus (HTTP 200).
   - Pastikan judul header menampilkan `Daftar Usulan RBA - Belum Ditugaskan ke Unit`.
   - Pastikan banner kuning muncul dengan pesan jelas untuk menghubungi Administrator.
2. Login sebagai Administrator:
   - Buka menu **Users**, edit akun pegawai SSO tersebut dan pilih Unit Kerjanya (misal: Unit Rawat Jalan).
   - Simpan perubahan.
3. Login kembali sebagai akun SSO tersebut:
   - Buka menu **Workboard RBA**.
   - Pastikan banner peringatan hilang, header menampilkan nama unit kerja yang benar (`Daftar Usulan RBA - Unit Rawat Jalan`), dan daftar usulan RBA unit tersebut tampil secara normal.
