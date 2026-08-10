# Implementation Plan - Filter Latar Belakang Operator Terpilih pada Cetak Supervisor

Memperbarui logika penayangan Latar Belakang pada fitur cetak Supervisor (Usulan Rincian Belanja dan RBA Final) agar saat Supervisor memilih **Operator Spesifik** (1 atau beberapa operator), laporan cetak hanya menampilkan data **Latar Belakang milik/berhubungan dengan Operator terpilih saja**, dan secara otomatis menyaring (menghapus) teks latar belakang milik operator yang tidak dipilih.

---

## User Review Required

> [!IMPORTANT]
> **Detail Logika Penyaringan Latar Belakang Operator**:
> 1. **Pencetakan Semua Operator**:
>    - Jika Supervisor memilih **Semua Operator** dan mencentang **Dengan Latar Belakang**, maka seluruh isi teks Latar Belakang Sub-Unit akan ditampilkan secara utuh.
> 2. **Pencetakan Operator Spesifik**:
>    - Jika Supervisor memilih **Pilih Operator Spesifik** (1 atau beberapa operator):
>      - Sistem akan mengecek ketersediaan rincian usulan belanja milik operator terpilih.
>      - Sistem menyaring baris/paragraf latar belakang sehingga baris yang secara spesifik merujuk pada nama operator yang **tidak dipilih** akan difilter/dibuang.
>      - Hanya baris/paragraf latar belakang milik operator terpilih (atau latar belakang umum usulan unit terpilih) yang akan ditampilkan pada pratinjau dan cetakan laporan.
>      - Jika operator terpilih tidak memiliki usulan rincian belanja pada submission tersebut, bagian latar belakang disembunyikan agar cetakan murni dan tidak mencantumkan data milik operator lain.

---

## Proposed Changes

### Controller Layer

#### [MODIFY] [ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Pada method `printPreview()` dan `printPreviewFinal()`:
  - Mengimplementasikan logika pemfilteran teks latar belakang (`$filteredBackground`):
    - Mengecek apakah `$isAllOperators` (true jika mencetak semua operator).
    - Jika memfilter operator spesifik, sistem memilah baris latar belakang berdasarkan nama operator terpilih (`$selectedOperatorNames`) dan membuang baris yang merujuk pada operator tidak terpilih (`$unselectedOperatorNames`).
    - Mengecek keberadaan usulan belanja operator terpilih (`$hasDetailsForSelected`).
  - Mengirim variabel `$filteredBackground` ke view `supervisor_rba_print` dan `supervisor_rba_final_print`.

---

### Report Template Layer

#### [MODIFY] [supervisor_rba_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_print.blade.php)
- Menggunakan `$filteredBackground` untuk menampilkan teks latar belakang yang telah terfilter sesuai operator terpilih.

#### [MODIFY] [supervisor_rba_final_print.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/supervisor_rba_final_print.blade.php)
- Menggunakan `$filteredBackground` untuk menampilkan teks latar belakang yang telah terfilter sesuai operator terpilih.

---

### Automated Tests Layer

#### [MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Menyesuaikan unit test case:
  - Memverifikasi saat `include_background=1` dan `operator_ids` diisi ID Operator Alpha, maka latar belakang khas Operator Alpha TAMPIL, sedangkan latar belakang khas Operator Beta TIDAK TAMPIL.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite PHPUnit Supervisor:
  ```powershell
  php artisan test --filter=ReviewTest
  ```

### Manual Verification
1. Login sebagai Supervisor.
2. Buka halaman Workboard Submisi (`/supervisor/submissions/{id}`).
3. Klik **"🖨️ Cetak Rincian Usulan / RBA Final"**:
   - Pilih **Dengan Latar Belakang** + **Pilih Operator Alpha**.
   - Verifikasi pratinjau cetak hanya menampilkan bagian latar belakang milik Operator Alpha, dan latar belakang Operator Beta disaring (tidak muncul).
