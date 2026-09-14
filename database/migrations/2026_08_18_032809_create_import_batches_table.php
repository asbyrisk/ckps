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
        Schema::create('import_batches', function (Blueprint $table) {
    $table->id();

    $table->string('filename');

    $table->unsignedInteger('total_sheets')
        ->default(0);

    $table->unsignedInteger('total_asns')
        ->default(0);

    $table->unsignedInteger('total_trainings')
        ->default(0);

    $table->unsignedInteger('total_competencies')
        ->default(0);

    $table->string('status', 30)
        ->default('processing');

    $table->text('error_message')
        ->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
