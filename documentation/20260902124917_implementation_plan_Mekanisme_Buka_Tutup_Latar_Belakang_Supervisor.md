# Implementation Plan - Mekanisme Buka-Tutup (Accordion / Collapsible) Latar Belakang RBA per Operator pada Tampilan Supervisor

Menambahkan mekanisme interaktif **buka-tutup (accordion / collapsible)** pada bagian Latar Belakang RBA per Operator di halaman review RBA Supervisor (`supervisor.submissions.show`), sehingga Supervisor dapat meninjau latar belakang operator secara ringkas tanpa menyebabkan halaman menjadi terlalu panjang (*long scroll*).

---

## User Review Required

> [!IMPORTANT]
> **Rancangan Mekanisme Buka-Tutup (Accordion / Collapsible):**
> 1. **Buka-Tutup per Kartu Operator (Default: Tertutup / Ringkas):**
>    - Secara default, kartu masing-masing operator disajikan dalam mode tertutup (*collapsed*) yang ringkas.
>    - Header kartu berfungsi sebagai tombol interaktif yang menampilkan:
>      - Avatar inisial, **Nama Operator**, NIP, dan email.
>      - Cuplikan singkat isi (*text snippet preview*) jika sudah diisi.
>      - Badge status: `✓ Latar Belakang Terisi` (hijau) atau `⚠️ Belum Mengisi` (kuning/amber).
>      - Indikator panah chevron (▼/▲) dengan animasi rotasi saat dibuka.
>    - Ketika kartu diklik, bagian isi teks latar belakang lengkap (*whitespace-pre-wrap*) beserta timestamp pembaruan akan terbuka secara halus (*smooth transition*).
> 2. **Tombol Aksi Massal (Buka Semua & Tutup Semua):**
>    - Pada header panel Latar Belakang, disediakan tombol aksi cepat:
>      - **"Buka Semua"**: Membuka seluruh kartu latar belakang operator dengan satu klik.
>      - **"Tutup Semua"**: Menutup kembali seluruh kartu latar belakang operator untuk merapikan tampilan.
> 3. **Penyusutan Panel Utama (Minimize Section):**
>    - Panel utama "Latar Belakang RBA per Operator" juga dilengkapi tombol buka-tutup di bagian judul, memungkinkan Supervisor menyembunyikan seluruh modul latar belakang jika ingin fokus pada tabel *Daftar Rincian Belanja*.

---

## Proposed Changes

### Frontend View Layer

#### [MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Menambahkan state management Alpine.js pada bagian Latar Belakang:
  - `sectionOpen`: Mengontrol buka-tutup seluruh modul Latar Belakang.
  - `openOperators`: Object dictionary status buka-tutup per operator `id` (`{ [opId]: boolean }`).
  - Method `toggleOperator(id)`: Membuka/menutup kartu operator tertentu.
  - Method `toggleAll(open)`: Membuka atau menutup seluruh kartu operator secara bersamaan.
  - Method `isOpen(id)`: Memeriksa apakah kartu operator tertentu sedang terbuka.
- Memperbarui tampilan header kartu operator:
  - Header kartu dapat diklik (`@click="toggleOperator({{ $op->id }})"`) dengan efek hover yang nyaman dan kursor pointer.
  - Menampilkan chevron panah yang berotasi 180° (`:class="isOpen({{ $op->id }}) ? 'rotate-180' : ''"`).
  - Ketika tertutup, menampilkan cuplikan teks 1 baris (*line-clamp-1*) sebagai intisari cepat.
- Membungkus konten teks latar belakang dengan `x-show="isOpen({{ $op->id }})"` dan `x-transition`.
- Menambahkan tombol **"Buka Semua"** dan **"Tutup Semua"** pada toolbar header bagian atas.

---

### Automated Tests Layer

#### [MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)
- Menambahkan test case `test_supervisor_view_contains_collapsible_accordion_controls_for_operator_backgrounds`:
  - Memverifikasi keberadaan tombol `Buka Semua`, `Tutup Semua`, dan atribut Alpine.js `toggleOperator` / `openOperators`.
  - Memverifikasi bahwa data latar belakang operator aktif termuat dengan benar di dalam struktur accordion.

---

## Verification Plan

### Automated Tests
- Menjalankan test suite Supervisor:
  `php artisan test --filter=ReviewTest`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login sebagai **Supervisor**.
2. Akses halaman review RBA suatu periode (`/supervisor/submissions/{id}`).
3. Verifikasi pada bagian **Latar Belakang RBA per Operator**:
   - Kartu operator tampil dalam keadaan ringkas secara default (tidak memakan tempat vertikal).
   - Klik salah satu kartu operator: isi teks latar belakang terbuka dengan animasi transisi yang mulus.
   - Klik tombol **"Buka Semua"**: seluruh kartu operator langsung terbuka serentak.
   - Klik tombol **"Tutup Semua"**: seluruh kartu operator kembali tertutup menjadi ringkas.
   - Klik tombol minimize panel utama: seluruh modul Latar Belakang menyusut rapi.
