# Rencana Implementasi: Fitur Edit Permohonan RKBMD oleh Operator Pemohon

Memfasilitasi Operator Pemohon untuk mengedit atau memperbarui data formulir permohonan RKBMD yang telah diajukan, **selama status permohonan masih 'Diajukan' dan belum pernah dialihkan (forward) ataupun dibalas oleh Operator Pengusul tujuan**.

---

## User Review Required

> [!IMPORTANT]
> **Kondisi & Batasan Izin Edit Permohonan**:
> 1. **Hak Akses**: Hanya pemohon yang membuat permohonan (`user_id === Auth::id()`) atau Administrator yang berwenang mengedit.
> 2. **Kondisi Berkas**:
>    - Status permohonan harus masih **`Diajukan`**.
>    - Belum pernah dibalas (`replied_at == null`).
>    - Belum pernah dialihkan / diforward (tidak ada riwayat dengan aksi `'Pengalihan'`).
> 3. **Item & Dokumen Pendukung**:
>    - Pemohon dapat menambah, mengubah, atau menghapus daftar barang yang dimohonkan (minimal 1 baris).
>    - Unggah memo PDF bersifat opsional saat edit: jika tidak mengunggah file baru, berkas PDF lama tetap dipertahankan; jika mengunggah file baru, file lama digantikan.
>    - Setiap pengeditan permohonan akan otomatis dicatat dalam **Riwayat & Jejak Audit** (`RkbmdHistory`) dengan aksi `'Edit Permohonan'` dan memperbarui kolom audit `updated_by` serta log aktivitas.

---

## Proposed Changes

### 1. Model & Logika Otorisasi

#### [MODIFY] [RkbmdSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php)
- Menambahkan method otorisasi `canEditSubmission(User $user): bool`:
  ```php
  public function canEditSubmission(User $user): bool
  {
      // Admin diizinkan jika berkas belum diproses
      if ($user->role === 'Administrator') {
          return $this->status === 'Diajukan'
              && empty($this->replied_at)
              && !$this->histories()->where('action', 'Pengalihan')->exists();
      }

      // Hanya pemohon yang berhak
      if ($this->user_id !== $user->id) {
          return false;
      }

      // Status harus 'Diajukan', belum dibalas, dan belum dialihkan
      return $this->status === 'Diajukan'
          && empty($this->replied_at)
          && !$this->histories()->where('action', 'Pengalihan')->exists();
  }
  ```

---

### 2. Rute & Controller

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Mendaftarkan rute `edit` dan `update` di grup rute `operator.rkbmd.*`:
  ```php
  Route::get('/{rkbmd}/edit', [\App\Http\Controllers\Operator\RkbmdController::class, 'edit'])->name('edit');
  Route::put('/{rkbmd}', [\App\Http\Controllers\Operator\RkbmdController::class, 'update'])->name('update');
  ```

#### [MODIFY] [RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)
- Menambahkan method `edit(RkbmdSubmission $rkbmd)`:
  - Memeriksa otorisasi `$rkbmd->canEditSubmission($user)` -> jika `false`, return `403 Forbidden`.
  - Mengambil daftar target operator aktif (`can_propose = true`, `id != $user->id`), katalog Master Barang BMD, dan relasi berkas.
  - Menampilkan view `operator.rkbmd.edit`.
- Menambahkan method `update(Request $request, RkbmdSubmission $rkbmd)`:
  - Memeriksa otorisasi `$rkbmd->canEditSubmission($user)` -> jika `false`, return `403 Forbidden`.
  - Melakukan validasi: `target_operator_id`, `title`, `year`, `notes`, `attachment` (opsional PDF maks 10MB), serta daftar `items` (minimal 1 item).
  - Mengelola file attachment: jika diunggah baru, hapus berkas lama di disk public dan simpan yang baru.
  - Membungkus proses penyimpanan dalam `DB::transaction`:
    - Update data `RkbmdSubmission` (`target_operator_id`, `original_operator_id`, `title`, `year`, `notes`, `attachment_path`, `updated_by`).
    - Sinkronisasi daftar barang: hapus items lama dan simpan baris items yang telah diperbarui.
    - Catat entri baru ke `RkbmdHistory`:
      - `action` => `'Edit Permohonan'`
      - `status_before` => `'Diajukan'`
      - `status_after` => `'Diajukan'`
      - `notes` => `"Permohonan diperbarui oleh pemohon ({$user->name})"`
      - `created_by` & `updated_by` => `$user->id`
  - Redirect ke `operator.rkbmd.show` dengan pesan sukses.

