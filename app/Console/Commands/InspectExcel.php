<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InspectExcel extends Command
{
    protected $signature = 'excel:inspect-content';

    protected $description = 'Memeriksa struktur file Excel CKPS';

    public function handle(): int
    {
        $path = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        if (!file_exists($path)) {
            $this->error('File Excel tidak ditemukan:');
            $this->line($path);

            return self::FAILURE;
        }

        $this->info('Membaca file Excel...');
        $this->newLine();

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);

            $spreadsheet = $reader->load($path);

            $worksheets = $spreadsheet->getWorksheetIterator();

            $totalSheets = $spreadsheet->getSheetCount();

            $this->info("Jumlah sheet: {$totalSheets}");
            $this->newLine();

            $index = 1;

            foreach ($worksheets as $worksheet) {
                $name = $worksheet->getTitle();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();

                $this->line(
                    "{$index}. {$name} | " .
                    "Baris: {$highestRow} | " .
                    "Kolom terakhir: {$highestColumn}"
                );

                $index++;
            }

            $this->newLine();
            $this->info('Inspeksi selesai.');

            return self::SUCCESS;

        } catch (\Throwable $e) {
    $this->error('Gagal membaca Excel.');
    $this->error('Tipe error: ' . get_class($e));
    $this->error('Pesan: ' . $e->getMessage());
    $this->error('File: ' . $path);

    return self::FAILURE;
}
    }
}