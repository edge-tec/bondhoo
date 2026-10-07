<?php

namespace App\Models;

use App\Services\Contracts\MediaStorageServiceInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mediable_type',
        'mediable_id',
        'collection',
        'disk',
        'original_path',
        'thumbnail_path',
        'medium_path',
        'large_path',
        'mime_type',
        'width',
        'height',
        'size',
        'checksum',
        'processing_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Resolve public/CDN URL for specific variant (original, thumbnail, medium, preview, large).
     */
    public function getVariantUrl(string $variant = 'original'): ?string
    {
        $path = match ($variant) {
            'thumbnail' => $this->thumbnail_path ?: ($this->medium_path ?: $this->original_path),
            'medium', 'preview' => $this->medium_path ?: $this->original_path,
            'large' => $this->large_path ?: $this->original_path,
            default => $this->original_path,
        };

        if (! $path) {
            return null;
        }

        $storageService = app(MediaStorageServiceInterface::class);

        return $storageService->getUrl($path);
    }

    /**
     * Virtual name attribute.
     */
    public function getNameAttribute(): string
    {
        return $this->metadata['original_filename'] ?? basename($this->original_path ?? '');
    }

    /**
     * Virtual filename attribute.
     */
    public function getFilenameAttribute(): string
    {
        return $this->metadata['original_filename'] ?? basename($this->original_path ?? '');
    }

    /**
     * Standard serialized representation of Media object.
     */
    public function toResponseArray(): array
    {
        $originalUrl = $this->getVariantUrl('original');
        $previewUrl = $this->getVariantUrl('medium') ?: $originalUrl;
        $thumbnailUrl = $this->getVariantUrl('thumbnail') ?: $previewUrl;
        $downloadUrl = url("/api/v1/messages/attachments/{$this->id}/download");
        $viewUrl = url("/api/v1/messages/attachments/{$this->id}/view");
        $filename = $this->metadata['original_filename'] ?? basename($this->original_path);

        return [
            'id' => $this->id,
            'collection' => $this->collection,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'dimensions' => ($this->width && $this->height) ? [
                'width' => $this->width,
                'height' => $this->height,
            ] : null,
            'status' => $this->processing_status,
            'url' => $previewUrl ?: $originalUrl,
            'preview_url' => $previewUrl ?: $originalUrl,
            'thumbnail_url' => $thumbnailUrl ?: $originalUrl,
            'download_url' => $downloadUrl,
            'view_url' => $viewUrl,
            'original_url' => $originalUrl,
            'name' => $filename,
            'filename' => $filename,
            'metadata' => $this->metadata,
            'urls' => [
                'original' => $originalUrl,
                'thumbnail' => $thumbnailUrl ?: $originalUrl,
                'medium' => $this->getVariantUrl('medium') ?: $originalUrl,
                'large' => $this->getVariantUrl('large') ?: $originalUrl,
                'preview' => $previewUrl ?: $originalUrl,
                'download' => $downloadUrl,
                'view' => $viewUrl,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
