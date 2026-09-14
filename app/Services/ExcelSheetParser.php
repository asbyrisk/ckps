<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelSheetParser
{
    public function detectAsnColumns(
        Worksheet $sheet
    ): array {

        $headerRow = $this->detectHeaderRow($sheet);

        if (!$headerRow) {
            return [
                'header_row' => null,
                'name' => null,
                'nip' => null,
                'rank' => null,
                'position' => null,
                'unit' => null,
                'training' => null,
                'year' => null,
                'code' => null,
                'competency' => null,
            ];
        }

        $headers = $this->getHeaders(
            $sheet,
            $headerRow
        );

        return [
            'header_row' => $headerRow,

            'name' => $this->findColumn(
                $headers,
                ['nama']
            ),

            'nip' => $this->findColumn(
                $headers,
                ['nip']
            ),

            'rank' => $this->findColumn(
                $headers,
                [
                    'pangkat / golongan',
                    'pangkat/golongan',
                    'pangkat golongan'
                ]
            ),

            'position' => $this->findColumn(
                $headers,
                ['jabatan']
            ),

            'unit' => $this->findColumn(
                $headers,
                ['unit kerja']
            ),

            'training' => $this->findColumn(
                $headers,
                [
                    'pelatihan yang telah diikuti',
                    'pelatihan'
                ]
            ),

            'year' => $this->findColumn(
                $headers,
                ['tahun']
            ),

            'code' => $this->findColumn(
                $headers,
                [
                    'kode level standar kompetensi',
                    'kode kompetensi'
                ]
            ),

            'competency' => $this->findColumn(
                $headers,
                ['kompetensi teknis']
            ),
        ];
    }

    private function detectHeaderRow(
        Worksheet $sheet
    ): ?int {

        $maxRow = min(
            $sheet->getHighestRow(),
            10
        );

        for ($row = 1; $row <= $maxRow; $row++) {

            $headers = $this->getHeaders(
                $sheet,
                $row
            );

            $hasName =
                $this->findColumn(
                    $headers,
                    ['nama']
                ) !== null;

            $hasNip =
                $this->findColumn(
                    $headers,
                    ['nip']
                ) !== null;

            if ($hasName && $hasNip) {
                return $row;
            }
        }

        return null;
    }

    private function getHeaders(
        Worksheet $sheet,
        int $row
    ): array {

        $headers = [];

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

            $value = $this->normalize(
                (string) $value
            );

            if ($value === '') {
                continue;
            }

            $headers[$coordinate] = $value;
        }

        return $headers;
    }

    private function findColumn(
        array $headers,
        array $targets
    ): ?string {

        foreach ($headers as $column => $header) {

            foreach ($targets as $target) {

                if (
                    $header ===
                    $this->normalize($target)
                ) {
                    return $column;
                }
            }
        }

        return null;
    }

    private function normalize(
        string $value
    ): string {

        $value = trim($value);

        $value = preg_replace(
            '/\s+/',
            ' ',
            $value
        );

        return strtolower($value);
    }
}