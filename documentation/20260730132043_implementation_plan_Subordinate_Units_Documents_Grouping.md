# Implementation Plan - Grouping RBA Documents by Subordinate Operator Users (Units) for Supervisor

Rencana implementasi ini bertujuan untuk memodifikasi cara penyimpanan dan penyajian dokumen KAK, RAK, dan RTP pada tampilan Supervisor, agar dokumen-dokumen tersebut dipisah berdasarkan masing-masing Operator (unit bawahan) yang mengunggahnya.

---

## Latar Belakang & Alur Bisnis Baru

Saat ini, dokumen KAK, RAK, dan RTP disimpan per `RbaSubmission` tanpa membedakan Operator pengunggahnya. Karena beberapa Operator berada di bawah unit yang sama (dan berbagi satu `RbaSubmission`), berkas yang diunggah oleh satu Operator dapat saling menindih berkas milik Operator lain di unit yang sama.

Dengan rencana ini:
1. Setiap dokumen `RbaSubmissionDocument` akan dikaitkan dengan `user_id` Operator pengunggah.
2. Operator hanya dapat melihat dan mengelola dokumen miliknya sendiri.
3. Supervisor dapat melihat pengelompokan berkas KAK, RAK, dan RTP untuk **setiap** Operator yang ada di bawah naungan unitnya.

---

## Usulan Perubahan Database

### Modifikasi Tabel `rba_submission_documents`
* Menambahkan kolom `user_id` (foreign key ke tabel `users`, cascade on delete).
* Menghapus indeks unique `rba_submission_documents_rba_submission_id_type_unique` `['rba_submission_id', 'type']`.
* Menambahkan indeks unique baru `['rba_submission_id', 'type', 'user_id']`.
* Migrasi data lama: mengisi kolom `user_id` dengan ID user pengunggah pertama dari riwayat versi dokumen.

---

## Usulan Perubahan Program

### 1. Backend Layer (Models & Controllers)

#### [MODIFY] [RbaSubmissionDocument.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaSubmissionDocument.php)
* Menambahkan `user_id` ke properti `$fillable`.
* Menambahkan relasi `user()` (belongsTo ke `User`).

#### [MODIFY] [DocumentController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DocumentController.php)
* **Method `uploadDocument`**: Ubah query `firstOrCreate` agar menyertakan `user_id => Auth::id()`.
* **Method `history`**: Tambahkan penyaringan berdasarkan `user_id` agar Supervisor/Admin dapat melihat riwayat versi dokumen milik operator tertentu secara spesifik.

#### [MODIFY] [SubmissionController.php (Operator)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
* **Method `show`**: Tambahkan filter `where('user_id', Auth::id())` pada relasi eager loading `documents` agar Operator hanya memuat dokumen miliknya sendiri.

#### [MODIFY] [ReviewController.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
* **Method `show`**:
  * Muat data seluruh Operator (unit bawahan) dengan `role = 'Operator'` dan `unit_id` yang sama dengan unit Supervisor.
  * Ambil dokumen-dokumen submission yang ada, dikelompokkan berdasarkan `user_id`.
  * Kirim data `$operators` dan `$documents` ke view.

---

### 2. Frontend Layer (Views)

#### [MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
* Ubah tata letak Dokumen Pendukung (KAK, RAK, RTP). Lakukan perulangan (`@foreach`) untuk masing-masing `$operators`.
* Tampilkan nama operator, diikuti grid berisi KAK, RAK, dan RTP khusus operator tersebut.
* Sesuaikan tautan riwayat versi agar menyertakan parameter `user_id`:
  ```html
  route('submissions.documents.history', ['submission' => $submission->id, 'type' => $docType, 'user_id' => $operator->id])
  ```

---

## Rencana Verifikasi

### Pengujian Otomatis (PHPUnit)
* Jalankan seluruh suite pengujian guna memastikan tidak ada fungsionalitas yang mengalami regresi:
  ```bash
  php artisan test
  ```

### Pengujian Manual
1. Pastikan migrasi database berjalan sukses.
2. Login sebagai Operator A (Unit Pelayanan). Unggah KAK.
3. Login sebagai Operator B (Unit Pelayanan). Unggah KAK.
4. Login sebagai Supervisor (Unit Pelayanan). Pastikan pada halaman detail review, KAK dari Operator A dan Operator B tampil terpisah di bawah nama mereka masing-masing.
