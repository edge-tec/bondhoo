<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * রিল মিডিয়া মডেল:
 * রিলের বিভিন্ন কোয়ালিটি ভার্সন (original, 1080p, 720p, 480p, 360p) এবং থাম্বনেইল।
 */
class ReelMedia extends Model
{
    use HasFactory;

    protected $table = 'reel_media';

    protected $fillable = [
        'reel_id',
        'media_id',
        'quality',
        'video_path',
        'thumbnail_path',
        'hls_path',
        'mime_type',
        'bitrate',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'bitrate' => 'integer',
            'size' => 'integer',
        ];
    }

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (! $this->video_path) {
            return null;
        }

        return Storage::url($this->video_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return Storage::url($this->thumbnail_path);
    }
}
