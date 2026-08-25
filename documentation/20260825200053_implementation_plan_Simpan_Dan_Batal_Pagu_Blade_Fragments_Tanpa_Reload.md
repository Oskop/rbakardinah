# Implementation Plan - Mekanisme Simpan & Batal Pagu Tanpa Reload (Analisis & Rekomendasi Arsitektur Laravel)

Menjawab pertanyaan dan merevisi rencana teknis dengan mengevaluasi **seluruh alternatif metode di ekosistem Laravel** yang paling mumpuni, hemat resource, minim/tanpa dependensi tambahan, dan paling selaras dengan arsitektur Laravel.

---

## Analisis Opsi Arsitektur di Ekosistem Laravel

Berikut adalah perbandingan 4 pendekatan yang memungkinkan di Laravel untuk mengatasi masalah scroll jump tanpa reload:

| Metode | Deskripsi | Dependensi Tambahan | Keunggulan | Kekurangan |
| :--- | :--- | :--- | :--- | :--- |
| **1. Laravel Blade Fragment + Native DOM Swap (Recommended ⭐)** | Fitur resmi bawaan Laravel (`@fragment` / `return view()->fragment()`). Server merender ulang potongan Blade baris & cards yang berubah, lalu disisipkan ke DOM secara instan. | **0 (Nol)** — Bawaan Laravel & Browser | • 100% logika tampilan tetap di Blade Laravel.<br>• Tidak perlu duplikasi formatting angka/badge di JS.<br>• Sangat hemat resource (server hanya kirim beberapa byte HTML potongan).<br>• Scroll tetap 100% di posisi semula. | Memerlukan script handler swap ~15 baris kode native. |
| **2. Alpine.js + JSON Response** | Form ditangani oleh Alpine.js (yang sudah terpasang bawaan di project ini via Laravel Breeze) dengan mengirim `fetch()` JSON ke Controller. | **0 (Nol)** — Sudah ada di project | • Sangat cepat dan reaktif di sisi browser.<br>• Tidak perlu install package baru. | Format badge / HTML ditangani di client-side (JSON parsing). |
| **3. Laravel Livewire** | Framework full-stack reaktif untuk Blade Laravel. | **Tinggi** (`composer require livewire/livewire`) | • Sangat deklaratif di level PHP/Blade. | • Menambah library & bundle size baru.<br>• Overhead server hydration/dehydration.<br>• Terlalu berlebihan (overkill) hanya untuk 1 halaman form. |
| **4. URL Hash Anchor Redirect (`#rekening-{id}`)** | Form POST biasa dengan redirect ke anchor ID `#rekening-123`. | **0 (Nol)** | • Tanpa Javascript sama sekali. | • Halaman **tetap reload penuh** (DOM reset, assets reload), hanya otomatis scroll kembali ke anchor. Masih terasa berkedip (*flicker*). |

---

## Solusi Terbaik yang Direkomendasikan: Opsi 1 (Laravel Blade Fragments)

> [!TIP]
> **Mengapa Opsi 1 (Laravel Blade Fragments) adalah yang Terbaik?**
> 1. **Paling Alami dengan Laravel (*Idiomatic Laravel*):** Laravel 9, 10, 11, dan 12 memiliki fitur bawaan resmi `@fragment` yang dirancang khusus untuk kasus seperti ini (merender potongan HTML tanpa reload seluruh layout).
> 2. **Zero Extra Dependencies:** Tidak memerlukan library atau plugin baru (baik composer maupun npm).
> 3. **Single Source of Truth Tampilan:** Desain badge, format rupiah, dan pesan error tetap ditulis 100% menggunakan sintaks Blade Laravel yang sudah ada, tanpa perlu menulis ulang template HTML di JavaScript.
> 4. **Ultra-Hemat Resource & Zero Scroll Jump:** Hanya mengirimkan potongan baris `<tr>` yang berubah (~300 bytes), dieksekusi dalam hitungan milidetik, dan posisi scroll browser tetap diam di baris yang sedang dikerjakan.

---

## User Review Required

