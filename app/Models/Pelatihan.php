<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pelatihan extends Model
{
    protected $table = 'trainings';

    protected $fillable = [
        'name',
        'code',
    ];

    public function kompetensi(): BelongsToMany
    {
        return $this->belongsToMany(
            Kompetensi::class,
            'training_kompetensi',
            'training_id',
            'kompetensi_id'
        )->withPivot('wajib')->withTimestamps();
    }

    public function asns(): BelongsToMany
    {
        return $this->belongsToMany(
            Asn::class,
            'asn_trainings',
            'training_id',
            'asn_id'
        )
        ->withPivot([
            'year',
            'status',
            'source_sheet',
        ])
        ->withTimestamps();
    }
}
