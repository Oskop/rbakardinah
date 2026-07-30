<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rba_submission_documents', 'user_id')) {
            Schema::table('rba_submission_documents', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('rba_submission_id')->constrained('users')->cascadeOnDelete();
            });
        }

        // Populate user_id for existing documents from earliest document version
        $documents = DB::table('rba_submission_documents')->whereNull('user_id')->get();
        foreach ($documents as $doc) {
            $firstVersion = DB::table('rba_submission_document_versions')
                ->where('rba_submission_document_id', $doc->id)
                ->orderBy('version_number', 'asc')
                ->first();

            if ($firstVersion) {
                DB::table('rba_submission_documents')
                    ->where('id', $doc->id)
                    ->update(['user_id' => $firstVersion->uploaded_by]);
            }
        }

        Schema::table('rba_submission_documents', function (Blueprint $table) {
            $table->unique(['rba_submission_id', 'type', 'user_id'], 'rba_sub_docs_sub_type_user_unique');
            $table->dropUnique('rba_submission_documents_rba_submission_id_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rba_submission_documents', function (Blueprint $table) {
            $table->dropUnique('rba_sub_docs_sub_type_user_unique');
            $table->unique(['rba_submission_id', 'type'], 'rba_submission_documents_rba_submission_id_type_unique');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
