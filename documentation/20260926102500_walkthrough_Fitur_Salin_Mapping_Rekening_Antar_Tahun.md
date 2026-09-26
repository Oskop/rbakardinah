# Walkthrough: Fitur Salin Mapping Rekening Antar Tahun

Fitur **Salin Mapping Rekening Antar Tahun** telah selesai diimplementasikan pada halaman **Manajemen Mapping Rekening Sub-Unit** (`admin/sub-unit-account-codes`). Fitur ini memungkinkan administrator untuk menduplikasi konfigurasi mapping nomor rekening belanja dari tahun anggaran sebelumnya (misalnya tahun 2027) ke tahun anggaran berikutnya (misalnya tahun 2028 atau seterusnya) secara cepat, aman, dan anti-duplikasi.

---

## 🛠️ Komponen yang Diimplementasikan

### 1. Rute Baru (`routes/web.php`)
Menambahkan rute POST untuk menangani request penyalinan antar tahun:
- **URL**: `/admin/sub-unit-account-codes/copy-year`
- **Method**: `POST`
- **Nama Rute**: `admin.sub-unit-account-codes.copy-year`
- **Controller Action**: `Admin\SubUnitAccountCodeController@copyYear`

### 2. Controller Action (`app/Http/Controllers/Admin/SubUnitAccountCodeController.php`)
Menambahkan method `copyYear(Request $request)` dengan kapabilitas:
- **Validasi Input**:
  - `source_year`: Wajib, harus ada di database.
  - `target_year`: Wajib, berupa 4 digit angka string tahun (contoh: `2028`).
  - `sub_unit_id`: Opsional (jika kosong, menyalin seluruh sub-unit kerja).
- **Anti-Duplikasi & Anti-Overwrite**:
  - Menggunakan metode `firstOrCreate` berbasis `sub_unit_id`, `account_code_id`, dan `fiscal_year`.
  - Jika relasi rekening sudah ada di tahun tujuan, data eksisting tidak akan disentuh/ditimpa.
- **Pencatatan Log Otomatis**:
  - Setiap record yang baru disalin otomatis tercatat ke tabel `activity_logs` melalui trait `LogsActivity` pada model `SubUnitAccountCode`.
- **Umpan Balik / Flash Message**:
  - Menampilkan jumlah record yang berhasil disalin serta informasi berapa record yang dilewati jika sudah ada sebelumnya.

### 3. Tampilan & Antarmuka (`resources/views/admin/sub_unit_account_codes/index.blade.php`)
- **Tombol Aksi Header**:
  - Menambahkan tombol berwarna amber: `📋 Salin Antar Tahun` di baris aksi header bersama tombol *Bulk Assign* dan *Tambah Mapping*.
- **Modal Interaktif (`modal-copy-year`)**:
  - Pilihan **Tahun Sumber (Asal)**: Dropdown dinamis dari tahun anggaran yang tersimpan di sistem.
  - Input **Tahun Anggaran Tujuan**: Input teks 4 digit tahun (default otomatis terisi tahun berikutnya).
  - Pilihan **Cakupan Sub-Unit Kerja**: Pilihan opsional apakah menyalin untuk semua sub-unit kerja sekaligus atau hanya sub-unit tertentu.
  - Banner informasi keamanan data (anti-duplikasi).
- **Fungsi JavaScript**:
  - `openCopyYearModal()` dan `closeCopyYearModal()` berbasis Vanilla JS murni.

---

## 🧪 Hasil Verifikasi & Pengujian

Pengujian otomatis telah dijalankan melalui skrip pengujian Laravel kernel (`test_copy_year_feature.php`):

| Skenario Pengujian | Hasil yang Diharapkan | Hasil Aktual | Status |
| :--- | :--- | :--- | :--- |
| **Salin Semua Mapping (2027 ke 2028)** | 74 mapping rekening tersalin ke 2028 | 74 mapping baru tersalin | ✅ PASSED |
| **Pencatatan Activity Log** | 74 log aksi terekam di `activity_logs` | 74 log terverifikasi tercatat | ✅ PASSED |
| **Idempotensi / Anti-Duplikasi** | 0 mapping baru, 74 dilewati | 0 baru, 74 dilewati | ✅ PASSED |
| **Salin Sub-Unit Spesifik (ke 2029)** | Hanya rekening sub-unit tersebut (17 rek) | 17 mapping baru tersalin | ✅ PASSED |
| **Validasi Render View Blade** | View berhasil di-render tanpa syntax error | Render sukses (629 KB HTML) | ✅ PASSED |

---

## 💻 Panduan Penggunaan bagi Pengguna
1. Buka halaman **Manajemen Mapping Rekening Sub-Unit** (`/admin/sub-unit-account-codes`).
2. Klik tombol **📋 Salin Antar Tahun** di sudut kanan atas tabel.
3. Pada modal yang muncul:
   - Pilih **Tahun Sumber** (contoh: `Tahun 2027`).
   - Masukkan **Tahun Tujuan** (contoh: `2028`).
   - (Opsional) Pilih **Sub-Unit Kerja** jika hanya ingin menyalin unit tertentu, atau biarkan default untuk menyalin seluruh sub-unit sekaligus.
4. Klik tombol **📋 Salin Mapping Sekarang**.
5. Sistem akan menampilkan notifikasi jumlah mapping yang berhasil disalin.
