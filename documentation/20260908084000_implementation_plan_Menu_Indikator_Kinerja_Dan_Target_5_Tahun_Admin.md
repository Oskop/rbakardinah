# Rencana Implementasi: Menu Indikator Kinerja & Target 5 Tahunan (Khusus Administrator)

Dokumen ini merinci rancangan teknis penambahan menu **Indikator Kinerja** khusus untuk peran **Administrator**, lengkap dengan pengelolaan target tahunan (rentang 5 tahun), sistem pelacakan riwayat versi pengeditan nilai target, dan ilustrasi antarmuka (UI/UX).

---

## 1. Analisis Kebutuhan & Arsitektur

### 1.1 Persyaratan Pengguna
1. **Hak Akses Khusus Admin**:
   - Menu dan fungsionalitas ini hanya dapat diakses oleh pengguna dengan peran `Administrator` (`role:Administrator`).
2. **Entitas Indikator Kinerja (Tabel Parent)**:
   - Admin mendefinisikan indikator kinerja terlebih dahulu (misal: Kode, Nama Indikator, Kategori, Satuan/Definisi, Keterangan).
3. **Target Indikator Tahunan (Tabel Child)**:
   - Admin menginput nilai target untuk setiap tahun.
   - Nilai target berupa **teks bebas yang ringkas** (contoh: angka skala `0-100`, persentase `100%`, rasio `1:20`, atau predikat mutu seperti `Paripurna`, `Sesuai Standar`, `Tersedia`).
4. **Tampilan Menu Matriks 5 Tahun**:
   - Menampilkan tabel berisikan kolom informasi indikator dan **5 kolom tahun berturut-turut** (misal: 2024, 2025, 2026, 2027, 2028).
   - Dilengkapi pemilih tahun awal (rentang 5 tahun dinamis/fleksibel), sehingga admin dapat menggeser rentang 5 tahun sesuai periode perencanaan (Renstra / RBA).
5. **Riwayat Pengeditan Nilai Target (Versioning)**:
   - Setiap pengeditan nilai target tahunan akan dicatat ke dalam tabel riwayat (*history/versioning*).
   - Mencatat versi (`V1`, `V2`, dst.), nilai sebelum diubah (`old_value`), nilai setelah diubah (`new_value`), alasan/catatan pengubahan, pengguna pengubah, dan waktu perubahan.
   - Pada cell target di tabel matriks, tersedia penanda visual versi yang dapat diklik untuk membuka modal linimasa riwayat pengubahan target.

---

## 2. Rancangan Skema Basis Data (Database Schema)

### 2.1 Tabel `performance_indicators` (Induk Indikator Kinerja)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED (PK)` | Auto increment ID |
| `code` | `VARCHAR(50)` | Kode indikator unik/opsional (misal: `IK-01`, `IK-02`) |
| `name` | `VARCHAR(255)` | Nama / uraian indikator kinerja (wajib) |
| `category` | `VARCHAR(100)` (nullable) | Kategori bidang (Pelayanan, Keuangan, Mutu, SDM, dll.) |
| `unit` | `VARCHAR(50)` (nullable) | Satuan ukuran (misal: `%`, `Skor`, `Predikat`, `Rasio`, `Kasus`) |
| `description` | `TEXT` (nullable) | Definisi operasional / formulasi indikator |
| `order` | `INT` (default 0) | Urutan tampilan pada tabel |
| `is_active` | `BOOLEAN` (default true) | Status aktif indikator |
| `created_by` | `BIGINT UNSIGNED (FK)` | Relasi ke `users.id` |
| `timestamps` | `TIMESTAMP` | `created_at` & `updated_at` |

### 2.2 Tabel `performance_indicator_targets` (Nilai Target Tahunan)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED (PK)` | Auto increment ID |
| `performance_indicator_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `performance_indicators.id` (onDelete: cascade) |
| `year` | `INT` | Tahun target (misal: 2025) |
| `target_value` | `VARCHAR(100)` | Teks nilai target (misal: `100%`, `95`, `Paripurna`) |
| `current_version` | `INT` (default 1) | Nomor versi aktif saat ini (`1`, `2`, `3`, dst.) |
| `created_by` | `BIGINT UNSIGNED (FK)` | Admin pembuat awal |
| `updated_by` | `BIGINT UNSIGNED (FK)` | Admin pengubah terakhir |
| `timestamps` | `TIMESTAMP` | `created_at` & `updated_at` |
| *Index* | `UNIQUE(performance_indicator_id, year)` | Mencegah duplikasi target pada indikator dan tahun yang sama |

### 2.3 Tabel `performance_indicator_target_histories` (Riwayat Perubahan Target)
| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `BIGINT UNSIGNED (PK)` | Auto increment ID |
| `target_id` | `BIGINT UNSIGNED (FK)` | Relasi ke `performance_indicator_targets.id` (onDelete: cascade) |
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

Berikut adalah konsep visual antarmuka pengguna berbasis teks/ASCII yang dirancang sesuai standar desain SIPAKAR RSUD Kardinah:

### 3.1 Menu Navigasi Utama (Admin Navbar)
```text
+-----------------------------------------------------------------------------------------------------------------------+
| [LOGO] SIPAKAR  Dashboard  Master Data ▼  RBA Headers  🎯 Indikator Kinerja  Laporan  📢 Pengumuman  Log Data   (Admin) ▼|
+-----------------------------------------------------------------------------------------------------------------------+
```

### 3.2 Tampilan Halaman Utama Indikator Kinerja (Matriks 5 Tahun)
```text
=========================================================================================================================
  MANAJEMEN INDIKATOR KINERJA RSUD KARDINAH
  Kelola indikator kinerja rumah sakit dan pantau target berkala 5 tahun perencanaan.
