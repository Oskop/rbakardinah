# Walkthrough: Perbaikan Akses Scroll pada Dropdown Pencarian Barang Formulir RKBMD

Telah berhasil diselesaikan perbaikan komprehensif pada fungsi scroll daftar barang hasil pencarian di formulir permohonan RKBMD (`operator/rkbmd/create` dan `operator/rkbmd/edit`).

---

## 1. Masalah yang Diselesaikan
Sebelumnya, ketika pengguna mencoba menggulir (*scroll*) daftar barang:
1. Menyeret (*drag*) atau mengeklik *scrollbar* menyebabkan dropdown tertutup seketika karena `@click.outside="closeDropdown()"` mendeteksi klik pada scrollbar Chromium Windows berada di luar batas *content box*.
2. Memutar roda *mouse wheel* memicu *scroll chaining* ke halaman latar belakang karena ketiadaan `overscroll-contain`.
3. Event `@mouseenter="highlightedIndex = bIdx"` memicu re-render kelas Alpine terus-menerus saat mouse melayang di atas daftar yang sedang bergulir.
4. Ketinggian `max-h-60` (240px) terlalu sempit (hanya memuat ~3 item).

---

## 2. Perubahan yang Diterapkan

### A. Formulir Tambah RKBMD (`resources/views/operator/rkbmd/create.blade.php`) & Formulir Edit (`edit.blade.php`)
- **Pemindahan `@click.outside="closeDropdown()"`**: Dipindahkan ke container pembungkus induk (`<div x-show="!item.selected_barang" class="relative" @click.outside="closeDropdown()">`) sehingga area input dan dropdown berada di dalam lingkup yang sama.
- **Pencegahan Event Bubbling**: Menambahkan `@mousedown.stop` dan `@click.stop` pada kontainer scrollable dropdown panel.
- **Perluasan Ketinggian**: Mengubah `max-h-60` (240px) menjadi `max-h-72` (288px) yang muat 5–6 item sekaligus.
- **Scroll Isolation (`overscroll-contain`)**: Mengunci momentum scroll mouse wheel agar tidak melompat ke halaman utama.
- **Navigasi Keyboard Akurat**: Memperbarui method `scrollToHighlighted()` menggunakan selector target atribut `:data-item-index="bIdx"`.
- **Penggantian Mouseenter**: Mengganti `@mouseenter` dengan `@mousemove` dan kelas native CSS `:hover` agar pengguliran mouse wheel berjalan 60 FPS tanpa *jitter*.
- **Custom Scrollbar Styling**: Menambahkan CSS scrollbar ramping (lebar 7px, track slate-50, thumb slate-300 rounded) yang terpasang melalui `@push('styles')`.

---

## 3. Hasil Pengujian & Verifikasi

Pengujian automated tests dijalankan kembali:
```
PASS Tests\Feature\Admin\PermendagriMasterBarangTest (5 tests)
PASS Tests\Feature\Operator\RkbmdTest (28 tests)

Tests: 33 passed (154 assertions)
Duration: 7.26s
```

Semua pengujian lolos 100% tanpa ada kerusakan (zero regression). Dropdown kini dapat di-scroll dengan sangat mudah menggunakan:
- Roda *mouse wheel* / touchpad
- Menyeret (*drag*) atau mengeklik *scrollbar*
- Tombol panah atas/bawah keyboard dengan auto-scrolling
