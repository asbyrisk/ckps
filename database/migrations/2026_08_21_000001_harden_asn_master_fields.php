<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asns')) {
            return;
        }

        // Pangkat/golongan kadang berisi nama jabatan lengkap pada sumber Excel.
        // Sistem tetap memvalidasi/menormalkan golongan, tetapi database tidak
        // boleh gagal hanya karena nilai sumber lebih panjang dari 50 karakter.
        if (Schema::hasColumn('asns', 'rank')) {
            DB::statement("ALTER TABLE `asns` MODIFY `rank` VARCHAR(255) NULL");
        }

        if (Schema::hasColumn('asns', 'position')) {
            DB::statement("ALTER TABLE `asns` MODIFY `position` VARCHAR(255) NULL");
        }

        if (Schema::hasColumn('asns', 'nip')) {
            DB::statement("ALTER TABLE `asns` MODIFY `nip` VARCHAR(30) NOT NULL");
        }
    }

    public function down(): void
    {
        // Non-destructive: jangan mengecilkan kembali kolom master ASN.
    }
};
