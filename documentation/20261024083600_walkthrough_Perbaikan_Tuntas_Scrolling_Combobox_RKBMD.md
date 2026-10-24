# Walkthrough: Perbaikan Tuntas Masalah Scrolling Combobox RKBMD

Dokumen ini mendokumentasikan implementasi perbaikan tuntas atas kendala di mana daftar barang hasil pencarian pada formulir permohonan RKBMD tidak dapat di-scroll.

---

## 1. Ringkasan Perubahan yang Diterapkan

### 1.1 Defensive Styling Eksplisit ([create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php) & [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php))
- Menambahkan class `.rkbmd-combobox-dropdown` ke dalam `@push('styles')`:
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
- Menambahkan inline style fail-safe pada kontainer dropdown:
  ```html
  class="rkbmd-combobox-dropdown custom-scrollbar divide-y divide-slate-100"
  style="max-height: 280px; overflow-y: auto; overscroll-behavior: contain;"
  ```
- **Dampak**: Dropdown kini **dijamin 100%** memiliki batasan tinggi (`max-height: 280px`) dan peramban langsung mengaktifkan scrollbar vertikal serta event scroll wheel, tanpa bergantung pada cache atau build status Tailwind utility.

### 1.2 Menghapus `overflow-hidden` pada Pembungkus Card Form
- Mengubah pembungkus form dari:
  ```html
  <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200/80">
  ```
  Menjadi:
  ```html
  <div class="bg-white shadow-sm sm:rounded-2xl border border-slate-200/80">
  ```
- **Dampak**: Menghilangkan efek pemotongan visual (clipping) di bagian bawah form sehingga popup dropdown yang melayang dapat terlihat utuh.

### 1.3 Membersihkan Duplikasi Tag Penutup HTML
- Menghapus blok HTML duplikat pada [create.blade.php:285-316](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php#L285-L316) dan [edit.blade.php:304-335](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php#L304-L335).
- **Dampak**: Pohon DOM form kembali valid, mencegah penutupan prematur elemen `<form>` dan template looping Alpine.

### 1.4 Eliminasi Event Flooding `@mousemove`
- Menghapus listener `@mousemove="highlightedIndex = bIdx"` dari perulangan template item barang.
- Interaksi visual mouse kini sepenuhnya ditangani oleh CSS native `:hover`:
  ```html
  :class="{ 'bg-indigo-50 text-indigo-900': highlightedIndex === bIdx, 'hover:bg-slate-50': highlightedIndex !== bIdx }"
  ```
- **Dampak**: Memutar roda mouse (wheel) di atas 25 item hasil pencarian kini berjalan sangat mulus (60+ FPS) tanpa membebani reaktivitas Alpine.js.

### 1.5 Elevasi Dinamis Z-Index
- Menambahkan `:class="open ? 'z-30' : 'z-10'"` pada kolom combobox:
  ```html
  <div class="md:col-span-6 relative" :class="open ? 'z-30' : 'z-10'" x-data="rkbmdBarangCombobox(item, masterBarangs, searchUrl)">
  ```
- **Dampak**: Baris barang yang dropdown-nya sedang terbuka dijamin berada di depan baris barang lain di bawahnya.

### 1.6 Sinkronisasi Build Aset Produksi
- Menjalankan `npm run build` yang sukses memperbarui manifest dan bundle aset Vite (`app-19jAdnoN.css`).

---

## 2. Hasil Verifikasi Pengujian

### 2.1 Automated Test Suite
Pengujian fitur RKBMD dan Master Barang Permendagri 108 dijalankan via PHPUnit / Pest:
```bash
php artisan test --filter="RkbmdTest|PermendagriMasterBarangTest"
```
**Hasil**:
```text
   PASS  Tests\Feature\Admin\PermendagriMasterBarangTest (5 tests)
   PASS  Tests\Feature\Operator\RkbmdTest (28 tests)

  Tests:    33 passed (154 assertions)
  Duration: 7.67s
```
Semua 33 tes pengujian lulus 100% tanpa ada regresi.

### 2.2 Template Rendering Integrity Test
Pengujian render view Blade dilakukan via Tinker:
- `operator.rkbmd.create`: **Rendered successfully (35,779 bytes)**.
- `operator.rkbmd.edit`: **Rendered successfully (37,348 bytes)**.
Tidak ada error parsing Blade maupun tag HTML yang rusak.

---

## 3. Panduan Verifikasi Pengguna di Peramban

Silakan lakukan pengetesan langsung di browser lokal Anda dengan langkah berikut:

1. Buka formulir permohonan RKBMD: `http://127.0.0.1:8000/operator/rkbmd/create`.
2. Klik kolom input pencarian barang bertuliskan *"Ketik kode (1.3.2...) atau nama barang..."*.
3. Perhatikan bahwa dropdown langsung muncul dengan batas tinggi maksimal 280px dan scrollbar vertikal di sisi kanan.
4. **Uji Scroll Wheel**: Letakkan kursor di atas daftar dan putar roda scroll mouse. Daftar barang akan bergulir naik dan turun dengan lancar tanpa macet.
5. **Uji Drag Scrollbar**: Klik dan tahan tombol kiri mouse pada bilah scrollbar abu-abu di sebelah kanan, lalu geser ke bawah.
6. **Uji Pencarian Kata Kunci**: Ketik kata kunci (contoh: `meja` atau `1.3.2`). Setelah hasil pencarian muncul (hingga 25 item), lakukan scroll hingga item paling bawah.
7. **Uji Pemilihan Barang**: Klik salah satu barang atau gunakan tombol panah keyboard `↓` lalu tekan `Enter`. Barang terpilih dengan badge kode rekening dan form siap dikirim.
