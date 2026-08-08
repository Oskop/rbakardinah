# Implementation Plan - Perbaikan Error Undefined Variable $totalUsulan pada RbaHeaderController

Perbaikan `ErrorException` saat mengakses halaman RBA Header Administrator (`/admin/headers/{id}`).

---

## Analysis & Root Cause

Berdasarkan analisis log error pada `app/Http/Controllers/RbaHeaderController.php:141`:
- Pada method `show()`, fungsi `view('admin.headers.show', compact('header', 'reportData', 'totalUsulan', 'totalPagu', 'units', 'allOperators'))` memanggil variabel `$totalUsulan` dan `$totalPagu`.
- Namun, deklarasi perhitungan akumulasi `$totalUsulan = $details->sum('nominal_request');` dan `$totalPagu = $pagus->sum('nominal_pagu');` sebelumnya terhapus saat pembaruan variabel `$units` dan `$allOperators`.

---

## User Review Required

> [!IMPORTANT]
> **Perbaikan Kode**:
> Menambahkan kembali perhitungan grand total usulan & pagu sebelum me-render tampilan `admin.headers.show`:
> ```php
> $totalUsulan = $details->sum('nominal_request');
> $totalPagu = $pagus->sum('nominal_pagu');
> ```

---

## Proposed Changes

### Controller Layer

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Restorasi variabel `$totalUsulan` dan `$totalPagu` pada method `show()` sebelum pengembalian `view()`.

---

## Verification Plan

### Automated Tests
- Menjalankan pengujian fitur Admin & RBA Header via Artisan Test:
  ```powershell
  php artisan test --filter=Admin
  ```

### Manual Verification
1. Login sebagai Administrator.
2. Buka halaman Detail RBA Header (`/admin/headers/{id}`).
3. Pastikan halaman terbuka dengan normal (Status 200 OK) tanpa error, serta Total Usulan Global & Total Pagu Global tampil dengan benar di kartu ringkasan header.
