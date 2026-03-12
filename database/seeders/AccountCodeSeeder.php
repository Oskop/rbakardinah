<?php

namespace Database\Seeders;

use App\Models\AccountCode;
use Illuminate\Database\Seeder;

class AccountCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pegawaiId = \App\Models\KelompokBelanja::where('kode', '5.1.01')->first()->id;
        $barangId = \App\Models\KelompokBelanja::where('kode', '5.1.02')->first()->id;
        $modalId = \App\Models\KelompokBelanja::where('kode', '5.1.03')->first()->id;

        $data = [
            // BELANJA PEGAWAI (5.1.01)
            ['kelompok_belanja_id' => $pegawaiId, 'code' => '5.1.01.03.06.0001', 'name' => 'Belanja Insentif Jasa Pelayanan Kesehatan bagi ASN'],
            ['kelompok_belanja_id' => $pegawaiId, 'code' => '5.1.01.03.07.0001', 'name' => 'Belanja Insentif Non Jasa Pelayanan Penanggungjawaban Pengelola Keuangan'],

            // BELANJA BARANG DAN JASA (5.1.02)
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0001', 'name' => 'Belanja Bahan-Bahan Bangunan dan Konstruksi'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0004', 'name' => 'Belanja Bahan-Bahan Bakar dan Pelumas'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0009', 'name' => 'Belanja Bahan-Isi Tabung Pemadam Kebakaran'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0010', 'name' => 'Belanja Bahan-Isi Tabung Gas'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0012', 'name' => 'Belanja Bahan-Bahan Lainnya'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0024', 'name' => 'Belanja Alat/Bahan untuk Kegiatan Kantor- Alat Tulis Kantor'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0026', 'name' => 'Belanja Alat/Bahan untuk Kegiatan Kantor-Bahan Cetak'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0027', 'name' => 'Belanja Alat/Bahan untuk Kegiatan Kantor- Benda Pos'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0031', 'name' => 'Belanja Alat/Bahan untuk Kegiatan Kantor- Alat Listrik'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0035', 'name' => 'Belanja Alat/Bahan untuk Kegiatan Kantor-Suvenir/Cinderamata'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0036', 'name' => 'Belanja Alat/Bahan untuk Kegiatan Kantor-Alat/Bahan untuk Kegiatan Kantor Lainnya'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0037', 'name' => 'Belanja Obat-Obatan-Obat'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0038', 'name' => 'Belanja Obat-Obatan-Obat-Obatan Lainnya'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0052', 'name' => 'Belanja Makanan dan Minuman Rapat'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0053', 'name' => 'Belanja Makanan dan Minuman Jamuan Tamu'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0054', 'name' => 'Belanja Penambah Daya Tahan Tubuh'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0056', 'name' => 'Belanja Makanan dan Minuman pada Fasilitas Pelayanan Urusan Kesehatan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.01.01.0063', 'name' => 'Belanja Pakaian Dinas Harian (PDH)'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0003', 'name' => 'Belanja Insentif Narasumber atau Pembahas, Moderator, Pembawa Acara, dan Panitia'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0004', 'name' => 'Belanja Insentif Tim Pelaksana Kegiatan dan Sekretariat Tim Pelaksana Kegiatan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0011', 'name' => 'Belanja Insentif Penyelenggara Kegiatan Pendidikan dan Pelatihan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0014', 'name' => 'Belanja Insentif Jasa Tenaga Kesehatan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0026', 'name' => 'Belanja Jasa Tenaga Administrasi'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0017', 'name' => 'Belanja Jasa Tenaga Ketenteraman, Ketertiban Umum, dan Perlindungan Masyarakat'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0029', 'name' => 'Belanja Jasa Tenaga Ahli'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0030', 'name' => 'Belanja Jasa Tenaga Kebersihan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0036', 'name' => 'Belanja Jasa Audit/Surveillance ISO'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0039', 'name' => 'Belanja Jasa Tenaga Informatika dan Teknologi'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0042', 'name' => 'Belanja Jasa Pelaksanaan Transaksi Keuangan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0046', 'name' => 'Belanja Jasa Konservasi Aplikasi / Sistem Informasi'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0048', 'name' => 'Belanja Jasa Kontribusi Asosiasi'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0055', 'name' => 'Belanja Jasa Iklan/Reklame, Film, dan Pemotretan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0059', 'name' => 'Belanja Tagihan Telepon'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0060', 'name' => 'Belanja Tagihan Air'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0061', 'name' => 'Belanja Tagihan Listrik'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0062', 'name' => 'Belanja Langganan Jurnal/Surat Kabar/Majalah'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0063', 'name' => 'Belanja Kawat/Faksimili/Internet/TV Berlangganan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0064', 'name' => 'Belanja Paket/Pengiriman'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0067', 'name' => 'Belanja Pembayaran Pajak, Bea, dan Perizinan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0069', 'name' => 'Belanja Pengolahan Air Limbah'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.01.0077', 'name' => 'Belanja Jasa Pelayanan Kesehatan bagi Non ASN'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.02.0005', 'name' => 'Belanja Iuran Jaminan Kesehatan bagi Non ASN'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.02.0006', 'name' => 'Belanja Iuran Jaminan Kecelakaan Kerja bagi Non ASN'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.04.0204', 'name' => 'Belanja Sewa Alat Kedokteran Umum'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.04.0207', 'name' => 'Belanja Sewa Alat Kedokteran Bedah'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.04.0218', 'name' => 'Belanja Sewa Alat Kedokteran Radiodiagnostic'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.04.0037', 'name' => 'Belanja Sewa Kendaraan Bermotor Angkutan Barang'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.04.0404', 'name' => 'Belanja Sewa Peralatan Jaringan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.08.0002', 'name' => 'Belanja Jasa Konsultansi Perencanaan Arsitektur-Jasa Desain Arsitektural'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.09.0003', 'name' => 'Belanja Jasa Konsultansi Berorientasi Bidang-Telematika'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.02.12.0001', 'name' => 'Belanja Kursus Singkat/Pelatihan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.02.0035', 'name' => 'Belanja Pemeliharaan Alat Angkutan-Alat Angkutan Darat Bermotor-Kendaraan Dinas Bermotor Perorangan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.02.0117', 'name' => 'Belanja Pemeliharaan Alat Kantor dan Rumah Tangga-Alat Kantor-Alat Kantor Lainnya'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.02.0204', 'name' => 'Belanja Pemeliharaan Alat Kedokteran dan Kesehatan-Alat Kedokteran-Alat Kedokteran Umum'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.02.0403', 'name' => 'Belanja Pemeliharaan Komputer-Komputer Unit-Peralatan Computer'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.02.0404', 'name' => 'Belanja Pemeliharaan Komputer-Komputer Unit-Computer Jaringan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.02.0405', 'name' => 'Belanja Pemeliharaan Komputer-Komputer Unit-Personal Computer'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.03.0006', 'name' => 'Belanja Pemeliharaan Bangunan Gedung- Bangunan Gedung Tempat Kerja-Bangunan Kesehatan'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.03.0036', 'name' => 'Belanja Pemeliharaan Bangunan Gedung- Bangunan Gedung Tempat Kerja-Taman'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.03.04.0126', 'name' => 'Belanja Pemeliharaan Jaringan-Jaringan Listrik-Jaringan Listrik Lainnya'],
            ['kelompok_belanja_id' => $barangId, 'code' => '5.1.02.04.01.0001', 'name' => 'Belanja Perjalanan Dinas Biasa'],

            // BELANJA MODAL (5.1.03)
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.05.02.0001', 'name' => 'Belanja Modal Mebel'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.05.02.0004', 'name' => 'Belanja Modal Alat Pendingin'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.05.02.0006', 'name' => 'Belanja Modal Alat Rumah Tangga Lainnya (Home Use)'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.05.02.0007', 'name' => 'Belanja Modal Vidio dan Audio'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.07.01.0001', 'name' => 'Belanja Modal Alat Kedokteran Umum'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.07.02.0005', 'name' => 'Belanja Modal Alat Kedokteran Lainnya'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.08.01.0011', 'name' => 'Belanja Modal Alat Laboratorium Umum'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.10.01.0002', 'name' => 'Belanja Modal Personal Computer'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.10.02.0003', 'name' => 'Belanja Modal Peralatan Personal Computer'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.10.02.0004', 'name' => 'Belanja Modal Peralatan Jaringan'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.02.10.02.0005', 'name' => 'Belanja Modal Komputer Server'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.04.01.001.0004', 'name' => 'Belanja Modal Jalan'],
            ['kelompok_belanja_id' => $modalId, 'code' => '5.2.04.01.001.0005', 'name' => 'Belanja Modal Pembangunan Gedung'],
        ];

        foreach ($data as $item) {
            AccountCode::create($item);
        }
    }
}
