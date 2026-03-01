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
        Schema::create('rba_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_header_id')->constrained('rba_headers');
            $table->foreignId('unit_id')->constrained('units');
            $table->string('status_submission')->default('Draft');
            $table->text('supervisor_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_submissions');
    }
};
