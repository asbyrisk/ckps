<?php

namespace App\Http\Controllers;

use App\Models\Asn;
use App\Models\AsnTraining;
use App\Models\Kompetensi;
use App\Models\Pelatihan;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAsn = Asn::count();
        $totalPelatihan = Pelatihan::count();
        $totalKompetensi = Kompetensi::count();

        $pelatihanDenganKompetensi = Pelatihan::whereHas('kompetensi')->count();
        $pelatihanTanpaKompetensi = Pelatihan::whereDoesntHave('kompetensi')->count();

        $kompetensiTerpakai = Kompetensi::whereHas('pelatihans')->count();
        $kompetensiBelumTerpakai = Kompetensi::whereDoesntHave('pelatihans')->count();

        $asnSudahPelatihan = Asn::whereHas('trainings', function ($query) {
            $query->whereIn('asn_trainings.status', AsnTraining::completedStatuses());
        })->count();

        $asnBelumPelatihan = Asn::where(function ($query) {
            $query->whereDoesntHave('trainings')
                ->orWhereHas('trainings', function ($trainingQuery) {
                    $trainingQuery->whereNotIn(
                        'asn_trainings.status',
                        AsnTraining::completedStatuses()
                    );
                });
        })->count();

        $asnMemilikiKompetensi = Asn::whereHas('competencies')->count();
        $asnBelumMemilikiKompetensi = Asn::whereDoesntHave('competencies')->count();

        $pelatihanTerbaru = Pelatihan::query()
            ->withCount([
                'kompetensi',
                'asns',
            ])
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'totalAsn',
            'totalPelatihan',
            'totalKompetensi',
            'pelatihanDenganKompetensi',
            'pelatihanTanpaKompetensi',
            'kompetensiTerpakai',
            'kompetensiBelumTerpakai',
            'asnSudahPelatihan',
            'asnBelumPelatihan',
            'asnMemilikiKompetensi',
            'asnBelumMemilikiKompetensi',
            'pelatihanTerbaru'
        ));
    }
}
