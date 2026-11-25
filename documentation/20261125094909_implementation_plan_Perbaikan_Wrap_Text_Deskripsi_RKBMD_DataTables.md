# Implementation Plan: Perbaikan Menyeluruh Wrap Text Deskripsi Permohonan RKBMD Pasca-Inisialisasi DataTables (Semua Level User)

Dokumen ini menjabarkan hasil penyelidikan mendalam atas kegagalan *wrap text* saat DataTables selesai memuat (*"reload DataTable"*), mengapa hal tersebut berdampak pada semua level pengguna, serta rencana perbaikan tuntas yang tahan terhadap timpaan style/script DataTables dan build CSS production.

---

## 1. Hasil Investigasi Akar Masalah (*Root Cause Analysis*)

Berdasarkan pemeriksaan langsung pada kode tampilan ([index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)), file CSS terkompilasi ([public/build/assets/app-19jAdnoN.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-19jAdnoN.css)), serta perilaku library **DataTables v2.0.3 (`dataTables.tailwindcss.js`)**, ditemukan **4 faktor utama** yang menyebabkan teks terlihat *wrap* sesaat lalu berubah memanjang tanpa *wrap* setelah DataTables selesai memuat:

### A. `dataTables.tailwindcss.js` Menyuntikkan `whitespace-nowrap` ke `<table>` Saat Runtime
Pada baris 687 [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php#L687), halaman memuat integrasi Tailwind dari CDN DataTables 2.0.3 (`dataTables.tailwindcss.js`). Secara default, skrip tersebut mendaftarkan konfigurasi global `DataTable.ext.classes.table` berisi:
```javascript
'dataTable min-w-full text-sm align-middle whitespace-nowrap'
```
Akibatnya terjadi urutan kejadian berikut di peramban:
1. **Saat render HTML awal (sebelum JS berjalan)**: Elemen `<table>` belum memiliki class `whitespace-nowrap`, sehingga teks deskripsi masih membungkus (*wrap*) mengikuti lebar tabel.
2. **Saat `$(document).ready()` mengeksekusi `.DataTable(...)` (*"reload DataTable"*)**: DataTables otomatis menambahkan class `whitespace-nowrap` langsung ke tag `<table>`. Karena properti CSS `white-space` bersifat **inherited** (diwariskan ke elemen anak), seluruh `<td>` dan `<div>` di dalamnya ikut terpengaruh menjadi `white-space: nowrap`.

### B. Aset Vite Production (`public/build/assets/app-19jAdnoN.css`) Belum Di-build Ulang Sejak 24 Oktober 2026
File `public/build` ikut dikelola di repositori Git, tetapi terakhir dikompilasi pada **24 Oktober 2026** (commit `b5ba6fb1`). Hasil inspeksi isi [public/build/assets/app-19jAdnoN.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-19jAdnoN.css) membuktikan bahwa:
- Utility Tailwind baru dari revisi sebelumnya — yaitu `whitespace-normal`, `break-words`, `min-w-[220px]`, dan `md:max-w-sm` — **sama sekali belum ada** di file CSS production.
- Sebaliknya, class **`whitespace-nowrap`** (yang disuntikkan oleh `dataTables.tailwindcss.js`) **sudah ada** di `app-19jAdnoN.css`.
- Akibatnya: saat DataTables menyuntikkan `whitespace-nowrap`, penangkal `whitespace-normal break-words` pada elemen deskripsi **tidak aktif di browser**. Teks seketika dipaksa menjadi satu baris memanjang.

### C. `autoWidth: true` DataTables 2.0 + Keterbatasan `max-width` Langsung di Elemen `<td>`
1. Under CSS `table-layout: auto`, properti `max-width` yang diletakkan langsung pada elemen `<td>` atau `<th>` **tidak mengikat secara konsisten**.
2. Selain itu, opsi default `autoWidth: true` pada DataTables 2.0 membuat library menghitung *intrinsic width* teks 1 baris saat inisialisasi (dan saat `adjustRkbmdTables()` dipanggil ketika berpindah tab Alpine), lalu menyuntikkan `<colgroup><col style="width: ...px"></colgroup>` selebar ribuan piksel ke dalam tabel.
3. Untuk teks sambung tanpa spasi (misalnya URL panjang atau rangkaian karakter tanpa jeda), `overflow-wrap: break-word` tidak membatasi *min-content width* tabel; diperlukan `overflow-wrap: anywhere` di dalam elemen blok pembungkus (`<div>`) berbatas lebar eksplisit.

### D. Tabel Level Administrator & Supervisor Belum Terjangkau Perbaikan Wrap
Halaman [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) melayani **seluruh 3 role user** (Operator, Administrator, dan Supervisor):
- Pada tabel **Administrator** (`#table-admin-rkbmd`, baris 522–525) dan **Supervisor** (`#table-supervisor-rkbmd`, baris 625–628), judul masih menggunakan `line-clamp-1` tanpa pembungkus lebar dan belum menerapkan `autoWidth: false` maupun perlindungan dari `whitespace-nowrap` bawaan DataTables.
- Kolom `Asal Unit & Sub-Unit` (Admin) dan `Keterkaitan / Arah Berkas` (Supervisor) juga tidak memakai `whitespace-nowrap`, sehingga ikut terdampak warisan `whitespace-nowrap` dari tag `<table>` setelah DataTables aktif.

---

## 2. Desain Solusi Menyeluruh (4 Lapis Proteksi)

Perbaikan akan diterapkan pada [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) dan hasil kompilasi aset Vite (`public/build/`):

### Lapisan 1: Hapus `whitespace-nowrap` Global dari DataTables & Nonaktifkan `autoWidth`
Pada blok `@push('scripts')`, tepat sebelum inisialisasi kelima tabel:
```javascript
if (window.DataTable && DataTable.ext && DataTable.ext.classes) {
    DataTable.ext.classes.table = 'dataTable min-w-full text-sm align-middle';
}
if ($.fn.dataTable && $.fn.dataTable.ext && $.fn.dataTable.ext.classes) {
    $.fn.dataTable.ext.classes.table = 'dataTable min-w-full text-sm align-middle';
}
```
- Menambahkan **`autoWidth: false`** pada konfigurasi `.DataTable({...})` di **semua 5 tabel** (`#table-my-rkbmd`, `#table-incoming-rkbmd`, `#table-forwarded-rkbmd`, `#table-admin-rkbmd`, dan `#table-supervisor-rkbmd`) agar DataTables tidak menyisipkan lebar kolom raksasa ke `<colgroup>` baik saat inisialisasi maupun saat `columns.adjust()`.
- Kolom pendek seperti No. Tiket, Tujuan, Jumlah Item, Status, dan Aksi **tetap mempertahankan class `whitespace-nowrap` masing-masing** langsung pada `<td>`-nya, sehingga tampilan kolom lain sama sekali tidak terganggu atau berantakan.

### Lapisan 2: Aturan CSS Eksklusif Pasca-`dataTables.tailwindcss.css` & Pembungkus Blok `<div>`
Pada blok `@push('styles')` (yang dimuat setelah `app.css` maupun `dataTables.tailwindcss.css` di [layouts/app.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/app.blade.php#L15-L18)):
```css
/* Memastikan kolom Perihal Permohonan & elemen pembungkus selalu wrap dan terkunci lebar maksimalnya */
table.dataTable td.rkbmd-col-perihal,
table.dataTable th.rkbmd-col-perihal {
    white-space: normal !important;
    min-width: 220px;
    max-width: 24rem;
    vertical-align: top;
}

.rkbmd-perihal-wrap {
    display: block;
    width: 100%;
    min-width: 220px;
    max-width: 24rem; /* 384px - membatasi min-content & max-content width di semua browser */
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.rkbmd-notes-wrap {
    white-space: normal !important;
    overflow-wrap: anywhere;
    word-break: break-word;
}
```
Mengapa menggunakan wrapper `<div class="rkbmd-perihal-wrap">` di dalam `<td>`?
- Karena elemen `<div>` (block element) selalu mematuhi `max-width: 24rem` (384px) di dalam sel tabel HTML, sedangkan `overflow-wrap: anywhere` menjamin baik kalimat panjang ratusan kata maupun kata/URL panjang tanpa spasi **pasti melipat ke bawah** dan tidak mendorong lebar tabel ke kanan.
- Karena aturan CSS berada di `<style>` pada `@push('styles')` tepat setelah `dataTables.tailwindcss.css`, aturan ini kebal terhadap cascade style DataTables maupun perbedaan cache build.

### Lapisan 3: Penerapan Konsisten pada Seluruh 5 Tabel (Semua Level User)
1. **Operator — Tab 1 (`#table-my-rkbmd`), Tab 2 (`#table-incoming-rkbmd`), Tab 3 (`#table-forwarded-rkbmd`)**:
   - Menambahkan class `rkbmd-col-perihal` pada `<th>` dan `<td>` kolom *Perihal Permohonan*.
   - Membungkus konten (judul, nama pemohon bila ada, dan deskripsi `$notes`) ke dalam `<div class="rkbmd-perihal-wrap">`.
   - Menambahkan class `.rkbmd-notes-wrap` pada blok deskripsi `$notes` (dengan tampilan bersih: `text-xs text-slate-500 mt-1 break-words whitespace-normal leading-relaxed`).
   - *(Opsional — untuk kenyamanan visual apabila deskripsi bernilai ribuan karakter)*: Dapat ditambahkan tombol kecil *"Selengkapnya / Ringkas"* menggunakan Alpine.js (`x-data="{ expanded: false }"`) untuk deskripsi yang sangat panjang (misal > 180 karakter) agar seluruh teks bisa tetap dibaca di tabel tanpa membuat satu baris setinggi satu halaman penuh. Default-nya seluruh teks tetap *wrap* rapi ke bawah sesuai lebar 384px.
2. **Administrator (`#table-admin-rkbmd`) & Supervisor (`#table-supervisor-rkbmd`)**:
   - Menerapkan `rkbmd-col-perihal` dan `<div class="rkbmd-perihal-wrap">` pada kolom *Perihal Permohonan*, menggantikan `line-clamp-1` pada judul (`$sub->title`) agar judul panjang terbungkus rapi.
   - Menampilkan deskripsi permohonan (`@if($sub->notes)`) dengan `.rkbmd-notes-wrap` di bawah `Pemohon` (konsisten dengan tampilan Operator) agar informasi deskripsi panjang juga terbaca rapi oleh Administrator & Supervisor tanpa memperlebar scroll horizontal. *(Atau tetap disembunyikan jika Admin/Supervisor hanya membutuhkan judul + pemohon).*
   - Memberikan batas wrap yang aman (`white-space: normal` / pembungkus `min-w-[160px] max-w-[220px]`) pada kolom *Asal Unit & Sub-Unit* (Admin) dan *Keterkaitan / Arah Berkas* (Supervisor) agar nama unit/sub-unit panjang tidak ikut melar setelah DataTables dimuat.

### Lapisan 4: Build Ulang Asset Frontend (`npm run build`)
Menjalankan perintah `npm run build` setelah perubahan view selesai, sehingga file `public/build/assets/app-*.css` dan `public/build/manifest.json` ikut terbarui dan menyimpan seluruh utility class Tailwind terbaru (`whitespace-normal`, `break-words`, `leading-relaxed`, `min-w-[220px]`, `md:max-w-sm`) secara permanen.

---

## 3. Berkas yang Akan Dimodifikasi / Dibuat

| Berkas | Perubahan |
| :--- | :--- |
| [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) | Override `DataTable.ext.classes.table`, set `autoWidth: false`, tambahkan CSS proteksi wrap pasca-DataTables di `@push('styles')`, dan bungkus kolom *Perihal Permohonan* pada semua 5 tabel (Operator 3 tab, Admin, Supervisor) dengan `.rkbmd-perihal-wrap`. |
| `public/build/*` | Kompilasi ulang Vite (`npm run build`) agar seluruh class Tailwind ter-generate ke CSS production. |
| [tests/Feature/Operator/RkbmdSubUnitTargetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdSubUnitTargetTest.php) & [tests/Feature/General/RkbmdReadOnlyAccessTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/RkbmdReadOnlyAccessTest.php) | Tambahkan asersi untuk memastikan ke-5 tabel (semua level user: Operator, Admin, Supervisor) merender wrapper `.rkbmd-perihal-wrap`, class wrap, `autoWidth: false`, serta override `DataTable.ext.classes.table` dengan benar. |
| `documentation/..._walkthrough_*.md` | Dokumentasi walkthrough setelah implementasi dan verifikasi selesai. |

---

## 4. Rencana Verifikasi

1. **Kompilasi Asset**: Jalankan `npm run build` dan verifikasi file CSS hasil build di `public/build/assets/` telah memuat class Tailwind (`whitespace-normal`, `break-words`).
2. **Pengujian Otomatis (PHPUnit)**:
   - Jalankan `php artisan test --filter=Rkbmd` untuk memverifikasi seluruh skenario Operator (3 tab), Administrator, Supervisor, serta test deskripsi panjang / string tanpa spasi.
3. **Verifikasi Perilaku DOM & DataTables**:
   - Pastikan saat DataTables v2.0.3 (`dataTables.tailwindcss.js`) menginisialisasi tabel dan saat `adjustRkbmdTables()` dipanggil ketika berganti tab:
     - Elemen `<table>` tidak mendapatkan class `whitespace-nowrap`.
     - Sel kolom *Perihal Permohonan* dan elemen deskripsi `.rkbmd-notes-wrap` tetap berstatus `white-space: normal`, `overflow-wrap: anywhere`, dengan lebar kontainer terkunci `<= 384px` (`24rem`), tanpa menambah panjang *horizontal scroll* sedikit pun.

