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
    Schema::create('asn_pelatihan', function (Blueprint $table) {
        $table->id();

        $table->foreignId('asn_id')
            ->constrained('asn')
            ->cascadeOnDelete();

        $table->foreignId('pelatihan_id')
            ->constrained('pelatihan')
            ->cascadeOnDelete();

        $table->enum('status', [
            'sudah',
            'belum',
        ])->default('belum');

        $table->year('tahun')->nullable();

        $table->string('sumber_data')->nullable();

        $table->text('keterangan')->nullable();

        $table->timestamps();

        $table->unique([
            'asn_id',
            'pelatihan_id',
        ]);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_pelatihan');
    }
};
