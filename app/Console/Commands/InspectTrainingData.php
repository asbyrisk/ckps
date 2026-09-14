<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectTrainingData extends Command
{
    protected $signature = 'excel:inspect-training-data';

    protected $description = 'Inspect data pelatihan ASN';

    public function handle(): int
    {
        $path = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        $sheets = [
            'BTBGPL',
            'Sheet2',
            'BPB',
            'DSSPI',
        ];

        foreach ($sheets as $sheetName) {

            $sheet = $spreadsheet->getSheetByName(
                $sheetName
            );

            if (!$sheet) {
                continue;
            }

            $this->newLine();

            $this->line(
                '=================================================='
            );

            $this->info(
                "SHEET: {$sheetName}"
            );

            $this->line(
                '=================================================='
            );

            $highestRow =
                $sheet->getHighestRow();

            for ($row = 1; $row <= min($highestRow, 15); $row++) {

                $nama = trim(
                    (string) $sheet
                        ->getCell("B{$row}")
                        ->getValue()
                );

                $nip = trim(
                    (string) $sheet
                        ->getCell("C{$row}")
                        ->getValue()
                );

                $training = trim(
                    (string) $sheet
                        ->getCell("H{$row}")
                        ->getValue()
                );

                $year = trim(
                    (string) $sheet
                        ->getCell("I{$row}")
                        ->getValue()
                );

                $code = trim(
                    (string) $sheet
                        ->getCell("J{$row}")
                        ->getValue()
                );

                $competency = trim(
                    (string) $sheet
                        ->getCell("K{$row}")
                        ->getValue()
                );

                if (
                    $nama !== '' ||
                    $nip !== '' ||
                    $training !== '' ||
                    $year !== '' ||
                    $code !== '' ||
                    $competency !== ''
                ) {
                    $this->line(
                        "BARIS {$row}"
                    );

                    $this->line(
                        "Nama        : {$nama}"
                    );

                    $this->line(
                        "NIP         : {$nip}"
                    );

                    $this->line(
                        "Pelatihan   : {$training}"
                    );

                    $this->line(
                        "Tahun       : {$year}"
                    );

                    $this->line(
                        "Kompetensi  : {$code}"
                    );

                    $this->line(
                        "Teknis      : {$competency}"
                    );

                    $this->newLine();
                }
            }
        }

        $this->info(
            'Inspeksi data pelatihan selesai.'
        );

        return self::SUCCESS;
    }
}