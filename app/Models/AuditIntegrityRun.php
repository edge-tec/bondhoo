<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIntegrityRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_uid',
        'records_checked',
        'start_id',
        'end_id',
        'verified',
        'error_code',
        'broken_at_id',
        'gap_ids',
        'root_hash',
        'duration_ms',
        'triggered_by',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'records_checked' => 'integer',
            'verified' => 'boolean',
            'gap_ids' => 'array',
            'duration_ms' => 'integer',
            'executed_at' => 'datetime',
        ];
    }

    public function triggerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
