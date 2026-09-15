# Walkthrough: Penerapan Filtering, Pencarian, dan Pengurutan Tabel RKBMD

Dokumen ini merangkum penyelesaian implementasi fitur **filtering** (rentang tanggal & status), **pencarian instan** (*keyword search*), dan **pengurutan kronologis** (*sorting* tanggal pengajuan) pada seluruh tabel RKBMD (*Permohonan Saya*, *Permohonan Masuk*, dan *Permohonan Dialihkan*).

---

## 1. Ringkasan Perubahan

| Komponen | File | Deskripsi Perubahan |
| :--- | :--- | :--- |
| **Backend Controller** | [`app/Http/Controllers/Operator/RkbmdController.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php) | Menambahkan filter query params (`start_date`, `end_date`, `status`, `search`) yang diterapkan secara seragam pada ketiga query koleksi: `$mySubmissions`, `$incomingSubmissions`, dan `$forwardedSubmissions`. |
| **Antarmuka & Script** | [`resources/views/operator/rkbmd/index.blade.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php) | <ul><li>Membuat Toolbar Filter terpadu (Rentang Tanggal, Status, Input Cari, dan Tombol Reset).</li><li>Menambahkan atribut `data-date` dan `data-status` pada baris `<tr>`.</li><li>Menambahkan `data-order="{{ $sub->created_at->timestamp }}"` pada kolom nomor & tanggal untuk pengurutan epoch numerik akurat.</li><li>Mengatur default DataTables sorting `order: [[0, 'desc']]`.</li><li>Menghubungkan event pencarian real-time via `$.fn.dataTable.ext.search.push` tanpa perlu *reload* halaman.</li><li>Menghapus `@empty` dengan `<td colspan>` yang memicu warning DataTables.</li><li>Mengintegrasikan penyesuaian DataTables (`adjust()`) saat berganti tab Alpine.js.</li></ul> |
| **Pengujian Otomatis** | [`tests/Feature/Operator/RkbmdTest.php`](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdTest.php) | Menambahkan 4 test cases pengujian server-side filter tanggal, status, keyword pencarian, dan integrasi aset DataTables. |

---

## 2. Detail Implementasi

### A. Backend Filtering (`RkbmdController.php`)
Filter dinamis diterapkan menggunakan Closure helper `$applyFilters`:
- `start_date`: Memfilter pengajuan dengan `DATE(created_at) >= ?`
- `end_date`: Memfilter pengajuan dengan `DATE(created_at) <= ?`
- `status`: Memfilter exact match kolom `status`
- `search`: Melakukan pencarian like `%keyword%` pada `ticket_number`, `subject`, nama `creator`, maupun nama `targetProposerUnit`.

Nilai filter dipassing kembali ke view via `compact('startDate', 'endDate', 'filterStatus', 'search')` untuk menjaga status input pada form pencarian.

### B. Tampilan & DataTables Interaktif (`index.blade.php`)
1. **Toolbar Filter Terpadu**:
   - Ditempatkan tepat di atas navigasi tab sehingga berlaku intuitif untuk tab mana pun yang sedang aktif.
   - Tombol **Reset** membersihkan input tanggal, mereset dropdown ke semua status, menghapus teks pencarian, dan merefresh tampilan ketiga tabel sekaligus.
2. **Pencarian Real-Time Client-Side**:
   - `$('#filter-search').on('keyup')` otomatis memicu `search()` pada ketiga instance DataTables.
   - `$.fn.dataTable.ext.search.push` menyaring baris berdasarkan perbandingan rentang tanggal `data-date` dan filter `data-status`.
3. **Pengurutan Kronologis Presisi**:
   - Default urutan: baris permohonan terbaru selalu berada di urutan teratas (`order: [[0, 'desc']]`).
   - Dilengkapi `data-order` epoch unix timestamp untuk mencegah kesalahan urutan akibat format tampilan tanggal teks lokal.
4. **Pembersihan Warning DataTables**:
   - Mengganti penanganan manual baris kosong Blade (`@empty <td colspan=...>`) dengan native language handler DataTables:
     ```javascript
     language: {
         emptyTable: "Tidak ada data permohonan RKBMD",
         zeroRecords: "Tidak ada permohonan yang sesuai dengan filter/pencarian",
         info: "Menampilkan _START_ s.d. _END_ dari _TOTAL_ permohonan",
         ...
     }
     ```

---

## 3. Hasil Pengujian & Verifikasi

### Pengujian Fitur RKBMD
Dijalankan melalui:
```powershell
php artisan test tests/Feature/Operator/RkbmdTest.php
```
**Hasil:**
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
✓ forwarding operator sees forwarded submission in dedicated tab
✓ forwarding operator can view show page and banner of forwarded submission
✓ forwarded submission status updates when resolved by target operator
✓ rkbmd index filters by date range server side
✓ rkbmd index filters by status server side
✓ rkbmd index filters by keyword search server side
✓ rkbmd index renders datatables assets and data attributes

Tests:    26 passed (121 assertions)
```

### Pengujian Regresi Menyeluruh (Full Test Suite)
Dijalankan melalui:
```powershell
php artisan test
```
**Hasil:**
```text
Tests:    243 passed (1190 assertions)
Duration: 48.92s
```
Semua 243 pengujian di seluruh modul (RBA, Pengumuman, Monitoring, RKBMD, User Management, dsb.) lulus 100% tanpa ada regresi.
