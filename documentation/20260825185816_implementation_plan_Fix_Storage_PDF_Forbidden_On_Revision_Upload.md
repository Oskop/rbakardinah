# Implementation Plan - Perbaikan Error 403 Forbidden File PDF Pasca Upload Revisi

Memperbaiki kendala error `403 | Forbidden` saat Operator mengeklik tautan file PDF revisi (misal V2) setelah berhasil diunggah.

---

## Analisis Akar Masalah (Root Cause)

1. **Penyimpanan File Sukses**: File PDF revisi yang diunggah Operator berhasil tersimpan di direktori server `storage/app/public/attachments/` dan tercatat di database `rba_attachments`.
2. **Kendala Symlink Windows**: Direktori `public/storage` di sistem operasi lokal berupa folder fisik terpisah (*unlinked folder*) dan bukan *symlink/directory junction* dinamis ke `storage/app/public/`.
3. **Penyebab Response 403**: Ketika browser mengakses URL `http://127.0.0.1:8099/storage/attachments/[filename].pdf`, web server tidak menemukan file fisik di folder `public/storage/attachments/` dan meneruskan request ke router Laravel. Karena sebelumnya tidak ada route handler untuk prefix `/storage/...`, web server / framework mengembalikan halaman error `403 Forbidden` / `404 Not Found`.

---

## User Review Required

> [!IMPORTANT]
> **Solusi Berlapis (Dual Layer Solution):**
> 1. **Route Handler Streaming `/storage/{path}` di Laravel (`routes/web.php`):**
>    Menambahkan route streaming resmi di Laravel yang membaca file langsung dari `storage_path('app/public/' . $path)`. Solusi ini memastikan file PDF selalu dapat diakses secara langsung dan andal tanpa tergantung pada keterbatasan symlink OS/web server.
> 2. **Sinkronisasi & Perbaikan Junction `public/storage`:**
>    Menghubungkan ulang folder `public/storage` dengan directory junction ke `storage/app/public`.

---

## Proposed Changes

### 1. Routing Web (`routes/web.php`)

#### [MODIFY] [web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Menambahkan route handler untuk `/storage/{path}`:
  ```php
  Route::get('/storage/{path}', function (string $path) {
      $filePath = storage_path('app/public/' . $path);
      if (!file_exists($filePath)) {
          abort(404, 'Dokumen file tidak ditemukan di server.');
      }
      return response()->file($filePath);
  })->where('path', '.*')->name('storage.file');
  ```

---

### 2. File System & Storage Link

- Memastikan direktori `public/storage` terhubung secara dinamis (*Windows directory junction*) ke `storage/app/public` sehingga web server juga dapat melayani file statis secara optimal.

---

### 3. Pengujian Otomatis (`tests/Feature/General/StorageTest.php`)

#### [NEW] [StorageTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/StorageTest.php)
- Menambahkan pengujian:
  1. `test_uploaded_attachment_can_be_accessed_via_storage_url`: Menguji bahwa file PDF yang baru diunggah melalui operator upload version dapat diakses langsung via URL `/storage/...` dan mengembalikan response 200 OK serta file PDF yang valid.
  2. `test_accessing_non_existent_storage_file_returns_404`: Menguji bahwa file yang tidak ada mengembalikan 404 (bukan 403 Forbidden).

---

## Verification Plan

### Automated Tests
- Jalankan test storage file:
  `php artisan test --filter=StorageTest`
- Jalankan test rincian usulan:
  `php artisan test --filter=RbaDetailTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator**.
2. Masuk ke usulan belanja, unggah revisi PDF baru pada rincian belanja.
3. Klik tautan **PDF V2** / versi terbaru yang baru saja diunggah.
4. Verifikasi bahwa file PDF langsung terbuka normal di browser (200 OK) tanpa muncul error 403 Forbidden.
