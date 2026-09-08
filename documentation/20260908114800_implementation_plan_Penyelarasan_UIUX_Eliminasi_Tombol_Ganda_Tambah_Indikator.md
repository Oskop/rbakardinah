# Implementation Plan: Penyelarasan UI/UX & Eliminasi Tombol Ganda Tambah Indikator

Dokumen ini merinci rencana perbaikan UI/UX pada halaman [resources/views/admin/performance-indicators/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/performance-indicators/index.blade.php) guna mengeliminasi redundansi dua tombol "Tambah Indikator" sehingga antarmuka kembali rapi, bersih, dan sesuai standar desain sistem SIPAKAR.

---

## 1. Analisis Kebutuhan & Masalah UI/UX

### 1.1 Masalah Saat Ini
- Pada pembaruan sebelumnya, untuk memastikan tombol berfungsi, tombol "+ Tambah Indikator" sempat ditambahkan ke dalam toolbar filter tabel di samping tombol Reset pencarian, sementara di header halaman (`<x-slot name="header">`) juga terdapat tombol "Tambah Indikator Baru".
- Keberadaan dua tombol untuk aksi yang sama dalam satu tampilan menciptakan redundansi visual (*visual clutter*), membingungkan alur pengguna (*cognitive overload*), dan tidak konsisten dengan konvensi halaman manajemen lainnya (seperti Periode, Pengumuman, dan Unit) di mana tombol aksi utama selalu berada di **Header Halaman**.

### 1.2 Tujuan Perbaikan
- Mengeliminasi tombol duplikat di toolbar filter tabel.
- Mempertahankan **satu tombol tunggal** yang elegan, proporsional, dan berfungsi penuh di **Header Halaman (`<x-slot name="header">`)**.
- Mengembalikan toolbar filter tabel ke fungsi aslinya sebagai area pencarian dan penyaringan data (Status Pills, Rentang 5 Tahun, Kolom Pencarian, Tombol Cari, dan Tombol Reset).

---

## 2. Rencana Perubahan (Proposed Changes)

### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/performance-indicators/index.blade.php)

1. **Header Halaman (`<x-slot name="header">`)**:
   - Pertahankan tombol utama "Tambah Indikator Baru" dengan penanganan event teruji:
     ```html
     <div class="flex items-center gap-2">
         <button type="button"
             onclick="window.dispatchEvent(new CustomEvent('open-indicator-modal'))"
             class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all cursor-pointer">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
             <span>Tambah Indikator Baru</span>
         </button>
     </div>
     ```

2. **Toolbar Filter Tabel**:
   - Hapus elemen `<button type="button" @click="openAddIndicatorModal()" ...>+ Tambah Indikator</button>` dari baris search/filter (baris ~110-117).
   - Susunan baris bawah toolbar menjadi ringkas dan fokus:
     - Input pencarian (search text field)
     - Tombol `🔍 Cari`
     - Tombol `Reset` (jika filter aktif)

---

## 3. Ilustrasi Tampilan Antarmuka yang Dirapikan

```text
=========================================================================================================================
  MANAJEMEN INDIKATOR KINERJA RSUD KARDINAH                             [ + Tambah Indikator Baru ]  <-- SATU TOMBOL SAJA
  Kelola data master indikator kinerja rumah sakit dan pantau target berkala 5 tahun perencanaan.
=========================================================================================================================

[ Status: Semua (4) | 🟢 Aktif (3) | ⚪ Nonaktif (1) ]            [ Periode Target (5 Tahun): ◀ [ 2024 - 2028 ▼ ] ▶ ]
-------------------------------------------------------------------------------------------------------------------------
[ 🔍 Cari indikator kinerja, kode, kategori teks bebas...                              ]  [ 🔍 Cari ]  [ Reset ]
                                                                                         ^^^^^^^^^^^^^^^^^^^^^^^^
                                                                                         (Toolbar bersih tanpa tombol ganda)
-------------------------------------------------------------------------------------------------------------------------
                                             TARGET PERIODE 5 TAHUN
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| NO  | KODE  | INDIKATOR KINERJA      | KAT/SAT| STATUS |  2024   |  2025   |  2026   |  2027   |  2028   |     AKSI      |
+-----+-------+------------------------+--------+--------+---------+---------+---------+---------+---------+---------------+
| 1   | IK-01 | Tingkat Akreditasi     | Mutu   |  🟢    | Paripurna 100%      100%      100%      100%    | [✏️][🎯][📜]   |
...
```

---

## 4. Rencana Verifikasi (Verification Plan)

1. **Pengujian Otomatis**:
   - Jalankan `php artisan test --filter=PerformanceIndicatorTest` untuk memastikan view ter-render tanpa error dan seluruh skenario pengujian tetap lulus 100%.
2. **Kompilasi Frontend & Blade**:
   - Jalankan `php artisan view:cache` dan `bun run build` untuk memverifikasi aset.
3. **Verifikasi Visual**:
   - Pastikan di browser hanya tampak **1 tombol "Tambah Indikator Baru"** di pojok kanan atas (header).
   - Ketika tombol tersebut diklik, modal dialog form tambah indikator terbuka dengan normal.