> [!IMPORTANT]
> **Alur Kerja Blade Fragments pada Penetapan Pagu:**
> 1. **Pada View (`admin/headers/pagu.blade.php`):**
>    - Bagian Summary Cards dibungkus dengan `@fragment('summary-cards') ... @endfragment`.
>    - Setiap baris rekening dibungkus dengan `@fragment('account-row') ... @endfragment` dengan atribut `id="row-{{ $code->id }}"`.
> 2. **Saat Admin klik Simpan atau Batal:**
>    - Request dikirim secara asynchronous via `fetch()` native.
>    - Server memproses data dan merender kembali fragment baris rekening + summary stats.
>    - Baris `<tr>` yang bersangkutan dan Summary Cards langsung diperbarui secara mulus (*smooth HTML replacement*).
>    - Notifikasi Toast (Hijau/Merah) muncul di sudut layar tanpa mengganggu layar kerja.

---

## Proposed Changes

### 1. Controller Admin Pagu (`app/Http/Controllers/Admin/RbaAccountPaguController.php`)

#### [MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)
- **Method `store()` & `destroy()`**:
  - Mendeteksi request asynchronous (`$request->ajax()` atau `$request->wantsJson()` atau header `X-Blade-Fragment`).
  - Menghitung statistik terbaru dan merender response fragment baris rekening terkait beserta summary cards:
    ```php
    if ($request->ajax() || $request->wantsJson()) {
        // Return view fragment atau JSON payload data + HTML fragment
        return response()->json([
            'success' => true,
            'message' => "Pagu rekening {$accountCode->code} berhasil ditetapkan.",
            'row_html' => view('admin.headers.pagu', $viewData)->fragment('account-row-' . $accountCode->id),
            'summary_html' => view('admin.headers.pagu', $viewData)->fragment('summary-cards'),
        ]);
    }
    ```
  - Jika terjadi error validasi supervisor (usulan belum divalidasi): Mengembalikan status HTTP 422 dengan pesan error dan rincian daftar operator & supervisor.

---

### 2. View Penetapan Pagu (`resources/views/admin/headers/pagu.blade.php`)

#### [MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- Menambahkan direktif `@fragment` pada:
  - Kartu Summary Stats: `@fragment('summary-cards') ... @endfragment`
  - Setiap baris `<tr>`: `@fragment('account-row-' . $code->id) ... @endfragment`
- Menambahkan JavaScript helper native ringan (~20 baris) untuk menangani submit form `Simpan` dan `Batal` secara asynchronous:
  - Mencegah form reload (`event.preventDefault()`).
  - Mengubah tombol menjadi state loading.
  - Mengganti elemen HTML `#row-{id}` dan `#summary-cards` secara instan dari respon server.
  - Menampilkan floating Toast Notification (sukses / error).

---

### 3. Pengujian Otomatis (`tests/Feature/Admin/PaguTest.php`)

#### [MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)
- Menambahkan unit/feature tests:
  - `test_admin_can_save_pagu_asynchronously_and_receive_fragment`
  - `test_admin_can_cancel_pagu_asynchronously_and_receive_fragment`
  - `test_async_save_pagu_fails_when_unvalidated_details_exist`

---

## Verification Plan

### Automated Tests
- Jalankan test suite Pagu:
  `php artisan test --filter=PaguTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Buka halaman Penetapan Pagu sebagai Administrator (`/admin/headers/{header}/pagu`).
2. Scroll ke bagian tengah atau bawah halaman daftar nomor rekening.
3. Ubah nominal dan klik **Simpan**:
   - Pastikan **halaman tidak reload**, posisi scroll tidak bergeser sama sekali.
   - Baris rekening langsung berubah menjadi status "Sudah Ditetapkan" dan tombol "Batal" muncul.
   - Kartu statistik di atas otomatis ter-update.
   - Muncul floating Toast sukses.
4. Klik **Batal**:
   - Pastikan pagu dibatalkan tanpa reload dan status kembali "Belum Ditetapkan".
5. Coba simpan pada rekening yang memiliki usulan belum divalidasi supervisor:
   - Pastikan muncul notifikasi error detail tanpa reload halaman.
