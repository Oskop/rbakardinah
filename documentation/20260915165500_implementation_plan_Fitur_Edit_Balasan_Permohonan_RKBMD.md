# Implementation Plan: Fitur Edit Balasan Permohonan RKBMD oleh Operator Pengusul

Dokumen ini menjelaskan rencana teknis penambahan fitur **Edit Balasan** pada modul **Rencana Kebutuhan Barang Milik Daerah (RKBMD)** pada SIPAKAR RSUD Kardinah. Fitur ini memungkinkan Operator Pengusul RBA (atau Administrator) untuk memperbarui status keputusan dan catatan balasan apabila terjadi kekeliruan penginputan, dengan tetap mempertahankan transparansi melalui pencatatan riwayat perubahan (*audit trail*).

---

## 1. Latar Belakang & Analisis Masalah

### Kondisi Saat Ini
1. Ketika Operator Pengusul membalas permohonan RKBMD melalui modal balasan (Skenario 1), status tiket berubah menjadi salah satu dari: `Dipenuhi`, `Dipenuhi Sebagian`, `Substitusi`, `Optimalisasi`, atau `Ditolak`.
2. Method `canBeManagedBy(User $user)` pada model `RkbmdSubmission` langsung mengunci tiket begitu salah satu dari kelima status tersebut aktif:
   ```php
   if (in_array($this->status, ['Dipenuhi', 'Dipenuhi Sebagian', 'Substitusi', 'Optimalisasi', 'Ditolak'])) {
       return false;
   }
   ```
3. Di halaman detail (`show.blade.php`), kartu *"Keputusan / Balasan Resmi Operator Pengusul"* hanya ditampilkan secara statis (*read-only*).
4. Jika operator pengusul salah memilih status keputusan (misal: seharusnya *Dipenuhi Sebagian* atau *Substitusi*, tetapi terlanjur terpilih *Dipenuhi* atau *Ditolak*), atau terdapat salah ketik / revisi instruksi pada catatan balasan, operator tidak memiliki cara untuk mengoreksinya.

### Kebutuhan Pengguna
- Menyediakan tombol dan formulir **Edit Balasan** bagi Operator Pengusul yang berwenang.
- Mengizinkan pembaruan pilihan status (`status`) dan catatan balasan (`reply_notes`).
- Memastikan hak akses terjaga (pemohon dan operator luar tidak boleh mengubah balasan).
- Mencatat setiap tindakan revisi balasan ke dalam riwayat permohonan (`rkbmd_histories`) dan log aktivitas sistem (`activity_logs`) agar seluruh perubahan transparan dan akuntabel.

---

## 2. Rencana Arsitektur & Hak Akses

### Penentuan Hak Akses Edit Balasan
Dibuat helper method khusus `canEditReply(User $user): bool` pada model `RkbmdSubmission`:
- **Role Administrator**: Selalu dapat mengedit balasan jika permohonan sudah pernah dibalas (`replied_at != null`).
- **Role Operator**:
  - Wajib memiliki hak pengusulan aktif (`can_propose = true`).
  - Permohonan harus sudah memiliki balasan sebelumnya (`replied_at != null`).
  - Pengguna adalah operator yang membalas (`replied_by === $user->id`) atau operator tujuan/pemegang berkas saat ini (`target_operator_id === $user->id`).
- **Operator Pemohon & Pihak Lain**: Tidak diizinkan mengedit balasan (respons HTTP `403 Forbidden`).

---

## User Review Required

> [!NOTE]
> - **Riwayat Audit**: Ketika balasan diedit, sistem akan mencatat entri baru di tabel `rkbmd_histories` dengan aksi `'Edit Balasan'`, mencatat status sebelum vs sesudah, serta keterangan revisi. Riwayat balasan lama tidak dihapus, melainkan tetap terekam di linimasa (*timeline audit trail*).
> - **Opsi Pengalihan**: Edit balasan berfokus pada koreksi status dan catatan keputusan. Apabila status diubah, operator pengusul tetap menjadi penanggung jawab balasan tersebut.

