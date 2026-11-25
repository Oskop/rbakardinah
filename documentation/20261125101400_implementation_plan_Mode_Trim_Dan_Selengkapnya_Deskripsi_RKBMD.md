# Implementation Plan: Mode Trim Default & Tombol `(selengkapnya...)` pada Deskripsi Permohonan RKBMD

Dokumen ini menjabarkan rencana implementasi agar deskripsi permohonan (`notes`) yang panjang pada tabel daftar RKBMD ditampilkan dalam **mode ringkas (*trim*) secara default** dan dapat diperluas/diringkas kembali secara interaktif melalui tombol **`(selengkapnya...)`** / **`(ringkas)`** di seluruh level pengguna.

---

## 1. Latar Belakang & Tujuan

Setelah masalah *wrap text* pada DataTables teratasi, permohonan yang memiliki deskripsi sangat panjang (misalnya ratusan hingga ribuan karakter) kini terlipat ke bawah dengan rapi tanpa *horizontal scroll*. Namun, bila seluruh teks langsung tampil penuh secara default di setiap baris tabel, tinggi baris (*row height*) menjadi sangat tinggi dan mengurangi efisiensi pemindaian daftar tabel.

Solusi yang dibutuhkan:
1. Deskripsi pendek ($\le$ **150 karakter**, sekitar 1–3 baris pada kolom selebar `24rem`/`384px`) tetap tampil utuh secara langsung tanpa tombol tambahan.
2. Deskripsi panjang ($>$ **150 karakter**) secara **default tampil dalam mode trim** (`150` karakter pertama + `...`) disertai tautan tombol inline **`(selengkapnya...)`**.
3. Saat tombol **`(selengkapnya...)`** diklik, teks penuh muncul secara inline (tetap terbungkus rapi dalam batas `.rkbmd-perihal-wrap` / `.rkbmd-notes-wrap`) disertai tombol **`(ringkas)`** untuk mengembalikan ke mode ringkas.
4. Tetap kompatibel penuh dengan fitur **Pencarian Cepat DataTables**, **pergantian tab**, **pagination**, **semua level user** (Operator 3 tab, Administrator, Supervisor), serta **nol horizontal overflow**.

---

## 2. Desain Teknis

### A. Ekstraksi Komponen Blade Reusable: `<x-rkbmd-description>`
Untuk mencegah duplikasi markup di kelima tabel pada [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php), logika tampilan deskripsi diekstrak ke komponen baru:
- **File Baru**: [resources/views/components/rkbmd-description.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/rkbmd-description.blade.php)
- **Props**:
  - `notes`: `?string` (default `null`)
  - `limit`: `int` (default `150`)
- **Mekanisme Kerja**:
  1. Membersihkan spasi tepi via `$trimmedNotes = trim((string) ($notes ?? ''))`. Jika kosong, komponen tidak merender HTML.
  2. Jika `mb_strlen($trimmedNotes) <= $limit`:
     Menampilkan teks penuh apa adanya dalam blok `.rkbmd-notes-wrap` tanpa tombol toggle.
  3. Jika `mb_strlen($trimmedNotes) > $limit`:
     Membungkus elemen deskripsi dalam `x-data="{ expanded: false }"`:
     - **State Default (`x-show="!expanded"`)**:
       Menampilkan `\Illuminate\Support\Str::limit($trimmedNotes, $limit, '')`, dilanjutkan inline `<span class="text-slate-400">...</span>` dan `<button type="button" @click="expanded = true" ...>(selengkapnya...)</button>`.
     - **State Penuh (`x-show="expanded" x-cloak style="display: none;"`)**:
       Menampilkan `{{ $trimmedNotes }}` lengkap (terbungkus `.rkbmd-notes-wrap`) diikuti inline `<button type="button" @click="expanded = false" ...>(ringkas)</button>`.

### B. Alasan Pilihan Pendekatan (`Str::limit` + Dual Block Alpine.js vs Pure CSS `line-clamp`)
1. **Penempatan Tombol Inline Persis di Ujung Teks**: CSS `-webkit-line-clamp` menggunakan `display: -webkit-box` yang tidak mendukung penempatan tombol inline `(selengkapnya...)` tepat di akhir potongan teks dan rentan bentrok dengan `overflow-wrap: anywhere` pada DataTables. Sebaliknya, `Str::limit(..., 150, '')` memotong presisi baik kalimat normal maupun URL panjang tanpa spasi, lalu menempatkan `(selengkapnya...)` tepat di ujung potongan.
2. **Pencarian DataTables Tetap Akurat 100%**: Karena blok teks penuh tetap berada di DOM (`<td>`) dalam status tersembunyi (`expanded: false`), fitur pencarian cepat (`#filter-search`) DataTables tetap dapat menemukan kata kunci yang berada di pertengahan atau akhir paragraf panjang.
3. **Nol Kedipan (*FOUC*) Sebelum Alpine.js Aktif**: Berdasarkan hasil inspeksi terhadap CSS build saat ini (`app-IrLr88ye.css`), rule `[x-cloak]` belum terdefinisi secara global. Dengan menambahkan `[x-cloak] { display: none !important; }` pada `@push('styles')` serta `style="display: none;"` pada elemen `x-show="expanded"`, teks penuh tidak akan sempat berkedip ganda saat halaman pertama dimuat.
4. **Lebar Kolom Tidak Bergeser Saat Expand**: Karena `.rkbmd-perihal-wrap` mengunci lebar maksimal `24rem` (`384px`) dan `autoWidth: false` aktif pada seluruh tabel DataTables, mengklik `(selengkapnya...)` hanya menambah tinggi sel secara vertikal tanpa memicu *horizontal scroll*.

