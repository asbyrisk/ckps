<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $table = 'units';

    protected $fillable = [
        'name',
    ];

    public function asns(): HasMany
    {
        return $this->hasMany(Asn::class, 'unit_id');
    }
}
