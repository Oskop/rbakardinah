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
        Schema::create('hard_delete_archives', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // FQCN: App\Models\RbaHeader, etc.
            $table->unsignedBigInteger('model_id'); // Original ID in source table
            $table->string('label'); // Human readable label
            $table->text('reason')->nullable(); // Alasan penghapusan
            $table->longText('root_data'); // JSON snapshot of the root model
            $table->longText('related_data')->nullable(); // JSON snapshot of all child relations
            $table->json('metadata')->nullable(); // Additional metadata (file paths, etc.)
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->useCurrent();
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('restored_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hard_delete_archives');
    }
};

