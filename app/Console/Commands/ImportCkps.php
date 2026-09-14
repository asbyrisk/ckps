<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class ImportCkps extends Command
{
    protected $signature = 'excel:import-ckps';

    protected $description =
        'Import data ASN, pelatihan, dan kompetensi dari Excel CKPS';

    private string $excelFile;

    /*
    |--------------------------------------------------------------------------
    | COUNTER
    |--------------------------------------------------------------------------
    */

    private int $totalAsnBaru = 0;
    private int $totalAsnSkipped = 0;
    private int $totalTrainingBaru = 0;
    private int $totalRelasiTraining = 0;
    private int $totalCompetencyBaru = 0;
    private int $totalCompetencyUpdate = 0;

    /*
    |--------------------------------------------------------------------------
    | CACHE
    |--------------------------------------------------------------------------
    */

    private array $unitCache = [];
    private array $asnCache = [];
    private array $trainingCache = [];
    private array $competencyCache = [];

    /*
    |--------------------------------------------------------------------------
    | HANDLE
    |--------------------------------------------------------------------------
    */

    public function handle()
    {
        $this->info('==============================================');
        $this->info('IMPORT DATA CKPS');
        $this->info('==============================================');
        $this->newLine();

        $this->excelFile = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        $this->info('File Excel:');
        $this->line($this->excelFile);
        $this->newLine();

        if (!file_exists($this->excelFile)) {
            $this->error('File Excel tidak ditemukan:');
            $this->error($this->excelFile);

            return self::FAILURE;
        }

        $batchId = null;

        try {
            /*
            |--------------------------------------------------------------------------
            | LOAD EXCEL
            |--------------------------------------------------------------------------
            */

            $this->info('Membaca file Excel...');

            $spreadsheet = IOFactory::load($this->excelFile);

            $sheets = $spreadsheet->getAllSheets();

            $totalSheets = count($sheets);

            $this->info(
                'Jumlah sheet: ' . $totalSheets
            );

            $this->newLine();

            /*
            |--------------------------------------------------------------------------
            | IMPORT BATCH
            |--------------------------------------------------------------------------
            */

            $batchId = DB::table('import_batches')->insertGetId([
                'filename' => basename($this->excelFile),
                'total_sheets' => $totalSheets,
                'total_asns' => 0,
                'total_trainings' => 0,
                'total_competencies' => 0,
                'status' => 'processing',
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | STEP 1
            |
            | IMPORT STANDKOM TERLEBIH DAHULU
            |--------------------------------------------------------------------------
            */

            $standkom = $this->findSheet(
                $sheets,
                'STANDKOM'
            );

            if ($standkom) {
                $this->importCompetencies($standkom);
            } else {
                $this->warn(
                    'Sheet STANDKOM tidak ditemukan.'
                );
            }

            $this->newLine();

            /*
            |--------------------------------------------------------------------------
            | STEP 2
            |
            | IMPORT SHEET ASN
            |--------------------------------------------------------------------------
            */

            foreach ($sheets as $sheet) {

                $sheetName = trim(
                    (string) $sheet->getTitle()
                );

                /*
                |--------------------------------------------------------------------------
                | STANDKOM SUDAH DIPROSES
                |--------------------------------------------------------------------------
                */

                if (
                    mb_strtolower($sheetName) === 'standkom'
                ) {
                    continue;
                }

                $highestRow =
                    $sheet->getHighestRow();

                $highestColumn =
                    $sheet->getHighestColumn();

                $this->line(
                    "SHEET: {$sheetName} | " .
                    "Baris: {$highestRow} | " .
                    "Kolom: {$highestColumn}"
                );

                /*
                |--------------------------------------------------------------------------
                | DETEKSI HEADER
                |--------------------------------------------------------------------------
                */

                $headerRow =
                    $this->detectHeaderRow($sheet);

                if (!$headerRow) {

                    $this->line(
                        '  -> Header tidak ditemukan, dilewati.'
                    );

                    $this->newLine();

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | DETEKSI KOLOM
                |--------------------------------------------------------------------------
                */

                $columns =
                    $this->detectColumns(
                        $sheet,
                        $headerRow
                    );

                $nameColumn =
                    $columns['name'];

                $nipColumn =
                    $columns['nip'];

                $rankColumn =
                    $columns['rank'];

                $positionColumn =
                    $columns['position'];

                $unitColumn =
                    $columns['unit'];

                /*
                |--------------------------------------------------------------------------
                | BUKAN SHEET ASN
                |--------------------------------------------------------------------------
                */

                if (
                    !$nameColumn ||
                    !$nipColumn
                ) {

                    $this->line(
                        '  -> Bukan sheet data ASN, dilewati.'
                    );

                    $this->newLine();

                    continue;
                }

                $this->line(
                    '  Kolom nama : ' .
                    ($nameColumn ?: '-')
                );

                $this->line(
                    '  Kolom NIP  : ' .
                    ($nipColumn ?: '-')
                );

                $this->line(
                    '  Kolom pangkat : ' .
                    ($rankColumn ?: '-')
                );

                $this->line(
                    '  Kolom jabatan : ' .
                    ($positionColumn ?: '-')
                );

                $this->line(
                    '  Kolom unit : ' .
                    ($unitColumn ?: '-')
                );

                /*
                |--------------------------------------------------------------------------
                | DETEKSI TRAINING
                |--------------------------------------------------------------------------
                */

                $trainingColumns =
                    $this->detectTrainingColumns(
                        $sheet,
                        $headerRow,
                        $columns
                    );

                /*
                |--------------------------------------------------------------------------
                | LOOP DATA
                |--------------------------------------------------------------------------
                */

                for (
                    $row = $headerRow + 1;
                    $row <= $highestRow;
                    $row++
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | IDENTITAS
                    |--------------------------------------------------------------------------
                    */

                    $name =
                        $this->cleanString(
                            $this->getCellValue(
                                $sheet,
                                $nameColumn,
                                $row
                            )
                        );

                    $nip =
                        $this->cleanNip(
                            $this->getCellValue(
                                $sheet,
                                $nipColumn,
                                $row
                            )
                        );

                    $rank =
                        $rankColumn
                            ? $this->cleanString(
                                $this->getCellValue(
                                    $sheet,
                                    $rankColumn,
                                    $row
                                )
                            )
                            : null;

                    $position =
                        $positionColumn
                            ? $this->cleanString(
                                $this->getCellValue(
                                    $sheet,
                                    $positionColumn,
                                    $row
                                )
                            )
                            : null;

                    $unitName =
                        $unitColumn
                            ? $this->cleanString(
                                $this->getCellValue(
                                    $sheet,
                                    $unitColumn,
                                    $row
                                )
                            )
                            : null;

                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI NIP
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$name ||
                        !$this->isValidNip($nip)
                    ) {
                        $this->totalAsnSkipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UNIT
                    |--------------------------------------------------------------------------
                    */

                    $unitId =
                        $this->findOrCreateUnit(
                            $unitName
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | ASN
                    |--------------------------------------------------------------------------
                    */

                    $asnId =
                        $this->findOrCreateAsn(
                            $nip,
                            $name,
                            $position,
                            $rank,
                            $unitId
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | TRAINING
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $trainingColumns
                        as $trainingColumn
                    ) {

                        $trainingName =
                            $this->cleanString(
                                $this->getCellValue(
                                    $sheet,
                                    $trainingColumn,
                                    $row
                                )
                            );

                        if (!$trainingName) {
                            continue;
                        }

                        if (
                            $this->isInvalidTrainingValue(
                                $trainingName
                            )
                        ) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | TRAINING
                        |--------------------------------------------------------------------------
                        */

                        $trainingId =
                            $this->findOrCreateTraining(
                                $trainingName
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | TAHUN
                        |--------------------------------------------------------------------------
                        */

                        $year =
                            $this->detectTrainingYear(
                                $sheet,
                                $row,
                                $trainingColumn,
                                $headerRow
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | RELASI
                        |--------------------------------------------------------------------------
                        */

                        $this->createTrainingRelation(
                            $asnId,
                            $trainingId,
                            $year,
                            $sheetName
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PROGRESS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $row % 50 === 0
                    ) {
                        $this->line(
                            "  Memproses baris {$row}/{$highestRow}..."
                        );
                    }
                }

                $this->newLine();
            }

            /*
            |--------------------------------------------------------------------------
            | TOTAL DATABASE
            |--------------------------------------------------------------------------
            */

            $databaseTotalAsn =
                DB::table('asns')->count();

            $databaseTotalTraining =
                DB::table('trainings')->count();

            $databaseTotalCompetency =
                DB::table('kompetensi')->count();

            /*
            |--------------------------------------------------------------------------
            | UPDATE BATCH
            |--------------------------------------------------------------------------
            */

            DB::table('import_batches')
                ->where('id', $batchId)
                ->update([
                    'total_asns' =>
                        $databaseTotalAsn,

                    'total_trainings' =>
                        $databaseTotalTraining,

                    'total_competencies' =>
                        $databaseTotalCompetency,

                    'status' =>
                        'completed',

                    'error_message' =>
                        null,

                    'updated_at' =>
                        now(),
                ]);

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
                'IMPORT BERHASIL'
            );

            $this->info(
                '=============================================='
            );

            $this->line(
                'ASN baru              : ' .
                $this->totalAsnBaru
            );

            $this->line(
                'ASN dilewati          : ' .
                $this->totalAsnSkipped
            );

            $this->line(
                'Training baru         : ' .
                $this->totalTrainingBaru
            );

            $this->line(
                'Relasi ASN-Training   : ' .
                $this->totalRelasiTraining
            );

            $this->line(
                'Kompetensi baru       : ' .
                $this->totalCompetencyBaru
            );

            $this->line(
                'Kompetensi diperbarui: ' .
                $this->totalCompetencyUpdate
            );

            $this->line(
                '----------------------------------------------'
            );

            $this->line(
                'Total ASN database        : ' .
                $databaseTotalAsn
            );

            $this->line(
                'Total Training database   : ' .
                $databaseTotalTraining
            );

            $this->line(
                'Total Kompetensi database : ' .
                $databaseTotalCompetency
            );

            $this->line(
                'Import Batch ID            : ' .
                $batchId
            );

            $this->newLine();

            return self::SUCCESS;

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | UPDATE BATCH FAILED
            |--------------------------------------------------------------------------
            */

            if ($batchId) {

                try {

                    DB::table('import_batches')
                        ->where('id', $batchId)
                        ->update([
                            'status' =>
                                'failed',

                            'error_message' =>
                                $e->getMessage(),

                            'updated_at' =>
                                now(),
                        ]);

                } catch (Throwable $ignore) {
                    // Jangan timpa error utama.
                }
            }

            $this->newLine();

            $this->error(
                'IMPORT GAGAL.'
            );

            $this->error(
                $e->getMessage()
            );

            $this->newLine();

            $this->line(
                'File: ' .
                $this->excelFile
            );

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
    | IMPORT COMPETENCY
    |--------------------------------------------------------------------------
    |
    | STANDKOM:
    |
    | LEVEL -> level
    | KODE  -> code
    | NAME  -> name
    | TRAIN -> existing_training
    |
    */

    private function importCompetencies(
        Worksheet $sheet
    ): void {

        $this->info(
            '=================================================='
        );

        $this->info(
            'IMPORT KOMPETENSI: STANDKOM'
        );

        $this->info(
            '=================================================='
        );

        $headerRow =
            $this->detectCompetencyHeaderRow(
                $sheet
            );

        if (!$headerRow) {

            $this->warn(
                'Header STANDKOM tidak ditemukan.'
            );

            return;
        }

        $columns =
            $this->detectCompetencyColumns(
                $sheet,
                $headerRow
            );

        $levelColumn =
            $columns['level'];

        $codeColumn =
            $columns['code'];

        $nameColumn =
            $columns['name'];

        $trainingColumn =
            $columns['training'];

        $this->line(
            '  Kolom level : ' .
            ($levelColumn ?: '-')
        );

        $this->line(
            '  Kolom kode  : ' .
            ($codeColumn ?: '-')
        );

        $this->line(
            '  Kolom nama  : ' .
            ($nameColumn ?: '-')
        );

        $this->line(
            '  Kolom train : ' .
            ($trainingColumn ?: '-')
        );

        if (
            !$levelColumn ||
            !$codeColumn ||
            !$nameColumn
        ) {

            $this->warn(
                'Struktur STANDKOM tidak lengkap.'
            );

            return;
        }

        $highestRow =
            $sheet->getHighestRow();

        for (
            $row = $headerRow + 1;
            $row <= $highestRow;
            $row++
        ) {

            $level =
                $this->cleanString(
                    $this->getCellValue(
                        $sheet,
                        $levelColumn,
                        $row
                    )
                );

            $code =
                $this->cleanString(
                    $this->getCellValue(
                        $sheet,
                        $codeColumn,
                        $row
                    )
                );

            $name =
                $this->cleanString(
                    $this->getCellValue(
                        $sheet,
                        $nameColumn,
                        $row
                    )
                );

            $existingTraining =
                $trainingColumn
                    ? $this->cleanString(
                        $this->getCellValue(
                            $sheet,
                            $trainingColumn,
                            $row
                        )
                    )
                    : null;

            /*
            |--------------------------------------------------------------------------
            | SKIP BARIS KOSONG
            |--------------------------------------------------------------------------
            */

            if (
                !$code ||
                !$name
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | SKIP FORMULA YANG BELUM TEREVALUASI
            |--------------------------------------------------------------------------
            */

            if (
                str_starts_with(
                    $code,
                    '='
                )
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | CACHE KEY
            |--------------------------------------------------------------------------
            */

            $cacheKey =
                mb_strtolower(
                    trim($code)
                );

            /*
            |--------------------------------------------------------------------------
            | COMPETENCY
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $this->competencyCache[
                        $cacheKey
                    ]
                )
            ) {

                $competencyId =
                    $this->competencyCache[
                        $cacheKey
                    ];

            } else {

                $competency =
                    DB::table('kompetensi')
                        ->where(
                            'kode',
                            $code
                        )
                        ->first();

                if (!$competency) {

                    $competencyId =
                        DB::table('kompetensi')
                            ->insertGetId([
                                'kode' =>
                                    $code,

                                'level' =>
                                    $level,

                                'nama' =>
                                    $name,

                                'pelatihan_eksisting' =>
                                    $existingTraining,

                                'created_at' =>
                                    now(),

                                'updated_at' =>
                                    now(),
                            ]);

                    $this->totalCompetencyBaru++;

                } else {

                    $competencyId =
                        $competency->id;

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE DATA KOMPETENSI
                    |--------------------------------------------------------------------------
                    */

                    $updateData = [
                        'updated_at' =>
                            now(),
                    ];

                    if (
                        $level &&
                        $competency->level !== $level
                    ) {
                        $updateData['level'] =
                            $level;
                    }

                    if (
                        $name &&
                        $competency->nama !== $name
                    ) {
                        $updateData['nama'] =
                            $name;
                    }

                    if (
                        $existingTraining &&
                        $competency->pelatihan_eksisting !==
                            $existingTraining
                    ) {
                        $updateData['pelatihan_eksisting'] =
                            $existingTraining;
                    }

                    if (
                        count($updateData) > 1
                    ) {

                        DB::table('kompetensi')
                            ->where(
                                'id',
                                $competencyId
                            )
                            ->update(
                                $updateData
                            );

                        $this->totalCompetencyUpdate++;
                    }
                }

                $this->competencyCache[
                    $cacheKey
                ] =
                    $competencyId;
            }

            /*
            |--------------------------------------------------------------------------
            | PROGRESS
            |--------------------------------------------------------------------------
            */

            if (
                $row % 25 === 0
            ) {

                $this->line(
                    "  Kompetensi baris {$row}/{$highestRow}..."
                );
            }
        }

        $this->info(
            '  Kompetensi selesai.'
        );

        $this->line(
            '  Kompetensi baru : ' .
            $this->totalCompetencyBaru
        );

        $this->line(
            '  Kompetensi update : ' .
            $this->totalCompetencyUpdate
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FIND SHEET
    |--------------------------------------------------------------------------
    */

    private function findSheet(
        array $sheets,
        string $name
    ): ?Worksheet {

        foreach ($sheets as $sheet) {

            if (
                mb_strtolower(
                    trim(
                        $sheet->getTitle()
                    )
                ) ===
                mb_strtolower(
                    trim($name)
                )
            ) {
                return $sheet;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT COMPETENCY HEADER
    |--------------------------------------------------------------------------
    */

    private function detectCompetencyHeaderRow(
        Worksheet $sheet
    ): ?int {

        $maxRow =
            min(
                $sheet->getHighestRow(),
                20
            );

        $maxColumn =
            Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

        for (
            $row = 1;
            $row <= $maxRow;
            $row++
        ) {

            $levelFound = false;
            $codeFound = false;
            $nameFound = false;

            for (
                $column = 1;
                $column <= $maxColumn;
                $column++
            ) {

                $letter =
                    Coordinate::stringFromColumnIndex(
                        $column
                    );

                $value =
                    mb_strtolower(
                        trim(
                            (string) $this->getCellValue(
                                $sheet,
                                $letter,
                                $row
                            )
                        )
                    );

                if (
                    in_array(
                        $value,
                        [
                            'level',
                            'tingkat',
                            'pelatihan eksisting pusbangkom sda-ckps',
                        ],
                        true
                    )
                ) {
                    $levelFound = true;
                }

                if (
                    in_array(
                        $value,
                        [
                            'kode',
                            'kode kompetensi',
                            'code',
                        ],
                        true
                    )
                ) {
                    $codeFound = true;
                }

                if (
                    in_array(
                        $value,
                        [
                            'name',
                            'nama',
                            'kompetensi teknis',
                            'kompetensi',
                        ],
                        true
                    )
                ) {
                    $nameFound = true;
                }
            }

            if (
                $levelFound &&
                $codeFound &&
                $nameFound
            ) {
                return $row;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT COMPETENCY COLUMNS
    |--------------------------------------------------------------------------
    */

    private function detectCompetencyColumns(
        Worksheet $sheet,
        int $headerRow
    ): array {

        $result = [
            'level' => null,
            'code' => null,
            'name' => null,
            'training' => null,
        ];

        $highestColumnIndex =
            Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

        for (
            $column = 1;
            $column <= $highestColumnIndex;
            $column++
        ) {

            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $value =
                mb_strtolower(
                    trim(
                        (string) $this->getCellValue(
                            $sheet,
                            $letter,
                            $headerRow
                        )
                    )
                );

            if (
                in_array(
                    $value,
                    [
                        'level',
                        'tingkat',
                        'pelatihan eksisting pusbangkom sda-ckps',
                    ],
                    true
                )
            ) {

                if (!$result['level']) {
                    $result['level'] =
                        $letter;
                }

                continue;
            }

            if (
                in_array(
                    $value,
                    [
                        'kode',
                        'kode kompetensi',
                        'code',
                    ],
                    true
                )
            ) {

                if (!$result['code']) {
                    $result['code'] =
                        $letter;
                }

                continue;
            }

            if (
                in_array(
                    $value,
                    [
                        'name',
                        'nama',
                        'kompetensi teknis',
                        'kompetensi',
                    ],
                    true
                )
            ) {

                if (!$result['name']) {
                    $result['name'] =
                        $letter;
                }

                continue;
            }

            if (
                in_array(
                    $value,
                    [
                        'train',
                        'training',
                        'pelatihan',
                        'pelatihan yang perlu diikuti',
                        'pelatihan eksisting',
                    ],
                    true
                )
            ) {

                if (!$result['training']) {
                    $result['training'] =
                        $letter;
                }
            }
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT ASN HEADER
    |--------------------------------------------------------------------------
    */

    private function detectHeaderRow(
        Worksheet $sheet
    ): ?int {

        $highestRow =
            min(
                $sheet->getHighestRow(),
                20
            );

        $highestColumnIndex =
            Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

        for (
            $row = 1;
            $row <= $highestRow;
            $row++
        ) {

            $nameFound = false;
            $nipFound = false;

            for (
                $column = 1;
                $column <= $highestColumnIndex;
                $column++
            ) {

                $letter =
                    Coordinate::stringFromColumnIndex(
                        $column
                    );

                $value =
                    mb_strtolower(
                        trim(
                            (string) $this->getCellValue(
                                $sheet,
                                $letter,
                                $row
                            )
                        )
                    );

                if (
                    in_array(
                        $value,
                        [
                            'nama',
                            'nama pegawai',
                            'nama asn',
                            'name',
                        ],
                        true
                    )
                ) {
                    $nameFound = true;
                }

                if (
                    in_array(
                        $value,
                        [
                            'nip',
                            'nip baru',
                            'nippegawai',
                            'nip pegawai',
                        ],
                        true
                    )
                ) {
                    $nipFound = true;
                }
            }

            if (
                $nameFound &&
                $nipFound
            ) {
                return $row;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT ASN COLUMNS
    |--------------------------------------------------------------------------
    */

    private function detectColumns(
        Worksheet $sheet,
        int $headerRow
    ): array {

        $result = [
            'name' => null,
            'nip' => null,
            'rank' => null,
            'position' => null,
            'unit' => null,
        ];

        $highestColumnIndex =
            Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

        for (
            $column = 1;
            $column <= $highestColumnIndex;
            $column++
        ) {

            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $value =
                mb_strtolower(
                    trim(
                        (string) $this->getCellValue(
                            $sheet,
                            $letter,
                            $headerRow
                        )
                    )
                );

            switch ($value) {

                case 'nama':
                case 'nama pegawai':
                case 'nama asn':
                case 'name':

                    if (!$result['name']) {
                        $result['name'] =
                            $letter;
                    }

                    break;

                case 'nip':
                case 'nip baru':
                case 'nippegawai':
                case 'nip pegawai':

                    if (!$result['nip']) {
                        $result['nip'] =
                            $letter;
                    }

                    break;

                case 'pangkat':
                case 'golongan':
                case 'gol':
                case 'pangkat/golongan':

                    if (!$result['rank']) {
                        $result['rank'] =
                            $letter;
                    }

                    break;

                case 'jabatan':
                case 'jabatan fungsional':
                case 'jabatan struktural':
                case 'position':

                    if (!$result['position']) {
                        $result['position'] =
                            $letter;
                    }

                    break;

                case 'unit':
                case 'unit kerja':
                case 'unit organisasi':
                case 'satker':

                    if (!$result['unit']) {
                        $result['unit'] =
                            $letter;
                    }

                    break;
            }
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT TRAINING COLUMNS
    |--------------------------------------------------------------------------
    |
    | Jangan memasukkan kolom identitas.
    |
    | Untuk sheet yang mempunyai header:
    |
    | Pelatihan yang telah diikuti
    |
    | kolom tersebut menjadi sumber training.
    |
    */

    private function detectTrainingColumns(
        Worksheet $sheet,
        int $headerRow,
        array $columns
    ): array {

        $result = [];

        $highestColumnIndex =
            Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

        $identityColumns = array_filter([
            $columns['name'],
            $columns['nip'],
            $columns['rank'],
            $columns['position'],
            $columns['unit'],
        ]);

        for (
            $column = 1;
            $column <= $highestColumnIndex;
            $column++
        ) {

            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            if (
                in_array(
                    $letter,
                    $identityColumns,
                    true
                )
            ) {
                continue;
            }

            $header =
                mb_strtolower(
                    trim(
                        (string) $this->getCellValue(
                            $sheet,
                            $letter,
                            $headerRow
                        )
                    )
                );

            if ($header === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | JANGAN AMBIL KOLOM KODE / KOMPETENSI / STATUS
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $header,
                    [
                        'nama',
                        'nip',
                        'pangkat',
                        'golongan',
                        'gol',
                        'pangkat/golongan',
                        'jabatan',
                        'jabatan fungsional',
                        'jabatan struktural',
                        'unit',
                        'unit kerja',
                        'unit organisasi',
                        'satker',
                        'kode',
                        'kode kompetensi',
                        'kompetensi',
                        'kompetensi teknis',
                        'kode level standar kompetensi',
                        'tahun',
                    ],
                    true
                )
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | PRIORITAS HEADER TRAINING
            |--------------------------------------------------------------------------
            */

            if (
                str_contains(
                    $header,
                    'pelatihan'
                ) ||
                str_contains(
                    $header,
                    'training'
                )
            ) {
                $result[] = $letter;

                continue;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        |
        | Beberapa sheet menggunakan header kosong di kolom training.
        |
        | Contoh:
        |
        | B = Nama
        | C = NIP
        | D = Pelatihan
        | E = Tahun
        |
        | Kalau header training tidak terbaca, kita tidak mengambil
        | kolom sembarangan. Ini sengaja untuk mencegah data kompetensi
        | masuk sebagai nama training.
        |
        */

        return array_values(
            array_unique($result)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FIND / CREATE UNIT
    |--------------------------------------------------------------------------
    */

    private function findOrCreateUnit(
        ?string $unitName
    ): ?int {

        if (!$unitName) {
            return null;
        }

        $key =
            mb_strtolower(
                trim($unitName)
            );

        if (
            isset(
                $this->unitCache[$key]
            )
        ) {
            return $this->unitCache[$key];
        }

        $unit =
            DB::table('units')
                ->where(
                    'name',
                    $unitName
                )
                ->first();

        if ($unit) {

            $unitId =
                $unit->id;

        } else {

            $unitId =
                DB::table('units')
                    ->insertGetId([
                        'name' =>
                            $unitName,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);
        }

        $this->unitCache[$key] =
            $unitId;

        return $unitId;
    }

    /*
    |--------------------------------------------------------------------------
    | FIND / CREATE ASN
    |--------------------------------------------------------------------------
    */

    private function findOrCreateAsn(
        string $nip,
        string $name,
        ?string $position,
        ?string $rank,
        ?int $unitId
    ): int {

        if (
            isset(
                $this->asnCache[$nip]
            )
        ) {

            $asnId =
                $this->asnCache[$nip];

            $this->updateAsnIfNeeded(
                $asnId,
                $name,
                $position,
                $rank,
                $unitId
            );

            return $asnId;
        }

        $asn =
            DB::table('asns')
                ->where(
                    'nip',
                    $nip
                )
                ->first();

        if (!$asn) {

            $asnId =
                DB::table('asns')
                    ->insertGetId([
                        'nip' =>
                            $nip,

                        'name' =>
                            $name,

                        'position' =>
                            $position,

                        'rank' =>
                            $rank,

                        'unit_id' =>
                            $unitId,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);

            $this->totalAsnBaru++;

        } else {

            $asnId =
                $asn->id;

            $this->updateAsnIfNeeded(
                $asnId,
                $name,
                $position,
                $rank,
                $unitId
            );
        }

        $this->asnCache[$nip] =
            $asnId;

        return $asnId;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ASN
    |--------------------------------------------------------------------------
    */

    private function updateAsnIfNeeded(
        int $asnId,
        ?string $name,
        ?string $position,
        ?string $rank,
        ?int $unitId
    ): void {

        $asn =
            DB::table('asns')
                ->where(
                    'id',
                    $asnId
                )
                ->first();

        if (!$asn) {
            return;
        }

        $updateData = [
            'updated_at' =>
                now(),
        ];

        if (
            $name &&
            empty($asn->name)
        ) {
            $updateData['name'] =
                $name;
        }

        if (
            $position &&
            empty($asn->position)
        ) {
            $updateData['position'] =
                $position;
        }

        if (
            $rank &&
            empty($asn->rank)
        ) {
            $updateData['rank'] =
                $rank;
        }

        if (
            $unitId &&
            empty($asn->unit_id)
        ) {
            $updateData['unit_id'] =
                $unitId;
        }

        if (
            count($updateData) > 1
        ) {

            DB::table('asns')
                ->where(
                    'id',
                    $asnId
                )
                ->update(
                    $updateData
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FIND / CREATE TRAINING
    |--------------------------------------------------------------------------
    */

    private function findOrCreateTraining(
        string $trainingName
    ): int {

        $key =
            mb_strtolower(
                trim($trainingName)
            );

        if (
            isset(
                $this->trainingCache[$key]
            )
        ) {
            return $this->trainingCache[$key];
        }

        $training =
            DB::table('trainings')
                ->where(
                    'name',
                    $trainingName
                )
                ->first();

        if ($training) {

            $trainingId =
                $training->id;

        } else {

            $trainingId =
                DB::table('trainings')
                    ->insertGetId([
                        'name' =>
                            $trainingName,

                        'code' =>
                            null,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);

            $this->totalTrainingBaru++;
        }

        $this->trainingCache[$key] =
            $trainingId;

        return $trainingId;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE ASN TRAINING RELATION
    |--------------------------------------------------------------------------
    */

    private function createTrainingRelation(
        int $asnId,
        int $trainingId,
        ?int $year,
        string $sheetName
    ): void {

        $relation =
            DB::table('asn_trainings')
                ->where(
                    'asn_id',
                    $asnId
                )
                ->where(
                    'training_id',
                    $trainingId
                )
                ->first();

        if (!$relation) {

            DB::table('asn_trainings')
                ->insert([
                    'asn_id' =>
                        $asnId,

                    'training_id' =>
                        $trainingId,

                    'year' =>
                        $year,

                    'status' =>
                        'terdeteksi',

                    'source_sheet' =>
                        $sheetName,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

            $this->totalRelasiTraining++;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATA YANG BELUM ADA
        |--------------------------------------------------------------------------
        */

        $updateData = [
            'updated_at' =>
                now(),
        ];

        if (
            $year &&
            empty($relation->year)
        ) {
            $updateData['year'] =
                $year;
        }

        if (
            empty($relation->source_sheet)
        ) {
            $updateData['source_sheet'] =
                $sheetName;
        }

        if (
            count($updateData) > 1
        ) {

            DB::table('asn_trainings')
                ->where(
                    'id',
                    $relation->id
                )
                ->update(
                    $updateData
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DETECT TRAINING YEAR
    |--------------------------------------------------------------------------
    */

    private function detectTrainingYear(
        Worksheet $sheet,
        int $row,
        string $trainingColumn,
        int $headerRow
    ): ?int {

        $highestColumnIndex =
            Coordinate::columnIndexFromString(
                $sheet->getHighestColumn()
            );

        /*
        |--------------------------------------------------------------------------
        | CARI KOLOM "TAHUN" TERDEKAT
        |--------------------------------------------------------------------------
        */

        $trainingIndex =
            Coordinate::columnIndexFromString(
                $trainingColumn
            );

        for (
            $column = $trainingIndex + 1;
            $column <= $highestColumnIndex;
            $column++
        ) {

            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $header =
                mb_strtolower(
                    trim(
                        (string) $this->getCellValue(
                            $sheet,
                            $letter,
                            $headerRow
                        )
                    )
                );

            if (
                $header === 'tahun' ||
                str_contains(
                    $header,
                    'tahun'
                )
            ) {

                $yearValue =
                    $this->cleanString(
                        $this->getCellValue(
                            $sheet,
                            $letter,
                            $row
                        )
                    );

                if (
                    $yearValue &&
                    preg_match(
                        '/^(19|20)\d{2}$/',
                        $yearValue
                    )
                ) {
                    return (int) $yearValue;
                }

                return null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FALLBACK:
        | CARI SEMUA KOLOM DENGAN HEADER TAHUN
        |--------------------------------------------------------------------------
        */

        for (
            $column = 1;
            $column <= $highestColumnIndex;
            $column++
        ) {

            $letter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $header =
                mb_strtolower(
                    trim(
                        (string) $this->getCellValue(
                            $sheet,
                            $letter,
                            $headerRow
                        )
                    )
                );

            if (
                $header === 'tahun'
            ) {

                $yearValue =
                    $this->cleanString(
                        $this->getCellValue(
                            $sheet,
                            $letter,
                            $row
                        )
                    );

                if (
                    $yearValue &&
                    preg_match(
                        '/^(19|20)\d{2}$/',
                        $yearValue
                    )
                ) {
                    return (int) $yearValue;
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | INVALID TRAINING VALUE
    |--------------------------------------------------------------------------
    */

    private function isInvalidTrainingValue(
        string $value
    ): bool {

        $lower =
            mb_strtolower(
                trim($value)
            );

        if (
            $this->looksLikeHeader($lower)
        ) {
            return true;
        }

        return in_array(
            $lower,
            [
                'belum',
                'belum ada',
                'belum ada data',
                'belum terdapat pelatihan terkait',
                'sudah',
                'tidak',
                'tidak ada',
                'tidak tersedia',
                'n/a',
                '#n/a',
                '-',
                'ya',
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET CELL VALUE
    |--------------------------------------------------------------------------
    |
    | PENTING:
    |
    | Tidak menggunakan:
    |
    | getCellByColumnAndRow()
    |
    */

    private function getCellValue(
        Worksheet $sheet,
        ?string $column,
        int $row
    ): ?string {

        if (!$column) {
            return null;
        }

        $coordinate =
            $column . $row;

        return trim(
            (string) $sheet
                ->getCell($coordinate)
                ->getFormattedValue()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN STRING
    |--------------------------------------------------------------------------
    */

    private function cleanString(
        $value
    ): ?string {

        if ($value === null) {
            return null;
        }

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                (string) $value
            );

        $value =
            trim($value);

        return $value === ''
            ? null
            : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN NIP
    |--------------------------------------------------------------------------
    */

    private function cleanNip(
        $value
    ): ?string {

        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        $value =
            preg_replace(
                '/\s+/u',
                '',
                $value
            );

        /*
        |--------------------------------------------------------------------------
        | EXCEL: 197801122006042004.0
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^(\d+)\.0$/',
                $value,
                $matches
            )
        ) {
            $value =
                $matches[1];
        }

        /*
        |--------------------------------------------------------------------------
        | SCIENTIFIC NOTATION
        |--------------------------------------------------------------------------
        */

        if (
            stripos(
                $value,
                'E+'
            ) !== false &&
            is_numeric($value)
        ) {

            $value =
                sprintf(
                    '%.0f',
                    (float) $value
                );
        }

        /*
        |--------------------------------------------------------------------------
        | HANYA ANGKA
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                '/[^0-9]/',
                '',
                $value
            );

        return $value === ''
            ? null
            : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | VALID NIP
    |--------------------------------------------------------------------------
    */

    private function isValidNip(
        ?string $nip
    ): bool {

        if (!$nip) {
            return false;
        }

        return preg_match(
            '/^\d{18}$/',
            $nip
        ) === 1;
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER CHECK
    |--------------------------------------------------------------------------
    */

    private function looksLikeHeader(
        string $value
    ): bool {

        $value =
            mb_strtolower(
                trim($value)
            );

        return in_array(
            $value,
            [
                'nama',
                'nip',
                'pelatihan',
                'training',
                'tahun',
                'kompetensi',
                'kompetensi teknis',
                'kode',
                'kode kompetensi',
                'kode level standar kompetensi',
                'teknis',
                'jabatan',
                'pangkat',
                'golongan',
                'unit',
                'unit kerja',
                'unit organisasi',
                'satker',
            ],
            true
        );
    }
}