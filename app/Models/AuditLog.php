<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function actionLabelFor(?string $action): string
    {
        return match ($action) {
            'excel_imported' => 'Import Excel berhasil',
            'excel_import_failed' => 'Import Excel gagal',
            'asn_created' => 'ASN ditambahkan',
            'asn_updated' => 'Data ASN diperbarui',
            'asn_deleted' => 'ASN dihapus',
            'training_status_updated' => 'Status pelatihan diubah',
            'unit_transferred' => 'Perpindahan unit kerja',
            'unit_history_canceled' => 'Koreksi perpindahan',
            default => ucfirst(str_replace('_', ' ', (string) $action)),
        };
    }

    public function actionLabel(): string
    {
        return self::actionLabelFor($this->action);
    }

    public function auditableLabel(): string
    {
        return match ($this->auditable_type) {
            Asn::class => 'ASN',
            AsnUnitHistory::class => 'Riwayat perpindahan ASN',
            AsnTraining::class => 'Status pelatihan ASN',
            ImportBatch::class => 'Import Excel',
            default => $this->auditable_type
                ? class_basename($this->auditable_type)
                : 'Sistem',
        };
    }
}
