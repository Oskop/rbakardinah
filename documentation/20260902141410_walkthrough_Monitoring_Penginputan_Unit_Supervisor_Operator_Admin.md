# Walkthrough - Monitoring Penginputan Unit (Level Supervisor & Operator) pada Halaman RBA Administrator

Fitur **Monitoring Penginputan Unit (Level Supervisor & Operator)** pada halaman detail RBA periode tertentu di sisi Administrator (`admin.headers.show`) telah selesai diimplementasikan dengan sangat hati-hati dan teruji 100%. 

Sesuai instruksi khusus, perubahan **hanya difokuskan pada bagian Status Unit** tanpa mengubah struktur, kolom, kalkulasi, maupun logika tabel RBA di bawahnya.

---

## Ringkasan Fitur yang Diterapkan

### 1. Backend Controller (`RbaHeaderController@show`)
- Mengambil data melalui eager loading teroptimasi (*single query batch*):
  - Unit dan seluruh pengguna aktif (`users` dengan role Supervisor dan Operator).
  - Data latar belakang operator (`operatorBackgrounds`).
  - Dokumen pokok (`documents.latestVersion`).
  - Rincian belanja beserta lampiran PDF (`details.attachments`).
- Menyusun struktur array metrik `$unitMonitoring` untuk setiap unit kerja:
  - **Supervisor Level:** Daftar supervisor aktif, total akumulasi nominal usulan belanja unit, dan progres validasi review (`validated_details` / `total_details`).
  - **Operator Level:**
    - Status pengisian Latar Belakang (`has_background`: `✓ Sudah Diisi` vs `⚠️ Belum Diisi`).
    - Nominal usulan operator (`nominal_usulan` dan `item_count`).
    - Status kelengkapan dokumen pokok KAK, RAK, dan RTP (`has_kak`, `has_rak`, `has_rtp`, rasio `X/3 Terunggah`).
    - Rasio usulan belanja yang berlampiran file PDF (`details_with_pdf_count` dari `total_details_count`).
    - Status kelengkapan akumulatif (`is_all_complete`).

---

### 2. Tampilan Administrator (`admin/headers/show.blade.php`)
- **Fokus Khusus Status Unit:**
  - Hanya menggantikan deretan badge status unit sederhana yang lama (sebelum tabel) dengan **Panel Monitoring Penginputan Unit dan Progres RBA**.
  - **Tabel Pohon RBA di bawahnya 100% UTUH** (tidak ada satupun baris atau kolom kode rekening/uraian/usulan/pagu/supervisor/operator yang diubah atau terganggu).
- **Fitur Panel Monitoring:**
  - **Header & Ringkasan Cepat:** Menampilkan judul, deskripsi, pill badge status ringkas tiap unit (`Draft`, `Pending Supervisor`, `Validated`).
  - **Toolbar Interaktif:** Pencarian cepat nama unit/supervisor/operator, tombol **"Buka Semua"** (`toggleAllUnits(true)`), tombol **"Tutup Semua"** (`toggleAllUnits(false)`), dan tombol ciutkan panel (`panelOpen`).
  - **Kartu Tiap Unit (Level Unit & Supervisor):**
    - Nama Unit dan badge status submission.
    - Nama Supervisor aktif di unit tersebut (atau `Belum ditugaskan`).
    - Total Usulan Unit (Rp).
    - Progres validasi usulan supervisor (contoh: `5/5 Usulan Divalidasi` atau `2 Ditolak`).
    - Tombol expand drawer operator (`X Operator ▼`).
  - **Sub-Tabel Rincian Operator (Level Operator):**
    - Nama Operator & NIP dengan avatar inisial.
    - Badge Status Latar Belakang (`✓ Sudah Diisi` / `⚠️ Belum Diisi`).
    - Nominal Usulan Belanja (`Rp ...` dan jumlah usulan).
    - Status Dokumen KAK, RAK, RTP (Chip indikator hijau/abu-abu).
    - Status PDF Lampiran Usulan (`X/Y PDF`).
    - Status Kelengkapan Akhir (`✓ Lengkap` / `⚠️ Belum Lengkap`).

---

## File yang Dimodifikasi

- **[MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)**:
  - Penambahan eager loading dan kalkulasi metrik `$unitMonitoring`.
- **[MODIFY] [show.blade.php (Administrator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)**:
  - Penggantian bagian status unit lama dengan Panel Monitoring Penginputan Unit (Supervisor & Operator).
  - Menjaga keutuhan tabel RBA di bawahnya tanpa perubahan.
- **[MODIFY] [AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)**:
  - Penambahan unit test `test_admin_can_view_unit_monitoring_with_supervisor_and_operator_progress` untuk memvalidasi akurasi metrik monitoring supervisor dan operator.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests PASS
Seluruh **113 feature & unit tests** pada aplikasi dijalankan dan **PASSED 100% (113 passed, 0 failed, 477 assertions)**:

```text
PASS  Tests\Feature\Admin\AdminDashboardTest
✓ admin can access dashboard and see rba list                                                                  1.11s  
✓ admin can preview print report with unit and operator filters                                                0.08s  
✓ admin can preview rba final print report with pagu and unit operator filters                                 0.05s  
✓ admin can view unit monitoring with supervisor and operator progress                                         0.38s  

PASS  Tests\Feature\Supervisor\ReviewTest (11 passed, 70 assertions)
PASS  Tests\Feature\Operator\RbaDetailTest (13 passed, 55 assertions)
PASS  Tests\Feature\Admin\UserManagementTest (2 passed, 27 assertions)
PASS  Tests\Feature\Supervisor\UserManagementTest (2 passed, 12 assertions)

Tests:    113 passed (477 assertions)
Duration: 38.41s
```

### 2. Frontend Assets Build (Bun) PASS
Asset CSS dan JavaScript berhasil dikompilasi dengan Vite melalui `bun run build`:
- `public/build/assets/app-BOtvSNCZ.css` (83.04 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.19s**
