# Implementation Plan: Integrasi Master Barang BMD dengan Tabel 2.6 Permendagri No. 108 Tahun 2016

## 1. Konteks & Analisis Kebutuhan

### 1.1 Latar Belakang
Pada aplikasi **SIPAKAR (RBA & RKBMD RSUD Kardinah)**, modul RKBMD (Rencana Kebutuhan Barang Milik Daerah) menggunakan tabel `master_barangs` melalui model [MasterBarang.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/MasterBarang.php). Setiap usulan belanja barang pada `rkbmd_items` bertransaksi menggunakan relasi:
$$\text{rkbmd\_items.master\_barang\_id} \longrightarrow \text{master\_barangs.id}$$

Di sisi lain, telah berhasil diekstrak seluruh master klasifikasi **Tabel 2.6 Permendagri No. 108 Tahun 2016** sebanyak **14.679 baris** ke dalam tabel database `permendagri_108_tabel_2_6`.

### 1.2 Usulan Pengguna
Pengguna mengusulkan:
1. Menambahkan kolom khusus `kode_permendagri` pada tabel `master_barangs`.
2. Mengisi kolom `nama_barang` pada model `MasterBarang` menggunakan `uraian` barang dari Tabel 2.6 Permendagri 108/2016.
3. Menyiapkan **implementation plan** menyeluruh dengan deliverable utama berupa **file SQL siap eksekusi di server**.

---

## 2. Analisis Arsitektur & Perbandingan Opsi Penerapan

### 2.1 Perbedaan Karakteristik Kedua Tabel

| Aspek | Tabel `permendagri_108_tabel_2_6` | Tabel `master_barangs` |
| :--- | :--- | :--- |
| **Fungsi Utama** | **Kamus Standar Nasional BMD** (Buku Babon Regulasi) | **Katalog Operasional Rumah Sakit/BLUD** |
| **Cakupan Data** | 14.679 baris dari Level 1 (Akun `1`) sampai Level 7 (Sub-Sub Rincian `001`) | Item barang riil yang dipesan, diadakan, dan diinventarisasi RSUD Kardinah |
| **Fleksibilitas** | Statis, sesuai lampiran resmi Permendagri 108/2016 | Dinamis, dapat ditambah spesifikasi internal, satuan lokal, merk, dan status aktif/nonaktif |
| **Penggunaan Transaksi** | Referensi pencarian kode / mapping akuntansi | Langsung dipakai pada transaksi `rkbmd_items` dan modul RBA |

---

### 2.2 Analisis Data: Level Mana yang Diimpor ke `master_barangs`?

Berdasarkan hasil analisis hierarki pada Tabel 2.6:
* **Level 1 s.d. Level 6 (1.206 baris)**: Merupakan nama kelompok penggolongan (misal `1` = ASET, `1.3.2.10` = KOMPUTER, `1.3.2.10.01.02` = Personal Computer). Ini adalah header kategori, bukan unit barang fisik yang dibeli.
* **Level 7 (13.473 baris)**: Merupakan **unit barang fisik konkret** (misal `1.3.2.10.01.02.001` = *Personal Computer (PC) Desktop*, `1.3.2.07.01.01.005` = *Tensimeter*, `1.3.2.02.01.06.001` = *Mobil Ambulance*).
* **Temuan**: Seluruh 980 kategori Level 6 memiliki anak di Level 7 (100% *coverage*).

> **Rekomendasi Arsitektur**:
> Data yang diimpor ke tabel operasional `master_barangs` adalah **seluruh item Level 7 (13.473 item)**. Tabel `permendagri_108_tabel_2_6` tetap dipertahankan utuh sebagai master kamus pencarian hierarkis (cascading dropdown / navigasi pohon akun).

---

### 2.3 Solusi Isu Kode Unik (Handling 16 Pasang Kode Kemendagri Ber-tanda Petik)

