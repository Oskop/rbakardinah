<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KelompokBelanjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            'Belanja Pegawai',
            'Belanja Barang & Jasa',
            'Belanja Modal',
        ];

        foreach ($groups as $group) {
            \App\Models\KelompokBelanja::create(['name' => $group]);
        }
    }
}
