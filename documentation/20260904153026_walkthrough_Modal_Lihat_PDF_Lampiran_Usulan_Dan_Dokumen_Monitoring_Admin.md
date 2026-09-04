# Walkthrough: Modal Pratinjau PDF Lampiran Usulan Belanja & Dokumen KAK/RAK/RTP pada Panel Monitoring Administrator

Fitur interaktif berbasis **Modal Popup (Alpine.js)** untuk melihat **PDF Lampiran Usulan Belanja** dan **Dokumen Pokok (KAK, RAK, RTP)** dengan dukungan sistem **Versioning (Riwayat Revisi)** pada panel *Monitoring Penginputan Unit dan Progres RBA* di halaman Administrator (`/admin/headers/{header}`) telah berhasil diimplementasikan dan diverifikasi secara menyeluruh.

---

## 1. Ringkasan Fitur & Perubahan

### A. Eager Loading & Payload Data Terstruktur (`RbaHeaderController.php`)
- **File**: `app/Http/Controllers/RbaHeaderController.php`
- Pada method `show()`, eager-loading dioptimalkan:
  - `submissions.documents.versions.uploader` memuat seluruh riwayat versi KAK, RAK, dan RTP beserta nama pengunggahnya.
  - `submissions.details.attachments.uploader` dan `submissions.details.accountCode` memuat seluruh versi lampiran PDF usulan belanja dan identitas kode rekening.
- Di dalam pemetaan `$operatorMetrics`:
  - `documents_data`: Memetakan data dokumen KAK, RAK, dan RTP yang berisi status ketersediaan dokumen (`has_doc`), jumlah versi, dan seluruh riwayat versi terurut descending (nomor versi, URL file storage, pengunggah, dan waktu unggah berformat WIB).
  - `proposal_details_data`: Memetakan seluruh rincian belanja milik operator tersebut lengkap dengan kode rekening, uraian belanja, volume, satuan, nominal usulan, status validasi/penolakan, serta daftar seluruh riwayat versi lampiran PDF (`attachments`).

### B. Interaktivitas Kolom Tabel Monitoring (`show.blade.php`)
- **File**: `resources/views/admin/headers/show.blade.php`
- **Kolom Dokumen Pokok (KAK, RAK, RTP)**:
  - Badge KAK, RAK, dan RTP kini interaktif (dapat diklik) dengan efek hover dan tooltip informatif yang menampilkan jumlah versi.
  - Mengklik salah satu badge akan membuka modal dokumen pokok dan secara otomatis mengaktifkan tab yang sesuai (KAK / RAK / RTP).
- **Kolom PDF Lampiran Usulan**:
  - Indikator `X/Y PDF` kini berupa tombol pill interaktif dengan icon dokumen/mata yang responsif.
  - Mengklik tombol ini akan membuka modal daftar usulan belanja dan riwayat versi lampiran PDF-nya.

### C. Komponen Modal Dokumen Pokok (KAK / RAK / RTP)
- Menggunakan mode modal Alpine.js yang mulus dengan backdrop blur.
- Header elegan bergradien gelap-indigo memuat nama Unit Kerja, Nama Operator, dan NIP.
- Tab navigasi KAK, RAK, dan RTP dengan badge counter jumlah versi.
- Di setiap tab:
  - Menampilkan timeline riwayat revisi (Versi 1, Versi 2 - Terbaru, dst).
  - Label versi terbaru, waktu unggah (WIB), dan nama pengunggah.
  - Tombol **🌐 Buka PDF** yang langsung membuka dokumen PDF di tab baru peramban (`target="_blank"`).
  - Tampilan state kosong yang informatif jika tipe dokumen belum diunggah.

### D. Komponen Modal PDF Lampiran Usulan Belanja
- Header memuat identitas Unit Kerja dan Operator.
- Fitur **Pencarian Cepat (Filter Instan)** di dalam modal untuk mencari berdasarkan kode rekening atau uraian belanja.
- Daftar kartu rincian belanja:
  - Badge monospaced kode rekening + nama rekening + status usulan (Divalidasi / Pending Review / Draft / Ditolak).
  - Uraian belanja, volume, satuan, dan total nominal usulan.
  - Bagian riwayat versi PDF lampiran usulan:
    - Menampilkan setiap versi lampiran PDF yang diunggah operator (Versi 1, Versi 2 - Terbaru, dst) beserta waktu dan nama pengunggah.
    - Tombol **🌐 Buka PDF** (`target="_blank"`).
    - Peringatan jika belum ada PDF yang dilampirkan.

---

## 2. File yang Dimodifikasi

1. [app/Http/Controllers/RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
2. [resources/views/admin/headers/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
3. [tests/Feature/Admin/AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)

---

## 3. Hasil Pengujian & Verifikasi

- **Vite Asset Compilation**: `bun run build` sukses mengompilasi CSS dan JS tanpa kendala (`public/build/assets/app-*.css` & `app-*.js`).
- **Feature Tests**: `Tests\Feature\Admin\AdminDashboardTest` lulus 100% (5 passed, 58 assertions).
- **Automated Tests Suite**: Seluruh 139 test cases pada sistem berhasil lulus tanpa kegagalan (139 passed, 657 assertions, 100% PASS).
