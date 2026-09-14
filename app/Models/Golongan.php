<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Golongan extends Model
{
    protected $table = 'golongan';

    protected $fillable = [
        'kode',
        'nama',
        'urutan',
    ];

    public function asn(): HasMany
    {
        return $this->hasMany(
            Asn::class,
            'golongan_id'
        );
    }
}