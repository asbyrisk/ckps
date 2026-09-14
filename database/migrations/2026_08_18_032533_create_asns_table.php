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
        Schema::create('asns', function (Blueprint $table) {
    $table->id();

    $table->string('nip', 30)->unique();
    $table->string('name');

    $table->text('position')->nullable();
    $table->string('rank', 50)->nullable();

    $table->foreignId('unit_id')
        ->nullable()
        ->constrained('units')
        ->nullOnDelete();

    $table->timestamps();

    $table->index('name');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asns');
    }
};
