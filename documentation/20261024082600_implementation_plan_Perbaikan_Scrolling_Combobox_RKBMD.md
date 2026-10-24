# Implementation Plan: Perbaikan Scrolling Daftar Barang pada Formulir RKBMD

Dokumen ini menyajikan investigasi teknis mendalam dan rencana perbaikan tuntas atas kendala di mana **daftar barang hasil pencarian pada formulir RKBMD tidak dapat di-scroll**, baik menggunakan mouse wheel, trackpad, drag scrollbar, maupun navigasi keyboard.

---

## 1. Analisis Akar Masalah (Root Cause Analysis)

Setelah dilakukan audit menyeluruh pada berkas [create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php), [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php), bundle aset Vite (`public/build/assets/`), dan hirarki DOM, ditemukan **5 penyebab utama** mengapa scrolling tidak berfungsi:

### 1.1 Ketiadaan Rule `max-h-72` pada Bundle CSS Produksi (Penyebab Utama)
- Elemen dropdown pencarian barang didefinisikan dengan class Tailwind:
  ```html
  <div class="... max-h-72 overflow-y-auto ...">
  ```
- Aplikasi Laravel ini berjalan dalam mode aset statis produksi (`public/build/assets/`). Class `max-h-72` merupakan class Tailwind utility baru yang **belum ter-compile ke dalam berkas CSS aktif saat itu**.
- Akibatnya, peramban membaca elemen dropdown dengan `max-height: none`. Kontainer dropdown memanjang secara penuh ke bawah mengikuti seluruh 25 item hasil pencarian (tinggi elemen sama dengan `scrollHeight`).
- Karena tinggi elemen membesar mengikuti seluruh isinya, **tidak terjadi overflow internal**. Tanpa overflow, peramban **tidak akan pernah memunculkan scrollbar** dan pemutaran scroll wheel pada mouse sama sekali tidak akan menggeser elemen dropdown!

### 1.2 Pemotongan Visual oleh Card Form (`overflow-hidden`)
- Pembungkus formulir utama pada baris 45 memiliki class:
  ```html
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
  ```
- Karena dropdown memanjang tanpa batasan tinggi (`max-height: none`), item-item di urutan bawah yang melampaui batas batas bawah kartu langsung **terpotong (clipped)** dan menjadi tak kasat mata di luar batas kartu, sementara kontainer dropdown itu sendiri tidak bisa di-scroll ke bawah.

