# Rencana Implementasi: Fitur Berita Acara (BA) Asistensi / Desk RBA Operator

Dokumen ini merancang implementasi fitur **Berita Acara (BA) Asistensi / Desk RBA** untuk kegiatan verifikasi lapangan antara Tim Teknis SIPAKAR dan Operator Sub-Unit. Fitur ini mencakup input parameter sebelum cetak, perhitungan komparasi rekening belanja otomatis (Awal vs Perubahan vs Selisih), template cetak resmi A4, pengunggahan dokumen bertanda tangan dengan sistem **versioning**, serta pencatatan otomatis pada **Log Data (Activity Log)**.

---

## 1. Analisis Kebutuhan & Dokumen Acuan

Berdasarkan analisis berkas acuan [`raw/BA Unit PDE.docx`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/raw/BA%20Unit%20PDE.docx) dan masukan pengguna:

1. **Judul Dokumen**:
   ```text
   BERITA ACARA ASISTENSI / DESK
   RENCANA ANGGARAN BELANJA (RAB) {PERIODE RBA}
   TAHUN ANGGARAN {TAHUN RBA}
   ```
2. **Kop & Paragraf Pembuka**:
   - Memuat hari pelaksanaan (misal *"Sabtu"*), pengejaan tanggal (misal *"tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam"*), tanggal digit format `DD-MM-YYYY` (misal *"05-09-2026"*), ruang desk (misal *"Ruang RA. Kardinah"*), serta Nama Sub Bagian / Instalasi / Unit.
3. **Tabel Komparasi Rekening Belanja**:
   - Kolom: `NO`, `REKENING BELANJA`, `AWAL`, `PERUBAHAN`, `SELISIH (+/-)`.
   - Menghitung agregat usulan belanja rincian (`rba_details`) operator pengusul per rekening belanja (`account_code`), dengan komparasi terhadap periode RBA sebelumnya (misal RBA Murni jika saat ini RBA Perubahan).
   - Baris `TOTAL` akumulasi di baris terbawah.
4. **Catatan Desk & Checklist Evaluasi Dinamis**:
   - Catatan hasil asistensi / desk.
   - Pertanyaan kriteria:
     - Usulan melalui SIPAKAR: `Ya` / `Tidak`
     - Latar belakang sudah memenuhi 3 kriteria: `Ya` / `Tidak` / `Perlu Perbaikan`
       > [!IMPORTANT]
       > **Kolom Catatan Perbaikan Latar Belakang**: Saat opsi *"Perlu Perbaikan"* dipilih, formulir secara interaktif menampilkan kolom teks bebas untuk mencatat poin-poin spesifik latar belakang yang perlu diperbaiki oleh operator. Teks ini akan ikut dicetak pada lembar Berita Acara.
     - Dokumen RAB diupload pada sistem: `Ya` / `Tidak`
5. **Kolom Tanda Tangan Dinamis**:
   - **Kiri (Tim Asistensi)**: Daftar nama anggota tim asistensi teknis SIPAKAR (dapat ditambah/diedit dinamis).
   - **Kanan (Sub Unit Asistensi)**: Daftar nama operator / pimpinan sub-unit kerja terkait.
6. **Sifat Kepemilikan Dokumen**:
   - Bersifat **per operator pengusul dalam periode RBA submission** (analog dengan `RbaSubmissionOperatorBackground`).
7. **Pengunggahan Scan & Versioning**:
   - Setelah dicetak dan ditandatangani manual, operator atau tim teknis dapat mengunggah berkas scan PDF (Versi 1).
   - Jika terdapat revisi BA dan perlu unggah ulang, versi otomatis naik (Versi 2, Versi 3, dst.), riwayat versi tetap dapat diakses dan diunduh.
8. **Audit Trail**:
   - Seluruh aktivitas pembuatan, edit parameter, dan pengunggahan versi dokumen BA otomatis tercatat di `ActivityLog`.

---

## 2. Perancangan Database & Model

### A. Migrasi Baru: `create_rba_desk_verifications_tables.php`

