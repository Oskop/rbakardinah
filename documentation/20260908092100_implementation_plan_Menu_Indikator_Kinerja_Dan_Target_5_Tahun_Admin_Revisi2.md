# Rencana Implementasi: Menu Indikator Kinerja & Target 5 Tahunan (Khusus Administrator) - [REVISI 2]

Dokumen ini merinci rancangan teknis penambahan menu **Indikator Kinerja** khusus untuk peran **Administrator**, lengkap dengan pengelolaan target tahunan (rentang 5 tahun), sistem pelacakan riwayat versi pengeditan target, proteksi data **Soft Delete**, serta **Fitur Penonaktifan & Pengaktifan Kembali (Toggle Status Aktif/Nonaktif)** pada entitas Indikator Kinerja.

---

## 1. Analisis Kebutuhan & Arsitektur

### 1.1 Persyaratan Pengguna
1. **Hak Akses Khusus Admin**:
   - Menu dan fungsionalitas ini hanya dapat diakses oleh pengguna dengan peran `Administrator` (`role:Administrator`).
2. **Entitas Indikator Kinerja (Tabel Parent)**:
   - Admin mendefinisikan indikator kinerja terlebih dahulu (Kode, Nama Indikator, Kategori/Bidang berupa **teks bebas**, Satuan, Definisi Operasional/Keterangan).
3. **Fitur Penonaktifan & Pengaktifan Kembali (Aktifkan Ulang)**:
   - Indikator kinerja memiliki status operasional: **Aktif** (`is_active = true`) dan **Nonaktif** (`is_active = false`).
   - Admin dapat **menonaktifkan** indikator kinerja yang sedang tidak dipakai (tanpa perlu menghapusnya).
   - Admin dapat **mengaktifkan kembali** indikator kinerja yang nonaktif kapan saja dengan satu klik tombol.
   - Tersedia filter toolbar untuk menampilkan: Semua Indikator, Hanya Aktif, atau Hanya Nonaktif.
4. **Target Indikator Tahunan (Tabel Child)**:
   - Admin menginput nilai target untuk setiap tahun.
   - Nilai target berupa **teks bebas yang ringkas** (contoh: angka skala `0-100`, persentase `100%`, rasio `1:20`, atau predikat mutu seperti `Paripurna`, `Sesuai Standar`, `Tersedia`).
5. **Tampilan Menu Matriks 5 Tahun**:
   - Menampilkan tabel berisikan kolom informasi indikator, status aktif, dan **5 kolom tahun berturut-turut** (misal: 2024, 2025, 2026, 2027, 2028).
   - Dilengkapi pemilih tahun awal (rentang 5 tahun dinamis/fleksibel), sehingga admin dapat menggeser rentang 5 tahun sesuai periode perencanaan (Renstra / RBA).
6. **Riwayat Pengeditan Nilai Target (Versioning)**:
   - Setiap pengeditan nilai target tahunan akan dicatat ke dalam tabel riwayat (*history/versioning*).
   - Mencatat versi (`V1`, `V2`, dst.), nilai sebelum diubah (`old_value`), nilai setelah diubah (`new_value`), alasan/catatan pengubahan, pengguna pengubah, dan waktu perubahan.
   - Pada cell target di tabel matriks, tersedia penanda visual versi yang dapat diklik untuk membuka modal linimasa riwayat pengubahan target.
7. **Proteksi Data dengan Soft Delete (Anti Hard Delete)**:
   - Menghapus indikator kinerja atau target **TIDAK AKAN** menghapus baris data secara permanen (*no hard delete*).
   - Menggunakan mekanisme `SoftDeletes` bawaan Laravel (`deleted_at`), sehingga data historis, audit trail, dan relasi pelaporan tetap utuh di database.

---

## 2. Rancangan Skema Basis Data (Database Schema)

