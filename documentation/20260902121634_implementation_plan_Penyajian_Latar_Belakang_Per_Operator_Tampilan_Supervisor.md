# Implementation Plan - Penyajian Latar Belakang RBA per Operator pada Tampilan Supervisor

Menyajikan data Latar Belakang RBA secara terpisah, jelas, dan terstruktur untuk setiap Operator di bawah naungan unit kerja Supervisor pada halaman Review RBA suatu periode (`supervisor.submissions.show`), sehingga Supervisor dapat meninjau latar belakang spesifik milik masing-masing operator secara transparan.

---

## User Review Required

> [!IMPORTANT]
> **Alur Bisnis & Rancangan Penyajian:**
> 1. **Penyimpanan Terpisah per Operator (`rba_submission_operator_backgrounds`):**
>    - Sama seperti dokumen KAK, RAK, dan RTP yang disimpan per operator (`user_id`), Latar Belakang kini memiliki tabel relasi tersendiri: `rba_submission_operator_backgrounds` (`rba_submission_id`, `user_id`, `background`).
>    - Setiap Operator mengisi dan memperbarui latar belakang usulan miliknya sendiri tanpa saling menimpa (*no overwrite*).
> 2. **Tampilan Supervisor (`supervisor.submissions.show`):**
>    - Bagian **Latar Belakang RBA** dirombak dari teks tunggal biasa menjadi **Daftar Kartu Latar Belakang per Operator**:
>      - Setiap operator di unit kerja ditampilkan lengkap dengan nama, NIP, status pengisian (✓ *Latar Belakang Terisi* vs ⚠️ *Belum Mengisi*), dan waktu pembaruan terakhir.
>      - Konten latar belakang disajikan di dalam kotak khusus berformat rapi (*whitespace-pre-wrap*).
>    - Jika ada data latar belakang lama (sebelum migrasi) yang tersimpan di kolom `rba_submissions.background`, sistem menyediakan kotak cadangan (*fallback display*) sehingga tidak ada data historis yang hilang.
> 3. **Sinkronisasi Otomatis ke Laporan Cetak:**
>    - Saat operator menyimpan latar belakangnya, sistem secara otomatis mengompilasi seluruh latar belakang operator di unit tersebut ke kolom `rba_submissions.background` dengan format `[Nama Operator]: [Isi Latar Belakang]`.
>    - Hal ini memastikan fitur cetak Supervisor dan filter cetak per operator yang sudah ada tetap berjalan 100% harmonis.

---

## Proposed Changes

### 1. Database Migration & Model Layer

#### [NEW] [2026_09_02_121634_create_rba_submission_operator_backgrounds_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_02_121634_create_rba_submission_operator_backgrounds_table.php)
- Membuat tabel `rba_submission_operator_backgrounds`:
  - `id`
  - `rba_submission_id` (foreign key ke `rba_submissions`, cascade on delete)
  - `user_id` (foreign key ke `users`, cascade on delete)
  - `background` (text)
  - `timestamps`
  - Unique constraint: `['rba_submission_id', 'user_id']`

#### [NEW] [RbaSubmissionOperatorBackground.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmissionOperatorBackground.php)
- Model Eloquent dengan relasi `submission()` dan `user()`.

#### [MODIFY] [RbaSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmission.php)
- Menambahkan relasi `operatorBackgrounds()`: `hasMany(RbaSubmissionOperatorBackground::class)`.

---

### 2. Controller Layer

#### [MODIFY] [ReviewController.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Pada method `show()`:
  - Eager load relasi `operatorBackgrounds.user`.
  - Mengelompokkan / mengindeks data latar belakang per operator (`$operatorBackgrounds = $submission->operatorBackgrounds->keyBy('user_id')`).
  - Mengirimkan variabel `$operatorBackgrounds` ke view `supervisor.submissions.show`.

#### [MODIFY] [SubmissionController.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Pada method `show()`:
  - Memuat latar belakang milik operator yang sedang login (`$myBackground`).
- Pada method `updateBackground()`:
  - Menyimpan data ke tabel `rba_submission_operator_backgrounds` dengan `user_id = Auth::id()`.
  - Mengompilasi latar belakang seluruh operator ke kolom `rba_submissions.background` agar laporan cetak tetap kompatibel.

#### [MODIFY] [DetailController.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Memastikan pengecekan sebelum menambah rincian belanja memeriksa latar belakang milik operator saat ini (`$submission->operatorBackgrounds()->where('user_id', Auth::id())->exists() || !empty($submission->background)`).

---

### 3. View Layer

#### [MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Mengganti tampilan blok latar belakang tunggal dengan **Komponen Latar Belakang RBA per Operator**:
  - Looping setiap operator di unit tersebut (`$operators`).
  - Kartu identitas operator dengan Avatar inisial, Nama, NIP, dan badge status (`✓ Latar Belakang Terisi` vs `⚠️ Belum Mengisi`).
  - Menampilkan teks latar belakang masing-masing operator dan timestamp pembaruan.
  - Menampilkan blok fallback jika ada teks submission historis.

#### [MODIFY] [show.blade.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Menyesuaikan form latar belakang agar menampilkan teks latar belakang milik operator saat ini (`$myBackground`), serta menampilkan accordion referensi latar belakang rekan operator lain jika sudah terisi.

---

### 4. Automated Tests Layer

#### [MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Menambahkan pengujian pada halaman `supervisor.submissions.show`:
  - Memverifikasi bahwa Supervisor melihat kartu latar belakang Operator 1 dengan isi teks milik Operator 1.
  - Memverifikasi bahwa Supervisor melihat kartu latar belakang Operator 2 dengan isi teks milik Operator 2.
  - Memverifikasi badge status terisi vs belum terisi.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite ReviewTest & RbaDetailTest:
  `php artisan test --filter=ReviewTest`
  `php artisan test --filter=RbaDetailTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login sebagai **Operator 1**:
   - Buka submission RBA unit, isi Latar Belakang: *"Latar Belakang Ruang Operasi oleh Op 1"*.
   - Simpan.
2. Login sebagai **Operator 2** (pada unit yang sama):
   - Buka submission RBA yang sama, isi Latar Belakang: *"Latar Belakang Sterilisasi Alkes oleh Op 2"*.
   - Simpan.
3. Login sebagai **Supervisor**:
   - Buka halaman Review RBA unit (`/supervisor/submissions/{id}`).
   - Verifikasi pada bagian **Latar Belakang RBA per Operator**:
     - Kartu Operator 1 menampilkan *"Latar Belakang Ruang Operasi oleh Op 1"* dengan badge `✓ Latar Belakang Terisi`.
     - Kartu Operator 2 menampilkan *"Latar Belakang Sterilisasi Alkes oleh Op 2"* dengan badge `✓ Latar Belakang Terisi`.
     - Jika ada Operator 3 yang belum mengisi, tampil badge `⚠️ Belum Mengisi` dengan teks informatif.
