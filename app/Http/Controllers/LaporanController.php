<?php

namespace App\Http\Controllers;

use App\Models\Asn;
use App\Models\AsnTraining;
use App\Models\Kompetensi;
use App\Models\Pelatihan;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index()
    {
        $totalAsn = Asn::count();
        $totalPelatihan = Pelatihan::count();
        $totalKompetensi = Kompetensi::count();

        $asnSudah = Asn::whereHas('trainings', function ($query) {
            $query->whereIn('asn_trainings.status', AsnTraining::completedStatuses());
        })->count();
        $asnBelum = max(0, $totalAsn - $asnSudah);

        $pelatihanDenganPeserta = Pelatihan::whereHas('asns')->count();
        $pelatihanTanpaPeserta = max(0, $totalPelatihan - $pelatihanDenganPeserta);

        $pelatihanDenganKompetensi = Pelatihan::whereHas('kompetensi')->count();
        $pelatihanTanpaKompetensi = max(0, $totalPelatihan - $pelatihanDenganKompetensi);

        $kompetensiTerpetakan = Kompetensi::whereHas('pelatihans')->count();
        $kompetensiBelumTerpetakan = max(0, $totalKompetensi - $kompetensiTerpetakan);

        $persentaseAsnSudah = $totalAsn > 0 ? round(($asnSudah / $totalAsn) * 100, 1) : 0;
        $persentasePelatihanTerisi = $totalPelatihan > 0 ? round(($pelatihanDenganPeserta / $totalPelatihan) * 100, 1) : 0;
        $persentaseKompetensiTerpetakan = $totalKompetensi > 0 ? round(($kompetensiTerpetakan / $totalKompetensi) * 100, 1) : 0;

        $topPelatihan = Pelatihan::query()
            ->withCount(['asns as peserta_count'])
            ->orderByDesc('peserta_count')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $unitSummary = Asn::query()
            ->leftJoin('units', 'units.id', '=', 'asns.unit_id')
            ->selectRaw("COALESCE(units.name, 'Belum diisi') as unit_name, COUNT(asns.id) as total")
            ->groupBy('unit_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $statusSummary = [
            ['label' => 'Sudah mengikuti pelatihan', 'value' => $asnSudah, 'percentage' => $persentaseAsnSudah, 'tone' => 'green'],
            ['label' => 'Belum mengikuti pelatihan', 'value' => $asnBelum, 'percentage' => $totalAsn > 0 ? round(($asnBelum / $totalAsn) * 100, 1) : 0, 'tone' => 'amber'],
        ];

        return view('laporan.index', compact(
            'totalAsn',
            'totalPelatihan',
            'totalKompetensi',
            'asnSudah',
            'asnBelum',
            'pelatihanDenganPeserta',
            'pelatihanTanpaPeserta',
            'pelatihanDenganKompetensi',
            'pelatihanTanpaKompetensi',
            'kompetensiTerpetakan',
            'kompetensiBelumTerpetakan',
            'persentaseAsnSudah',
            'persentasePelatihanTerisi',
            'persentaseKompetensiTerpetakan',
            'topPelatihan',
            'unitSummary',
            'statusSummary'
        ));
    }
}