1. **Tabel `rba_desk_verifications`**:
   - `id`: Primary Key
   - `rba_submission_id`: Foreign Key ke `rba_submissions` (cascade delete)
   - `user_id`: Foreign Key ke `users` (operator pengusul / pemilik usulan)
   - `hari`: string (misal: "Sabtu")
   - `tanggal_desk`: date (misal: "2026-09-05")
   - `tanggal_desk_spelled`: string (ejaan terbilang tanggal)
   - `ruang_desk`: string (misal: "Ruang RA. Kardinah")
   - `sub_unit_name`: string (nama sub unit yang diasistensi)
   - `catatan`: text (nullable, catatan umum asistensi)
   - `is_usulan_sipakar`: enum ('Ya', 'Tidak'), default 'Ya'
   - `kriteria_latar_belakang`: enum ('Ya', 'Tidak', 'Perlu Perbaikan'), default 'Ya'
   - `catatan_perbaikan_latar_belakang`: text (nullable, kolom teks bebas saat kriteria latar belakang dipilih 'Perlu Perbaikan')
   - `is_dokumen_rab_uploaded`: enum ('Ya', 'Tidak'), default 'Ya'
   - `tim_asistensi`: json (daftar nama anggota tim asistensi, misal `["M. Riza F., A.Md.", "Ananta Bayu, S.Kom", "Nurul L. R., S.I.Pus."]`)
   - `anggota_sub_unit`: json (daftar nama anggota sub-unit yang menandatangani)
   - `created_by`: Foreign Key ke `users` (siapa yang membuat/menyimpan)
   - `timestamps()`
   - Unique Constraint: `['rba_submission_id', 'user_id']` (1 operator per submission memiliki 1 data BA)

2. **Tabel `rba_desk_verification_documents`**:
   - `id`: Primary Key
   - `rba_desk_verification_id`: Foreign Key ke `rba_desk_verifications` (cascade delete)
   - `version_number`: unsigned integer (1, 2, 3...)
   - `file_path`: string (path penyimpanan berkas PDF di storage)
   - `original_filename`: string
   - `notes`: string (nullable, catatan versi revisi)
   - `uploaded_by`: Foreign Key ke `users`
   - `timestamps()`

### B. Domain Models
- [`app/Models/RbaDeskVerification.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDeskVerification.php): Relasi ke `submission`, `user`, `creator`, `documents`, dan `latestDocument`. Menampung `$fillable` termasuk `catatan_perbaikan_latar_belakang`. Menggunakan trait `LogsActivity`.
- [`app/Models/RbaDeskVerificationDocument.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDeskVerificationDocument.php): Relasi ke `deskVerification` dan `uploader`. Menggunakan trait `LogsActivity`.
- Helper Date Speller: Helper internal untuk konversi otomatis tanggal `2026-09-05` menjadi Hari `"Sabtu"` dan Pengejaan `"tanggal Lima Bulan September Tahun Dua Ribu Dua Puluh Enam"`.

---

## 3. Perancangan Backend Controller & Layanan

### A. Controller: `Operator\DeskVerificationController`
- `showForm(RbaSubmission $submission)`:
  - Mengambil data eksisting BA atau menyusun nilai default cerdas (hari ini, nama tim asistensi default, nama sub-unit operator).
- `storeOrUpdate(Request $request, RbaSubmission $submission)`:
  - Validasi input form parameter (hari, tanggal, ejaan, ruang, checklist kriteria, `catatan_perbaikan_latar_belakang` (wajib diisi jika kriteria adalah 'Perlu Perbaikan'), array tim asistensi, array anggota sub unit).
  - Simpan/Perbarui record di `rba_desk_verifications` untuk operator aktif (`user_id`).
- `print(Request $request, RbaSubmission $submission, ?User $operator = null)`:
  - Mempersiapkan data komparasi rekening belanja:
    - Agregasi rincian usulan belanja (`rba_details`) operator pengusul per `AccountCode`.
    - Mengambil nilai `AWAL` dari header periode sebelumnya (misal RBA Murni tahun bersangkutan jika saat ini RBA Perubahan).
    - Menghitung `PERUBAHAN` dan `SELISIH (+/-)`.
  - Mengirim status kriteria dan teks perbaikan latar belakang jika ada.
  - Merender view cetak portrait resmi [`reports/berita_acara_desk_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/berita_acara_desk_print.blade.php).
- `uploadSignedDocument(Request $request, RbaSubmission $submission)`:
  - Validasi berkas PDF (maksimal 10MB).
  - Hitung nomor versi baru (`latestVersion + 1`).
  - Simpan ke `storage/app/public/berita_acara/`.
  - Simpan ke `rba_desk_verification_documents`.
- `history(RbaSubmission $submission, ?User $operator = null)`:
  - Menampilkan riwayat seluruh versi dokumen BA yang telah diunggah untuk dapat diunduh ulang.

### B. Integrasi Trait `LogsActivity` & `ActivityLogController`
- Memperbarui [`app/Traits/LogsActivity.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php):
  - Menambahkan generator deskripsi khusus untuk `RbaDeskVerification` ("membuat/memperbarui Berita Acara Asistensi / Desk RBA untuk [Sub Unit]") dan `RbaDeskVerificationDocument` ("mengunggah berkas Berita Acara Ditandatangani Versi X").