### 1.3 Adanya Duplikasi Blok Tag Penutup HTML (DOM Hierarchy Corruption)
- Pada [create.blade.php:285-316](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php#L285-L316) dan [edit.blade.php:304-335](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php#L304-L335), terdapat duplikasi blok input volume/satuan dan blok tag penutup `</div></div></template></div></div>`.
- Tag penutup ekstra ini merusak pohon DOM dan menutup elemen `<form>` serta container Alpine secara prematur di tengah halaman.

### 1.4 Event Flooding dari `@mousemove` saat Memutar Scroll Wheel
- Pada setiap item hasil pencarian terdapat listener:
  ```html
  @mousemove="highlightedIndex = bIdx"
  ```
- Saat pengguna mencoba melakukan scroll menggunakan roda mouse (wheel), setiap item yang melintas di bawah kursor memicu event `mousemove`. Hal ini memicu Alpine.js untuk mengevaluasi ulang binding class CSS pada seluruh 25 node DOM secara berulang-ulang, menyebabkan jank/lag yang parah.

### 1.5 Kurangnya Elevasi `z-index` Baris Aktif
- Pada mode banyak baris barang (`Item #1`, `Item #2`, dst.), dropdown baris pertama yang mengembang ke bawah dapat tertindih oleh kontainer baris kedua di bawahnya jika konteks `z-index` baris aktif tidak dinaikkan saat dropdown dibuka.

---

## 2. Rencana Solusi & Perbaikan Komprehensif

Untuk memastikan scrolling **100% berfungsi lancar dan kebal terhadap masalah cache atau build aset**, perbaikan akan diterapkan dengan prinsip **defensive styling**:

### 2.1 Definisi Styling Eksplisit & Fail-Safe (`.rkbmd-combobox-dropdown`)
Alih-alih hanya mengandalkan utility Tailwind yang rentan build, tambahkan rule CSS mandiri pada `@push('styles')` di `create.blade.php` dan `edit.blade.php`:

```css
.rkbmd-combobox-dropdown {
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    top: 100% !important;
    margin-top: 0.25rem !important;
    max-height: 280px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    overscroll-behavior: contain !important;
    -webkit-overflow-scrolling: touch;
    z-index: 50 !important;
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 0.75rem !important;
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
}
```

Serta tambahkan inline style langsung pada elemen sebagai jaminan lapis kedua:
```html
style="max-height: 280px; overflow-y: auto; overscroll-behavior: contain;"
```
Dengan ini, `max-height: 280px` dan `overflow-y: auto` dijamin aktif 100% dalam kondisi apapun.

### 2.2 Bersihkan Duplikasi Blok HTML
- Buang blok duplikat [create.blade.php:285-316](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php#L285-L316).
- Buang blok duplikat [edit.blade.php:304-335](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php#L304-L335).
- Pastikan setiap tag pembuka `<template>` dan `<div>` berpasangan secara tepat.

### 2.3 Perbaiki Overflow pada Card Pembungkus Form
- Ubah baris 45 dari:
  ```html
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
  ```
  Menjadi:
  ```html
  <div class="bg-white shadow-sm sm:rounded-2xl border border-slate-200/80">
  ```
  Menghilangkan `overflow-hidden` memastikan dropdown yang melayang (`position: absolute`) tidak terpotong oleh batas kartu form.

### 2.4 Ganti `@mousemove` dengan CSS Native Hover
- Hapus `@mousemove="highlightedIndex = bIdx"` dari template pengulangan item barang.
- Gunakan CSS class `hover:bg-indigo-50/80 hover:text-indigo-950` murni untuk interaksi mouse.
- Pertahankan `highlightedIndex` hanya saat pengguna bernavigasi menggunakan tombol panah keyboard (`ArrowUp` / `ArrowDown`).
- Ini mengeliminasi lag dan jank saat mouse wheel diputar cepat.

### 2.5 Dynamic Z-Index Elevasi Baris Aktif
- Berikan binding class dinamis pada wrapper combobox:
  ```html
  <div class="md:col-span-6 relative" :class="open ? 'z-30' : 'z-10'" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">
  ```
  Memastikan baris yang dropdown-nya sedang terbuka selalu berada di lapisan visual teratas.

### 2.6 Sinkronisasi Build Aset Vite
- Jalankan `npm run build` untuk memperbarui berkas aset produksi `public/build/assets/` sehingga manifest dan seluruh class utilitas sinkron sempurna.

---

## 3. Tahapan Eksekusi (Proposed Changes)

### Langkah 1: Perbarui [resources/views/operator/rkbmd/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php)
1. Hilangkan `overflow-hidden` pada card form baris 45.
2. Tambahkan `:class="open ? 'z-30' : 'z-10'"` pada container combobox.
3. Terapkan class `.rkbmd-combobox-dropdown` dan inline style `max-height: 280px; overflow-y: auto; overscroll-behavior: contain;`.
4. Hapus `@mousemove` pada template item dan gunakan CSS `:hover`.
5. Hapus blok duplikat tag baris 285-316.
6. Perbarui blok `@push('styles')` dengan styling lengkap `.rkbmd-combobox-dropdown` dan scrollbar styling.

### Langkah 2: Perbarui [resources/views/operator/rkbmd/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php)
1. Terapkan perbaikan yang persis sama dengan Langkah 1 pada tampilan edit.
2. Hapus blok duplikat tag baris 304-335.

### Langkah 3: Rebuild Aset Vite
1. Eksekusi `npm run build` untuk mengompilasi manifest dan aset produksi.

---

## 4. Rencana Verifikasi (Verification Plan)

### Automated Test:
Jalankan seluruh test suite RKBMD dan Master Barang untuk menjamin tidak ada regresi fungsional:
```bash
php artisan test --filter="RkbmdTest|PermendagriMasterBarangTest"
```
*Ekspektasi*: Seluruh 33 pengujian (154 assertions) tetap **PASS 100%**.

### DOM & Syntax Validation:
Periksa integritas pohon DOM hasil render Blade untuk memastikan tidak ada tag yang bocor, terpotong, atau tidak seimbang.

### Manual Browser Checklist (Panduan Verifikasi Pengguna):
1. Buka halaman `http://127.0.0.1:8000/operator/rkbmd/create`.
2. Klik kolom pencarian barang (muncul daftar rekomendasi 15 barang).
3. **Uji Scroll Wheel**: Putar roda mouse di atas daftar barang. Daftar harus bergulir naik dan turun dengan mulus.
4. **Uji Scrollbar Drag**: Klik dan geser bilah scrollbar di sisi kanan dropdown. Daftar harus mengikuti pergeseran scrollbar.
5. **Uji Pencarian Kata Kunci**: Ketik kata kunci (misal `meja` atau `1.3.2`). Daftar 25 hasil pencarian muncul dan dapat di-scroll hingga item ke-25.
6. **Uji Navigasi Keyboard**: Tekan tombol `ArrowDown` dan `ArrowUp`. Indikator highlight berpindah dan kontainer otomatis men-scroll item terpilih ke area pandang (`scrollIntoView`).
7. **Uji Klik Pilihan**: Tekan tombol `Enter` atau klik salah satu item. Barang terpilih dengan benar dan dropdown tertutup.
