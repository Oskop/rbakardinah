# Implementation Plan - Reset Status Ditolak Menjadi Draft Saat Upload Revisi PDF

Memastikan bahwa ketika Operator mengunggah versi baru file PDF (klik tombol **Revisi**) pada usulan rincian belanja yang sebelumnya berstatus **Ditolak (`is_rejected = true`)** oleh Supervisor, status usulan tersebut **otomatis kembali menjadi Draft** (`is_rejected = false`, `is_validated = false`, `is_submitted = false`, `rejection_reason = null`).

---

## Analisis Akar Masalah (Root Cause)

Pada method `DetailController::uploadVersion()`, saat versi PDF baru berhasil disimpan ke tabel `rba_attachments`, sistem hanya mereset `is_validated` dan `is_submitted` ke `false`, namun **tidak mereset kolom `is_rejected`, `rejected_at`, `rejected_by`, dan `rejection_reason`**.
Akibatnya, karena `$detail->is_rejected` masih bernilai `true`, antarmuka tetap menampilkan badge status **"Tolak"** dan memblokir alur pengajuan ulang ke Supervisor.

---

## User Review Required

> [!IMPORTANT]
> **Alur Pasca-Upload Revisi PDF:**
> 1. Saat Operator mengunggah file PDF revisi baru (misal V2 / V3) pada usulan yang ditolak:
>    - Atribut penolakan (`is_rejected`, `rejected_at`, `rejected_by`, `rejection_reason`) dibersihkan/direset ke `null`/`false`.
>    - Atribut validasi & pengajuan (`is_validated`, `is_submitted`, dll.) direset ke `null`/`false`.
>    - Status usulan otomatis berubah menjadi **Draft**.
> 2. Operator kemudian dapat memeriksa usulan dan menekan tombol **Ajukan** agar usulan revisi tersebut masuk kembali ke daftar review Supervisor.

---

## Proposed Changes

### 1. Operator Detail Controller (`app/Http/Controllers/Operator/DetailController.php`)

#### [MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- **Method `uploadVersion(Request $request, RbaDetail $detail)`**:
  - Memperbarui pemanggilan `$detail->update(...)` untuk membersihkan seluruh flag penolakan dan validasi:
    ```php
    $detail->update([
        'is_validated' => false,
        'validated_at' => null,
        'validated_by' => null,
        'is_submitted' => false,
        'is_rejected' => false,
        'rejected_at' => null,
        'rejected_by' => null,
        'rejection_reason' => null,
    ]);
    ```
  - Memperbarui notifikasi sukses menjadi: `"Versi PDF baru (V{$newVersion}) berhasil diunggah. Status usulan kembali menjadi Draft (silakan klik Ajukan ke Supervisor)."`

---

### 2. Pengujian Otomatis (`tests/Feature/Operator/RbaDetailTest.php`)

#### [MODIFY] [RbaDetailTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailTest.php)
- Menambahkan pengujian:
  - `test_uploading_revision_pdf_on_rejected_detail_resets_status_to_draft`:
    1. Detail berstatus ditolak oleh Supervisor (`is_rejected = true`, `rejection_reason = 'Perbaiki dokumen'`).
    2. Operator mengunggah revisi PDF baru (`uploadVersion`).
    3. Memverifikasi bahwa `is_rejected` menjadi `false`, `rejection_reason` menjadi `null`, dan `is_submitted` menjadi `false` (status Draft).
    4. Operator dapat mengajukan kembali (`submitItem`) sehingga `is_submitted` menjadi `true`.

---

## Verification Plan

### Automated Tests
- Jalankan test suite rincian usulan:
  `php artisan test --filter=RbaDetailTest`
- Jalankan test suite review supervisor:
  `php artisan test --filter=ReviewTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Supervisor**, tolak salah satu usulan rincian belanja dengan mengisi alasan penolakan.
2. Login sebagai **Operator**, buka halaman pengusulan RBA:
   - Status usulan saat ini adalah **Tolak** dengan alasan penolakan.
3. Unggah file PDF baru melalui form revisi dan klik tombol **Revisi**.
4. Verifikasi:
   - Muncul notifikasi sukses.
   - Status usulan berubah menjadi **Draft**.
   - Badge "Tolak" dan pesan alasan penolakan sebelumnya telah dibersihkan.
   - Tombol **Ajukan** muncul dan aktif.
5. Klik **Ajukan**, verifikasi status berubah menjadi **Ajuan** dan muncul di halaman review Supervisor.
