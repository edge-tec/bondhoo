<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateResume extends Model
{
    use HasFactory, SoftDeletes;

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_RECRUITERS_ONLY = 'recruiters_only';

    public const VISIBILITY_PRIVATE = 'private';

    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'is_default',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => round($this->file_size / 1024, 1).' KB',
            'is_default' => $this->is_default,
            'visibility' => $this->visibility,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
