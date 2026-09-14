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
        Schema::create('rkbmd_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkbmd_submission_id')->constrained('rkbmd_submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Aktor yang melakukan tindakan

            $table->string('action', 50); // Pengajuan, Pengalihan, Balasan
            $table->foreignId('from_operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_operator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status_before', 50)->nullable();
            $table->string('status_after', 50)->nullable();
            $table->text('notes')->nullable(); // Alasan pengalihan atau teks balasan

            // Audit Columns
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rkbmd_histories');
    }
};
