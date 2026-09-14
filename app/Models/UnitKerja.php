<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitKerja extends Model
{
    protected $table = 'unit_kerja';

    protected $fillable = [
        'nama',
        'parent_id',
        'jenis',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            UnitKerja::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            UnitKerja::class,
            'parent_id'
        );
    }

    public function asn(): HasMany
    {
        return $this->hasMany(
            Asn::class,
            'unit_kerja_id'
        );
    }
}