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
        Schema::dropIfExists('rba_submission_document_versions');
        Schema::dropIfExists('rba_submission_documents');

        Schema::create('rba_submission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_submission_id')->constrained('rba_submissions')->cascadeOnDelete();
            $table->string('type'); // 'KAK', 'RAK', 'RTP'
            $table->timestamps();

            $table->unique(['rba_submission_id', 'type']);
        });

        Schema::create('rba_submission_document_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rba_submission_document_id');
            $table->string('file_path');
            $table->integer('version_number');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->foreign('rba_submission_document_id', 'fk_doc_versions_doc_id')
                ->references('id')
                ->on('rba_submission_documents')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_submission_document_versions');
        Schema::dropIfExists('rba_submission_documents');
    }
};
