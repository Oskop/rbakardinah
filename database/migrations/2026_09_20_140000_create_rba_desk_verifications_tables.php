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
        Schema::create('rba_desk_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_submission_id')->constrained('rba_submissions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('hari');
            $table->date('tanggal_desk');
            $table->string('tanggal_desk_spelled');
            $table->string('ruang_desk');
            $table->string('sub_unit_name')->nullable();
            $table->text('catatan')->nullable();
            $table->string('is_usulan_sipakar', 10)->default('Ya');
            $table->string('kriteria_latar_belakang', 20)->default('Ya');
            $table->text('catatan_perbaikan_latar_belakang')->nullable();
            $table->string('is_dokumen_rab_uploaded', 10)->default('Ya');
            $table->json('tim_asistensi')->nullable();
            $table->json('anggota_sub_unit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rba_submission_id', 'user_id'], 'rba_desk_verif_sub_user_unique');
        });

        Schema::create('rba_desk_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_desk_verification_id')->constrained('rba_desk_verifications')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['rba_desk_verification_id', 'version_number'], 'desk_verif_doc_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_desk_verification_documents');
        Schema::dropIfExists('rba_desk_verifications');
    }
};
