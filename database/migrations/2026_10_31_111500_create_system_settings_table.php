<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // 'boolean', 'string', 'integer', 'json'
            $table->string('group')->default('general'); // 'security', 'general', 'rba'
            $table->string('label');
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Inisialisasi pengaturan default keamanan penghapusan RBA (default: nonaktif / aman)
        \Illuminate\Support\Facades\DB::table('system_settings')->insert([
            'key' => 'allow_rba_deletion',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'security',
            'label' => 'Izinkan Penghapusan RBA Header',
            'description' => 'Menentukan apakah tombol Hapus RBA dan tindakan penghapusan RBA diizinkan di sistem. Jika nonaktif, tombol disembunyikan dan endpoint penghapusan dilindungi.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
