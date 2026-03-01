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
        Schema::create('rba_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_submission_id')->constrained('rba_submissions');
            $table->foreignId('account_code_id')->constrained('account_codes');
            $table->string('description');
            $table->decimal('nominal_request', 15, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_details');
    }
};
