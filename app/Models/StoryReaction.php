<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * স্টোরি রিঅ্যাকশন মডেল:
 * ফেসবুক স্টোরির মতো Like, Love, Haha, Wow, Sad, Angry ইমোজি রিঅ্যাকশন।
 */
class StoryReaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'story_id',
        'user_id',
        'type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
