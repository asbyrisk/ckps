<?php

namespace App\Services;

use App\Models\Asn;
use App\Models\AsnTraining;
use App\Models\Kompetensi;
use App\Models\ImportBatch;
use App\Models\Pelatihan;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelImporterService
{
    private $spreadsheet;
    private ImportBatch $batch;

    /**
     * Analisis aman sebelum menulis apa pun ke database.
     * Menghasilkan ringkasan sheet, sampel ASN, duplikasi NIP, dan peringatan.
     */
    public function preview(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheets = [];
        $warnings = [];
        $sampleAsns = [];
        $rowIssues = [];
        $allNips = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetName = trim((string) $sheet->getTitle());
            $highestRow = $sheet->getHighestRow();
            $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            $parser = new ExcelSheetParser();
            $columns = $parser->detectAsnColumns($sheet);
            $sheetNips = [];
            $invalidNips = [];
            $trainingHeaders = [];
            $rowsWithData = 0;

            for ($row = 1; $row <= $highestRow; $row++) {
                $rowHasData = false;
                for ($col = 1; $col <= min($highestCol, 30); $col++) {
                    $value = trim((string) $this->cellValue($sheet, $col, $row));
                    if ($value !== '') { $rowHasData = true; }

                    $nip = $this->cleanNip($value);
                    if ($this->isValidNip($nip)) {
                        $sheetNips[] = $nip;
                    } elseif ($value !== '' && preg_match('/^\d{15,19}$/', preg_replace('/\s+/', '', $value))) {
                        $invalidNips[] = $value;
                    }
                }
                if ($rowHasData) { $rowsWithData++; }
            }

            $sheetNips = array_values(array_unique($sheetNips));
            foreach ($sheetNips as $nip) { $allNips[] = $nip; }

            // Header pelatihan: cari kolom yang berada setelah identitas pada dua baris awal.
            for ($row = 1; $row <= min($highestRow, 5); $row++) {
                for ($col = 1; $col <= $highestCol; $col++) {
                    $value = $this->clean($this->cellValue($sheet, $col, $row));
                    if ($value !== '' && !$this->isGenericHeader($value) && !$this->isInvalidTrainingName($value)
                        && (str_contains(strtolower($value), 'pelatihan') || str_contains(strtolower($value), 'training'))) {
                        $trainingHeaders[] = $value;
                    }
                }
            }

            $type = strcasecmp($sheetName, 'STANDKOM') === 0 ? 'Master kompetensi' : ($this->isMatrixTrainingSheet($sheet) ? 'Matriks pelatihan' : 'Data ASN');

            $sheetWarnings = [];
            $sheetRowIssues = [];
            $sheetRowWarnings = [];

            // Laporan baris: jika header ASN dapat dideteksi, periksa setiap
            // baris data agar mentor bisa langsung tahu nomor baris yang harus
            // diperbaiki sebelum import. Ini hanya membaca file, belum menulis DB.
            if ($columns['name'] && $columns['nip']) {
                $nameCol = Coordinate::columnIndexFromString($columns['name']);
                $nipCol = Coordinate::columnIndexFromString($columns['nip']);
                $positionCol = $columns['position'] ? Coordinate::columnIndexFromString($columns['position']) : null;
                $unitCol = $columns['unit'] ? Coordinate::columnIndexFromString($columns['unit']) : null;
                $rankCol = $columns['rank'] ? Coordinate::columnIndexFromString($columns['rank']) : null;

                for ($dataRow = $columns['header_row'] + 1; $dataRow <= $highestRow; $dataRow++) {
                    $name = $this->clean($this->cellValue($sheet, $nameCol, $dataRow));
                    $rawNip = $this->clean($this->cellValue($sheet, $nipCol, $dataRow));
                    $nip = $this->cleanNip($rawNip);

                    // Abaikan baris kosong sepenuhnya.
                    if ($name === '' && $rawNip === '') {
                        continue;
                    }

                    $issues = [];
                    $warningsForRow = [];
                    if ($name === '') {
                        $issues[] = ['column' => 'Nama', 'value' => '', 'message' => 'Nama ASN kosong.'];
                    }
                    if (!$this->isValidNip($nip)) {
                        $issues[] = ['column' => 'NIP', 'value' => $rawNip, 'message' => 'NIP harus terdiri dari tepat 18 digit.'];
                    }

                    [$position, $unitName, $rank] = $this->readMasterIdentity($sheet, $dataRow, $positionCol, $unitCol, $rankCol);

                    // Pemeriksaan jabatan/unit/pangkat bersifat advisory.
                    // Jangan memblokir seluruh import hanya karena heuristik
                    // mendeteksi nilai yang terlihat mirip. Pembacaan master
                    // sendiri sudah memprioritaskan pola nilai D/E/F dan
                    // golongan seperti III/c atau IV/a.
                    if ($rank !== '' && !$this->looksLikeRank($rank)) {
                        $warningsForRow[] = ['column' => 'Pangkat/Golongan', 'value' => $rank, 'message' => 'Nilai pangkat/golongan perlu diperiksa karena tidak berbentuk pola seperti IV/a atau III/c.'];
                    }
                    if ($position !== '' && $this->looksLikeUnitName($position) && !$this->looksLikePositionName($position)) {
                        $warningsForRow[] = ['column' => 'Jabatan', 'value' => $position, 'message' => 'Nilai terlihat seperti Unit Kerja. Periksa preview sebelum konfirmasi.'];
                    }
                    if ($unitName !== '' && $this->looksLikePositionName($unitName) && !$this->looksLikeUnitName($unitName)) {
                        $warningsForRow[] = ['column' => 'Unit Kerja', 'value' => $unitName, 'message' => 'Nilai terlihat seperti Jabatan. Periksa preview sebelum konfirmasi.'];
                    }

                    if ($issues) {
                        $sheetRowIssues[] = [
                            'row' => $dataRow,
                            'name' => $name,
                            'nip' => $nip ?: $rawNip,
                            'severity' => 'error',
                            'issues' => $issues,
                        ];
                    }

                    // Batasi payload preview agar file besar tidak membuat session
                    // membengkak, tetapi tetap simpan jumlah total masalah.
                    if ($warningsForRow) {
                        $sheetRowWarnings[] = [
                            'row' => $dataRow,
                            'name' => $name,
                            'nip' => $nip ?: $rawNip,
                            'warnings' => $warningsForRow,
                        ];
                    }

                    if (count($sheetRowIssues) >= 200) {
                        break;
                    }
                }
            }

            if ($sheetRowIssues) {
                $sheetWarnings[] = count($sheetRowIssues) . ' baris ASN memiliki error Nama/NIP; lihat Laporan Validasi Baris di bawah.';
                $rowIssues = array_merge($rowIssues, array_map(function ($issue) use ($sheetName) {
                    $issue['sheet'] = $sheetName;
                    return $issue;
                }, $sheetRowIssues));
            }

            if ($sheetRowWarnings) {
                $sheetWarnings[] = count($sheetRowWarnings) . ' baris memiliki catatan pemeriksaan jabatan/pangkat/unit kerja. Catatan ini tidak memblokir import; periksa sampel sebelum konfirmasi.';
            }

            if (!$columns['name'] || !$columns['nip']) {
                if ($type === 'Data ASN') {
                    $sheetWarnings[] = 'Header Nama/NIP tidak terdeteksi; sheet ini akan diproses dengan aturan layout lama.';
                }
            }
            if (count($invalidNips) > 0) {
                $sheetWarnings[] = count($invalidNips) . ' nilai yang menyerupai NIP tetapi tidak berjumlah 18 digit.';
            }
            if ($type === 'Matriks pelatihan' && count($sheetNips) === 0) {
                $sheetWarnings[] = 'Tidak ditemukan NIP valid pada sheet matriks.';
            }
            if (count($trainingHeaders) > 0) {
                $trainingHeaders = array_values(array_unique($trainingHeaders));
            }

            // Sampel identitas hanya dari sheet dengan layout master yang dapat dibaca.
            if ($columns['name'] && $columns['nip'] && count($sampleAsns) < 10) {
                $nameCol = Coordinate::columnIndexFromString($columns['name']);
                $nipCol = Coordinate::columnIndexFromString($columns['nip']);
                $positionCol = $columns['position'] ? Coordinate::columnIndexFromString($columns['position']) : null;
                $unitCol = $columns['unit'] ? Coordinate::columnIndexFromString($columns['unit']) : null;
                $rankCol = $columns['rank'] ? Coordinate::columnIndexFromString($columns['rank']) : null;
                for ($row = $columns['header_row'] + 1; $row <= $highestRow && count($sampleAsns) < 10; $row++) {
                    $nip = $this->cleanNip($this->cellValue($sheet, $nipCol, $row));
                    if (!$this->isValidNip($nip)) continue;
                    [$position, $unitName, $rank] = $this->readMasterIdentity($sheet, $row, $positionCol, $unitCol, $rankCol);
                    $sampleAsns[] = [
                        'sheet' => $sheetName,
                        'name' => $this->clean($this->cellValue($sheet, $nameCol, $row)),
                        'nip' => $nip,
                        'position' => $position,
                        'rank' => $rank,
                        'unit' => $unitName,
                    ];
                }
            }

            $sheets[] = [
                'name' => $sheetName,
                'type' => $type,
                'rows' => $rowsWithData,
                'asn_count' => count($sheetNips),
                'training_headers' => array_slice($trainingHeaders, 0, 12),
                'warnings' => $sheetWarnings,
            ];
            foreach ($sheetWarnings as $warning) {
                $warnings[] = "{$sheetName}: {$warning}";
            }
        }

        $nipCounts = array_count_values($allNips);
        $duplicateNips = array_keys(array_filter($nipCounts, fn ($count) => $count > 1));
        if ($duplicateNips) {
            $warnings[] = count($duplicateNips) . ' NIP muncul di lebih dari satu sheet. Import akan memperbarui ASN yang sama berdasarkan NIP.';
        }

        $rankTooLong = collect($sampleAsns)->filter(fn ($asn) => mb_strlen($asn['rank']) > 50)->count();
        if ($rankTooLong > 0) {
            $warnings[] = 'Ada pangkat/golongan pada sampel yang melebihi 50 karakter.';
        }

        /*
         * Guard import: kondisi yang sangat berisiko tidak boleh diteruskan
         * hanya dengan mengandalkan tombol konfirmasi. Warning tetap boleh
         * dikonfirmasi, tetapi blocking_errors harus diperbaiki terlebih dahulu.
         */
        $blockingErrors = [];

        // Jika laporan baris menemukan error, jadikan blocking. Dengan begitu
        // tombol konfirmasi tidak dapat melewati validasi hanya karena preview
        // tingkat sheet terlihat baik.
        if (!empty($rowIssues)) {
            $blockingErrors[] = count($rowIssues) . ' baris ASN memiliki error validasi. Perbaiki baris yang ditampilkan sebelum konfirmasi import.';
        }

        $asnSheets = collect($sheets)->where('type', 'Data ASN');

        if ($asnSheets->isNotEmpty()) {
            $withoutIdentity = $asnSheets->filter(
                fn ($sheet) => ($sheet['asn_count'] ?? 0) === 0
            )->count();

            if ($withoutIdentity === $asnSheets->count()) {
                $blockingErrors[] = 'Tidak ada NIP ASN valid yang ditemukan pada sheet Data ASN. Import dibatalkan agar database tidak berubah karena file salah struktur.';
            }
        }

        if (count($sampleAsns) > 0) {
            $badRankSamples = collect($sampleAsns)->filter(function ($asn) {
                $rank = trim((string) ($asn['rank'] ?? ''));
                return $rank !== '' && !$this->looksLikeRank($rank);
            })->count();

            if ($badRankSamples > 0) {
                $warnings[] = "Terdapat {$badRankSamples} sampel pangkat/golongan yang perlu diperiksa. Pola yang diharapkan seperti IV/a atau III/c.";
            }

            $positionLooksLikeUnit = collect($sampleAsns)->filter(function ($asn) {
                $position = strtolower(trim((string) ($asn['position'] ?? '')));
                return $position !== '' && $this->looksLikeUnitName($position) && !$this->looksLikePositionName($position);
            })->count();

            $unitLooksLikePosition = collect($sampleAsns)->filter(function ($asn) {
                $unit = strtolower(trim((string) ($asn['unit'] ?? '')));
                return $unit !== '' && $this->looksLikePositionName($unit) && !$this->looksLikeUnitName($unit);
            })->count();

            if ($positionLooksLikeUnit > 0 || $unitLooksLikePosition > 0) {
                $warnings[] = 'Sistem mendeteksi kemungkinan Jabatan dan Unit Kerja tertukar pada sebagian sampel. Periksa preview sebelum konfirmasi.';
            }
        }

        return [
            'filename' => basename($path),
            'total_sheets' => $spreadsheet->getSheetCount(),
            'total_rows' => array_sum(array_column($sheets, 'rows')),
            'unique_nips' => count(array_unique($allNips)),
            'duplicate_nips' => $duplicateNips,
            'sheets' => $sheets,
            'sample_asns' => $sampleAsns,
            'row_issues' => array_slice($rowIssues, 0, 200),
            'row_issue_count' => count($rowIssues),
            'warnings' => array_values(array_unique($warnings)),
            'blocking_errors' => array_values(array_unique($blockingErrors)),
            'can_confirm' => empty($blockingErrors),
        ];
    }

    public function import(string $path): ImportBatch
    {
        return DB::transaction(function () use ($path) {
            $this->spreadsheet = IOFactory::load($path);

            $this->batch = ImportBatch::create([
                'filename' => basename($path),
                'total_sheets' => $this->spreadsheet->getSheetCount(),
                'status' => 'processing',
            ]);

            // Bersihkan artefak import lama seperti nama pelatihan "1", "10", "11", dll.
            // Angka tersebut adalah penanda di sheet matriks, bukan nama pelatihan.
            $this->cleanupCorruptedImportArtifacts();

            foreach ($this->spreadsheet->getWorksheetIterator() as $sheet) {
                $sheetName = trim((string) $sheet->getTitle());

                if (strcasecmp($sheetName, 'STANDKOM') === 0) {
                    $this->processStandkom($sheet);
                    continue;
                }

                if ($this->isMatrixTrainingSheet($sheet)) {
                    $this->processMatrixSheet($sheet);
                    continue;
                }

                $this->processAsnSheet($sheet);
            }

            // Buat kode internal yang stabil bila Excel tidak menyediakan kode resmi.
            $this->backfillTrainingCodes();

            // Sinkronkan relasi kompetensi <-> pelatihan menggunakan nama pelatihan
            // yang benar-benar ada di master trainings.
            $this->syncCompetencyTrainingRelations();

            $this->batch->update([
                'status' => 'completed',
                'total_asns' => Asn::count(),
                'total_trainings' => Pelatihan::count(),
                'total_competencies' => Kompetensi::count(),
            ]);

            return $this->batch;
        });
    }

    private function cleanupCorruptedImportArtifacts(): void
    {
        $badIds = Pelatihan::query()
            ->get(['id', 'name'])
            ->filter(function ($training) {
                $name = trim((string) $training->name);
                return preg_match('/^\d+$/', $name) === 1;
            })
            ->pluck('id');

        if ($badIds->isNotEmpty()) {
            DB::table('asn_trainings')->whereIn('training_id', $badIds)->delete();
            if (Schema::hasTable('training_kompetensi')) {
                DB::table('training_kompetensi')->whereIn('training_id', $badIds)->delete();
            }
            // `kompetensi_pelatihan` adalah pivot legacy yang merujuk ke tabel
            // `pelatihan`; jangan disentuh dari importer baru.
            Pelatihan::whereIn('id', $badIds)->delete();
        }

        // Import versi lama membaca rumus Excel sebagai teks literal, sehingga
        // kompetensi sempat tersimpan sebagai "=VLOOKUP(...)". Hapus artefak
        // tersebut sebelum import ulang agar master kompetensi tidak dobel.
        $badCompetencyIds = Kompetensi::query()
            ->get(['id', 'kode', 'nama'])
            ->filter(function ($competency) {
                $kode = trim((string) $competency->kode);
                $nama = trim((string) $competency->nama);

                return str_starts_with($kode, '=')
                    || str_starts_with($nama, '=')
                    || strcasecmp($kode, '#N/A') === 0
                    || strcasecmp($nama, '#N/A') === 0;
            })
            ->pluck('id');

        if ($badCompetencyIds->isNotEmpty()) {
            if (Schema::hasTable('asn_kompetensi')) {
                DB::table('asn_kompetensi')
                    ->whereIn('kompetensi_id', $badCompetencyIds)
                    ->delete();
            }

            if (Schema::hasTable('training_kompetensi')) {
                DB::table('training_kompetensi')
                    ->whereIn('kompetensi_id', $badCompetencyIds)
                    ->delete();
            }

            Kompetensi::whereIn('id', $badCompetencyIds)->delete();
        }
    }

    private function processStandkom(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = 4; $row <= $highestRow; $row++) {
            $code = $this->clean($this->cellValue($sheet, 6, $row));
            $name = $this->clean($this->cellValue($sheet, 7, $row));
            $level = $this->clean($this->cellValue($sheet, 3, $row));
            $training = $this->clean($this->cellValue($sheet, 5, $row));

            if ($code === '' || $name === '') {
                continue;
            }

            Kompetensi::updateOrCreate(
                ['kode' => $code],
                [
                    'nama' => $name,
                    'level' => is_numeric($level) ? (int) $level : null,
                    'pelatihan_eksisting' => $training !== '' ? $training : null,
                ]
            );
        }

        // STANDKOM menyimpan master pelatihan pada blok M:Z.
        // Kolom L berisi nomor 1, 2, 3, ... dan TIDAK boleh dipakai sebagai nama pelatihan.
        for ($row = 6; $row <= $highestRow; $row++) {
            for ($col = 13; $col <= 26; $col++) {
                $masterTraining = $this->clean($this->cellValue($sheet, $col, $row));

                if ($masterTraining !== '' && !$this->isInvalidTrainingName($masterTraining) && !$this->isGenericHeader($masterTraining)) {
                    $this->saveTrainingMaster($masterTraining);
                }
            }
        }
    }

    private function isMatrixTrainingSheet(Worksheet $sheet): bool
    {
        $maxRow = min($sheet->getHighestRow(), 8);
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($row = 1; $row <= $maxRow; $row++) {
            for ($col = 1; $col <= min($maxCol, 10); $col++) {
                $value = strtolower($this->clean($this->cellValue($sheet, $col, $row)));
                if ($value === 'pelatihan' || str_contains($value, 'pelatihan bgkp')) {
                    $nextRow = $row + 1;
                    if ($nextRow > $sheet->getHighestRow()) {
                        continue;
                    }

                    $nonEmpty = 0;
                    for ($c = $col; $c <= $maxCol; $c++) {
                        $header = $this->clean($this->cellValue($sheet, $c, $nextRow));
                        if ($header !== '' && !$this->isGenericHeader($header)) {
                            $nonEmpty++;
                        }
                    }

                    if ($nonEmpty >= 3) {
                        return true;
                    }
                }
            }
        }

        // Format matrix langsung: baris 2 berisi identitas lalu nama-nama pelatihan
        // mulai kolom G/H, tanpa header grup 'Pelatihan'. Contoh: Dir Air Minum.
        if ($sheet->getHighestRow() >= 3) {
            $nameHeader = strtolower($this->clean($this->cellValue($sheet, 2, 2)));
            $nipHeader = strtolower($this->clean($this->cellValue($sheet, 3, 2)));
            $trainingHeaderCount = 0;

            if ($nameHeader === 'nama' && $nipHeader === 'nip') {
                for ($c = 7; $c <= $maxCol; $c++) {
                    $header = $this->clean($this->cellValue($sheet, $c, 2));
                    if ($header !== '' && !$this->isGenericHeader($header) && !$this->isInvalidTrainingName($header)
                        && !str_contains(strtolower($header), 'pangkat/golongan')
                        && !str_contains(strtolower($header), 'pangkat / golongan')
                    ) {
                        $trainingHeaderCount++;
                    }
                }

                if ($trainingHeaderCount >= 10) {
                    return true;
                }
            }
        }

        // Format DSSPI: baris 1 = identitas + Pelatihan, baris 2 = nama training.
        if ($sheet->getHighestRow() >= 3) {
            $row1Training = [];
            $row2Training = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                $v1 = strtolower($this->clean($this->cellValue($sheet, $c, 1)));
                $v2 = $this->clean($this->cellValue($sheet, $c, 2));
                if (str_contains($v1, 'pelatihan')) {
                    $row1Training[] = $c;
                }
                if ($v2 !== '') {
                    $row2Training[] = $v2;
                }
            }
            if ($row1Training && count($row2Training) >= 3) {
                return true;
            }
        }

        return false;
    }

    private function processMatrixSheet(Worksheet $sheet): void
    {
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $trainingHeaderRow = null;
        $trainingStartColumn = null;
        $dataStartRow = null;

        // Pola 1: row X berisi "Pelatihan", row X+1 berisi nama-nama training.
        for ($row = 1; $row <= min($sheet->getHighestRow(), 8); $row++) {
            for ($col = 1; $col <= $maxCol; $col++) {
                $value = strtolower($this->clean($this->cellValue($sheet, $col, $row)));
                if ($value !== 'pelatihan' && !str_contains($value, 'pelatihan bgkp')) {
                    continue;
                }

                $nextRow = $row + 1;
                $candidateCount = 0;
                $candidateStart = null;
                for ($c = $col; $c <= $maxCol; $c++) {
                    $header = $this->clean($this->cellValue($sheet, $c, $nextRow));
                    if ($header !== '' && !$this->isGenericHeader($header)) {
                        $candidateCount++;
                        $candidateStart ??= $c;
                    }
                }
                if ($candidateCount >= 3) {
                    $trainingHeaderRow = $nextRow;
                    $trainingStartColumn = $candidateStart;
                    $dataStartRow = $nextRow + 1;
                    break 2;
                }
            }
        }

        // Pola matrix langsung: row 2 = identitas + nama training.
        if ($trainingHeaderRow === null && $sheet->getHighestRow() >= 3) {
            $nameHeader = strtolower($this->clean($this->cellValue($sheet, 2, 2)));
            $nipHeader = strtolower($this->clean($this->cellValue($sheet, 3, 2)));

            if ($nameHeader === 'nama' && $nipHeader === 'nip') {
                $candidateStart = null;
                $candidateCount = 0;

                for ($c = 7; $c <= $maxCol; $c++) {
                    $header = $this->clean($this->cellValue($sheet, $c, 2));
                    if ($header === '' || $this->isGenericHeader($header) || $this->isInvalidTrainingName($header)) {
                        continue;
                    }

                    $candidateStart ??= $c;
                    $candidateCount++;
                }

                if ($candidateCount >= 10 && $candidateStart !== null) {
                    $trainingHeaderRow = 2;
                    $trainingStartColumn = $candidateStart;
                    $dataStartRow = 3;
                }
            }
        }

        // Pola DSSPI: row 1 = Pelatihan, row 2 = nama training.
        if ($trainingHeaderRow === null) {
            for ($col = 1; $col <= $maxCol; $col++) {
                $v = strtolower($this->clean($this->cellValue($sheet, $col, 1)));
                if (str_contains($v, 'pelatihan')) {
                    $count = 0;
                    for ($c = $col; $c <= $maxCol; $c++) {
                        $h = $this->clean($this->cellValue($sheet, $c, 2));
                        if ($h !== '') $count++;
                    }
                    if ($count >= 3) {
                        $trainingHeaderRow = 2;
                        $trainingStartColumn = $col;
                        $dataStartRow = 3;
                        break;
                    }
                }
            }
        }

        if ($trainingHeaderRow === null || $trainingStartColumn === null || $dataStartRow === null) {
            return;
        }

        $trainingColumns = [];
        for ($col = $trainingStartColumn; $col <= $maxCol; $col++) {
            $trainingName = $this->clean($this->cellValue($sheet, $col, $trainingHeaderRow));
            if ($trainingName === '' || $this->isGenericHeader($trainingName) || $this->isInvalidTrainingName($trainingName)) {
                continue;
            }
            $trainingColumns[$col] = $trainingName;
            $this->saveTrainingMaster($trainingName);
        }

        $nameColumn = null;
        $nipColumn = null;
        $positionColumn = null;
        $rankColumn = null;
        $unitColumn = null;

        // Cari identitas pada dua baris awal.
        for ($row = 1; $row <= min($trainingHeaderRow, 3); $row++) {
            for ($col = 1; $col < $trainingStartColumn; $col++) {
                $header = strtolower($this->clean($this->cellValue($sheet, $col, $row)));
                if ($header === 'nama' && !$nameColumn) $nameColumn = $col;
                if ($header === 'nip' && !$nipColumn) $nipColumn = $col;
                if (str_contains($header, 'jabatan') && !$positionColumn) $positionColumn = $col;
                if (str_contains($header, 'pangkat') && !$rankColumn) $rankColumn = $col;
                if (str_contains($header, 'unit kerja') && !$unitColumn) $unitColumn = $col;
            }
        }

        if (!$nameColumn) $nameColumn = 2;
        if (!$nipColumn) $nipColumn = 3;

        for ($row = $dataStartRow; $row <= $sheet->getHighestRow(); $row++) {
            $name = $this->clean($this->cellValue($sheet, $nameColumn, $row));
            $nip = $this->cleanNip($this->cellValue($sheet, $nipColumn, $row));

            if ($name === '' || !$this->isValidNip($nip)) {
                continue;
            }

            [$position, $unitName, $rank] = $this->readMasterIdentity(
                $sheet,
                $row,
                $positionColumn,
                $unitColumn,
                $rankColumn
            );

            $unit = $unitName !== '' ? Unit::firstOrCreate(['name' => $unitName]) : null;
            $asn = Asn::firstOrNew(['nip' => $nip]);
            $asn->name = $name;

            // Jangan menghapus data master yang sudah benar hanya karena
            // sheet tertentu tidak membawa kolom identitas lengkap.
            if ($position !== '') {
                $asn->position = $position;
            }
            if ($rank !== '') {
                $asn->rank = $rank;
            }
            if ($unit) {
                $asn->unit_id = $unit->id;
            }
            $asn->save();

            foreach ($trainingColumns as $col => $trainingName) {
                $marker = strtolower($this->clean($this->cellValue($sheet, $col, $row)));
                if ($marker === '' || in_array($marker, ['belum', 'tidak', '0', 'pns', '-'], true)) {
                    continue;
                }

                $training = $this->saveTrainingMaster($trainingName);
                AsnTraining::updateOrCreate(
                    [
                        'asn_id' => $asn->id,
                        'training_id' => $training->id,
                        'year' => $this->extractYear($marker),
                    ],
                    [
                        'status' => 'completed',
                        'source_sheet' => $sheet->getTitle(),
                    ]
                );
            }
        }
    }

    private function processAsnSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        $currentAsn = null;

        for ($row = 1; $row <= $highestRow; $row++) {
            $nip = $this->cleanNip($this->cellValue($sheet, 3, $row));
            $name = $this->clean($this->cellValue($sheet, 2, $row));

            if ($nip !== '' && $this->isValidNip($nip)) {
                $currentAsn = $this->findOrCreateAsn($sheet, $row);
            }

            if (!$currentAsn) continue;

            $training = $this->clean($this->cellValue($sheet, 8, $row));
            $year = $this->clean($this->cellValue($sheet, 9, $row));
            $code = $this->clean($this->cellValue($sheet, 10, $row));
            $competency = $this->clean($this->cellValue($sheet, 11, $row));

            if ($training !== '' && !$this->isInvalidTrainingName($training)) {
                $trainingCode = $this->findTrainingCode($sheet, $row);
                $this->saveTraining($currentAsn, $training, $year, $sheet->getTitle(), $trainingCode);
            }

            if ($code !== '' && strtolower($code) !== 'belum' && $code !== '#N/A' && $competency !== '' && strtolower($competency) !== 'belum' && $competency !== '#N/A') {
                $this->saveCompetency($currentAsn, $code, $competency, $sheet->getTitle());
            }
        }
    }

    private function findOrCreateAsn(Worksheet $sheet, int $row): Asn
    {
        $nip = $this->cleanNip($this->cellValue($sheet, 3, $row));
        $name = $this->clean($this->cellValue($sheet, 2, $row));
        [$position, $unitName, $rank] = $this->readMasterIdentity($sheet, $row);

        $unit = $unitName !== '' ? Unit::firstOrCreate(['name' => $unitName]) : null;

        $asn = Asn::firstOrNew(['nip' => $nip]);
        $asn->name = $name;

        // Hanya update kolom yang benar-benar ditemukan dari Excel.
        // Ini mencegah sheet pelatihan menimpa jabatan/unit/pangkat
        // dengan NULL atau nilai dari kolom yang salah.
        if ($position !== '') {
            $asn->position = $position;
        }
        if ($rank !== '') {
            $asn->rank = $rank;
        }
        if ($unit) {
            $asn->unit_id = $unit->id;
        }

        $asn->save();

        return $asn;
    }


    /**
     * Membaca master ASN dari format Excel yang dipakai BPBPK.
     *
     * Format yang harus diprioritaskan:
     * B = Nama, C = NIP, D = Jabatan (meskipun header tertulis
     * 'Pangkat / Golongan'), E = Unit Kerja, F = Golongan.
     *
     * Nilai aktual lebih dipercaya daripada nama header, sehingga jabatan
     * panjang tidak pernah masuk ke kolom rank.
     */
    private function readMasterIdentity(
        Worksheet $sheet,
        int $row,
        ?int $positionColumn = null,
        ?int $unitColumn = null,
        ?int $rankColumn = null
    ): array {
        /*
         * File sumber mentor ternyata memiliki beberapa layout:
         *
         * 1) D=Jabatan, E=Unit Kerja, F=Golongan
         * 2) D=Pangkat/Golongan, E=Jabatan, F=Unit Kerja
         * 3) D=Pangkat/Golongan, E=Unit Kerja, F=Golongan
         * 4) D=Jabatan, E=Unit Kerja, F=Pangkat/Golongan
         *
         * Karena beberapa sheet memakai header "Unit Kerja" dua kali,
         * posisi header saja tidak cukup. Nilai D/E/F juga diperiksa.
         */
        $values = [
            4 => $this->clean($this->cellValue($sheet, 4, $row)),
            5 => $this->clean($this->cellValue($sheet, 5, $row)),
            6 => $this->clean($this->cellValue($sheet, 6, $row)),
        ];

        $rank = '';
        $rankSource = null;

        // Prioritas pertama: nilai yang jelas-jelas merupakan golongan ASN.
        foreach ($values as $column => $value) {
            if ($this->looksLikeRank($value)) {
                $rank = $this->normalizeRank($value);
                $rankSource = $column;
                break;
            }
        }

        // Jika tidak ditemukan dari D/E/F, coba kolom yang terdeteksi header pangkat.
        if ($rank === '' && $rankColumn) {
            $candidate = $this->clean($this->cellValue($sheet, $rankColumn, $row));
            if ($this->looksLikeRank($candidate)) {
                $rank = $this->normalizeRank($candidate);
                $rankSource = $rankColumn;
            }
        }

        $remaining = [];
        foreach ($values as $column => $value) {
            if ($column === $rankSource || $value === '') {
                continue;
            }
            $remaining[$column] = $value;
        }

        // Jika header eksplisit "Jabatan" menunjuk ke nilai non-rank,
        // gunakan itu terlebih dahulu.
        $position = '';
        if ($positionColumn) {
            $candidate = $this->clean($this->cellValue($sheet, $positionColumn, $row));
            if ($candidate !== '' && !$this->looksLikeRank($candidate)) {
                $position = $candidate;
                unset($remaining[$positionColumn]);
            }
        }

        // Jika header "Unit Kerja" tunggal menunjuk ke nilai non-rank,
        // gunakan sebagai unit. Jangan percaya jika nilainya ternyata jabatan.
        $unitName = '';
        if ($unitColumn) {
            $candidate = $this->clean($this->cellValue($sheet, $unitColumn, $row));
            if (
                $candidate !== '' &&
                !$this->looksLikeRank($candidate) &&
                !$this->looksLikePositionName($candidate)
            ) {
                $unitName = $candidate;
                unset($remaining[$unitColumn]);
            }
        }

        // Ambil sisa dua nilai dengan heuristik isi.
        foreach ($remaining as $column => $value) {
            if ($value === '') {
                continue;
            }

            if ($unitName === '' && $this->looksLikeUnitName($value)) {
                $unitName = $value;
                unset($remaining[$column]);
                continue;
            }

            if ($position === '' && $this->looksLikePositionName($value)) {
                $position = $value;
                unset($remaining[$column]);
            }
        }

        // Jika hanya tersisa satu nilai dan belum bisa diklasifikasikan,
        // gunakan konteks header sebagai fallback.
        foreach ($remaining as $column => $value) {
            if ($position === '' && $column === 4) {
                $position = $value;
                continue;
            }
            if ($unitName === '') {
                $unitName = $value;
            } elseif ($position === '') {
                $position = $value;
            }
        }

        // Format lama yang memiliki kolom eksplisit tetap didukung.
        if ($position === '' && $positionColumn) {
            $candidate = $this->clean($this->cellValue($sheet, $positionColumn, $row));
            if ($candidate !== '' && !$this->looksLikeRank($candidate)) {
                $position = $candidate;
            }
        }

        if ($unitName === '' && $unitColumn) {
            $candidate = $this->clean($this->cellValue($sheet, $unitColumn, $row));
            if ($candidate !== '' && !$this->looksLikeRank($candidate)) {
                $unitName = $candidate;
            }
        }

        return [$position, $unitName, $rank];
    }

    private function looksLikeUnitName(string $value): bool
    {
        $value = strtolower(trim($value));
        if ($value === '' || $this->looksLikeRank($value)) {
            return false;
        }

        $keywords = [
            'balai',
            'direktorat',
            'sekretariat',
            'biro',
            'pusat',
            'badan',
            'kementerian',
            'kantor',
            'wilayah',
            'provinsi',
            'kabupaten',
            'kota',
            'bpbpk',
            'd.i.',
            'jawa barat',
            'jawa tengah',
            'jawa timur',
            'sumatera',
            'sumatra',
            'kalimantan',
            'sulawesi',
            'maluku',
            'papua',
            'nusa tenggara',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return true;
            }
        }

        // Struktur organisasi biasanya lebih panjang daripada nama jabatan.
        return str_contains($value, ',') &&
            (str_contains($value, 'penataan') || str_contains($value, 'bangunan'));
    }

    private function looksLikePositionName(string $value): bool
    {
        $value = strtolower(trim($value));
        if ($value === '' || $this->looksLikeRank($value)) {
            return false;
        }

        $keywords = [
            'ahli',
            'terampil',
            'penata',
            'pengelola',
            'perekayasa',
            'analis',
            'pranata',
            'kepala',
            'direktur',
            'sekretaris',
            'fungsional',
            'pelaksana',
            'administrator',
            'pengawas',
            'teknisi',
            'operator',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($value, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function saveTraining(Asn $asn, string $name, string $year, string $sourceSheet, ?string $code = null): void
    {
        if ($this->isInvalidTrainingName($name)) return;

        $training = $this->saveTrainingMaster($name, $code);
        $yearValue = $this->extractYear($year);

        AsnTraining::updateOrCreate(
            [
                'asn_id' => $asn->id,
                'training_id' => $training->id,
                'year' => $yearValue,
            ],
            [
                'status' => 'completed',
                'source_sheet' => $sourceSheet,
            ]
        );
    }

    private function saveTrainingMaster(string $name, ?string $code = null): Pelatihan
    {
        $name = $this->clean($name);
        $code = $code !== null ? $this->clean($code) : '';

        if ($this->isInvalidTrainingName($name)) {
            throw new \InvalidArgumentException('Nama pelatihan tidak valid.');
        }

        // Cari tanpa membedakan huruf besar/kecil agar import ulang tidak membuat duplikat.
        $training = Pelatihan::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$name])
            ->first();

        if (!$training) {
            $training = new Pelatihan();
            $training->name = $name;
        }

        if ($code !== '') {
            $training->code = $code;
        }

        $training->save();

        return $training;
    }

    private function backfillTrainingCodes(): void
    {
        $nextNumber = (int) Pelatihan::query()
            ->where('code', 'like', 'PLT-%')
            ->pluck('code')
            ->map(function ($code) {
                return preg_match('/^PLT-(\d+)$/i', (string) $code, $m) ? (int) $m[1] : 0;
            })
            ->max();

        Pelatihan::query()
            ->where(function ($query) {
                $query->whereNull('code')->orWhere('code', '');
            })
            ->orderBy('id')
            ->each(function (Pelatihan $training) use (&$nextNumber) {
                do {
                    $nextNumber++;
                    $code = 'PLT-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
                } while (Pelatihan::query()->where('code', $code)->exists());

                $training->forceFill(['code' => $code])->save();
            });
    }

    private function findTrainingCode(Worksheet $sheet, int $row): ?string
    {
        // Mendukung format Excel baru tanpa merusak format lama.
        foreach (['L', 'G'] as $column) {
            $value = $this->clean($this->cellValue($sheet, Coordinate::columnIndexFromString($column), $row));
            if ($value !== '' && preg_match('/^(PL|PEL|TR|TRAIN)[-_ ]?[A-Z0-9]+$/i', $value)) {
                return $value;
            }
        }
        return null;
    }

    private function saveCompetency(Asn $asn, string $code, string $name, string $source): void
    {
        $competency = Kompetensi::updateOrCreate(
            ['kode' => $code],
            ['nama' => $name]
        );

        // Gunakan skema lama yang memang menyimpan kompetensi pada tabel
        // `kompetensi` + pivot `asn_kompetensi`. Ini menghindari konflik FK
        // dengan tabel duplikat `competencies`/`asn_competencies` dari versi lama.
        if (Schema::hasTable('asn_kompetensi')) {
            DB::table('asn_kompetensi')->updateOrInsert(
                [
                    'asn_id' => $asn->id,
                    'kompetensi_id' => $competency->id,
                ],
                [
                    'sumber' => $source,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function syncCompetencyTrainingRelations(): void
    {
        // Aplikasi saat ini menggunakan pivot `training_kompetensi`
        // (trainings.id <-> kompetensi.id). Jangan menulis ke
        // `kompetensi_pelatihan` karena tabel lama tersebut merujuk ke
        // tabel `pelatihan` yang berbeda dan akan memicu FK 1452.
        if (!Schema::hasTable('training_kompetensi')) {
            throw new \RuntimeException(
                'Tabel training_kompetensi belum tersedia. Jalankan php artisan migrate terlebih dahulu.'
            );
        }

        $trainings = Pelatihan::query()
            ->get(['id', 'name'])
            ->keyBy(fn (Pelatihan $training) => $this->normalizeTrainingKey($training->name));

        if ($trainings->isEmpty()) {
            return;
        }

        Kompetensi::query()
            ->whereNotNull('pelatihan_eksisting')
            ->where('pelatihan_eksisting', '!=', '')
            ->get(['id', 'pelatihan_eksisting'])
            ->each(function (Kompetensi $competency) use ($trainings) {
                $names = preg_split('/[\n;|]+/u', (string) $competency->pelatihan_eksisting);

                foreach ($names as $name) {
                    $key = $this->normalizeTrainingKey($name);
                    if ($key === '' || !$trainings->has($key)) {
                        continue;
                    }

                    $training = $trainings->get($key);

                    DB::table('training_kompetensi')->updateOrInsert(
                        [
                            'kompetensi_id' => $competency->id,
                            'training_id' => $training->id,
                        ],
                        [
                            'wajib' => false,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            });
    }

    private function normalizeTrainingKey(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return mb_strtolower($value);
    }

    private function extractYear(string $value): ?int
    {
        if (preg_match('/\b(19|20)\d{2}\b/', $value, $matches)) {
            return (int) $matches[0];
        }
        return null;
    }

    /**
     * PhpSpreadsheet 2.x removed Worksheet::getCellByColumnAndRow().
     * Convert numeric column indexes to A1 notation and read the cell safely.
     */
    private function cellValue(Worksheet $sheet, int $column, int $row): mixed
    {
        $coordinate = Coordinate::stringFromColumnIndex($column) . $row;
        $cell = $sheet->getCell($coordinate);

        // getValue() mengembalikan rumus mentah seperti =VLOOKUP(...).
        // Untuk master kompetensi kita membutuhkan hasil rumusnya.
        if ($cell->isFormula()) {
            try {
                return $cell->getCalculatedValue();
            } catch (\Throwable $e) {
                // Jika engine kalkulasi tidak mampu menghitung rumus tertentu,
                // gunakan nilai cache Excel bila tersedia. Jangan menyimpan
                // string rumus ke database.
                $calculated = $cell->getOldCalculatedValue();
                if ($calculated !== null && $calculated !== '') {
                    return $calculated;
                }

                return '';
            }
        }

        return $cell->getValue();
    }

    private function cleanNip($value): string
    {
        if ($value === null) return '';
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', '', $value);
        if (preg_match('/^(\d+)\.0$/', $value, $m)) $value = $m[1];
        if (stripos($value, 'E+') !== false && is_numeric($value)) $value = sprintf('%.0f', (float) $value);
        return preg_replace('/[^0-9]/', '', $value);
    }

    private function isValidNip(string $nip): bool
    {
        return preg_match('/^\d{18}$/', $nip) === 1;
    }

    private function isInvalidTrainingName(string $value): bool
    {
        $lower = strtolower(trim($value));
        if ($lower === '' || preg_match('/^\d+$/', $lower)) return true;
        return in_array($lower, [
            'belum', 'belum ada', 'belum ada data', 'belum terdapat pelatihan terkait',
            'sudah', 'tidak', 'tidak ada', 'tidak tersedia', 'n/a', '#n/a', '-', 'ya', 'pns'
        ], true);
    }

    private function isGenericHeader(string $value): bool
    {
        return in_array(strtolower(trim($value)), [
            'tahun', 'no', 'nama', 'nip', 'jabatan', 'unit kerja', 'pangkat / golongan', 'pelatihan', 'kompetensi teknis', 'kode kompetensi'
        ], true);
    }

    private function clean($value): string
    {
        if ($value === null) return '';
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        return strtolower($value) === '#n/a' ? '' : $value;
    }


    private function normalizeRank(string $value): string
    {
        $value = $this->clean($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/\b(I|II|III|IV)\s*[\/\-]\s*([a-e])\b/i', $value, $match)) {
            return strtoupper($match[1]) . '/' . strtolower($match[2]);
        }

        return '';
    }

    private function looksLikeRank(string $value): bool
    {
        return preg_match('/(?:^|[^A-Z0-9])(I|II|III|IV)\s*[\/\-]\s*[a-e](?:$|[^A-Z0-9])/i', trim($value)) === 1;
    }
}
