<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountCode;
use App\Models\SubUnitAccountCode;
use App\Models\KelompokBelanja;

class SubUnitAccountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pegawaiId = KelompokBelanja::where('kode', '5.1.01')->first()?->id ?? 1;
        $barangId = KelompokBelanja::where('kode', '5.1.02')->first()?->id ?? 2;
        $modalId = KelompokBelanja::where('kode', '5.1.03')->first()?->id ?? 3;

        $items = [
            [
                'code' => '5.1.01.01.01.0001',
                'uraian' => 'Belanja Gaji dan Tunjangan ASN',
                'sub_unit_id' => 49,
                'keterangan' => null,
            ],
            [
                'code' => '5.1.01.03.06.0001',
                'uraian' => 'Belanja Insentif Jasa Pelayanan Kesehatan bagi ASN',
                'sub_unit_id' => 49,
                'keterangan' => null,
            ],
            [
                'code' => '5.1.01.03.07.0001',
                'uraian' => 'Belanja Insentif Non Jasa Pelayanan Penanggungjawaban Pengelola Keuangan',
                'sub_unit_id' => 49,
                'keterangan' => null,
            ],
            [
                'code' => '5.1.01.03.07.0002',
                'uraian' => 'Belanja Insentif Non Jasa Pelayanan Pengadaan Barang/Jasa',
                'sub_unit_id' => 49,
                'keterangan' => null,
            ],
            [
                'code' => '5.1.02.01.01.0001',
                'uraian' => 'Belanja Bahan-Bahan Bangunan dan Konstruksi',
                'sub_unit_id' => 43,
                'keterangan' => 'Belanja Bahan Bangunan',
            ],
            [
                'code' => '5.1.02.01.01.0004',
                'uraian' => 'Belanja Bahan-Bahan Bakar dan Pelumas',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Bahan Bakar Minyak/Gas dan Pelumas',
            ],
            [
                'code' => '5.1.02.01.01.0009',
                'uraian' => 'Belanja Bahan-Isi Tabung Pemadam Kebakaran',
                'sub_unit_id' => 37,
                'keterangan' => 'Belanja Pengisian Tabung Pemadam Kebakaran',
            ],
            [
                'code' => '5.1.02.01.01.0010',
                'uraian' => 'Belanja Bahan-Isi Tabung Gas',
                'sub_unit_id' => 36,
                'keterangan' => 'Belanja Pengisian Tabung Gas Elpiji',
            ],
            [
                'code' => '5.1.02.01.01.0012',
                'uraian' => 'Belanja Bahan-Bahan Lainnya',
                'sub_unit_id' => 32,
                'keterangan' => 'Belanja Bahan Linen dan Loundry',
            ],
            [
                'code' => '5.1.02.01.01.0024',
                'uraian' => 'Belanja Alat/Bahan untuk Kegiatan Kantor- Alat Tulis Kantor',
                'sub_unit_id' => 45,
                'keterangan' => 'Belanja Alat Tulis Kantor',
            ],
            [
                'code' => '5.1.02.01.01.0026',
                'uraian' => 'Belanja Alat/Bahan untuk Kegiatan Kantor-Bahan Cetak',
                'sub_unit_id' => 45,
                'keterangan' => 'Belanja Cetak dan Penggandaan',
            ],
            [
                'code' => '5.1.02.01.01.0027',
                'uraian' => 'Belanja Alat/Bahan untuk Kegiatan Kantor- Benda Pos',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Perangko, Meterai dan Benda Pos lainnya',
            ],
            [
                'code' => '5.1.02.01.01.0031',
                'uraian' => 'Belanja Alat/Bahan untuk Kegiatan Kantor- Alat Listrik',
                'sub_unit_id' => 43,
                'keterangan' => 'Belanja Alat Listrik dan Elektronik',
            ],
            [
                'code' => '5.1.02.01.01.0035',
                'uraian' => 'Belanja Alat/Bahan untuk Kegiatan Kantor-Suvenir/Cinderamata',
                'sub_unit_id' => 47,
                'keterangan' => 'Belanja Bahan Suvenir/Cinderamata',
            ],
            [
                'code' => '5.1.02.01.01.0036',
                'uraian' => 'Belanja Alat/Bahan untuk Kegiatan Kantor-Alat/Bahan untuk Kegiatan Kantor Lainnya',
                'sub_unit_id' => 45,
                'keterangan' => 'Belanja Bahan Alat Rumah Tangga',
            ],
            [
                'code' => '5.1.02.01.01.0037',
                'uraian' => 'Belanja Obat-Obatan-Obat',
                'sub_unit_id' => 30,
                'keterangan' => 'Belanja Bahan Obat-obatan',
            ],
            [
                'code' => '5.1.02.01.01.0038',
                'uraian' => 'Belanja Obat-Obatan-Obat-Obatan Lainnya',
                'sub_unit_id' => 30,
                'keterangan' => 'Belanja Bahan Alat Kesehatan Habis Pakai & BDRS & Kimia',
            ],
            [
                'code' => '5.1.02.01.01.0052',
                'uraian' => 'Belanja Makanan dan Minuman Rapat',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Makanan dan Minuman Rapat',
            ],
            [
                'code' => '5.1.02.01.01.0053',
                'uraian' => 'Belanja Makanan dan Minuman Jamuan Tamu',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Makanan dan Minuman Tamu',
            ],
            [
                'code' => '5.1.02.01.01.0054',
                'uraian' => 'Belanja Penambah Daya Tahan Tubuh',
                'sub_unit_id' => 36,
                'keterangan' => 'Belanja Makanan dan Minuman Pegawai',
            ],
            [
                'code' => '5.1.02.01.01.0056',
                'uraian' => 'Belanja Makanan dan Minuman pada Fasilitas Pelayanan Urusan Kesehatan',
                'sub_unit_id' => 36,
                'keterangan' => 'Belanja Makanan dan Minuman Pasien',
            ],
            [
                'code' => '5.1.02.02.01.0003',
                'uraian' => 'Belanja Insentif Narasumber atau Pembahas, Moderator, Pembawa Acara, dan Panitia',
                'sub_unit_id' => 49,
                'keterangan' => 'Honorarium Narasumber atau Pembahas, Moderator, Pembawa Acara, dan Panitia',
            ],
            [
                'code' => '5.1.02.02.01.0004',
                'uraian' => 'Belanja Insentif Tim Pelaksana Kegiatan dan Sekretariat Tim Pelaksana Kegiatan',
                'sub_unit_id' => 49,
                'keterangan' => 'Honorarium Dokter Jaga, Pembina Satpam, Tunjangan Radiasi, Lembur Stok Opname',
            ],
            [
                'code' => '5.1.02.02.01.0011',
                'uraian' => 'Belanja Insentif Penyelenggara Kegiatan Pendidikan dan Pelatihan',
                'sub_unit_id' => 15,
                'keterangan' => 'Honorarium Pengajar Kordik',
            ],
            [
                'code' => '5.1.02.02.01.0014',
                'uraian' => 'Belanja Insentif Jasa Tenaga Kesehatan',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Tenaga Pegawai Non ASN (Dokter, Perawat, Instalasi Laboratorium, Radiologi, CSSD, Fisioterapi)',
            ],
            [
                'code' => '5.1.02.02.01.0026',
                'uraian' => 'Belanja Jasa Tenaga Administrasi',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Tenaga Pegawai Non ASN (Manajemen, Keuangan, Umum, Gizi, IPSRS, IPL, Loundry)',
            ],
            [
                'code' => '5.1.02.02.01.0017',
                'uraian' => 'Belanja Jasa Tenaga Ketenteraman, Ketertiban Umum, dan Perlindungan Masyarakat',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Keamanan, Penguburan Pasien Tidak mampu/Terlantar',
            ],
            [
                'code' => '5.1.02.02.01.0029',
                'uraian' => 'Belanja Jasa Tenaga Ahli',
                'sub_unit_id' => 37,
                'keterangan' => 'Belanja Jasa Tenaga Ahli',
            ],
            [
                'code' => '5.1.02.02.01.0030',
                'uraian' => 'Belanja Jasa Tenaga Kebersihan',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Jasa Cleaning Service',
            ],
            [
                'code' => '5.1.02.02.01.0036',
                'uraian' => 'Belanja Jasa Audit/Surveillance ISO',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Kontribusi Akreditasi / ISO',
            ],
            [
                'code' => '5.1.02.02.01.0042',
                'uraian' => 'Belanja Jasa Pelaksanaan Transaksi Keuangan',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Pelaksanaan Transaksi Keuangan',
            ],
            [
                'code' => '5.1.02.02.01.0046',
                'uraian' => 'Belanja Jasa Konversi Aplikasi/Sistem Informasi',
                'sub_unit_id' => 42,
                'keterangan' => 'Belanja Jasa Konversi Aplikasi/Sistem Informasi',
            ],
            [
                'code' => '5.1.02.02.01.0048',
                'uraian' => 'Belanja Jasa Kontribusi Asosiasi',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Kontribusi ARSADA & PERSI',
            ],
            [
                'code' => '5.1.02.02.01.0055',
                'uraian' => 'Belanja Jasa Iklan/Reklame, Film, dan Pemotretan',
                'sub_unit_id' => 41,
                'keterangan' => 'Belanja Advertensi',
            ],
            [
                'code' => '5.1.02.02.01.0059',
                'uraian' => 'Belanja Tagihan Telepon',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Telepon',
            ],
            [
                'code' => '5.1.02.02.01.0060',
                'uraian' => 'Belanja Tagihan Air',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Air',
            ],
            [
                'code' => '5.1.02.02.01.0061',
                'uraian' => 'Belanja Tagihan Listrik',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Listrik',
            ],
            [
                'code' => '5.1.02.02.01.0062',
                'uraian' => 'Belanja Langganan Jurnal/Surat Kabar/Majalah',
                'sub_unit_id' => 41,
                'keterangan' => 'Belanja Surat Kabar/Majalah',
            ],
            [
                'code' => '5.1.02.02.01.0063',
                'uraian' => 'Belanja Kawat/Faksimili/Internet/TV Berlangganan',
                'sub_unit_id' => 42,
                'keterangan' => 'Belanja Internet',
            ],
            [
                'code' => '5.1.02.02.01.0064',
                'uraian' => 'Belanja Paket/Pengiriman',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Paket /Pengiriman',
            ],
            [
                'code' => '5.1.02.02.01.0067',
                'uraian' => 'Belanja Pembayaran Pajak, Bea, dan Perizinan',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Surat Tanda Nomor Kendaraan/ Denda Pajak / Ijin Operasional RS',
            ],
            [
                'code' => '5.1.02.02.01.0069',
                'uraian' => 'Belanja Pengolahan Air Limbah',
                'sub_unit_id' => 37,
                'keterangan' => 'Belanja Pengolahan Air Limbah',
            ],
            [
                'code' => '5.1.02.02.01.0077',
                'uraian' => 'Belanja Jasa Pelayanan Kesehatan bagi Non ASN',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Pelayanan Kesehatan bagi Non ASN',
            ],
            [
                'code' => '5.1.02.02.02.0005',
                'uraian' => 'Belanja Iuran Jaminan Kesehatan bagi Non ASN',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Iuran Jaminan Kesehatan bagi Non ASN',
            ],
            [
                'code' => '5.1.02.02.02.0006',
                'uraian' => 'Belanja Iuran Jaminan Kecelakaan Kerja bagi Non ASN',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Iuran Jaminan Kecelakaan Kerja bagi Non ASN',
            ],
            [
                'code' => '5.1.02.02.04.0204',
                'uraian' => 'Belanja Sewa Alat Kedokteran Umum',
                'sub_unit_id' => 38,
                'keterangan' => 'Belanja Sewa Alat Kedokteran Umum - CTScan',
            ],
            [
                'code' => '5.1.02.02.04.0207',
                'uraian' => 'Belanja Sewa Alat Kedokteran Bedah',
                'sub_unit_id' => 38,
                'keterangan' => 'Belanja Sewa Alat Kedokteran Bedah - MOT',
            ],
            [
                'code' => '5.1.02.02.04.0218',
                'uraian' => 'Belanja Sewa Alat Kedokteran Radiodiagnostic',
                'sub_unit_id' => 38,
                'keterangan' => 'Belanja Sewa Alat Kedokteran Radiodiagnostic - MRI',
            ],
            [
                'code' => '5.1.02.02.04.0037',
                'uraian' => 'Belanja Sewa Kendaraan Bermotor Angkutan Barang',
                'sub_unit_id' => 45,
                'keterangan' => 'Belanja Sewa Kendaraan Bermotor Angkutan Barang',
            ],
            [
                'code' => '5.1.02.02.04.0404',
                'uraian' => 'Belanja Sewa Peralatan Jaringan',
                'sub_unit_id' => 42,
                'keterangan' => 'Belanja Sewa Peralatan Jaringan',
            ],
            [
                'code' => '5.1.02.02.08.0002',
                'uraian' => 'Belanja Jasa Konsultansi Perencanaan Arsitektur-Jasa Desain Arsitektural',
                'sub_unit_id' => 45,
                'keterangan' => 'Belanja Jasa Konsultansi Perencanaan Arsitektur-Jasa Desain Arsitektural',
            ],
            [
                'code' => '5.1.02.02.09.0003',
                'uraian' => 'Belanja Jasa Konsultansi Berorientasi Bidang-Telematika',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Konsultansi Berorientasi Bidang-Telematika',
            ],
            [
                'code' => '5.1.02.02.12.0001',
                'uraian' => 'Belanja Kursus Singkat/Pelatihan',
                'sub_unit_id' => 47,
                'keterangan' => 'Belanja Kursus singkat/pelatihan',
            ],
            [
                'code' => '5.1.02.02.13.0001',
                'uraian' => 'Belanja Jasa Pelayanan Kesehatan bagi ASN',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Jasa Pelayanan Kesehatan bagi ASN',
            ],
            [
                'code' => '5.1.02.03.02.0035',
                'uraian' => 'Belanja Pemeliharaan Alat Angkutan-Alat Angkutan Darat Bermotor-Kendaraan Dinas Bermotor Perorangan',
                'sub_unit_id' => 46,
                'keterangan' => 'Belanja Jasa Service dan Suku Cadang',
            ],
            [
                'code' => '5.1.02.03.02.0117',
                'uraian' => 'Belanja Pemeliharaan Alat Kantor dan Rumah Tangga-Alat Kantor-Alat Kantor Lainnya',
                'sub_unit_id' => 45,
                'keterangan' => 'Belanja Pemeliharaan Peralatan Rumah Tangga',
            ],
            [
                'code' => '5.1.02.03.02.0204',
                'uraian' => 'Belanja Pemeliharaan Alat Kedokteran dan Kesehatan-Alat Kedokteran-Alat Kedokteran Umum',
                'sub_unit_id' => 38,
                'keterangan' => 'Belanja Pemeliharaan Alat-Alat Kedokteran',
            ],
            [
                'code' => '5.1.02.03.02.0403',
                'uraian' => 'Belanja Pemeliharaan Komputer-Komputer Unit-Peralatan Computer',
                'sub_unit_id' => 42,
                'keterangan' => 'Belanja Pemeliharaan Peralatan Komputer',
            ],
            [
                'code' => '5.1.02.03.02.0404',
                'uraian' => 'Belanja Pemeliharaan Komputer-Komputer Unit-Computer Jaringan',
                'sub_unit_id' => 42,
                'keterangan' => 'Belanja Pemeliharaan Komputer Jaringan',
            ],
            [
                'code' => '5.1.02.03.02.0405',
                'uraian' => 'Belanja Pemeliharaan Komputer-Komputer Unit-Personal Computer',
                'sub_unit_id' => 42,
                'keterangan' => 'Belanja Pemeliharaan Personal Komputer dan Laptop',
            ],
            [
                'code' => '5.1.02.03.03.0006',
                'uraian' => 'Belanja Pemeliharaan Bangunan Gedung- Bangunan Gedung Tempat Kerja-Bangunan Kesehatan',
                'sub_unit_id' => 43,
                'keterangan' => 'Belanja Pemeliharaan Bangunan Gedung',
            ],
            [
                'code' => '5.1.02.03.03.0036',
                'uraian' => 'Belanja Pemeliharaan Bangunan Gedung- Bangunan Gedung Tempat Kerja-Taman',
                'sub_unit_id' => 43,
                'keterangan' => 'Belanja Pemeliharaan Taman',
            ],
            [
                'code' => '5.1.02.03.04.0126',
                'uraian' => 'Belanja Pemeliharaan Jaringan-Jaringan Listrik-Jaringan Listrik Lainnya',
                'sub_unit_id' => 43,
                'keterangan' => 'Belanja Pemeliharaan Jaringan Listrik dan Genset',
            ],
            [
                'code' => '5.1.02.04.01.0001',
                'uraian' => 'Belanja Perjalanan Dinas Biasa',
                'sub_unit_id' => 49,
                'keterangan' => 'Belanja Perjalanan Dinas Luar Daerah',
            ],
            [
                'code' => '5.2.02.05.02.0001',
                'uraian' => 'Belanja Modal Mebel',
                'sub_unit_id' => 45,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.05.02.0004',
                'uraian' => 'Belanja Modal Alat Pendingin',
                'sub_unit_id' => 45,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.05.02.0006',
                'uraian' => 'Belanja Modal Alat Rumah Tangga Lainnya (Home Use)',
                'sub_unit_id' => 45,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.06.01.0001',
                'uraian' => 'Belanja Modal Peralatan Studio Video dan Film',
                'sub_unit_id' => 41,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.07.01.0001',
                'uraian' => 'Belanja Modal Alat Kedokteran Umum',
                'sub_unit_id' => 38,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.07.02.0005',
                'uraian' => 'Belanja Modal Alat Kedokteran Umum Lainnya',
                'sub_unit_id' => 38,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.10.01.0002',
                'uraian' => 'Belanja Modal Personal Computer',
                'sub_unit_id' => 42,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.10.02.0003',
                'uraian' => 'Belanja Modal Peralatan Personal Computer',
                'sub_unit_id' => 42,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.10.02.0004',
                'uraian' => 'Belanja Modal Peralatan Jaringan Computer',
                'sub_unit_id' => 42,
                'keterangan' => null,
            ],
            [
                'code' => '5.2.02.10.02.0005',
                'uraian' => 'Belanja Modal Komputer Server',
                'sub_unit_id' => 42,
                'keterangan' => null,
            ],
        ];

        $addedCodes = 0;
        $skippedCodes = 0;
        $createdMappings = 0;

        foreach ($items as $item) {
            // 1. Cek keberadaan kode rekening (STRICT ANTI-OVERWRITE)
            $accountCode = AccountCode::where('code', $item['code'])->first();

            if (!$accountCode) {
                // Tentukan kelompok_belanja_id
                $kelompokId = match (true) {
                    str_starts_with($item['code'], '5.1.01') => $pegawaiId,
                    str_starts_with($item['code'], '5.1.02') => $barangId,
                    str_starts_with($item['code'], '5.2') || str_starts_with($item['code'], '5.1.03') => $modalId,
                    default => $barangId,
                };

                $accountCode = AccountCode::create([
                    'kelompok_belanja_id' => $kelompokId,
                    'code' => $item['code'],
                    'name' => $item['uraian'],
                    'is_active' => true,
                ]);
                $addedCodes++;
                $this->command->info("  [BARU] Nomor Rekening: {$item['code']} - {$item['uraian']}");
            } else {
                // SUDAH ADA: Jangan di-replace / update nama, pertahankan data database!
                $skippedCodes++;
            }

            // 2. Hubungkan ke sub unit (firstOrCreate agar anti-duplikasi)
            $mapping = SubUnitAccountCode::firstOrCreate(
                [
                    'sub_unit_id' => $item['sub_unit_id'],
                    'account_code_id' => $accountCode->id,
                    'fiscal_year' => '2027',
                ],
                [
                    'keterangan_khusus' => $item['keterangan'],
                ]
            );

            if ($mapping->wasRecentlyCreated) {
                $createdMappings++;
            }
        }

        $this->command->info("Seeder Selesai!");
        $this->command->info("- Rekening baru ditambahkan: {$addedCodes}");
        $this->command->info("- Rekening eksisting dipertahankan (skip): {$skippedCodes}");
        $this->command->info("- Relasi mapping sub unit dibuat: {$createdMappings}");
    }
}
