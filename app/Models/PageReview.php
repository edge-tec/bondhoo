<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'rating',
        'review_text',
        'reply_text',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
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
            'author_name' => $this->user?->name ?? 'User',
            'author_avatar' => $this->user?->profile?->avatar_url ?? $this->user?->avatar_url,
            'rating' => $this->rating,
            'review_text' => $this->review_text,
            'reply_text' => $this->reply_text,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