### C. Penerapan di Kelima Tabel (`index.blade.php`)
Mengganti seluruh 5 blok `@if(...->notes)` pada [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) menjadi `<x-rkbmd-description :notes="$..." />`:
1. `#table-my-rkbmd` (Operator — Permohonan Saya)
2. `#table-incoming-rkbmd` (Operator — Permohonan Masuk)
3. `#table-forwarded-rkbmd` (Operator — Permohonan Dialihkan)
4. `#table-admin-rkbmd` (Administrator — Seluruh Rumah Sakit)
5. `#table-supervisor-rkbmd` (Supervisor — Terkait Unit)

Serta menambahkan `[x-cloak] { display: none !important; }` ke blok `@push('styles')`.

---

## 3. Berkas yang Akan Dibuat / Dimodifikasi

| Berkas | Perubahan |
| :--- | :--- |
| [resources/views/components/rkbmd-description.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/rkbmd-description.blade.php) | *(Baru)* Komponen reusable deskripsi RKBMD dengan batas default 150 karakter, mode trim default, dan tombol toggle `(selengkapnya...)` / `(ringkas)`. |
| [resources/views/operator/rkbmd/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) | Menambahkan `[x-cloak]` di `@push('styles')` dan mengganti tampilan deskripsi pada kelima tabel menggunakan `<x-rkbmd-description :notes="..." />`. |
| `public/build/*` | Menjalankan `npm run build` agar class Tailwind yang dipakai di komponen baru masuk ke CSS production. |
| [tests/Feature/Operator/RkbmdSubUnitTargetTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdSubUnitTargetTest.php) & [tests/Feature/General/RkbmdReadOnlyAccessTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/General/RkbmdReadOnlyAccessTest.php) | Menambahkan asersi PHPUnit untuk deskripsi pendek ($\le 150$ karakter, tanpa tombol `(selengkapnya...)`) serta deskripsi panjang ($> 150$ karakter, merender pratinjau trim + tombol `(selengkapnya...)` & `(ringkas)`). |
| `documentation/..._walkthrough_*.md` | Pembuatan walkthrough sinkron di artifact dan `documentation/` setelah selesai. |

---

## 4. Poin Konfirmasi UI (Opsional)

1. **Batas Pemotongan Default (`150` karakter)**: Pada kolom lebar `384px`, `150` karakter tampil sekitar **2–3 baris**. Apakah angka `150` karakter ini sudah sesuai, atau Anda ingin lebih pendek/panjang (misalnya `100` atau `200` karakter)?
2. **Label Tombol**: Menggunakan `(selengkapnya...)` saat ter-trim dan `(ringkas)` saat diperluas penuh.

---

## 5. Rencana Verifikasi

1. **Kompilasi Asset**: Jalankan `npm run build` dan pastikan aset Vite ter-generate bersih.
2. **Uji Otomatis PHPUnit (`php artisan test --filter=Rkbmd`)**:
   - Memverifikasi deskripsi pendek tidak memunculkan tombol `(selengkapnya...)`.
   - Memverifikasi deskripsi panjang pada Operator (3 tab), Administrator, dan Supervisor memunculkan pratinjau ter-trim, tombol `(selengkapnya...)`, tombol `(ringkas)`, dan tetap menyimpan teks penuh di DOM untuk pencarian DataTables.
3. **Verifikasi Interaktif DOM di Browser (Edge Headless)**:
   - Memastikan kondisi awal ter-trim (`!expanded`) hanya menampilkan pratinjau ringkas tanpa FOUC.
   - Mengklik tombol `(selengkapnya...)` secara programatik di halaman ter-render dan memverifikasi tinggi sel bertambah menampilkan teks penuh while lebar tetap terkunci (`wrapWidth <= 384px`, `horizontalOverflowPx === 0`), lalu kembali ringkas saat diklik `(ringkas)`.

