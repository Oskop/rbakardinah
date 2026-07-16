# Implementation Plan - Supervisor RBA Details Volume, Satuan & Harga Satuan Columns

Rencana implementasi ini bertujuan untuk memodifikasi tabel rincian biaya usulan pada halaman detail RBA Supervisor agar menampilkan kolom **Volume**, **Satuan**, dan **Harga Satuan** secara terpisah, menggantikan tampilan rincian teks inline sebelumnya. Hal ini menyelaraskan tampilan Supervisor dengan tampilan Operator yang sudah diimplementasikan sebelumnya.

---

## Alur Bisnis & Tampilan Baru

1. **Tampilan Tabel (Supervisor)**:
   * Kolom baru **Volume**, **Satuan**, dan **Harga Satuan** ditambahkan ke dalam tabel rincian biaya usulan.
   * Data dari kolom `volume`, `satuan`, dan `harga_satuan` akan langsung ditampilkan pada masing-masing kolom baru tersebut.
   * Format teks rincian inline di bawah deskripsi (`Rincian: X Y x Rp Z`) dihapus untuk menjaga kebersihan dan estetika visual tabel.
   * Colspan pada baris kosong disesuaikan dari 8 menjadi 11 kolom.

---

## Usulan Perubahan Program

### 1. Frontend Layer (Views)

#### [MODIFY] [show.blade.php (Supervisor)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
* **Table Header (`<thead>`)**:
  Menyisipkan kolom baru setelah **Deskripsi**:
  ```html
  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rekening</th>
  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Volume</th>
  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Harga Satuan</th>
  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase text-right">Usulan</th>
  ...
  ```

* **Table Body (`<tbody>`)**:
  * Menghapus div detail rincian inline di bawah `$detail->description`:
    ```html
    <!-- SEBELUMNYA -->
    <td class="px-4 py-2 text-sm">
        {{ $detail->description }}
        <div class="text-[11px] text-gray-500 mt-0.5 font-medium">
            Rincian: {{ number_format($detail->volume, 2, ',', '.') }} {{ $detail->satuan }} x Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}
        </div>
    </td>

    <!-- MENJADI -->
    <td class="px-4 py-2 text-sm">
        {{ $detail->description }}
    </td>
    ```
  * Menambahkan tiga kolom `<td>` baru setelah kolom Deskripsi:
    ```html
    <td class="px-4 py-2 text-sm text-right">
        {{ number_format($detail->volume, 2, ',', '.') }}
    </td>
    <td class="px-4 py-2 text-sm">
        {{ $detail->satuan }}
    </td>
    <td class="px-4 py-2 text-sm text-right">
        Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}
    </td>
    ```
  * Menyesuaikan `colspan` pada baris kosong:
    ```html
    <tr>
        <td colspan="11" class="px-4 py-8 text-center text-gray-500 italic">Belum ada rincian belanja.</td>
    </tr>
    ```

---

## Rencana Verifikasi

### Pengujian Otomatis (PHPUnit)
* Jalankan seluruh rangkaian test suite untuk memastikan tidak ada pengujian yang terpengaruh/gagal:
  ```bash
  php artisan test
  ```

### Pengujian Manual
1. Login sebagai Supervisor.
2. Buka halaman detail salah satu submission RBA.
3. Periksa visualisasi tabel rincian biaya:
   * Pastikan kolom Volume, Satuan, dan Harga Satuan tampil secara terpisah.
   * Pastikan format angka dan penyelarasan teks (kanan/kiri) sudah sesuai.
   * Pastikan tidak ada lagi teks rincian inline di bawah deskripsi item.
