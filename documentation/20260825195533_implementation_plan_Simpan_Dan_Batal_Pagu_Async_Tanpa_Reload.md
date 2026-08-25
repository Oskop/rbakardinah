# Implementation Plan - Simpan & Batal Pagu Asynchronous Tanpa Reload Halaman

Menerapkan mekanisme penyimpanan (**Simpan**) dan pembatalan (**Batal**) penetapan pagu per nomor rekening secara **Asynchronous (AJAX / Non-blocking)** tanpa memuat ulang (*reload*) halaman, sehingga posisi scroll halaman admin tidak bergeser dan proses kerja input pagu menjadi jauh lebih cepat dan nyaman.

---

## Analisis Akar Masalah (Root Cause)

1. **Full Page Reload:** Saat ini form input pagu dan tombol batal pada setiap baris nomor rekening menggunakan form submission standar HTTP POST/DELETE yang me-refresh seluruh halaman (`redirect()->back()`).
2. **Scroll Jump:** Karena daftar nomor rekening berjumlah banyak, me-reload halaman menyebabkan posisi scroll kembali ke atas halaman. Administrator harus melakukan scroll manual ke bawah berulang kali untuk melanjutkan input baris berikutnya.

---

## User Review Required

> [!IMPORTANT]
> **Alur Interaksi Tanpa Reload (Asynchronous UX):**
> 1. **Klik Simpan:**
>    - Tombol menampilkan status loading (`⏳ Menyimpan...`).
>    - Request dikirim ke server via AJAX/JSON.
>    - **Jika Sukses:**
>      - Badge status di baris rekening langsung berubah menjadi `✅ Sudah Ditetapkan` (dengan timestamp).
>      - Tombol **Batal** otomatis muncul secara dinamis di samping form tanpa reload.
>      - Kartu ringkasan di atas (*Sudah Ditetapkan*, *Belum Ditetapkan*, *Total Pagu Ditetapkan*) otomatis ter-update nilainya secara real-time.
>      - Muncul notifikasi mengambang (*Toast Notification*) di sudut kanan atas.
>      - Posisi scroll halaman tetap diam di posisi baris yang sedang diedit.
>    - **Jika Gagal (Ada Usulan Belum Divalidasi Supervisor):**
>      - Muncul modal/toast peringatan interaktif yang merinci nama Operator, Unit, dan Supervisor yang wajib memvalidasi.
> 2. **Klik Batal:**
>    - Konfirmasi pembatalan.
>    - Badge status berubah kembali ke `⏳ Belum Ditetapkan`.
>    - Input nominal dikosongkan/direset, tombol **Batal** hilang.
>    - Kartu ringkasan statistik otomatis ter-update.

---

## Proposed Changes

### 1. Controller Admin Pagu (`app/Http/Controllers/Admin/RbaAccountPaguController.php`)

#### [MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)
- **Method `store()`**:
  - Deteksi `$request->wantsJson() || $request->ajax()`.
  - Jika terdapat usulan yang belum divalidasi supervisor, kembalikan HTTP 422 JSON dengan rincian pesan error dan daftar item.
  - Jika berhasil disimpan, hitung statistik terbaru (*ditetapkanCount*, *belumDitetapkanCount*, *totalPaguNominal*) dan kembalikan JSON 200 dengan payload:
    ```json
    {
      "success": true,
      "message": "Pagu untuk rekening ... berhasil ditetapkan sebesar Rp ...",
      "data": {
        "account_code_id": 1,
        "nominal_pagu": 5000000,
        "nominal_formatted": "5.000.000",
        "updated_at_formatted": "25/08/2026 19:55",
        "stats": {
          "ditetapkan_count": 12,
          "belum_ditetapkan_count": 8,
          "total_pagu_nominal": 150000000,
          "total_pagu_formatted": "150.000.000"
        }
      }
    }
    ```
  - Tetap pertahankan fallback HTTP redirect untuk backward compatibility.
- **Method `destroy()`**:
  - Deteksi request JSON dan kembalikan JSON 200 berisi pesan sukses dan data statistik terbaru.

---

### 2. View Penetapan Pagu (`resources/views/admin/headers/pagu.blade.php`)

#### [MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- Menggunakan state Alpine.js `paguManager` untuk:
  - Menyimpan reactive state per baris rekening (`isEstablished`, `nominal`, `loading`, `updatedAt`).
  - Mengirim request `fetch`/`axios` ke endpoint `admin.headers.pagu.store` dan `admin.headers.pagu.destroy` dengan header `Accept: application/json`.
  - Memperbarui reaktifitas kartu ringkasan di atas secara real-time.
  - Menampilkan **Floating Toast Notification** (Hijau untuk sukses, Merah/Kuning untuk peringatan).
  - Menyediakan modal popup penjelasan jika ada usulan yang belum divalidasi supervisor saat mencoba menyimpan pagu.

---

### 3. Pengujian Otomatis (`tests/Feature/Admin/PaguTest.php`)

#### [MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)
- Menambahkan pengujian:
  - `test_admin_can_save_pagu_via_ajax_without_page_reload`
  - `test_admin_can_delete_pagu_via_ajax_without_page_reload`
  - `test_ajax_save_pagu_fails_with_422_when_unvalidated_details_exist`

---

## Verification Plan

### Automated Tests
- Jalankan test suite Pagu:
  `php artisan test --filter=PaguTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke halaman **Penetapan Pagu** pada RBA Header yang aktif (`/admin/headers/{header}/pagu`).
3. Scroll ke baris rekening di tengah atau bawah halaman.
4. Input nominal pagu pada baris tersebut dan klik tombol **💾 Simpan**:
   - Verifikasi halaman **tidak reload**.
   - Posisi scroll **tidak berpindah**.
   - Badge langsung berubah menjadi "Sudah Ditetapkan" dan tombol "Batal" muncul.
   - Kartu statistik di atas otomatis ter-update.
   - Toast notifikasi sukses muncul di pojok kanan atas.
5. Klik tombol **Batal** pada rekening tersebut:
   - Verifikasi penetapan pagu dibatalkan tanpa reload halaman.
   - Badge berubah menjadi "Belum Ditetapkan", tombol "Batal" hilang.
   - Kartu statistik di atas otomatis berkurang.
6. Coba simpan pada rekening yang memiliki usulan belum divalidasi oleh supervisor:
   - Verifikasi muncul modal/toast peringatan dengan info detail Operator dan Supervisor yang belum memvalidasi tanpa me-reload halaman.
