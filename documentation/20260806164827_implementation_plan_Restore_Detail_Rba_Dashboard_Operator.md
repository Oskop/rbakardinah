# Implementation Plan - Restorasi Tampilan Detail RBA pada Operator Dashboard

Mengembalikan tampilan bagian **Detail RBA** pada Dashboard Operator (`resources/views/operator/dashboard.blade.php`) ke versi awal yang komprehensif, sementara tetap mempertahankan fitur switcher **Mode Grafik Batang Historis** pada bagian **Daftar RBA Historis**.

## User Review Required

> [!IMPORTANT]
> - **Restorasi Bagian Detail RBA (Sisi Kanan / 8 Kolom)**:
>   - Mengembalikan 3 Kartu Ringkasan Metrik (*Total Usulan Global*, *Total Pagu Global*, *Jumlah Operator Berkontribusi*).
>   - Mengembalikan Struktur Tabel Breakdown per Operator dengan 6 kolom asli (*No*, *Nama Operator*, *Unit asal*, *Total Usulan Belanja*, *Pagu Terkait*, *Proporsi Usulan*) dan *tfoot* akumulasi total usulan & pagu.
>   - Mengembalikan tampilan **Chart Pie** proporsi usulan per operator dengan ukuran canvas `h-80 sm:h-96`.
> - **Tetap Mempertahankan Fitur Daftar RBA Historis (Sisi Kiri / 4 Kolom)**:
>   - Fitur toggle mode switcher (*Mode Daftar* & *Mode Grafik*) pada **Daftar RBA Historis** tetap aktif 100% tanpa ada perubahan.

## Open Questions

> [!NOTE]
> - Perubahan ini sepenuhnya menyelaraskan layout bagian Detail RBA sesuai keinginan user tanpa merusak fitur Grafik Batang Historis yang telah dibangun sebelumnya.

## Proposed Changes

### View Layer

#### [MODIFY] [dashboard.blade.php (Operator Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- Merestorasi bagian template `<template x-if="selectedRba">` (Detail Workspace RBA) ke struktur komplit semula:
  - Header & Controls (Tabel vs Chart Pie switcher).
  - 3 Cards Metrik (Total Usulan Global, Total Pagu Global, Jumlah Operator).
  - Tabel 6 Kolom + Footer Total Akumulasi.
  - Pie Chart Container.
- Memastikan bagian **Daftar RBA Historis** tetap memiliki `historyViewMode` toggle (Mode Daftar vs Mode Grafik).

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan test suite lulus 100%.

### Manual Verification
- Login sebagai Operator.
- Buka Operator Dashboard (`/operator/dashboard`).
- Pilih salah satu RBA dari Daftar RBA Historis.
- Pastikan tampilan **Detail RBA** di sisi kanan menyajikan 3 kartu metrik ringkasan, tabel 6 kolom dengan footer total akumulasi, serta toggle Chart Pie yang utuh dan rapi seperti semula.
- Uji toggle **Mode Grafik** pada bagian Daftar RBA Historis untuk memastikan keduanya berfungsi secara sempurna.
