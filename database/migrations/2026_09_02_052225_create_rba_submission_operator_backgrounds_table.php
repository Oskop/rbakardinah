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
        Schema::dropIfExists('rba_submission_operator_backgrounds');

        Schema::create('rba_submission_operator_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_submission_id')->constrained('rba_submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('background');
            $table->timestamps();

            $table->unique(['rba_submission_id', 'user_id'], 'rba_sub_op_bg_sub_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_submission_operator_backgrounds');
    }
};
