<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rba_details', function (Blueprint $table) {
            $table->decimal('volume', 12, 2)->default(1.00)->after('description');
            $table->string('satuan', 50)->default('Pkt')->nullable()->after('volume');
            $table->decimal('harga_satuan', 15, 2)->default(0.00)->after('satuan');
        });

        // Migrasi data lama
        DB::table('rba_details')->update([
            'volume' => 1.00,
            'satuan' => 'Pkt',
            'harga_satuan' => DB::raw('nominal_request')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rba_details', function (Blueprint $table) {
            $table->dropColumn(['volume', 'satuan', 'harga_satuan']);
        });
    }
};
