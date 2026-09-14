<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsnUnitHistory extends Model
{
    protected $table = 'asn_unit_histories';

    protected $fillable = [
        'asn_id', 'from_unit_id', 'to_unit_id', 'effective_date',
        'reason', 'note', 'changed_by', 'status', 'canceled_at', 'canceled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'canceled_at' => 'datetime',
    ];

    public function fromUnit(): BelongsTo { return $this->belongsTo(Unit::class, 'from_unit_id'); }
    public function toUnit(): BelongsTo { return $this->belongsTo(Unit::class, 'to_unit_id'); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
    public function canceledBy(): BelongsTo { return $this->belongsTo(User::class, 'canceled_by'); }

    public function isActive(): bool
    {
        return ($this->status ?: 'aktif') === 'aktif';
    }

    public function isCanceled(): bool
    {
        return ($this->status ?: 'aktif') === 'dibatalkan';
    }
}
