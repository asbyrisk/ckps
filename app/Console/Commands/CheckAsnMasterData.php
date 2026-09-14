<?php

namespace App\Console\Commands;

use App\Models\Asn;
use Illuminate\Console\Command;

class CheckAsnMasterData extends Command
{
    protected $signature = 'asn:check-master-data';
    protected $description = 'Memeriksa data master ASN yang berpotensi salah mapping dari Excel.';

    public function handle(): int
    {
        $badRanks = Asn::query()->whereNotNull('rank')->get()->filter(function (Asn $asn) {
            return !preg_match('/^(I|II|III|IV)\/[a-e]$/i', trim((string) $asn->rank));
        });

        $this->info('ASN diperiksa: ' . Asn::count());
        $this->warn('Nilai rank yang bukan format golongan I/a–IV/e: ' . $badRanks->count());

        if ($badRanks->count()) {
            $badRanks->take(20)->each(function (Asn $asn) {
                $this->line($asn->nip . ' | ' . $asn->name . ' | rank=' . $asn->rank);
            });
            $this->comment('Jalankan import Excel ulang dengan patch importer ini agar Jabatan/Unit Kerja/Pangkat dibaca dari D/E/F.');
        }

        return self::SUCCESS;
    }
}
