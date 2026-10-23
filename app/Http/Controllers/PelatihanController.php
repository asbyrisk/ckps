<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use App\Models\Kompetensi;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

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


    public function editMapping(Pelatihan $pelatihan)
    {
        $pelatihan->load('kompetensi');
        $kompetensi = Kompetensi::query()
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();

        $selected = $pelatihan->kompetensi->keyBy('id');

        return view('pelatihan.mapping', compact('pelatihan', 'kompetensi', 'selected'));
    }

    public function updateMapping(Request $request, Pelatihan $pelatihan, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'kompetensi' => ['nullable', 'array'],
            'kompetensi.*' => ['integer', 'exists:kompetensi,id'],
            'wajib' => ['nullable', 'array'],
            'wajib.*' => ['integer', 'exists:kompetensi,id'],
        ]);

        $ids = collect($validated['kompetensi'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $required = collect($validated['wajib'] ?? [])->map(fn ($id) => (int) $id)->unique();

        $oldIds = $pelatihan->kompetensi()->pluck('kompetensi.id')->map(fn ($id) => (int) $id)->sort()->values();
        $sync = [];
        foreach ($ids as $id) {
            $sync[$id] = ['wajib' => $required->contains($id)];
        }

        $pelatihan->kompetensi()->sync($sync);

        $audit->log(
            'training_competency_mapping_updated',
            "Pemetaan kompetensi untuk pelatihan {$pelatihan->name} diperbarui.",
            $pelatihan,
            ['kompetensi_ids' => $oldIds->all()],
            ['kompetensi_ids' => $ids->sort()->values()->all(), 'wajib_ids' => $required->intersect($ids)->sort()->values()->all()]
        );

        return redirect()->route('pelatihan.show', $pelatihan)
            ->with('success', 'Pemetaan kompetensi berhasil diperbarui.');
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
