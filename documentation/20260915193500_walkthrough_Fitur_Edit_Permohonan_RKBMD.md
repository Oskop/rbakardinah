# Walkthrough: Fitur Edit Permohonan RKBMD oleh Operator Pemohon

## Ringkasan Fitur
Modul **Rencana Kebutuhan Barang Milik Daerah (RKBMD)** kini telah dilengkapi dengan **Fitur Edit Permohonan**. Fitur ini memfasilitasi Operator Pemohon (akun Operator sub-unit) untuk merevisi atau memperbaiki formulir permohonan yang telah diajukan, **selama berkas tersebut masih berstatus 'Diajukan' dan belum pernah dialihkan (forward) ataupun dibalas oleh Operator Pengusul tujuan**.

Setiap perubahan permohonan terekam secara otomatis dalam linimasa **Riwayat & Jejak Audit** (*audit trail*) dan activity log sistem, sehingga kronologi pengajuan tetap transparan dan dapat dipertanggungjawabkan.

---

## Perubahan yang Diimplementasikan

### 1. Model & Logika Otorisasi
- **File**: [`app/Models/RkbmdSubmission.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php)
- Menambahkan method otorisasi [`canEditSubmission(User $user): bool`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php#L131-L148):
  - **Hak Akses Pengguna**: Hanya pemilik berkas pemohon (`user_id === $user->id`) atau Administrator.
  - **Kondisi Validitas Berkas**:
    1. Status berkas harus masih `Diajukan`.
    2. Belum pernah dibalas (`replied_at == null`).
    3. Belum pernah dialihkan (`!$this->histories()->where('action', 'Pengalihan')->exists()`).
  - Menolak pengeditan oleh pihak ketiga yang tidak berwenang maupun permohonan yang sudah masuk tahap proses/telaah.

### 2. Controller & Rute
- **File**: [`app/Http/Controllers/Operator/RkbmdController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)
  - Method `edit(RkbmdSubmission $rkbmd)`:
    - Melakukan validasi izin `$rkbmd->canEditSubmission($user)`.
    - Mengambil daftar operator pengusul aktif (`can_propose = 1`), katalog Master Barang BMD (Permendagri 108), dan data item permohonan.
    - Menampilkan view `operator.rkbmd.edit`.
  - Method `update(Request $request, RkbmdSubmission $rkbmd)`:
    - Melakukan validasi data (`target_operator_id`, `title`, `year`, `notes`, optional `attachment` PDF maks 10MB, dan array `items`).
    - Penanganan berkas: Jika pemohon mengunggah memo PDF baru, berkas lama dibersihkan dari penyimpanan disk dan digantikan file baru; jika dikosongkan, berkas PDF lama tetap dipertahankan.
    - Menjalankan transaksi database (`DB::transaction`): memperbarui data permohonan, menyinkronkan ulang daftar barang yang diajukan, dan mencatat entri baru ke `RkbmdHistory` dengan aksi `'Edit Permohonan'`.

### 3. Antarmuka Pengguna (Blade Views & Alpine.js)
- **File Formulir Edit Baru**: [`resources/views/operator/rkbmd/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php)
  - Formulir modern dengan nilai terisi otomatis (*pre-filled*): asal sub-unit terkunci otomatis, pilihan operator tujuan, perihal kebutuhan, dan tahun anggaran.
  - Tabel Daftar Barang dinamis berbasis Alpine.js yang dimuat langsung dari koleksi item eksisting (operator dapat menambah baris, menghapus baris, serta mengubah spesifikasi dan kuantitas).
  - Tautan untuk melihat dokumen PDF memo saat ini, disertai kolom unggah opsional.
- **File Halaman Detail**: [`resources/views/operator/rkbmd/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php)
  - Menampilkan kartu banner informatif berwarna amber bagi pemohon: *"Permohonan Masih Dapat Diedit"* dengan tombol cepat **"✏️ Edit Permohonan"**.
  - Menambahkan tombol **"✏️ Edit Permohonan"** pada bagian header aksi halaman.
  - Pada linimasa *Riwayat & Jejak Audit*, menambahkan penanda visual (marker biru/indigo) untuk tahapan aksi `'Edit Permohonan'`.