=========================================================================================================================

[ 🔍 Cari Indikator... ]   [ Rentang Tahun: 2024 - 2028 ▼ ]   [ Kategori: Semua ▼ ]          [ + Tambah Indikator ]
-------------------------------------------------------------------------------------------------------------------------
                                             TARGET PERIODE 5 TAHUN
+-----+-------+------------------------+--------+---------+---------+---------+---------+---------+--------------------+
| NO  | KODE  | INDIKATOR KINERJA      | SATUAN |  2024   |  2025   |  2026   |  2027   |  2028   |        AKSI        |
+-----+-------+------------------------+--------+---------+---------+---------+---------+---------+--------------------+
| 1   | IK-01 | Tingkat Akreditasi     | Predik | Paripurna 100%      100%      100%      100%    | [✏️] [🎯] [📜] [🗑️] |
|     |       | Rumah Sakit            |        | [V1]    | [V2 📜] | [V1]    | [V1]    | [V1]    |                    |
+-----+-------+------------------------+--------+---------+---------+---------+---------+---------+--------------------+
| 2   | IK-02 | Kepatuhan Hand Hygiene | %      | 85%     | 90%     | 92%     | 95%     | 95%     | [✏️] [🎯] [📜] [🗑️] |
|     |       | Tenaga Medis           |        | [V1]    | [V3 📜] | [V1]    | [V1]    | [V1]    |                    |
+-----+-------+------------------------+--------+---------+---------+---------+---------+---------+--------------------+
| 3   | IK-03 | Waktu Tanggap (Respon  | Menit  | ≤ 5     | ≤ 5     | ≤ 4     | ≤ 4     | ≤ 3     | [✏️] [🎯] [📜] [🗑️] |
|     |       | Time) Pelayanan IGD    |        | [V1]    | [V1]    | [V1]    | [V1]    | [V1]    |                    |
+-----+-------+------------------------+--------+---------+---------+---------+---------+---------+--------------------+
| 4   | IK-04 | Indeks Kepuasan        | Skor   | 82.5    | 85.0    | 87.5    | -       | -       | [✏️] [🎯] [📜] [🗑️] |
|     |       | Masyarakat (IKM)       |        | [V1]    | [V1]    | [V2 📜] | [+ Isi] | [+ Isi] |                    |
+-----+-------+------------------------+--------+---------+---------+---------+---------+---------+--------------------+

Keterangan:
- Badge [V1], [V2 📜]: Menunjukkan nomor versi target. Jika diklik akan membuka Modal Riwayat Perubahan.
- Cell bernilai [+ Isi] atau nilai yang diklik: Membuka pop-up cepat ubah nilai target tahun bersangkutan.
- Tombol Aksi: [✏️ Edit Indikator] [🎯 Input/Edit Target 5 Tahun Sekaligus] [📜 Riwayat Lengkap] [🗑️ Hapus]
```

### 3.3 Modal 1: Tambah / Edit Indikator Kinerja (Parent)
```text
+-----------------------------------------------------------------------+
|  🎯 Tambah Indikator Kinerja Baru                                 [X] |
+-----------------------------------------------------------------------+
|  Kode Indikator:                                                      |
|  [ IK-05                                                            ] |
|                                                                       |
|  Nama Indikator Kinerja (*):                                          |
|  [ Rata-rata Lama Rawat Pasien (Length of Stay / LOS)                ] |
|                                                                       |
|  Kategori / Bidang:                 Satuan Ukuran:                    |
|  [ Pelayanan Medik            ▼ ]   [ Hari                          ] |
|                                                                       |
|  Definisi Operasional / Formulasi:                                    |
|  [ Jumlah hari perawatan pasien keluar / Jumlah pasien keluar hidup   ] |
|  [ dan mati dalam kurun waktu satu tahun.                           ] |
|                                                                       |
|  Urutan Tampil:                     Status:                           |
|  [ 5          ]                     [v] Aktif                         |
+-----------------------------------------------------------------------+
|                                        [ Batal ]   [ Simpan Indikator ]|
+-----------------------------------------------------------------------+
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
|  Contoh: 100%, 85, Paripurna, Sesuai SPM, dsb.                        |
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
     - Membuat tabel `performance_indicators`.
     - Membuat tabel `performance_indicator_targets`.
     - Membuat tabel `performance_indicator_target_histories`.
