<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

/**
 * Compatibility alias for older code. The application now uses the
 * `kompetensi` table through the Kompetensi model as its single source of truth.
 */
class Competency extends Model
{
    protected $table = 'kompetensi';

    protected $fillable = [
        'kode',
        'level',
        'nama',
        'pelatihan_eksisting',
        'aktif',
    ];

    public function asns(): BelongsToMany
    {
        return $this->belongsToMany(
            Asn::class,
            'asn_kompetensi',
            'kompetensi_id',
            'asn_id'
        )->withPivot(['level', 'sumber', 'status'])->withTimestamps();
    }

    public function pelatihans(): BelongsToMany
    {
        return $this->belongsToMany(
            Pelatihan::class,
            'training_kompetensi',
            'kompetensi_id',
            'training_id'
        )->withPivot('wajib')->withTimestamps();
    }
}
