<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MapCkpsCompetencies extends Command
{
    protected $signature = 'excel:map-ckps-competencies';

    protected $description =
        'Mapping kompetensi CKPS ke pelatihan dan ASN berdasarkan pelatihan_eksisting';

    public function handle()
    {
        $this->info('==============================================');
        $this->info('MAPPING KOMPETENSI CKPS');
        $this->info('==============================================');
        $this->newLine();

        try {

            /*
            |--------------------------------------------------------------------------
            | CEK DATA
            |--------------------------------------------------------------------------
            */

            $competencies = DB::table('kompetensi')
                ->orderBy('id')
                ->get();

            $trainings = DB::table('trainings')
                ->orderBy('id')
                ->get();

            $this->info(
                'Kompetensi ditemukan : ' . $competencies->count()
            );

            $this->info(
                'Training ditemukan   : ' . $trainings->count()
            );

            $this->newLine();

            if ($competencies->isEmpty()) {
                $this->warn(
                    'Tidak ada data kompetensi.'
                );

                return self::SUCCESS;
            }

            if ($trainings->isEmpty()) {
                $this->warn(
                    'Tidak ada data trainings.'
                );

                return self::SUCCESS;
            }

            /*
            |--------------------------------------------------------------------------
            | CACHE TRAINING
            |--------------------------------------------------------------------------
            |
            | Kita buat beberapa index supaya pencarian tidak perlu query database
            | berulang-ulang.
            |
            */

            $trainingByName = [];

            $trainingByNormalizedName = [];

            foreach ($trainings as $training) {

                $name = trim(
                    (string) $training->name
                );

                if ($name === '') {
                    continue;
                }

                $key = $this->normalize($name);

                /*
                | Jangan menimpa data pertama kalau ada duplicate.
                */

                if (!isset($trainingByName[$name])) {
                    $trainingByName[$name] = $training;
                }

                if (!isset(
                    $trainingByNormalizedName[$key]
                )) {
                    $trainingByNormalizedName[$key] = $training;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | COUNTER
            |--------------------------------------------------------------------------
            */

            $competencyProcessed = 0;

            $competencyWithTraining = 0;

            $competencyWithoutTraining = 0;

            $trainingMatched = 0;

            $trainingNotMatched = 0;

            $asnMatched = 0;

            $asnNotMatched = 0;

            $relationFound = 0;

            $relationUnique = [];

            /*
            |--------------------------------------------------------------------------
            | DETAIL HASIL
            |--------------------------------------------------------------------------
            */

            $unmatchedTrainings = [];

            /*
            |--------------------------------------------------------------------------
            | LOOP COMPETENCY
            |--------------------------------------------------------------------------
            */

            foreach ($competencies as $competency) {

                $competencyProcessed++;

                $code = trim(
                    (string) $competency->kode
                );

                $level = trim(
                    (string) $competency->level
                );

                $name = trim(
                    (string) $competency->nama
                );

                $existingTraining =
                    trim(
                        (string) $competency->pelatihan_eksisting
                    );

                /*
                |--------------------------------------------------------------------------
                | OUTPUT KOMPETENSI
                |--------------------------------------------------------------------------
                */

                $this->line(
                    '--------------------------------------------------'
                );

                $this->line(
                    "KOMPETENSI #{$competency->id}"
                );

                $this->line(
                    '  Code : ' . ($code ?: '-')
                );

                $this->line(
                    '  Level: ' . ($level ?: '-')
                );

                $this->line(
                    '  Nama : ' . ($name ?: '-')
                );

                $this->line(
                    '  Training existing: ' .
                    ($existingTraining ?: '-')
                );

                /*
                |--------------------------------------------------------------------------
                | KALAU TIDAK ADA EXISTING TRAINING
                |--------------------------------------------------------------------------
                */

                if ($existingTraining === '') {

                    $competencyWithoutTraining++;

                    $this->warn(
                        '  -> Tidak mempunyai existing training.'
                    );

                    continue;
                }

                $competencyWithTraining++;

                /*
                |--------------------------------------------------------------------------
                | BERSIHKAN EXISTING TRAINING
                |--------------------------------------------------------------------------
                */

                $existingTraining =
                    $this->cleanTrainingName(
                        $existingTraining
                    );

                if ($existingTraining === '') {

                    $competencyWithoutTraining++;

                    $this->warn(
                        '  -> Existing training kosong setelah cleaning.'
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | CARI TRAINING
                |--------------------------------------------------------------------------
                */

                $training = null;

                /*
                |--------------------------------------------------------------------------
                | EXACT MATCH
                |--------------------------------------------------------------------------
                */

                if (
                    isset(
                        $trainingByName[
                            $existingTraining
                        ]
                    )
                ) {

                    $training =
                        $trainingByName[
                            $existingTraining
                        ];
                }

                /*
                |--------------------------------------------------------------------------
                | NORMALIZED MATCH
                |--------------------------------------------------------------------------
                */

                if (!$training) {

                    $normalized =
                        $this->normalize(
                            $existingTraining
                        );

                    if (
                        isset(
                            $trainingByNormalizedName[
                                $normalized
                            ]
                        )
                    ) {

                        $training =
                            $trainingByNormalizedName[
                                $normalized
                            ];
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | FALLBACK: CARI DATABASE
                |--------------------------------------------------------------------------
                */

                if (!$training) {

                    $training =
                        DB::table('trainings')
                            ->where(
                                'name',
                                $existingTraining
                            )
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | TRAINING TIDAK DITEMUKAN
                |--------------------------------------------------------------------------
                */

                if (!$training) {

                    $trainingNotMatched++;

                    $unmatchedKey =
                        $this->normalize(
                            $existingTraining
                        );

                    if (
                        !isset(
                            $unmatchedTrainings[
                                $unmatchedKey
                            ]
                        )
                    ) {

                        $unmatchedTrainings[
                            $unmatchedKey
                        ] = [
                            'competency_id' =>
                                $competency->id,

                            'code' =>
                                $code,

                            'competency' =>
                                $name,

                            'training' =>
                                $existingTraining,
                        ];
                    }

                    $this->error(
                        '  -> TRAINING TIDAK DITEMUKAN'
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | TRAINING DITEMUKAN
                |--------------------------------------------------------------------------
                */

                $trainingMatched++;

                $this->info(
                    '  -> Training ID: ' .
                    $training->id
                );

                $this->line(
                    '  -> Training: ' .
                    $training->name
                );

                /*
                |--------------------------------------------------------------------------
                | SIMPAN RELASI KOMPETENSI <-> PELATIHAN
                |--------------------------------------------------------------------------
                */

                DB::table('training_kompetensi')->updateOrInsert(
                    [
                        'kompetensi_id' => $competency->id,
                        'training_id' => $training->id,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | CARI ASN YANG MENGIKUTI TRAINING
                |--------------------------------------------------------------------------
                */

                $asnTrainingRows =
                    DB::table('asn_trainings')
                        ->where(
                            'training_id',
                            $training->id
                        )
                        ->get();

                if (
                    $asnTrainingRows->isEmpty()
                ) {

                    $asnNotMatched++;

                    $this->warn(
                        '  -> Belum ada ASN yang terhubung ke training ini.'
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | ASN DITEMUKAN
                |--------------------------------------------------------------------------
                */

                $asnCount =
                    $asnTrainingRows->count();

                $asnMatched += $asnCount;

                $this->info(
                    '  -> ASN terhubung: ' .
                    $asnCount
                );

                /*
                |--------------------------------------------------------------------------
                | MAPPING KOMPETENSI -> PELATIHAN -> ASN
                |--------------------------------------------------------------------------
                | Relasi kompetensi dan pelatihan disimpan di
                | kompetensi_pelatihan. Riwayat ASN tetap memakai asn_trainings.
                */

                foreach (
                    $asnTrainingRows
                    as $asnTraining
                ) {

                    $uniqueKey =
                        $competency->id .
                        ':' .
                        $training->id .
                        ':' .
                        $asnTraining->asn_id;

                    if (
                        !isset(
                            $relationUnique[
                                $uniqueKey
                            ]
                        )
                    ) {

                        $relationUnique[
                            $uniqueKey
                        ] = true;

                        $relationFound++;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | HASIL
            |--------------------------------------------------------------------------
            */

            $this->newLine();

            $this->info(
                '=============================================='
            );

            $this->info(
                'HASIL MAPPING KOMPETENSI'
            );

            $this->info(
                '=============================================='
            );

            $this->line(
                'Kompetensi diproses       : ' .
                $competencyProcessed
            );

            $this->line(
                'Punya existing training   : ' .
                $competencyWithTraining
            );

            $this->line(
                'Tanpa existing training   : ' .
                $competencyWithoutTraining
            );

            $this->line(
                'Training berhasil match   : ' .
                $trainingMatched
            );

            $this->line(
                'Training tidak ditemukan  : ' .
                $trainingNotMatched
            );

            $this->line(
                'ASN dari training         : ' .
                $asnMatched
            );

            $this->line(
                'Training tanpa ASN        : ' .
                $asnNotMatched
            );

            $this->line(
                'Mapping unik kompetensi-ASN: ' .
                $relationFound
            );

            /*
            |--------------------------------------------------------------------------
            | TRAINING TIDAK MATCH
            |--------------------------------------------------------------------------
            */

            if (
                !empty($unmatchedTrainings)
            ) {

                $this->newLine();

                $this->warn(
                    '=============================================='
                );

                $this->warn(
                    'TRAINING YANG TIDAK DITEMUKAN'
                );

                $this->warn(
                    '=============================================='
                );

                foreach (
                    $unmatchedTrainings
                    as $item
                ) {

                    $this->line(
                        'Kompetensi ID : ' .
                        $item['competency_id']
                    );

                    $this->line(
                        'Code          : ' .
                        ($item['code'] ?: '-')
                    );

                    $this->line(
                        'Kompetensi    : ' .
                        $item['competency']
                    );

                    $this->line(
                        'Training      : ' .
                        $item['training']
                    );

                    $this->newLine();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SELESAI
            |--------------------------------------------------------------------------
            */

            $this->newLine();

            $this->info(
                'Mapping selesai.'
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->newLine();

            $this->error(
                'MAPPING GAGAL.'
            );

            $this->error(
                $e->getMessage()
            );

            $this->newLine();

            $this->line(
                'File kode: ' .
                $e->getFile()
            );

            $this->line(
                'Line: ' .
                $e->getLine()
            );

            return self::FAILURE;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE
    |--------------------------------------------------------------------------
    */

    private function normalize(
        ?string $value
    ): string {

        if ($value === null) {
            return '';
        }

        $value =
            mb_strtolower(
                trim($value)
            );

        /*
        | Hilangkan line break.
        */

        $value =
            preg_replace(
                '/[\r\n\t]+/u',
                ' ',
                $value
            );

        /*
        | Hilangkan whitespace berlebihan.
        */

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            );

        /*
        | Beberapa Excel mempunyai spasi sebelum/sesudah
        | tanda baca.
        */

        $value =
            preg_replace(
                '/\s*,\s*/u',
                ',',
                $value
            );

        $value =
            preg_replace(
                '/\s*-\s*/u',
                '-',
                $value
            );

        return trim($value);
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN TRAINING NAME
    |--------------------------------------------------------------------------
    */

    private function cleanTrainingName(
        ?string $value
    ): string {

        if ($value === null) {
            return '';
        }

        $value =
            trim($value);

        /*
        | Formula Excel yang belum dihitung jangan dianggap
        | sebagai nama training.
        */

        if (
            str_starts_with(
                $value,
                '='
            )
        ) {
            return '';
        }

        /*
        | Status kosong.
        */

        $lower =
            mb_strtolower(
                trim($value)
            );

        if (
            in_array(
                $lower,
                [
                    '',
                    'belum',
                    'belum ada',
                    'belum ada data',
                    'sudah',
                    'tidak',
                    'tidak ada',
                    'tidak tersedia',
                    'n/a',
                    '#n/a',
                    '-',
                ],
                true
            )
        ) {
            return '';
        }

        /*
        | Rapikan whitespace.
        */

        $value =
            preg_replace(
                '/[\r\n\t]+/u',
                ' ',
                $value
            );

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            );

        return trim($value);
    }
}