2. **Models**:
   - `app/Models/PerformanceIndicator.php`: Relasi `hasMany(PerformanceIndicatorTarget::class)` dan helper target per tahun.
   - `app/Models/PerformanceIndicatorTarget.php`: Relasi `belongsTo(PerformanceIndicator::class)` dan `hasMany(PerformanceIndicatorTargetHistory::class)`.
   - `app/Models/PerformanceIndicatorTargetHistory.php`: Relasi `belongsTo(PerformanceIndicatorTarget::class)`, `belongsTo(User::class)`.

### 4.2 Controller & Routing
1. **Controller**:
   - `app/Http/Controllers/Admin/PerformanceIndicatorController.php`:
     - `index(Request $request)`: Menampilkan tabel matriks indikator dan 5 kolom tahun (dengan kalkulasi rentang tahun dinamis atau berdasarkan filter `start_year`).
     - `store(Request $request)`: Menyimpan indikator induk baru.
     - `update(Request $request, PerformanceIndicator $indicator)`: Mengubah indikator induk.
     - `destroy(PerformanceIndicator $indicator)`: Menghapus indikator kinerja beserta targetnya.
     - `updateTarget(Request $request, PerformanceIndicator $indicator)`: Menyimpan / memperbarui nilai target tahun tertentu sekaligus mencatat histori versi perubahan (`V1`, `V2`, dst.).
     - `batchUpdateTargets(Request $request, PerformanceIndicator $indicator)`: Menyimpan 5 target tahunan sekaligus untuk 1 indikator.
     - `targetHistory(PerformanceIndicator $indicator, $year)`: Mengambil data riwayat versi untuk tahun tersebut dalam format JSON (untuk modal riwayat).
2. **Routes (`routes/web.php`)**:
   - Ditempatkan di dalam grup `Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->name('admin.')->group(...)`:
     ```php
     Route::resource('performance-indicators', \App\Http\Controllers\Admin\PerformanceIndicatorController::class);
     Route::post('performance-indicators/{indicator}/targets/{year}', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'updateTarget'])->name('performance-indicators.targets.update');
     Route::post('performance-indicators/{indicator}/targets-batch', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'batchUpdateTargets'])->name('performance-indicators.targets.batch');
     Route::get('performance-indicators/{indicator}/targets/{year}/history', [\App\Http\Controllers\Admin\PerformanceIndicatorController::class, 'targetHistory'])->name('performance-indicators.targets.history');
     ```

### 4.3 Navigasi Menu
- `resources/views/layouts/navigation.blade.php`:
  - Menambahkan tautan navigasi `🎯 Indikator Kinerja` di bagian menu Administrator (desktop dan responsive mobile).

### 4.4 Tampilan Antarmuka (Views)
- `resources/views/admin/performance-indicators/index.blade.php`:
  - Halaman utama tabel matriks 5 tahun dengan Alpine.js untuk pencarian, pemilih tahun awal rentang 5 tahun, modal kelola indikator, modal edit nilai target, dan modal riwayat versi target.

---

## 5. Pertanyaan & Konfirmasi untuk Pengguna (Open Questions)

> [!NOTE]
> 1. **Penentuan Rentang 5 Tahun**:
>    - Apakah Anda menyukai opsi rentang 5 tahun yang dapat dipilih tahun awalnya secara dinamis melalui dropdown (misal memilih tahun awal `2024`, maka sistem menampilkan 5 kolom: `2024`, `2025`, `2026`, `2027`, `2028`), dengan default tahun berjalan?
> 2. **Alasan Perubahan Nilai Target**:
>    - Ketika admin mengedit nilai target (menghasilkan versi baru `V2`, `V3`), apakah kolom "Alasan / Catatan Perubahan" sebaiknya dijadikan **wajib diisi** (*required*) agar audit trail memiliki konteks yang jelas, atau opsional?
> 3. **Ekspor Data**:
>    - Apakah perlu ditambahkan tombol ekspor Excel / PDF untuk tabel matriks indikator kinerja 5 tahunan ini di masa mendatang?

---

## 6. Rencana Verifikasi & Pengujian
1. **Automated Feature Testing (Pest / PHPUnit)**:
   - Buat `tests/Feature/Admin/PerformanceIndicatorTest.php`:
     - Test hak akses: Admin dapat mengakses, Supervisor & Operator dilarang (403 Forbidden).
     - Test CRUD indikator kinerja induk.
     - Test input target tahunan baru (mencatat versi 1).
     - Test pengeditan target tahunan (mencatat versi 2, nilai lama, nilai baru, catatan perubahan).
     - Test endpoint API/JSON riwayat target.
2. **Kompilasi Frontend**:
   - Menjalankan `bun run build`.
3. **Full Regression Suite**:
   - Menjalankan `php artisan test` untuk memastikan 100% tes lama tetap passed.