Pada dokumen resmi Kemendagri, pada halaman 214–216 (bagian Meja & Kursi Pejabat), terdapat **16 pasang kode duplikat** yang oleh Kemendagri ditandai dengan tanda petik (misal `'001`, `'002'`, `'003'`).
Contoh:
* `1.3.2.05.03.04.002` (tanpa petik): *Kursi Rapat Pejabat Eselon I/Wakil Gubernur...*
* `1.3.2.05.03.04.002` (dengan petik `'002`): *Kursi Rapat Ruangan Rapat Ketua/Wakil Ketua DPRD*

Jika `kode_barang` pada `master_barangs` memiliki `UNIQUE constraint`, maka:
* Kolom `kode_permendagri` diisi kode regulasi: `1.3.2.05.03.04.002`.
* Kolom `kode_barang` dijamin unik dengan menambahkan postfix pembeda yang rapi:
  * Baris tanpa petik: `1.3.2.05.03.04.002`
  * Baris berpetik: `1.3.2.05.03.04.002.1` (atau `1.3.2.05.03.04.002-A`)

Dengan mekanisme ini, `UNIQUE constraint` pada `kode_barang` tetap terpenuhi 100%, dan `kode_permendagri` tetap merefleksikan kode asli Permendagri 108.

---

## 3. Desain Skema Database & Perubahan Kode

### 3.1 Skema Tabel `master_barangs` (Revisi Terpadu)

```sql
CREATE TABLE `master_barangs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_barang` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Kode identitas unik barang di sistem SIPAKAR',
  `kode_permendagri` VARCHAR(50) DEFAULT NULL COMMENT 'Kodefikasi Level 7 Permendagri No. 108 Tahun 2016',
  `nama_barang` VARCHAR(255) NOT NULL COMMENT 'Nama/Uraian barang',
  `satuan` VARCHAR(50) DEFAULT 'Unit' COMMENT 'Satuan default pemesanan/pengukuran',
  `deskripsi` TEXT DEFAULT NULL COMMENT 'Spesifikasi atau informasi hierarki kategori',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Status aktif untuk pemesanan RKBMD',
  
  -- Audit Columns
  `created_by` BIGINT UNSIGNED DEFAULT NULL,
  `updated_by` BIGINT UNSIGNED DEFAULT NULL,
  `deleted_by` BIGINT UNSIGNED DEFAULT NULL,
  
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  
  INDEX `idx_master_barangs_kode_permendagri` (`kode_permendagri`),
  INDEX `idx_master_barangs_is_active` (`is_active`),
  FULLTEXT INDEX `ft_master_barangs_search` (`nama_barang`, `kode_barang`, `kode_permendagri`),
  
  CONSTRAINT `fk_master_barangs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_master_barangs_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_master_barangs_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 3.2 Penyesuaian Model Eloquent Laravel

