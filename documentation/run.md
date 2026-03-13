# Panduan Menjalankan Proyek RBA Hospital dari Awal

Dokumen ini berisi langkah-langkah untuk menyiapkan dan menjalankan proyek RBA Hospital di lingkungan pengembangan lokal.

## Persyaratan Sistem

Pastikan perangkat Anda sudah terpasang:
- **PHP**: ^8.2
- **Composer**
- **Bun**
- **Database**: MySQL atau PostgreSQL

---

## Langkah-Langkah Instalasi

### 1. Persiapan Lingkungan
Salin file `.env.example` menjadi `.env` dan sesuaikan konfigurasi database Anda.

```powershell
copy .env.example .env
```

Kemudian pada file php.ini pastikan sudah mengaktifkan extension mbstring, zip, fileinf, pdo_mysql, mysqli
### 2. Instalasi Dependensi
Instal semua package PHP dan JavaScript yang dibutuhkan.

```powershell
composer install
bun install
```

### 3. Konfigurasi Aplikasi
Generate Application Key untuk keamanan Laravel.

```powershell
php artisan key:generate
```

### 3. Inisialisasi Database (via CLI)
Sebelum menjalankan migrasi, Anda perlu membuat database secara manual melalui MySQL CLI.

```powershell
# Masuk ke MySQL (Gunakan password jika ada)
mysql -u root -p

# Jalankan perintah SQL berikut di dalam prompt MySQL
CREATE DATABASE db_rba_hospital;
EXIT;
```

### 4. Setup Database (Laravel)
Jalankan migrasi untuk membuat tabel dan seeder untuk data awal (termasuk user dummy).

```powershell
php artisan migrate --seed
```

---

## Menjalankan Aplikasi

Anda dapat menggunakan perintah singkat yang sudah disediakan di `composer.json` untuk menjalankan server backend dan frontend sekaligus:

```powershell
composer dev
```

Atau menjalankan secara terpisah:

**Backend (Laravel):**
```powershell
php artisan serve
```

**Frontend (Vite):**
```powershell
bun run dev
```

---

## Transfer Data (Export/Import)

Jika Anda ingin memindahkan data transaksional dari satu komputer ke komputer lain:

### 1. Export Data (Komputer Asal)
Jalankan perintah ini untuk membuat file seeder berdasarkan data yang ada di database lokal Anda.

```powershell
php artisan app:export-transactional-data
```
File akan tersimpan di `database/seeders/TransactionalDataSeeder.php`. Salin file ini ke proyek di komputer baru.

### 2. Export File/Lampiran (Komputer Asal)
Selain data database, Anda juga perlu menyalin file-file yang telah diunggah (PDF rincian belanja). Salin folder berikut ke komputer baru:
`storage/app/public/attachments`

### 3. Import Data (Komputer Tujuan)
Setelah file seeder disalin ke folder `database/seeders/` dan folder lampiran disalin ke `storage/app/public/`, jalankan perintah ini di komputer tujuan:

```powershell
# Hubungkan storage link jika belum
php artisan storage:link

# Import data database
php artisan db:seed --class=TransactionalDataSeeder
```

---

## Informasi Login (Default)

Gunakan kredensial berikut untuk masuk ke sistem (Password: `password`):

| Role | Email |
| :--- | :--- |
| **Administrator** | `admin@hospital.com` |
| **Supervisor** | `keuangan@hospital.com` |
| **Operator** | `farmasi@hospital.com` |

> [!NOTE]
> Daftar lengkap user dapat dilihat pada file `database/seeders/DatabaseSeeder.php`.
