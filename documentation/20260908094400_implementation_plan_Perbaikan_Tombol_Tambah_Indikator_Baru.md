# Implementation Plan: Perbaikan Tombol "Tambah Indikator Baru" (Indikator Kinerja Admin)

Dokumen ini merinci rencana perbaikan masalah tombol **Tambah Indikator Baru** yang tidak merespon atau tidak memunculkan modal saat diklik di halaman [resources/views/admin/performance-indicators/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/performance-indicators/index.blade.php).

---

## 1. Akar Masalah (Root Cause Analysis)

Berdasarkan penelusuran kode sumber:
1. **Event `alpine:init` Sudah Terlewat (Timing Issue)**:
   - Pada layout `app.blade.php`, script `@vite(['resources/css/app.css', 'resources/js/app.js'])` dimuat di `<head>`.
   - Di dalam `app.js`, Alpine.js diimpor dan langsung dijalankan seketika via `Alpine.start()`.
   - Di `index.blade.php`, komponen didaftarkan melalui:
     ```javascript
     document.addEventListener('alpine:init', () => {
         Alpine.data('performanceIndicatorsManager', () => ({ ... }));
     });
     ```
   - Karena event `alpine:init` sudah selesai di-dispatch saat pemuatan di `<head>`, callback listener di `index.blade.php` **tidak pernah terpanggil**. Akibatnya, `performanceIndicatorsManager` tidak terdaftar di Alpine dan seluruh direktif Alpine di halaman tersebut (`x-data`, `@click`, `x-show`) gagal diinisialisasi.
2. **Scope Tombol di Luar Lingkup `x-data`**:
   - Tombol "Tambah Indikator Baru" berada di dalam `<x-slot name="header">` (bagian `<header>`), sedangkan `x-data="performanceIndicatorsManager()"` berada di bawahnya di dalam `{{ $slot }}` (`<main>`).
   - Elemen `<button>` di dalam `<x-slot name="header">` tidak memiliki direktif `x-data`, sehingga atribut `@click="$dispatch('open-indicator-modal')"` tidak diproses oleh Alpine.js.

---

## 2. Rencana Solusi (Proposed Solution)

### 2.1 Ubah Registrasi Alpine Component ke Fungsi Global Standar
- Mengikuti pola yang terbukti sukses di fitur SIPAKAR lainnya (seperti `paguManager` pada `pagu.blade.php`), kita ubah deklarasi komponen dari `Alpine.data(...)` di dalam event listener menjadi **fungsi JavaScript global**:
  ```javascript
  function performanceIndicatorsManager() {
      return {
          // state & methods
      };
  }
  ```
- Dengan cara ini, ketika Alpine mem-parsing DOM `<div x-data="performanceIndicatorsManager()">`, fungsi langsung dieksekusi secara sinkron tanpa bergantung pada event `alpine:init`.

### 2.2 Hubungkan Tombol Header dengan Event Dispatcher Terpercaya
- Pada tombol di dalam `<x-slot name="header">`, gunakan listener ganda yang aman:
  ```html
  <button type="button"
          onclick="window.dispatchEvent(new CustomEvent('open-indicator-modal'))"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all cursor-pointer">
      ...
      <span>Tambah Indikator Baru</span>
  </button>
  ```
- Di dalam `init()` komponen Alpine `performanceIndicatorsManager`:
  ```javascript
  init() {
      window.addEventListener('open-indicator-modal', () => {
          this.openAddIndicatorModal();
      });
  }
  ```

### 2.3 Tambahkan Tombol Aksi Langsung di Toolbar Tabel
- Selain tombol di header atas, tambahkan juga tombol `+ Tambah Indikator` langsung di dalam toolbar tabel (sejajar dengan rentang tahun dan pencarian).
- Tombol di dalam toolbar ini berada langsung di dalam scope `x-data`, sehingga dapat memanggil `@click="openAddIndicatorModal()"` secara langsung dan instan tanpa perantara event.

### 2.4 Pindahkan Script ke Stack `@push('scripts')`
- Bungkus tag `<script>` di dalam direktif `@push('scripts') ... @endpush` agar di-render di akhir dokumen sebelum tag penutup `</body>` sesuai konvensi layout Laravel Blade `app.blade.php`.

---

## 3. Rencana Perubahan Berkas (Proposed Changes)

### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/performance-indicators/index.blade.php)
1. Perbarui tombol di `<x-slot name="header">` agar menggunakan `onclick="window.dispatchEvent(new CustomEvent('open-indicator-modal'))"`.
2. Tambahkan tombol `+ Tambah Indikator Baru` di toolbar filter tabel di dalam scope `x-data`.
3. Ubah deklarasi script dari `document.addEventListener('alpine:init', ...)` menjadi fungsi global `function performanceIndicatorsManager() { return { ... }; }`.
4. Bungkus script dengan `@push('scripts')`.

---

## 4. Rencana Verifikasi (Verification Plan)

### 4.1 Verifikasi Otomatis
- Jalankan test suite `php artisan test --filter=PerformanceIndicatorTest` untuk memastikan seluruh fungsionalitas backend dan render view tetap valid.
- Jalankan `bun run build` untuk memverifikasi kompilasi aset frontend.

### 4.2 Verifikasi Interaktivitas Browser
- Buka browser menggunakan tool `browser_subagent` untuk mengunjungi `/admin/performance-indicators`.
- Klik tombol "Tambah Indikator Baru" baik yang di header maupun di toolbar.
- Pastikan Modal Dialog "Tambah Indikator Kinerja Baru" muncul dengan sempurna dan field-field input dapat diisi.
