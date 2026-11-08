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
        Schema::table('rkbmd_submissions', function (Blueprint $table) {
            $table->foreignId('target_unit_id')->nullable()->after('original_operator_id')->constrained('units')->nullOnDelete();
            $table->foreignId('target_sub_unit_id')->nullable()->after('target_unit_id')->constrained('sub_units')->nullOnDelete();
        });

        // Auto-backfill: Isi target_unit_id dan target_sub_unit_id dari data user target_operator_id yang sudah ada
        $submissions = DB::table('rkbmd_submissions')
            ->whereNull('target_unit_id')
            ->whereNotNull('target_operator_id')
            ->get(['id', 'target_operator_id']);

        foreach ($submissions as $sub) {
            $targetUser = DB::table('users')
                ->where('id', $sub->target_operator_id)
                ->first(['unit_id', 'sub_unit_id']);

            if ($targetUser) {
                DB::table('rkbmd_submissions')->where('id', $sub->id)->update([
                    'target_unit_id' => $targetUser->unit_id,
                    'target_sub_unit_id' => $targetUser->sub_unit_id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rkbmd_submissions', function (Blueprint $table) {
            $table->dropForeign(['target_unit_id']);
            $table->dropForeign(['target_sub_unit_id']);
            $table->dropColumn(['target_unit_id', 'target_sub_unit_id']);
        });
    }
};

