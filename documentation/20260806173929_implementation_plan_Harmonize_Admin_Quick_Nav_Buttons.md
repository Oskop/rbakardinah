# Implementation Plan - Penyelarasan Layout & Desain Tombol Navigasi Cepat Master Data Dashboard Administrator

Meningkatkan estetika dan kerapian visual pada **Navigasi Cepat Master Data** di Dashboard Administrator (`resources/views/admin/dashboard.blade.php`), menyelaraskan tinggi (`h-11`), ukuran font, sudut membulat (`rounded-xl`), serta skema warna lembut yang harmonis pada 5 tombol master data (*Users*, *Units*, *Kode Rekening*, *Periode RBA*, *Init RBA*).

## User Review Required

> [!IMPORTANT]
> - **Transformasi Ke Kartu Dedicated Navigasi Cepat (`grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3`)**:
>   - Mengubah 5 tombol navigasi cepat yang sebelumnya bertumpuk atau tidak sejajar di header menjadi sebuah kontainer kartu dedicated yang rapi di bawah banner utama.
> - **Penyelarasan Ukuran & Dimensi Seragam**:
>   - Setiap tombol memiliki tinggi seragam (`h-11` = 44px), teks 1 baris berukuran `text-xs font-bold`, ikon SVG `w-4 h-4`, serta sudut membulat konsisten `rounded-xl`.
> - **Skema Warna Lembut & High-Contrast**:
>   - **Kelola User**: Soft Indigo (`bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white`).
>   - **Unit Kerja**: Soft Blue (`bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white`).
>   - **Kode Rekening**: Soft Purple (`bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white`).
>   - **Periode RBA**: Soft Teal (`bg-teal-50 text-teal-700 hover:bg-teal-600 hover:text-white`).
>   - **Init RBA (Header)**: Soft Amber (`bg-amber-50 text-amber-700 hover:bg-amber-600 hover:text-white`).

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Perubahan ini murni menyempurnakan tampilan estetika UI/UX Dashboard Administrator tanpa mengubah fungsionalitas rute master data.

## Proposed Changes

### View Layer

#### [MODIFY] [dashboard.blade.php (Admin Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/admin/dashboard.blade.php)
- Memindahkan dan merestrukturisasi bagian tombol navigasi cepat ke kontainer khusus di bawah Banner Welcome Admin:
  - Menggunakan Tailwind Grid `grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3`.
  - Menerapkan class seragam `flex items-center justify-center gap-2 h-11 px-3.5 font-bold text-xs rounded-xl border transition-all duration-200 shadow-sm`.
  - Memberikan warna lembut (*soft pastel palette with solid hover state*) untuk setiap kategori master data.

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memverifikasi seluruh 52 unit & feature test lulus 100%.

### Manual Verification
- Login sebagai Administrator.
- Akses Dashboard Administrator (`/admin/dashboard`).
- Amati kartu **Navigasi Cepat Master Data**.
- Pastikan kelima tombol (*Users*, *Units*, *Kode Rekening*, *Periode RBA*, dan *Init RBA*) memiliki tinggi, bentuk, dan ukuran yang 100% simetris, sejajar, serta warna yang elegan dan tidak berantakan di berbagai resolusi layar (desktop, tablet, smartphone).
