# Implementation Plan - Perbaikan Tombol Batal Pagu Pasca Klik Simpan

Memperbaiki bug pada halaman **Penetapan Pagu Per Nomor Rekening** di mana tombol **Batal** tidak dapat diklik / tidak merespons setelah admin melakukan aksi **Simpan** pada baris rekening (sebelum halaman di-refresh).

---

## Analisis Akar Masalah (Root Cause)

1. **Selector Collision pada Global Bridge:**
   - Setelah sukses melakukan `savePagu()`, kode sebelumnya membuat elemen tombol Batal baru menggunakan manipulasi string `innerHTML` dengan atribut `onclick="window.dispatchPaguCancel(...)"`.
   - Pada fungsi `window.dispatchPaguCancel`, kode menggunakan `document.querySelector('[x-data]')` untuk memanggil instance Alpine.js.
   - Karena pada bagian atas halaman terdapat komponen navigasi `<nav x-data="{ open: false }">`, selector `[x-data]` mengambil instance navbar tersebut, bukan instance `paguManager`. Akibatnya, pemanggilan `manager.cancelPagu()` gagal secara diam-diam (*silent fail*) karena method tersebut tidak ada pada navbar.
2. **Desain Imperatif vs Reaktif:**
   - Menggunakan `innerHTML` dan `onclick` manual keluar dari siklus hidup reaktif Alpine.js, rentan terhadap masalah *event binding* dan *selector collision*.

---

## User Review Required

> [!IMPORTANT]
> **Pendekatan Perbaikan (Full Alpine.js Reactive State):**
> 1. **Data State Terpusat:** Setiap nomor rekening diinisialisasi ke dalam dictionary reaktif `pagus[accountId] = { isEstablished, nominal, destroyUrl, updatedAt, accountName }`.
> 2. **Deklaratif Render:** Tombol **Batal** dan **Status Badge** dirender secara deklaratif menggunakan `<template x-if="pagus[id].isEstablished">` dengan event listener asli `@click="cancelPagu(id)"`.
> 3. **Instan & Konsisten:** Saat `savePagu()` berhasil, sistem cukup mengubah `this.pagus[accountId].isEstablished = true`. Alpine.js secara otomatis merender tombol Batal dengan event listener yang 100% terhubung langsung ke method `cancelPagu(id)` tanpa perantara `innerHTML` atau selector DOM.

---

## Proposed Changes

### 1. View Penetapan Pagu (`resources/views/admin/headers/pagu.blade.php`)

#### [MODIFY] [pagu.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/pagu.blade.php)
- Mengirim initial data `pagus` dari Blade ke fungsi `paguManager(initialData)`.
- Mengubah badge status dan tombol batal menggunakan template deklaratif Alpine.js:
  ```blade
  <!-- Status Badge -->
  <template x-if="pagus[{{ $code->id }}]?.isEstablished">
      <div class="inline-flex flex-col items-center">
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
              <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Sudah Ditetapkan
          </span>
          <span class="text-[10px] text-gray-400 mt-1" x-text="pagus[{{ $code->id }}]?.updatedAt"></span>
      </div>
  </template>
  <template x-if="!pagus[{{ $code->id }}]?.isEstablished">
      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200">
          ⏳ Belum Ditetapkan
      </span>
  </template>
  ```
  ```blade
  <!-- Tombol Batal -->
  <template x-if="pagus[{{ $code->id }}]?.isEstablished">
      <button type="button"
          @click="cancelPagu({{ $code->id }})"
          :disabled="loadingRows[{{ $code->id }}]"
          class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1.5 rounded hover:bg-rose-50 transition"
          title="Batalkan penetapan pagu rekening ini">
          Batal
      </button>
  </template>
  ```
- Memperbarui method `savePagu(event, accountId)` dan `cancelPagu(accountId)` pada objek Alpine.js:
  - `savePagu`: `this.pagus[accountId].isEstablished = true`, `this.pagus[accountId].updatedAt = result.data.updated_at`, `this.pagus[accountId].destroyUrl = result.data.destroy_url`.
  - `cancelPagu`: `this.pagus[accountId].isEstablished = false`, `this.pagus[accountId].updatedAt = ''`.
- Menghapus fungsi bridge `window.dispatchPaguCancel`.

---

## Verification Plan

### Automated Tests
- Jalankan test suite Pagu:
  `php artisan test --filter=PaguTest`
- Jalankan keseluruhan test suite:
  `php artisan test`

### Manual Verification
1. Login sebagai **Administrator**.
2. Masuk ke halaman **Penetapan Pagu** (`/admin/headers/{header}/pagu`).
3. Pilih salah satu rekening yang belum ditetapkan pagu (atau ubah nominal rekening yang sudah ada).
4. Klik **💾 Simpan**:
   - Status berubah menjadi "Sudah Ditetapkan" dan tombol **Batal** langsung muncul.
5. **Langsung klik tombol "Batal" tersebut tanpa me-refresh halaman**:
   - Muncul dialog konfirmasi browser.
   - Saat dikonfirmasi, pagu berhasil dibatalkan, badge berubah kembali menjadi "Belum Ditetapkan", dan tombol Batal hilang.
   - Notifikasi sukses muncul dan nilai kartu Summary Stats di atas otomatis berkurang.
6. Klik **💾 Simpan** kembali pada baris yang sama, lalu klik **Batal** lagi berulang kali untuk memastikan tidak ada race condition maupun kebocoran state.
