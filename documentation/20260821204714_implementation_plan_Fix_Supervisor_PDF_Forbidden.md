# Implementation Plan - Analisis Tepat & Solusi Error 403 Forbidden PDF Review Supervisor

Menganalisis secara tepat penyebab mengapa **hanya salah satu file PDF spesifik** (`QTJdzg0q3Ef3pLSfUVHPthOExtBza0WwRGSclY3Z.pdf`) yang menghasilkan respon `403 | Forbidden` pada tampilan Supervisor, sementara file PDF usulan rincian belanja lainnya dapat dibuka secara normal.

## Hasil Analisis Tepat (Root Cause)

Berdasarkan investigasi empiris pada direktori fisik server (`public/storage/attachments/` dan `storage/app/public/attachments/`), ditemukan akar permasalahan utama:

1. **File Fisik PDF Tidak Ada di Disk Storage (*Missing File on Server Disk*)**:
   - PDF usulan lainnya (seperti `QfeKyRrV6qnGAb8wPqMypbgajHTgUTnkWW9bUU9w.pdf`, `04LLzb0FmlHVVqhQdtWPi903cGysjvzzIdVnCAA0.pdf`, dll) dapat dibuka dengan normal karena **file fisiknya benar-benar ada di folder penyimpanan server** (`storage/app/public/attachments/`).
   - Sebaliknya, file `QTJdzg0q3Ef3pLSfUVHPthOExtBza0WwRGSclY3Z.pdf` **TIDAK ADA / HILANG secara fisik di direktori server disk**, meskipun record data lampiran tersebut tercatat di database (`rba_attachments`). Hal ini umumnya terjadi apabila proses pengunggahan file terputus saat disimpan, adanya pembersihan storage temporary, atau file storage tidak ikut ter-copy saat migrasi environment.

2. **Mekanisme Server Web / Fallback Router**:
   - Ketika browser mengakses URL `http://127.0.0.1:8099/storage/attachments/QTJdzg0q3Ef3pLSfUVHPthOExtBza0WwRGSclY3Z.pdf`, web server (`php artisan serve`, Apache, atau Nginx) mencoba membaca file fisik di folder `public/storage/attachments/`.
   - Karena file fisik tersebut tidak ditemukan pada disk, web server mencoba mengarahkan request tersebut ke `index.php` (Laravel). Karena tidak ada rute Laravel untuk path file statis tersebut, server merespon dengan status **403 Forbidden** (atau 404 dari web server/Laravel router fallback).

---

## Solusi & Rencana Perubahan

Untuk menangani dan mencegah masalah ini terjadi pada pengguna, dilakukan penanganan pada tingkat antarmuka (UI) dan alur sistem:

### 1. Protective File Checking pada Views (UI)

#### [MODIFY] [supervisor/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Sebelum menampilkan tombol/link download PDF, tambahkan pengecekan keberadaan file fisik menggunakan `\Illuminate\Support\Facades\Storage::disk('public')->exists($latest->file_path)`.
- Apabila file fisik ada: tampilkan link download PDF seperti biasa.
- Apabila file fisik hilang/tidak ada di disk: tampilkan badge peringatan yang informatif, seperti:
  `<span class="text-amber-600 font-bold text-xs bg-amber-50 border border-amber-200 px-2 py-0.5 rounded" title="File PDF fisik tidak ditemukan di storage server. Minta Operator unggah ulang.">⚠️ File Tidak Ditemukan</span>`

#### [MODIFY] [operator/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Tambahkan proteksi pengecekan file fisik yang sama pada tampilan Operator agar Operator mengetahui jika file yang mereka unggah sebelumnya hilang dari disk dan dapat mengunggah versi baru (*upload new version*).

#### [MODIFY] [general/history.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/general/history.blade.php)
- Tambahkan pengecekan `Storage::disk('public')->exists()` pada halaman riwayat/logs versi lampiran agar link file yang hilang terlindungi.

---

### 2. Otorisasi Peran pada Logs/History Controller

#### [MODIFY] [HistoryController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/General/HistoryController.php)
- Memastikan logika otorisasi `HistoryController::show()` mengizinkan peran **Supervisor** melihat riwayat lampiran (`$user->role === 'Supervisor'`), mencegah potensi rintangan otorisasi sekunder.

---

## Verification Plan

### Automated Tests
- Jalankan test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login sebagai **Supervisor** dan buka halaman review rincian usulan RBA.
2. Untuk usulan yang file PDF-nya ada di disk: pastikan link PDF dapat diklik dan terbuka normal.
3. Untuk rincian yang file PDF-nya tidak ada di disk: pastikan tampilan tidak memberikan link yang rusak/forbidden, melainkan menampilkan indikator informatif `⚠️ File Tidak Ditemukan`.
4. Login sebagai **Operator**, buka rincian tersebut dan unggah versi PDF baru (*Upload Versi Baru*). Pastikan file baru tersimpan secara fisik dan dapat dibuka kembali oleh Supervisor secara normal.
