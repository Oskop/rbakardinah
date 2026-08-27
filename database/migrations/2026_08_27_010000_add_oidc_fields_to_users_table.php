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
        Schema::table('users', function (Blueprint $table) {
            $table->string('simrs_sub')->nullable()->unique()->after('email');
            $table->string('nip')->nullable()->index()->after('simrs_sub');
            $table->enum('auth_provider', ['local', 'simrs_oidc'])->default('local')->after('role');
            $table->json('simrs_metadata')->nullable()->after('auth_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['simrs_sub', 'nip', 'auth_provider', 'simrs_metadata']);
        });
    }
};
