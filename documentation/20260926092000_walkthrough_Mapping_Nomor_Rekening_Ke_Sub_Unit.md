# Walkthrough: Eksekusi Mapping Nomor Rekening ke Sub Unit & Integrasi Log Data (SIPAKAR)

## 1. Ringkasan Eksekusi
Seluruh tahapan implementasi pemetaan 74 nomor rekening ke sub-unit berdasarkan acuan Sheet 2 (`Mapping Operator 2027`) telah berhasil dieksekusi dengan aman sesuai arahan pengguna.

### Hasil Utama yang Telah Terlaksana:
1. **Tabel Database Baru**: Dibuat tabel `sub_unit_account_codes` melalui file migrasi [2026_09_26_091500_create_sub_unit_account_codes_table.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/migrations/2026_09_26_091500_create_sub_unit_account_codes_table.php).
2. **Model & Relasi Eloquent**:
   - Model baru [SubUnitAccountCode.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/SubUnitAccountCode.php) dibuat dengan mengimplementasikan trait `LogsActivity`.
   - Relasi `accountCodes()` ditambahkan pada [SubUnit.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/SubUnit.php).
   - Relasi `subUnits()` ditambahkan pada [AccountCode.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Models/AccountCode.php).
3. **Integrasi Log Data (`activity_logs`)**:
   - Handler deskripsi aktivitas human-readable untuk `SubUnitAccountCode` ditambahkan pada [LogsActivity.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Traits/LogsActivity.php).
   - Entri `'SubUnitAccountCode' => 'Mapping Rekening ke Sub Unit'` didaftarkan pada filter dropdown [ActivityLogController.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/app/Http/Controllers/Admin/ActivityLogController.php).
