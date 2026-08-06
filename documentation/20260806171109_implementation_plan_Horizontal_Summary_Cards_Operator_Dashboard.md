# Implementation Plan - Layout Horizontal Kartu Ringkasan Detail RBA Operator Dashboard

Mengubah susunan 3 informasi ringkasan (*Total Usulan Global*, *Total Pagu Global*, dan *Jumlah Operator Berkontribusi*) pada bagian **Detail RBA** di Dashboard Operator (`resources/views/operator/dashboard.blade.php`) menjadi layout horizontal yang hemat ruang serta responsif untuk smartphone dan tablet.

## User Review Required

> [!IMPORTANT]
> - **Transformasi Layout Horizontal**:
>   - Mengubah struktur internal kartu ringkasan dari *stacked vertical block* (label di atas, nominal di bawah) menjadi layout horizontal `flex items-center justify-between` (label di sebelah kiri, nominal/angka di sebelah kanan).
>   - Menggunakan padding ramping `px-4 py-3` untuk menghemat ruang vertikal layar secara signifikan.
> - **Kompatibilitas Responsif (Smartphone & Tablet)**:
>   - Menggunakan Tailwind Grid `grid grid-cols-1 sm:grid-cols-3 gap-3` sehingga pada layar tablet/desktop ketiga kartu berjajar horizontal 1 baris, sedangkan pada layar smartphone kartu menyesuaikan lebar layar secara proporsional.

## Open Questions

> [!NOTE]
> - Hanya berfokus pada 3 informasi ringkasan tersebut sesuai instruksi tanpa mengubah kode lainnya.

## Proposed Changes

### View Layer

#### [MODIFY] [dashboard.blade.php (Operator Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- Memperbarui 3 kartu ringkasan pada `<template x-if="selectedRba">`:
  - Mengganti kontainer `p-4` dengan `px-4 py-3 flex items-center justify-between gap-3 rounded-xl`.
  - Menempatkan label di sebelah kiri (`text-xs font-bold uppercase tracking-wider`) dan nominal/angka di sebelah kanan (`text-base font-black`).

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan seluruh test suite lulus 100%.

### Manual Verification
- Login sebagai Operator.
- Buka Operator Dashboard (`/operator/dashboard`).
- Amati 3 kartu ringkasan di atas tabel Detail RBA.
- Pastikan judul dan nominal ditampilkan secara horizontal menyamping (hemat ruang vertikal), serta responsif dan rapi saat ukuran layar disimulasikan sebagai tablet maupun smartphone.
