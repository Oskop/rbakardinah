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
        // 1. Tabel Induk Indikator Kinerja
        if (!Schema::hasTable('performance_indicators')) {
            Schema::create('performance_indicators', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable();
                $table->string('name', 255);
                $table->string('category', 100)->nullable();
                $table->string('unit', 50)->nullable();
                $table->text('description')->nullable();
                $table->integer('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('created_by', 'fk_pi_created_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 2. Tabel Target Tahunan Indikator Kinerja
        if (!Schema::hasTable('performance_indicator_targets')) {
            Schema::create('performance_indicator_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('performance_indicator_id');
                $table->integer('year');
                $table->string('target_value', 100);
                $table->integer('current_version')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('performance_indicator_id', 'fk_pit_indicator_id')
                    ->references('id')->on('performance_indicators')->cascadeOnDelete();
                $table->foreign('created_by', 'fk_pit_created_by')
                    ->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by', 'fk_pit_updated_by')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique(['performance_indicator_id', 'year'], 'pi_targets_indicator_year_unique');
            });
        }

        // 3. Tabel Riwayat Perubahan Nilai Target (Audit Trail / Versioning)
        if (!Schema::hasTable('performance_indicator_target_histories')) {
            Schema::create('performance_indicator_target_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('target_id');
                $table->unsignedBigInteger('performance_indicator_id');
                $table->integer('year');
                $table->integer('version_number');
                $table->string('old_value', 100)->nullable();
                $table->string('new_value', 100);
                $table->text('change_note')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();

                $table->foreign('target_id', 'fk_pith_target_id')
                    ->references('id')->on('performance_indicator_targets')->cascadeOnDelete();
                $table->foreign('performance_indicator_id', 'fk_pith_indicator_id')
                    ->references('id')->on('performance_indicators')->cascadeOnDelete();
                $table->foreign('user_id', 'fk_pith_user_id')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_indicator_target_histories');
        Schema::dropIfExists('performance_indicator_targets');
        Schema::dropIfExists('performance_indicators');
    }
};
