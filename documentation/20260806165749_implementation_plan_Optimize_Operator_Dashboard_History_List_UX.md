# Implementation Plan - Optimalisasi UX Mode Daftar pada Daftar RBA Historis Operator Dashboard

Meningkatkan kenyamanan pengguna (*User Experience*) pada bagian **Daftar RBA Historis** (Mode Daftar) di Dashboard Operator (`resources/views/operator/dashboard.blade.php`) agar ketika jumlah RBA bertambah banyak, halaman tidak mengalami *scroll vertical* yang terlalu panjang ke bawah.

## User Review Required

> [!IMPORTANT]
> - **Penambahan Fitur Pencarian RBA (`searchRba`)**:
>   - Menyisipkan *search box* interaktif berbasis Alpine.js untuk memfilter daftar RBA secara *real-time* berdasarkan tahun RBA atau tipe periode (misal: mencari *"2025"*, *"Murni"*, *"Perubahan"*).
> - **Batasan Tinggi Maksimal Scrollable (`max-h-[500px]`)**:
>   - Mengatur kontainer daftar kartu RBA agar memiliki tinggi maksimal `max-h-[500px]` dengan *smooth vertical scrollbar*, sehingga ketinggian kolom kiri selaras dan proporsional dengan workspace detail di sisi kanan.
> - **Indikator Jumlah & Empty Search State**:
>   - Menampilkan jumlah hasil pencarian RBA dan pesan ramah *"Tanda RBA tidak ditemukan"* jika kueri pencarian tidak cocok.

## Open Questions

> [!NOTE]
> - Tidak ada isu pemblokir. Perubahan dilakukan murni pada aspek interaktivitas UI/UX Alpine.js di `dashboard.blade.php`.

## Proposed Changes

### View Layer

#### [MODIFY] [dashboard.blade.php (Operator Dashboard)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/dashboard.blade.php)
- Memperbarui state Alpine.js dengan menambahkan `searchRba: ''` dan getter `filteredRbas`.
- Pada **Mode Daftar** di bagian **Daftar RBA Historis**:
  - Menyisipkan *Search Bar* dengan ikon pencarian.
  - Membungkus kartu RBA dalam kontainer `max-h-[500px] overflow-y-auto pr-1 space-y-3`.
  - Mengiterasi `filteredRbas` untuk hasil pencarian presisi.

---

## Verification Plan

### Automated Tests
- Menjalankan `php artisan test` untuk memastikan seluruh test suite lulus 100%.

### Manual Verification
- Login sebagai Operator.
- Buka Operator Dashboard (`/operator/dashboard`).
- Pada **Daftar RBA Historis** (Mode Daftar):
  - Coba ketik kata kunci pada kotak pencarian (misal: "2025" atau "Perubahan").
  - Pastikan hasil daftar memfilter RBA secara otomatis.
  - Pastikan kontainer memiliki batas scroll vertikal yang rapi (`max-h-[500px]`) sehingga tidak memanjang berlebihan ke bawah.
