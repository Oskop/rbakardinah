# Implementation Plan - Fix Description Column Length in RbaDetails Table

Mengatasi error truncation database (`SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'description'`) saat operator menyimpan rincian usulan belanja dengan spesifikasi/uraian panjang.

## User Review Required

> [!IMPORTANT]
> - **Penyebab Error**: Kolom `description` pada tabel `rba_details` saat ini bertipe `VARCHAR(255)`. Ketika operator menginput rincian belanja dengan spesifikasi teknis (seperti spesifikasi server, alat medis, dll.) yang panjangnya melebihi 255 karakter, database MySQL menolak data tersebut.
> - **Solusi**: Mengubah tipe data kolom `description` di tabel `rba_details` menjadi `TEXT` melalui migrasi database Laravel.

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Tipe data `TEXT` pada MySQL mendukung hingga 65.535 karakter, sangat aman untuk deskripsi rincian belanja dan spesifikasi teknis barang.

## Proposed Changes

### Database Migration

#### [NEW] [2026_08_06_155300_change_description_column_type_in_rba_details_table.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_08_06_155300_change_description_column_type_in_rba_details_table.php)
- Membuat migrasi baru untuk mengubah tipe kolom `description` pada tabel `rba_details` dari `VARCHAR(255)` menjadi `TEXT`.

---

## Verification Plan

### Automated Tests
- Menambahkan pengujian `test_operator_can_create_rba_detail_with_long_description` pada `RbaDetailFeaturesTest.php` yang memverifikasi penyimpanan rincian usulan dengan deskripsi >300 karakter.
- Menjalankan `php artisan migrate` dan `php artisan test`.

### Manual Verification
- Login sebagai Operator.
- Buka form Tambah Rincian Belanja.
- Input deskripsi spesifikasi panjang (contoh: spesifikasi server >300 karakter).
- Klik Simpan, pastikan data berhasil disimpan tanpa error SQL.
