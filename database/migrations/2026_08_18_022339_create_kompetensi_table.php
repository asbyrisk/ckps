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
    Schema::create('kompetensi', function (Blueprint $table) {
        $table->id();

        $table->string('kode')->unique();
        $table->text('nama');

        $table->unsignedTinyInteger('level')->nullable();

        $table->text('pelatihan_eksisting')->nullable();

        $table->boolean('aktif')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kompetensi');
    }
};
