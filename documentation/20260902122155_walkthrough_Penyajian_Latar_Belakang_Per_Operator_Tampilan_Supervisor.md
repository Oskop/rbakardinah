# Walkthrough - Penyajian Latar Belakang RBA per Operator pada Tampilan Supervisor

Fitur penyajian data **Latar Belakang RBA per Operator** pada tampilan Supervisor (`supervisor.submissions.show`) telah selesai diimplementasikan secara terstruktur, elegan, dan teruji 100%. Sistem memastikan hanya operator yang berstatus **aktif** (`is_active = true`) yang ditampilkan, serta latar belakang setiap operator tersimpan secara independen tanpa saling menimpa.

---

## Ringkasan Fitur yang Diterapkan

### 1. Model & Skema Database Independen per Operator
- Dibuat tabel `rba_submission_operator_backgrounds` dengan relasi ke `rba_submissions` dan `users`:
  - Kolom: `id`, `rba_submission_id`, `user_id`, `background`, `timestamps`.
  - Constraint unik: `['rba_submission_id', 'user_id']`.
- Model baru `RbaSubmissionOperatorBackground` terhubung dengan `RbaSubmission` melalui relasi `operatorBackgrounds()`.
- Setiap operator di unit kerja mengelola teks justifikasi usulan RBA miliknya sendiri.

---

### 2. Tampilan Review Supervisor (`supervisor.submissions.show`)
- **Penyaringan Operator Aktif:**
  - Supervisor hanya melihat daftar operator yang berstatus **aktif** (`is_active = true`). Operator yang dinonaktifkan (`is_active = false`) secara otomatis disaring keluar dari tampilan review dan opsi modal cetak.
- **Komponen Kartu Latar Belakang per Operator:**
  - Setiap operator aktif disajikan dalam kartu yang memuat:
    - Avatar inisial, **Nama Operator**, NIP, dan email.
    - **Badge Status:**
      - `✓ Latar Belakang Terisi` (hijau) bila operator sudah menyimpan latar belakang.
      - `⚠️ Belum Mengisi` (amber) bila operator belum mengisi data latar belakang usulan.
    - Kotak konten teks latar belakang dengan format rapi (*whitespace-pre-wrap*) serta penanda waktu pembaruan terakhir.
- **Fallback Data Historis:**
  - Bila ada data usulan lama (sebelum migrasi per operator) yang tersimpan di kolom `rba_submissions.background`, sistem menampilkan blok informasi cadangan sehingga tidak ada arsip historis yang hilang.

---

### 3. Tampilan Operator (`operator.submissions.show`)
- Form berlabel spesifik: **"Latar Belakang RBA Anda (Nama Operator)"**.
- Dilengkapi indikator badge hijau jika latar belakang milik operator saat ini telah tersimpan.
- Dilengkapi accordion referensi: **"Lihat Latar Belakang Rekan Operator Lain di Unit Ini"**, memungkinkan operator saling berkoordinasi secara transparan.
- Saat disimpan, sistem mengompilasi latar belakang seluruh operator aktif ke `rba_submissions.background` agar laporan cetak (PDF/web print) tetap kompatibel tanpa penyesuaian rumit.

---

## File yang Dimodifikasi / Dibuat

- **[NEW] [Migration Table](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_02_052225_create_rba_submission_operator_backgrounds_table.php)**: Skema tabel `rba_submission_operator_backgrounds`.
- **[NEW] [RbaSubmissionOperatorBackground.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmissionOperatorBackground.php)**: Model Eloquent relasi latar belakang operator.
- **[MODIFY] [RbaSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaSubmission.php)**: Relasi `operatorBackgrounds()`.
- **[MODIFY] [ReviewController.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)**: Filter `where('is_active', true)` pada operator dan pemuatan data latar belakang per operator.
- **[MODIFY] [SubmissionController.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php)**: Dukungan penyimpanan per operator dan kompilasi latar belakang.
- **[MODIFY] [DetailController.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**: Pengecekan latar belakang operator saat menambah rincian belanja.
- **[MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)**: Komponen kartu latar belakang per operator aktif.
- **[MODIFY] [show.blade.php (Operator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)**: Form latar belakang spesifik operator dan accordion rekan kerja.
- **[MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)**: Pengujian pemilahan kartu latar belakang per operator aktif dan validasi operator nonaktif tidak tampil.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests PASS
Seluruh **111 feature & unit tests** pada aplikasi dijalankan dan **PASSED 100% (111 passed, 0 failed, 455 assertions)**:

```text
PASS  Tests\Feature\Supervisor\ReviewTest
✓ supervisor can view their unit submissions                                                                   1.10s  
✓ supervisor can validate submission                                                                           0.04s  
✓ supervisor can see previous period pagu in awal column                                                       0.07s  
✓ supervisor can preview print report with operator filters                                                    0.05s  
✓ supervisor can preview rba final print report with pagu and operator filters                                 0.05s  
✓ supervisor cannot see draft unsubmitted details                                                              0.05s  
✓ detail disappears from supervisor when rejected detail is edited and reappears when resubmitted              0.09s  
✓ supervisor cannot validate or reject unsubmitted detail                                                      0.04s  
✓ supervisor can see distinct background cards for each active operator                                        0.05s  
✓ operator can save and update their own background without affecting other operators                          0.04s  

PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\UserManagementTest (2 passed, 12 assertions)

Tests:    111 passed (455 assertions)
Duration: 37.86s
```

### 2. Frontend Assets Build (Bun) PASS
Asset CSS dan JavaScript berhasil dikompilasi dengan `bun run build`:
- `public/build/assets/app-CtdOVeH0.css` (81.76 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.20s**
