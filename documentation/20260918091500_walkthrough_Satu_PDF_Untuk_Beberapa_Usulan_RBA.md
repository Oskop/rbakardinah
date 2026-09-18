# Walkthrough: Fitur 1 PDF untuk Beberapa Usulan Belanja RBA (Multi-Item Shared PDF & Versioning)

Dokumen ini merangkum penyelesaian implementasi fitur **1 Berkas PDF untuk Beberapa Usulan Belanja (`rba_detail`)** dari operator dengan sistem **versioning dinamis**, fleksibilitas pemisahan dokumen mandiri (*standalone*), pembaruan revisi bersama (*shared update*), dan penjagaan riwayat audit trail per usulan.

---

## 1. Ringkasan Perubahan

| Komponen | File | Deskripsi Perubahan |
| :--- | :--- | :--- |
| **Database Migrations** | [`2026_09_18_080000_create_rba_detail_documents_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_18_080000_create_rba_detail_documents_table.php)<br>[`2026_09_18_080100_create_rba_detail_attachments_table.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_18_080100_create_rba_detail_attachments_table.php) | <ul><li>Membuat tabel `rba_detail_documents` sebagai entitas induk dokumen pengajuan unit.</li><li>Menambahkan `rba_detail_document_id` dan `original_filename` pada `rba_attachments`, serta membuat `rba_detail_id` nullable.</li><li>Membuat tabel pivot `rba_detail_attachments` untuk menghubungkan relasi Banyak-ke-Banyak (*Many-to-Many*) antara `rba_details` dan `rba_attachments`.</li><li>Menyalin seluruh data eksisting ke dalam tabel pivot secara otomatis.</li></ul> |
| **Domain Models** | [`app/Models/RbaDetailDocument.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDetailDocument.php)<br>[`app/Models/RbaAttachment.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaAttachment.php)<br>[`app/Models/RbaDetail.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/RbaDetail.php) | <ul><li>Model baru `RbaDetailDocument` dengan relasi `submission`, `creator`, `versions`, dan `latestVersion`.</li><li>`RbaAttachment`: Menambahkan relasi `document()`, `details()`, serta `booted()` hook untuk otomatisasi sinkronisasi ke tabel pivot.</li><li>`RbaDetail`: Mengubah relasi `attachments()` menjadi `BelongsToMany(RbaAttachment::class, 'rba_detail_attachments')`, helper `latestAttachment()`, dan helper `document()`.</li></ul> |
| **Operator Controller** | [`app/Http/Controllers/Operator/DetailController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/DetailController.php) | <ul><li>`create()` & `edit()`: Mengambil daftar dokumen yang sudah pernah diunggah (`$existingDocuments`) untuk pengajuan terkait.</li><li>`store()`: Mendukung 2 mode sumber dokumen (`new` atau `existing`). Jika `existing`, usulan baru langsung terhubung ke PDF tanpa mengunggah ulang berkas fisik.</li><li>`uploadVersion()`: Mendukung 2 mode revisi (`standalone` untuk pisah dokumen mandiri, atau `shared_update` untuk revisi dokumen bersama dengan checklist usulan terkait).</li><li>`update()`: Mendukung opsi beralih atau mengganti dokumen yang ditautkan ke usulan.</li></ul> |
| **General History Controller** | [`app/Http/Controllers/General/HistoryController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/General/HistoryController.php) | Eager loading relasi dokumen induk dan daftar usulan lain yang berbagi berkas tersebut pada setiap versi lampiran. |
| **Frontend Views** | [`resources/views/operator/details/create.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/details/create.blade.php)<br>[`resources/views/operator/details/edit.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/details/edit.blade.php)<br>[`resources/views/operator/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/submissions/show.blade.php)<br>[`resources/views/supervisor/submissions/show.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/supervisor/submissions/show.blade.php)<br>[`resources/views/general/history.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/general/history.blade.php) | <ul><li>`create.blade.php`: Tab interaktif Alpine.js untuk memilih *"Gunakan Dokumen yang Ada"* vs *"Unggah PDF Baru"*.</li><li>`edit.blade.php`: Kartu informasi dokumen lampiran terpasang beserta opsi beralih ke dokumen lain.</li><li>`operator/submissions/show.blade.php`: Badge tabel *"👥 Bersama (X)"* dan Modal Revisi cerdas dengan opsi *"Perbarui Bersama"* vs *"Pisahkan Dokumen"*.</li><li>`supervisor/submissions/show.blade.php`: Indikator *"👥 Bersama (X)"* agar supervisor tahu berkas berasal dari satu memo/nota dinas.</li><li>`history.blade.php`: Audit trail lengkap menampilkan judul dokumen, nama file asli, dan daftar usulan lain yang berbagi berkas tersebut.</li></ul> |
| **Feature Testing** | [`tests/Feature/Operator/RbaDetailTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RbaDetailTest.php) | Menambahkan 5 skenario pengujian komprehensif untuk dokumen bernama, usulan susulan dengan dokumen eksisting, revisi bersama parsial, pemisahan usulan ditolak (*standalone*), dan integritas dokumen saat salah satu usulan dihapus. |

---

## 2. Penanganan Seluruh Skenario Pengguna

### A. Skenario Multi-Item 1 PDF Saat Pembuatan
1. Operator membuat usulan pertama: Mengunggah file PDF (misal `Nota_Dinas_ATK.pdf`) dan memberi nama *"Nota Dinas Belanja ATK 2026"*.
2. Berkas tersimpan sebagai **Dokumen Pengajuan** dengan **Versi 1**.
3. Usulan pertama langsung terikat ke berkas tersebut.

### B. Skenario Usulan Susulan / Terlewat (Skenario 2 User)
1. 5 usulan belanja sudah tersimpan / diajukan.
2. Terdapat usulan ke-6 yang terlewat namun tercantum pada nota dinas yang sama.
3. Operator klik *"Tambah Rincian Belanja"*, sistem otomatis menampilkan tab pilihan:
   - **Gunakan Dokumen yang Ada**: Operator memilih *"Nota Dinas Belanja ATK 2026 (V1)"*.
   - Operator mengisi uraian, volume, satuan, dan harga, lalu klik Simpan.
4. Usulan ke-6 langsung terikat ke dokumen V1 tersebut tanpa perlu mengunggah berkas fisik lagi. Status 5 usulan sebelumnya tidak terganggu.

### C. Skenario Revisi Parsial & Pemisahan Usulan Ditolak (Skenario 1 User)
1. Dokumen V1 digunakan oleh 5 usulan. 1 usulan (Usulan 5) ditolak oleh supervisor.
2. Operator memiliki dua opsi fleksibel:
   - **Opsi 1 (Pisahkan Dokumen Mandiri)**:
     Operator klik tombol revisi pada Usulan 5, memilih *"Pisahkan Dokumen"*, dan mengunggah PDF nota dinas baru khusus usulan tersebut. Usulan 5 kini memiliki dokumen mandiri dengan riwayat tersendiri, sementara Usulan 1-4 tetap utuh pada dokumen bersama.
   - **Opsi 2 (Revisi Dokumen Bersama V2)**:
     Operator mengunggah berkas revisi V2 dan mencentang Usulan 1-4, sedangkan Usulan 5 tidak dicentang. Usulan 1-4 diperbarui ke Versi 2, sementara Usulan 5 tetap di Versi 1 (atau menunggu dokumen baru).

### D. Skenario Penghapusan & Integritas Dokumen
- Jika salah satu usulan dihapus (`destroy` / soft delete), relasi usulan tersebut pada tabel pivot dilepas, namun berkas fisik dan data dokumen tetap aman dan aktif untuk usulan-usulan lainnya yang berbagi berkas tersebut.

---

## 3. Hasil Pengujian & Verifikasi

### Pengujian Fitur RBA Detail (`RbaDetailTest.php`)
```powershell
php artisan test tests/Feature/Operator/RbaDetailTest.php
```
**Hasil:**
```text
PASS  Tests\Feature\Operator\RbaDetailTest
✓ operator can view their submissions
✓ operator can create rba detail with pdf
✓ operator submission view displays previous period pagu in awal column
✓ operator can upload new version of pdf
✓ operator can submit item to supervisor
✓ operator can soft delete rba detail
✓ operator must upload new pdf when nominal exceeds pagu
✓ supervisor cannot validate item exceeding pagu without revision
✓ operator cannot add detail if background is empty
✓ operator can save background
✓ operator can upload kak rak rtp versioned documents when locked
✓ operator cannot edit or upload revision on validated detail
✓ uploading revision pdf on rejected detail resets status to draft
✓ operator can create detail with named document
✓ operator can create subsequent detail using existing shared pdf
✓ shared pdf revising updates selected items and leaves unselected
✓ detaching item to new document creates standalone history
✓ deleting one item preserves shared attachment for other items

Tests:    18 passed (80 assertions)
Duration: 2.71s
```

### Pengujian Full Test Suite Seluruh Aplikasi
```powershell
php artisan test
```
**Hasil:**
```text
Tests:    248 passed (1215 assertions)
Duration: 45.84s
```
Semua 248 pengujian di seluruh modul aplikasi (RBA Operator, Review Supervisor, Admin Monitoring, RBA Print/Export, RKBMD, Manajemen User, SSO OIDC, dsb.) lulus **100% tanpa kegagalan**.
