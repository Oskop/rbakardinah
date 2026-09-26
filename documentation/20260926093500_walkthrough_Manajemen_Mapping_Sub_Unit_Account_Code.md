# Walkthrough: Pembangunan Halaman Manajemen Mapping Sub Unit dengan Nomor Rekening (Admin SIPAKAR)

## 1. Ringkasan Eksekusi
Modul **Manajemen Mapping Rekening Sub-Unit** telah berhasil dibuat, diintegrasikan ke menu **Master Data** panel Administrator SIPAKAR, dan diverifikasi fungsionalitasnya secara menyeluruh.

### File-File yang Dibuat & Diperbarui:
1. **Controller**: [SubUnitAccountCodeController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/SubUnitAccountCodeController.php)
   - Method `index`: Menampilkan daftar mapping, ringkasan statistik, dan master data pendukung.
   - Method `store`: Menambahkan relasi mapping rekening satuan.
   - Method `bulkStore`: Fitur unggulan untuk menambahkan banyak rekening sekaligus ke satu sub-unit.
   - Method `update`: Mengubah catatan khusus atau memindahkan sub-unit mapping.
   - Method `destroy`: Menghapus relasi mapping.
2. **Rute Web**: [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
   - `admin.sub-unit-account-codes.index` (`GET /admin/sub-unit-account-codes`)
   - `admin.sub-unit-account-codes.store` (`POST /admin/sub-unit-account-codes`)
   - `admin.sub-unit-account-codes.bulk-store` (`POST /admin/sub-unit-account-codes/bulk-store`)
   - `admin.sub-unit-account-codes.update` (`PUT /admin/sub-unit-account-codes/{id}`)
   - `admin.sub-unit-account-codes.destroy` (`DELETE /admin/sub-unit-account-codes/{id}`)
3. **Antarmuka Pengguna (View Blade)**: [resources/views/admin/sub_unit_account_codes/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/sub_unit_account_codes/index.blade.php)
   - 4 Kartu Statistik Cepat (*Total Mapping, Sub-Unit Terpetakan, Rekening Terpetakan, Tahun Anggaran*).
   - Toolbar Filter Interaktif (*Sub-Unit Kerja, Kelompok Belanja, Tahun Anggaran, dan tombol Reset Filter*).
   - Tabel DataTables responsif dengan badge kelompok belanja dan pencarian cerdas.
   - Modal 1: **Tambah Mapping Satuan**.
   - Modal 2: **Bulk Assign Rekening** lengkap dengan filter pencarian instan dan checkbox select all/unselect.
   - Modal 3: **Edit Mapping**.
4. **Navigasi Menu**: [resources/views/layouts/navigation.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/layouts/navigation.blade.php)
   - Ditambahkan tautan menu `🔗 Mapping Rekening Sub-Unit` pada dropdown **Master Data** desktop maupun menu mobile.
5. **Integrasi Log Data**:
   - Setiap aksi penambahan, perubahan, dan penghapusan otomatis menghasilkan entri audit log di menu **Log Data (`/admin/logs`)**.

---

## 2. Hasil Pengujian & Verifikasi

### A. Pengujian Rute
Perintah `php artisan route:list --name=sub-unit-account-codes` mengonfirmasi 5 rute aktif:
```text
GET|HEAD   admin/sub-unit-account-codes ............. admin.sub-unit-account-codes.index
POST       admin/sub-unit-account-codes ............. admin.sub-unit-account-codes.store
POST       admin/sub-unit-account-codes/bulk-store .. admin.sub-unit-account-codes.bulk-store
PUT|PATCH  admin/sub-unit-account-codes/{id} ........ admin.sub-unit-account-codes.update
DELETE     admin/sub-unit-account-codes/{id} ........ admin.sub-unit-account-codes.destroy
```

### B. Pengujian Render Halaman Blade
Simulasi request Admin pada `SubUnitAccountCodeController@index`:
* Status: **Berhasil render 100% tanpa error** (ukuran HTML yang dihasilkan: ~589 KB).
* Metrik yang diteruskan ke view:
  - Total Mappings: **74**
  - Total Sub-Units: **13**
  - Total Accounts: **74**
  - Tahun Anggaran: **2027**

### C. Pengujian Siklus CRUD & Pencatatan Log Data
Pengujian otomatis seluruh aksi (Tambah, Update, Hapus):
1. **Tambah (Store)**:
   - Membuat relasi mapping baru berhasil.
   - Log tercatat: *"Admin (Administrator) menambahkan mapping rekening [5.1.01.03.06.0001] Belanja Insentif Jasa Pelayanan Kesehatan bagi ASN ke SPI (Tahun 2027)"*.
2. **Ubah (Update)**:
   - Memperbarui keterangan khusus berhasil.
   - Log tercatat: *"Admin (Administrator) memperbarui mapping rekening [5.1.01.03.06.0001] Belanja Insentif Jasa Pelayanan Kesehatan bagi ASN ke SPI (Tahun 2027)"*.
3. **Hapus (Destroy)**:
   - Menghapus relasi mapping berhasil.
   - Log tercatat: *"Admin (Administrator) menghapus mapping rekening [5.1.01.03.06.0001] Belanja Insentif Jasa Pelayanan Kesehatan bagi ASN ke SPI (Tahun 2027)"*.
4. **Kondisi Akhir Database**:
   - Total 74 mapping awal acuan Tahun 2027 tetap utuh dan stabil.

---

## 3. Cara Penggunaan untuk Administrator

1. **Mengakses Halaman**:
   - Login sebagai akun Administrator (misal: `admin@hospital.com`).
   - Pada bar navigasi atas, klik menu dropdown **Master Data** $\rightarrow$ pilih **🔗 Mapping Rekening Sub-Unit**.
   - URL langsung: `http://localhost:8000/admin/sub-unit-account-codes`.
2. **Menyaring Data**:
   - Gunakan dropdown **Sub-Unit Kerja** untuk melihat daftar rekening yang dipegang oleh unit tertentu (contoh: pilih *Unit PDE* untuk melihat 10 rekening IT).
   - Gunakan dropdown **Kelompok Belanja** untuk memfilter rekening *Belanja Pegawai*, *Belanja Barang dan Jasa*, atau *Belanja Modal*.
   - Klik tombol **Reset Semua Filter** untuk mengembalikan tampilan semula.
3. **Menambahkan Mapping Sekaligus (Bulk Assign)**:
   - Klik tombol hijau **⚡ Bulk Assign Rekening**.
   - Pilih Sub-Unit tujuan dan tahun anggaran.
   - Gunakan kotak pencarian untuk menemukan rekening yang diinginkan, centang beberapa rekening, lalu klik **Simpan Semua Terpilih**.
4. **Mengubah Catatan Khusus**:
   - Klik ikon pensil ✏️ pada baris yang diinginkan untuk mengubah keterangan khusus RSUD.
5. **Memantau Riwayat di Log Data**:
   - Buka menu **Log Data** (`/admin/logs`), pilih filter model **Mapping Rekening ke Sub Unit** untuk melihat jejak audit siapa yang mengubah atau menambah mapping tersebut.
