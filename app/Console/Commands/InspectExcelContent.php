<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectExcelContent extends Command
{
    protected $signature = 'excel:inspect-content';

    protected $description = 'Melihat isi awal sheet Excel CKPS';

    public function handle(): int
    {
        $path = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan:");
            $this->error($path);

            return self::FAILURE;
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($path);

            // Hanya sheet yang kita butuhkan untuk analisis awal.
            $targetSheets = [
                'STANDKOM',
                'Sheet2',
                'BTBGPL',
                'BTBGPL (2)',
                'Sekditjen',
                'BPBPK Jabar',
            ];

            foreach ($targetSheets as $sheetName) {

                if (!$spreadsheet->sheetNameExists($sheetName)) {
                    $this->warn(
                        "Sheet tidak ditemukan: {$sheetName}"
                    );

                    continue;
                }

                $sheet = $spreadsheet->getSheetByName(
                    $sheetName
                );

                $this->newLine();

                $this->info(
                    '=================================================='
                );

                $this->info(
                    "SHEET: {$sheetName}"
                );

                $this->info(
                    '=================================================='
                );

                $highestRow = min(
                    $sheet->getHighestRow(),
                    10
                );

                $highestColumn =
                    $sheet->getHighestColumn();

                $rows = $sheet->rangeToArray(
                    "A1:{$highestColumn}{$highestRow}",
                    null,
                    true,
                    false
                );

                foreach ($rows as $index => $row) {

                    $rowNumber = $index + 1;

                    $values = [];

                    foreach (
                        $row
                        as $columnIndex => $value
                    ) {

                        if (
                            $value !== null &&
                            trim((string) $value) !== ''
                        ) {

                            $column =
                                $this->columnName(
                                    $columnIndex + 1
                                );

                            $values[] =
                                "{$column}=" .
                                trim((string) $value);
                        }
                    }

                    if (!empty($values)) {

                        $this->line(
                            "Baris {$rowNumber}: " .
                            implode(
                                ' | ',
                                $values
                            )
                        );
                    }
                }
            }

            $this->newLine();

            $this->info(
                'Analisis isi sheet selesai.'
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {

            $this->error(
                'Gagal membaca Excel.'
            );

            $this->error(
                'Tipe: ' .
                get_class($e)
            );

            $this->error(
                'Pesan: ' .
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function columnName(
        int $number
    ): string {

        $name = '';

        while ($number > 0) {

            $remainder =
                ($number - 1) % 26;

            $name =
                chr(65 + $remainder) .
                $name;

            $number =
                intdiv(
                    $number - 1,
                    26
                );
        }

        return $name;
    }
}