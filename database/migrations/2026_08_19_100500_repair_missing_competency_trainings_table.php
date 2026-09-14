<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('competency_trainings')) {
            return;
        }

        // Repair migration: some installations already recorded the original
        // competency_trainings migration as migrated while the table was absent.
        // This migration safely creates it only when it is actually missing.
        Schema::create('competency_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')
                ->constrained('kompetensi')
                ->cascadeOnDelete();
            $table->foreignId('training_id')
                ->constrained('trainings')
                ->cascadeOnDelete();
            $table->boolean('wajib')->default(false);
            $table->timestamps();
            $table->unique(['competency_id', 'training_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_trainings');
    }
};
