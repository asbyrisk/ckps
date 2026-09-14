<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PreviewTraining extends Command
{
    /**
     * Nama dan signature command.
     */
    protected $signature = 'excel:preview-training';

    /**
     * Deskripsi command.
     */
    protected $description = 'Preview riwayat pelatihan ASN dari file Excel';

    /**
     * Jalankan command.
     */
    public function handle()
    {
        $this->info('Membaca riwayat pelatihan...');
        $this->newLine();

        // Lokasi file Excel
        $file = storage_path(
            'app/imports/Mapping ASN Bidang CKPS.xlsx'
        );

        // Pastikan file ada
        if (!file_exists($file)) {
            $this->error('File Excel tidak ditemukan:');
            $this->error($file);

            return self::FAILURE;
        }

        try {
            // Baca workbook
            $spreadsheet = IOFactory::load($file);

            // Ambil semua worksheet
            $sheets = $spreadsheet->getWorksheetIterator();

            $totalTraining = 0;

            foreach ($sheets as $sheet) {

                $sheetName = $sheet->getTitle();

                /*
                 * Sheet yang berupa master / referensi
                 * tidak perlu diproses sebagai riwayat pelatihan.
                 */
                if ($sheetName === 'STANDKOM') {
                    continue;
                }

                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $highestColumnIndex =
                    Coordinate::columnIndexFromString(
                        $highestColumn
                    );

                /*
                 * Kita hanya memproses sheet yang memiliki
                 * struktur data ASN.
                 *
                 * Umumnya:
                 * B = Nama
                 * C = NIP
                 * D/F = Pangkat
                 * E/F = Unit Kerja
                 */
                $this->info(
                    "SHEET: {$sheetName}"
                );

                $this->line(
                    "Jumlah baris: {$highestRow}"
                );

                $this->line(
                    "Kolom terakhir: {$highestColumn}"
                );

                $this->newLine();

                /*
                 * Cari header.
                 *
                 * Pada file kita sebagian besar header berada
                 * di baris 2 atau 3.
                 */
                $headerRow = $this->findHeaderRow($sheet);

                if ($headerRow === null) {
                    $this->warn(
                        "Header ASN tidak ditemukan. Sheet dilewati."
                    );

                    $this->newLine();

                    continue;
                }

                $this->line(
                    "Header row: {$headerRow}"
                );

                /*
                 * Cari posisi kolom.
                 */
                $columns = $this->detectColumns(
                    $sheet,
                    $headerRow,
                    $highestColumnIndex
                );

                $this->line(
                    'Kolom nama    : ' .
                    ($columns['nama'] ?? '-')
                );

                $this->line(
                    'Kolom NIP     : ' .
                    ($columns['nip'] ?? '-')
                );

                $this->line(
                    'Kolom pangkat : ' .
                    ($columns['pangkat'] ?? '-')
                );

                $this->line(
                    'Kolom jabatan : ' .
                    ($columns['jabatan'] ?? '-')
                );

                $this->line(
                    'Kolom unit    : ' .
                    ($columns['unit'] ?? '-')
                );

                $this->newLine();

                /*
                 * Kalau NIP atau nama tidak ditemukan,
                 * sheet tidak aman untuk diproses.
                 */
                if (
                    !isset($columns['nama']) ||
                    !isset($columns['nip'])
                ) {
                    $this->warn(
                        'Kolom Nama/NIP tidak ditemukan. Sheet dilewati.'
                    );

                    $this->newLine();

                    continue;
                }

                /*
                 * Preview beberapa ASN pertama
                 * yang memiliki riwayat pelatihan.
                 */
                $previewCount = 0;

                for (
                    $row = $headerRow + 1;
                    $row <= $highestRow;
                    $row++
                ) {

                    $nama = $this->getCellValue(
                        $sheet,
                        $columns['nama'],
                        $row
                    );

                    $nip = $this->getCellValue(
                        $sheet,
                        $columns['nip'],
                        $row
                    );

                    /*
                     * Lewati baris kosong.
                     */
                    if (
                        trim($nama) === '' &&
                        trim($nip) === ''
                    ) {
                        continue;
                    }

                    /*
                     * Tampilkan maksimal 5 ASN per sheet
                     * agar terminal tidak terlalu penuh.
                     */
                    if ($previewCount < 5) {

                        $this->line(
                            "ASN #" . ($previewCount + 1)
                        );

                        $this->line(
                            "Nama : {$nama}"
                        );

                        $this->line(
                            "NIP  : {$nip}"
                        );

                        /*
                         * Ambil semua kolom setelah data identitas
                         * dan cari isi pelatihan.
                         */
                        $trainingFound = [];

                        for (
                            $column = 7;
                            $column <= $highestColumnIndex;
                            $column++
                        ) {

                            /*
                             * PERBAIKAN PENTING:
                             *
                             * Jangan menggunakan:
                             *
                             * getCellByColumnAndRow()
                             *
                             * karena tidak tersedia pada versi
                             * PhpSpreadsheet yang kita gunakan.
                             *
                             * Gunakan Coordinate + getCell().
                             */

                            $columnLetter =
                                Coordinate::stringFromColumnIndex(
                                    $column
                                );

                            $value = $sheet
                                ->getCell(
                                    $columnLetter . $row
                                )
                                ->getFormattedValue();

                            $value = trim((string) $value);

                            if ($value !== '') {

                                $trainingFound[] = [
                                    'kolom' => $columnLetter,
                                    'nilai' => $value,
                                ];
                            }
                        }

                        if (count($trainingFound) > 0) {

                            $this->line(
                                'Pelatihan/data ditemukan:'
                            );

                            foreach (
                                $trainingFound as $training
                            ) {

                                $this->line(
                                    '  ' .
                                    $training['kolom'] .
                                    ' = ' .
                                    $training['nilai']
                                );
                            }

                            $totalTraining +=
                                count($trainingFound);

                        } else {

                            $this->line(
                                'Pelatihan: Belum ada data'
                            );
                        }

                        $this->newLine();

                        $previewCount++;
                    }
                }

                $this->line(
                    '----------------------------------------'
                );

                $this->newLine();
            }

            /*
             * Ringkasan
             */
            $this->info(
                'Preview riwayat pelatihan selesai.'
            );

            $this->line(
                "Total data pelatihan yang terdeteksi pada preview: {$totalTraining}"
            );

            $this->newLine();

            $this->info(
                'Belum ada data yang dimasukkan ke database.'
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {

            $this->newLine();

            $this->error(
                'Gagal membaca riwayat pelatihan.'
            );

            $this->error(
                'Tipe error: ' .
                get_class($e)
            );

            $this->error(
                'Pesan: ' .
                $e->getMessage()
            );

            $this->error(
                'File: ' .
                $e->getFile()
            );

            $this->error(
                'Baris: ' .
                $e->getLine()
            );

            return self::FAILURE;
        }
    }

    /**
     * Mendapatkan nilai cell berdasarkan huruf kolom.
     */
    private function getCellValue(
        $sheet,
        string $column,
        int $row
    ): string {

        if ($column === '') {
            return '';
        }

        $value = $sheet
            ->getCell($column . $row)
            ->getFormattedValue();

        return trim((string) $value);
    }

    /**
     * Mencari baris header ASN.
     */
    private function findHeaderRow($sheet): ?int
    {
        $maxRows = min(
            10,
            $sheet->getHighestRow()
        );

        for ($row = 1; $row <= $maxRows; $row++) {

            $foundNama = false;
            $foundNip = false;

            $highestColumnIndex =
                Coordinate::columnIndexFromString(
                    $sheet->getHighestColumn()
                );

            for (
                $column = 1;
                $column <= $highestColumnIndex;
                $column++
            ) {

                $columnLetter =
                    Coordinate::stringFromColumnIndex(
                        $column
                    );

                $value = strtolower(
                    trim(
                        (string) $sheet
                            ->getCell(
                                $columnLetter . $row
                            )
                            ->getFormattedValue()
                    )
                );

                if (
                    $value === 'nama' ||
                    str_contains($value, 'nama')
                ) {
                    $foundNama = true;
                }

                if (
                    $value === 'nip' ||
                    str_contains($value, 'nip')
                ) {
                    $foundNip = true;
                }
            }

            if ($foundNama && $foundNip) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Mendeteksi posisi kolom berdasarkan header.
     */
    private function detectColumns(
        $sheet,
        int $headerRow,
        int $highestColumnIndex
    ): array {

        $columns = [];

        for (
            $column = 1;
            $column <= $highestColumnIndex;
            $column++
        ) {

            $columnLetter =
                Coordinate::stringFromColumnIndex(
                    $column
                );

            $value = strtolower(
                trim(
                    (string) $sheet
                        ->getCell(
                            $columnLetter . $headerRow
                        )
                        ->getFormattedValue()
                )
            );

            if ($value === '') {
                continue;
            }

            /*
             * Nama
             */
            if (
                $value === 'nama' ||
                str_contains($value, 'nama')
            ) {
                $columns['nama'] =
                    $columnLetter;
            }

            /*
             * NIP
             */
            if (
                $value === 'nip' ||
                str_contains($value, 'nip')
            ) {
                $columns['nip'] =
                    $columnLetter;
            }

            /*
             * Pangkat
             */
            if (
                str_contains($value, 'pangkat') ||
                str_contains($value, 'golongan')
            ) {
                $columns['pangkat'] =
                    $columnLetter;
            }

            /*
             * Jabatan
             */
            if (
                str_contains($value, 'jabatan')
            ) {
                $columns['jabatan'] =
                    $columnLetter;
            }

            /*
             * Unit kerja
             */
            if (
                str_contains($value, 'unit kerja')
            ) {
                $columns['unit'] =
                    $columnLetter;
            }
        }

        return $columns;
    }
}