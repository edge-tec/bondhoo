<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoryHighlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'cover_url',
    ];

    public function getCoverImagePathAttribute(): ?string
    {
        return $this->cover_url;
    }

    public function setCoverImagePathAttribute(?string $value): void
    {
        $this->attributes['cover_url'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoryHighlightItem::class, 'highlight_id');
    }
}
