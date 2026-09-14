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
    Schema::create('asn', function (Blueprint $table) {
        $table->id();

        $table->string('nip', 20)->unique();
        $table->string('nama');

        $table->foreignId('jabatan_id')
            ->nullable()
            ->constrained('jabatan')
            ->nullOnDelete();

        $table->foreignId('unit_kerja_id')
            ->nullable()
            ->constrained('unit_kerja')
            ->nullOnDelete();

        $table->foreignId('golongan_id')
            ->nullable()
            ->constrained('golongan')
            ->nullOnDelete();

        $table->enum('status_asn', [
            'PNS',
            'PPPK',
            'CPNS',
            'Lainnya',
        ])->default('PNS');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn');
    }
};
