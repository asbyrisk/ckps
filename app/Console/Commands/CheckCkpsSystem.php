<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckCkpsSystem extends Command
{
    protected $signature = 'ckps:check';
    protected $description = 'Memeriksa tabel inti CKPS Mapping dan relasi import.';

    public function handle(): int
    {
        $tables = [
            'users', 'asns', 'units', 'trainings', 'kompetensi',
            'asn_trainings', 'asn_kompetensi', 'training_kompetensi',
            'asn_unit_histories', 'import_batches', 'import_previews', 'audit_logs',
        ];

        $failed = false;

        $this->info('CKPS Mapping - pemeriksaan struktur database');
        $this->newLine();

        foreach ($tables as $table) {
            $ok = Schema::hasTable($table);
            $this->line(($ok ? '<info>OK</info>   ' : '<error>FAIL</error> ') . $table);
            $failed = $failed || !$ok;
        }

        $columns = [
            'asns' => ['nip', 'name', 'position', 'rank', 'unit_id'],
            'asn_unit_histories' => ['asn_id', 'from_unit_id', 'to_unit_id', 'effective_date', 'status'],
            'audit_logs' => ['user_id', 'action', 'description', 'old_values', 'new_values'],
        ];

        $this->newLine();
        $this->info('Pemeriksaan kolom penting');

        foreach ($columns as $table => $requiredColumns) {
            foreach ($requiredColumns as $column) {
                $ok = Schema::hasColumn($table, $column);
                $this->line(($ok ? '<info>OK</info>   ' : '<error>FAIL</error> ') . $table . '.' . $column);
                $failed = $failed || !$ok;
            }
        }

        if (!$failed) {
            $this->newLine();
            $this->info('CKPS Mapping: struktur database inti tersedia dan kolom penting lengkap.');
            $this->line('Database: ' . DB::connection()->getDatabaseName());
            $this->line('ASN: ' . DB::table('asns')->count());
            $this->line('Unit kerja: ' . DB::table('units')->count());
            $this->line('Pelatihan: ' . DB::table('trainings')->count());
            $this->line('Kompetensi: ' . DB::table('kompetensi')->count());
            $this->line('Riwayat perpindahan: ' . DB::table('asn_unit_histories')->count());
            $this->line('Audit log: ' . DB::table('audit_logs')->count());
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
