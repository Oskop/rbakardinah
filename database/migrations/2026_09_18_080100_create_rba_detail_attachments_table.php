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
        // 1. Tambahkan kolom pendukung pada rba_attachments
        Schema::table('rba_attachments', function (Blueprint $table) {
            $table->foreignId('rba_detail_document_id')
                ->nullable()
                ->after('id')
                ->constrained('rba_detail_documents')
                ->nullOnDelete();
            $table->string('original_filename')->nullable()->after('file_path');
            $table->foreignId('rba_detail_id')->nullable()->change();
        });

        // 2. Buat tabel pivot rba_detail_attachments
        Schema::create('rba_detail_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_detail_id')->constrained('rba_details')->cascadeOnDelete();
            $table->foreignId('rba_attachment_id')->constrained('rba_attachments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['rba_detail_id', 'rba_attachment_id'], 'detail_attachment_unique');
        });

        // 3. Migrasi data eksisting: Hubungkan rba_attachments yang ada ke rba_detail_attachments
        $existingAttachments = DB::table('rba_attachments')->get();
        foreach ($existingAttachments as $att) {
            if ($att->rba_detail_id) {
                // Masukkan ke pivot
                DB::table('rba_detail_attachments')->insertOrIgnore([
                    'rba_detail_id' => $att->rba_detail_id,
                    'rba_attachment_id' => $att->id,
                    'created_at' => $att->created_at ?? now(),
                    'updated_at' => $att->updated_at ?? now(),
                ]);

                // Buat entitas rba_detail_documents jika belum ada
                $detail = DB::table('rba_details')->where('id', $att->rba_detail_id)->first();
                if ($detail) {
                    $docName = basename($att->file_path);
                    $docId = DB::table('rba_detail_documents')->insertGetId([
                        'rba_submission_id' => $detail->rba_submission_id,
                        'document_name' => $docName,
                        'created_by' => $att->uploaded_by,
                        'created_at' => $att->created_at ?? now(),
                        'updated_at' => $att->updated_at ?? now(),
                    ]);

                    DB::table('rba_attachments')
                        ->where('id', $att->id)
                        ->update([
                            'rba_detail_document_id' => $docId,
                            'original_filename' => $docName,
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_detail_attachments');

        Schema::table('rba_attachments', function (Blueprint $table) {
            $table->dropForeign(['rba_detail_document_id']);
            $table->dropColumn(['rba_detail_document_id', 'original_filename']);
        });
    }
};
