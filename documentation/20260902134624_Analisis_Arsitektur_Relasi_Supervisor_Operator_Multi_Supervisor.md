# Analisis Arsitektur: Relasi Multi-Supervisor dan Multi-Operator dalam Satu Unit Kerja

Dokumen ini mendokumentasikan analisis arsitektur dan dampak teknis terkait skenario manajerial di mana terdapat lebih dari satu Supervisor (misal: Supervisor A dan Supervisor B) dan beberapa Operator (Operator A, B, C, D) di dalam satu unit kerja yang sama (misal: *Unit Perencanaan*).

---

## 1. Latar Belakang & Pertanyaan Arsitektural

### Skenario:
Di dalam sebuah unit kerja (misal: **Unit Perencanaan**):
- Terdapat **Supervisor A** dan **Supervisor B**.
- Terdapat **Operator A**, **Operator B**, **Operator C**, dan **Operator D**.
- Secara tugas riil di lapangan, diasumsikan Supervisor A membawahi Operator A & B, sedangkan Supervisor B membawahi Operator C & D.

### Pertanyaan Kunci:
1. Apakah di sistem saat ini Supervisor B juga membawahi Operator A & B?
2. Apakah Supervisor A juga membawahi Operator C & D?
3. Jika ingin dipisah secara tegas, apa dampak dan risiko dari masing-masing pendekatan teknis terhadap fitur-fitur yang sudah berjalan di aplikasi?

---

## 2. Kondisi Sistem Saat Ini (Existing Architecture)

Di aplikasi SIPAKAR RBA RSUD Kardinah saat ini:
- **Relasi Berbasis Unit Kerja (`unit_id`), Bukan Hirarki Perorangan:**
  - Tabel `users` hanya memiliki relasi `unit_id` ke tabel `units`.
  - Belum ada kolom `supervisor_id` atau tabel perantara penugasan (*assignment*).
- **Prinsip Utama Aplikasi:**
  $$\text{1 Unit Kerja} = \text{1 Dokumen Pengajuan RBA (RbaSubmission per Periode)}$$
- **Hak Akses Supervisor:**
  Query pada `ReviewController.php` memuat seluruh data berdasarkan kesamaan `unit_id`:
  ```php
  $operators = User::where('unit_id', Auth::user()->unit_id)
      ->where('role', 'Operator')
      ->where('is_active', true)
      ->get();
  ```
- **Kesimpulan Kondisi Eksisting:**
  **Ya, keduanya saling membawahi secara setara (*peer supervisors*).**
  Supervisor A dan Supervisor B sama-sama dapat melihat, me-review, dan memvalidasi seluruh usulan belanja maupun latar belakang milik Operator A, B, C, dan D.

---

## 3. Evaluasi Pendekatan 1: Pemisahan Menjadi Sub-Unit (di Master Data Unit)

### Konsep:
Unit Perencanaan dipecah menjadi dua unit administratif di master data `units`:
1. `Unit Perencanaan - Seksi Program` (Supervisor A, Operator A & B)
2. `Unit Perencanaan - Seksi Anggaran` (Supervisor B, Operator C & D)

### Dampak terhadap Fitur Eksisting:
1. **Stabilitas Kode (Zero Risk / Bebas Bug):**
   - **TIDAK ADA** perubahan kode logic backend pada `ReviewController`, `SubmissionController`, `DetailController`, maupun `PaguService`.
   - Seluruh 112 automated unit/feature tests tetap berjalan **100% PASS**.
2. **Alur Validasi & Status Submission:**
   - Bersih dan independen. Seksi Program memiliki status dokumen pengajuannya sendiri (`Draft` $\rightarrow$ `Pending` $\rightarrow$ `Validated`), demikian pula dengan Seksi Anggaran. Tidak ada risiko saling tunggu atau saling mengunci draf.
3. **Dokumen Pendukung & Laporan Cetak:**
   - Berkas KAK, RAK, dan RTP terisolasi rapi.
   - Lembar cetak RBA Final terbit per sub-unit dengan 1 slot tanda tangan supervisor yang valid dan definitif.
4. **Dashboard Administrator:**
   - Admin melihat 2 baris submission yang jelas progresnya dari masing-masing sub-unit.

---

## 4. Evaluasi Pendekatan 2: Penugasan Langsung (`supervisor_id` pada Tabel `users`)

### Konsep:
Tetap mempertahankan 1 Unit Perencanaan, namun menambahkan kolom `supervisor_id` (foreign key ke `users.id`) pada tabel `users`. Operator A & B di-assign ke Supervisor A, sedangkan Operator C & D di-assign ke Supervisor B.

