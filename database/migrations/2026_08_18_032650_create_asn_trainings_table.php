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
       Schema::create('asn_trainings', function (Blueprint $table) {
    $table->id();

    $table->foreignId('asn_id')
        ->constrained('asns')
        ->cascadeOnDelete();

    $table->foreignId('training_id')
        ->constrained('trainings')
        ->cascadeOnDelete();

    $table->unsignedSmallInteger('year')->nullable();

    $table->string('status', 30)
        ->default('completed');

    $table->string('source_sheet')->nullable();

    $table->timestamps();

    $table->unique([
        'asn_id',
        'training_id',
        'year'
    ]);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_trainings');
    }
};
