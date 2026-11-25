# Walkthrough: Perbaikan Wrap Text Deskripsi Permohonan RKBMD Pasca-Inisialisasi DataTables (Semua Level User)

Dokumen ini merangkum investigasi teknis, implementasi perbaikan berlapis, serta hasil verifikasi terkait masalah teks deskripsi permohonan RKBMD yang kembali menjadi satu baris (*no-wrap*) sesaat setelah DataTables selesai memuat di halaman daftar RKBMD.

---

## 1. Akar Masalah yang Ditemukan

Dari inspeksi langsung terhadap kode tampilan, hasil kompilasi aset Vite, serta perilaku DataTables v2.0.3 (`dataTables.tailwindcss.js`), teridentifikasi 4 penyebab utama:

1. **Suntikan `whitespace-nowrap` oleh `dataTables.tailwindcss.js`**:
   Skrip `dataTables.tailwindcss.js` v2.0.3 secara bawaan mengisi `DataTable.ext.classes.table` dengan `'dataTable min-w-full text-sm align-middle whitespace-nowrap'`. Saat `$(document).ready()` menjalankan `.DataTable({...})`, class `whitespace-nowrap` langsung ditambahkan ke elemen `<table>`, dan sifat CSS *inherited* membuat teks di bawahnya dipaksa tetap satu baris.
2. **Asset Vite Production Belum Di-build Ulang Sejak 24 Oktober 2026**:
   File CSS terkompilasi sebelumnya ([public/build/assets/app-19jAdnoN.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-19jAdnoN.css)) belum memuat class `whitespace-normal`, `break-words`, `min-w-[220px]`, maupun `md:max-w-sm`, sementara `whitespace-nowrap` sudah ada di dalam file tersebut. Akibatnya, class penangkal *wrap* tidak bekerja di browser ketika DataTables aktif.
3. **`autoWidth: true` DataTables & Aturan `max-width` pada `<td>`**:
   Dalam mode `table-layout: auto` + `autoWidth: true`, `max-width` langsung pada `<td>` mudah terabaikan saat DataTables menyisipkan `<colgroup>` dan saat `columns.adjust()` dipanggil ketika berpindah tab, serta dibutuhkan `overflow-wrap: anywhere` untuk memecah string panjang tanpa spasi (misal URL panjang).
4. **Cakupan Level Administrator & Supervisor**:
   Tabel Administrator (`#table-admin-rkbmd`) dan Supervisor (`#table-supervisor-rkbmd`) di halaman yang sama belum dilengkapi pembatas lebar wrapper, masih memakai `line-clamp-1` pada judul, serta belum menampilkan deskripsi (`notes`) secara terbungkus (*wrapped*).

---

## 2. Perubahan yang Diimplementasikan

### A. Pembaruan View & Override DataTables ([index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php))

1. **Proteksi CSS Pasca-`dataTables.tailwindcss.css` (`@push('styles')`)**:
   - Menambahkan rule setelah `dataTables.tailwindcss.css`:
     - `table.dataTable { white-space: normal; }` agar tag `<table>` tidak mewariskan `nowrap`.
     - `table.dataTable td.rkbmd-col-perihal` dan `.rkbmd-perihal-wrap` (`display: block; width: 100%; min-width: 220px; max-width: 24rem; white-space: normal !important; overflow-wrap: anywhere; word-break: break-word;`).
     - `.rkbmd-notes-wrap` (`white-space: normal !important; overflow-wrap: anywhere; word-break: break-word;`).
     - `table.dataTable td.rkbmd-col-meta` dan `.rkbmd-meta-wrap` (`min-width: 160px; max-width: 220px; white-space: normal !important; overflow-wrap: anywhere; word-break: break-word;`) untuk kolom unit/asal pada Administrator dan Supervisor.
2. **Override Kelas Global & Konfigurasi DataTables (`@push('scripts')`)**:
   - Menimpa `DataTable.ext.classes.table` dan `$.fn.dataTable.ext.classes.table` sebelum inisialisasi tabel menjadi `'dataTable min-w-full text-sm align-middle'` (tanpa `whitespace-nowrap`).
   - Memusatkan inisialisasi kelima tabel ke helper `initRkbmdDataTable(selector, nonOrderableCol)` dengan **`autoWidth: false`** serta memastikan class `whitespace-nowrap` dihapus dari elemen `<table>` setelah inisialisasi.
   - Header `<th>` dan sel kolom pendek lainnya (No. Tiket, Tujuan, Jumlah Item, Status, Aksi) tetap mempertahankan `whitespace-nowrap` masing-masing agar tata letak tabel tetap rapi.
