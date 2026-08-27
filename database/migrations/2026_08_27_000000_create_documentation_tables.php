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
        Schema::create('documentation_versions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['html', 'pdf'])->default('html');
            $table->string('version'); // e.g. v1.0.0
            $table->string('title'); // e.g. Buku Panduan Penggunaan Sistem RBA RSUD Kardinah
            $table->string('file_path')->nullable(); // For PDF uploads
            $table->unsignedBigInteger('file_size')->nullable(); // In bytes
            $table->text('release_notes')->nullable(); // Changelog summary
            $table->date('released_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('documentation_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documentation_version_id')->constrained('documentation_versions')->cascadeOnDelete();
            $table->string('category'); // e.g. Pengenalan, Panduan Operator, Panduan Supervisor, Panduan Administrator, FAQ & Bantuan
            $table->string('title'); // e.g. Penginputan Rincian Usulan Belanja
            $table->string('slug'); // e.g. penginputan-rincian-usulan-belanja
            $table->string('icon')->nullable(); // e.g. 📝, 🚀, 🔍, 👑, 💡
            $table->integer('order')->default(0);
            $table->longText('content'); // Markdown / Rich HTML content
            $table->timestamps();

            $table->unique(['documentation_version_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentation_articles');
        Schema::dropIfExists('documentation_versions');
    }
};
