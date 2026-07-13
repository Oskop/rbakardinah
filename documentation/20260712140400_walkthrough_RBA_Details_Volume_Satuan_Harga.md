# Walkthrough - RBA Details Volume, Satuan & Harga Satuan

Rencana implementasi untuk memodifikasi struktur data dan alur input rincian belanja (`rba_details`) dengan menambahkan kuantitas detail (Volume, Satuan, dan Harga Satuan) telah sukses diwujudkan. Harga total (nominal usulan) kini secara otomatis dihitung dan disimpan oleh sistem.

---

## Detil Perubahan

### 1. Database & Migrasi
* **[2026_07_12_124600_add_volume_fields_to_rba_details.php](file:///c:/Users/PC12/Project/rbakardinah/database/migrations/2026_07_12_124600_add_volume_fields_to_rba_details.php)**:
  * Menambahkan kolom `volume` (`DECIMAL(12, 2)`), `satuan` (`VARCHAR(50)`), dan `harga_satuan` (`DECIMAL(15, 2)`) ke tabel `rba_details`.
  * Menyertakan query migrasi data lama agar kompatibel secara langsung:
    * `volume` lama diatur ke `1.00`.
    * `satuan` lama diatur ke `'Pkt'`.
    * `harga_satuan` lama diatur senilai `nominal_request` sebelumnya.

### 2. Model Eloquent
* **[RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php)**:
  * Kolom `volume`, `satuan`, dan `harga_satuan` telah ditambahkan ke properti `$fillable`.
  * Melakukan casting tipe data: `'volume' => 'float'` dan `'harga_satuan' => 'decimal:2'` untuk menjamin presisi nominal.

### 3. Logika Backend & Validasi
* **[DetailController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DetailController.php)**:
  * Menghapus input manual `nominal_request` dari rules validator pada method `store` dan `update`.
  * Menambahkan rules validasi untuk `volume` (wajib, numerik, minimal 0.01), `satuan` (wajib, string, max 50), dan `harga_satuan` (wajib, numerik, minimal 0).
  * Menambahkan proses kalkulasi otomatis: `$validated['nominal_request'] = $validated['volume'] * $validated['harga_satuan']` tepat sebelum data disimpan ke database.

### 4. Perubahan Tampilan & JavaScript
* **[create.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/details/create.blade.php) & [edit.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/details/edit.blade.php)**:
  * Input manual nominal usulan digantikan oleh tiga field input sejajar (Volume, Satuan, Harga Satuan) dan satu field read-only Harga Total.
  * Dilengkapi script JavaScript real-time untuk memproses perkalian `Volume * Harga Satuan` begitu operator mengetikkan nilai pada form.
* **show.blade.php Operator ([operator/submissions/show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)) & Supervisor ([supervisor/submissions/show.blade.php](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php))**:
  * Menampilkan visual breakdown di kolom Deskripsi (contoh: `Rincian: 10,00 Pcs x Rp 50.000`) di bawah teks nama item utama agar mempermudah pemeriksaan anggaran.

---

## Hasil Verifikasi & Pengujian

### Pengujian Otomatis (PHPUnit)
Seluruh pengujian fungsionalitas rincian belanja pada unit test dan feature test telah diperbarui agar menggunakan parameter baru (`volume`, `satuan`, `harga_satuan`). Semua test case berhasil diselesaikan tanpa ada kegagalan (`44 passed, 0 failed`):

```bash
php artisan test
```
- **`test_operator_can_create_rba_detail_with_pdf`**: Berhasil memproses penyimpanan dan verifikasi hasil perkalian total di basis data.
- **`test_operator_cannot_add_detail_if_background_is_empty`**: Tetap berfungsi dengan validasi kuantitas yang baru.
- **`test_operator_can_upload_new_version_of_pdf`**, **`test_operator_can_submit_item_to_supervisor`**, dll.: Berhasil lolos pengujian dengan format data `rba_details` yang baru.
