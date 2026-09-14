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
        Schema::create('rkbmd_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_permohonan', 100)->unique();
            $table->year('year');

            // Asal Pemohon
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('sub_unit_id')->nullable()->constrained('sub_units')->nullOnDelete();

            // Operator Tujuan & Alihan
            $table->foreignId('target_operator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('original_operator_id')->constrained('users')->cascadeOnDelete();

            // Konten Pengajuan
            $table->string('title', 255);
            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();

            // Status Permohonan
            $table->enum('status', [
                'Diajukan',
                'Dialihkan',
                'Dipenuhi',
                'Dipenuhi Sebagian',
                'Substitusi',
                'Optimalisasi',
                'Ditolak'
            ])->default('Diajukan');

            // Balasan Akhir
            $table->text('reply_notes')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();

            // Audit Columns
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rkbmd_submissions');
    }
};
