# Implementation Plan - RBA Details Volume, Satuan & Harga Satuan

Rencana implementasi ini bertujuan untuk memodifikasi struktur data dan alur input rincian belanja (`rba_details`) dengan menambahkan rincian kuantitas (Volume, Satuan, dan Harga Satuan). Harga total (nominal usulan) akan dihitung secara otomatis oleh sistem melalui perkalian antara Volume dan Harga Satuan.

---

## Alur Bisnis Baru

1. **Input Form (Operator)**:
   * Operator mengisi:
     * **Volume** (angka, mendukung pecahan desimal seperti 1.5).
     * **Satuan** (teks bebas, misal: *Rim*, *Pcs*, *Bulan*, *Orang*).
     * **Harga Satuan** (angka nominal rupiah).
   * Sistem secara otomatis mengalikan **Volume** dengan **Harga Satuan** menggunakan JavaScript di peramban dan menampilkan hasilnya di input **Harga Total** (read-only).
2. **Penyimpanan (Backend)**:
   * Kontroler menerima `volume`, `satuan`, dan `harga_satuan`.
   * Kontroler menghitung total harga (`nominal_request = volume * harga_satuan`).
   * Kontroler memvalidasi dan menyimpan keempat nilai tersebut ke basis data.
3. **Tampilan (Dashboard)**:
   * Pada halaman kerja (workboard) Operator dan ulasan Supervisor, rincian biaya akan menampilkan rincian perkalian kuantitas di bawah deskripsi item (contoh: `100 Rim x Rp 50.000`).

---

## Usulan Perubahan Database

### Modifikasi Tabel Rincian Belanja (`rba_details`)
Menambahkan kolom baru:
* `volume`: `DECIMAL(12, 2)` (default `1.00`), mendukung kuantitas desimal.
* `satuan`: `VARCHAR(50)` (default `Pkt` / Packet, nullable).
* `harga_satuan`: `DECIMAL(15, 2)` (default `0.00`).

Data lama yang sudah ada di database akan dimigrasi dengan aman:
* `volume` diatur menjadi `1`.
* `satuan` diatur menjadi `Pkt`.
* `harga_satuan` diatur senilai dengan `nominal_request` saat ini.

---

## Usulan Perubahan Program

### 1. Backend Layer (Models & Database)

#### [NEW] [Migration: Add volume fields to rba details](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_07_12_124600_add_volume_fields_to_rba_details.php)
* Menambahkan kolom `volume`, `satuan`, dan `harga_satuan` ke tabel `rba_details` dengan nilai default untuk kompatibilitas data lama.

#### [MODIFY] [RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php)
* Menambahkan `volume`, `satuan`, dan `harga_satuan` ke dalam array `$fillable`.
* Menambahkan cast tipe data di method `casts()`:
  * `'volume' => 'float'`
  * `'harga_satuan' => 'decimal:2'`

---

### 2. Controller & Validation Layer

#### [MODIFY] [DetailController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
* **Method `store` & `update`**:
  * Ubah aturan validasi request:
    * `volume` $\rightarrow$ `required|numeric|min:0.01`
    * `satuan` $\rightarrow$ `required|string|max:50`
    * `harga_satuan` $\rightarrow$ `required|numeric|min:0`
    * Hapus input `nominal_request` dari daftar validasi manual.
  * Tambahkan kalkulasi otomatis sebelum proses `create` / `update` dilakukan:
    `$validated['nominal_request'] = $validated['volume'] * $validated['harga_satuan'];`

---

### 3. Frontend Layer (Views & JS)

#### [MODIFY] [create.blade.php (Operator)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/details/create.blade.php)
* Ganti input `nominal_request` dengan komponen form baru:
  * **Volume**: `<input type="number" name="volume" id="volume" step="0.01" min="0.01" required>`
  * **Satuan**: `<input type="text" name="satuan" id="satuan" placeholder="Contoh: Rim, Pcs" required>`
  * **Harga Satuan**: `<input type="number" name="harga_satuan" id="harga_satuan" min="0" required>`
  * **Harga Total (Nominal Usulan)**: `<input type="number" id="nominal_request" class="bg-gray-100 cursor-not-allowed" readonly>`
* Tambahkan skrip JavaScript sederhana untuk mendengarkan perubahan nilai pada input `#volume` dan `#harga_satuan`, mengalikannya, lalu memasukkan hasilnya ke `#nominal_request`.

#### [MODIFY] [edit.blade.php (Operator)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/details/edit.blade.php)
* Ubah input seperti pada form `create.blade.php`.
* Isi nilai awal menggunakan `$detail->volume`, `$detail->satuan`, dan `$detail->harga_satuan`.
* Pasang skrip JavaScript kalkulasi otomatis yang sama.

#### [MODIFY] [show.blade.php (Operator & Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)
* Tampilkan teks rincian di bawah deskripsi item belanja pada kolom Deskripsi agar baris tabel tetap bersih dan rapi:
  ```html
  <td class="px-4 py-2 text-sm">
      {{ $detail->description }}
      <div class="text-xs text-gray-500 mt-0.5 font-medium">
          Rincian: {{ number_format($detail->volume, 2, ',', '.') }} {{ $detail->satuan }} x Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}
      </div>
  </td>
  ```
* Terapkan modifikasi serupa pada file review Supervisor ([show.blade.php Supervisor](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)).

---

## Rencana Verifikasi

### Pengujian Otomatis (PHPUnit)
Memperbarui berkas pengujian agar mencerminkan parameter masukan yang baru:
* Modifikasi data parameter di `RbaDetailTest.php` dan `RbaDetailFeaturesTest.php` (ganti parameter `nominal_request` dengan `volume`, `satuan`, dan `harga_satuan`).
* Jalankan pengujian otomatis untuk memastikan tidak ada regresi:
  ```bash
  php artisan test
  ```

### Pengujian Manual
1. Buka form tambah rincian belanja.
2. Masukkan Volume = `10` dan Harga Satuan = `15000`. Pastikan Harga Total otomatis terisi `150000` secara real-time.
3. Simpan data, lalu periksa apakah rincian tampil sebagai `10,00 Rim x Rp 15.000` di halaman workboard operator maupun supervisor.
