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
        if (!Schema::hasColumn('master_barangs', 'kode_permendagri')) {
            Schema::table('master_barangs', function (Blueprint $table) {
                $table->string('kode_permendagri', 50)
                    ->nullable()
                    ->after('kode_barang')
                    ->index('idx_master_barangs_kode_permendagri');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('master_barangs', 'kode_permendagri')) {
            Schema::table('master_barangs', function (Blueprint $table) {
                $table->dropIndex('idx_master_barangs_kode_permendagri');
                $table->dropColumn('kode_permendagri');
            });
        }
    }
};
