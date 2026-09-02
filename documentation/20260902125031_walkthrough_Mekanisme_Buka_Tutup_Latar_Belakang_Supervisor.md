# Walkthrough - Mekanisme Buka-Tutup (Accordion / Collapsible) Latar Belakang RBA per Operator pada Tampilan Supervisor

Mekanisme **buka-tutup (accordion / collapsible)** pada bagian Latar Belakang RBA per Operator di halaman review RBA Supervisor (`supervisor.submissions.show`) telah selesai diimplementasikan, dibangun, dan diuji 100%. Dengan mekanisme ini, tampilan halaman tetap ringkas, bersih, dan nyaman dibaca tanpa terbebani scroll yang terlalu panjang (*long scroll*).

---

## Ringkasan Fitur yang Diimplementasikan

### 1. Kartu Operator Ringkas Berbasis Accordion (Default: Tertutup)
- **Header Kartu Interaktif:**
  - Tiap operator aktif memiliki kartu dengan header yang dapat diklik (`@click="toggleOperator(id)"`).
  - Menampilkan Avatar inisial, **Nama Operator**, NIP, dan email.
  - **Cuplikan Cepat (Collapsed Snippet Preview):** Saat kartu tertutup, sistem menyajikan cuplikan 1 baris teks latar belakang (*line-clamp-1 italic*) sehingga Supervisor dapat melihat garis besar isi usulan tanpa harus membuka kartu.
  - **Badge Status:** `✓ Latar Belakang Terisi` (hijau) atau `⚠️ Belum Mengisi` (kuning/amber).
  - **Indikator Chevron Dinamis:** Panah chevron berotasi 180° secara halus saat kartu dibuka/ditutup lengkap dengan teks indikator `Buka` / `Tutup`.
- **Isi Latar Belakang Terbuka Halus (Smooth Expand):**
  - Konten teks latar belakang lengkap dibuka dengan transisi Alpine.js (`x-show` dan `x-transition`), menampilkan teks berformat rapi (*whitespace-pre-wrap*) serta waktu terakhir diperbarui.

---

### 2. Tombol Aksi Massal (Buka Semua & Tutup Semua)
- Pada toolbar header bagian Latar Belakang, disediakan dua tombol aksi cepat:
  - **"Buka Semua"** (`toggleAll(true)`): Membuka seluruh kartu latar belakang operator secara serempak.
  - **"Tutup Semua"** (`toggleAll(false)`): Menutup kembali seluruh kartu latar belakang menjadi ringkas.

---

### 3. Pengecilan Panel Utama (Minimize Section)
- Judul panel utama **"Latar Belakang RBA per Operator"** dilengkapi toggle buka-tutup (`sectionOpen`).
- Supervisor dapat mengecilkan seluruh modul latar belakang jika ingin langsung fokus ke tabel *Daftar Rincian Belanja*.

---

## File yang Dimodifikasi

- **[MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)**:
  - Implementasi state management Alpine.js (`sectionOpen`, `openOperators`, `toggleOperator`, `toggleAll`, `isOpen`).
  - Komponen kartu operator interaktif dengan header tombol, snippet preview, badge status, rotasi chevron, dan body transisi.
  - Toolbar tombol aksi cepat `Buka Semua` dan `Tutup Semua`.
- **[MODIFY] [ReviewTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Supervisor/ReviewTest.php)**:
  - Penambahan unit test `test_supervisor_view_contains_collapsible_accordion_controls_for_operator_backgrounds` untuk memastikan elemen tombol kontrol dan direktif Alpine.js ter-render dengan sempurna.

---

## Hasil Pengujian & Verifikasi

### 1. Automated Tests PASS
Seluruh **112 feature & unit tests** aplikasi lulus 100% tanpa kegagalan:

```text
PASS  Tests\Feature\Supervisor\ReviewTest
✓ supervisor can view their unit submissions                                                                   1.12s  
✓ supervisor can validate submission                                                                           0.04s  
✓ supervisor can see previous period pagu in awal column                                                       0.40s  
✓ supervisor can preview print report with operator filters                                                    0.06s  
✓ supervisor can preview rba final print report with pagu and operator filters                                 0.05s  
✓ supervisor cannot see draft unsubmitted details                                                              0.06s  
✓ detail disappears from supervisor when rejected detail is edited and reappears when resubmitted              0.09s  
✓ supervisor cannot validate or reject unsubmitted detail                                                      0.04s  
✓ supervisor can see distinct background cards for each active operator                                        0.05s  
✓ operator can save and update their own background without affecting other operators                          0.05s  
✓ supervisor view contains collapsible accordion controls for operator backgrounds                             0.05s  

Tests:    112 passed (464 assertions)
Duration: 38.02s
```

### 2. Frontend Assets Compilation (Bun) PASS
Asset frontend berhasil dikompilasi dengan Vite:
- `public/build/assets/app-Ca-HcVvZ.css` (82.75 kB)
- `public/build/assets/app-CBbTb_k3.js` (83.04 kB)
- Waktu build: **2.17s**
