<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asn extends Model
{
    protected $table = 'asns';

    protected $fillable = [
        'nip',
        'name',
        'position',
        'rank',
        'unit_id',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelatihan::class,
            'asn_trainings',
            'asn_id',
            'training_id'
        )
        ->withPivot([
            'year',
            'status',
            'source_sheet',
        ])
        ->withTimestamps();
    }

    public function unitHistories(): HasMany
    {
        return $this->hasMany(AsnUnitHistory::class, 'asn_id')->latest('effective_date')->latest('id');
    }

    public function competencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Kompetensi::class,
            'asn_kompetensi',
            'asn_id',
            'kompetensi_id'
        )
        ->withPivot(['level', 'sumber', 'status'])
        ->withTimestamps();
    }
}
