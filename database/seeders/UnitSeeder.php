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
        Unit::create(['code' => 'U001', 'name' => 'Unit Farmasi']);
        Unit::create(['code' => 'U002', 'name' => 'Unit Keuangan']);
        Unit::create(['code' => 'U003', 'name' => 'Unit IT']);
        Unit::create(['code' => 'U004', 'name' => 'Unit Rawat Inap']);
    }
}
