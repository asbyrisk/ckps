<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asn_unit_histories')) {
            return;
        }

        if (!Schema::hasColumn('asn_unit_histories', 'status')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->string('status', 20)->default('aktif')->after('note');
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'canceled_at')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->timestamp('canceled_at')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'canceled_by')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->foreignId('canceled_by')->nullable()->after('canceled_at');
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'cancellation_reason')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->text('cancellation_reason')->nullable()->after('canceled_by');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('asn_unit_histories')) {
            return;
        }

        foreach (['cancellation_reason', 'canceled_by', 'canceled_at', 'status'] as $column) {
            if (Schema::hasColumn('asn_unit_histories', $column)) {
                Schema::table('asn_unit_histories', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
