# Rencana Implementasi: Penghilangan Sisa Ruang Kosong Banner Pengumuman Pasca-Dismiss

## 1. Deskripsi Masalah & Analisis Akar Masalah (Root Cause)

### Deskripsi Masalah
Ketika pengguna (Operator, Supervisor, maupun Administrator) mengklik tombol tutup ("x") pada kartu banner pengumuman, kartu pesan memang tertutup (`display: none`), namun masih menyisakan ruang/celah kosong (*blank space*) yang cukup lebar di antara bilah navigasi atas (*top navigation*) dan judul halaman (*page title header*).

### Analisis Akar Masalah (*Root Cause*)
Pada [announcement-banner.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/announcement-banner.blade.php):
```html
@if($activeAnnouncements->isNotEmpty())
    <div class="w-full space-y-2.5 px-4 sm:px-6 lg:px-8 pt-4">
        @foreach($activeAnnouncements as $announcement)
            <div x-data="{ dismissed: ... }" x-show="!dismissed">
                ...
```
1. **Container Luar Berdiri Sendiri Tanpa State Visibilitas**: Kontainer pembungkus luar memiliki utilitas padding `pt-4` dan padding horizontal `px-4 sm:px-6 lg:px-8`.
2. **State Alpine Terisolasi di Level Kartu**: State `dismissed` hanya ada pada masing-masing kartu banner di dalam `@foreach`. Saat tombol "x" diklik, kartu di dalamnya disembunyikan, tetapi elemen `div` kontainer luar tetap ada di DOM dan mempertahankan tinggi padding `pt-4`.
3. **Efek Pasca-Refresh Halaman**: Ketika halaman dimuat ulang di sesi yang sama, jika seluruh pengumuman aktif telah ditutup oleh pengguna (`sessionStorage` bernilai `'true'`), Blade template di server tetap me-render pembungkus luar `<div class="... pt-4">` karena query `$activeAnnouncements->isNotEmpty()` tetap bernilai `true`. Hal ini menyebabkan celah kosong muncul permanen di setiap halaman selama sesi aktif.

---

## 2. Solusi yang Diusulkan

### A. Pengangkatan State Alpine.js ke Container Luar (*Lift State Up*)
Pindahkan manajemen state Alpine.js dari kartu pengumuman individu ke elemen kontainer luar:
- Inisialisasi dictionary `announcements` pada container luar yang memetakan status visibilitas setiap ID pengumuman dari `sessionStorage`.
- Buat getter reaktif `get hasVisible()` yang memeriksa apakah masih ada setidaknya satu pengumuman yang terlihat (`Object.values(this.announcements).some(v => v === true)`).
- Terapkan `x-show="hasVisible"` pada container luar. Jika seluruh pengumuman telah ditutup (atau saat semua bernilai `dismissed` pada pemuatan awal), container luar akan otomatis disembunyikan (`display: none`), sehingga padding `pt-4` langsung lenyap 100% tanpa sisa ruang.
- Sediakan method `dismiss(id)` yang mengubah state `this.announcements[id] = false` dan menyimpan flag ke `sessionStorage`.

### B. Mencegah Kedipan Layout (*Layout Shift / Flash*) dengan `[x-cloak]`
- Tambahkan aturan utilitas CSS `[x-cloak] { display: none !important; }` ke [app.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/css/app.css).
- Pasang atribut `x-cloak` pada container banner agar elemen tidak merender padding kosong sebelum JavaScript Alpine.js selesai mengevaluasi status `sessionStorage`.

---

## 3. Rincian Perubahan Berkas (*Proposed Changes*)

### Frontend & Blade Views

#### [MODIFY] [announcement-banner.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/announcement-banner.blade.php)
- Bungkus container luar dengan `x-data`, `x-show="hasVisible"`, `x-cloak`, dan transisi keluar halus.
- Delegasikan aksi tombol dismiss pada masing-masing kartu ke method `dismiss(id)` milik container luar.

#### [MODIFY] [app.css](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/css/app.css)
- Tambahkan definisi styling `[x-cloak] { display: none !important; }` untuk menghilangkan flicker saat inisialisasi Alpine.js.

---

## 4. Rencana Verifikasi (*Verification Plan*)

### Automated Tests
- Menjalankan kembali seluruh test suite pengumuman:
  `php artisan test --filter=AnnouncementTest`
- Menjalankan kompilasi frontend Vite:
  `bun run build`
- Menjalankan full regression test:
  `php artisan test`

### Manual Verification
1. Login sebagai Operator atau Supervisor dengan pengumuman aktif.
2. Periksa posisi banner pengumuman (berada tepat di bawah navbar dengan spasi rapi).
3. Klik tombol tutup ("x") pada banner pengumuman.
4. Pastikan kartu pengumuman tertutup dan container luar seketika hilang (`display: none`), sehingga jarak antara navbar dan judul halaman normal kembali tanpa ada celah sisa padding `pt-4`.
5. Lakukan reload halaman: pastikan celah kosong tidak muncul kembali karena semua banner telah di-dismiss pada sesi tersebut.

---

## 5. Lokasi Penyimpanan Dokumentasi
Setelah disetujui, rencana implementasi ini disimpan di:
- `documentation/20260907140700_implementation_plan_Hilangkan_Sisa_Ruang_Banner_Pengumuman_Pasca_Dismiss.md`
