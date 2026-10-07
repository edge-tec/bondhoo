<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaProcessingJob extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'media_id',
        'job_uuid',
        'job_type',
        'status',
        'progress',
        'attempts',
        'error_message',
        'resource_metrics',
        'payload',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'attempts' => 'integer',
            'resource_metrics' => 'array',
            'payload' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function markCompleted(array $metrics = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'completed_at' => now(),
            'resource_metrics' => $metrics ?: $this->resource_metrics,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }
}
