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
        Schema::create('api_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->string('client_name')->nullable()->comment('Nama klien pada saat pemanggilan');
            $table->string('endpoint')->comment('Path endpoint yang diakses');
            $table->string('method', 10)->default('GET')->comment('HTTP Method');
            $table->unsignedSmallInteger('status_code')->index()->comment('HTTP Status Code');
            $table->json('query_params')->nullable()->comment('Parameter query yang diminta');
            $table->float('response_time_ms', 8, 2)->default(0)->comment('Durasi pemrosesan dalam milidetik');
            $table->string('ip_address', 45)->nullable()->index()->comment('Alamat IP pemanggil');
            $table->text('user_agent')->nullable()->comment('User-Agent pemanggil');
            $table->text('error_message')->nullable()->comment('Pesan error jika request gagal');
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_access_logs');
    }
};