### 2.1 Tabel `performance_indicators` (Induk Indikator Kinerja)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED (PK)` | Auto increment ID |
| `code` | `VARCHAR(50)` | Kode indikator unik/opsional (misal: `IK-01`, `IK-02`) |
| `name` | `VARCHAR(255)` | Nama / uraian indikator kinerja (wajib) |
| `category` | `VARCHAR(100)` (nullable) | **Teks bebas** bidang/kategori (misal: "Pelayanan Medik", "Keuangan", "Mutu", dll.) |
| `unit` | `VARCHAR(50)` (nullable) | Satuan ukuran (misal: `%`, `Skor`, `Predikat`, `Rasio`, `Hari`, `Kasus`) |
| `description` | `TEXT` (nullable) | Definisi operasional / formulasi indikator |
| `order` | `INT` (default 0) | Urutan tampilan pada tabel |
| `is_active` | `BOOLEAN` (default true) | **Status Aktif/Nonaktif** (bisa dinonaktifkan & diaktifkan ulang) |
| `created_by` | `BIGINT UNSIGNED (FK)` | Relasi ke `users.id` |
| `timestamps` | `TIMESTAMP` | `created_at` & `updated_at` |
| `deleted_at` | `TIMESTAMP` (nullable) | **Soft delete** (mencegah hard delete) |

### 2.2 Tabel `performance_indicator_targets` (Nilai Target Tahunan)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED (PK)` | Auto increment ID |
| `performance_indicator_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `performance_indicators.id` |
| `year` | `INT` | Tahun target (misal: 2025) |
| `target_value` | `VARCHAR(100)` | Teks nilai target (misal: `100%`, `95`, `Paripurna`) |
| `current_version` | `INT` (default 1) | Nomor versi aktif saat ini (`1`, `2`, `3`, dst.) |
| `created_by` | `BIGINT UNSIGNED (FK)` | Admin pembuat awal |
| `updated_by` | `BIGINT UNSIGNED (FK)` | Admin pengubah terakhir |
| `timestamps` | `TIMESTAMP` | `created_at` & `updated_at` |
| `deleted_at` | `TIMESTAMP` (nullable) | **Soft delete** (mencegah hard delete) |
| *Index* | `UNIQUE(performance_indicator_id, year)` | Mencegah duplikasi target pada indikator dan tahun yang sama (untuk data aktif) |

### 2.3 Tabel `performance_indicator_target_histories` (Riwayat Perubahan Target)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED (PK)` | Auto increment ID |
| `target_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `performance_indicator_targets.id` |
| `performance_indicator_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `performance_indicators.id` |
| `year` | `INT` | Tahun target yang bersangkutan |
| `version_number` | `INT` | Nomor versi (1 untuk awal, 2, 3, dst.) |
| `old_value` | `VARCHAR(100)` (nullable) | Nilai sebelum diedit (null jika versi 1 / pembuatan awal) |
| `new_value` | `VARCHAR(100)` | Nilai sesudah diedit |
| `change_note` | `TEXT` (nullable) | Alasan / keterangan perubahan nilai |
| `user_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `users.id` (Admin yang melakukan pengubahan) |
| `timestamps` | `TIMESTAMP` | Waktu pencatatan riwayat (`created_at`) |

---

## 3. Ilustrasi Tampilan Antarmuka (Visual Mockup)

Berikut adalah konsep visual antarmuka pengguna berbasis teks/ASCII yang menampilkan indikator aktif, indikator nonaktif, tombol aktifkan ulang, dan input teks bebas:

### 3.1 Menu Navigasi Utama (Admin Navbar)
```text
+-----------------------------------------------------------------------------------------------------------------------+
| [LOGO] SIPAKAR  Dashboard  Master Data ▼  RBA Headers  🎯 Indikator Kinerja  Laporan  📢 Pengumuman  Log Data   (Admin) ▼|
+-----------------------------------------------------------------------------------------------------------------------+
```

### 3.2 Tampilan Halaman Utama Indikator Kinerja (Matriks 5 Tahun & Status Toggle)
```text
=========================================================================================================================
  MANAJEMEN INDIKATOR KINERJA RSUD KARDINAH
  Kelola indikator kinerja rumah sakit dan pantau target berkala 5 tahun perencanaan.
=========================================================================================================================

