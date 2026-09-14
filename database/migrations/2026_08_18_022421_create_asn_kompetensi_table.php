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
    Schema::create('asn_kompetensi', function (Blueprint $table) {
        $table->id();

        $table->foreignId('asn_id')
            ->constrained('asn')
            ->cascadeOnDelete();

        $table->foreignId('kompetensi_id')
            ->constrained('kompetensi')
            ->cascadeOnDelete();

        $table->unsignedTinyInteger('level')->nullable();

        $table->string('sumber')->nullable();

        $table->string('status')->nullable();

        $table->timestamps();

        $table->unique([
            'asn_id',
            'kompetensi_id',
        ]);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_kompetensi');
    }
};
