<?php

namespace App\Http\Controllers;

use App\Models\Asn;
use App\Models\AsnTraining;
use App\Models\AsnUnitHistory;
use App\Models\Pelatihan;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;

class AsnController extends Controller
{
    public function index(Request $request)
    {
        $query = Asn::query()
            ->with([
                'unit',
                'competencies.pelatihans',
            ])
            ->withCount([
                'trainings',
                'competencies',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('rank', 'like', "%{$search}%")
                    ->orWhereHas('unit', function ($unitQuery) use ($search) {
                        $unitQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->status === 'sudah') {
            $query->whereHas('trainings', function ($q) {
                $q->whereIn('asn_trainings.status', AsnTraining::completedStatuses());
            });
        }

        if ($request->status === 'belum') {
            $query->where(function ($q) {
                $q->whereDoesntHave('trainings')
                    ->orWhereHas('trainings', function ($trainingQuery) {
                        $trainingQuery->whereNotIn(
                            'asn_trainings.status',
                            AsnTraining::completedStatuses()
                        );
                    });
            });
        }

        if ($request->status === 'diusulkan') {
            $query->whereHas('trainings', function ($q) {
                $q->where('asn_trainings.status', 'diusulkan');
            });
        }

        $asn = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $asn->getCollection()->transform(function ($item) {
            $item->setAttribute(
                'mapped_trainings',
                $item->competencies
                    ->flatMap(fn ($competency) => $competency->pelatihans)
                    ->unique('id')
                    ->sortBy('name')
                    ->values()
            );

            return $item;
        });

        return view('asn.index', compact('asn'));
    }

    public function create()
    {
        $units = Unit::query()
            ->orderBy('name')
            ->get();

        return view('asn.create', compact('units'));
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:30', 'unique:asns,nip'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'rank' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'exists:units,id'],
        ]);

        $asn = Asn::create($validated);
        $audit->log(
            'asn_created',
            'ASN baru ditambahkan: ' . $asn->name,
            $asn,
            null,
            $asn->only(['nip', 'name', 'position', 'rank', 'unit_id'])
        );

        return redirect()
            ->route('asn.show', $asn)
            ->with('success', 'Data ASN berhasil ditambahkan.');
    }

    public function show(Asn $asn)
    {
        $asn->load([
            'unit',
            'competencies',
            'unitHistories.fromUnit',
            'unitHistories.toUnit',
            'unitHistories.changedBy',
        ]);

        $trainings = Pelatihan::query()
            ->orderBy('name')
            ->get();

        // Build training recommendations from the competency mapping maintained by Admin.
        // A training is recommended when at least one ASN competency is mapped to it.
        // Required competencies are surfaced separately so the user can see whether the
        // ASN already satisfies all main competency requirements.
        $asnCompetencyIds = $asn->competencies->pluck('id')->map(fn ($id) => (int) $id);
        $mappedTrainings = Pelatihan::query()
            ->with(['kompetensi' => fn ($query) => $query->orderBy('nama')])
            ->whereHas('kompetensi', function ($query) use ($asnCompetencyIds) {
                $query->whereIn('kompetensi.id', $asnCompetencyIds->all());
            })
            ->orderBy('name')
            ->get();

        $recommendations = $mappedTrainings->map(function ($training) use ($asnCompetencyIds) {
            $mappedCompetencies = $training->kompetensi;
            $matched = $mappedCompetencies->filter(
                fn ($competency) => $asnCompetencyIds->contains((int) $competency->id)
            )->values();

            $required = $mappedCompetencies->filter(
                fn ($competency) => (bool) $competency->pivot->wajib
            )->values();

            $matchedRequired = $required->filter(
                fn ($competency) => $asnCompetencyIds->contains((int) $competency->id)
            )->values();

            $training->setAttribute('matched_competencies', $matched);
            $training->setAttribute('required_competencies', $required);
            $training->setAttribute('matched_required_competencies', $matchedRequired);
            $training->setAttribute('match_count', $matched->count());
            $training->setAttribute('required_count', $required->count());
            $training->setAttribute('required_match_count', $matchedRequired->count());
            $level = $required->isNotEmpty() && $matchedRequired->count() < $required->count()
                ? 'sebagian'
                : 'direkomendasikan';

            $training->setAttribute('recommendation_level', $level);
            $training->setAttribute(
                'recommendation_score',
                ($level === 'direkomendasikan' ? 100000 : 0)
                    + ($matchedRequired->count() * 100)
                    + $matched->count()
            );

            return $training;
        });

        $recommendations = $recommendations
            ->sortByDesc('recommendation_score')
            ->sortBy('name')
            ->values();

        $statusRows = AsnTraining::query()
            ->where('asn_id', $asn->id)
            ->orderByDesc('id')
            ->get();

        $trainingStatus = $statusRows
            ->groupBy('training_id')
            ->map(function ($rows) {
                $manual = $rows
                    ->filter(function ($row) {
                        return $row->source_sheet === 'manual'
                            && is_null($row->year);
                    })
                    ->sortByDesc('id')
                    ->first();

                if ($manual) {
                    return AsnTraining::normalizeStatus($manual->status);
                }

                return $rows->contains(function ($row) {
                    return in_array(
                        strtolower(trim((string) $row->status)),
                        AsnTraining::completedStatuses(),
                        true
                    );
                }) ? 'sudah' : 'belum';
            });

        // Do not recommend trainings already completed by this ASN.
        $completedTrainingIds = $trainingStatus
            ->filter(fn ($status) => $status === 'sudah')
            ->keys()
            ->map(fn ($id) => (int) $id);

        $recommendations = $recommendations
            ->reject(fn ($training) => $completedTrainingIds->contains((int) $training->id))
            ->values();

        return view('asn.show', compact(
            'asn',
            'trainings',
            'trainingStatus',
            'recommendations'
        ));
    }

    public function edit(Asn $asn)
    {
        $units = Unit::query()
            ->orderBy('name')
            ->get();

        return view('asn.edit', compact('asn', 'units'));
    }

    public function update(Request $request, Asn $asn, AuditLogger $audit)
    {
        $validated = $request->validate([
            'nip' => [
                'required',
                'string',
                'max:30',
                'unique:asns,nip,' . $asn->id,
            ],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'rank' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'exists:units,id'],
        ]);

        $old = $asn->only(['nip', 'name', 'position', 'rank', 'unit_id']);
        $asn->update($validated);
        $new = $asn->only(['nip', 'name', 'position', 'rank', 'unit_id']);

        $changes = array_diff_assoc($new, $old);
        if ($changes) {
            $audit->log(
                'asn_updated',
                'Data ASN diperbarui: ' . $asn->name,
                $asn,
                $old,
                $new
            );
        }

        return redirect()
            ->route('asn.show', $asn)
            ->with('success', 'Data ASN berhasil diperbarui.');
    }