[ 🔍 Cari Nama/Kategori... ]   [ Rentang Tahun: 2024 - 2028 ▼ ]   [ Filter Status: Semua (4) ▼ ]   [ + Tambah Indikator ]
-------------------------------------------------------------------------------------------------------------------------
                                             TARGET PERIODE 5 TAHUN
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| NO  | KODE  | INDIKATOR KINERJA      | KAT/SAT| STATUS |  2024   |  2025   |  2026   |  2027   |  2028   |     AKSI      |
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| 1   | IK-01 | Tingkat Akreditasi     | Mutu   |  🟢    | Paripurna 100%      100%      100%      100%    | [✏️][🎯][📜]   |
|     |       | Rumah Sakit            | Predik | Aktif  | [V1]    | [V2 📜] | [V1]    | [V1]    | [V1]    | [⏸️ Nonaktif] |
|     |       |                        |        |        |         |         |         |         |         | [🗑️ Hapus]    |
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| 2   | IK-02 | Kepatuhan Hand Hygiene | Medis  |  🟢    | 85%     | 90%     | 92%     | 95%     | 95%     | [✏️][🎯][📜]   |
|     |       | Tenaga Medis           | %      | Aktif  | [V1]    | [V3 📜] | [V1]    | [V1]    | [V1]    | [⏸️ Nonaktif] |
|     |       |                        |        |        |         |         |         |         |         | [🗑️ Hapus]    |
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| 3   | IK-03 | Waktu Tanggap (Respon  | IGD    |  🟢    | ≤ 5     | ≤ 5     | ≤ 4     | ≤ 4     | ≤ 3     | [✏️][🎯][📜]   |
|     |       | Time) Pelayanan IGD    | Menit  | Aktif  | [V1]    | [V1]    | [V1]    | [V1]    | [V1]    | [⏸️ Nonaktif] |
|     |       |                        |        |        |         |         |         |         |         | [🗑️ Hapus]    |
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| 4   | IK-04 | Indeks Kepuasan        | Umum   |  ⚪    | 82.5    | 85.0    | -       | -       | -       | [✏️][🎯][📜]   |
|     |       | Pelayanan Lama (Arsip) | Skor   |Nonaktif| [V1]    | [V1]    | [-]     | [-]     | [-]     | [▶️ AKTIFKAN] |
|     |       |                        |        |        |         |         |         |         |         | [🗑️ Hapus]    |
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+

