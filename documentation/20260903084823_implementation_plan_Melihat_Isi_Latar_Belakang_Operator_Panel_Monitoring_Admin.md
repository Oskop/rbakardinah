# Implementation Plan - Melihat Isi Latar Belakang Operator pada Panel Monitoring RBA Administrator

Mengembangkan kolom **Status Latar Belakang** pada Panel Monitoring Penginputan Unit di halaman Administrator (`admin.headers.show`) agar Administrator tidak hanya melihat status keterisian (`Sudah Diisi` / `Belum Diisi`), tetapi juga dapat **mengklik status tersebut untuk langsung melihat teks lengkap latar belakang yang diinput oleh operator** dalam modal dialog interaktif.

---

## User Review Required

> [!IMPORTANT]
> **Interaktivitas yang Diterapkan pada Kolom Status Latar Belakang:**
> 1. **Jika Belum Diisi:**
>    - Menampilkan badge status: `⚠️ Belum Diisi` (warna amber).
> 2. **Jika Sudah Diisi:**
>    - Menampilkan tombol interaktif: `✓ Sudah Diisi 👁️` (warna emerald/hijau dengan ikon preview).
>    - Dilengkapi efek hover halus dan tooltip *"Klik untuk melihat isi latar belakang"*.
> 3. **Modal Dialog Interaktif (Alpine.js):**
>    - Saat tombol diklik, modal muncul di tengah layar menampilkan:
>      - **Header:** Nama Unit Kerja, Nama Operator, dan NIP.
>      - **Konten Latar Belakang:** Paragraf teks latar belakang utuh dengan pemformatan baris yang rapi (`whitespace-pre-line`).
>      - **Footer:** Tombol **"Tutup"**.
>    - Dapat ditutup dengan menekan tombol **Tutup**, mengklik area luar modal (*backdrop click*), atau menekan tombol **Escape** keyboard.
> 4. **Jaminan Keutuhan Kode:**
>    - Seluruh tabel pohon RBA di bawahnya tetap **100% UTUH** dan tidak tersentuh perubahan apa pun.

---

## Proposed Changes

### 1. Controller Layer

#### [MODIFY] [RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php)
- Memastikan atribut `background_text` pada array `$operatorMetrics`:
  ```php
  'background_text' => $hasOwnBg 
      ? $operatorBackgrounds->get($operator->id)->background 
      : ($hasLegacyBg ? $submission->background : null),
  ```
  Hal ini menjamin data teks latar belakang terambil secara sempurna baik dari tabel per-operator (`rba_submission_operator_backgrounds`) maupun data latar belakang submission legacy.

---

### 2. View Layer

#### [MODIFY] [show.blade.php (Administrator)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php)
- Pada container Panel Monitoring Penginputan Unit:
  - Menambahkan state Alpine.js:
    - `bgModalOpen: false`
    - `modalOperatorName: ''`
    - `modalOperatorNip: ''`
    - `modalUnitName: ''`
    - `modalBackgroundText: ''`
    - Method `showBackground(opName, opNip, unitName, text)`
  - Pada kolom **Status Latar Belakang** di baris operator:
    - Mengganti teks badge statis menjadi tombol interaktif berpenampilan badge saat `has_background` bernilai `true`.
    - Memanggil `showBackground(...)` dengan data yang aman (`json_encode`).
  - Menambahkan komponen **Modal Dialog Latar Belakang Operator** di dalam container Panel Monitoring:
    - Backdrop semi-transparan dengan transisi halus.
    - Card modal dengan desain rapi, tipografi profesional, dan tombol tutup.
- **Mempertahankan:** Seluruh kode tabel RBA di bawahnya tetap tidak diubah sama sekali.

---

### 3. Automated Tests Layer

#### [MODIFY] [AdminDashboardTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/AdminDashboardTest.php)
- Menambahkan asersi pengujian pada `test_admin_can_view_unit_monitoring_with_supervisor_and_operator_progress`:
  - Memverifikasi tombol/badge interaktif untuk melihat latar belakang ada di halaman.
  - Memverifikasi isi teks latar belakang yang disiapkan untuk operator ada di dalam response HTML.

---

## Verification Plan

### Automated Tests
1. Menjalankan test suite Admin Dashboard:
   ```powershell
   php artisan test --filter=AdminDashboardTest
   ```
2. Menjalankan seluruh test suite aplikasi untuk memastikan zero regression:
   ```powershell
   php artisan test
   ```

### Manual Verification
1. Login sebagai **Administrator**.
2. Buka salah satu RBA periode aktif (misal `/admin/headers/2`).
3. Pada **Panel Monitoring Penginputan Unit**, klik salah satu unit kerja untuk membuka daftar operator.
4. Pada kolom **Status Latar Belakang:**
   - Operator yang belum mengisi menampilkan badge `⚠️ Belum Diisi`.
   - Operator yang sudah mengisi menampilkan tombol `✓ Sudah Diisi 👁️`.
5. Klik tombol `✓ Sudah Diisi 👁️`:
   - Modal dialog terbuka di tengah layar.
   - Teks latar belakang operator tampil lengkap dengan identitas operator dan unit kerja.
   - Klik tombol **Tutup** atau tombol **Escape** keyboard untuk menutup modal.
