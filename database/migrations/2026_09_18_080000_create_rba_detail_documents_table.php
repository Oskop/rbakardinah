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
        Schema::create('rba_detail_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_submission_id')->constrained('rba_submissions')->cascadeOnDelete();
            $table->string('document_name');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_detail_documents');
    }
};
