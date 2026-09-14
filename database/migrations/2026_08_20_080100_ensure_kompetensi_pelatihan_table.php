<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compatibility guard for installations where the legacy pivot migration
        // was not applied, while the application models already depend on it.
        if (Schema::hasTable('kompetensi_pelatihan')) {
            return;
        }

        if (!Schema::hasTable('kompetensi') || !Schema::hasTable('trainings')) {
            return;
        }

        Schema::create('kompetensi_pelatihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kompetensi_id')->constrained('kompetensi')->cascadeOnDelete();
            $table->foreignId('pelatihan_id')->constrained('trainings')->cascadeOnDelete();
            $table->boolean('wajib')->default(false);
            $table->timestamps();
            $table->unique(['kompetensi_id', 'pelatihan_id']);
        });
    }

    public function down(): void
    {
        // Intentionally left intact: this compatibility migration must not
        // remove an existing pivot table that may contain production data.
    }
};
