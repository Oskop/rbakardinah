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
        Schema::table('rba_details', function (Blueprint $table) {
            $table->boolean('is_submitted')->default(false)->after('nominal_request');
            $table->softDeletes()->after('rejection_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rba_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('is_submitted');
        });
    }
};
