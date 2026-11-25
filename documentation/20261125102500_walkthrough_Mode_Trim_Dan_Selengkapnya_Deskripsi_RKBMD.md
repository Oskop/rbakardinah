# Walkthrough: Mode Trim Default & Tombol `(selengkapnya...)` pada Deskripsi Permohonan RKBMD

Dokumen ini merangkum implementasi fitur **mode trim default** dengan tombol interaktif **`(selengkapnya...)`** dan **`(ringkas)`** pada kolom *Perihal Permohonan* di seluruh 5 tabel daftar RKBMD (Operator 3 tab, Administrator, dan Supervisor), beserta hasil pengujian PHPUnit dan verifikasi interaktif browser dengan DataTables v2.0.3.

---

## 1. Ringkasan Implementasi

### A. Komponen Blade Reusable: `<x-rkbmd-description>`
Dibuat komponen baru [rkbmd-description.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/rkbmd-description.blade.php) dengan props `$notes` (`?string`) dan `$limit` (`int`, default **`150`** karakter):

1. **Deskripsi Pendek / Sedang ($\le 150$ karakter)**:
   Tampil penuh secara langsung dalam blok `.rkbmd-notes-wrap` tanpa tombol tambahan agar daftar tidak penuh elemen berulang.
2. **Deskripsi Panjang ($> 150$ karakter)**:
   - **Mode Trim Default (`.rkbmd-desc-trimmed`)**: Memotong teks pada 150 karakter pertama menggunakan `\Illuminate\Support\Str::limit($trimmedNotes, $limit, '')`, diikuti `...` serta tombol inline **`(selengkapnya...)`** (`data-rkbmd-toggle="expand"`).
   - **Mode Penuh (`.rkbmd-desc-full.hidden`)**: Menyimpan teks utuh `$trimmedNotes` diikuti tombol inline **`(ringkas)`** (`data-rkbmd-toggle="collapse"`).
   - Tetap mewarisi proteksi wrap (`.rkbmd-perihal-wrap`, `.rkbmd-notes-wrap`, `overflow-wrap: anywhere`, lebar terkunci $\le 24\text{rem}$ / `384px`).

### B. Penemuan Penting Saat Uji Browser & Solusi Delegated Event Handler
Saat simulasi di browser nyata (Edge Headless) dengan DataTables v2.0.3 (`order: [[0, 'asc']]` aktif), ditemukan bahwa **jika toggle memakai `x-show` Alpine.js 3.4.2**, ketika DataTables mengurutkan/memindahkan elemen `<tr>` dari dan ke `<tbody>`, `MutationObserver` Alpine membersihkan efek reaktif `x-show` sehingga klik `(selengkapnya...)` hanya mengubah nilai state di memori tanpa mengubah visibilitas DOM.

**Solusi permanen di [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)**:
- Menggunakan **delegated click handler jQuery** `$(document).on('click', '.rkbmd-desc-toggle', ...)` untuk menukar class `.hidden` pada `.rkbmd-desc-trimmed` dan `.rkbmd-desc-full`.
- Dengan pola *event delegation* pada `document` dan class Tailwind `.hidden`, fitur toggle bekerja **100% kebal** terhadap proses inisialisasi DataTables, pengurutan (*sorting*), filter tanggal/status, pencarian cepat, perpindahan tab, maupun perpindahan halaman pagination.
- Karena kedua span tetap berada dalam sel `<td>`, fungsi pencarian cepat DataTables tetap dapat mencari kata kunci yang berada di bagian akhir teks tersembunyi.

### C. Penerapan Konsisten di Semua Level User
Seluruh 5 blok deskripsi pada [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) kini memakai `<x-rkbmd-description :notes="$..." />`:
1. `#table-my-rkbmd` (Operator — Permohonan Saya)
2. `#table-incoming-rkbmd` (Operator — Permohonan Masuk)
3. `#table-forwarded-rkbmd` (Operator — Permohonan Dialihkan)
4. `#table-admin-rkbmd` (Administrator — Seluruh Rumah Sakit)
5. `#table-supervisor-rkbmd` (Supervisor — Terkait Unit)

---

## 2. Berkas yang Dibuat / Dimodifikasi

| Berkas | Perubahan |
| :--- | :--- |
| [resources/views/components/rkbmd-description.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/rkbmd-description.blade.php) | *(Baru)* Komponen deskripsi dengan mode trim 150 karakter dan toggle `(selengkapnya...)` / `(ringkas)`. |
| [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) | Pemakaian `<x-rkbmd-description />` di kelima tabel serta penambahan *delegated click listener* `.rkbmd-desc-toggle`. |
| [public/build/assets/app-IrLr88ye.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-IrLr88ye.css) | Kompilasi ulang asset Vite (`npm run build`, selesai dalam `3.29s`), memastikan utility `.hidden`, `text-indigo-600`, `hover:underline`, `break-words`, dan `whitespace-normal` tersedia. |
| [tests/Feature/Operator/RkbmdSubUnitTargetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdSubUnitTargetTest.php) | Memverifikasi pratinjau trim, `.rkbmd-desc-container`, `.rkbmd-desc-toggle`, tombol `(selengkapnya...)` & `(ringkas)` untuk deskripsi panjang serta ketiadaan tombol toggle untuk deskripsi pendek. |
| [tests/Feature/General/RkbmdReadOnlyAccessTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/RkbmdReadOnlyAccessTest.php) | Memverifikasi Administrator dan Supervisor melihat deskripsi pendek tanpa toggle dan melihat mode trim + `(selengkapnya...)` / `(ringkas)` untuk deskripsi panjang. |

---

## 3. Hasil Verifikasi

### A. Pengujian Otomatis (`php artisan test --filter=Rkbmd`)
Dieksekusi setelah seluruh perubahan terakhir selesai dengan hasil **PASS 100%**:
- `Tests\Feature\General\RkbmdReadOnlyAccessTest`: **10 passed**
- `Tests\Feature\MasterBarangCategoryAndSearchTest`: **1 passed**
- `Tests\Feature\Operator\RkbmdSubUnitTargetTest`: **7 passed**
- `Tests\Feature\Operator\RkbmdTest`: **28 passed**
- `Tests\Feature\TimezoneAndRkbmdTimestampTest`: **4 passed**
- **Total**: **50 passed (313 assertions)**

### B. Verifikasi Interaktif DOM di Microsoft Edge Headless + DataTables 2.0.3 CDN
Menguji halaman simulasi dengan CSS Vite lokal ([app-IrLr88ye.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/public/build/assets/app-IrLr88ye.css)) + DataTables v2.0.3 CDN (`order: [[0, 'asc']]` memicu reorder `<tr>` + `dt.search()` + `draw()`) pada viewport `1366x900`:
- **Kondisi Awal (Mode Trim Default)**:
  - `trimmedVisible`: `true`, `fullVisible`: `false`, `height`: `59` px, `width`: `384` px, `overflowPx`: `0`
- **Setelah Klik `(selengkapnya...)` (Pasca-Reorder DataTables)**:
  - `trimmedVisible`: `false`, `fullVisible`: `true`, `height`: `156` px, `width`: `384` px, `overflowPx`: `0`
- **Pencarian DataTables terhadap teks tersembunyi** (`dt.search('pengadaan-switch-core')`):
  - `searchMatchCount`: `1` (teks tetap terindeks)
- **Setelah Klik `(ringkas)` (Pasca-Search & Redraw)**:
  - `trimmedVisible`: `true`, `fullVisible`: `false`, `height`: `59` px, `width`: `384` px, `overflowPx`: `0`

