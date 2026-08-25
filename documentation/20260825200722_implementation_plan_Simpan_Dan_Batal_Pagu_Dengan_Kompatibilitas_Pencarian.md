# Implementation Plan - Mekanisme Simpan & Batal Pagu dengan Kompatibilitas Fitur Pencarian (Search-Ready)

Menganalisis dan merancang arsitektur penyimpanan (**Simpan**) dan pembatalan (**Batal**) penetapan pagu agar **100% kompatibel dengan fitur pencarian (*search/filter*)** pada Daftar Rekening Belanja, baik pencarian client-side instan maupun server-side, tanpa mereset kata kunci pencarian, tanpa reload, dan tanpa scroll jump.

---

## Analisis Kompatibilitas Terhadap Fitur Pencarian

Ketika Administrator melakukan pencarian pada daftar nomor rekening (misalnya mengetik `"5.1.02"` atau `"ATK"`):

| Metode Request | Kompatibilitas dengan Fitur Pencarian | Mengapa? |
| :--- | :--- | :--- |
| **Traditional Form Submit (Kondisi Saat Ini)** | ❌ **Sangat Buruk / Tidak Kompatibel** | Setiap kali klik Simpan/Batal, halaman reload penuh. Kata kunci pencarian akan ter-reset/hilang (atau harus di-carry over di URL), posisi filter tertutup kembali, dan layar melompat ke atas. |
| **Targeted Row DOM Swap + Event Delegation (Blade Fragment / Fetch)** | ✅ **Sempurna & 100% Kompatibel (Recommended ⭐)** | 1. Update hanya menargetkan elemen baris spesifik (`#row-{accountCodeId}`) dan kartu ringkasan (`#summary-cards`).<br>2. Filter pencarian yang sedang aktif **tidak terganggu sama sekali** (kata kunci tetap ada, baris yang sedang difilter tetap tampil).<br>3. Dengan *Event Delegation*, event listener form tetap aktif meskipun baris di-hide/show oleh filter pencarian. |
| **Alpine.js Reactive Table State** | ✅ **Sempurna & Sangat Interaktif** | State `searchQuery` dikelola langsung di level komponen tabel. Saat Simpan/Batal dijalankan via `fetch()`, hanya baris terkait yang di-update tanpa mengubah state `searchQuery`. |

---

## Desain Arsitektur yang Diterapkan: Alpine.js + Targeted Row Fragment Swap

> [!TIP]
> **Kombinasi Terbaik untuk Menjamin Kompatibilitas Pencarian:**
> 1. **State Pencarian Terisolasi:** Fitur pencarian menyaring tampilan baris secara instan (real-time tanpa delay) berdasarkan kode rekening, nama rekening, atau kelompok belanja.
> 2. **Targeted Asynchronous Action:** Saat Admin klik **Simpan** atau **Batal** pada salah satu baris hasil pencarian:
>    - Request dikirim via `fetch()` ke endpoint controller.
>    - Respon server mengembalikan potongan fragment HTML terbaru untuk baris `#row-{id}` dan `#summary-cards`.
>    - Elemen `#row-{id}` diperbarui di tempat tanpa merusak filter pencarian yang sedang aktif.
>    - Toast notifikasi muncul dan input teks pencarian tetap berisi kata kunci yang sama.
> 3. **Future-Proof:** Desain ini sudah langsung menyertakan input **Pencarian Cepat Rekening (*Instant Search*)** di atas tabel sehingga Admin dapat langsung mencari rekening dengan mudah.

---

## User Review Required

> [!IMPORTANT]
> **Fitur yang Diintegrasikan:**
> 1. **Pencarian Cepat (*Instant Search Bar*):** Kotak pencarian di atas tabel untuk memfilter nomor/nama rekening secara instan di sisi browser (zero delay).
> 2. **Simpan & Batal Non-Blocking (Tanpa Reload):** Tombol Simpan dan Batal memproses data di background via `fetch()` dan memperbarui baris rekening terkait secara presisi.
> 3. **Status & Notifikasi Real-time:**
>    - Badge status baris berganti seketika.
>    - Kartu ringkasan pagu di atas ter-update.
>    - Floating Toast (Sukses / Peringatan Validasi Supervisor) muncul tanpa menggeser scroll maupun hasil pencarian.

---

## Proposed Changes

### 1. Controller Admin Pagu (`app/Http/Controllers/Admin/RbaAccountPaguController.php`)

#### [MODIFY] [RbaAccountPaguController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/RbaAccountPaguController.php)
- Mendukung response JSON/Fragment untuk request asynchronous:
  - **Method `store()`**:
    - Jika validasi gagal karena ada usulan yang belum divalidasi supervisor: Return HTTP 422 JSON dengan rincian pesan dan daftar operator/supervisor.
    - Jika sukses: Return JSON 200 dengan payload fragment baris rekening `#row-{id}`, fragment `#summary-cards`, dan statistik terbaru.
  - **Method `destroy()`**:
    - Return JSON 200 dengan fragment baris rekening yang telah direset statusnya ke draft/belum ditetapkan beserta fragment summary cards terbaru.

---

### 2. View Penetapan Pagu (`resources/views/admin/headers/pagu.blade.php`)

#### [MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- Menambahkan **Input Search Bar** modern di atas tabel rekening dengan binding Alpine.js `searchQuery`.
- Menambahkan atribut pencarian pada setiap baris `<tr>`: `data-search="{{ strtolower($code->code . ' ' . $code->name . ' ' . ($code->kelompokBelanja->name ?? '')) }}"` dan `x-show="matchSearch($el)"`.
- Menangani aksi submit form `Simpan` dan `Batal` secara asynchronous:
  - Mencegah full-page reload.
  - Menampilkan loading spinner pada tombol.
  - Mengganti HTML `#row-{id}` secara targeted.
  - Memperbarui kartu `#summary-cards`.
  - Menampilkan Floating Toast Notifikasi.

---

### 3. Pengujian Otomatis (`tests/Feature/Admin/PaguTest.php`)

#### [MODIFY] [PaguTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PaguTest.php)
- Menguji:
  - Simpan pagu via asynchronous request dan penerimaan payload fragment.
  - Batal pagu via asynchronous request.
  - Penolakan simpan pagu dengan HTTP 422 jika masih ada usulan belum divalidasi supervisor.

---

## Verification Plan

### Automated Tests
- Jalankan test suite Pagu:
  `php artisan test --filter=PaguTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Administrator** dan buka halaman Penetapan Pagu (`/admin/headers/{header}/pagu`).
2. Ketik kata kunci pada kotak **Pencarian Cepat Rekening** (misal: `"5.1"` atau `"Belanja"`).
3. Verifikasi daftar tabel langsung terfilter menampilkan rekening yang cocok.
4. Ubah nominal pagu pada salah satu rekening hasil pencarian dan klik **💾 Simpan**:
   - Pastikan **halaman tidak reload**.
   - Hasil pencarian **tetap aktif** dan tidak hilang.
   - Posisi scroll tidak bergeser.
   - Baris rekening langsung berubah menjadi "Sudah Ditetapkan" dan tombol "Batal" muncul.
   - Kartu statistik di atas otomatis ter-update.
5. Hapus kata kunci pencarian, verifikasi seluruh daftar rekening tampil kembali dengan status yang tetap konsisten.
