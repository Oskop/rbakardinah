# Rencana Implementasi: Fitur Tombol Munculkan Ulang Banner Pengumuman

Fitur ini memungkinkan pengguna (Operator, Supervisor, Administrator) yang telah menutup atau menyembunyikan banner pengumuman untuk **memunculkannya kembali kapan saja** secara instan saat membutuhkan rincian informasi pengumuman tersebut kembali.

---

## 1. Analisis Kebutuhan & Desain Solusi

### Kebutuhan Pengguna
- Saat ini, ketika pengguna mengklik tombol tutup ("x"), pengumuman disembunyikan ke dalam `sessionStorage` untuk sesi browser tersebut.
- Pengguna membutuhkan akses untuk **memunculkan ulang** (*restore/reopen*) pengumuman tersebut jika ingin membaca ulang detail informasi, tanggal batas waktu, atau instruksi di dalamnya, tanpa perlu membersihkan data browser atau membuka sesi baru.

### Desain UX & Interaksi (*User Experience*)
1. **Floating Restore Action Pill (Tombol Melayang yang Elegan & Non-Intrusive)**:
   - Ketika ada pengumuman aktif yang berstatus disembunyikan (`hasDismissed === true`), sebuah tombol pill kecil modern akan muncul secara otomatis di sudut kanan bawah layar (`fixed bottom-6 right-6 z-40`).
   - Tampilan: Badge pill dengan efek glassmorphism (`bg-white/95 backdrop-blur-md shadow-lg border border-indigo-200 text-indigo-700 hover:text-indigo-900`), icon pengumuman 📢, indikator dot animasi, dan teks interaktif:
     `📢 Munculkan Kembali Pengumuman` (disertai indikator jumlah pengumuman yang disembunyikan).
   - **Posisi Nyaman & Tidak Menghalangi**: Tombol diletakkan di sudut kanan bawah sehingga tidak memakan ruang antara bilah navigasi dan judul halaman (area atas tetap bersih dan rapat saat pengumuman ditutup).
2. **Aksi Munculkan Ulang (*Restore Action*)**:
   - Ketika tombol "Munculkan Kembali Pengumuman" diklik:
     - State visibilitas di Alpine.js langsung diaktifkan kembali (`this.announcements[id] = true`).
     - Kunci `sessionStorage` untuk pengumuman terkait dihapus (`sessionStorage.removeItem(...)`).
     - Banner pengumuman di atas halaman kembali mengembang dan tampil seketika dengan animasi transisi yang halus.
     - Tombol melayang di kanan bawah otomatis menghilang.
3. **Isolasi Kode**:
   - Seluruh logika dan template ditempatkan **hanya pada berkas komponen pengumuman** [resources/views/components/announcement-banner.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/announcement-banner.blade.php), tanpa mengubah berkas atau modul di luar pengumuman.

---

## 2. Rincian Perubahan Berkas (*Proposed Changes*)

### [MODIFY] [resources/views/components/announcement-banner.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/components/announcement-banner.blade.php)

1. **Peningkatan State Alpine.js**:
   - Menambahkan getter `hasDismissed`:
     `Object.values(this.announcements).some(v => v === false)`
   - Menambahkan getter `dismissedCount`: menghitung jumlah pengumuman yang sedang disembunyikan.
   - Menambahkan method `restoreAll()`:
     - Mengubah semua nilai di `this.announcements` menjadi `true`.
     - Menghapus kunci `announcement_dismissed_[id]` dari `sessionStorage`.
   - Menambahkan method `restore(id)`: memulihkan pengumuman spesifik jika diinginkan.
2. **Floating Restore Pill**:
   - Menambahkan elemen tombol restore dengan direktif `x-show="hasDismissed"`, `x-transition`, dan event `@click="restoreAll()"`.
   - Tombol dilengkapi efek hover, scale micro-interaction, dan tooltip yang informatif.

---

## 3. Rencana Verifikasi (*Verification Plan*)

### Automated Tests
- Menjalankan kembali automated feature test pengumuman:
  `php artisan test --filter=AnnouncementTest`
- Menjalankan kompilasi aset frontend Vite:
  `bun run build`
- Menjalankan seluruh test suite aplikasi:
  `php artisan test`

### Manual Verification
1. Login sebagai Operator / Supervisor / Administrator dengan pengumuman aktif.
2. Klik tombol tutup ("x") pada kartu banner pengumuman.
3. Verifikasi:
   - Banner di atas halaman menutup dan celah kosong lenyap (rapat seperti biasa).
   - Muncul tombol floating di sudut kanan bawah: `📢 Munculkan Kembali Pengumuman`.
4. Klik tombol "Munculkan Kembali Pengumuman".
5. Verifikasi:
   - Banner pengumuman di bagian atas langsung muncul kembali.
   - Tombol floating di kanan bawah otomatis hilang.
   - Halaman di-refresh: status pemunculan ulang tetap terjaga karena flag di `sessionStorage` telah dibersihkan.

---

## 4. Lokasi Penyimpanan Dokumentasi
Rencana ini disimpan di:
- `documentation/20260907142000_implementation_plan_Fitur_Tombol_Munculkan_Ulang_Banner_Pengumuman.md`
