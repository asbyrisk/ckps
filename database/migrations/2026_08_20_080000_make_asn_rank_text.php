<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asns') || !Schema::hasColumn('asns', 'rank')) {
            return;
        }

        Schema::table('asns', function (Blueprint $table) {
            $table->string('rank', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('asns') || !Schema::hasColumn('asns', 'rank')) {
            return;
        }

        Schema::table('asns', function (Blueprint $table) {
            $table->string('rank', 50)->nullable()->change();
        });
    }
};
