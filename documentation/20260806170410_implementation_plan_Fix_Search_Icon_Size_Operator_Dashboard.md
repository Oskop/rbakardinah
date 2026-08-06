# Implementation Plan - Fix Ukuran & Posisi Ikon Pencarian RBA pada Operator Dashboard

Memperbaiki bug ukuran dan posisi ikon pencarian (*search icon*) pada bagian **Daftar RBA Historis (Mode Daftar)** di Dashboard Operator (`resources/views/operator/dashboard.blade.php`), sehingga ikon kaca pembesar terkurung dengan sempurna di dalam bidang input tanpa membesar atau menutupi konten daftar RBA.

## User Review Required

> [!IMPORTANT]
> - **Permasalahan**: Ikon SVG kaca pembesar pada kotak pencarian Mode Daftar membesar dan menutupi elemen di bawahnya karena kurangnya kontainer batas inset dan atribut dimensi eksplisit.
> - **Solusi**:
>   - Membungkus ikon SVG ke dalam pembungkus standar Tailwind `<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">`.
>   - Menambahkan atribut dimensi eksplisit `width: 16px; height: 16px;` dan `pointer-events-none` pada SVG agar ukuran ikon strictly terkunci di 16px dan berpresisi di tengah sisi kiri kolom pencarian.

## Open Questions

> [!NOTE]
> - Perubahan hanya berfokus pada Mode Daftar di bagian **Daftar RBA Historis** sesuai petunjuk user.

## Proposed Changes

### View Layer

#### [MODIFY] [dashboard.blade.php (Operator Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- Memperbarui struktur HTML pada kotak pencarian `searchRba` di Mode Daftar:
  - Menyisipkan pembungkus `<div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">`.
  - Mengatur class dan style ikon SVG menjadi `class="h-4 w-4 text-gray-400" style="width:16px; height:16px;"`.
  - Menyesuaikan `pl-9` pada input text untuk *padding-left* yang rapi.

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan tidak ada efek samping/regresi.

### Manual Verification
- Login sebagai Operator.
- Buka Operator Dashboard (`/operator/dashboard`).
- Pada **Daftar RBA Historis** (Mode Daftar), amati ikon kaca pembesar pada kolom pencarian.
- Pastikan ikon tampil sangat rapi, berukuran proporsional (16px), berada di dalam kotak input, dan tidak menutupi daftar RBA maupun detail RBA di bawahnya.
- Ganti ke **Mode Grafik** dan kembali ke **Mode Daftar** untuk memastikan tampilan konsisten 100%.
