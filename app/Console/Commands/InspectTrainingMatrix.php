<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectTrainingMatrix extends Command
{
    protected $signature = 'excel:inspect-training';

    protected $description = 'Inspect matrix pelatihan ASN';

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

        $reader = IOFactory::createReaderForFile($path);

        // Jangan hanya membaca calculated value.
        $reader->setReadDataOnly(false);

        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheetByName('BPBPK Jabar');

        if (!$sheet) {
            $this->error('Sheet BPBPK Jabar tidak ditemukan.');

            return self::FAILURE;
        }

        $this->info('Membaca BPBPK Jabar...');
        $this->newLine();

        /*
         * Tampilkan struktur kolom G-AM
         */
        $this->info('HEADER PELATIHAN:');
        $this->line(str_repeat('=', 80));

        for ($column = 7; $column <= 39; $column++) {

            $letter = $this->columnName($column);

            $cell = $sheet->getCell("{$letter}3");

            $value = $cell->getValue();

            $formatted = $cell->getFormattedValue();

            $this->line(
                "{$letter}3 | value=[" .
                var_export($value, true) .
                "] | formatted=[" .
                $formatted .
                "]"
            );
        }

        $this->newLine();

        /*
         * Tampilkan isi matrix ASN
         */
        $this->info('DATA MATRIX ASN:');
        $this->line(str_repeat('=', 80));

        for ($row = 4; $row <= 20; $row++) {

            $nama = trim(
                (string) $sheet
                    ->getCell("B{$row}")
                    ->getFormattedValue()
            );

            $nip = trim(
                (string) $sheet
                    ->getCell("C{$row}")
                    ->getFormattedValue()
            );

            if ($nama === '' && $nip === '') {
                continue;
            }

            $this->newLine();

            $this->line(
                "BARIS {$row}: {$nama}"
            );

            $this->line(
                "NIP: {$nip}"
            );

            for ($column = 7; $column <= 39; $column++) {

                $letter = $this->columnName($column);

                $cell = $sheet->getCell(
                    "{$letter}{$row}"
                );

                $raw = $cell->getValue();

                $formatted = $cell->getFormattedValue();

                $type = $cell->getDataType();

                /*
                 * Tampilkan SEMUA cell,
                 * termasuk yang kosong.
                 */
                $this->line(
                    "{$letter}{$row} " .
                    "| raw=[" .
                    var_export($raw, true) .
                    "] " .
                    "| formatted=[" .
                    $formatted .
                    "] " .
                    "| type={$type}"
                );
            }
        }

        $this->newLine();

        $this->info(
            'Inspeksi matrix selesai.'
        );

        return self::SUCCESS;
    }

    private function columnName(int $number): string
    {
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