# Walkthrough: Revisi Permohonan RKBMD Berbasis Sub-Unit Kerja & Audit Logging

Dokumen ini menjelaskan implementasi perubahan arsitektur data pada modul **RKBMD (Rencana Kebutuhan Barang Milik Daerah)**, dari yang sebelumnya terikat secara eksklusif ke personil individu (`target_operator_id` dan `user_id`) menjadi berbasis **Sub-Unit Kerja** (ruangan/instalasi/bagian), dengan tetap mempertahankan integritas pencatatan riwayat (*history*) dan log aktivitas (*activity log*).

---

## 1. Latar Belakang & Masalah

Pada arsitektur sebelumnya:
1. **Sisi Tujuan (Inbound)**: Permohonan RKBMD langsung ditujukan ke akun individu operator PIC (`target_operator_id`). Ketika ada staf lain di sub-unit kerja yang sama (misal user *Dhimas* yang terpetakan ke Sub-Unit PDE bersama user *PDE*), user tersebut tidak dapat melihat daftar permohonan yang masuk ke ruangannya.
2. **Sisi Asal (Outbound)**: Permohonan hanya dianggap milik akun pemohon pribadi. Staf lain di sub-unit pemohon tidak mengetahui apakah rekan ruangannya sudah mengajukan permohonan barang tertentu, berpotensi menimbulkan duplikasi pengajuan barang.
3. **Kebutuhan Otorisasi**:
   - Seluruh anggota sub-unit tujuan berhak **memantau** permohonan masuk (Read-Only).
   - Hak membalas (*reply*) dan mengalihkan (*forward*) harus tetap **terkunci eksklusif untuk Operator Pengusul / PIC** (`can_propose = true`) dari sub-unit tujuan.
   - Seluruh anggota sub-unit pemohon berhak **memantau** pengajuan ruangannya.
   - Hak mengubah/mengedit (*update*) permohonan harus tetap **terkunci eksklusif untuk Pemohon Asli**.
   - Seluruh aktivitas pengguna (Pengajuan, Edit, Pengalihan, Balasan, Revisi Balasan) **wajib tetap tercatat secara akurat di log aktivitas dan tabel riwayat**.

---

## 2. Ringkasan Perubahan Teknis

### A. Skema Basis Data & Migrasi
File: [2026_11_08_100000_add_target_sub_unit_to_rkbmd_submissions_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_11_08_100000_add_target_sub_unit_to_rkbmd_submissions_table.php)

- Menambahkan kolom:
  - `target_unit_id` (foreign key `units.id`, nullable)
  - `target_sub_unit_id` (foreign key `sub_units.id`, nullable)
- **Auto-Backfill Data Produksi**: Migrasi secara otomatis membaca seluruh data eksisting di tabel `rkbmd_submissions`, menelusuri unit dan sub-unit dari operator pada `target_operator_id`, dan mengisi kolom baru tersebut tanpa menyebabkan *downtime* atau kehilangan data.

```php
// Cuplikan migrasi auto-backfill data eksisting
$submissions = DB::table('rkbmd_submissions')->whereNotNull('target_operator_id')->get();
foreach ($submissions as $sub) {
    $operator = DB::table('users')->where('id', $sub->target_operator_id)->first();
    if ($operator) {
        DB::table('rkbmd_submissions')->where('id', $sub->id)->update([
            'target_unit_id' => $operator->unit_id,
            'target_sub_unit_id' => $operator->sub_unit_id,
        ]);
    }
}
```

---

### B. Perubahan Model & Helper Otorisasi
File: [RkbmdSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php)

- Menambahkan relasi Eloquent:
  - `targetUnit()`: Relasi ke model `Unit` tujuan.
  - `targetSubUnit()`: Relasi ke model `SubUnit` tujuan.
- Menambahkan dan memperbarui metode otorisasi:
  - `canBeManagedBy(User $user)`: Memastikan aksi balas (*reply*) dan alihkan (*forward*) hanya dapat dieksekusi oleh Operator PIC (`can_propose = true`) yang berada pada sub-unit penerima (atau fallback ke `target_operator_id`).
  - `canEditReply(User $user)`: Memastikan edit balasan hanya dapat dilakukan oleh operator yang membalas atau PIC sub-unit penerima.
  - `canEditSubmission(User $user)`: Memastikan edit berkas permohonan hanya dapat dilakukan oleh **Pemohon Asli** (`$this->user_id === $user->id`), selama status permohonan masih `Diajukan`.

