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
        Schema::create('rba_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('rba_periods');
            $table->foreignId('admin_id')->constrained('users');
            $table->year('year');
            $table->string('status_global')->default('Draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rba_headers');
    }
};
