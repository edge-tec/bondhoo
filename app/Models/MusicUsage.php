<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * MusicUsage Model
 *
 * Tracks individual audio track usage inside Reels or Stories.
 */
class MusicUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'music_track_id',
        'usable_type',
        'usable_id',
        'user_id',
        'start_time_seconds',
        'duration_seconds',
        'volume_percent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time_seconds' => 'float',
            'duration_seconds' => 'float',
            'volume_percent' => 'integer',
        ];
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(MusicTrack::class, 'music_track_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