---

### C. Logika Controller & Scoping Kueri
File: [RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)

1. **`index()`**:
   - **Tab Permohonan Saya (Sub-Unit Kita)**: Menampilkan permohonan yang diajukan oleh user login **ATAU** seluruh permohonan yang diajukan oleh sub-unit kerjanya (`where('sub_unit_id', $user->sub_unit_id)->orWhere('user_id', $user->id)`).
   - **Tab Permohonan Masuk (Sub-Unit Kita)**: Menampilkan seluruh permohonan yang ditujukan ke sub-unit kerjanya (`where('target_sub_unit_id', $user->sub_unit_id)->orWhere('target_operator_id', $user->id)`). Staf non-PIC seperti user *Dhimas* kini dapat melihat seluruh berkas masuk.
2. **`show()`**:
   - Memperluas variabel `$isParticipant` sehingga rekan satu sub-unit pemohon maupun rekan satu sub-unit penerima memiliki hak akses melihat halaman detail berkas (Read-Only).
3. **`store()` & `update()`**:
   - Otomatis mengisi dan menyinkronkan `target_unit_id` dan `target_sub_unit_id` berdasarkan target PIC yang dipilih.
   - Pada `update()`, divalidasi ketat dengan `$rkbmd->canEditSubmission($user)`.
4. **`reply()`, `updateReply()`, dan `forward()`**:
   - Divalidasi ketat dengan `$rkbmd->canBeManagedBy($user)` / `$rkbmd->canEditReply($user)`.
   - Pada `forward()`, `target_unit_id` dan `target_sub_unit_id` diperbarui secara otomatis mengikuti sub-unit dari operator PIC tujuan baru.

---

### D. Tampilan Antarmuka (Blade Views)
1. [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php):
   - Judul tab disesuaikan: *"Permohonan Saya (Sub-Unit Kita)"* dan *"Permohonan Masuk (Sub-Unit Kita)"*.
   - Tombol aksi kondisional:
     - Jika pengguna adalah PIC tujuan: Tombol *"Tanggapi / Kelola"*.
     - Jika pengguna adalah staf biasa (non-PIC) di sub-unit tujuan: Tombol beralih menjadi *"Lihat Berkas →"*.
     - Jika pengguna bukan pemohon asli di tab permohonan diajukan: Tombol *"Edit"* tidak dimunculkan, hanya tombol *"Lihat Detail"*.
2. [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php):
   - Menambahkan banner informatif *"Akses Pemantauan Sub-Unit (Read-Only)"* ketika dibuka oleh staf non-PIC atau rekan satu ruangan.
   - Menyembunyikan form aksi dan modal dialog *Tanggapi Permohonan* serta *Alihkan Permohonan* bagi pengguna yang bukan PIC berwenang (`@if($rkbmd->canBeManagedBy(Auth::user()))`).
3. [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php):
   - Label target diperjelas: *"Sub-Unit Kerja & PIC RBA Tujuan"*.

---

## 3. Verifikasi Log Aktivitas & Jejak Audit

Sesuai instruksi khusus: **setelah pembaruan ini, seluruh kegiatan pengguna terhadap RKBMD tetap tercatat di log dengan benar**.

Sistem menerapkan dua lapis pencatatan log:

| Lapisan | Tabel Database | Mekanisme | Aksi yang Tercatat |
| :--- | :--- | :--- | :--- |
| **Audit Log Global** | `activity_logs` | Trait `LogsActivity` & `TracksUserAudit` pada Model `RkbmdSubmission` | Pencatatan otomatis event `created` dan `updated`, mencakup `user_id`, waktu WIB, perubahan nilai atribut (*before/after*). |
| **Riwayat Alur RKBMD** | `rkbmd_histories` | Explicit Call di `RkbmdController` pada setiap transaksi DB | Mencatat alur bisnis: `Pengajuan`, `Edit Permohonan`, `Pengalihan`, `Balasan`, dan `Edit Balasan`, lengkap dengan status awal/akhir, operator asal, dan operator tujuan. |

