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
            'Murni',
            'Perubahan',
        ];

        foreach ($phases as $phase) {
            RbaPeriod::create([
                'name' => $phase,
            ]);
        }
    }
}
