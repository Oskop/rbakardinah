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
        Schema::create('rba_account_pagus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rba_header_id')->constrained('rba_headers');
            $table->foreignId('account_code_id')->constrained('account_codes');
            $table->decimal('nominal_pagu', 15, 2);
            $table->timestamps();

            $table->unique(['rba_header_id', 'account_code_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_account_pagus');
    }
};