### Dampak & Titik Kritis terhadap Fitur Eksisting:
> [!CAUTION]
> Pendekatan ini memiliki risiko benturan logika bisnis (*logic conflict*) yang tinggi jika tidak dilakukan perombakan (*refactoring*) menyeluruh.

1. **Konflik pada Status Dokumen Pengajuan (`rba_submissions.status_submission`):**
   - Karena hanya ada **1 baris submission** untuk Unit Perencanaan:
     - Jika Operator A & B sudah selesai dan menekan tombol *"Ajukan ke Supervisor"*, status pengajuan unit berubah menjadi `Pending Supervisor`.
     - **Masalah:** Operator C & D yang masih menyusun draf bisa terkunci dan tidak bisa mengedit rincian belanja mereka.
     - Jika Supervisor A menyetujui/memvalidasi pengajuan, apakah status submission unit berubah menjadi `Validated`? Jika ya, usulan Operator C & D dianggap sudah sah padahal belum diperiksa oleh Supervisor B.
2. **Kalkulasi Akumulasi Usulan vs Pagu Rekening:**
   - Pagu anggaran rumah sakit melekat pada kode rekening per periode.
   - Jika Supervisor A hanya ditampilkan usulan Operator A & B, Supervisor A tidak dapat mengetahui apakah total gabungan belanja (termasuk usulan Operator C & D) sudah melebihi pagu atau belum.
3. **Risiko Data Tercecer (Unassigned Operators):**
   - Jika ada operator baru yang belum dipetakan ke supervisor (`supervisor_id = null`), usulannya tidak akan muncul di layar supervisor mana pun (*data hilang dari radar review*).
4. **Ambiguitas Lembar Pengesahan / Cetak RBA Final:**
   - Format cetak resmi rumah sakit membutuhkan nama dan NIP Kepala Unit/Supervisor untuk tanda tangan. Jika dalam 1 unit terdapat 2 supervisor, sistem ambigu menentukan siapa penandatangan resmi laporan RBA unit tersebut.
5. **Kebutuhan Perombakan Kode Masif:**
   - Memerlukan migrasi database, perombakan puluhan query controller, penyesuaian policy otorisasi, dan perbaikan puluhan skenario test yang telah ada.

---

## 5. Matriks Perbandingan Komprehensif

| Indikator Penilaian | Pendekatan 1: Pemisahan Sub-Unit | Pendekatan 2: Direct `supervisor_id` |
| :--- | :--- | :--- |
| **Tingkat Risiko Kerusakan Kode** | **Sangat Rendah (0%)** | **Tinggi (Perlu refactoring besar)** |
| **Perubahan Struktur Database** | Tidak ada (hanya data master unit) | Tambah kolom foreign key di `users` |
| **Independensi Alur Kerja** | Penuh (masing-masing punya siklus review) | Rawan terkunci (*deadlock*) antar operator |
| **Kesesuaian Laporan Cetak Resmi** | Sangat Sesuai (1 tanda tangan per unit) | Ambigu (siapa yang tanda tangan) |
| **Kalkulasi Pagu Anggaran** | Akurat & terisolasi per unit | Rawan membingungkan jika terpotong |
| **Kompatibilitas 112 Unit Test** | 100% lulus tanpa perubahan | Puluhan test case perlu ditulis ulang |
| **Waktu & Kompleksitas Pengerjaan**| **Instan (Hitungan menit)** | **Lama (Memerlukan sprint terpisah)** |

---

## 6. Rekomendasi Strategis untuk RSUD Kardinah

1. **Rekomendasi Utama (Pendekatan Sub-Unit):**
   - Jika pembagian kerja antara Supervisor A dan Supervisor B mencerminkan struktur organisasi formal (seperti Kepala Seksi/Koordinator Sub-Bagian), maka pendekatan **Pemisahan Sub-Unit (Pendekatan 1)** adalah opsi terbaik. Opsi ini menjaga stabilitas sistem, menjamin independensi dokumen anggaran, dan tidak memiliki risiko menimbulkan bug.
2. **Kapan Pendekatan 2 Perlu Dipertimbangkan?**
   - Pendekatan 2 hanya disarankan jika rumah sakit secara mutlak mewajibkan dokumen RBA harus tetap 1 entitas tunggal tidak boleh dipecah, namun tetap menginginkan pembagian tugas *ad-hoc*. Jika ini dipilih, arsitektur `status_submission` harus dirombak dari tingkat unit menjadi status per rincian belanja (*detail-level validation workflow*).

---

*Dokumen ini dibuat sebagai referensi arsitektural proyek SIPAKAR RBA RSUD Kardinah.*
