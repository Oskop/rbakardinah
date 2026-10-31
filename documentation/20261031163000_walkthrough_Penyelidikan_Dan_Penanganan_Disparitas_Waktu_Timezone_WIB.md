# Walkthrough: Penyelidikan & Penanganan Disparitas Waktu (Timezone UTC vs WIB)

## Ringkasan Eksekutif
Masalah tercatatnya waktu pengajuan permohonan RKBMD pada pukul **02:35 WIB** (padahal permohonan diajukan di siang hari antara rentang pukul 10:00 – 12:00 WIB) telah berhasil diselidiki dan diselesaikan secara tuntas.

Penyebab utamanya adalah konfigurasi bawaan Laravel [config/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/app.php) yang menyetel `'timezone' => 'UTC'`. Ketika permohonan diajukan pada pukul **09:35 WIB**, aplikasi mencatatnya dalam UTC sebagai **02:35 UTC** ($09:35 - 7\text{ jam} = 02:35$). Tampilan RKBMD kemudian mencetak nilai raw UTC tersebut dengan tambahan label statis `"WIB"` (*hardcoded*), sehingga tampak seolah-olah diajukan pada jam setengah tiga subuh.

---

## 1. Perubahan yang Dilakukan

### A. Standarisasi Timezone Global Aplikasi
Mengubah timezone dasar PHP & Laravel menjadi **`Asia/Jakarta`** (WIB / UTC+7) sehingga seluruh fungsi `now()`, Eloquent timestamps, audit log, cetak laporan, dan filter tanggal hari ini otomatis sinkron dengan jam operasional RSUD Kardinah.

- [config/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/app.php#L68):
  ```php
  'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
  ```
- [.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env) dan [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example):
  ```env
  APP_TIMEZONE=Asia/Jakarta
  ```

### B. Standarisasi Tampilan RKBMD
Memastikan seluruh tab dan timeline aksi permohonan menampilkan format tanggal dan jam lokal yang seragam dengan akhiran `WIB`:
- [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php):
  - Tab "Permohonan Saya": `Tahun {{ $sub->year }} • {{ $sub->created_at->format('d/m/Y H:i') }} WIB`
  - Tab "Permohonan Masuk": `Tahun {{ $inSub->year }} • {{ $inSub->created_at->format('d/m/Y H:i') }} WIB`
  - Tab "Permohonan Dialihkan": `Tahun {{ $fSub->year }} • {{ $fSub->created_at->format('d/m/Y H:i') }} WIB`
- [resources/views/operator/rkbmd/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php):
  - Timeline Riwayat Tindakan: `{{ $history->created_at->format('d/m/Y H:i') }} WIB`

### C. Artisan Command untuk Penyesuaian Timestamps Production
Dibuat artisan command baru [app/Console/Commands/FixRkbmdUtcTimestamps.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Console/Commands/FixRkbmdUtcTimestamps.php):
```bash
php artisan rkbmd:fix-utc-timestamps {--hours=7} {--before=} {--dry-run} {--force}
```
Command ini secara aman dan transparan dapat memajukan jam pada record database yang terlanjur tersimpan dalam format UTC (+7 jam). Tabel yang disesuaikan secara atomik (*database transaction*):
- `rkbmd_submissions`: `created_at`, `updated_at`, `replied_at`
- `rkbmd_items`: `created_at`, `updated_at`
- `rkbmd_histories`: `created_at`, `updated_at`

---

## 2. Panduan Eksekusi di Server Production

Bagi Administrator / DevOps yang mengelola server production, berikut langkah-langkah yang perlu dijalankan:

### Langkah 1: Perbarui `.env` Production
Buka file `.env` di server production dan pastikan terdapat baris berikut:
```env
APP_TIMEZONE=Asia/Jakarta
```
Kemudian bersihkan cache konfigurasi Laravel:
```bash
php artisan config:clear
```

### Langkah 2: Pratinjau Record yang Akan Disesuaikan (Dry-Run)
Jalankan perintah berikut untuk melihat pratinjau record RKBMD yang ada di database tanpa mengubah apapun:
```bash
php artisan rkbmd:fix-utc-timestamps --dry-run
```
Output akan menampilkan tabel perbandingan jam asli vs jam setelah ditambah +7 jam:
```text
=== Penyesuaian Timestamp RKBMD (UTC ke WIB) ===
Pergeseran waktu : +7 jam
Mode             : DRY-RUN (Hanya Pratinjau)

Ditemukan 2 record rkbmd_submissions.
+----+--------------------+---------------------+---------------------+---------------------+---------------------+
| ID | Nomor RKBMD        | Created (Asli)      | Created (+7 Jam)    | Replied (Asli)      | Replied (+7 Jam)    |
+----+--------------------+---------------------+---------------------+---------------------+---------------------+
| 1  | RKBMD/2026/09/0001 | 2026-09-15 09:39:38 | 2026-09-15 16:39:38 | 2026-09-15 09:42:03 | 2026-09-15 16:42:03 |
...
```

### Langkah 3: Eksekusi Penyesuaian Database
Setelah memastikan data sesuai, jalankan perintah eksekusi:
```bash
php artisan rkbmd:fix-utc-timestamps --force
```
Seluruh timestamps pada record submission, item, dan riwayat akan otomatis maju 7 jam dan waktu pengajuan akan sesuai dengan jam operasional nyata di siang hari.

---

## 3. Hasil Pengujian dan Verifikasi

Dibuat test suite baru [tests/Feature/TimezoneAndRkbmdTimestampTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/TimezoneAndRkbmdTimestampTest.php) untuk memverifikasi seluruh skenario secara otomatis:

1. **Test Application Timezone**:
   - Memastikan `config('app.timezone')` mengembalikan `Asia/Jakarta`.
   - Memastikan `now()->timezoneName` adalah `Asia/Jakarta`.
   - Status: **PASSED**.
2. **Test New RKBMD Submission Storage**:
   - Memastikan record RKBMD yang baru disimpan menggunakan jam lokal WIB.
   - Status: **PASSED**.
3. **Test RKBMD Views Rendering**:
   - Memastikan halaman index dan detail (show) merender format waktu dengan benar beserta sufiks `"WIB"`.
   - Status: **PASSED**.
4. **Test Artisan Command**:
   - Memastikan mode `--dry-run` tidak mengubah data.
   - Memastikan mode `--force` berhasil memajukan record submission, item, dan history sebesar 7 jam.
   - Status: **PASSED**.

Hasil pengujian otomatis:
- **Timezone & Timestamp Suite**: 4 passed (18 assertions)
- **RKBMD Feature Suite**: 42 passed (205 assertions)
