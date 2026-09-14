<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use Illuminate\Http\Request;

class PelatihanController extends Controller
{
    public function index(Request $request)
    {
        $query = Pelatihan::query()
            ->withCount([
                'kompetensi',
                'asns',
            ])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $pelatihan = $query
            ->paginate(20)
            ->withQueryString();

        return view('pelatihan.index', compact('pelatihan'));
    }

    public function show(Pelatihan $pelatihan)
    {
        $pelatihan->load([
            'kompetensi' => fn ($query) => $query->orderBy('nama'),
            'asns' => fn ($query) => $query->orderBy('name'),
        ]);

        $pelatihanAsns = $pelatihan->asns
            ->groupBy('id')
            ->map(function ($rows) {
                $manual = $rows
                    ->filter(fn ($asn) => $asn->pivot->source_sheet === 'manual')
                    ->sortByDesc('pivot.created_at')
                    ->first();

                return $manual ?: $rows->sortByDesc('pivot.created_at')->first();
            })
            ->values();

        return view('pelatihan.show', compact('pelatihan', 'pelatihanAsns'));
    }
}
