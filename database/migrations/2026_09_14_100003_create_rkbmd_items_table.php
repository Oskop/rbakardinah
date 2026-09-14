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
        Schema::create('rkbmd_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkbmd_submission_id')->constrained('rkbmd_submissions')->cascadeOnDelete();
            $table->foreignId('master_barang_id')->constrained('master_barangs')->cascadeOnDelete();

            $table->decimal('volume', 12, 2);
            $table->string('satuan', 50);
            $table->text('spesifikasi')->nullable();

            // Kolom Tindak Lanjut / Balasan Item
            $table->decimal('volume_disetujui', 12, 2)->nullable();
            $table->string('status_item', 50)->nullable(); // Dipenuhi, Dipenuhi Sebagian, Substitusi, Optimalisasi, Ditolak
            $table->text('catatan_operator')->nullable();

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
        Schema::dropIfExists('rkbmd_items');
    }
};
