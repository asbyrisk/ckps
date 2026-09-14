<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportPreview extends Model
{
    protected $fillable = [
        'user_id', 'filename', 'path', 'summary', 'status', 'expires_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
