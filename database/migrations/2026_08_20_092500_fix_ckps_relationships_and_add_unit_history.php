<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The active application schema uses asns/units/trainings/kompetensi.
        // Older migrations created duplicate tables (asn, pelatihan, etc.).
        // Re-point the active pivot tables to the active schema so imports do not
        // fail with foreign-key errors against the legacy tables.
        if (Schema::hasTable('kompetensi_pelatihan')) {
            $legacyRows = [];

            if (Schema::hasTable('pelatihan') && Schema::hasTable('trainings')) {
                $legacyRows = DB::table('kompetensi_pelatihan as kp')
                    ->join('pelatihan as p', 'p.id', '=', 'kp.pelatihan_id')
                    ->whereNotNull('kp.kompetensi_id')
                    ->get([
                        'kp.kompetensi_id',
                        'kp.wajib',
                        'p.nama',
                    ]);
            }

            try {
                Schema::table('kompetensi_pelatihan', function (Blueprint $table) {
                    $table->dropForeign(['pelatihan_id']);
                });
            } catch (Throwable $e) {
                // Some installations may already have the corrected FK.
            }

            if (Schema::hasTable('trainings')) {
                // Rebuild the pivot using trainings IDs. The previous rows used
                // pelatihan IDs, so they cannot remain when the FK is switched.
                DB::table('kompetensi_pelatihan')->delete();

                foreach ($legacyRows as $row) {
                    $trainingId = DB::table('trainings')
                        ->whereRaw('LOWER(name) = LOWER(?)', [$row->nama])
                        ->value('id');

                    if (!$trainingId) {
                        $trainingId = DB::table('trainings')->insertGetId([
                            'name' => $row->nama,
                            'code' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('kompetensi_pelatihan')->updateOrInsert(
                        [
                            'kompetensi_id' => $row->kompetensi_id,
                            'pelatihan_id' => $trainingId,
                        ],
                        [
                            'wajib' => (bool) $row->wajib,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }

            // Remove legacy FK before adding the active one. Existing pivot rows
            // are now expected to use trainings IDs.
            if (Schema::hasTable('trainings')) {
                Schema::table('kompetensi_pelatihan', function (Blueprint $table) {
                    $table->foreign('pelatihan_id', 'kompetensi_pelatihan_pelatihan_id_foreign')
                        ->references('id')
                        ->on('trainings')
                        ->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('asn_kompetensi')) {
            try {
                Schema::table('asn_kompetensi', function (Blueprint $table) {
                    $table->dropForeign(['asn_id']);
                });
            } catch (Throwable $e) {
                // Ignore when the FK has already been corrected.
            }

            if (Schema::hasTable('asns')) {
                Schema::table('asn_kompetensi', function (Blueprint $table) {
                    $table->foreign('asn_id', 'asn_kompetensi_asn_id_foreign')
                        ->references('id')
                        ->on('asns')
                        ->cascadeOnDelete();
                });
            }
        }

        // Riwayat perpindahan memiliki migration khusus 2026_08_20_100000
        // dan normalisasi 2026_08_20_110000. Jangan membuat tabel kedua kali di sini.
    }

    public function down(): void
    {
        Schema::dropIfExists('asn_unit_histories');

        if (Schema::hasTable('asn_kompetensi')) {
            try {
                Schema::table('asn_kompetensi', function (Blueprint $table) {
                    $table->dropForeign(['asn_id']);
                });
            } catch (Throwable $e) {}

            if (Schema::hasTable('asn')) {
                Schema::table('asn_kompetensi', function (Blueprint $table) {
                    $table->foreign('asn_id', 'asn_kompetensi_asn_id_foreign')
                        ->references('id')
                        ->on('asn')
                        ->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('kompetensi_pelatihan')) {
            try {
                Schema::table('kompetensi_pelatihan', function (Blueprint $table) {
                    $table->dropForeign(['pelatihan_id']);
                });
            } catch (Throwable $e) {}

            if (Schema::hasTable('pelatihan')) {
                Schema::table('kompetensi_pelatihan', function (Blueprint $table) {
                    $table->foreign('pelatihan_id', 'kompetensi_pelatihan_pelatihan_id_foreign')
                        ->references('id')
                        ->on('pelatihan')
                        ->cascadeOnDelete();
                });
            }
        }
    }
};
