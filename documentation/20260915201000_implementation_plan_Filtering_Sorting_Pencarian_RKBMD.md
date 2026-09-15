# Rencana Implementasi: Fitur Filtering, Pencarian, dan Pengurutan Data Tabel RKBMD

Menerapkan sistem penyaringan (*filtering* termasuk rentang tanggal dan status), pencarian cepat (*keyword search*), serta pengurutan (*sorting* berdasarkan tanggal permohonan) pada ketiga tabel RKBMD: **Permohonan Saya**, **Permohonan Masuk**, dan **Permohonan Dialihkan**.

---

## User Review Required

> [!IMPORTANT]
> **Pendekatan Dual-Filtering (Server-Side & Client-Side Real-Time)**:
> 1. **Toolbar Filter Terpadu**:
>    - **Rentang Tanggal Pengajuan**: Input `Mulai Tanggal` (*start date*) dan `Sampai Tanggal` (*end date*) untuk menyaring berkas berdasarkan kurun waktu pengajuan permohonan.
>    - **Filter Status**: Dropdown pilihan status (*Semua Status*, *Diajukan*, *Dialihkan*, *Dipenuhi*, *Dipenuhi Sebagian*, *Substitusi*, *Optimalisasi*, *Ditolak*).
>    - **Pencarian Cepat**: Input pencarian bebas (*free-text search*) yang menyaring nomor tiket, perihal kebutuhan, nama pemohon, atau catatan secara instan.
>    - **Tombol Reset Filter**: Mengembalikan seluruh filter ke kondisi semula dengan satu klik.
> 2. **Pengurutan (Sorting) Berdasarkan Tanggal**:
>    - Kolom tanggal/tiket dilengkapi atribut `data-order="{{ $submission->created_at->timestamp }}"` untuk pengurutan kronologis yang akurat.
>    - Secara *default*, tabel menampilkan permohonan terbaru di baris paling atas (`order: [[0, 'desc']]`).
>    - Pengguna dapat mengklik header kolom tanggal untuk membalikkan urutan (ASC / DESC), maupun mengklik kolom lainnya untuk menyortir berdasarkan kolom tersebut.
> 3. **Bebas Error Colspan**: Seluruh `tbody` tabel dibersihkan dari tag `td colspan` agar DataTables dapat mengelola *empty state* dan *zero records* secara *native* tanpa risiko *JavaScript warning*.

---

## Proposed Changes

### 1. Controller & Backend Query

#### [MODIFY] [RkbmdController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Operator/RkbmdController.php)
- Pada method `index()`:
  - Menerima parameter request: `start_date`, `end_date`, `status`, `search`.
  - Menerapkan helper closure query filter:
    ```php
    $applyFilters = function ($query) use ($request) {
        $query->when($request->filled('start_date'), fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
              ->when($request->filled('end_date'), fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
              ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
              ->when($request->filled('search'), function ($q) use ($request) {
                  $search = $request->search;
                  $q->where(function ($subQ) use ($search) {
                      $subQ->where('nomor_permohonan', 'like', "%{$search}%")
                           ->orWhere('title', 'like', "%{$search}%")
                           ->orWhere('notes', 'like', "%{$search}%");
                  });
              });
    };
    ```
  - Menerapkan filter pada ketiga koleksi data (`mySubmissions`, `incomingSubmissions`, dan `forwardedSubmissions`).
  - Mengirim parameter filter ke view agar form input terisi otomatis (*pre-filled*) jika diakses melalui tautan/query URL.

---

### 2. Antarmuka Pengguna (Blade Views & DataTables JavaScript)

#### [MODIFY] [index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/operator/rkbmd/index.blade.php)
- **Komponen Toolbar Filter & Pencarian**:
  - Ditempatkan sebelum kartu tab switcher.
  - Memuat field: `Cari Kata Kunci`, `Status Permohonan`, `Mulai Tanggal`, `Sampai Tanggal`, dan tombol `Reset Filter`.
- **Penyesuaian Baris Tabel (HTML Markup)**:
  - Menambahkan atribut pada setiap `<tr>`:
    `data-date="{{ $sub->created_at->format('Y-m-d') }}" data-status="{{ $sub->status }}"`
  - Menambahkan atribut pada sel tanggal/tiket:
    `<td data-order="{{ $sub->created_at->timestamp }}">`
  - Membersihkan blok `@empty` bertag `td colspan` pada ketiga tabel agar DataTables menangani *zeroRecords* / *emptyTable* secara otomatis dan mulus.
- **Integrasi Library DataTables & Script Filter**:
  - Memuat stylesheet dan script DataTables Tailwind via `@push('styles')` dan `@push('scripts')`.
  - Menginisialisasi ketiga tabel (`#table-my-rkbmd`, `#table-incoming-rkbmd`, `#table-forwarded-rkbmd`) dengan konfigurasi:
    - `responsive: true`
    - `order: [[0, 'desc']]` (terbaru ke terlama)
    - Konfigurasi bahasa Indonesia yang ramah pengguna.
  - Menghubungkan fungsi filter rentang tanggal kustom via `$.fn.dataTable.ext.search.push()` yang membaca atribut `data-date` dan `data-status`.
  - Menyesuaikan lebar kolom DataTables saat perpindahan tab Alpine.js (`$.fn.dataTable.tables({ visible: true, api: true }).columns.adjust()`).

---

## Verification Plan

### Automated Tests
Menambahkan pengujian baru pada `tests/Feature/Operator/RkbmdTest.php`:
1. `test_rkbmd_index_filters_by_date_range_server_side`:
   - Membuat 2 permohonan dengan tanggal berbeda (misal hari ini dan 30 hari yang lalu).
   - Mengakses `operator.rkbmd.index` dengan parameter `start_date` dan `end_date` hari ini.
   - Assert hanya permohonan hari ini yang tampil, permohonan 30 hari lalu tidak muncul.
2. `test_rkbmd_index_filters_by_status_server_side`:
   - Membuat permohonan berstatus `Diajukan` dan permohonan yang sudah dibalas `Dipenuhi`.
   - Mengakses `operator.rkbmd.index?status=Dipenuhi`.
   - Assert hanya permohonan berstatus `Dipenuhi` yang tampil pada koleksi.
3. `test_rkbmd_index_renders_datatables_assets_and_data_attributes`:
   - Memastikan respons halaman memuat script DataTables, filter toolbar, serta atribut `data-date` dan `data-order` pada baris tabel.

Eksekusi pengujian:
```bash
php artisan test tests/Feature/Operator/RkbmdTest.php
php artisan test
```

### Manual Verification
- Buka menu RKBMD (`/operator/rkbmd`).
- Ketik kata kunci pada kotak pencarian -> pastikan baris tabel tersaring secara instan.
- Pilih rentang tanggal pada `Mulai Tanggal` dan `Sampai Tanggal` -> pastikan baris tabel hanya memuat tanggal yang berada dalam rentang tersebut.
- Pilih status "Dipenuhi" -> pastikan hanya permohonan yang sudah dipenuhi yang tampil.
- Klik header kolom "No. Tiket / Tanggal" -> pastikan urutan berbalik dari terlama ke terbaru (ASC) dan klik lagi untuk kembali ke terbaru (DESC).
- Beralih antar tab (Permohonan Saya, Permohonan Masuk, Permohonan Dialihkan) -> pastikan tabel dan styling kolom tampil rapi tanpa error visual.
- Klik tombol "Reset Semua Filter" -> pastikan seluruh data kembali ditampilkan normal.
