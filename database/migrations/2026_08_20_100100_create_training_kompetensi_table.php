<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table may already exist from an earlier manual/partial migration.
        if (Schema::hasTable('training_kompetensi')) {
            return;
        }

        Schema::create('training_kompetensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('kompetensi_id')->constrained('kompetensi')->cascadeOnDelete();
            $table->boolean('wajib')->default(false);
            $table->timestamps();
            $table->unique(['training_id', 'kompetensi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_kompetensi');
    }
};
