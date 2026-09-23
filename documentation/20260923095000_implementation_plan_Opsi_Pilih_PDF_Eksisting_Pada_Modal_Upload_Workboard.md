# Rencana Implementasi: Opsi Beralih ke PDF Eksisting pada Modal Upload Tabel Usulan Belanja

Menambahkan opsi untuk beralih/mengganti dokumen ke PDF eksisting (yang sudah ada pada pengajuan terkait) pada modal upload di tabel usulan belanja halaman `/operator/submissions/{submission_id}` tampilan operator, **tanpa merombak atau mengganggu alur proses yang sudah ada saat ini**.

---

## User Review Required

> [!IMPORTANT]
> **Jaminan Bebas Regresi (Non-Breaking Guarantee):**
> 1. Alur unggah berkas revisi PDF fisik yang sudah berjalan saat ini (baik mode *Perbarui Bersama* maupun *Pisahkan Dokumen*) tetap menjadi opsi default dan berjalan 100% seperti sebelumnya tanpa perubahan logika inti.
> 2. Opsi baru (*Pilih Dokumen Lain yang Ada*) disediakan berdampingan melalui *segmented control* / pemilih mode di dalam modal. Operator dapat dengan bebas memilih apakah ingin mengunggah berkas PDF baru atau cukup menautkan ke dokumen PDF yang sudah pernah diunggah dalam pengajuan tersebut.
> 3. Setelah beralih ke dokumen eksisting, status usulan rincian belanja secara otomatis disinkronkan kembali menjadi `Draft` (siap diajukan kembali ke Supervisor) dengan lampiran versi terbaru dari dokumen yang dipilih.

---

## Proposed Changes

### 1. Controller & Backend Layer

#### [MODIFY] [SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)
- Pada method `show(RbaSubmission $submission)`:
  - Ambil koleksi dokumen belanja eksisting pada pengajuan tersebut:
    ```php
    $existingDocuments = \App\Models\RbaDetailDocument::where('rba_submission_id', $submission->id)
        ->with(['latestVersion.details.accountCode'])
        ->orderByDesc('id')
        ->get();
    ```
  - Teruskan `$existingDocuments` ke view `operator.submissions.show` bersama variabel lainnya.

#### [MODIFY] [DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Pada method `uploadVersion(Request $request, RbaDetail $detail)`:
  - Tambahkan penanganan kondisi jika request memilih `source_mode === 'existing'`:
    - Validasi `rba_detail_document_id` wajib ada dan valid di tabel `rba_detail_documents`.
    - Lakukan pemeriksaan otorisasi: unit ownership check dan `Gate::authorize('uploadVersion', $detail)`.
    - Pastikan dokumen tersebut milik `rba_submission_id` yang sama dan memiliki versi lampiran fisik (`latestVersion`).
    - Dalam `DB::transaction`, hubungkan attachment versi terbaru ke rincian usulan: `$detail->attachments()->sync([$doc->latestVersion->id]);`.
    - Reset status usulan rincian ke `Draft` (`is_validated = false`, `is_submitted = false`, `is_rejected = false`, dsb.).
    - Jalankan `$detail->submission->syncValidationStatus()`.
    - Kembalikan pesan sukses yang informatif: `"Berhasil beralih ke dokumen eksisting: '{nama_dokumen}' (V{versi}). Status usulan kembali menjadi Draft."`.
  - Jika `source_mode !== 'existing'` (alur unggah berkas fisik revisi baru):
    - Jalankan seluruh kode validasi berkas fisik PDF dan proses revisi dokumen bersama / mandiri yang sudah ada **secara utuh tanpa perubahan**.

#### [MODIFY] [RbaDetail.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDetail.php)
- Pada method `hasUploadedRevision()`:
  - Pastikan pengecekan penyesuaian pagu mempertimbangkan timestamp pivot keterikatan lampiran (`$latest->pivot?->created_at`) di samping `$latest->created_at`.
  - Hal ini menjamin bahwa jika usulan yang melebihi pagu dialihkan ke dokumen PDF eksisting setelah pagu ditetapkan, sistem tetap mengenalinya sebagai telah memiliki revisi yang sah.

---

### 2. View & User Interface Layer

