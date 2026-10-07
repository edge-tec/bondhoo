<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoAlbumItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'album_id',
        'media_id',
        'media_url',
        'caption',
        'location',
        'tagged_user_ids',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'tagged_user_ids' => 'array',
            'display_order' => 'integer',
        ];
    }

    public function getMediaPathAttribute(): ?string
    {
        return $this->media_url;
    }

    public function setMediaPathAttribute(?string $value): void
    {
        $this->attributes['media_url'] = $value;
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(PhotoAlbum::class, 'album_id');
    }
}
