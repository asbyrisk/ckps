<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = [
        'nama',
        'jenis',
    ];

    public function asn(): HasMany
    {
        return $this->hasMany(
            Asn::class,
            'jabatan_id'
        );
    }
}