- **File Indeks Daftar Permohonan**: [`resources/views/operator/rkbmd/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)
  - Menambahkan tombol aksi cepat **"✏️ Edit"** di kolom aksi tabel *Permohonan Saya* apabila baris permohonan masih berstatus 'Diajukan' dan belum diproses.

---

## Verifikasi & Hasil Pengujian

### 1. Pengujian Otomatis Fitur RKBMD (`Tests\Feature\Operator\RkbmdTest`)
Pengujian mencakup:
1. Pemohon dapat mengakses formulir edit saat permohonan masih 'Diajukan' dan belum dialihkan/dibalas.
2. Pemohon berhasil memperbarui data judul, catatan, dan daftar barang, serta riwayat tercatat di `rkbmd_histories`.
3. Pemohon ditolak (`403 Forbidden`) saat mencoba mengedit permohonan yang sudah dibalas oleh Operator Pengusul.
4. Pemohon ditolak (`403 Forbidden`) saat mencoba mengedit permohonan yang sudah dialihkan ke operator lain.
5. Pengguna lain yang bukan pemohon ditolak (`403 Forbidden`) saat mencoba mengakses formulir atau mengirim update.

Hasil eksekusi:
```text
   PASS  Tests\Feature\Operator\RkbmdTest
  ✓ operator can access rkbmd index and create page
  ✓ operator can submit rkbmd with items and pdf memo
  ✓ audit columns and activity logs are automatically populated
  ✓ target operator can reply with status and free text
  ✓ target operator can forward rkbmd to another proposer and logs history
  ✓ viewer operator cannot reply or forward rkbmd
  ✓ admin can manage master barang permendagri 108
  ✓ admin master barangs index handles empty table without breaking datatables
  ✓ target operator can edit reply and logs history
  ✓ viewer and unauthorized operator cannot edit reply
  ✓ admin can delegate master barangs menu permission to operator
  ✓ operator with delegated permission can manage master barangs
  ✓ operator without delegated permission cannot access master barangs
  ✓ operator with delegated permission cannot access other admin menus
  ✓ applicant operator can access edit page when status diajukan and no forward or reply
  ✓ applicant operator can update submission and items and logs history
  ✓ applicant cannot edit if submission has been replied
  ✓ applicant cannot edit if submission has been forwarded
  ✓ unauthorized operator cannot edit submission

  Tests:    19 passed (88 assertions)
  Duration: 4.04s
```

### 2. Pengujian Regresi Menyeluruh (Full Test Suite)
```text
Tests:    236 passed (1157 assertions)
Duration: 44.97s
Status:   100% HIJAU (PASSED)
```

---

## Alur Kerja Penggunaan

1. **Pemohon** membuat permohonan RKBMD baru.
2. Saat status masih **Diajukan** dan belum ada respon/pengalihan dari operator pengusul tujuan:
   - Pemohon melihat tombol **"✏️ Edit Permohonan"** di tabel permohonan dan halaman detail.
   - Pemohon dapat mengklik tombol tersebut untuk membuka formulir edit, melakukan koreksi nama barang, jumlah, memo PDF, maupun mengganti operator tujuan jika salah pilih.
   - Setelah disimpan, riwayat pembaruan langsung tercatat transparan di linimasa permohonan.
3. Begitu **Operator Pengusul Tujuan** melakukan salah satu tindakan (memberikan balasan keputusan ataupun mengalihkan berkas):
   - Tombol edit otomatis hilang dari tampilan pemohon.
   - Akses rute edit/update langsung dikunci dengan HTTP `403 Forbidden`, menjaga integritas proses telaah berkas.
