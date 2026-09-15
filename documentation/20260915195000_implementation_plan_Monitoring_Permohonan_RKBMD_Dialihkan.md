# Rencana Implementasi: Fitur Monitoring Permohonan RKBMD yang Dialihkan oleh Operator Pengusul

Menyediakan antarmuka dan tab khusus **"↪️ Permohonan Dialihkan"** bagi Operator Pengusul (PIC RBA) yang pernah mengalihkan berkas permohonan ke operator pengusul lain, sehingga berkas tetap dapat dipantau perkembangannya dan tidak terkesan hilang dari daftar.

---

## User Review Required

> [!IMPORTANT]
> **Pemisahan Antara Kotak Masuk Tindak Lanjut & Riwayat Alihan**:
> 1. **Tab "📥 Permohonan Masuk"** tetap berfungsi sebagai *Actionable Inbox* (hanya menampilkan berkas aktif yang saat ini sedang ditujukan ke operator login dan membutuhkan tindak lanjut/balasan/pengalihan).
> 2. **Tab Baru "↪️ Permohonan Dialihkan"** berfungsi sebagai *Handover Tracking List* khusus bagi Operator Pengusul, yang menampilkan seluruh permohonan yang pernah dialihkan oleh operator tersebut ke PIC lain, mencakup:
>    - Nomor tiket & tanggal permohonan
>    - Nama pemohon & asal sub-unit
>    - Perihal permohonan & jumlah item
>    - Nama Operator Tujuan Alihan saat ini
>    - Status terkini berkas (misal masih *Dialihkan*, atau sudah selesai *Dipenuhi / Ditolak* oleh PIC penerima alihan)
>    - Tombol aksi *"Lihat Detail & Pantau →"*
> 3. Pada halaman detail (`show.blade.php`), operator pengalih akan melihat banner informatif bahwa berkas telah dialihkan beserta linimasa audit transparan yang mencatat setiap perkembangan status.

---

## Proposed Changes

### 1. Controller & Query Logika

#### [MODIFY] [RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)
- Pada method `index()`:
  - Menambahkan pengambilan koleksi `$forwardedSubmissions` khusus jika user adalah `$isProposer`:
    ```php
    $forwardedSubmissions = RkbmdSubmission::with(['applicant', 'subUnit', 'unit', 'items', 'targetOperator', 'histories'])
        ->whereHas('histories', function ($q) use ($user) {
            $q->where('action', 'Pengalihan')
              ->where('from_operator_id', $user->id);
        })
        ->where('target_operator_id', '!=', $user->id)
        ->latest('updated_at')
        ->get();
    ```
  - Mengirim `$forwardedSubmissions` ke view `operator.rkbmd.index`.
- Pada method `show()`:
  - Merapikan scoping grup `where` pada pengecekan histori keterlibatan berkas (`$isParticipant`) agar terisolasi dengan baik pada `rkbmd_submission_id`:
    ```php
    || $rkbmd->histories()->where(function ($q) use ($user) {
        $q->where('user_id', $user->id)
          ->orWhere('from_operator_id', $user->id)
          ->orWhere('to_operator_id', $user->id);
    })->exists()
    ```

---

### 2. Antarmuka Pengguna (Blade Views & Alpine.js)

#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)
- Menambahkan tab ke-3 pada tombol switch tab atas:
  - **"↪️ Permohonan Dialihkan"** dengan badge counter `{{ $forwardedSubmissions->count() }}`.
- Menambahkan kontainer konten **TAB 3: PERMOHONAN DIALIHKAN** (`x-show="activeTab === 'forwarded'"`):
  - Menampilkan tabel terstruktur lengkap dengan kolom: No. Tiket, Pemohon, Perihal, Dialihkan Kepada (PIC tujuan), Jumlah Item, Status Terkini, dan Aksi "Lihat Detail & Pantau →".
  - Dilengkapi *empty state* yang rapi jika belum ada berkas yang dialihkan.

#### [MODIFY] [show.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/show.blade.php)
- Jika pengguna login adalah operator yang pernah mengalihkan berkas ini dan berkas saat ini dipegang oleh operator lain:
  - Tampilkan banner status informatif berwarna amber di bagian atas konten:
    *"ℹ️ Anda telah mengalihkan permohonan ini kepada [Nama Operator Penerima]. Anda tetap dapat memantau perkembangan status keputusan melalui linimasa audit di bawah ini."*

---

## Verification Plan

### Automated Tests
Menambahkan test cases baru pada `tests/Feature/Operator/RkbmdTest.php`:
1. `test_forwarding_operator_sees_forwarded_submission_in_dedicated_tab`:
   - Operator Pemohon membuat permohonan ditujukan ke Proposer A.
   - Proposer A melihat permohonan di tab `incoming` (Permohonan Masuk).
   - Proposer A mengalihkan permohonan ke Proposer B.
   - Assert bahwa permohonan tersebut:
     - Tidak lagi ada di `incoming` Proposer A.
     - Muncul di `forwarded` (Permohonan Dialihkan) milik Proposer A.
     - Muncul di `incoming` (Permohonan Masuk) milik Proposer B.
2. `test_forwarding_operator_can_view_show_page_and_audit_trail_of_forwarded_submission`:
   - Proposer A mengakses halaman `operator.rkbmd.show` berkas yang dialihkan -> assert status `200` dan melihat banner informasi serta linimasa alihan.
3. `test_forwarded_submission_updates_status_in_forwarded_tab_when_resolved`:
   - Proposer B membalas permohonan dengan status `Dipenuhi`.
   - Proposer A membuka index -> di tab `forwarded`, status permohonan terupdate menjadi `Dipenuhi`.

Eksekusi pengujian:
```bash
php artisan test tests/Feature/Operator/RkbmdTest.php
php artisan test
```

### Manual Verification
- Login sebagai Operator Pengusul A.
- Buka menu RKBMD, terima permohonan dari pemohon.
- Alihkan permohonan tersebut ke Operator Pengusul B.
- Buka tab "↪️ Permohonan Dialihkan", pastikan data permohonan yang baru saja dialihkan tampil rapi dengan tujuan Operator B dan status 'Dialihkan'.
- Klik "Lihat Detail & Pantau →", pastikan halaman detail menampilkan banner bahwa berkas telah dialihkan beserta riwayat lengkap.
- Login sebagai Operator Pengusul B, berikan balasan ("Dipenuhi").
- Kembali login sebagai Operator Pengusul A, pastikan status di tab "Permohonan Dialihkan" berubah menjadi "Dipenuhi".
