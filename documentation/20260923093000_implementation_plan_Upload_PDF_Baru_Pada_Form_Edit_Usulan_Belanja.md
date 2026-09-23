# Implementation Plan (Revisi) - Penambahan Opsi Unggah PDF Baru di Form Edit Usulan Belanja

## Jawaban Pertanyaan Pengguna: Apakah Penerapan Ini Akan Merombak Alur Saat Ini?

> [!IMPORTANT]
> **Jawabannya: TIDAK SAMA SEKALI (100% Non-Breaking & Strictly Additive).**
> Penerapan ini **tidak merombak**, **tidak menggantikan**, dan **tidak mengganggu** satupun alur kerja (*workflow*) maupun arsitektur sistem yang saat ini sudah berjalan. Fitur ini murni **bersifat aditif (melengkapi)** celah pada formulir edit.

Berikut rincian perbandingan alur untuk memastikan integritas sistem:

| Alur / Komponen | Kondisi Saat Ini | Pasca Penerapan Fitur | Dampak |
| :--- | :--- | :--- | :--- |
| **1. Modal Upload Revisi di Tabel Workboard (`uploadVersion`)** | Digunakan saat usulan *Ditolak* / *Melebihi Pagu* untuk unggah revisi (mendukung opsi *Perbarui Bersama V2/V3* atau *Pisahkan Dokumen Mandiri*). | **Tetap berjalan persis 100% seperti sekarang.** Tidak ada kode pada alur ini yang diubah atau dihapus. | **Tidak Berdampak (Aman)** |
| **2. Edit Usulan Biasa Tanpa Ubah PDF** | Operator mengedit deskripsi, kode rekening, volume, atau harga satuan. Status kembali menjadi `Draft`. | **Tetap berjalan 100% sama.** Opsi default adalah *"Tetap Gunakan Saat Ini"*. Jika operator tidak menyentuh dokumen, tidak ada perubahan pada lampiran. | **Tidak Berdampak (Aman)** |
| **3. Ganti ke Dokumen Lain yang Sudah Ada** | Operator beralih ke dokumen eksisting yang sudah ada di pengajuan melalui dropdown. | **Tetap disediakan** melalui tab *"Pilih Dokumen Lain"*. | **Tidak Berdampak (Aman)** |
| **4. Fitur Baru: Unggah PDF Baru di Form Edit** | *Belum ada* (operator terpaksa keluar form jika ingin melampirkan berkas PDF baru yang belum pernah diunggah). | **Kini tersedia** melalui tab *"Unggah PDF Baru"*. Sistem membuat entitas dokumen baru dan menautkannya ke usulan yang sedang diedit. | **Fitur Baru (Melengkapi)** |
| **5. Skema Database & Migrasi** | Tabel `rba_detail_documents`, `rba_attachments`, dan pivot `rba_detail_attachments`. | **Tidak ada perubahan tabel sama sekali (0 Migration).** Menggunakan struktur database yang sudah ada. | **Nol Risiko DB** |

---

## Analisis Kebutuhan & Batasan Fitur

1. **Konteks Masalah**:
   - Di form edit (`resources/views/operator/details/edit.blade.php`), saat ini operator hanya memiliki tombol *"Ganti Dokumen"* yang isinya hanya memilih dokumen lain yang sudah pernah diunggah sebelumnya pada pengajuan tersebut.
   - Jika usulan yang sedang diedit ternyata memerlukan dokumen lampiran fisik PDF baru (misalnya nota dinas terpisah, KAK baru, atau penawaran baru), operator tidak memiliki tombol upload berkas di halaman tersebut.
2. **Tujuan Penerapan**:
   - Menambahkan opsi bagi operator untuk dapat langsung mengunggah berkas PDF baru dari form edit.
   - Dokumen baru tersebut disimpan sebagai entitas `RbaDetailDocument` baru pada pengajuan tersebut, sehingga di masa depan jika ada usulan lain yang ingin berbagi dokumen ini, usulan lain tersebut juga dapat memilihnya.
3. **Prinsip Keamanan Sistem**:
   - Status usulan pasca edit tetap diatur ulang (*reset*) menjadi `Draft` (`is_validated = false`, `is_submitted = false`) agar supervisor tetap memverifikasi perubahan data maupun dokumen.
   - Dokumen lama yang sebelumnya digunakan bersama oleh usulan lain **tidak akan rusak atau terhapus** (relasi usulan lain tetap utuh di tabel pivot `rba_detail_attachments`).

---

## User Review Required

