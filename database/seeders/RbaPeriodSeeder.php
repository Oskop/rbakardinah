<?php

namespace Database\Seeders;

use App\Models\RbaPeriod;
use Illuminate\Database\Seeder;

class RbaPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phases = [
            'Perencanaan Murni',
            'Penganggaran Murni',
            'Pergeseran Murni',
            'Perencanaan Perubahan',
            'Penganggaran Perubahan',
            'Pergeseran Perubahan',
        ];

        foreach ($phases as $phase) {
            RbaPeriod::create([
                'name' => $phase,
            ]);
        }
    }
}
