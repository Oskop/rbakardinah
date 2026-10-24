# Implementation Plan: Optimalisasi UI Filter Kategori Level 5 pada Formulir RKBMD Operator (`create` & `edit`)

Dokumen ini disusun untuk mengoptimalkan pengalaman pengguna (UX) operator pada formulir **Pengajuan RKBMD** ([resources/views/operator/rkbmd/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php)) dan **Edit RKBMD** ([resources/views/operator/rkbmd/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php)), khususnya pada bagian **Filter Kategori Level 5 (Rincian Objek)** yang saat ini masih menggunakan native HTML `<select>` dan `<option>`.

---

## 1. Analisis Masalah & Kebutuhan Pengguna

### 1.1 Kendala UX Saat Ini
1. **Beban Data pada Setiap Baris Dinamis**:
   - Operator dapat menambah baris barang secara dinamis (`x-for="(item, index) in items"`).
   - Setiap baris saat ini merender elemen `<select>` dengan **158 tag `<option>`** statis. Jika operator mengajukan 5 baris barang, browser harus merender `5 x 158 = 790` tag `<option>`.
2. **Ketiadaan Fitur Pencarian di dalam Dropdown**:
   - Native HTML `<select>` tidak memiliki kolom pencarian (*search-as-you-type*).
   - Operator kesulitan dan lelah menggulir (*scrolling*) 158 daftar kategori untuk menemukan kategori yang relevan (misal: "KOMPUTER UNIT", "ALAT TULIS KANTOR", atau "ALAT KEDOKTERAN").
3. **Kebutuhan Kompatibilitas Reaktivitas Alpine.js**:
   - Karena daftar baris bersifat dinamis (bisa ditambah, dihapus, atau diubah urutannya), komponen filter harus **100% reaktif dan terisolasi per baris** tanpa menyebabkan bug *lifecycle* atau *DOM detachment*.

---

## 2. Komparasi Pendekatan Teknis untuk Formulir Dinamis

```
+---------------------------------------------------------------------------------------------------+
| OPSI 1: Alpine.js Native Searchable Dropdown (Rekomendasi Utama)                                  |
| - 100% selaras dengan Alpine.js (x-data="rkbmdBarangCombobox")                                   |
| - Data 158 kategori dimuat 1x di JavaScript, pencarian in-memory instan (0 ms latency)            |
| - Bebas dari konflik manipulasi DOM pihak ketiga saat baris ditambah/dihapus (x-for safe)         |
+---------------------------------------------------------------------------------------------------+
| OPSI 2: Plugin Pihak Ketiga (TomSelect / Select2 di Setiap Baris Dinamis)                         |
| - Memasang TomSelect pada <select> di dalam template x-for                                        |
| - Rentan bug: Re-inisialisasi manual, memory leaks, dan lepas event listener saat baris dihapus   |
+---------------------------------------------------------------------------------------------------+
| OPSI 3: Modal Dialog Pemilih Kategori                                                             |
| - Membuka modal popup saat tombol "Pilih Kategori" diklik                                         |
| - Terlalu lambat (banyak klik & context switching) untuk sekadar filter per baris                 |
+---------------------------------------------------------------------------------------------------+
```

### Tabel Matriks Perbandingan

| Kriteria Evaluasi | Opsi 1: Alpine Native Combobox (Rekomendasi) | Opsi 2: TomSelect di Dalam `x-for` | Opsi 3: Modal Dialog |
|---|---|---|---|
| **Stabilitas pada Dynamic Rows (`x-for`)** | **Sangat Tinggi** (Reaktif murni Alpine) | Rentan Bug (DOM conflict) | Tinggi |
| **Kecepatan Pencarian Kategori** | **Instan** (In-Memory Array Filter) | Cepat | Cepat |
| **Ukuran DOM & Jejak Memori** | **Sangat Ringan** (Tanpa 158 `<option>` per baris) | Berat (Wrapper DOM TomSelect) | Sedang |
| **Pengalaman Pengguna (UX)** | **Modern & Ringkas** (Badge aktif + tombol ✕) | Bagus | Terlalu banyak klik |
| **Kemudahan Pemeliharaan Kode** | **Tinggi** (Terpusat di objek combobox) | Rendah (Butuh hook lifecycle rumit)| Sedang |

> [!TIP]
> **Rekomendasi**: Pilih **Opsi 1 (Alpine.js Native Searchable Dropdown)**. Solusi ini memberikan kecepatan maksimal, tampilan yang elegan, dan menjamin **zero bug** pada baris dinamis RKBMD.

---

## 3. Rincian Desain Solusi

### 3.1 Struktur Komponen Filter Kategori per Baris
Komponen filter kategori di dalam combobox baris dirancang dengan dua status:

