<?php

namespace App\Http\Controllers;

use App\Models\Kompetensi;
use Illuminate\Http\Request;

class KompetensiController extends Controller
{
    public function index(Request $request)
    {
        $query = Kompetensi::query()
            ->withCount([
                'pelatihans',
                'asns',
            ])
            ->orderBy('nama');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('level', 'like', "%{$search}%");
            });
        }

        $kompetensi = $query
            ->paginate(20)
            ->withQueryString();

        return view('kompetensi.index', compact('kompetensi'));
    }

    public function show(Kompetensi $kompetensi)
    {
        $kompetensi->load([
            'pelatihans' => fn ($query) => $query->orderBy('name'),
            'asns' => fn ($query) => $query->orderBy('name'),
        ]);

        return view('kompetensi.show', compact('kompetensi'));
    }
}
