<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MusicTrack Model
 *
 * Manages approved, licensed, and royalty-free platform background audio tracks.
 */
class MusicTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'artist',
        'album',
        'audio_url',
        'cover_url',
        'duration',
        'genre',
        'language',
        'tags',
        'bpm',
        'license_type',
        'license_holder',
        'is_admin_approved',
        'is_active',
        'usages_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration' => 'float',
            'tags' => 'array',
            'bpm' => 'integer',
            'is_admin_approved' => 'boolean',
            'is_active' => 'boolean',
            'usages_count' => 'integer',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MusicUsage::class);
    }

    public function reels(): HasMany
    {
        return $this->hasMany(Reel::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Scope for active and approved music library tracks.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('is_admin_approved', true);
    }

    /**
     * Format response for frontend music picker.
     *
     * @return array<string, mixed>
     */
    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'artist' => $this->artist,
            'album' => $this->album,
            'audio_url' => $this->audio_url,
            'cover_url' => $this->cover_url ?: '/images/bondhoo-icon.png',
            'duration' => (float) $this->duration,
            'formatted_duration' => sprintf('%d:%02d', (int) ($this->duration / 60), (int) ($this->duration % 60)),
            'genre' => $this->genre,
            'language' => $this->language,
            'tags' => $this->tags ?? [],
            'bpm' => $this->bpm,
            'license_type' => $this->license_type,
            'usages_count' => $this->usages_count,
        ];
    }
}
