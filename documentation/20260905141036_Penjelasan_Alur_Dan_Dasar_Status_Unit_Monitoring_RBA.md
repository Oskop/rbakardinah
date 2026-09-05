# Penjelasan Alur dan Dasar Penentuan Status Unit pada Panel Monitoring RBA Administrator

Dokumen ini menjelaskan dasar logika, alur perubahan, dan acuan kode sumber mengenai penentuan **Status Unit** (`Draft`, `Pending Supervisor`, dan `Validated`) pada bagian *Monitoring Penginputan Unit dan Progres RBA* di halaman Administrator (`/admin/headers/{header}`).

---

## 1. Sumber Data Status Unit

Status unit yang ditampilkan pada tabel *Monitoring Penginputan Unit dan Progres RBA* (baik pada *Quick Summary Pills Bar* maupun pada *Card Header Bar* masing-masing unit) bersumber langsung dari atribut kolom **`status_submission`** pada tabel database `rba_submissions` (`App\Models\RbaSubmission`).

Setiap kali suatu periode RBA (RBA Header) dibuat oleh Administrator, sistem secara otomatis membuat satu rekaman berkas `rba_submissions` untuk setiap unit kerja yang ada di RSUD Kardinah.

---

## 2. Rincian dan Dasar Penentuan Masing-Masing Status

```
[Administrator Buat RBA]
           │
           ▼
        [DRAFT]  ◄── Awal pembuatan, Operator masih menyusun usulan / dokumen
           │
           │ (Operator klik "Ajukan ke Supervisor" pada rincian / berkas unit)
           ▼
  [PENDING SUPERVISOR]  ◄── Menunggu pemeriksaan & verifikasi oleh Supervisor Unit
           │
           │ (Supervisor klik tombol "Validasi & Lanjutkan")
           ▼
      [VALIDATED]  ◄── Resmi disetujui di tingkat Unit, siap untuk penetapan Pagu
```

---

### A. Status: `Draft` (Warna Abu-abu / Netral)
- **Dasar Penentuan**:
  - Dibuat secara otomatis saat Administrator membuat RBA Header baru melalui method `store()` di `App\Http\Controllers\RbaHeaderController`:
    ```php
    \App\Models\RbaSubmission::create([
        'rba_header_id' => $header->id,
        'unit_id' => $unit->id,
        'status_submission' => 'Draft',
    ]);
    ```
- **Makna & Kondisi**:
  - Berkas usulan RBA pada unit kerja tersebut baru diinisiasi.
  - Operator di unit kerja tersebut masih dalam proses menyusun dokumen usulan (mengisi teks latar belakang sub-unit, mengunggah dokumen KAK/RAK/RTP, serta membuat draf rincian usulan belanja).
  - Belum ada usulan rincian belanja yang diajukan secara resmi ke Supervisor unit.

---

### B. Status: `Pending Supervisor` (Warna Kuning / Amber)
- **Dasar Penentuan**:
  Status unit berubah dari `Draft` menjadi `Pending Supervisor` melalui salah satu dari **dua tindakan Operator**:
  1. **Pengajuan Per Rincian Belanja (`submitItem`)**:
     - Lokasi: `App\Http\Controllers\Operator\DetailController::submitItem`
     - Ketika Operator mengklik tombol *"Ajukan ke Supervisor"* pada salah satu rincian belanja (`$detail->update(['is_submitted' => true])`), sistem mengecek: jika status berkas unit saat itu masih `Draft`, sistem langsung meng-update status unit menjadi `Pending Supervisor`:
       ```php
       if ($detail->submission->status_submission === 'Draft') {
           $detail->submission->update(['status_submission' => 'Pending Supervisor']);
       }
       ```
  2. **Pengajuan Berkas Unit Secara Keseluruhan (`submit`)**:
     - Lokasi: `App\Http\Controllers\Operator\SubmissionController::submit`
     - Ketika Operator menekan tombol *"Ajukan ke Supervisor"* pada tingkat berkas submission unit di workboard:
       ```php
       if ($submission->status_submission !== 'Draft') {
           return back()->with('error', 'Only Draft submissions can be submitted.');
       }
       $submission->update(['status_submission' => 'Pending Supervisor']);
       ```
- **Makna & Kondisi**:
  - Operator di unit kerja tersebut telah selesai menyusun usulan atau sudah mulai mengajukan usulan rincian belanja ke Supervisor.
  - Berkas usulan unit kini berada pada antrean penelaahan (*Review RBA*) milik Supervisor unit untuk diperiksa, diverifikasi, atau divalidasi tiap butirnya.

---

### C. Status: `Validated` (Warna Hijau / Emerald)
- **Dasar Penentuan**:
  - Lokasi: `App\Http\Controllers\Supervisor\ReviewController::validate`
  - Berubah ketika **Supervisor unit** membuka halaman review usulan unitnya (`/supervisor/submissions/{submission}`) dan mengklik tombol **"Validasi & Lanjutkan"**:
    ```php
    if ($submission->status_submission !== 'Pending Supervisor') {
        return back()->with('error', 'Only Pending submissions can be validated.');
    }
    $submission->update(['status_submission' => 'Validated']);
    ```
- **Makna & Kondisi**:
  - Supervisor unit telah selesai memeriksa dan memvalidasi seluruh rangkaian usulan dari operator di bawah unitnya.
  - Berkas usulan unit dinyatakan telah sah dan resmi disetujui di tingkat unit kerja.
  - Berkas usulan unit tersebut siap diproses oleh Administrator atau Tim Anggaran rumah sakit untuk proses penetapan pagu indikatif/final.

---

## 3. Perbedaan Status Unit (Makro) vs Status Rincian Belanja (Mikro)

Di dalam sistem SIPAKAR, terdapat dua tingkatan status yang saling melengkapi:

| Tingkatan | Objek Model | Nilai Status yang Mungkin | Keterangan |
| :--- | :--- | :--- | :--- |
| **Status Unit (Makro)** | `RbaSubmission` | `Draft`, `Pending Supervisor`, `Validated` | Menunjukkan progres keseluruhan berkas unit dari penyusunan awal hingga pengesahan oleh Supervisor unit. |
| **Status Butir Rincian (Mikro)** | `RbaDetail` | `Draft`, `Pending Review`, `Divalidasi`, `Ditolak` | Menunjukkan status spesifik per butir rekening belanja usulan operator yang divalidasi atau ditolak satu per satu oleh Supervisor. |

Pada panel monitoring Administrator, kedua tingkatan ini disajikan secara harmonis: status makro unit ditampilkan pada badge status unit, sementara status mikro rincian belanja disajikan dalam metrik perbandingan validasi (contoh: `<span class="text-emerald-600 font-bold">5</span>/5 Usulan`).

---

## 4. Referensi File Kode Terkait

1. Inisiasi status awal (`Draft`): [app/Http/Controllers/RbaHeaderController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/RbaHeaderController.php#L57-L63)
2. Perubahan ke `Pending Supervisor` (Per Item): [app/Http/Controllers/Operator/DetailController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php#L239-L243)
3. Perubahan ke `Pending Supervisor` (Per Submission): [app/Http/Controllers/Operator/SubmissionController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/SubmissionController.php#L104-L110)
4. Perubahan ke `Validated`: [app/Http/Controllers/Supervisor/ReviewController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Supervisor/ReviewController.php#L312-L318)
5. Tampilan Panel Monitoring Admin: [resources/views/admin/headers/show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/headers/show.blade.php#L313-L355)
