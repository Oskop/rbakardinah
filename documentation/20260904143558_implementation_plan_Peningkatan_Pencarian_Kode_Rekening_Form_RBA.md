# Rencana Implementasi: Peningkatan Fitur Pencarian Kode Rekening pada Form Rincian Belanja Operator

Dokumen ini berisi analisis komparatif, rekomendasi arsitektur, dan rencana implementasi untuk meningkatkan pengalaman pengguna (UX) operator saat memilih **Kode Rekening** pada form Tambah Rincian Belanja (`operator.details.create`) dan Edit Rincian Belanja (`operator.details.edit`).

---

## 1. Analisis Permasalahan Saat Ini

### Masalah yang Dihadapi Pengguna:
1. **Jumlah Data Sangat Banyak**: Bagan Akun Standar (BAS) / Nomor Rekening belanja di RSUD Kardinah mencakup ratusan hingga ribuan item (Belanja Barang & Jasa, Modal Alat Medis, ATK, Obat-obatan, Jasa Kebersihan, IT, dsb).
2. **Elemen `<select>` HTML Standar**:
   - Tidak memiliki kotak pencarian (*search box*).
   - Operator terpaksa menggulir (*scroll*) daftar yang sangat panjang secara manual.
   - Pencarian bawaan peramban hanya melompat ke 1 huruf pertama yang ditekan pada keyboard.
   - Tidak dapat mencari berdasarkan potongan kode akun (misal: `5.1.02...`) ATAU potongan kata pada nama belanja (misal: "kertas", "spuit", "laptop").
   - Opsi nomor rekening tidak dikelompokkan secara visual berdasarkan **Kelompok Belanja**, sehingga menyulitkan navigasi hierarki belanja.

---

## 2. Analisa Komparatif Metode / Pustaka Pilihan

Berikut perbandingan mendalam antara **Select2**, **Tom Select**, **Choices.js**, dan pendekatan **Modal Picker**:

| Kriteria / Fitur | Select2 (Metode Tradisional) | Tom Select (Rekomendasi Terbaik) | Choices.js | Modal Table Picker (DataTables) |
| :--- | :--- | :--- | :--- | :--- |
| **Arsitektur & Dependency** | Membutuhkan **jQuery** (wajib) | **Vanilla JavaScript Murni** (Zero dependency) | **Vanilla JavaScript Murni** | Membutuhkan jQuery & DataTables |
| **Ukuran & Kecepatan** | Relatif berat (~87KB jQuery + 70KB Select2) | **Sangat ringan (~16KB gzipped)**, instan render | Ringan (~20KB) | Sedang |
| **Pencarian Cerdas (Dual-Search)** | Baik, namun filtering fuzzy terbatas | **Sangat Cerdas (Fuzzy Search & Multi-Field)**: Mencari potongan kode akun + nama barang + nama kelompok belanja sekaligus | Standar | Menggunakan search box DataTables |
| **Pengelompokan (`<optgroup>`)** | Mendukung optgroup standar | **Native optgroup dengan pencarian pada nama grup & styling header modern** | Terbatas & kaku pada optgroup kompleks | Menggunakan filter dropdown terpisah |
| **Integrasi & Styling Tailwind CSS** | Sulit diharmonisasi dengan Tailwind (bawaan era Bootstrap) | **Sangat mudah di-custom dengan Tailwind CSS** (border, focus ring, rounded, shadow) | Cukup mudah | Sesuai tabel DataTables |
| **Custom Template Rendering** | Ada (`templateResult`), namun kaku & lambat di DOM besar | **Sangat fleksibel**: Menampilkan Badge Kode Monospace + Nama Rekening + Keterangan Kelompok | Kurang fleksibel | Menampilkan kolom-kolom tabel |
| **Kemudahan Alur Operator (UX)** | Cepat (inline select) | **Paling Cepat & Ergonomis (Inline Combobox dengan keyboard navigation lengkap)** | Cepat | Lambat (harus klik tombol -> buka modal -> cari -> klik pilih -> tutup modal) |

