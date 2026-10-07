<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'media_id',
        'cover_url',
        'position_y',
        'is_current',
        'caption',
        'privacy',
    ];

    protected $appends = [
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'position_y' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    public function getPhotoPathAttribute(): ?string
    {
        return $this->cover_url;
    }

    public function setPhotoPathAttribute(?string $value): void
    {
        $this->attributes['cover_url'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
