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
        Schema::create('sub_unit_account_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_unit_id')->constrained('sub_units')->cascadeOnDelete();
            $table->foreignId('account_code_id')->constrained('account_codes')->cascadeOnDelete();
            $table->string('fiscal_year', 10)->default('2027');
            $table->text('keterangan_khusus')->nullable();
            $table->timestamps();

            $table->unique(['sub_unit_id', 'account_code_id', 'fiscal_year'], 'sub_unit_account_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_unit_account_codes');
    }
};
