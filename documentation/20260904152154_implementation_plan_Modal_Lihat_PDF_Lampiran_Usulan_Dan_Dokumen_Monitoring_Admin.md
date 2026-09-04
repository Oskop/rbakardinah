# Rencana Implementasi: Modal Pratinjau PDF Lampiran Usulan Belanja & Dokumen KAK/RAK/RTP pada Panel Monitoring Administrator

Dokumen ini menjelaskan rencana teknis untuk menambahkan fitur interaktif berbasis **Modal Popup (Alpine.js)** pada kolom **PDF Lampiran Usulan** dan kolom **Dokumen Pokok (KAK / RAK / RTP)** di tabel *Monitoring Penginputan Unit dan Progres RBA* pada halaman Administrator (`/admin/headers/{header}`).

---

## 1. Latar Belakang & Analisis Kebutuhan

### Permasalahan Saat Ini:
1. Pada halaman detail RBA periode Administrator (`/admin/headers/{header}`):
   - Kolom **Dokumen KAK / RAK / RTP** hanya berupa label statis teks berwarna hijau/abu-abu. Administrator tidak dapat membuka atau memeriksa isi fisik dokumen yang diunggah operator tanpa navigasi manual yang rumit.
   - Kolom **PDF Lampiran Usulan** hanya menampilkan indikator teks statis (misalnya `2/2 PDF` atau `0/1 PDF`). Administrator tidak dapat langsung melihat dokumen PDF apa saja yang diunggah pada masing-masing rincian belanja usulan operator.
2. **Sistem Versioning (Riwayat Revisi)**:
   - Dokumen **KAK, RAK, dan RTP** memiliki sistem versioning (`RbaSubmissionDocumentVersion`) di mana operator dapat mengunggah versi baru saat terjadi perubahan/penyesuaian RBA.
   - Dokumen **PDF Lampiran Usulan Belanja** juga memiliki sistem versioning (`RbaAttachment`) di mana versi baru (V1, V2, dst) terbentuk saat operator memperbarui rincian usulan menyesuaikan pagu atau penolakan supervisor.
   - Administrator memerlukan visibilitas penuh terhadap seluruh riwayat versi dokumen PDF tersebut secara transparan.

### Sasaran & Solusi:
- Menggunakan pendekatan **Mode Modal (Popup)** berbasis **Alpine.js** yang telah terbukti mulus, responsif, dan tidak merusak layout tabel monitoring hierarki yang sudah ada.
- Administrator dapat mengklik:
  1. Badge **KAK / RAK / RTP** untuk membuka **Modal Dokumen Pokok** dengan tab navigasi antar jenis dokumen dan riwayat versinya.
  2. Badge/Tombol **PDF Lampiran Usulan** untuk membuka **Modal Lampiran Usulan Belanja** yang menampilkan rincian belanja operator beserta seluruh riwayat versi lampiran PDF untuk tiap item.
- Setiap file PDF dapat dibuka secara langsung di tab peramban baru (`target="_blank"`) melalui endpoint storage yang aman.

---

## 2. Rencana Perubahan Teknis (Proposed Changes)