---

## Proposed Changes

### Eloquent Model

#### [MODIFY] [RkbmdSubmission.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RkbmdSubmission.php)
- Tambahkan method `canEditReply(User $user): bool` untuk memvalidasi hak akses edit balasan secara tersentralisasi.

---

### Routing & Controller

#### [MODIFY] [routes/web.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/routes/web.php)
- Daftarkan route baru:
  ```php
  Route::put('rkbmd/{rkbmd}/reply', [\App\Http\Controllers\Operator\RkbmdController::class, 'updateReply'])->name('rkbmd.reply.update');
  ```

#### [MODIFY] [RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)
- Tambahkan method `updateReply(Request $request, RkbmdSubmission $rkbmd)`:
  - Validasi otorisasi via `$rkbmd->canEditReply($user)`.
  - Validasi input `status` (5 pilihan status) dan `reply_notes` (wajib, max 2000 karakter).
  - Update `status`, `reply_notes`, dan `updated_by`.
  - Buat rekaman baru di `RkbmdHistory` dengan `action = 'Edit Balasan'`, mencatat `status_before`, `status_after`, dan perbandingan catatan.

---

### Blade Views & UI

#### [MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php)
- Pada Alpine.js `x-data`, tambahkan variabel `editReplyModalOpen: false`.
- Pada kartu *Keputusan / Balasan Resmi Operator Pengusul*, tambahkan tombol **"✏️ Edit Balasan"** (hanya muncul jika `$rkbmd->canEditReply(Auth::user())`).
- Tambahkan komponen modal **Edit Balasan Permohonan RKBMD** (`editReplyModalOpen`):
  - Dropdown pilihan status terisi otomatis (*selected*) dengan status saat ini.
  - Textarea catatan balasan terisi teks balasan saat ini.
  - Form submit menggunakan `@method('PUT')` ke `route('operator.rkbmd.reply.update', $rkbmd)`.
- Pada linimasa *Riwayat & Jejak Audit*, berikan penanda visual / badge khusus untuk aksi `'Edit Balasan'` (ikon pensil ✏️ dan badge warna amber/oranye) agar terlihat jelas perbedaannya dengan pengajuan atau pengalihan.

---

### Automated Tests

#### [MODIFY] [RkbmdTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdTest.php)
- Tambahkan test cases:
  1. `test_proposer_operator_can_edit_their_reply_and_history_is_recorded`: Memastikan operator pengusul dapat memperbarui status & catatan balasan, serta histori `Edit Balasan` tercatat.
  2. `test_viewer_operator_cannot_edit_reply_and_gets_403`: Memastikan operator pemohon/non-pengusul ditolak saat mencoba mengedit balasan.
  3. `test_unrelated_operator_cannot_edit_reply`: Memastikan operator lain di luar pembalas/pemegang berkas tidak dapat mengedit balasan.

---

## Verification Plan

### Automated Tests
- Menjalankan suite pengujian RKBMD:
  ```powershell
  php artisan test tests/Feature/Operator/RkbmdTest.php
  ```
- Menjalankan seluruh test suite aplikasi (225+ tests):
  ```powershell
  php artisan test
  ```

### Manual Verification
- Login sebagai Operator Pengusul yang menerima permohonan:
  - Berikan balasan awal (misal: Status "Dipenuhi").
  - Pastikan tombol "✏️ Edit Balasan" muncul pada kartu balasan.
  - Klik tombol "✏️ Edit Balasan", ubah status menjadi "Dipenuhi Sebagian" dan perbarui catatan.
  - Kirim perbaruan dan pastikan status tiket berubah serta muncul catatan revisi di linimasa audit.
- Login sebagai Operator Pemohon:
  - Buka tiket yang sama, pastikan status terbaru dan catatan revisi terlihat, serta pastikan tombol "Edit Balasan" TIDAK muncul.
