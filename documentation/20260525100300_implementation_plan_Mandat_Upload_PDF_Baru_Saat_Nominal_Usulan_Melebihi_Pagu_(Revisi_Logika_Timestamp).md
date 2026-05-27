# Mandat Upload PDF Baru Saat Nominal Usulan Melebihi Pagu (Revisi Logika Timestamp)

Mewajibkan Operator untuk mengunggah berkas PDF rincian belanja baru (sebagai revisi/penyesuaian) apabila total usulan untuk kode rekening tertentu melebihi nominal pagu global yang telah ditetapkan oleh Administrator.

Logika penentuan kewajiban revisi menggunakan perbandingan waktu (*timestamp*): Jika PDF terakhir diunggah sebelum pagu terakhir kali ditetapkan/diperbarui oleh Admin, Operator wajib mengunggah PDF baru.

## User Review Required

> [!IMPORTANT]
> - **Pembatasan Validasi Supervisor**: Supervisor tidak diperbolehkan memvalidasi (*approve*) item rincian belanja yang nominalnya melebihi pagu kecuali Operator telah mengunggah PDF revisi baru (waktu pembuatan PDF lebih baru daripada waktu penetapan/pembaruan pagu).
> - **Pembatasan Pengajuan Operator**: Operator tidak diperbolehkan mengajukan (*submit*) item rincian belanja yang nominalnya melebihi pagu jika belum mengunggah dokumen PDF revisi baru setelah pagu ditetapkan.

## Proposed Changes

### [Backend Model & Policy]

#### [MODIFY] [RbaDetail.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/RbaDetail.php)
- Menambahkan metode pembantu `isExceedingPagu(): bool` untuk mengecek apakah akumulasi usulan belanja pada kode rekening di RBA Header ini melebihi pagu global.
- Menambahkan metode pembantu `hasUploadedRevision(): bool`:
  - Mengambil data pagu global (`RbaAccountPagu`) untuk rekening dan header ini.
  - Mengambil file lampiran terakhir (`latestAttachment()`).
  - Membandingkan `created_at` dari lampiran terakhir dengan `updated_at` dari pagu.
  - Mengembalikan `true` jika `created_at` lampiran $\ge$ `updated_at` pagu. Mengembalikan `false` jika sebaliknya (atau belum ada lampiran).

#### [MODIFY] [RbaDetailPolicy.php](file:///c:/Users/PC12/Project/rbakardinah/app/Policies/RbaDetailPolicy.php)
- Membuat method `uploadVersion(User $user, RbaDetail $rbaDetail): Response` khusus untuk memvalidasi hak unggah versi PDF baru:
  - Diperbolehkan jika pengguna adalah pemilik rincian belanja tersebut.
  - Jika pagu global sudah diset (> 0): diperbolehkan mengunggah berkas revisi **hanya** jika total usulan pada rekening tersebut melebihi pagu (`isExceedingPagu()`).

---

### [Controller Validation]

#### [MODIFY] [DetailController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Operator/DetailController.php)
- Mengubah otorisasi pada method `uploadVersion` untuk menggunakan Gate action `uploadVersion` (bukan `update`).
- Pada method `submitItem` (aksi pengajuan item oleh Operator), menambahkan validasi agar menolak pengajuan jika item tersebut melebihi pagu tetapi belum diunggah PDF revisi baru (`hasUploadedRevision()` mengembalikan `false`).

#### [MODIFY] [ReviewController.php](file:///c:/Users/PC12/Project/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php)
- Pada method `toggleDetailValidation`, menambahkan pengecekan ketika supervisor mencoba memvalidasi item: jika item tersebut melebihi pagu dan belum direvisi dengan PDF baru oleh operator (`hasUploadedRevision()` mengembalikan `false`), batalkan proses dengan pesan error yang jelas.

---

### [Frontend Antarmuka]

#### [MODIFY] [show.blade.php (operator)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/operator/submissions/show.blade.php)
- Di bagian kolom status pagu / aksi, jika nominal usulan melebihi pagu:
  - Jika belum unggah revisi (`hasUploadedRevision()` bernilai `false`): tampilkan teks/badge peringatan berwarna merah `⚠ Wajib Upload PDF Baru` dan tampilkan form untuk unggah versi baru. Tombol "Ajukan" disembunyikan/dinonaktifkan.
  - Jika sudah unggah revisi (`hasUploadedRevision()` bernilai `true`): tampilkan badge hijau `✓ PDF Penyesuaian Diunggah`, dan tampilkan tombol "Ajukan" (jika item belum diajukan).

#### [MODIFY] [show.blade.php (supervisor)](file:///c:/Users/PC12/Project/rbakardinah/resources/views/supervisor/submissions/show.blade.php)
- Menampilkan indikator penanda `⚠ Butuh PDF Revisi` jika item melebihi pagu dan `hasUploadedRevision()` bernilai `false`.
- Menampilkan tanda `✓ PDF Revisi Diunggah` jika `hasUploadedRevision()` bernilai `true`.

---

## Verification Plan

### Automated Tests
- Menjalankan dev server untuk memverifikasi fungsionalitas.
- Membuat usulan belanja yang melebihi pagu global.
- Memastikan tombol pengajuan (Ajukan) bagi Operator dibatasi, dan form upload revisi PDF dimunculkan.
- Memverifikasi Supervisor diblokir dari memvalidasi item tersebut jika PDF revisi belum diunggah.

### Manual Verification
- Melakukan login sebagai Operator, mengunggah PDF versi baru, lalu memverifikasi bahwa usulan dapat diajukan.
- Mencoba mengubah kembali pagu di Admin, memverifikasi status item di Operator kembali meminta PDF baru (karena timestamp ter-update).
- Melakukan login sebagai Supervisor dan memverifikasi bahwa item sekarang dapat divalidasi.
