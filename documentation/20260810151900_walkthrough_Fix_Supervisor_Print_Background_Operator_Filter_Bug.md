# Walkthrough - Fix Display Latar Belakang pada Cetak Supervisor saat Filter Operator Spesifik

Perbaikan bug pada fitur cetak **Tampilan Supervisor** (Usulan Rincian Belanja dan RBA Final) telah selesai dilakukan. Teks Latar Belakang Sub-Unit (`$submission->background`) kini secara otomatis disembunyikan jika Supervisor memilih untuk mencetak **Operator Spesifik** (bukan seluruh Operator bawahan), sehingga cetakan murni khusus usulan operator tersebut dan tidak tercampur latar belakang unit / operator lain.

---

## Perubahan yang Dilakukan

### Controller Layer
- **[MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)**:
  - Memperbarui evaluasi variabel `$includeBackground` pada method `printPreview()` dan `printPreviewFinal()`:
    ```php
    $isAllOperators = empty($selectedOperatorIds) || count($selectedOperatorIds) === $allOperators->count();
    $includeBackground = ($request->get('include_background', '1') == '1') && $isAllOperators;
    ```
  - Teks latar belakang hanya di-render apabila Supervisor memilih cetak **Semua Operator** dan mencentang **Dengan Latar Belakang**.

### Automated Tests Layer
- **[MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)**:
  - Menambahkan assertion `assertSee` untuk teks latar belakang saat cetak Semua Operator.
  - Menambahkan assertion `assertDontSee` untuk teks latar belakang saat cetak 1 Operator Spesifik.

---

## Verification Results

### Automated Tests
- Menjalankan test suite PHPUnit:
  ```powershell
  php artisan test --filter=ReviewTest
  ```
  **Status**: PASS (5/5 tests passed clean, 30 assertions, exit code 0).

### Verification Summary
1. **Semua Operator + Dengan Latar Belakang**: Teks Latar Belakang TAMPIL pada pratinjau cetak.
2. **Pilih Operator Spesifik + Dengan Latar Belakang**: Teks Latar Belakang TIDAK TAMPIL (disembunyikan secara tepat sasaran).
