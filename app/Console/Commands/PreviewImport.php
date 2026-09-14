<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PreviewImport extends Command
{
    protected $signature = 'excel:preview-import';

    protected $description = 'Preview data ASN sebelum import ke database';

    public function handle(): int
    {
        $path = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        if (!file_exists($path)) {
            $this->error("File Excel tidak ditemukan:");
            $this->error($path);

            return self::FAILURE;
        }

        $this->info('Membaca file Excel...');
        $this->newLine();

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        $totalSheets = $spreadsheet->getSheetCount();

        $this->info("Jumlah sheet: {$totalSheets}");
        $this->newLine();

        $allNips = [];

        $totalRows = 0;

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $sheet
        ) {

            $sheetName = $sheet->getTitle();

            // STANDKOM adalah master kompetensi,
            // bukan daftar ASN.
            if ($sheetName === 'STANDKOM') {
                continue;
            }

            $highestRow =
                $sheet->getHighestRow();

            $this->line(
                "SHEET: {$sheetName}"
            );

            $this->line(
                "Jumlah baris: {$highestRow}"
            );

            /*
             * Cari NIP di seluruh sheet.
             * Kita tidak mengasumsikan NIP
             * selalu berada di kolom C.
             */
            for (
                $row = 1;
                $row <= $highestRow;
                $row++
            ) {

                $highestColumn =
                    $sheet->getHighestColumn($row);

                $highestColumnIndex =
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                        $highestColumn
                    );

                for (
                    $column = 1;
                    $column <= $highestColumnIndex;
                    $column++
                ) {

                    $coordinate =
                        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                            $column
                        );

                    $value =
                        $sheet->getCell(
                            "{$coordinate}{$row}"
                        )->getValue();

                    if ($value === null) {
                        continue;
                    }

                    $value = trim((string) $value);

                    /*
                     * NIP ASN umumnya 18 digit.
                     */
                    if (
                        preg_match(
                            '/^\d{18}$/',
                            $value
                        )
                    ) {

                        $allNips[$value] = true;

                        $totalRows++;

                        break;
                    }
                }
            }

            $this->newLine();
        }

        $uniqueAsn = count($allNips);

        $this->newLine();

        $this->line(
            str_repeat('=', 50)
        );

        $this->info(
            'PREVIEW IMPORT CKPS'
        );

        $this->line(
            str_repeat('=', 50)
        );

        $this->newLine();

        $this->line(
            "NIP ditemukan        : {$totalRows}"
        );

        $this->line(
            "ASN unik berdasarkan NIP : {$uniqueAsn}"
        );

        $this->newLine();

        $this->line(
            str_repeat('-', 50)
        );

        $this->info(
            'Preview selesai.'
        );

        $this->warn(
            'Belum ada data yang dimasukkan ke database.'
        );

        return self::SUCCESS;
    }
}