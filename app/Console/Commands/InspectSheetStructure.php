<?php

namespace App\Console\Commands;

use App\Services\ExcelSheetParser;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectSheetStructure extends Command
{
    protected $signature =
        'excel:inspect-structure';

    protected $description =
        'Mendeteksi struktur kolom semua sheet Excel';

    public function handle(
        ExcelSheetParser $parser
    ): int {

        $path = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        $reader =
            IOFactory::createReaderForFile($path);

        $reader->setReadDataOnly(true);

        $spreadsheet =
            $reader->load($path);

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $sheet
        ) {

            $name =
                $sheet->getTitle();

            if ($name === 'STANDKOM') {
                continue;
            }

            $columns =
                $parser->detectAsnColumns($sheet);

            $this->newLine();

            $this->info(
                "SHEET: {$name}"
            );

            $this->line(
                "Header row : " .
                ($columns['header_row'] ?? '-')
            );

            $this->line(
                "Nama       : " .
                ($columns['name'] ?? '-')
            );

            $this->line(
                "NIP        : " .
                ($columns['nip'] ?? '-')
            );

            $this->line(
                "Pangkat     : " .
                ($columns['rank'] ?? '-')
            );

            $this->line(
                "Jabatan     : " .
                ($columns['position'] ?? '-')
            );

            $this->line(
                "Unit Kerja  : " .
                ($columns['unit'] ?? '-')
            );

            $this->line(
                "Pelatihan   : " .
                ($columns['training'] ?? '-')
            );

            $this->line(
                "Tahun       : " .
                ($columns['year'] ?? '-')
            );

            $this->line(
                "Kode        : " .
                ($columns['code'] ?? '-')
            );

            $this->line(
                "Kompetensi  : " .
                ($columns['competency'] ?? '-')
            );
        }

        $this->newLine();

        $this->info(
            'Deteksi struktur selesai.'
        );

        return self::SUCCESS;
    }
}