    public function updateTrainingStatus(
        Request $request,
        Asn $asn,
        Pelatihan $pelatihan,
        AuditLogger $audit
    ) {
        $validated = $request->validate([
            'status' => ['required', 'in:sudah,belum,diusulkan'],
        ]);

        $oldStatus = null;
        DB::transaction(function () use ($asn, $pelatihan, $validated, &$oldStatus) {
            $manual = AsnTraining::query()
                ->where('asn_id', $asn->id)
                ->where('training_id', $pelatihan->id)
                ->whereNull('year')
                ->where('source_sheet', 'manual')
                ->latest('id')
                ->first();

            if (!$manual) {
                $manual = new AsnTraining([
                    'asn_id' => $asn->id,
                    'training_id' => $pelatihan->id,
                    'year' => null,
                    'source_sheet' => 'manual',
                ]);
            }

            $oldStatus = $manual->exists ? $manual->status : null;
            $manual->status = $validated['status'];
            $manual->save();
        });

        $label = match ($validated['status']) {
            'sudah' => 'Sudah mengikuti',
            'diusulkan' => 'Diusulkan',
            default => 'Belum mengikuti',
        };

        if ($oldStatus !== $validated['status']) {
            $manual = AsnTraining::query()
                ->where('asn_id', $asn->id)
                ->where('training_id', $pelatihan->id)
                ->whereNull('year')
                ->where('source_sheet', 'manual')
                ->latest('id')
                ->first();

            $audit->log(
                'training_status_updated',
                'Status pelatihan ASN diperbarui: ' . $asn->name . ' — ' . $pelatihan->name,
                $manual,
                [
                    'asn_id' => $asn->id,
                    'training_id' => $pelatihan->id,
                    'training_name' => $pelatihan->name,
                    'status' => $oldStatus,
                ],
                [
                    'asn_id' => $asn->id,
                    'training_id' => $pelatihan->id,
                    'training_name' => $pelatihan->name,
                    'status' => $validated['status'],
                ]
            );
        }

        return redirect()
            ->route('asn.show', $asn)
            ->with(
                'success',
                "Status " . $pelatihan->name . " berhasil diubah menjadi: {$label}."
            );
    }

