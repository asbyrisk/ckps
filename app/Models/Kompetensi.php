<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kompetensi extends Model
{
    protected $table = 'kompetensi';

    protected $fillable = [
        'kode',
        'nama',
        'level',
        'pelatihan_eksisting',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function pelatihans(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelatihan::class,
            'training_kompetensi',
            'kompetensi_id',
            'training_id'
        )->withPivot('wajib')->withTimestamps();
    }

    public function asns(): BelongsToMany
    {
        return $this->belongsToMany(
            Asn::class,
            'asn_kompetensi',
            'kompetensi_id',
            'asn_id'
        )
        ->withPivot(['level', 'sumber', 'status'])
        ->withTimestamps();
    }
}
