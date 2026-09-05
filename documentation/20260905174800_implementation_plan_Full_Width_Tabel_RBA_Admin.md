# Implementation Plan: Penyesuaian Layout Full-Width pada Halaman Detail RBA Administrator

## 1. Masalah yang Diidentifikasi

Pada pembaruan sebelumnya, seluruh halaman detail RBA Administrator ([`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)) dibungkus dengan kelas Tailwind `max-w-7xl mx-auto`.
Kelas `max-w-7xl` membatasi lebar maksimal kontainer hanya sampai **1280px (80rem)** dan memusatkannya di tengah layar monitor.

**Dampak Negatif**:
- Tabel Hierarki RBA memiliki 6 kolom informasi padat (Kode Rekening, Uraian Belanja dengan level indentasi bertingkat 1–5, Nominal Usulan, Nominal Pagu, Supervisor, dan Operator).
- Pembatasan 1280px membuat tabel menjadi sangat sempit (*cramped*), kolom-kolom terhimpit, dan teks uraian serta nama pengguna mudah terpotong atau turun baris (*wrap*).
- Panel Monitoring Penginputan Unit di atas tabel juga menjadi kurang leluasa dalam menampilkan metrik unit kerja secara berdampingan.

---

## 2. Rencana Solusi & Perubahan

Mengubah pembungkus kontainer utama halaman detail RBA Administrator dari kontainer dengan batas lebar sempit (`max-w-7xl mx-auto`) menjadi kontainer **Lebar Penuh (*Full-Width / Fluid*)** dengan padding responsif yang konsisten:

```html
<!-- Sebelumnya -->
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

<!-- Diubah Menjadi -->
<div class="py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
```

### Keuntungan Layout Full-Width:
1. **Tabel Hierarki RBA Kembali Leluasa**:
   Tabel akan membentang mengikuti lebar resolusi layar monitor admin secara penuh (100% viewport) dengan tetap memiliki batas padding tepi yang aman (`px-4 sm:px-6 lg:px-8`).
2. **Keterbacaan Indentasi Kode Rekening Optimal**:
   Setiap sub-level kode rekening (level 1 hingga level 5) yang menjorok ke kanan memiliki ruang yang luas sehingga uraian belanja tidak bertabrakan dengan kolom nominal.
3. **Harmonisasi Seluruh Komponen Halaman**:
   Banner notifikasi flash session, kartu ringkasan Total Usulan & Pagu Global, panel Monitoring Penginputan Unit, serta Tabel Hierarki RBA semuanya sejajar pada lebar penuh tanpa adanya bagian yang belang.

---

## 3. Berkas yang Akan Dimodifikasi

### [MODIFY] [`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Mengubah pembungkus kontainer pada baris 154-155:
  - Dari: `<div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">`
  - Menjadi: `<div class="py-8"><div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">`

---

## 4. Rencana Pengujian & Verifikasi

1. **Uji Tampilan & Integritas Tag HTML**:
   - Memastikan pembungkus `w-full px-4 sm:px-6 lg:px-8` menutup dengan sempurna pada tag penutup `</div>` di akhir file.
2. **Automated Feature Tests**:
   - Menjalankan `php artisan test --filter=AdminDashboardTest` untuk memastikan seluruh aksi admin tetap lulus 100%.
3. **Build Aset Frontend**:
   - Menjalankan `bun run build` untuk mengonfirmasi kompilasi aset CSS dan JS.
4. **Full Test Suite**:
   - Menjalankan `php artisan test` untuk memastikan 148 tes seluruh modul aplikasi lulus tanpa regresi.
