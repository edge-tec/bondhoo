<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MediaUploadSession extends Model
{
    use HasFactory;

    public const STATUS_INITIALIZED = 'initialized';

    public const STATUS_UPLOADING = 'uploading';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_ASSEMBLING = 'assembling';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'session_id',
        'collection',
        'filename',
        'original_name',
        'mime_type',
        'file_size',
        'chunk_size',
        'total_chunks',
        'uploaded_chunks_count',
        'status',
        'checksum',
        'target_path',
        'temp_dir',
        'metadata',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'chunk_size' => 'integer',
            'total_chunks' => 'integer',
            'uploaded_chunks_count' => 'integer',
            'metadata' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(MediaUploadChunk::class, 'upload_session_id')->orderBy('chunk_number');
    }

    public function processingJob(): HasOne
    {
        return $this->hasOne(MediaProcessingJob::class, 'job_uuid', 'session_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isComplete(): bool
    {
        return $this->uploaded_chunks_count >= $this->total_chunks;
    }

    public function getProgressPercent(): int
    {
        if ($this->total_chunks <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->uploaded_chunks_count / $this->total_chunks) * 100));
    }

    /**
     * Get missing chunk numbers list for resume capability.
     *
     * @return array<int>
     */
    public function getMissingChunks(): array
    {
        $existing = $this->chunks()->pluck('chunk_number')->all();
        $existingMap = array_flip($existing);
        $missing = [];

        for ($i = 1; $i <= $this->total_chunks; $i++) {
            if (! isset($existingMap[$i])) {
                $missing[] = $i;
            }
        }

        return $missing;
    }
}
