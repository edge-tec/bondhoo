<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_id',
        'photo_url',
        'is_current',
        'caption',
        'frame',
        'privacy',
    ];

    protected $appends = [
        'photo_path',
        'frame_id',
    ];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
        ];
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->photo_url;
    }

    public function setPhotoPathAttribute(?string $value): void
    {
        $this->attributes['photo_url'] = $value;
    }

    public function getFrameIdAttribute(): ?string
    {
        return $this->frame;
    }

    public function setFrameIdAttribute(?string $value): void
    {
        $this->attributes['frame'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
