<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyRequest extends Model
{
    use HasFactory;

    public const TYPE_EXPORT = 'export';

    public const TYPE_ERASURE = 'erasure';

    public const TYPE_RECTIFICATION = 'rectification';

    public const TYPE_OBJECTION = 'objection';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_BLOCKED_BY_LEGAL_HOLD = 'blocked_by_legal_hold';

    protected $fillable = [
        'request_number',
        'user_id',
        'request_type',
        'status',
        'reason',
        'export_file_path',
        'export_expires_at',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'export_expires_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
