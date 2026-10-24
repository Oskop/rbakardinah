# Implementation Plan (Revisi): Optimalisasi Performa Master Barang BMD (`/admin/master-barangs`)

Dokumen ini merevisi rencana implementasi sebelumnya dengan **memaparkan, membandingkan secara komprehensif, dan mengevaluasi metode-metode alternatif non-AJAX** untuk mengatasi kendala memory exhausted (**Allowed memory size of 134217728 bytes exhausted**) pada 13.476 data master barang katalog Permendagri No. 108 Tahun 2016.

---

## 1. Analisis Akar Masalah (Root Cause Analysis)

- **Masalah Utama**: Pemanggilan `$items = MasterBarang::latest()->get()` di [MasterBarangController.php:13](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php#L13) mencoba memuat seluruh **13.476 baris data ke dalam memori PHP sekaligus**.
- **Konsumsi Memori**: Menginstansiasi 13.476 objek model Eloquent dan merender 13.476 tag `<tr>` di Blade menghabiskan **150MB - 180MB RAM**, yang secara instan melampaui limit memori default PHP yaitu **128MB (134.217.728 bytes)**.
- **Beban Browser**: Client-side DataTables di browser dipaksa membaca ribuan node DOM tabel sekaligus, memicu lag dan freeze pada peramban.

---

## 2. Pemaparan & Komparasi Metode Optimalisasi

Untuk menangani dataset 13.476 baris, terdapat **3 metode arsitektur** yang dapat diterapkan:

### Metode 1: Laravel Native Server-Side Pagination & URL State (Rekomendasi Utama Non-AJAX)
*Menggunakan fitur pagination asli Laravel (`->paginate(25)->withQueryString()`) berpadu dengan form pencarian & filter Tailwind murni, serta tautan pagination `{{ $items->links() }}`.*

- **Cara Kerja**:
  1. Pengguna memasukkan kata kunci pencarian atau memilih status, lalu menekan Cari (atau Enter).
  2. Request dikirim via HTTP GET standar dengan parameter URL bersih (`/admin/master-barangs?search=komputer&status=active&page=2`).
  3. Controller hanya mengambil 25 baris data dari database menggunakan query SQL `LIMIT 25 OFFSET ...`.
  4. Template Blade hanya merender 25 baris data, dan library **jQuery 3.7.1 serta DataTables 2.0.3 CDN dihapus sepenuhnya dari halaman**.
- **Kelebihan**:
  - **100% Bebas Dependensi Eksternal**: Menghapus CDN luar (jQuery & DataTables). Ini sangat vital untuk keandalan sistem rumah sakit jika jaringan internet/intranet sedang lambat atau memblokir akses domain CDN luar.
  - **Shareable URL & Bookmark**: Admin dapat menyalin (copy) link URL hasil pencarian atau halaman tertentu dan mengirimkannya ke staf lain, atau menandainya (bookmark).
  - **Dukungan Tombol Back/Forward Browser Sempurna**: Riwayat penelusuran tersimpan di riwayat browser.
  - **Konsumsi RAM Sangat Minimal**: Hanya meng-hydrate 25 objek model ke memori (< 3 MB RAM).
  - **Konsistensi Arsitektur**: Selaras dengan halaman admin lainnya di aplikasi ini yang sudah menggunakan pola ini (seperti `admin/logs` pada [ActivityLogController.php:60](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/ActivityLogController.php#L60)).
- **Kekurangan**:
  - Terjadi reload halaman saat berpindah halaman atau mencari (namun karena hanya memproses 25 baris, waktu reload sangat instan: ~20-40 milidetik).

---

### Metode 2: DataTables Server-Side Processing (AJAX)
*Mempertahankan pustaka DataTables 2.0.3, namun mengubah modenya menjadi `serverSide: true` di mana DataTables meminta JSON 10/25 baris ke controller via background HTTP AJAX.*

- **Cara Kerja**:
  1. Halaman utama hanya memuat tabel kosong (< 5KB).
  2. DataTables mengirimkan request AJAX di latar belakang dengan parameter pagination, sorting, dan filter.
  3. Controller mengembalikan respon format JSON DataTables (`recordsTotal`, `recordsFiltered`, `data`).
- **Kelebihan**:
  - Tidak terjadi reload halaman penuh saat berpindah halaman atau menyaring data (in-place update).
  - Mempertahankan tampilan dan kontrol bawaan DataTables yang sudah ada (pilihan entri 10, 25, 50, 100).
- **Kekurangan**:
  - Tetap bergantung pada library CDN pihak ketiga (jQuery 3.7.1 & DataTables 2.0.3).
  - State pencarian tidak otomatis tersimpan di URL bar (sulit di-bookmark/dishare).
  - Kode controller lebih kompleks karena harus memformat struktur JSON DataTables spesifik dan HTML tombol aksi manual.

---

### Metode 3: Alpine.js Reactive Async Table (Native REST Fetch, Tanpa DataTables/jQuery)
*Menghapus jQuery & DataTables, lalu membangun tabel reaktif menggunakan Alpine.js bawaan project yang memanggil REST API internal via `fetch()`.*

- **Cara Kerja**:
  1. Halaman dirender menggunakan Alpine.js (`x-data="masterBarangsTable()"`).
  2. Alpine mengambil data via `fetch()` dengan debounce 300ms dan merender tabel via `<template x-for="item in items">`.
- **Kelebihan**:
  - Bebas dari jQuery dan DataTables CDN.
  - Tampilan reaktif tanpa reload halaman.
- **Kekurangan**:
  - Membutuhkan penulisan ulang komponen pagination, tombol sorting, dan debounce di Alpine.js dari nol (overhead kode front-end lebih tinggi).

---

## 3. Matriks Perbandingan Komprehensif

| Kriteria Evaluasi | Metode 1: Laravel Native Pagination (Non-AJAX) | Metode 2: DataTables Server-Side (AJAX) | Metode 3: Alpine.js Async Fetch |
|---|---|---|---|
| **Ketergantungan Eksternal (CDN)** | **Nol (0%) — Mandiri Total** | Bergantung pada 2 CDN (jQuery & DataTables) | **Nol (0%) — Menggunakan Alpine bawaan** |
| **Konsumsi Memori RAM PHP** | **Sangat Hemat (< 3 MB)** | **Sangat Hemat (< 3 MB)** | **Sangat Hemat (< 3 MB)** |
| **Waktu Respon Query SQL** | **Sangat Cepat (< 20 ms)** | **Sangat Cepat (< 25 ms)** | **Sangat Cepat (< 25 ms)** |
| **Shareable URL / Bookmark** | **Ya Otomatis** (`?search=meja&page=2`) | Tidak secara default | Butuh koding manual `history.pushState` |
| **Dukungan Tombol Back/Forward** | **Bawaan Browser Sempurna** | Tidak berfungsi untuk state pencarian | Butuh koding listener `popstate` |
| **Ketahanan Jaringan Intranet** | **Sangat Tinggi (Kebal Blokir CDN)** | Rentan jika akses CDN internet terganggu | **Sangat Tinggi** |
| **Ukuran Aset yang Dimuat** | **Paling Ringan (-207 KB)** | Lebih berat (+207 KB library JS/CSS) | Ringan (hanya skrip Alpine kecil) |
| **User Experience (UX)** | Refresh Halaman Penuh (~30ms) | In-place update tanpa reload | In-place update tanpa reload |
| **Kompleksitas Pemeliharaan Kode** | **Paling Sederhana & Standar Laravel** | Sedang (Perlu format array JSON) | Sedang ke Tinggi (Bikin komponen UI sendiri) |

---

## 4. Rekomendasi Solusi

Berdasarkan analisis arsitektur, kestabilan jangka panjang, dan efisiensi sistem, **Metode 1 (Laravel Native Server-Side Pagination)** sangat direkomendasikan karena:
1. **Mengeliminasi Risiko Kegagalan Jaringan**: Sistem rumah sakit sering kali menerapkan firewall ketat atau memiliki koneksi lokal intranet yang membatasi unduhan dari CDN publik luar (`cdn.datatables.net` dan `code.jquery.com`). Metode 1 menjadikan aplikasi 100% mandiri lokal.
2. **Kemudahan Kolaborasi Staf**: Admin dapat membagikan tautan langsung hasil pencarian spesifik ke staf lain (contoh: `http://.../admin/master-barangs?search=1.3.2&status=1`).
3. **Penyelarasan Desain**: Menggunakan komponen Blade Card & Toolbar Tailwind modern yang persis sama dengan halaman menu Admin lainnya di aplikasi SIPAKAR.
4. **Waktu Transisi Sangat Cepat**: Dengan query SQL berindeks hanya mengambil 25 baris, perpindahan halaman terasa instan (< 40 milidetik).

---

## 5. Rencana Perubahan Detail (Jika Menggunakan Metode 1 Rekomendasi)

### 5.1 Perbarui Controller [app/Http/Controllers/Admin/MasterBarangController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/MasterBarangController.php)
```php
public function index(Request $request)
{
    $query = MasterBarang::query();

    // 1. Filter Pencarian Cepat (Kode Barang, Kode Permendagri 108, Nama Barang, Satuan)
    if ($request->filled('search')) {
        $search = trim($request->search);
        $query->where(function ($q) use ($search) {
            $q->where('nama_barang', 'like', "%{$search}%")
              ->orWhere('kode_barang', 'like', "%{$search}%")
              ->orWhere('kode_permendagri', 'like', "%{$search}%")
              ->orWhere('satuan', 'like', "%{$search}%");
        });
    }

    // 2. Filter Status (Semua, Aktif, Nonaktif)
    if ($request->filled('status') && in_array($request->status, ['active', 'inactive'])) {
        $query->where('is_active', $request->status === 'active');
    }

    // 3. Pagination Efisien (25 item per halaman) dengan mempertahankan query string URL
    $items = $query->orderBy('nama_barang')->paginate(25)->withQueryString();

    // Statistik Ringkas
    $totalBarangs = MasterBarang::count();
    $totalActive = MasterBarang::where('is_active', true)->count();

    return view('admin.master_barangs.index', compact('items', 'totalBarangs', 'totalActive'));
}
```

### 5.2 Perbarui Tampilan [resources/views/admin/master_barangs/index.blade.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/resources/views/admin/master_barangs/index.blade.php)
1. Buat **Toolbar Filter & Pencarian** di atas tabel (input search dengan tombol submit/reset, dropdown status aktif/semua).
2. Tampilkan baris data melalui `@forelse($items as $item)` (25 baris).
3. Sertakan komponen pagination bawaan:
   ```html
   @if($items->hasPages())
       <div class="p-4 border-t border-slate-100 bg-slate-50/50">
           {{ $items->links() }}
       </div>
   @endif
   ```
4. **Hapus blok CDN jQuery dan DataTables** pada `@push('styles')` dan `@push('scripts')`.

### 5.3 Perbarui Uji Otomatis [tests/Feature/Admin/PermendagriMasterBarangTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Admin/PermendagriMasterBarangTest.php) & [tests/Feature/Operator/RkbmdTest.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/tests/Feature/Operator/RkbmdTest.php)
- Sesuaikan penegasan uji agar memvalidasi pagination native (misal memeriksa tautan pagination dan pencarian keyword).
- Pastikan seluruh 33 test eksisting tetap **PASS 100%**.

---

## 6. Rencana Verifikasi (Verification Plan)

### Automated Test:
Jalankan pengujian unit & feature test:
```bash
php artisan test --filter="PermendagriMasterBarangTest|RkbmdTest"
```
*Ekspektasi*: Seluruh test suite lulus 100%.

### Benchmark Pemakaian RAM PHP:
Verifikasi bahwa konsumsi memori saat mengakses `/admin/master-barangs` tetap sangat rendah:
```bash
php artisan tinker --execute="echo 'Memory Peak: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB';"
```
*Ekspektasi*: Pemakaian memori di bawah 10 MB (jauh di bawah limit 128 MB).

### Manual Browser Verification:
1. Akses menu `http://127.0.0.1:8000/admin/master-barangs`.
2. Periksa kecepatan render halaman (harus instan tanpa lag).
3. Coba lakukan pencarian barang berdasarkan kode permendagri (contoh: `1.3.2`) atau nama (`meja`).
4. Klik tombol pagination (Halaman 2, 3, Next, Previous) dan pastikan parameter URL sinkron.
5. Uji tombol aksi Edit dan Aktivasi/Deaktivasi.
