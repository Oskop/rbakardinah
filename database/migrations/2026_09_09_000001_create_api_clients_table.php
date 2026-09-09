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
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama aplikasi/klien pengguna API');
            $table->string('key_prefix', 16)->comment('Prefix identifikasi API Key');
            $table->string('api_key_hash', 64)->index()->comment('SHA-256 hash dari API Key');
            $table->boolean('is_active')->default(true)->comment('Status aktif API Key');
            $table->timestamp('expires_at')->nullable()->comment('Waktu kedaluwarsa');
            $table->timestamp('last_used_at')->nullable()->comment('Terakhir digunakan');
            $table->string('last_used_ip', 45)->nullable()->comment('IP address terakhir yang memanggil');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
