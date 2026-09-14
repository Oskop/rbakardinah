<?php

namespace Database\Seeders;

use App\Models\MasterBarang;
use Illuminate\Database\Seeder;

class MasterBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Peralatan Komputer & Elektronik (Permendagri 108: 1.3.2.10)
            [
                'kode_barang' => '1.3.2.10.01.02.001',
                'nama_barang' => 'Personal Computer (PC) Desktop Unit',
                'satuan' => 'Unit',
                'deskripsi' => 'Komputer meja untuk kebutuhan operasional staf pelayanan dan administrasi.',
            ],
            [
                'kode_barang' => '1.3.2.10.01.02.002',
                'nama_barang' => 'Laptop / Notebook Komputer',
                'satuan' => 'Unit',
                'deskripsi' => 'Komputer jinjing portabel untuk dokter spesialis, manajemen, dan verifikator.',
            ],
            [
                'kode_barang' => '1.3.2.10.02.04.001',
                'nama_barang' => 'Printer Laserjet / Inkjet Multifungsi',
                'satuan' => 'Unit',
                'deskripsi' => 'Mesin cetak resep, formulir rekam medis, dan dokumen pelaporan.',
            ],
            [
                'kode_barang' => '1.3.2.10.02.04.002',
                'nama_barang' => 'Barcode Scanner 2D / QR Code Reader',
                'satuan' => 'Unit',
                'deskripsi' => 'Pemindai gelang pasien, barcode rekam medis, dan nomor batch obat.',
            ],
            [
                'kode_barang' => '1.3.2.10.02.03.001',
                'nama_barang' => 'Uninterruptible Power Supply (UPS) 1200VA',
                'satuan' => 'Unit',
                'deskripsi' => 'Cadangan daya listrik darurat untuk perangkat komputer kritis dan server lokal.',
            ],

            // Alat Kedokteran & Kesehatan (Permendagri 108: 1.3.2.07 & 1.3.2.08)
            [
                'kode_barang' => '1.3.2.07.01.01.001',
                'nama_barang' => 'Tensimeter Digital Presisi Medis (Sphygmomanometer)',
                'satuan' => 'Unit',
                'deskripsi' => 'Alat ukur tekanan darah digital terkalibrasi untuk rawat jalan dan rawat inap.',
            ],
            [
                'kode_barang' => '1.3.2.07.01.01.002',
                'nama_barang' => 'Stetoskop Medis Dewasa & Anak',
                'satuan' => 'Buah',
                'deskripsi' => 'Alat auskultasi akustik berkualitas tinggi untuk pemeriksaan fisik dokter.',
            ],
            [
                'kode_barang' => '1.3.2.07.02.03.001',
                'nama_barang' => 'Infusion Pump / Syringe Pump Medis',
                'satuan' => 'Unit',
                'deskripsi' => 'Perangkat pengatur tetesan cairan infus dan obat injeksi presisi tinggi ICU/Rawat Inap.',
            ],
            [
                'kode_barang' => '1.3.2.07.02.01.001',
                'nama_barang' => 'Patient Monitor 5 Parameter',
                'satuan' => 'Unit',
                'deskripsi' => 'Monitor tanda vital pasien (ECG, NIBP, SpO2, Resp, Temp) untuk IGD dan ICU.',
            ],
            [
                'kode_barang' => '1.3.2.07.03.02.001',
                'nama_barang' => 'Suction Pump Portable (Alat Penghisap Lendir)',
                'satuan' => 'Unit',
                'deskripsi' => 'Alat medis hisap cairan dan lendir saluran napas untuk ruang tindakan dan IGD.',
            ],
            [
                'kode_barang' => '1.3.2.07.04.01.001',
                'nama_barang' => 'Tempat Tidur Pasien Manual 3 Crank (Hospital Bed)',
                'satuan' => 'Unit',
                'deskripsi' => 'Tempat tidur rawat inap lengkap dengan kasur anti-decubitus dan tiang infus.',
            ],
            [
                'kode_barang' => '1.3.2.07.04.02.001',
                'nama_barang' => 'Kursi Roda Pasien Standar Rumah Sakit',
                'satuan' => 'Unit',
                'deskripsi' => 'Kursi roda lipat stainless steel untuk mobilisasi pasien di poli dan IGD.',
            ],

            // Mebeuler & Perabot Kantor (Permendagri 108: 1.3.2.05)
            [
                'kode_barang' => '1.3.2.05.01.01.001',
                'nama_barang' => 'Meja Kerja Dokter / Konsul Poliklinik',
                'satuan' => 'Buah',
                'deskripsi' => 'Meja kantor kayu lapis HPL lengkap dengan laci pengunci berkas resep.',
            ],
            [
                'kode_barang' => '1.3.2.05.01.02.001',
                'nama_barang' => 'Kursi Putar Kerja Ergonomis Staf',
                'satuan' => 'Buah',
                'deskripsi' => 'Kursi kerja ergonomis dengan sandaran jaring hidrolik untuk operator dan staf.',
            ],
            [
                'kode_barang' => '1.3.2.05.02.01.001',
                'nama_barang' => 'Lemari Arsip Berkas / Filing Cabinet Besi 4 Pintu',
                'satuan' => 'Unit',
                'deskripsi' => 'Lemari metal anti-karat untuk penyimpanan dokumen rekam medis dan SPJ.',
            ],
            [
                'kode_barang' => '1.3.2.05.03.01.001',
                'nama_barang' => 'Kursi Tunggu Pasien Stainless Steel 4 Dudukan',
                'satuan' => 'Set',
                'deskripsi' => 'Deretan kursi tunggu pasien ruang tunggu poliklinik dan farmasi.',
            ],

            // Pendingin Ruangan & Fasilitas Pelayanan
            [
                'kode_barang' => '1.3.2.05.02.04.001',
                'nama_barang' => 'Air Conditioner (AC) Split Wall 2 PK Inverter',
                'satuan' => 'Unit',
                'deskripsi' => 'Pendingin ruangan efisiensi energi untuk ruang poli, farmasi, dan tindakan.',
            ],
            [
                'kode_barang' => '1.3.2.05.02.05.001',
                'nama_barang' => 'Lemari Pendingin Medis (Vaccine / Reagent Refrigerator)',
                'satuan' => 'Unit',
                'deskripsi' => 'Kulkas khusus penyimpanan obat, vaksin, dan reagensia suhu 2 - 8 derajat Celcius.',
            ],

            // Bahan Habis Pakai / ATK (Persediaan BMD 1.1.7)
            [
                'kode_barang' => '1.1.7.01.01.01.001',
                'nama_barang' => 'Kertas HVS A4 80 Gram',
                'satuan' => 'Rim',
                'deskripsi' => 'Kertas cetak berkas administrasi rekam medis dan pelaporan keuangan.',
            ],
            [
                'kode_barang' => '1.1.7.01.01.02.001',
                'nama_barang' => 'Toner Printer Laserjet Hitam',
                'satuan' => 'Cartridge',
                'deskripsi' => 'Toner cetak original untuk printer penerimaan kasir dan farmasi.',
            ],
        ];

        foreach ($items as $item) {
            MasterBarang::firstOrCreate(
                ['kode_barang' => $item['kode_barang']],
                [
                    'nama_barang' => $item['nama_barang'],
                    'satuan' => $item['satuan'],
                    'deskripsi' => $item['deskripsi'],
                    'is_active' => true,
                ]
            );
        }
    }
}
