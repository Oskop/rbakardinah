<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Unit::create(['code' => 'U001', 'name' => 'Unit Pelayanan']);
        Unit::create(['code' => 'U002', 'name' => 'Unit Keperawatan']);
        Unit::create(['code' => 'U003', 'name' => 'Unit Penunjang']);
        Unit::create(['code' => 'U004', 'name' => 'Unit Perencanaan dan Pemasaran']);
        Unit::create(['code' => 'U005', 'name' => 'Unit Umum']);
        Unit::create(['code' => 'U006', 'name' => 'Unit Keuangan']);
    }
}