Pada [app/Models/MasterBarang.php](file:///c:/Users/PC12/Project/rbakardinah/app/Models/MasterBarang.php):

1. **Tambahkan `kode_permendagri` ke `$fillable`**:
```php
protected $fillable = [
    'kode_barang',
    'kode_permendagri',
    'nama_barang',
    'satuan',
    'deskripsi',
    'is_active',
    'created_by',
    'updated_by',
    'deleted_by',
];
```

2. **Tambahkan Relasi ke Tabel Referensi Permendagri (Opsional tapi direkomendasikan)**:
```php
public function referensiPermendagri()
{
    return $this->belongsTo(PermendagriTabel26::class, 'kode_permendagri', 'kode');
}
```

3. **Scope Pencarian**:
```php
public function scopeSearch($query, $keyword)
{
    return $query->where(function ($q) use ($keyword) {
        $q->where('nama_barang', 'like', "%{$keyword}%")
          ->orWhere('kode_barang', 'like', "%{$keyword}%")
          ->orWhere('kode_permendagri', 'like', "%{$keyword}%");
    });
}
```

---

## 4. Rencana Kerja & File Deliverable (SQL File)

Sesuai instruksi pengguna (*"sebatas sampai menghasilkan file sql saja, jadi saya tinggal eksekusi di server"*):

### Tahap 1: Pembuatan Generator SQL
Membuat skrip Python di lingkungan `p311` yang membaca data hasil ekstraksi Tabel 2.6 (khusus **Level 7**) dan memetakan:
* `kode_barang` $\leftarrow$ `kode` Permendagri (dengan postfix `.1` khusus untuk 16 baris ber-apostrof).
* `kode_permendagri` $\leftarrow$ `kode` Permendagri asli (`1.x.x.xx.xx.xx.xxx`).
* `nama_barang` $\leftarrow$ `uraian` dari Tabel 2.6.
* `satuan` $\leftarrow$ disesuaikan dengan jenis barang (misal: Obat $\rightarrow$ `Botol`/`Ampul`/`Tablet`/`Pcs`, Alat Medis & Mesin $\rightarrow$ `Unit`, Peralatan Kantor $\rightarrow$ `Unit`/`Buah`, Bahan Konstruksi $\rightarrow$ `Kg`/`Meter`/`M3`, default: `'Unit'`).
* `deskripsi` $\leftarrow$ hierarki asal barang (misal: `Persediaan > Barang Pakai Habis > Bahan > Bahan Bangunan dan Konstruksi`).
* `is_active` $\leftarrow$ `1` (Aktif).

### Tahap 2: Menghasilkan File SQL Siap Eksekusi
File deliverable:
`data/permendagri_108_2016/populate_master_barangs_from_permendagri.sql`

Karakteristik file SQL ini:
1. **Idempoten & Aman**:
   * Jika tabel `master_barangs` belum ada: menjalankan DDL `CREATE TABLE master_barangs`.
   * Jika tabel `master_barangs` sudah ada: menjalankan DDL `ALTER TABLE master_barangs ADD COLUMN IF NOT EXISTS kode_permendagri ...`.
2. **Bulk Insert Optimal**:
   * Menggunakan chunk multi-row `INSERT INTO ... VALUES (...)` dengan ukuran 500 baris per statement (sehingga total 13.473 baris tuntas dieksekusi hanya dalam 27 query / < 2 detik di MySQL).
3. **Menggunakan `INSERT INTO ... ON DUPLICATE KEY UPDATE`**:
   * Menjamin tidak ada error duplikasi jika dieksekusi berulang kali di server.

---

## 5. Cara Eksekusi di Server (Petunjuk Pengguna)

Setelah file SQL terbentuk, pengguna dapat menjalankannya di server dengan salah satu cara berikut:

### Opsi A: Via Command Line MySQL (Windows Server / PowerShell)
```powershell
& 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe' -u root -p --default-character-set=utf8mb4 db_rba_hospital < "c:\Users\PC12\Project\rbakardinah\data\permendagri_108_2016\populate_master_barangs_from_permendagri.sql"
```

### Opsi B: Via Linux Server (Production)
```bash
mysql -u [username] -p --default-character-set=utf8mb4 [nama_database] < populate_master_barangs_from_permendagri.sql
```

### Opsi C: Via GUI (phpMyAdmin / Navicat / DBeaver)
* Buka database `db_rba_hospital`.
* Pilih menu **Import** atau buka SQL Editor.
* Buka dan jalankan file `populate_master_barangs_from_permendagri.sql`.

---

## 6. Verifikasi & Pengujian Pasca Eksekusi

Kueri untuk memverifikasi data di server:
```sql
-- 1. Cek total baris yang berhasil masuk (target: 13.473 item Level 7)
SELECT COUNT(*) FROM master_barangs;

-- 2. Cek sampel item medis / RSUD
SELECT kode_barang, kode_permendagri, nama_barang, satuan 
FROM master_barangs 
WHERE nama_barang LIKE '%Tensimeter%' 
   OR nama_barang LIKE '%Stetoskop%' 
   OR nama_barang LIKE '%Ambulance%'
LIMIT 10;

-- 3. Cek sampel item komputer / IT
SELECT kode_barang, kode_permendagri, nama_barang 
FROM master_barangs 
WHERE kode_permendagri LIKE '1.3.2.10%' 
LIMIT 10;
```
