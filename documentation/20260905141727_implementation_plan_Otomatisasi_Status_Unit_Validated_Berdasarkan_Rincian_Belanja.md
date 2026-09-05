# Implementation Plan: Otomatisasi Status Unit Validated Berdasarkan Rincian Belanja

Mengubah mekanisme penentuan **Status Unit (Makro - `RbaSubmission`)** menjadi **otomatis (reaktif)** berdasarkan kelengkapan validasi seluruh butir rincian usulan belanja (`RbaDetail`) di bawah unit kerja tersebut, menggantikan penekanan tombol manual *"Validasi & Lanjutkan"*.

---

## 1. Analisis Masalah & Latar Belakang

### Kondisi Saat Ini (As-Is)
- Saat ini, status makro unit (`RbaSubmission::$status_submission`) diubah menjadi `Validated` secara **manual** melalui tombol *"Validasi & Lanjutkan"* di halaman Review Supervisor (`/supervisor/submissions/{submission}`).
- Terdapat **desinkronisasi** antara status makro unit dan status mikro rincian belanja:
  1. Supervisor bisa saja sudah memvalidasi 100% rincian belanja, namun lupa menekan tombol manual *"Validasi & Lanjutkan"*, sehingga status unit di panel monitoring Admin tetap tertahan di `Pending Supervisor`.
  2. Supervisor dapat menekan tombol manual *"Validasi & Lanjutkan"* meskipun masih ada rincian belanja yang belum divalidasi atau bahkan berstatus ditolak.
  3. Jika Supervisor membatalkan validasi salah satu rincian belanja setelah menekan tombol manual, status makro unit tetap `Validated`, sehingga menimbulkan anomali data.

### Solusi yang Diusulkan (To-Be)
- Menghapus ketergantungan pada tombol manual *"Validasi & Lanjutkan"*.
- Mengimplementasikan fungsi sinkronisasi status otomatis (**Single Source of Truth**):
  - **`Draft`**: Belum ada rincian belanja yang diajukan ke Supervisor (`is_submitted = true` berjumlah 0).
  - **`Pending Supervisor`**: Sudah ada rincian belanja yang diajukan, **tetapi belum semuanya divalidasi** (masih ada yang pending review atau ditolak).
  - **`Validated`**: **Semua** rincian belanja yang diajukan oleh Operator di unit tersebut telah berhasil divalidasi oleh Supervisor (`is_validated = true` untuk semua item yang diajukan, dan jumlah usulan > 0).

---

## 2. Diagram Alur Logika Status Otomatis

```
                  [RBA Header Dibuat]
                           │
                           ▼
                        [Draft]
                           │
                           │ Operator mengajukan item (submitItem / submit)
                           ▼
                [Pending Supervisor] ◄──────────────────────────────┐
                           │                                        │
           Supervisor memvalidasi rincian                           │ Supervisor batalkan
         (Apakah SEMUA item telah valid?)                           │ validasi salah satu item
                    │            │                                  │ ATAU Operator ajukan
                   Ya          Tidak                                │ item revisi/baru
                    │            │                                  │
                    ▼            └──────────────────────────────────┘
               [Validated]
  (Otomatis disahkan di level unit)
```

---

## 3. Matriks Kondisi Penentuan Status Unit

| Status Unit | Kondisi Rincian Belanja (`RbaDetail`) di Bawah Unit | Aksi Pemicu |
| :--- | :--- | :--- |
| **`Draft`** | Jumlah usulan diajukan (`is_submitted = true`) = 0. | Inisiasi RBA Header baru, atau penghapusan rincian belanja terakhir. |
| **`Pending Supervisor`** | Ada usulan diajukan (`count > 0`), namun ada minimal satu usulan yang belum divalidasi (`is_validated = false`) atau berstatus ditolak (`is_rejected = true`). | Operator mengajukan rincian baru/revisi, Supervisor membatalkan validasi salah satu rincian, atau Supervisor menolak rincian belanja. |
| **`Validated`** | Ada usulan diajukan (`count > 0`), **seluruh** usulan diajukan berstatus `is_validated = true`, dan tidak ada usulan berstatus ditolak (`is_rejected = false`). | Supervisor mengklik validasi pada rincian belanja terakhir yang belum divalidasi. |

---

## 4. Rencana Perubahan Komponen

