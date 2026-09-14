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
    Schema::create('kompetensi_pelatihan', function (Blueprint $table) {
        $table->id();

        $table->foreignId('kompetensi_id')
            ->constrained('kompetensi')
            ->cascadeOnDelete();

        $table->foreignId('pelatihan_id')
            ->constrained('pelatihan')
            ->cascadeOnDelete();

        $table->boolean('wajib')->default(false);

        $table->timestamps();

        $table->unique([
            'kompetensi_id',
            'pelatihan_id',
        ]);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kompetensi_pelatihan');
    }
};