4. **Seeder Aman (Anti-Overwrite)**:
   - Dibuat seeder [SubUnitAccountCodeSeeder.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/SubUnitAccountCodeSeeder.php) yang memuat dataset 74 rekening.
   - Kebijakan proteksi: 70 rekening eksisting dipertahankan 100% tanpa menimpa (*skip without update/replace*), dan hanya 4 rekening baru yang di-insert.
   - Relasi mapping menggunakan `firstOrCreate`.
   - Terdaftar di [DatabaseSeeder.php](file:///c:/Users/PDETUF/Project/Rumah%20Sakit/rbakardinah/database/seeders/DatabaseSeeder.php).

---

## 2. Bukti Verifikasi Database & Seeder

### A. Output Eksekusi Seeder Pertama Kali
```text
INFO  Seeding database.  

[BARU] Nomor Rekening: 5.1.01.01.01.0001 - Belanja Gaji dan Tunjangan ASN
[BARU] Nomor Rekening: 5.1.01.03.07.0002 - Belanja Insentif Non Jasa Pelayanan Pengadaan Barang/Jasa
[BARU] Nomor Rekening: 5.1.02.02.13.0001 - Belanja Jasa Pelayanan Kesehatan bagi ASN
[BARU] Nomor Rekening: 5.2.02.06.01.0001 - Belanja Modal Peralatan Studio Video dan Film
Seeder Selesai!
- Rekening baru ditambahkan: 4
- Rekening eksisting dipertahankan (skip): 70
- Relasi mapping sub unit dibuat: 74
```

### B. Uji Idempotensi (Seeder Dijalankan Ulang)
```text
INFO  Seeding database.  

Seeder Selesai!
- Rekening baru ditambahkan: 0
- Rekening eksisting dipertahankan (skip): 74
- Relasi mapping sub unit dibuat: 0
```
> [!TIP]
> Terbukti bahwa data eksisting tidak pernah ditimpa atau digandakan saat seeder dipanggil berulang kali.

---

## 3. Matriks Hasil Pemetaan di Database (`sub_unit_account_codes`)

Total data mapping tersimpan: **74 relasi**.

| No | Sub Unit di Database SIPAKAR | ID DB | Jumlah Rekening | Keterangan Status |
|:---:|:---|:---:|:---|:---:|:---|
| 1 | **Sub Bag. Anggaran** | 49 | **17** | Termasuk 4 Rekening Belanja Pegawai (Gaji ASN, Jaspel ASN, Insentif Keuangan & PBJ) |
| 2 | **Sub Bag. Tata Usaha** | 46 | **12** | BBM, Meterai, Jamuan Tamu/Rapat, CS, Audit ISO, Listrik, Air, Ekspedisi, Pajak/STNK |
| 3 | **Unit PDE** | 42 | **10** | Modal PC/Server/Jaringan/Peralatan PC, Sewa Jaringan, Jasa Konversi Sistem, Internet, Pemeliharaan IT |
| 4 | **Sub Bag. Perlengkapan dan RT** | 45 | **9** | ATK, Cetak/Penggandaan, Alat Rumah Tangga, Sewa Angkutan Barang, Desain Arsitektural, Modal Mebel/Pendingin |
| 5 | **Unit PPM** | 38 | **6** | Pemeliharaan Alkes Kedokteran (eks-Subag PPM) + Sewa CT-Scan/MOT/MRI + Modal Alkes |
| 6 | **IPSRS** | 43 | **5** | Bahan Bangunan, Alat Listrik/Elektronik, Pemeliharaan Gedung, Taman, Listrik & Genset |
| 7 | **Sub Bag. Pemasaran dan Humas** | 41 | **3** | Belanja Advertensi/Iklan, Langganan Majalah, Modal Peralatan Studio Video & Film |
| 8 | **IPLRS** | 37 | **3** | Isi Tabung APAR, Jasa Tenaga Ahli IPL, Pengolahan Air Limbah / B3 |
| 9 | **Instalasi Gizi** | 36 | **3** | Tabung Gas LPG, Makanan Pegawai (Natura), Makanan Pasien |
| 10 | **Sub Bag. Kepegawaian** | 47 | **2** | Souvenir/Cinderamata, Kursus Singkat / Pelatihan Pegawai |
| 11 | **Instalasi Farmasi** | 30 | **2** | Belanja Obat-Obatan, Bahan Alkes Habis Pakai (BMHP), BDRS, Kimia/Reagen |
| 12 | **Instalasi Loundry** | 32 | **1** | Belanja Bahan Linen dan Laundry |
| 13 | **Komkordik** | 15 | **1** | Insentif Pengajar/Pendidik Klinis Dokter Kordik |
| **TOTAL** | | | **74** | **100% Sesuai Target** |

---

## 4. Bukti Pencatatan di Menu Log Data (`activity_logs`)

Seluruh aktivitas seeder otomatis tercatat di tabel `activity_logs` (Total bertambah dari 206 menjadi **284 log data**):
* **Log Penambahan Rekening Baru (4 log)**:
  - `[App\Models\AccountCode] Sistem menambahkan Nomor Rekening: "5.1.01.01.01.0001 - Belanja Gaji dan Tunjangan ASN"`
  - `[App\Models\AccountCode] Sistem menambahkan Nomor Rekening: "5.1.01.03.07.0002 - Belanja Insentif Non Jasa Pelayanan Pengadaan Barang/Jasa"`
  - `[App\Models\AccountCode] Sistem menambahkan Nomor Rekening: "5.1.02.02.13.0001 - Belanja Jasa Pelayanan Kesehatan bagi ASN"`
  - `[App\Models\AccountCode] Sistem menambahkan Nomor Rekening: "5.2.02.06.01.0001 - Belanja Modal Peralatan Studio Video dan Film"`
* **Log Pemetaan Sub Unit (74 log)**:
  - Contoh: `[App\Models\SubUnitAccountCode] Sistem menambahkan mapping rekening [5.2.02.10.02.0005] Belanja Modal Komputer Server ke Unit PDE (Tahun 2027)`
  - Contoh: `[App\Models\SubUnitAccountCode] Sistem menambahkan mapping rekening [5.1.02.01.01.0024] Belanja Alat Tulis Kantor ke Sub Bag. Perlengkapan dan RT (Tahun 2027)`
* **Filter Menu Log Data**:
  - Administrator kini dapat membuka menu **/admin/logs** dan memilih filter dropdown **Model: Mapping Rekening ke Sub Unit** untuk melihat seluruh riwayat ini secara instan.
