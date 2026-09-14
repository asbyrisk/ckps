<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsnCompetency extends Model
{
    protected $table = 'asn_kompetensi';

    protected $fillable = [
        'asn_id',
        'kompetensi_id',
        'source',
    ];

    public function asn(): BelongsTo
    {
        return $this->belongsTo(Asn::class, 'asn_id');
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Kompetensi::class, 'kompetensi_id');
    }
}
