<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->foreignId('kelompok_belanja_id')->nullable()->after('id')->constrained('kelompok_belanjas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kelompok_belanja_id');
        });
    }
};
