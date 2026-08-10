# Walkthrough - Filter Latar Belakang Operator Terpilih pada Cetak Supervisor

Perbaikan logika penayangan Latar Belakang pada cetak **Tampilan Supervisor** (Usulan Rincian Belanja dan RBA Final) telah selesai dilaksanakan. Ketika Supervisor memilih **Operator Spesifik** (1 atau beberapa operator) dengan opsi **Dengan Latar Belakang**, pratinjau dan hasil cetakan **hanya menampilkan teks latar belakang milik operator terpilih saja**, dan secara otomatis menyaring (membuang) teks latar belakang milik operator yang tidak dipilih.

---

## Perubahan yang Dilakukan

### Controller Layer
- **[MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)**:
  - Menambahkan method helper `getFilteredBackground()` untuk memproses pemfilteran baris/paragraf latar belakang berdasarkan operator terpilih (`$selectedOperatorNames`) dan membuang baris yang merujuk pada operator yang tidak terpilih (`$unselectedOperatorNames`).
  - Mengirimkan variabel `$filteredBackground` dan `$includeBackground` pada method `printPreview()` dan `printPreviewFinal()`.

### Report Template Layer
- **[MODIFY] [supervisor_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_print.blade.php)**:
  - Menggunakan data `$filteredBackground` untuk menampilkan teks latar belakang yang telah terfilter sesuai operator terpilih.
- **[MODIFY] [supervisor_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php)**:
  - Menggunakan data `$filteredBackground` untuk menampilkan teks latar belakang yang telah terfilter sesuai operator terpilih.

### Automated Tests Layer
- **[MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)**:
  - Menyesuaikan test case untuk memvalidasi bahwa saat mencetak 1 operator spesifik (Operator Alpha), latar belakang khusus Operator Alpha TAMPIL (`assertSee`), sedangkan latar belakang Operator Beta TIDAK TAMPIL (`assertDontSee`).

---

## Verification Results

### Automated Tests
- Menjalankan test suite PHPUnit:
  ```powershell
  php artisan test --filter=ReviewTest
  ```
  **Status**: PASS (5/5 tests passed clean, 36 assertions, exit code 0).

### Verification Summary
1. **Semua Operator + Dengan Latar Belakang**: Seluruh teks latar belakang unit/semua operator TAMPIL utuh.
2. **Pilih Operator Spesifik + Dengan Latar Belakang**: Hanya teks latar belakang milik operator terpilih yang TAMPIL, sedangkan latar belakang operator yang tidak dipilih terfilter dengan rapi.
