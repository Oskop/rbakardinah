# Implementation Plan: Penyelidikan dan Penanganan Disparitas Waktu (Timezone UTC vs WIB)

## 1. Ringkasan Masalah & Pertanyaan Pengguna
- **Fenomena**: Operator mengajukan permohonan RKBMD di server production antara rentang waktu pukul 10:00 – 12:00 siang WIB, namun saat dicek di sistem tercatat waktu **"02:35 WIB"**.
- **Pertanyaan**: Apa sebenarnya yang terjadi di sistem, mengapa muncul jam 02:35 WIB, dan bagaimana solusi terbaiknya?

---

## 2. Hasil Penyelidikan Akar Masalah (Root Cause Analysis)

### A. Selisih Matematis Tepat 7 Jam (UTC vs WIB)
Indonesia Bagian Barat (WIB) berada pada zona waktu **UTC+7** (Universal Time Coordinated + 7 jam).
Ketika permohonan dibuat pada pukul **09:35 WIB**:
$$\text{Waktu UTC} = 09:35 - 7\text{ jam} = \mathbf{02:35\text{ UTC}}$$

### B. Konfigurasi Default Aplikasi Laravel (`config/app.php`)
Di file [config/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/app.php#L59-L68):
```php
'timezone' => 'UTC',
```
Karena nilai ini diatur ke `'UTC'`, maka saat fungsi `now()`, `Carbon::now()`, atau timestamps otomatis Eloquent (`created_at`, `updated_at`) dieksekusi, Laravel mengambil waktu dalam standar **UTC**.

Database MySQL menyimpan string waktu mentah tersebut apa adanya:
```sql
created_at: '2026-10-31 02:35:xx'
```

### C. Jebakan Teks Statis `"WIB"` di Tampilan RKBMD
Di file view RKBMD:
- [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php#L493):
  ```blade
  {{ $sub->created_at->format('d/m/Y H:i') }} WIB
  ```
- [resources/views/operator/rkbmd/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php#L175):
  ```blade
  {{ $rkbmd->created_at->format('d M Y, H:i') }} WIB
  {{ $rkbmd->replied_at->format('d M Y, H:i') }} WIB
  {{ $rkbmd->updated_at->format('d M Y, H:i') }} WIB
  ```
Nilai `created_at` yang masih dalam **UTC (02:35)** langsung dicetak dan **ditempeli kata "WIB" secara harfiah**. Akibatnya, jam **02:35 UTC** terbaca di layar sebagai **02:35 WIB**.

### D. Temuan Inkonsistensi di Codebase Lain
Penyelidikan menemukan bahwa developer sebelumnya telah menyadari isu UTC ini pada modul lain, namun menanganinya secara manual (*ad-hoc patch*):
- Pada Modul RBA Header ([app/Http/Controllers/RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php#L194)):
  ```php
  $v->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'
  ```
- Pada Modul Arsip Hard Delete ([resources/views/admin/archives/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/archives/index.blade.php#L117)):
  ```blade
  {{ $archive->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' }}
  ```
- Namun pada modul **RKBMD** dan cetak laporan ([resources/views/reports/*](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/admin_rba_print.blade.php#L365)), pemanggilan `->timezone('Asia/Jakarta')` terlewat (*human error*), sehingga menampilkan UTC mentah berlabel WIB.

---

## 3. Analisis Perbandingan Opsi Solusi

| Aspek Pertimbangan | Opsi 1: Standarisasi Sistem Global (`Asia/Jakarta`) **(Direkomendasikan)** | Opsi 2: Konversi Lapisan Tampilan Saja (*Presentation Layer Only*) |
| :--- | :--- | :--- |
| **Deskripsi** | Mengubah `config/app.php` menjadi `'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta')` dan menambahkan `APP_TIMEZONE=Asia/Jakarta` di `.env`. | Membiarkan `config/app.php` tetap `UTC`, lalu menambahkan `->timezone('Asia/Jakarta')` pada view RKBMD & cetak laporan. |
| **Kelebihan** | 1. Menyelesaikan masalah di akar secara permanen untuk seluruh sistem saat ini & masa depan.<br>2. Menghapus risiko developer lupa menambah `->timezone('Asia/Jakarta')`.<br>3. Log aktivitas, export laporan, audit trail, dan perbandingan tanggal (`today()`, filter harian) 100% sinkron dengan jam operasional RSUD Kardinah (WIB).<br>4. Kode yang sudah memiliki `->timezone('Asia/Jakarta')` bersifat **idempotent** (tidak rusak dan nilainya tetap akurat). | 1. Tidak mengubah konfigurasi global aplikasi.<br>2. Data RKBMD yang terlanjur terinput di database production (disimpan dalam angka UTC) langsung terkonversi otomatis ke WIB saat ditampilkan di layar. |
| **Kekurangan** | Data RKBMD yang *sudah terlanjur* disimpan di production sebelum konfigurasi diubah (string angka `02:35`) perlu disesuaikan +7 jam via satu skrip/query penyesuaian jika ingin record lama tersebut tampil tepat jam 09:35 WIB. | 1. *Technical debt* berulang: modul baru yang dibuat nanti bisa terkena bug yang sama jika lupa menulis `->timezone()`.<br>2. Filter database harian (`whereDate('created_at', today())`) di pagi hari (pukul 00:00–06:59 WIB) akan menghasilkan data yang salah karena di UTC masih hari kemarin. |
| **Kesesuaian Karakter Aplikasi** | **Sangat Sesuai**: RBA Kardinah adalah aplikasi intranet/internal RSUD Kardinah Kota Tegal yang seluruh penggunanya berada di zona waktu WIB. Standar aplikasi enterprise rumah sakit satu zona waktu adalah menyetel timezone aplikasi sesuai zona waktu operasionalnya. | **Kurang Sesuai**: Standar UTC murni biasanya hanya diperlukan jika aplikasi berskala global multi-negara di mana pengguna berada di zona waktu berbeda-beda (New York, London, Tokyo). |

---

## 4. Rencana Implementasi Bertahap (Proposed Plan)

Jika **Opsi 1** disetujui (dengan opsi pembaruan data eksisting):

### Tahap 1: Konfigurasi Global Timezone
1. Edit [config/app.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/config/app.php#L68):
   ```php
   'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
   ```
2. Edit [.env](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env) dan [.env.example](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/.env.example):
   ```env
   APP_TIMEZONE=Asia/Jakarta
   ```

### Tahap 2: Standarisasi View RKBMD & Laporan Cetak
1. Pastikan view RKBMD:
   - [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)
   - [resources/views/operator/rkbmd/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php)
   Format waktu tampil bersih dan seragam.
2. Pastikan view laporan cetak [resources/views/reports/*](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/) mencetak waktu saat ini sesuai zona waktu WIB.

### Tahap 3: Penanganan Data RKBMD Eksisting di Production
Kami akan menyediakan 2 pilihan untuk data permohonan yang sudah terlanjur diinput sebelum perubahan ini:
- **Pilihan A (Otomatis via Artisan Command / Migration)**: Menjalankan skrip penyesuaian untuk memajukan 7 jam record RKBMD yang dibuat sebelum migrasi timezone:
  ```sql
  UPDATE rkbmd_submissions 
  SET created_at = DATE_ADD(created_at, INTERVAL 7 HOUR),
      updated_at = DATE_ADD(updated_at, INTERVAL 7 HOUR)
  WHERE created_at < [WAKTU_MIGRASI];
  ```
  *(Termasuk pada tabel `rkbmd_histories` dan `rkbmd_items` jika diperlukan).*
- **Pilihan B (Biarkan jika data uji coba)**: Jika permohonan tersebut adalah permohonan uji coba perdana yang tidak memerlukan audit ketat, data lama dibiarkan dan seluruh data baru berikutnya otomatis akurat 100%.

### Tahap 4: Pengujian & Verifikasi
1. Jalankan test otomatis feature RKBMD (`php artisan test --filter=Rkbmd`).
2. Jalankan simulasi penyimpanan record baru dan periksa apakah `now()` menghasilkan jam WIB yang sesuai dengan waktu lokal server/laptop.
3. Pastikan tidak ada regresi pada modul RBA Header, Arsip Hard Delete, dan Submissions.

---

## 5. Pertanyaan untuk Review Pengguna
1. Apakah Anda menyetujui **Opsi 1** (Standarisasi global ke `Asia/Jakarta` di `config/app.php` dan `.env`) sebagai solusi jangka panjang?
2. Untuk data permohonan RKBMD yang sudah terlanjur masuk di database production dengan jam 02:35, apakah Anda ingin kami sediakan skrip/artisan command untuk otomatis memajukan jamnya sebesar +7 jam (**Pilihan A**), atau permohonan tersebut cukup dibiarkan/dibuat ulang (**Pilihan B**)?
