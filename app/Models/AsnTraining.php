<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsnTraining extends Model
{
    protected $table = 'asn_trainings';

    protected $fillable = [
        'asn_id',
        'training_id',
        'year',
        'status',
        'source_sheet',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public static function completedStatuses(): array
    {
        return [
            'sudah',
            'completed',
            'terdeteksi',
            'selesai',
            'finish',
            'finished',
        ];
    }

    public static function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'diusulkan') {
            return 'diusulkan';
        }

        return in_array($status, self::completedStatuses(), true)
            ? 'sudah'
            : 'belum';
    }

    public static function statusLabel(?string $status): string
    {
        return match (self::normalizeStatus($status)) {
            'sudah' => 'Sudah mengikuti',
            'diusulkan' => 'Diusulkan',
            default => 'Belum mengikuti',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabel($this->status);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereIn('status', self::completedStatuses());
    }

    public function asn(): BelongsTo
    {
        return $this->belongsTo(Asn::class, 'asn_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class, 'training_id');
    }
}