### Mengapa Tom Select Lebih Canggih dan Sangat Diandalkan?
1. **Modern & Zero-jQuery**: Tom Select adalah rewrite modern dari *Selectize.js* yang dirancang untuk arsitektur frontend masa kini. SIPAKAR menggunakan Tailwind CSS & Alpine.js; Tom Select bekerja selaras tanpa membebani halaman form dengan dependensi jQuery.
2. **Multi-Field Search (Cari Bebas)**:
   - Operator A yang hafal kode bisa langsung mengetik: `5.1.02`
   - Operator B yang hanya tahu nama barang bisa mengetik: `kertas hvs`
   - Tom Select akan langsung menyaring dan menampilkan hasil yang relevan secara *real-time* dengan penandaan (*highlighting*).
3. **Penyajian Visual yang Sangat Jelas (Badge Monospace + Nama)**:
   - Kode rekening ditampilkan dalam bentuk badge ringkas monospace (misal: `[ 5.1.02.01.01.0024 ]`) dengan latar belakang lembut.
   - Nama rekening ditampilkan di sampingnya dengan teks kontras dan mudah dibaca.
   - Pengelompokan berdasarkan *Kelompok Belanja* menjadi judul pemisah yang rapi.
4. **Fitur Pelengkap Terintegrasi**:
   - Tombol hapus cepat (*clear button*).
   - Pengaturan dropdown posisi otomatis (*dropdown direction auto-flip* agar tidak keluar layar).
   - Aksesibilitas keyboard penuh (panah atas/bawah, Enter untuk memilih, Escape untuk menutup).

---

## 3. Rencana Perubahan Teknis (Proposed Changes)

### A. Controller: `app/Http/Controllers/Operator/DetailController.php`
- Eager load relasi `kelompokBelanja` pada query `AccountCode` agar tidak terjadi N+1 query:
  ```php
  $accountCodes = AccountCode::with('kelompokBelanja')
      ->where('is_active', true)
      ->whereNotIn('id', $lockedAccountIds)
      ->orderBy('code')
      ->get();
  ```
- Lakukan hal yang sama pada method `edit()` agar rekening yang saat ini terpilih tetap ter-load dengan relasi kelompok belanjanya.

### B. Form Tambah: `resources/views/operator/details/create.blade.php`
- Ubah dropdown rekening menjadi terstruktur dengan `<optgroup label="...">` berdasarkan Kelompok Belanja.
- Tambahkan data attributes (`data-code`, `data-name`, `data-group`) pada tiap `<option>` untuk mempermudah indexing Tom Select.
- Pasang aset Tom Select (CSS & JS) via CDN resmi yang stabil di `@push('styles')` dan `@push('scripts')`.
- Terapkan konfigurasi Tom Select:
  - Plugin: `dropdown_input`, `clear_button`.
  - Custom render item & option dengan styling Tailwind (badge kode monospace + nama rekening).
  - Search fields: `['text', 'code', 'name', 'group']`.

### C. Form Edit: `resources/views/operator/details/edit.blade.php`
- Terapkan struktur `<optgroup>` dan inisialisasi Tom Select yang identik dengan form create.
- Pastikan rekening yang sedang aktif tetap terpilih (*pre-selected*) dengan benar saat halaman dibuka.

---

## 4. Rencana Verifikasi & Pengujian (Verification Plan)

### A. Automated Tests:
- Jalankan test suite `php artisan test` untuk memastikan tidak ada alur bisnis operator atau validasi RBA yang terganggu.
- Pastikan endpoint `create`, `edit`, `store`, dan `update` rincian belanja tetap berfungsi 100%.

### B. Manual / Visual Verification:
1. Akses halaman `/operator/submissions/{id}/details/create`:
   - Verifikasi dropdown rekening telah berubah menjadi searchable input yang modern dan elegan.
   - Coba ketik potongan kode (misal: angka kode) -> pastikan opsi tersaring dengan tepat.
   - Coba ketik nama barang/belanja -> pastikan opsi tersaring dengan tepat.
   - Verifikasi grouping per Kelompok Belanja tampil rapi.
   - Verifikasi pemilihan rekening berhasil mengisi nilai form dan penghitungan otomatis harga total (`volume * harga_satuan`) tetap berfungsi tanpa konflik JavaScript.
2. Akses halaman edit rincian belanja:
   - Verifikasi rekening yang sebelumnya dipilih langsung terisi dengan benar pada Tom Select.
   - Lakukan pengubahan rekening dan submit form, pastikan update berhasil tersimpan ke database.