#### Status 1: Kategori Belum Dipilih (Default)
- Menampilkan tombol pemicu (*trigger*) yang rapi dan ringkas:
  ```html
  <button type="button" @click="catOpen = !catOpen"
      class="w-full flex items-center justify-between text-xs py-1.5 px-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 transition-colors">
      <span class="flex items-center gap-1.5">
          <span>📁</span>
          <span>Saring Kategori Level 5 (Opsional)</span>
      </span>
      <span class="text-slate-400 text-[10px]">▼</span>
  </button>
  ```

#### Status 2: Dropdown Pencarian Kategori Terbuka (`catOpen = true`)
- Muncul floating popover dengan kotak input pencarian *autofocus*:
  - **Input Pencarian**: Operator cukup mengetik kata (misal: `"komp"`, `"alat medis"`, `"kantor"`, atau kode `"1.3.2"`).
  - **Daftar Hasil Terfilter**: Hanya menampilkan kategori yang cocok dari 158 item secara instan, lengkap dengan badge kode rekening font mono (`[1.3.2.10.01]`).
  - **Opsi Reset**: Tersedia opsi *"-- Tampilkan Semua Kategori (Tanpa Filter) --"*.
  - **Klik di Luar**: Tertutup otomatis via `@click.outside="catOpen = false"`.

#### Status 3: Kategori Terpilih
- Tombol berubah menjadi **Pill Badge Aktif** yang informatif:
  ```html
  <div class="flex items-center justify-between p-1.5 px-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-900">
      <div class="flex items-center gap-2 truncate">
          <span class="px-1.5 py-0.5 rounded font-mono text-[9px] font-bold bg-blue-200 text-blue-800" x-text="selectedCategory"></span>
          <span class="font-bold text-[11px] truncate" x-text="selectedCategoryName"></span>
      </div>
      <button type="button" @click="clearCategory()" class="text-blue-400 hover:text-rose-600 font-bold ml-2 text-xs" title="Hapus saringan kategori">✕</button>
  </div>
  ```
- Dropdown pencarian barang di bawahnya secara otomatis tersaring hanya menampilkan barang-barang di bawah kategori Level 5 tersebut.

---

## 4. File yang Akan Dimodifikasi

| No | Berkas | Tipe Perubahan | Deskripsi |
|---|---|---|---|
| 1 | [resources/views/operator/rkbmd/create.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/create.blade.php) | **Blade View & JS** | - Mengirimkan data `level5List` JSON ke script combobox<br>- Mengganti native `<select>` dengan searchable popover dropdown<br>- Menambahkan state `catOpen`, `catQuery`, `selectedCategoryName`, `filteredCategories()` |
| 2 | [resources/views/operator/rkbmd/edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php) | **Blade View & JS** | Mengimplementasikan pembaruan yang sama pada halaman edit RKBMD |
| 3 | [tests/Feature/MasterBarangCategoryAndSearchTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/MasterBarangCategoryAndSearchTest.php) | **Test Suite** | Menambahkan pengujian render halaman `create` dan `edit` RKBMD memuat daftar kategori Level 5 JSON |

---

## 5. Rencana Tahapan Eksekusi

```
Step 1: Persiapan Data Kategori di Blade View
  └─ Pastikan level5List dioper ke JavaScript sebagai variabel global atau parameter combobox

Step 2: Pembaruan Komponen JS rkbmdBarangCombobox
  └─ Tambahkan state: catOpen, catQuery, selectedCategory, selectedCategoryName
  └─ Tambahkan method: filteredCategories(), selectCategory(cat), clearCategory()
  └─ Integrasikan pemanggilan search() saat kategori dipilih atau dibersihkan

Step 3: Pembaruan Tampilan HTML di create.blade.php & edit.blade.php
  └─ Gantikan elemen <select> native dengan Trigger Button + Floating Popover
  └─ Tambahkan kotak input pencarian kategori dan daftar hasil terfilter dengan scrollbar halus
  └─ Tampilkan active badge saat kategori terpilih dengan tombol silang (✕)

Step 4: Verifikasi & Testing
  └─ Jalankan php artisan test --filter=MasterBarang
  └─ Jalankan php artisan test --filter=RkbmdTest
  └─ Pastikan 100% test lulus tanpa regresi
```

---

## 6. Kriteria Keberhasilan (Success Criteria)

1. **Pencarian Cepat & Ringan**: Operator dapat mencari dari 158 kategori Level 5 secara instan hanya dengan mengetik beberapa huruf.
2. **DOM Bersih & Hemat Memori**: Tidak ada lagi ratusan tag `<option>` statis yang terduplikasi di setiap baris pengajuan.
3. **Kompatibel 100% dengan Penambahan Baris Dinamis**: Menambah atau menghapus baris barang berjalan mulus tanpa error JavaScript.
4. **Desain Terpadu**: Komponen selaras dengan badge kode dan hierarki Permendagri 108 yang telah diterapkan sebelumnya.
5. **Zero Regression**: Seluruh rangkaian tes otomatis aplikasi tetap berstatus **PASS** (100% hijau).