3. **Cakupan Konsisten di Seluruh 5 Tabel (Semua Level User)**:
   - **Operator Tab 1** (`#table-my-rkbmd`), **Tab 2** (`#table-incoming-rkbmd`), dan **Tab 3** (`#table-forwarded-rkbmd`): kolom *Perihal Permohonan* dibungkus dengan `.rkbmd-col-perihal`, `.rkbmd-perihal-wrap`, dan `.rkbmd-notes-wrap`.
   - **Administrator** (`#table-admin-rkbmd`) dan **Supervisor** (`#table-supervisor-rkbmd`): mengganti `line-clamp-1` pada `$sub->title` dengan pembungkus `.rkbmd-perihal-wrap`, menambahkan tampilan `@if($sub->notes)` dengan `.rkbmd-notes-wrap`, serta membungkus kolom *Asal Unit & Sub-Unit* / *Keterkaitan / Arah Berkas* dengan `.rkbmd-col-meta` dan `.rkbmd-meta-wrap`.

### B. Kompilasi Ulang Aset Frontend Vite
- Menjalankan `npm run build`, menghasilkan aset terbaru:
  - [public/build/manifest.json](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/manifest.json)
  - [public/build/assets/app-IrLr88ye.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-IrLr88ye.css) (`109.12 kB`) — terverifikasi memuat `break-words`, `whitespace-normal`, `min-w-[220px]`, dan `md:max-w-sm` (menggantikan aset lama `app-19jAdnoN.css`).

### C. Pembaruan Pengujian Otomatis
- [tests/Feature/Operator/RkbmdSubUnitTargetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdSubUnitTargetTest.php): Menambahkan payload URL/string panjang tanpa spasi serta memverifikasi keberadaan `.rkbmd-perihal-wrap`, `.rkbmd-notes-wrap`, `autoWidth: false`, dan override `DataTable.ext.classes.table` pada ketiga tabel Operator.
- [tests/Feature/General/RkbmdReadOnlyAccessTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/RkbmdReadOnlyAccessTest.php): Memverifikasi tabel Administrator dan Supervisor turut merender `.rkbmd-perihal-wrap`, `.rkbmd-notes-wrap`, serta menampilkan `$sub->notes`.

---

## 3. Hasil Verifikasi

### A. Verifikasi Pengujian Otomatis (`php artisan test --filter=Rkbmd`)
Seluruh rangkaian pengujian fitur RKBMD setelah perubahan terakhir selesai dijalankan dengan hasil **PASS 100%**:
- `Tests\Feature\General\RkbmdReadOnlyAccessTest`: **9 passed**
- `Tests\Feature\MasterBarangCategoryAndSearchTest`: **1 passed**
- `Tests\Feature\Operator\RkbmdSubUnitTargetTest`: **6 passed**
- `Tests\Feature\Operator\RkbmdTest`: **28 passed**
- `Tests\Feature\TimezoneAndRkbmdTimestampTest`: **4 passed**
- **Total**: **48 passed (286 assertions)**

### B. Verifikasi DOM & Rendering Browser Nyata (Microsoft Edge Headless + DataTables 2.0.3 CDN)
Menguji halaman simulasi dengan aset build lokal ([app-IrLr88ye.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-IrLr88ye.css)) + CSS/JS CDN DataTables v2.0.3 (`jquery-3.7.1`, `dataTables.js`, `dataTables.tailwindcss.js`, `dataTables.tailwindcss.css`) pada viewport `1366x900` dengan deskripsi panjang berisi kalimat panjang dan URL tanpa spasi sesudah `DataTable()` & `columns.adjust()` dieksekusi:
- `dtLoaded`: `true`
- `tableHasNowrapClass`: `false`
- `tableComputedWhiteSpace`: `"normal"`
- `notesComputedWhiteSpace`: `"normal"`
- `wrapWidth`: `349` px
- `notesWidth`: `349` px (`notesHeight`: `176` px — teks melipat rapi ke bawah)
- `scrollContainerWidth`: `1104` px, `scrollContentWidth`: `1104` px (`horizontalOverflowPx`: **`0`**)