#### [MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)
1. **Inisialisasi Data Dokumen untuk Alpine.js**:
   - Format `$existingDocuments` menjadi payload array JSON ringan (`allDocuments`) berisi `id`, `name`, `version`, `filename`, `file_url`, dan `details_count`.
   - Tambahkan state Alpine pada komponen pembungkus:
     - `sourceMode: 'upload'` (default: mode unggah berkas revisi baru)
     - `selectedExistingDocId: null`
     - `allDocuments: @js($documentsData)`
     - Helper getter `availableExistingDocs` untuk menyaring daftar dokumen selain dokumen yang saat ini sedang digunakan oleh usulan terkait.
2. **Standardisasi Parameter Pemanggilan `openUploadModal`**:
   - Pada ketiga tombol pemanggil modal di tabel usulan (tombol upload revisi mode normal di baris 454, tombol upload revisi saat over pagu di baris 506, dan tombol upload versi lebih baru di baris 542), pastikan data `currentDocId`, `documentName`, dan `sharedDetails` diteruskan secara seragam.
3. **Penyempurnaan Modal Dialog**:
   - Tambahkan **Segmented Control / Tab Switcher** yang elegan di bagian atas form modal:
     - Tab 1: **"📤 Unggah Berkas PDF Revisi"** *(Default)*
     - Tab 2: **"🔄 Pilih Dokumen Lain yang Ada"** *(Menampilkan badge jumlah dokumen yang tersedia)*
   - **Tampilan saat Tab "Unggah Berkas PDF Revisi" aktif**:
     - Menampilkan alur yang sudah ada: pilihan cakupan (Perbarui Bersama vs Pisahkan Dokumen), checklist usulan terkait, alert over pagu, dan dropzone picker file PDF.
     - Input file hanya `required` saat `sourceMode === 'upload'`.
   - **Tampilan saat Tab "Pilih Dokumen Lain yang Ada" aktif**:
     - Menampilkan petunjuk singkat.
     - Menampilkan daftar kartu interaktif pilihan dokumen eksisting (radio button, nama dokumen, badge versi, nama file, jumlah usulan belanja terikat, serta tombol pratinjau "Lihat PDF").
     - Jika belum ada dokumen lain, menampilkan pesan informasi yang ramah.
   - **Tombol Aksi Modal**:
     - Secara dinamis menyesuaikan label dan status disable:
       - Saat mode Unggah: label "Simpan & Unggah PDF", aktif jika file telah dipilih.
       - Saat mode Eksisting: label "Ganti ke Dokumen Terpilih", aktif jika dokumen eksisting telah dipilih.

---

## Verification Plan

### Automated Tests
Jalankan pengujian otomatis untuk memverifikasi alur baru dan memastikan tidak ada regresi pada pengujian yang sudah ada:
```bash
# 1. Jalankan pengujian spesifik fitur usulan belanja dan revisi dokumen
php artisan test --filter=RbaDetailTest

# 2. Tambahkan unit/feature test baru di RbaDetailTest:
#    - test_operator_can_switch_detail_to_existing_document_via_upload_version
#    - test_switching_to_existing_document_resets_status_to_draft
#    - test_existing_upload_flow_remains_functional
php artisan test --filter=RbaDetailTest

# 3. Jalankan seluruh test suite operator
php artisan test tests/Feature/Operator
```

### Manual Verification
1. Login sebagai Operator pengusul dan buka halaman `/operator/submissions/{submission_id}`.
2. Temukan usulan belanja di tabel Rincian Biaya, klik tombol **Upload PDF Revisi** / **Unggah Dokumen PDF Revisi**.
3. Pastikan modal terbuka dengan dua tab pilihan: "Unggah Berkas PDF Revisi" (terpilih secara default) dan "Pilih Dokumen Lain yang Ada".
4. Uji alur default (unggah berkas PDF baru) untuk memastikan alur lama tetap berfungsi normal.
5. Klik tab "Pilih Dokumen Lain yang Ada":
   - Pastikan daftar dokumen eksisting lainnya muncul lengkap dengan badge versi dan tautan pratinjau PDF.
   - Pilih salah satu dokumen eksisting lalu klik "Ganti ke Dokumen Terpilih".
   - Verifikasi bahwa halaman ter-refresh dengan pesan sukses, usulan sekarang terikat ke dokumen baru tersebut, dan status usulan kembali menjadi `Draft`.
