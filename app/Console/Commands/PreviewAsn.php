<?php

namespace App\Console\Commands;

use App\Services\ExcelSheetParser;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PreviewAsn extends Command
{
    protected $signature = 'excel:preview-asn';

    protected $description = 'Preview data ASN hasil pembacaan Excel';

    public function handle(ExcelSheetParser $parser): int
    {
        $path = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        if (!file_exists($path)) {
            $this->error('File Excel tidak ditemukan.');
            return self::FAILURE;
        }

        $this->info('Membaca data ASN...');
        $this->newLine();

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        $asn = [];

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $sheet
        ) {
            $sheetName = $sheet->getTitle();

            // STANDKOM bukan data ASN
            if ($sheetName === 'STANDKOM') {
                continue;
            }

            $structure = $parser->detectAsnColumns($sheet);

            $headerRow = $structure['header_row'];

            $nameColumn = $structure['name'];
            $nipColumn = $structure['nip'];
            $rankColumn = $structure['rank'];
            $positionColumn = $structure['position'];
            $unitColumn = $structure['unit'];

            // Sheet tidak mempunyai struktur ASN standar
            if (!$headerRow || !$nameColumn || !$nipColumn) {
                continue;
            }

            $highestRow = $sheet->getHighestRow();

            for (
                $row = $headerRow + 1;
                $row <= $highestRow;
                $row++
            ) {
                $nip = $this->cellValue(
                    $sheet,
                    $nipColumn,
                    $row
                );

                $nip = preg_replace(
                    '/\D/',
                    '',
                    $nip
                );

                // NIP harus 18 digit
                if (strlen($nip) !== 18) {
                    continue;
                }

                $name = $this->cellValue(
                    $sheet,
                    $nameColumn,
                    $row
                );

                if ($name === '') {
                    continue;
                }

                /*
                 * Jika ASN muncul di banyak sheet,
                 * jangan membuat data ASN baru.
                 * Gunakan NIP sebagai identitas utama.
                 */
                if (!isset($asn[$nip])) {
                    $asn[$nip] = [
                        'nama' => $name,
                        'nip' => $nip,
                        'pangkat' => $rankColumn
                            ? $this->cellValue(
                                $sheet,
                                $rankColumn,
                                $row
                            )
                            : '',

                        'jabatan' => $positionColumn
                            ? $this->cellValue(
                                $sheet,
                                $positionColumn,
                                $row
                            )
                            : '',

                        'unit_kerja' => $unitColumn
                            ? $this->cellValue(
                                $sheet,
                                $unitColumn,
                                $row
                            )
                            : '',

                        'sumber_sheet' => $sheetName,
                    ];

                    continue;
                }

                /*
                 * Kalau data yang ditemukan di sheet lain
                 * lebih lengkap, lengkapi data sebelumnya.
                 */
                if (
                    $asn[$nip]['nama'] === '' &&
                    $name !== ''
                ) {
                    $asn[$nip]['nama'] = $name;
                }

                if (
                    $asn[$nip]['pangkat'] === '' &&
                    $rankColumn
                ) {
                    $asn[$nip]['pangkat'] =
                        $this->cellValue(
                            $sheet,
                            $rankColumn,
                            $row
                        );
                }

                if (
                    $asn[$nip]['jabatan'] === '' &&
                    $positionColumn
                ) {
                    $asn[$nip]['jabatan'] =
                        $this->cellValue(
                            $sheet,
                            $positionColumn,
                            $row
                        );
                }

                if (
                    $asn[$nip]['unit_kerja'] === '' &&
                    $unitColumn
                ) {
                    $asn[$nip]['unit_kerja'] =
                        $this->cellValue(
                            $sheet,
                            $unitColumn,
                            $row
                        );
                }
            }
        }

        $asn = array_values($asn);

        $this->line(
            str_repeat('=', 70)
        );

        $this->info(
            'PREVIEW 20 ASN PERTAMA'
        );

        $this->line(
            str_repeat('=', 70)
        );

        $this->newLine();

        $limit = min(20, count($asn));

        for ($i = 0; $i < $limit; $i++) {

            $data = $asn[$i];

            $this->line(
                ($i + 1) . '. ' . $data['nama']
            );

            $this->line(
                '   NIP        : ' .
                ($data['nip'] ?: '-')
            );

            $this->line(
                '   Pangkat    : ' .
                ($data['pangkat'] ?: '-')
            );

            $this->line(
                '   Jabatan    : ' .
                ($data['jabatan'] ?: '-')
            );

            $this->line(
                '   Unit Kerja : ' .
                ($data['unit_kerja'] ?: '-')
            );

            $this->line(
                '   Sumber     : ' .
                ($data['sumber_sheet'] ?: '-')
            );

            $this->newLine();
        }

        $this->line(
            str_repeat('-', 70)
        );

        $this->info(
            'RINGKASAN'
        );

        $this->line(
            'Total ASN unik : ' . count($asn)
        );

        $this->newLine();

        $this->info(
            'Preview selesai.'
        );

        $this->warn(
            'Belum ada data yang dimasukkan ke database.'
        );

        return self::SUCCESS;
    }

    private function cellValue(
        $sheet,
        string $column,
        int $row
    ): string {
        $value = $sheet
            ->getCell("{$column}{$row}")
            ->getFormattedValue();

        return trim((string) $value);
    }
}