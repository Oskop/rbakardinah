# Implementation Plan - Pengelompokan Dokumen KAK, RAK, dan RTP Berdasarkan Unit Bawahan (Operator) di Tampilan Supervisor

Rencana implementasi ini bertujuan untuk memodifikasi cara penyimpanan dan penyajian dokumen KAK, RAK, dan RTP pada tampilan Supervisor, agar dokumen-dokumen tersebut dipisah berdasarkan masing-masing Operator (unit bawahan) yang mengunggahnya.

---

## User Review Required

> [!IMPORTANT]
> **Perubahan Skema Database**:
> * Menambahkan kolom `user_id` pada tabel `rba_submission_documents`.
> * Menghapus indeks unique lama `['rba_submission_id', 'type']` dan menggantinya dengan indeks unique baru `['rba_submission_id', 'type', 'user_id']`.
> * Melakukan penyesuaian data lama (*migration data*) dengan mengisi `user_id` dari versi dokumen pertama yang dicatat di `rba_submission_document_versions`.

---

## Proposed Changes

### Database Layer

#### [NEW] [2026_07_17_182358_add_user_id_to_rba_submission_documents_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_07_17_182358_add_user_id_to_rba_submission_documents_table.php)
* Menambahkan kolom `user_id` (foreign key ke `users.id`, nullable / cascade on delete).
* Menghapus indeks unique `rba_submission_documents_rba_submission_id_type_unique`.
* Menambahkan indeks unique `rba_sub_docs_sub_type_user_unique` pada `['rba_submission_id', 'type', 'user_id']`.
* Mengisi data `user_id` pada data dokumen lama dari riwayat versi.

---

### Backend Layer (Models & Controllers)

#### [MODIFY] [RbaSubmissionDocument.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmissionDocument.php)
* Menambahkan `user_id` ke properti `$fillable`.
* Menambahkan relasi `user()` (`belongsTo` ke `User`).

#### [MODIFY] [DocumentController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DocumentController.php)
* **Method `uploadDocument`**: Mengubah query `firstOrCreate` agar menyertakan `'user_id' => Auth::id()`.
* **Method `history`**: Mengakomodasi filter `user_id` (jika diakses Supervisor) agar riwayat versi dokumen yang diambil sesuai dengan Operator yang dipilih.

#### [MODIFY] [SubmissionController.php (Operator)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
* **Method `show`**: Menambahkan filter `where('user_id', Auth::id())` pada relasi eager loading `documents` agar Operator hanya memuat dokumen miliknya sendiri.

#### [MODIFY] [ReviewController.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
* **Method `show`**:
  * Memuat data seluruh Operator (unit bawahan) dengan `role = 'Operator'` dan `unit_id` yang sama dengan unit Supervisor.
  * Mengambil dokumen-dokumen submission yang dikelompokkan berdasarkan `user_id`.
  * Mengirim data `$operators` dan `$documents` ke view.

---

### Frontend Layer (Views)

#### [MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
* Mengubah struktur section Dokumen Pendukung (KAK, RAK, RTP).
* Menampilkan daftar Operator bawahan (`@foreach($operators as $operator)`), di mana setiap Operator memiliki grid berkas KAK, RAK, dan RTP masing-masing.
* Menyertakan parameter `user_id` pada tombol/tautan riwayat versi dokumen:
  ```html
  route('submissions.documents.history', ['submission' => $submission->id, 'type' => $docType, 'user_id' => $operator->id])
  ```

---

## Verification Plan

### Automated Tests
* Jalankan suite pengujian PHPUnit untuk memastikan tidak ada fungsionalitas RBA submission & dokumen yang rusak:
  ```bash
  php artisan test
  ```

### Manual Verification
1. Jalankan migrasi database: `php artisan migrate`.
2. Login sebagai **Operator A** (Unit Pelayanan) -> Unggah Dokumen KAK.
3. Login sebagai **Operator B** (Unit Pelayanan) -> Unggah Dokumen KAK.
4. Login sebagai **Supervisor** (Unit Pelayanan) -> Buka halaman Detail Review Submissions.
5. Verifikasi bahwa Dokumen KAK Operator A dan Operator B tampil terpisah di bawah nama/seksi masing-masing Operator bawahan.
