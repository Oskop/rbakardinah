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
        $pegawai = \App\Models\KelompokBelanja::where('name', 'Belanja Pegawai')->first()->id;
        $barang = \App\Models\KelompokBelanja::where('name', 'Belanja Barang & Jasa')->first()->id;
        $modal = \App\Models\KelompokBelanja::where('name', 'Belanja Modal')->first()->id;

        AccountCode::create(['kelompok_belanja_id' => $pegawai, 'code' => '5.1.01.01', 'name' => 'Belanja Gaji & Tunjangan']);
        AccountCode::create(['kelompok_belanja_id' => $barang, 'code' => '5.1.02.01', 'name' => 'Belanja Barang dan Jasa']);
        AccountCode::create(['kelompok_belanja_id' => $barang, 'code' => '5.1.02.02', 'name' => 'Belanja Bahan Habis Pakai']);
        AccountCode::create(['kelompok_belanja_id' => $modal, 'code' => '5.2.01.01', 'name' => 'Belanja Modal Peralatan']);
    }
}
