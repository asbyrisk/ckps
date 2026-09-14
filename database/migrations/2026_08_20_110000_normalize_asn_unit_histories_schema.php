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

        // Be compatible with the earlier revision that used previous_unit_id,
        // new_unit_id and notes instead of from_unit_id, to_unit_id and note.
        if (!Schema::hasColumn('asn_unit_histories', 'from_unit_id')
            && Schema::hasColumn('asn_unit_histories', 'previous_unit_id')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->renameColumn('previous_unit_id', 'from_unit_id');
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'to_unit_id')
            && Schema::hasColumn('asn_unit_histories', 'new_unit_id')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->renameColumn('new_unit_id', 'to_unit_id');
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'note')
            && Schema::hasColumn('asn_unit_histories', 'notes')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->renameColumn('notes', 'note');
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'effective_date')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->date('effective_date')->nullable();
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'reason')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->string('reason')->nullable();
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'note')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->text('note')->nullable();
            });
        }

        if (!Schema::hasColumn('asn_unit_histories', 'changed_by')) {
            Schema::table('asn_unit_histories', function (Blueprint $table) {
                $table->foreignId('changed_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Deliberately non-destructive. Existing movement history must not be
        // deleted merely because a later migration is rolled back.
    }
};
