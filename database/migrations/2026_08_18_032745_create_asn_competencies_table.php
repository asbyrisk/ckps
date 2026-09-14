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
        Schema::create('asn_competencies', function (Blueprint $table) {
    $table->id();

    $table->foreignId('asn_id')
        ->constrained('asns')
        ->cascadeOnDelete();

    $table->foreignId('competency_id')
        ->constrained('competencies')
        ->cascadeOnDelete();

    $table->string('source')->nullable();

    $table->timestamps();

    $table->unique([
        'asn_id',
        'competency_id'
    ]);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_competencies');
    }
};
