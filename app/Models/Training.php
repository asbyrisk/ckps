<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Training extends Model
{
    protected $table = 'trainings';

    protected $fillable = [
        'name',
        'code',
    ];

    public function asns(): BelongsToMany
    {
        return $this->belongsToMany(
            Asn::class,
            'asn_trainings',
            'training_id',
            'asn_id'
        )->withPivot([
            'year',
            'status',
            'source_sheet',
        ])->withTimestamps();
    }
}