Fitur Status & Aksi:
- 🟢 Aktif: Indikator sedang berjalan. Terdapat tombol [⏸️ Nonaktifkan] untuk mengarsipkan sementara tanpa menghapus.
- ⚪ Nonaktif: Indikator diarsipkan sementara (baris tampak lebih redup/abu-abu). Terdapat tombol [▶️ Aktifkan Kembali] untuk mengaktifkannya lagi.
- [🗑️ Hapus]: Menghapus dengan Soft Delete (data tersimpan aman di database dengan status deleted_at terisi).
```

### 3.3 Modal 1: Tambah / Edit Indikator Kinerja Parent
```text
+-----------------------------------------------------------------------------------------------+
|  🎯 Tambah Indikator Kinerja Baru                                                         [X] |
+-----------------------------------------------------------------------------------------------+
|  Kode Indikator:                                                                              |
|  [ IK-05                                                                                    ] |
|                                                                                               |
|  Nama Indikator Kinerja (*):                                                                  |
|  [ Rata-rata Lama Rawat Pasien (Length of Stay / LOS)                                        ] |
|                                                                                               |
|  Kategori / Bidang (Teks Bebas):                                                              |
|  [ Pelayanan Medik                                                                          ] |
|  *Teks bebas opsional. Contoh: Pelayanan Medik, Keuangan, Mutu Pelayanan, SDM, dsb.           |
|                                                                                               |
|  Satuan Ukuran:                                                                               |
|  [ Hari                                                                                     ] |
|  *Contoh: %, Skor, Hari, Predikat, Orang, dsb.                                                |
|                                                                                               |
|  Definisi Operasional / Formulasi:                                                            |
|  [ Jumlah hari perawatan pasien keluar / Jumlah pasien keluar hidup dan mati dalam 1 tahun. ] |
|                                                                                               |
|  Urutan Tampil:                             Status Indikator:                                 |
|  [ 5          ]                             [v] Aktif (Siap digunakan dan dipantau)           |
+-----------------------------------------------------------------------------------------------+
|                                                                [ Batal ]   [ Simpan Indikator ]|
+-----------------------------------------------------------------------------------------------+
```

### 3.4 Modal 2: Input / Edit Cepat Target Tahunan
```text
+-----------------------------------------------------------------------+
|  🎯 Ubah Target Indikator                                         [X] |
+-----------------------------------------------------------------------+
|  Indikator : Tingkat Akreditasi Rumah Sakit (IK-01)                   |
|  Tahun     : 2025                                                     |
|  Versi Saat Ini : V1 (Nilai Sebelumnya: "Madya")                      |
+-----------------------------------------------------------------------+
|  Nilai Target Baru (*):                                               |
|  [ Paripurna                                                        ] |
|  *Teks bebas ringkas. Contoh: 100%, 85, Paripurna, Sesuai SPM, dsb.   |
|                                                                       |
|  Alasan / Catatan Perubahan (*):                                      |
|  [ Penyesuaian target mengikuti Renstra Revisi RSUD Kardinah Tahun  ] |
|  [ 2025 sesuai Surat Keputusan Direktur.                            ] |
+-----------------------------------------------------------------------+
|  ℹ️ Perubahan ini akan otomatis dicatat sebagai Versi 2 (V2)         |
|                                        [ Batal ]   [ Simpan Perubahan ]|
+-----------------------------------------------------------------------+
```

### 3.5 Modal 3: Dialog Riwayat Perubahan Nilai Target (Audit Trail Versioning)
```text
+-----------------------------------------------------------------------------------------------+
|  📜 Riwayat Versi Target: Tingkat Akreditasi Rumah Sakit (Tahun 2025)                     [X] |
+-----------------------------------------------------------------------------------------------+
|                                                                                               |
|  ● VERSI 2 (TERKINI)                                            📅 08 Sep 2026, 09:15 WIB    |
|    Nilai Target    : Paripurna                                  👤 Oleh: Administrator (Budi) |
|    Perubahan Dari  : "Madya"  ➔  "Paripurna"                                                  |
|    Catatan/Alasan  : "Penyesuaian target mengikuti Renstra Revisi RSUD Kardinah Tahun 2025     |
|                       sesuai SK Direktur."                                                    |
|                                                                                               |
|  -------------------------------------------------------------------------------------------  |
|                                                                                               |
|  ○ VERSI 1 (NILAI AWAL)                                         📅 15 Jan 2025, 10:00 WIB    |
|    Nilai Target    : Madya                                      👤 Oleh: Administrator (Ahmad)|
|    Perubahan Dari  : (Input Target Awal)                                                      |
|    Catatan/Alasan  : "Penetapan target awal Renstra RSUD Kardinah 2024-2028."                 |
|                                                                                               |
+-----------------------------------------------------------------------------------------------+
|                                                                                 [ Tutup ]     |
+-----------------------------------------------------------------------------------------------+
```

---

## 4. Rencana Perubahan Komponen & File

### 4.1 Basis Data & Model (Backend)
1. **Migration File**:
   - `database/migrations/YYYY_MM_DD_HHMMSS_create_performance_indicators_tables.php`:
     - Membuat tabel `performance_indicators` (dengan `is_active` boolean default true dan `$table->softDeletes()`).
     - Membuat tabel `performance_indicator_targets` (dengan `$table->softDeletes()`).
     - Membuat tabel `performance_indicator_target_histories`.
2. **Models**:
   - `app/Models/PerformanceIndicator.php`:
     - Menambahkan `use SoftDeletes, LogsActivity;`
     - Kolom `$fillable = ['code', 'name', 'category', 'unit', 'description', 'order', 'is_active', 'created_by']`
     - Cast: `'is_active' => 'boolean'`
     - Relasi `hasMany(PerformanceIndicatorTarget::class)`
     - Helper method `getTargetByYear($year)`
   - `app/Models/PerformanceIndicatorTarget.php`:
     - Menambahkan `use SoftDeletes, LogsActivity;`
     - Relasi `belongsTo(PerformanceIndicator::class)`
     - Relasi `hasMany(PerformanceIndicatorTargetHistory::class)`
   - `app/Models/PerformanceIndicatorTargetHistory.php`:
     - Relasi `belongsTo(PerformanceIndicatorTarget::class)`, `belongsTo(User::class)`

### 4.2 Controller & Routing
1. **Controller**:
   - `app/Http/Controllers/Admin/PerformanceIndicatorController.php`:
     - `index(Request $request)`: Menampilkan tabel matriks indikator (dengan filter status Aktif/Nonaktif/Semua, pencarian nama/kategori, dan 5 kolom tahun).
     - `store(Request $request)`: Menyimpan indikator induk baru (termasuk status aktif awal).
     - `update(Request $request, PerformanceIndicator $indicator)`: Mengubah data indikator induk.
     - `toggleStatus(PerformanceIndicator $indicator)`: **Menonaktifkan / Mengaktifkan kembali** indikator kinerja dengan pesan feedback sukses.
     - `destroy(PerformanceIndicator $indicator)`: Melakukan **soft delete** pada indikator kinerja dan target-targetnya (tanpa hard delete).
     - `updateTarget(Request $request, PerformanceIndicator $indicator, $year)`: Menyimpan / memperbarui nilai target tahun tertentu sekaligus mencatat histori versi perubahan (`V1`, `V2`, dst.).
     - `batchUpdateTargets(Request $request, PerformanceIndicator $indicator)`: Menyimpan 5 target tahunan sekaligus untuk 1 indikator.
     - `targetHistory(PerformanceIndicator $indicator, $year)`: Mengambil data riwayat versi untuk tahun tersebut dalam format JSON (untuk modal riwayat).
2. **Routes (`routes/web.php`)**:
   - Ditempatkan di dalam grup `Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->name('admin.')->group(...)`:
     ```php
     Route::post('performance-indicators/{indicator}/toggle-status', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'toggleStatus'])->name('performance-indicators.toggle-status');
     Route::post('performance-indicators/{indicator}/targets/{year}', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'updateTarget'])->name('performance-indicators.targets.update');
     Route::post('performance-indicators/{indicator}/targets-batch', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'batchUpdateTargets'])->name('performance-indicators.targets.batch');
     Route::get('performance-indicators/{indicator}/targets/{year}/history', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'targetHistory'])->name('performance-indicators.targets.history');
     Route::resource('performance-indicators', \App\Http\Controllers\Admin\PerformanceIndicatorController::class);
     ```

### 4.3 Navigasi Menu
- `resources/views/layouts/navigation.blade.php`:
  - Menambahkan tautan navigasi `🎯 Indikator Kinerja` di bagian menu Administrator (desktop dan responsive mobile).

### 4.4 Tampilan Antarmuka (Views)
- `resources/views/admin/performance-indicators/index.blade.php`:
  - Halaman utama tabel matriks 5 tahun dengan Alpine.js untuk pencarian, filter status (Aktif/Nonaktif), pemilih rentang 5 tahun, badge status, tombol toggle status nonaktif/aktifkan ulang, modal kelola indikator, modal edit nilai target, dan modal riwayat versi target.

---

## 5. Rencana Verifikasi & Pengujian
1. **Automated Feature Testing (Pest / PHPUnit)**:
   - Buat `tests/Feature/Admin/PerformanceIndicatorTest.php`:
     - Test hak akses: Hanya Admin yang dapat mengakses, peran lain 403 Forbidden.
     - Test CRUD indikator kinerja induk dengan kategori teks bebas.
     - Test fitur **Toggle Status** (Nonaktifkan -> `is_active = false`, Aktifkan Kembali -> `is_active = true`).
     - Test penghapusan menggunakan **Soft Delete** (`assertSoftDeleted`).
     - Test input target tahunan baru (mencatat versi 1).
     - Test pengeditan target tahunan (mencatat versi 2, nilai lama, nilai baru, catatan perubahan).
     - Test endpoint API/JSON riwayat target.
2. **Kompilasi Frontend**:
   - Menjalankan `bun run build`.
3. **Full Regression Suite**:
   - Menjalankan `php artisan test` untuk memastikan 100% seluruh 161+ tes lama tetap passed.
