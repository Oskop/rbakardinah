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
            ['kode' => '5.1.01', 'name' => 'Belanja Pegawai'],
            ['kode' => '5.1.02', 'name' => 'Belanja Barang dan Jasa'],
            ['kode' => '5.1.03', 'name' => 'Belanja Modal'],
        ];

        foreach ($groups as $group) {
            \App\Models\KelompokBelanja::create($group);
        }
    }
}
