<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'allow_rba_deletion'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Izinkan Penghapusan RBA Header',
                'description' => 'Menentukan apakah tombol Hapus RBA dan tindakan penghapusan RBA diizinkan di sistem. Jika nonaktif, tombol disembunyikan dan endpoint penghapusan dilindungi.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