> [!NOTE]
> **Tampilan Antarmuka yang Disediakan**:
> Pada kartu dokumen lampiran di formulir edit, operator diberikan kendali tab yang jelas dan ramah pengguna:
> 1. **[📄 Tetap Gunakan Saat Ini]** *(Default)*: Dokumen lampiran yang ada tetap dipertahankan tanpa perubahan.
> 2. **[🔄 Pilih Dokumen Lain]**: Tersedia jika ada dokumen lain pada pengajuan tersebut (alur ganti dokumen yang selama ini ada).
> 3. **[📤 Unggah PDF Baru]**: Opsi baru untuk memilih berkas PDF dari komputer (maks. 10MB) serta mengisi nama dokumen (opsional).

---

## Proposed Changes

### 1. Antarmuka Pengguna (Frontend View)

#### [MODIFY] [`resources/views/operator/details/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/details/edit.blade.php)

- Menambahkan atribut `enctype="multipart/form-data"` pada tag form.
- Mengganti blok statis kartu dokumen dengan komponen interaktif berbasis Alpine.js:
  - Menyediakan tab switcher:
    - `docAction = 'keep'`: Menampilkan dokumen saat ini dan tombol *"Lihat PDF"*.
    - `docAction = 'existing'`: Menampilkan daftar dokumen yang sudah ada di pengajuan.
    - `docAction = 'new'`: Menampilkan input file PDF (`name="attachment"`) dan input judul dokumen (`name="document_name"`).
  - Jika operator tidak mengubah tab (tetap di `keep`), form akan dikirim tanpa memproses berkas baru.

---

### 2. Pengendali Backend (Controller)

#### [MODIFY] [`app/Http/Controllers/Operator/DetailController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)

- Pada method `update(Request $request, RbaDetail $detail)`:
  - Validasi kondisional:
    - Jika `document_action === 'new'` (atau ada file `attachment` yang diunggah): validasi `attachment` (`file|mimes:pdf|max:10240`) dan `document_name` (`nullable|string|max:255`).
    - Jika `document_action === 'existing'` (atau ada `rba_detail_document_id`): validasi `exists:rba_detail_documents,id`.
  - Penanganan transaksi database:
    - Pembaruan field pokok usulan belanja (`account_code_id`, `description`, `volume`, `satuan`, `harga_satuan`, `nominal_request`) dan reset status ke `Draft`.
    - **Jika `document_action === 'new'`**:
      1. Simpan berkas PDF ke storage `public/attachments`.
      2. Buat entitas `RbaDetailDocument` baru.
      3. Buat entitas `RbaAttachment` versi 1 untuk dokumen baru tersebut.
      4. Tautkan attachment ke usulan melalui pivot: `$detail->attachments()->syncWithoutDetaching([$attachment->id])`.
    - **Jika `document_action === 'existing'`**:
      - Tautkan dokumen eksisting terpilih ke usulan (alur yang sudah berjalan saat ini).
    - **Jika `document_action === 'keep'`**:
      - Tidak ada perubahan dokumen apapun.

---

### 3. Pengujian Otomatis (Automated Feature Tests)

#### [MODIFY] [`tests/Feature/Operator/RbaDetailTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailTest.php)

- Menambahkan skenario pengujian spesifik untuk form edit:
  1. `test_operator_can_edit_detail_and_upload_new_pdf_document()`:
     - Memastikan berkas baru berhasil diunggah, dokumen baru terbentuk, dan usulan belanja beralih ke dokumen baru tersebut.
  2. `test_operator_can_edit_detail_and_switch_to_existing_document()`:
     - Memastikan usulan dapat beralih ke dokumen lain yang sudah ada tanpa error.
  3. `test_operator_can_edit_detail_keeping_current_document()`:
     - Memastikan pengeditan data pokok (volume/harga/uraian) tidak mengubah dokumen yang terpasang jika operator memilih tetap gunakan saat ini.

---

## Verification Plan

### Automated Tests
1. Uji khusus fitur RBA Detail:
   ```bash
   php artisan test tests/Feature/Operator/RbaDetailTest.php
   ```
2. Uji menyeluruh seluruh test suite aplikasi (254 test):
   ```bash
   php artisan test
   ```
   *Memastikan 100% test tetap hijau tanpa satu pun kegagalan/regresi.*

### Manual Verification
1. Masuk sebagai operator pengusul.
2. Edit rincian belanja, pilih tab *"Unggah PDF Baru"*, pilih file PDF baru dan simpan.
3. Pastikan rincian belanja berhasil diperbarui dan berkas PDF baru dapat dilihat.
4. Coba edit kembali usulan lain dan pastikan opsi dokumen eksisting maupun opsi tetap gunakan saat ini berfungsi normal.