- Memperbarui [`app/Http/Controllers/Admin/ActivityLogController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/ActivityLogController.php):
  - Menambahkan label filter model: `'RbaDeskVerification' => 'Berita Acara Asistensi Desk'` dan `'RbaDeskVerificationDocument' => 'Dokumen Berita Acara Ditandatangani'`.

---

## 4. Perancangan Tampilan Antarmuka (Views)

1. **Card Berita Acara di Workboard Operator** ([`resources/views/operator/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)):
   - Kartu khusus *"Berita Acara Asistensi / Desk RBA"*:
     - Menampilkan indikator status (*Belum Dibuat / Siap Cetak*).
     - Tombol *"Atur & Isi Parameter Berita Acara"* (membuka modal / form interaktif):
       - Pemilih hari, tanggal, ejaan terbilang (auto-generated dengan opsi override), dan ruang desk.
       - Checklist 3 kriteria:
         - Saat opsi *"Perlu Perbaikan"* pada Latar Belakang dipilih, textarea *"Catatan Perbaikan Latar Belakang"* otomatis tampil secara dinamis menggunakan Alpine.js (`x-show="kriteriaLatarBelakang === 'Perlu Perbaikan'"`).
       - Daftar dinamis Anggota Tim Asistensi (+ Tambah / - Hapus) dan Anggota Sub Unit.
     - Tombol *"Cetak Berita Acara"* (membuka halaman print preview baru).
     - Bagian Unggah Scan BA Ditandatangani:
       - Status berkas (*Belum Diunggah* atau *Versi X Aktif*).
       - Tombol unduh berkas aktif.
       - Form unggah berkas PDF baru / revisi baru.
       - Tautan *"Lihat Riwayat Versi Berita Acara"*.
2. **Template Cetak Resmi Berita Acara** ([`resources/views/reports/berita_acara_desk_print.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/reports/berita_acara_desk_print.blade.php)):
   - Format standar A4 Portrait.
   - Sesuai persis dengan dokumen [`raw/BA Unit PDE.docx`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/raw/BA%20Unit%20PDE.docx):
     - Judul: `BERITA ACARA ASISTENSI / DESK RENCANA ANGGARAN BELANJA (RAB) {PERIODE} TAHUN ANGGARAN {TAHUN}`.
     - Paragraf pembuka: Hari, tanggal ejaan, tanggal digit, ruang desk, nama sub unit.
     - Tabel rekening belanja: `NO`, `REKENING BELANJA`, `AWAL`, `PERUBAHAN`, `SELISIH (+/-)` dan baris `TOTAL`.
     - Catatan desk dan 3 butir kriteria checklist:
       - Memuat penandaan status kriteria latar belakang (*Ya / Tidak / Perlu Perbaikan*).
       - Jika berstatus *Perlu Perbaikan*, langsung menampilkan blok rincian:
         *Catatan Perbaikan Latar Belakang: [Rincian catatan perbaikan]*
     - Kolom tanda tangan 2 sisi: Kiri (Tim Asistensi) dan Kanan (Sub Unit Asistensi).
     - Toolbar print preview (*Cetak / Simpan PDF, Kembali*).
3. **Akses Review Supervisor & Admin**:
   - Supervisor pada [`resources/views/supervisor/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php) dapat melihat status BA per operator, membaca catatan perbaikan latar belakang jika ada, mencetak BA, dan mengunduh berkas scan yang sudah diunggah.

---

## 5. Rencana Pengujian (Verification Plan)

### Automated Tests (`tests/Feature/Operator/BeritaAcaraTest.php`)
- `test_operator_can_save_and_update_berita_acara_parameters()`: Memverifikasi penyimpanan parameter BA per operator, termasuk field `catatan_perbaikan_latar_belakang` saat opsi 'Perlu Perbaikan' dipilih.
- `test_operator_can_view_print_preview_berita_acara_with_correct_data()`: Memverifikasi perhitungan komparasi rekening (Awal, Perubahan, Selisih), teks judul/tanggal, dan penampilan catatan perbaikan latar belakang pada cetakan.
- `test_operator_can_upload_signed_berita_acara_document_and_increments_version()`: Memverifikasi pengunggahan file scan dan kenaikan versi saat revisi diunggah.
- `test_activity_logs_records_berita_acara_creation_and_document_upload()`: Memverifikasi tercatatnya pembuatan BA dan upload dokumen di `activity_logs`.
- Full Regression Test: Memastikan seluruh 248 test suite tetap hijau (`php artisan test`).

### Manual Verification
- Buka workboard submission operator.
- Buka modal parameter BA, pilih opsi kriteria latar belakang *"Perlu Perbaikan"*, pastikan textarea catatan perbaikan muncul dan isi deskripsinya.
- Simpan dan klik *"Cetak Berita Acara"*, verifikasi catatan perbaikan tampil pada lembar cetak A4.
- Unggah file scan bertanda tangan (V1), lalu coba unggah revisi (V2) dan periksa riwayat versinya.
- Periksa menu Log Data Admin untuk memastikan seluruh aktivitas terekam.
