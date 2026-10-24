# Implementation Plan: Optimalisasi Server-Side DataTables Master Barang BMD (`/admin/master-barangs`)

Dokumen ini menganalisis akar masalah dan menyajikan rencana perbaikan tuntas atas error **"Allowed memory size of 134217728 bytes exhausted"** (Memory Limit 128MB) saat mengakses halaman master barang BMD di `/admin/master-barangs`.

---

## 1. Analisis Akar Masalah (Root Cause Analysis)

### 1.1 Volume Data Baru (13.476 Baris) vs Client-Side Hydration
- Sebelumnya, tabel master barang hanya memiliki sedikit data dummy. Setelah impor katalog Permendagri No. 108 Tahun 2016, tabel `master_barangs` kini memiliki **13.476 item barang**.
- Pada [MasterBarangController.php:13](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php#L13):
  ```php
  public function index()
  {
      $items = MasterBarang::latest()->get();
      return view('admin.master_barangs.index', compact('items'));
  }
  ```
  Method `index` memanggil `MasterBarang::latest()->get()`, yang menginstansiasi **13.476 objek model Eloquent** sekaligus ke dalam RAM PHP.
- Di dalam template [index.blade.php:57-106](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php#L57-L106), Blade melakukan perulangan `@foreach($items as $item)` dan merender 13.476 baris tabel HTML (`<tr>...</tr>`) berukuran ~12-15 MB teks HTML murni.
- Total memori yang dibutuhkan PHP untuk proses hidrasi model dan kompilasi Blade mencapai **150MB - 180MB**, sehingga langsung melampaui batasan memori default PHP yaitu **128MB (134.217.728 bytes)**.
- Sekalipun batas memori PHP dinaikkan, browser pengguna akan mengalami freeze (hang 15-30 detik) karena DataTables client-side harus mem-parsing 13.476 node DOM di sisi browser.

---

## 2. Rencana Solusi (Architecture & Strategy)

Solusi permanen berstandar industri adalah mengubah tabel master barang dari **Client-Side Rendering** menjadi **Native Server-Side Processing (AJAX)** pada DataTables:

```
[Browser DataTables] ---(AJAX Request: start=0, length=10, search="...")---> [MasterBarangController@index]
                                                                                     |
                                                                             SQL Query dengan:
                                                                             - LIMIT 10 OFFSET 0
                                                                             - WHERE LIKE (pencarian)
                                                                             - ORDER BY
                                                                                     |
[Browser Render 10 Baris] <---(JSON Response: recordsTotal, data)----------- [Memory < 5MB, Waktu < 30ms]
```

### 2.1 Keuntungan Server-Side Processing:
1. **Penggunaan RAM PHP Sangat Rendah**: Hanya 10-25 objek yang di-load per request (konsumsi memori PHP turun dari **150MB+ menjadi < 5MB**).
2. **Performa Memuat Super Cepat**: Waktu respon halaman turun dari **15+ detik menjadi < 50 milidetik**.
3. **Pencarian Cepat di Sisi Database**: Pencarian kode rekening, kode Permendagri, maupun nama barang dilakukan langsung via indeks database SQL, bukan memfilter ribuan elemen DOM di browser.
4. **Proteksi Memory Limit**: Penjagaan batas maksimum `length` (maks. 100 baris per AJAX request) untuk mencegah permintaan data masif yang disengaja.

---

## 3. Rencana Perubahan Berkas (Proposed Changes)

### 3.1 Perbarui Controller [app/Http/Controllers/Admin/MasterBarangController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php)
Ubah method `index(Request $request)` agar mendukung dua mode:
1. **Request Normal (Browser GET)**:
   - Mengembalikan view `admin.master_barangs.index` dengan skeleton tabel kosong (tanpa query data berat).
2. **Request AJAX (`$request->ajax()`)**:
   - Membaca parameter standar DataTables:
     - `draw` (integer pengenal request)
     - `start` (offset pagination, default 0)
     - `length` (limit pagination, dibatasi 1 s/d 100 baris)
     - `search.value` (kata kunci pencarian)
     - `order.0.column` & `order.0.dir` (kolom dan arah pengurutan)
   - Melakukan query dengan `LIMIT` dan `OFFSET`.
   - Mengembalikan JSON:
     ```json
     {
         "draw": 1,
         "recordsTotal": 13476,
         "recordsFiltered": 25,
         "data": [ ... ]
     }
     ```
   - Setiap baris data memuat HTML yang sudah di-escape aman (`e(...)`):
     - Kolom 0: `kode_barang` + badge referensi `kode_permendagri`.
     - Kolom 1: `nama_barang` + `deskripsi`.
     - Kolom 2: `satuan`.
     - Kolom 3: Badge Status (`Active` / `Inactive`).
     - Kolom 4: Tombol aksi (`Edit` dan form `Deactivate`/`Activate` dengan CSRF token).

### 3.2 Perbarui Tampilan [resources/views/admin/master_barangs/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php)
1. Kosongkan tag `<tbody>` (hapus `@foreach($items as $item)`). Data akan diisi secara dinamis oleh DataTables AJAX.
2. Perbarui inisialisasi DataTables:
   ```javascript
   $('#master-barangs-table').DataTable({
       processing: true,
       serverSide: true,
       ajax: {
           url: "{{ route('admin.master-barangs.index') }}",
           type: 'GET'
       },
       responsive: true,
       order: [[0, 'desc']],
       columns: [
           { data: 'kode_barang', name: 'kode_barang', className: 'px-6 py-4 whitespace-nowrap text-xs font-mono font-bold text-indigo-700' },
           { data: 'nama_barang', name: 'nama_barang', className: 'px-6 py-4' },
           { data: 'satuan', name: 'satuan', className: 'px-6 py-4 whitespace-nowrap text-xs text-gray-600 font-medium' },
           { data: 'is_active', name: 'is_active', className: 'px-6 py-4 whitespace-nowrap text-xs' },
           { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2' }
       ],
       ...
   });
   ```

### 3.3 Tambahkan Uji Otomatis di [tests/Feature/Admin/PermendagriMasterBarangTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PermendagriMasterBarangTest.php)
Tambahkan test case spesifik untuk:
1. Mengakses `/admin/master-barangs` via browser biasa (memastikan view status 200 tanpa out of memory).
2. Mengakses `/admin/master-barangs` via AJAX request (memverifikasi struktur response JSON `draw`, `recordsTotal`, `recordsFiltered`, dan `data`).
3. Menguji pencarian server-side (`search[value]`) dan pagination (`start`, `length`).

---

## 4. Rencana Verifikasi (Verification Plan)

### Automated Test:
Jalankan seluruh test suite master barang dan RKBMD:
```bash
php artisan test --filter="PermendagriMasterBarangTest|RkbmdTest"
```
*Ekspektasi*: Semua 33+ pengujian berstatus **PASS 100%**.

### Memory Benchmark Test:
Jalankan pengujian konsumsi memori via command line:
```bash
php artisan tinker --execute="echo 'Memory Peak: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';"
```
Memastikan puncak konsumsi memori saat memuat `/admin/master-barangs` tetap jauh di bawah 20MB (dari batas 128MB).

### Manual Browser Verification:
1. Login sebagai Administrator (`admin@hospital.com` atau akun admin aktif).
2. Akses menu `http://127.0.0.1:8000/admin/master-barangs`.
3. Pastikan halaman terbuka secara instan (tanpa error 500 / Memory Exhausted).
4. Coba ketik kata kunci pada kotak *"Cari Barang"* (contoh: `meja`, `komputer`, atau `1.3.2`).
5. Uji tombol pagination (halaman 1, 2, 3...) dan pengurutan kolom.
6. Uji tombol `Edit` dan `Activate`/`Deactivate`.