    public function cancelUnitHistory(Request $request, Asn $asn, AsnUnitHistory $history, AuditLogger $audit)
    {
        if ((int) $history->asn_id !== (int) $asn->id) {
            abort(404);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($asn, $history, $validated) {
            $history = AsnUnitHistory::query()
                ->whereKey($history->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($history->status ?: 'aktif') !== 'aktif') {
                throw new \RuntimeException('Riwayat perpindahan tersebut sudah dibatalkan.');
            }

            $latestActive = AsnUnitHistory::query()
                ->where('asn_id', $asn->id)
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', 'aktif');
                })
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$latestActive || (int) $latestActive->id !== (int) $history->id) {
                throw new \RuntimeException(
                    'Riwayat ini tidak dapat dibatalkan karena sudah ada perpindahan aktif setelahnya. Batalkan perpindahan terbaru terlebih dahulu.'
                );
            }

            if ((int) ($asn->unit_id ?? 0) !== (int) ($history->to_unit_id ?? 0)) {
                throw new \RuntimeException(
                    'Unit kerja ASN saat ini tidak lagi sesuai dengan tujuan riwayat tersebut. Riwayat tidak dibatalkan untuk mencegah data menjadi tidak konsisten.'
                );
            }

            $asn->update([
                'unit_id' => $history->from_unit_id,
            ]);

                $history->update([
                    'status' => 'dibatalkan',
                    'canceled_at' => now(),
                    'canceled_by' => auth()->id(),
                    'cancellation_reason' => $validated['cancellation_reason'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('asn.show', $asn)
                ->with('error', $e->getMessage());
        }

        $audit->log(
            'unit_history_canceled',
            'Koreksi perpindahan ASN: ' . $asn->name,
            $history,
            ['status' => 'aktif', 'unit_id' => $history->to_unit_id],
            ['status' => 'dibatalkan', 'unit_id' => $history->from_unit_id, 'cancellation_reason' => $validated['cancellation_reason']]
        );

        return redirect()
            ->route('asn.show', $asn)
            ->with('success', 'Perpindahan dibatalkan. Unit kerja ASN dikembalikan ke unit sebelumnya dan riwayat tetap disimpan sebagai jejak koreksi.');
    }

    public function transferUnit(Request $request, Asn $asn, AuditLogger $audit)
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'exists:units,id'],
            'effective_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $history = null;

        DB::transaction(function () use ($asn, $validated, &$history) {
            $fromUnitId = $asn->unit_id;
            $toUnitId = (int) $validated['unit_id'];

            if ($fromUnitId === $toUnitId) {
                return;
            }

            $history = AsnUnitHistory::create([
                'asn_id' => $asn->id,
                'from_unit_id' => $fromUnitId,
                'to_unit_id' => $toUnitId,
                'effective_date' => $validated['effective_date'] ?? now()->toDateString(),
                'reason' => $validated['reason'] ?? 'Penugasan / perpindahan unit kerja',
                'note' => $validated['note'] ?? null,
                'changed_by' => auth()->id(),
            ]);

            $asn->update(['unit_id' => $toUnitId]);
        });

        if ($history) {
            $audit->log(
                'unit_transferred',
                'ASN dipindahkan: ' . $asn->name,
                $history,
                ['unit_id' => $history->from_unit_id],
                ['unit_id' => $history->to_unit_id, 'effective_date' => optional($history->effective_date)->toDateString(), 'reason' => $history->reason, 'note' => $history->note]
            );
        }

        return redirect()->route('asn.show', $asn)
            ->with('success', 'Unit kerja ASN berhasil diperbarui dan riwayat perpindahan disimpan.');
    }

    public function destroy(Asn $asn, AuditLogger $audit)
    {
        $snapshot = $asn->only(['nip', 'name', 'position', 'rank', 'unit_id']);

        DB::transaction(function () use ($asn) {
            DB::table('asn_trainings')
                ->where('asn_id', $asn->id)
                ->delete();

            DB::table('asn_kompetensi')
                ->where('asn_id', $asn->id)
                ->delete();

            $asn->delete();
        });

        $audit->log(
            'asn_deleted',
            'ASN dihapus: ' . $asn->name,
            null,
            $snapshot,
            null
        );

        return redirect()
            ->route('asn.index')
            ->with('success', 'Data ASN berhasil dihapus.');
    }
}
