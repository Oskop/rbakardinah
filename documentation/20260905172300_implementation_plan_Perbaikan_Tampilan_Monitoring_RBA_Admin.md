# Implementation Plan: Perbaikan Tampilan Monitoring RBA & Halaman Detail RBA Administrator

## 1. Masalah yang Ditemukan (Root Cause Analysis)

Setelah dilakukan audit mendalam pada berkas [`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php), ditemukan penyebab utama mengapa tampilan menjadi berantakan:

1. **Invalid HTML Nesting pada Baris Kartu Unit (Penyebab Utama Kerusakan Layout)**:
   - Baris kartu unit dibungkus oleh elemen `<button type="button" @click="toggleUnit(...)">`.
   - Di dalamnya, ditambahkan tombol refresh unit individual yang dibungkus oleh elemen `<form>` dan `<button type="submit">`.
   - Berdasarkan spesifikasi standar **W3C HTML5**, elemen `<button>` dilarang keras memuat elemen interaktif turunan seperti `<form>` atau `<button>` lain.
   - Saat browser (Chrome, Edge, Firefox) mem-parsing HTML yang tidak valid ini, browser secara otomatis menutup tag `<button>` luar secara paksa di tengah jalan.
   - **Dampaknya**:
     - Struktur Flexbox kartu unit putus di tengah jalan.
     - Elemen sisi kanan (Total Usulan Unit, Status Validasi Review, jumlah operator, dan icon panah chevron accordion) terlempar keluar dari header bar kartu atau jatuh ke baris baru secara tidak beraturan.
     - Menghasilkan tag penutup liar `</button>` tak berpasangan di baris 430 yang merusak parsing elemen tabel di dalam accordion.

2. **Mismatch Kontainer Lebar Halaman (Container Alignment & Width)**:
   - Banner notifikasi flash session (`session('success')` & `session('error')`) dibungkus oleh kontainer `<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">` yang langsung ditutup sebelum konten utama.
   - Sedangkan konten utama di bawahnya (Laporan Hierarki, Total Usulan/Pagu Global, Monitoring Panel, dan Tabel Hierarki) langsung berada di bawah `<div class="py-12">` tanpa pembungkus `max-w-7xl` atau padding responsif.
   - **Dampaknya**: Banner notifikasi memiliki lebar terbatas di tengah layar (1280px), sementara seluruh isi tabel dan kartu monitoring di bawahnya membentang 100% mentok ke pinggir layar tanpa batas dan tanpa margin/padding yang selaras.

---

## 2. Rencana Solusi dan Perbaikan

### A. Memperbaiki Baris Kartu Unit Menjadi Valid HTML5
- Mengubah elemen pembungkus terluar header bar unit dari:
  ```html
  <button type="button" @click="toggleUnit({{ $m['submission_id'] }})" class="...">
  ```
  menjadi elemen `<div>` interaktif yang valid:
  ```html
  <div @click="toggleUnit({{ $m['submission_id'] }})" role="button" tabindex="0"
      class="w-full text-left p-3.5 flex flex-col lg:flex-row lg:items-center justify-between gap-3 hover:bg-slate-50 transition-colors focus:outline-none cursor-pointer select-none">
  ```
  dan menutupnya dengan `</div>` di akhir baris kartu.
- Di dalam elemen `<div>`, meletakkan `<form action="{{ route('admin.submissions.sync-status', ...) }}" method="POST" class="inline" @click.stop>` dan `<button type="submit">` adalah **100% Legal & Valid HTML5**.
- Atribut `@click.stop` pada form/button refresh akan mencegah pemicuan event klik accordion kartu unit.
- Seluruh elemen baris unit (nama unit, badge status, tombol refresh, total usulan unit, validasi review, dan icon chevron) akan tersusun kembali dengan rapi dalam satu baris Flexbox horizontal yang presisi.

### B. Menyelaraskan Kontainer Utama Halaman
- Membungkus seluruh konten halaman utama (banner alert notifikasi, ringkasan usulan global, panel monitoring, dan tabel hierarki) di dalam satu kontainer standar yang konsisten:
  ```html
  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
          @if(session('success')) ... @endif
          @if(session('error')) ... @endif

          <div x-data="{ search: '', ... }">
              <!-- Total Usulan & Pagu Global -->
              <!-- Panel Monitoring Penginputan Unit -->
              <!-- Tabel Hierarki RBA -->
          </div>
      </div>
  </div>
  ```
- Merapikan tag penutup `</div>` di akhir file agar kedalaman tag (DOM tree depth) seimbang 100% tanpa tag liar/orphan.

### C. Menjaga Keutuhan Tombol Refresh Toolbar
- Memastikan tombol **Refresh Status Unit** global di toolbar panel monitoring tetap tersusun rapi berdampingan dengan tombol *Buka Semua* dan *Tutup Semua* tanpa menimbulkan *horizontal scroll overflow*.

---

## 3. Rencana Berkas yang Diubah

### [MODIFY] [`resources/views/admin/headers/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Restrukturisasi baris 154: satukan wrapper `<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">` untuk membungkus session alert dan seluruh konten halaman.
- Ganti baris 361-430: ubah outer tag `<button>` kartu unit menjadi `<div>` dengan `role="button"` dan cursor pointer.
- Rapikan tag penutup di akhir berkas agar tidak ada orphan closing `</div>`.

---

## 4. Rencana Pengujian & Verifikasi

1. **Audit Struktur DOM**:
   - Menjalankan skrip validasi tag HTML untuk memastikan tidak ada mismatch tag, nesting form/button di dalam button, atau orphan `</div>`.
2. **Automated Feature Tests**:
   - Menjalankan `php artisan test --filter=AdminDashboardTest` untuk memastikan seluruh fungsionalitas admin dan tombol sinkronisasi tetap berjalan sukses.
3. **Full Test Suite**:
   - Menjalankan `php artisan test` untuk memastikan 148 tes seluruh modul lulus 100%.
4. **Vite Assets Build**:
   - Menjalankan `bun run build` untuk mengonfirmasi aset siap digunakan.
