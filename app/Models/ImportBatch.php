<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $table = 'import_batches';

    protected $fillable = [
        'filename',
        'total_sheets',
        'total_asns',
        'total_trainings',
        'total_competencies',
        'status',
        'error_message',
    ];

    protected $casts = [
        'total_sheets' => 'integer',
        'total_asns' => 'integer',
        'total_trainings' => 'integer',
        'total_competencies' => 'integer',
    ];
}