### Bukti Pengujian Otomatis Logging:
Pada pengujian automated test `test_activity_log_and_rkbmd_history_are_recorded_for_all_actions` di [RkbmdSubUnitTargetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdSubUnitTargetTest.php):

1. **Aksi Pengajuan (Store)**:
   - `RkbmdHistory`: Tercatat dengan `action = 'Pengajuan'`, `status_after = 'Diajukan'`.
   - `ActivityLog`: Tercatat dengan `action = 'created'`, `model_type = RkbmdSubmission`.
2. **Aksi Pengalihan (Forward)**:
   - `RkbmdHistory`: Tercatat dengan `action = 'Pengalihan'`, `from_operator_id`, `to_operator_id`, `status_after = 'Dialihkan'`.
   - `ActivityLog`: Tercatat dengan `action = 'updated'`.
3. **Aksi Balasan (Reply)**:
   - `RkbmdHistory`: Tercatat dengan `action = 'Balasan'`, `status_after = 'Dipenuhi'`.
   - `ActivityLog`: Tercatat dengan `action = 'updated'`.
4. **Aksi Edit Balasan (Update Reply)**:
   - `RkbmdHistory`: Tercatat dengan `action = 'Edit Balasan'`, `status_after = 'Dipenuhi Sebagian'`.
   - `ActivityLog`: Tercatat dengan `action = 'updated'`.
5. **Aksi Edit Permohonan (Update Submission)**:
   - `RkbmdHistory`: Tercatat dengan `action = 'Edit Permohonan'`.
   - `ActivityLog`: Tercatat dengan `action = 'updated'`.

---

## 4. Hasil Pengujian Sistem

Seluruh rangkaian pengujian fitur RKBMD dan sistem secara keseluruhan telah dieksekusi dengan hasil sukses tanpa regresi:

- **Pengujian Spesifik Sub-Unit & Logging** (`RkbmdSubUnitTargetTest`):
  - `incoming submissions are visible to all staff in target sub unit` : **PASS**
  - `target pic can reply and logs activity and history` : **PASS**
  - `outbound submissions are visible to all staff in applicant sub unit` : **PASS**
  - `activity log and rkbmd history are recorded for all actions` : **PASS**
  - `existing production data without target sub unit is accessible` : **PASS**

- **Pengujian Keseluruhan Modul RKBMD** (`php artisan test --filter=Rkbmd`):
  - **47 tests PASSED, 259 assertions PASSED (100%)**.

---

## 5. Ringkasan Hak Akses Matriks Peran

| Peran / Aktor | Tab Permohonan Saya (Keluar) | Tab Permohonan Masuk (Kedalam) | Aksi Balas / Alihkan | Aksi Edit Pengajuan |
| :--- | :--- | :--- | :--- | :--- |
| **Operator Pemohon Asli** | Melihat semua pengajuan sub-unit | Melihat permohonan masuk sub-unit | Terkunci (hanya jika dia PIC tujuan) | **Dapat Edit** (Status: Diajukan) |
| **Rekan Staf Sub-Unit Pemohon** | Melihat semua pengajuan sub-unit | Melihat permohonan masuk sub-unit | Terkunci (hanya jika dia PIC tujuan) | Terkunci (Read-Only) |
| **Operator PIC Sub-Unit Tujuan** | Sesuai sub-unit kerjanya | Melihat permohonan masuk sub-unit | **Dapat Membalas & Mengalihkan** | Terkunci |
| **Staf Biasa Sub-Unit Tujuan (Non-PIC, misal Dhimas)** | Sesuai sub-unit kerjanya | Melihat permohonan masuk sub-unit | Terkunci (Read-Only) | Terkunci |
| **Supervisor Unit** | Tab Unit Terkait (Read-Only) | Tab Unit Terkait (Read-Only) | Terkunci (Read-Only) | Terkunci (Read-Only) |
| **Administrator** | Tab Seluruh Rumah Sakit (Read-Only) | Tab Seluruh Rumah Sakit (Read-Only) | Terkunci (Read-Only) | Terkunci (Read-Only) |

