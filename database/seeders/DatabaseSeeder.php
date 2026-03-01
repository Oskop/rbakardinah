<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            KelompokBelanjaSeeder::class,
            AccountCodeSeeder::class,
            RbaPeriodSeeder::class,
        ]);

        // Admin User
        User::factory()->create([
            'name' => 'Global Admin',
            'email' => 'admin@hospital.com',
            'password' => bcrypt('password'),
            'role' => 'Administrator',
            'unit_id' => null,
        ]);

        // Supervisor User (Unit Farmasi)
        User::factory()->create([
            'name' => 'Supervisor Farmasi',
            'email' => 'supervisor@hospital.com',
            'password' => bcrypt('password'),
            'role' => 'Supervisor',
            'unit_id' => 1,
        ]);

        // Operator User (Unit Farmasi)
        User::factory()->create([
            'name' => 'Operator Farmasi',
            'email' => 'operator@hospital.com',
            'password' => bcrypt('password'),
            'role' => 'Operator',
            'unit_id' => 1,
        ]);
    }
}