### A. Model `RbaSubmission`
#### [MODIFY] [app/Models/RbaSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmission.php)
- Menambahkan method helper `syncValidationStatus()`:
  ```php
  public function syncValidationStatus(): string
  {
      $submittedDetails = $this->details()->where('is_submitted', true)->get();

      if ($submittedDetails->isEmpty()) {
          $newStatus = 'Draft';
      } else {
          $totalSubmitted = $submittedDetails->count();
          $totalValidated = $submittedDetails->where('is_validated', true)->count();
          $hasRejection = $submittedDetails->where('is_rejected', true)->isNotEmpty();

          if ($totalValidated === $totalSubmitted && !$hasRejection) {
              $newStatus = 'Validated';
          } else {
              $newStatus = 'Pending Supervisor';
          }
      }

      if ($this->status_submission !== $newStatus) {
          $this->update(['status_submission' => $newStatus]);
      }

      return $newStatus;
  }
  ```

---

### B. Controller Supervisor & Operator
#### [MODIFY] [app/Http/Controllers/Supervisor/ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
1. **`toggleDetailValidation`**:
   - Setelah status validasi rincian diubah, panggil `$detail->submission->syncValidationStatus()`.
   - Perbarui pesan flash session: jika status unit berubah menjadi `Validated`, tampilkan notifikasi istimewa bahwa seluruh usulan unit telah lengkap dan tervalidasi.
2. **`rejectDetail`**:
   - Setelah rincian ditolak, panggil `$detail->submission->syncValidationStatus()` untuk memastikan status unit berada pada `Pending Supervisor`.
3. **`validate` (Method Tombol Lama)**:
   - Di-update agar memanggil `syncValidationStatus()`. Jika belum semua divalidasi, tampilkan pesan peringatan ramah bahwa masih ada rincian yang belum divalidasi.

#### [MODIFY] [app/Http/Controllers/Operator/DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
1. **`submitItem`**:
   - Memanggil `$detail->submission->syncValidationStatus()` saat operator mengajukan rincian baru.
2. **`destroy`**:
   - Memanggil `$submission->syncValidationStatus()` saat operator menghapus rincian usulan belanja.
3. **`uploadVersion`**:
   - Memanggil `$detail->submission->syncValidationStatus()` saat operator mengunggah revisi PDF pada rincian yang sebelumnya ditolak.

---

### C. Antarmuka Pengguna (UI / Views)
#### [MODIFY] [resources/views/supervisor/submissions/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Mengganti tombol manual *"Validasi & Lanjutkan"* dengan **Badge Status Validasi Dinamis**:
  - **Jika Status `Validated`**:
    Menampilkan badge hijau bersinar:
    `✓ Seluruh Usulan Unit Divalidasi (Validated)` lengkap dengan icon centang ganda.
  - **Jika Masih `Pending Supervisor`**:
    Menampilkan progress pill interaktif:
    `⏳ Validasi Berjalan: X/Y Usulan Disetujui` (membantu supervisor mengetahui berapa usulan yang tersisa).
  - **Jika Masih `Draft`**:
    Menampilkan badge netral: `📝 Draft (Menunggu Pengajuan Operator)`.

---

### D. Pengujian & Otomasi (Tests)
#### [MODIFY] [tests/Feature/Supervisor/ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Memperbarui test case `test_supervisor_can_validate_submission`:
  - Menguji bahwa saat supervisor memvalidasi seluruh rincian belanja yang diajukan, status `RbaSubmission` secara otomatis menjadi `Validated`.
  - Menguji bahwa ketika salah satu rincian belanja dibatalkan validasinya (`toggleDetailValidation`), status `RbaSubmission` otomatis turun kembali menjadi `Pending Supervisor`.
  - Menguji bahwa ketika rincian ditolak (`rejectDetail`), status `RbaSubmission` tetap `Pending Supervisor`.
- Menambahkan test case baru untuk memastikan penambahan item baru oleh operator pada submission yang sudah `Validated` akan mereset status unit kembali ke `Pending Supervisor`.

---

## 5. Rencana Verifikasi

1. **Automated Unit & Feature Tests**:
   - Menjalankan `php artisan test --filter=ReviewTest` untuk memverifikasi logika baru.
   - Menjalankan seluruh test suite aplikasi (`php artisan test`) untuk memastikan 144+ test cases tetap lulus 100%.
2. **Kompilasi Aset**:
   - Menjalankan `bun run build` untuk memvalidasi template Blade.
3. **Manual Verification Simulation**:
   - Memverifikasi tampilan halaman review supervisor saat usulan belum lengkap divalidasi vs setelah semua usulan divalidasi.
   - Memverifikasi bahwa panel monitoring di `/admin/headers/{header}` langsung menampilkan status `Validated` tanpa perlu klik tombol manual lagi.

---

## 6. Persetujuan Pengguna (User Review)

Apakah konsep otomatisasi status makro `Validated` ini sudah sesuai dengan kebutuhan yang Anda maksudkan? Mohon berikan approval agar pengerjaan implementasi dapat segera kami eksekusi.
