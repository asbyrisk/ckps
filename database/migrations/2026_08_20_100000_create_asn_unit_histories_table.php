<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The table may already exist if a previous revision created it manually.
        // Returning here lets Laravel mark this migration as applied without
        // attempting to create the table a second time.
        if (Schema::hasTable('asn_unit_histories')) {
            return;
        }

        Schema::create('asn_unit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asn_id')->constrained('asns')->cascadeOnDelete();
            $table->foreignId('from_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('to_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['asn_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asn_unit_histories');
    }
};
