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
        Schema::table('master_barangs', function (Blueprint $table) {
            if (!Schema::hasColumn('master_barangs', 'kode_level5')) {
                $table->string('kode_level5', 20)->nullable()->after('kode_permendagri')->index('idx_master_barangs_kode_level5');
            }
            if (!Schema::hasColumn('master_barangs', 'nama_level5')) {
                $table->string('nama_level5', 255)->nullable()->after('kode_level5')->index('idx_master_barangs_nama_level5');
            }
            if (!Schema::hasColumn('master_barangs', 'kode_level6')) {
                $table->string('kode_level6', 30)->nullable()->after('nama_level5')->index('idx_master_barangs_kode_level6');
            }
            if (!Schema::hasColumn('master_barangs', 'nama_level6')) {
                $table->string('nama_level6', 255)->nullable()->after('kode_level6');
            }
        });

        // Tambah composite index untuk efisiensi cascading dropdown query
        Schema::table('master_barangs', function (Blueprint $table) {
            $table->index(['kode_level5', 'kode_level6'], 'idx_master_barangs_level5_level6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_barangs', function (Blueprint $table) {
            $table->dropIndex('idx_master_barangs_level5_level6');
            if (Schema::hasColumn('master_barangs', 'kode_level5')) {
                $table->dropIndex('idx_master_barangs_kode_level5');
                $table->dropColumn('kode_level5');
            }
            if (Schema::hasColumn('master_barangs', 'nama_level5')) {
                $table->dropIndex('idx_master_barangs_nama_level5');
                $table->dropColumn('nama_level5');
            }
            if (Schema::hasColumn('master_barangs', 'kode_level6')) {
                $table->dropIndex('idx_master_barangs_kode_level6');
                $table->dropColumn('kode_level6');
            }
            if (Schema::hasColumn('master_barangs', 'nama_level6')) {
                $table->dropColumn('nama_level6');
            }
        });
    }
};