### A. Controller: `app/Http/Controllers/RbaHeaderController.php`
- **File**: [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- **Modifikasi Eager-Loading**:
  - Ubah `'submissions.documents.latestVersion'` menjadi `'submissions.documents.versions.uploader'` agar seluruh riwayat versi KAK/RAK/RTP beserta nama pengunggah dimuat tanpa N+1 query.
  - Perkaya eager load `'submissions.details'` dengan `['attachments.uploader', 'accountCode', 'creator', 'validator']`.
- **Modifikasi Payload `$unitMonitoring`**:
  - Untuk setiap operator, susun payload JSON-ready:
    1. `documents_data`: Peta data untuk tipe `KAK`, `RAK`, dan `RTP` yang memuat flag ketersediaan dokumen (`has_doc`), URL versi terbaru, dan array seluruh versi (`version_number`, `file_url`, `uploaded_by`, `created_at`).
    2. `proposal_details_data`: Koleksi seluruh item usulan belanja milik operator tersebut, memuat `account_code`, `account_name`, `description`, `nominal_request`, volume, satuan, status validasi/penolakan, serta daftar `attachments` dengan riwayat versinya.

### B. View: `resources/views/admin/headers/show.blade.php`
- **File**: [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- **Pembaruan State Alpine.js**:
  - Tambahkan state dan helper method pada komponen monitoring:
    - `docModalOpen`: boolean status buka/tutup modal dokumen pokok.
    - `activeDocType`: string tab aktif (`KAK` / `RAK` / `RTP`).
    - `modalDocsData`: objek data dokumen KAK/RAK/RTP operator terpilih.
    - `showDocuments(opName, opNip, unitName, docsData, defaultType)`: fungsi pembuka modal dokumen pokok.
    - `proposalModalOpen`: boolean status buka/tutup modal lampiran usulan.
    - `proposalSearch`: string pencarian filter rincian belanja di dalam modal.
    - `modalProposalDetails`: array rincian usulan belanja operator terpilih.
    - `showProposalPdfs(opName, opNip, unitName, proposalDetails)`: fungsi pembuka modal lampiran usulan.
- **Interaktivitas Kolom Tabel**:
  - **Kolom KAK / RAK / RTP**: Setiap badge diubah menjadi elemen tombol interaktif dengan efek hover dan tooltip, yang ketika diklik langsung membuka modal dokumen pada tab yang bersangkutan.
  - **Kolom PDF Lampiran Usulan**: Mengubah teks statis menjadi tombol pill interaktif dengan icon dokumen/mata yang memicu modal lampiran usulan.
- **Komponen Modal Baru**:
  1. **Modal Dokumen KAK / RAK / RTP**:
     - Header: Identitas Dokumen, Unit Kerja, dan Operator.
     - Navigasi Tab: Tombol beralih antara KAK, RAK, dan RTP dengan indikator jumlah versi.
     - Konten Tab: Daftar riwayat versi (V1, V2, dst), waktu unggah (WIB), nama pengunggah, dan tombol *Buka / Unduh PDF*. State kosong jika belum diunggah.
  2. **Modal Daftar PDF Lampiran Usulan Belanja**:
     - Header: Identitas Unit Kerja, Operator, dan total usulan.
     - Kotak Pencarian: Filter cepat berdasarkan nomor rekening atau uraian belanja.
     - Daftar Item Usulan:
       - Kartu informasi rincian belanja (Badge Monospace Kode Rekening, Uraian, Nominal Usulan, Status Validasi).
       - Riwayat Versi Lampiran PDF: Versi terbaru & versi terdahulu dengan tombol *Buka / Unduh PDF*.
       - Indikator peringatan jika suatu rincian belum memiliki lampiran PDF.

---

## 3. Rencana Verifikasi & Pengujian (Verification Plan)

### A. Automated Tests
- Jalankan automated test suite `php artisan test` untuk memastikan:
  1. Test yang sudah ada pada `AdminDashboardTest` tetap lulus 100%.
  2. Tambahkan / sesuaikan test case untuk memverifikasi bahwa halaman show header admin merender trigger modal dan payload dokumen/lampiran usulan secara tepat.

### B. Manual / Visual Verification
1. Login sebagai Administrator, buka menu RBA Header dan pilih salah satu periode (misal: `/admin/headers/2`).
2. Gulir ke panel **Monitoring Penginputan Unit dan Progres RBA**, buka salah satu accordion Unit Kerja:
   - Klik badge **KAK** pada baris operator -> Pastikan modal Dokumen Pokok terbuka pada tab KAK dan menampilkan riwayat versi PDF.
   - Pindah ke tab **RAK** dan **RTP** -> Pastikan perpindahan tab mulus dan data versi berganti sesuai dokumen.
   - Klik tombol *Buka PDF* pada salah satu versi -> Pastikan file PDF terbuka di tab baru peramban.
3. Klik tombol pada kolom **PDF Lampiran Usulan** (`X/Y PDF`):
   - Pastikan modal Lampiran Usulan terbuka dan menampilkan seluruh rincian belanja milik operator tersebut.
   - Periksa apakah riwayat versi PDF (V1, V2, dst) tampil lengkap dengan metadata waktu unggah dan tombol buka file.
   - Coba ketik teks pada filter pencarian modal untuk memverifikasi pencarian instan rincian belanja.
   - Tekan tombol Tutup atau tombol Escape untuk memastikan modal tertutup tanpa memengaruhi tampilan tabel.
