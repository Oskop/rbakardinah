# Walkthrough: Fitur Unggah Berkas PDF Baru Pada Form Edit Usulan Belanja Operator

## Ringkasan Fitur
Telah selesai diimplementasikan fitur **Unggah Berkas PDF Baru** pada halaman edit rincian belanja operator (`resources/views/operator/details/edit.blade.php`) dan pengendali `app/Http/Controllers/Operator/DetailController.php`. Fitur ini melengkapi opsi sebelumnya yang hanya memungkinkan pemilihan dokumen eksisting yang sudah ada pada pengajuan, sehingga kini operator dapat langsung mengunggah berkas PDF baru (misalnya nota dinas, KAK, atau penawaran harga mandiri baru) saat mengedit usulan belanja tanpa merombak alur yang sudah ada.

---

## 1. Rincian Perubahan yang Diterapkan

### A. Antarmuka Pengguna (`resources/views/operator/details/edit.blade.php`)
1. **Atribut Enctype Multipart**:
   - Menambahkan `enctype="multipart/form-data"` pada tag `<form action="{{ route('operator.details.update', $detail) }}" method="POST">`.
2. **Segmented Tab Switcher Interaktif (Alpine.js)**:
   - State `docAction` dengan nilai:
     - **`keep` (📄 Tetap Gunakan Saat Ini)**: Menampilkan badge dokumen yang sedang terpasang, nomor versi, nama berkas, dan tautan *"Lihat PDF"*. Tidak ada pengubahan lampiran.
     - **`existing` (🔄 Pilih Dokumen Lain)**: Ditampilkan jika terdapat dokumen lain di pengajuan tersebut, memungkinkan operator memilih dokumen lain yang sudah ada tanpa unggah ulang.
     - **`new` (📤 Unggah PDF Baru)**: Menyediakan area upload berkas PDF (maks. 10MB) serta input nama/judul dokumen baru (opsional).
   - Tampilan interaktif file chooser yang langsung memunculkan nama berkas dan ukuran file saat dipilih oleh pengguna.

### B. Pengendali Backend (`app/Http/Controllers/Operator/DetailController.php`)
1. **Validasi Kondisional**:
   - Menerima `document_action` (`keep`, `existing`, `new`) beserta *auto-detection fallback* bila aksi tidak dikirimkan secara eksplisit.
   - Jika `new`: memvalidasi `attachment` (`required|file|mimes:pdf|max:10240`) dan `document_name` (`nullable|string|max:255`).
   - Jika `existing`: memvalidasi `rba_detail_document_id` (`required|exists:rba_detail_documents,id`).
2. **Transaksi Database Bersih**:
   - Mengupdate data usulan belanja dan menghitung ulang `nominal_request`.
   - Mengatur ulang status usulan ke `Draft` (`is_validated = false`, `is_submitted = false`).
   - **Jika `new`**:
     - Menyimpan berkas PDF ke disk `public/attachments`.
     - Membuat record `RbaDetailDocument` baru untuk pengajuan terkait.
     - Membuat `RbaAttachment` versi 1 untuk dokumen baru tersebut.
     - Menautkan attachment ke usulan belanja melalui relasi pivot: `$detail->attachments()->sync([$attachment->id])`.
   - **Jika `existing`**:
     - Menautkan dokumen eksisting yang dipilih ke usulan belanja: `$detail->attachments()->sync([$doc->latestVersion->id])`.

---

## 2. Jaminan Keamanan Alur (Non-Breaking)

1. **Alur Modal Upload Revisi di Workboard (`uploadVersion`)**:
   - Tetap utuh 100% dan tidak disentuh kodenya.
2. **Alur Edit Biasa Tanpa Ganti PDF**:
   - Pilihan *default* adalah `keep`. Jika operator tidak memilih tab lain, dokumen tidak berubah sama sekali.
3. **Nol Perubahan Skema Database**:
   - Sepenuhnya memanfaatkan tabel `rba_detail_documents`, `rba_attachments`, dan `rba_detail_attachments` yang sudah ada.

---

## 3. Hasil Pengujian Otomatis

### A. Pengujian Fitur RBA Detail (`tests/Feature/Operator/RbaDetailTest.php`)
```bash
php artisan test tests/Feature/Operator/RbaDetailTest.php
```
**Hasil**:
```text
PASS  Tests\Feature\Operator\RbaDetailTest
✓ operator can view their submissions
✓ operator can create rba detail with pdf
✓ operator submission view displays previous period pagu in awal column
✓ operator can upload new version of pdf
✓ operator can submit item to supervisor
✓ operator can soft delete rba detail
✓ operator must upload new pdf when nominal exceeds pagu
✓ supervisor cannot validate item exceeding pagu without revision
✓ operator cannot add detail if background is empty
✓ operator can save background
✓ operator can upload kak rak rtp versioned documents when locked
✓ operator cannot edit or upload revision on validated detail
✓ uploading revision pdf on rejected detail resets status to draft
✓ operator can create detail with named document
✓ operator can create subsequent detail using existing shared pdf
✓ shared pdf revising updates selected items and leaves unselected
✓ detaching item to new document creates standalone history
✓ deleting one item preserves shared attachment for other items
✓ operator can edit detail and upload new pdf document
✓ operator can edit detail and switch to existing document
✓ operator can edit detail keeping current document

Tests: 21 passed (100 assertions)
```

### B. Full Test Suite Regression Check
```bash
php artisan test
```
**Hasil**:
```text
Tests: 257 passed (1284 assertions)
Duration: 46.98s
Status: 100% PASS (Zero Failures)
```