---

### 3. Antarmuka Pengguna (Blade Views & Alpine.js)

#### [NEW] [edit.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/edit.blade.php)
- Menyediakan formulir edit yang terisi otomatis (*pre-filled*) dengan data saat ini:
  - Header dengan nomor permohonan dan tombol kembali ke halaman detail.
  - Pilihan Operator Pengusul tujuan (terpilih operator yang sedang dituju).
  - Judul permohonan, tahun anggaran, dan catatan/latar belakang kebutuhan.
  - Tabel Daftar Barang dinamis menggunakan Alpine.js yang diinisialisasi dari `$rkbmd->items` (dapat menambah baris, menghapus baris, mengubah kuantitas/spesifikasi).
  - Bagian Unggah Berkas PDF dengan indikator tautan ke file PDF lama, dan instruksi jelas bahwa upload bersifat opsional jika tidak ada perubahan dokumen.

#### [MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php)
- Jika `$rkbmd->canEditSubmission(Auth::user())`:
  - Menampilkan banner aksi informatif: *"Permohonan Masih Dapat Diedit: Status berkas masih 'Diajukan' dan belum diproses/dialihkan oleh Operator Pengusul tujuan"* dengan tombol **"✏️ Edit Permohonan"**.
  - Menambahkan tombol **"✏️ Edit Permohonan"** pada bagian header navigasi atas.
- Pada linimasa *Riwayat & Jejak Audit*, menambahkan styling badge dan teks untuk aksi `'Edit Permohonan'`.

#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)
- Pada tabel *Permohonan Saya*, jika baris permohonan memenuhi `$sub->canEditSubmission(Auth::user())`, tampilkan tombol aksi cepat **"✏️ Edit"** di samping tombol "Lihat Detail →".

---

## Verification Plan

### Automated Tests
Menambahkan test cases baru pada `tests/Feature/Operator/RkbmdTest.php`:
1. `test_applicant_operator_can_access_edit_page_when_status_diajukan_and_no_forward_or_reply`:
   - Pemohon membuat permohonan.
   - Mengakses `operator.rkbmd.edit` -> assert status `200` dan melihat judul permohonan.
2. `test_applicant_operator_can_update_submission_and_items_and_logs_history`:
   - Mengirim request `PUT` ke `operator.rkbmd.update` dengan data revisi judul, catatan, dan item baru.
   - Assert redirect ke `show`, data di database terupdate, dan riwayat `Edit Permohonan` tercatat di `rkbmd_histories`.
3. `test_applicant_cannot_edit_if_submission_has_been_replied`:
   - Operator tujuan memberikan balasan (`Dipenuhi`).
   - Pemohon mencoba akses `edit` atau kirim `update` -> assert `403 Forbidden`.
4. `test_applicant_cannot_edit_if_submission_has_been_forwarded`:
   - Operator tujuan mengalihkan permohonan ke operator pengusul lain.
   - Pemohon mencoba akses `edit` atau kirim `update` -> assert `403 Forbidden`.
5. `test_unauthorized_operator_cannot_edit_submission`:
   - Operator lain yang bukan pemohon mencoba akses `edit` atau kirim `update` -> assert `403 Forbidden`.

Eksekusi pengujian:
```bash
php artisan test tests/Feature/Operator/RkbmdTest.php
php artisan test
```

### Manual Verification
- Login sebagai Operator Pemohon di Sub-Unit (misal Perawat Poli Jantung).
- Buat permohonan RKBMD baru.
- Periksa bahwa tombol **"✏️ Edit Permohonan"** muncul pada daftar dan halaman detail.
- Klik edit, ubah judul, tambah 1 item barang, dan klik simpan.
- Periksa halaman detail dan pastikan data terupdate serta linimasa audit mencatat tahapan *"✏️ Edit Permohonan"*.
- Coba simulasikan berkas yang sudah dibalas/dialihkan: pastikan tombol edit hilang dan akses URL manual ditolak dengan HTTP 403.
