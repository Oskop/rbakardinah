# Walkthrough: Otomatisasi Status Unit Validated Berdasarkan Rincian Belanja

Pembaruan mekanisme penentuan **Status Unit (Makro - `RbaSubmission`)** menjadi **otomatis (reaktif)** berdasarkan kelengkapan validasi seluruh butir rincian usulan belanja (`RbaDetail`) di bawah unit kerja tersebut—menggantikan penekanan tombol manual *"Validasi & Lanjutkan"*—telah berhasil diimplementasikan dan diverifikasi 100% lulus pada seluruh rangkaian pengujian.

---

## 1. Ringkasan Implementasi

### A. Helper Sinkronisasi Otomatis pada Model (`RbaSubmission.php`)
- **File**: `app/Models/RbaSubmission.php`
- Menambahkan method `syncValidationStatus(): string`:
  - Mengambil seluruh rincian belanja unit yang berstatus diajukan (`is_submitted = true`).
  - **Kondisi 1 (`Draft`)**: Jika tidak ada rincian belanja yang diajukan (`count = 0`).
  - **Kondisi 2 (`Validated`)**: Jika ada rincian belanja diajukan (`count > 0`), **seluruh** rincian belanja tersebut telah berstatus `is_validated = true`, dan **tidak ada** rincian yang berstatus ditolak (`is_rejected = true`).
  - **Kondisi 3 (`Pending Supervisor`)**: Jika ada rincian yang diajukan namun belum semua divalidasi atau masih terdapat rincian yang ditolak.
  - Memperbarui kolom `status_submission` hanya bila terjadi perubahan status (menjaga performa query).

### B. Otomasi Event-Driven pada Controller Supervisor & Operator
1. **`Supervisor\ReviewController.php`**:
   - `toggleDetailValidation`: Memanggil `syncValidationStatus()` segera setelah supervisor memvalidasi atau membatalkan validasi salah satu rincian belanja. Jika rincian yang divalidasi adalah rincian terakhir yang dibutuhkan, status unit otomatis menjadi `Validated` dan notifikasi sukses menginfokan kepada supervisor. Jika supervisor membatalkan validasi salah satu rincian, status unit otomatis turun kembali menjadi `Pending Supervisor`.
   - `rejectDetail`: Memanggil `syncValidationStatus()` saat supervisor menolak salah satu rincian belanja, sehingga status unit tetap/kembali ke `Pending Supervisor`.
   - `validate`: Di-update agar memvalidasi kesiapan unit menggunakan `syncValidationStatus()`.
2. **`Operator\DetailController.php`**:
   - `submitItem`: Memanggil `syncValidationStatus()` saat operator mengajukan rincian belanja baru/revisi ke supervisor. Jika unit sebelumnya sempat `Validated`, adanya usulan baru yang belum diperiksa otomatis mengembalikan status unit ke `Pending Supervisor`.
   - `destroy`: Memanggil `syncValidationStatus()` saat rincian belanja dihapus oleh operator.
   - `uploadVersion`: Memanggil `syncValidationStatus()` saat operator mengunggah PDF revisi baru pada rincian yang sempat ditolak.

### C. Antarmuka Pengguna Realtime (`supervisor/submissions/show.blade.php`)
- **File**: `resources/views/supervisor/submissions/show.blade.php`
- Tombol manual *"Validasi & Lanjutkan"* dihapus dan digantikan dengan **Indikator Progres Validasi Otomatis**:
  - **Status `Validated`**: Badge hijau bersinar `✓ Unit Validated (X/Y)` lengkap dengan icon centang tebal.
  - **Status `Pending Supervisor`**: Pill amber interaktif `⏳ Validasi Berjalan: X/Y Usulan Disetujui` (disertai keterangan jumlah yang ditolak jika ada).
  - **Status `Draft`**: Badge netral `📝 Draft (Menunggu Pengajuan Operator)`.

---

## 2. Berkas yang Dimodifikasi

1. [app/Models/RbaSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmission.php)
2. [app/Http/Controllers/Supervisor/ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
3. [app/Http/Controllers/Operator/DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
4. [resources/views/supervisor/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
5. [tests/Feature/Supervisor/ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)

---

## 3. Hasil Pengujian & Verifikasi

- **Feature Tests Supervisor Review**:
  `Tests\Feature\Supervisor\ReviewTest` lulus 100% (12 passed, 74 assertions):
  - `test_unit_status_automatically_becomes_validated_when_all_details_are_validated`:
    1. Status awal: `Pending Supervisor`.
    2. Validasi item 1: Status tetap `Pending Supervisor`.
    3. Validasi item 2 (semua item tervalidasi): Status otomatis berubah menjadi `Validated`.
    4. Batalkan validasi item 1: Status otomatis turun kembali menjadi `Pending Supervisor`.
- **Kompilasi Aset Frontend**:
  `bun run build` sukses mengompilasi Vite CSS dan JS (`public/build/assets/app-BChQyNYJ.css` & `app-CBbTb_k3.js`).
- **Seluruh Rangkaian Test Suite Aplikasi**:
  `php artisan test` berhasil mengeksekusi seluruh **145 test cases** tanpa kegagalan (`145 passed, 688 assertions, 100% PASS`).
