<?php

namespace App\Http\Controllers;

use App\Models\Asn;
use App\Models\AsnTraining;
use App\Models\Kompetensi;
use App\Models\Pelatihan;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAsn = Asn::count();
        $totalPelatihan = Pelatihan::count();
        $totalKompetensi = Kompetensi::where('aktif', true)->count();

        $pelatihanDenganKompetensi = Pelatihan::whereHas('kompetensi')->count();
        $pelatihanTanpaKompetensi = Pelatihan::whereDoesntHave('kompetensi')->count();

        $kompetensiTerpakai = Kompetensi::where('aktif', true)->whereHas('pelatihans')->count();
        $kompetensiBelumTerpakai = Kompetensi::where('aktif', true)->whereDoesntHave('pelatihans')->count();

        $totalPemetaan = DB::table('training_kompetensi')->count();
        $pemetaanWajib = DB::table('training_kompetensi')->where('wajib', true)->count();
        $pelatihanDenganKompetensiWajib = Pelatihan::whereHas('kompetensi', function ($query) {
            $query->where('training_kompetensi.wajib', true);
        })->count();

        $asnSudahPelatihan = Asn::whereHas('trainings', function ($query) {
            $query->whereIn('asn_trainings.status', AsnTraining::completedStatuses());
        })->count();

        $asnBelumPelatihan = max(0, $totalAsn - $asnSudahPelatihan);
        $asnMemilikiKompetensi = Asn::whereHas('competencies')->count();
        $asnBelumMemilikiKompetensi = max(0, $totalAsn - $asnMemilikiKompetensi);

        $pelatihanTerbaru = Pelatihan::query()
            ->withCount(['kompetensi', 'asns'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        // Insight pemetaan untuk Admin.
        $pelatihanTanpaPemetaan = Pelatihan::query()
            ->whereDoesntHave('kompetensi')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'code']);

        $kompetensiTanpaPelatihan = Kompetensi::query()
            ->where('aktif', true)
            ->whereDoesntHave('pelatihans')
            ->orderBy('nama')
            ->limit(8)
            ->get(['id', 'kode', 'nama', 'level']);

        $pelatihanPalingTerpetakan = Pelatihan::query()
            ->withCount('kompetensi')
            ->having('kompetensi_count', '>', 0)
            ->orderByDesc('kompetensi_count')
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'code']);

        $kompetensiPalingDimiliki = Kompetensi::query()
            ->where('aktif', true)
            ->withCount('asns')
            ->having('asns_count', '>', 0)
            ->orderByDesc('asns_count')
            ->orderBy('nama')
            ->limit(6)
            ->get(['id', 'kode', 'nama']);

        // Data grafik untuk presentasi mentor.
        $asnPerUnit = Unit::query()
            ->withCount('asns')
            ->having('asns_count', '>', 0)
            ->orderByDesc('asns_count')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name']);

        $statusPelatihan = DB::table('asn_trainings')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return view('dashboard', compact(
            'totalAsn',
            'totalPelatihan',
            'totalKompetensi',
            'pelatihanDenganKompetensi',
            'pelatihanTanpaKompetensi',
            'kompetensiTerpakai',
            'kompetensiBelumTerpakai',
            'totalPemetaan',
            'pemetaanWajib',
            'pelatihanDenganKompetensiWajib',
            'asnSudahPelatihan',
            'asnBelumPelatihan',
            'asnMemilikiKompetensi',
            'asnBelumMemilikiKompetensi',
            'pelatihanTerbaru',
            'pelatihanTanpaPemetaan',
            'kompetensiTanpaPelatihan',
            'pelatihanPalingTerpetakan',
            'kompetensiPalingDimiliki',
            'asnPerUnit',
            'statusPelatihan'
        ));
    }
}
