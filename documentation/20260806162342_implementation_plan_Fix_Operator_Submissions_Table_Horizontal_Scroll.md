# Implementation Plan - Fix Operator Submissions Table Horizontal Scroll & Responsive Layout

Mengatasi masalah tampilan kolom tabel "Rincian Biaya" yang terpotong/overflow pada halaman Usulan Belanja Operator dengan menambahkan pembungkus (*wrapper*) responsif `overflow-x-auto` dan penyesuaian lebar minimum tabel agar seluruh 12 kolom (termasuk kolom **Aksi**) dapat di-scroll secara horizontal dengan sempurna di seluruh resolusi layar.

## User Review Required

> [!IMPORTANT]
> - **Permasalahan**: Tabel Rincian Biaya memiliki banyak kolom (12 kolom), sehingga pada resolusi layar standar/laptop/mobile, kolom paling kanan (**Aksi**) serta background card terpotong karena tidak adanya pembungkus scroll horizontal (`overflow-x-auto`).
> - **Solusi**:
>   1. Membungkus tabel dengan container `<div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">`.
>   2. Menentukan lebar minimum tabel (`min-w-[1200px] w-full`) agar isi sel tidak saling berhimpitan dan kolom **Aksi** tetap utuh serta nyaman digunakan.

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Perubahan dilakukan pada lapisan tampilan (Blade layout) tanpa mengubah struktur logika database atau controller.

## Proposed Changes

### View Layer

#### [MODIFY] [show.blade.php (Operator Submissions)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Membungkus tabel `#details-table` dengan pembungkus responsif `overflow-x-auto`.
- Menyesuaikan atribut class tabel menjadi `min-w-[1200px] w-full divide-y divide-gray-200` agar memberikan ruang lebar yang cukup untuk seluruh 12 kolom.

#### [MODIFY] [show.blade.php (Supervisor Submissions)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Menambahkan pembungkus `overflow-x-auto` pada tabel peninjauan supervisor untuk konsistensi pengalaman pengguna.

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan seluruh test suite lulus 100%.

### Manual Verification
- Login sebagai Operator.
- Masuk ke halaman Workboard / Rincian Belanja (`operator.submissions.show`).
- Uji pada berbagai ukuran resolusi layar (desktop, laptop, tablet, mobile).
- Pastikan seluruh tabel rincian belanja dapat di-scroll secara horizontal, dan kolom **Aksi** di ujung kanan beserta background card terlihat utuh 100%.
