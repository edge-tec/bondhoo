<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * স্টোরি রিপ্লাই মডেল:
 * স্টোরিতে সরাসরি পাঠানো কমেন্ট/মেসেজ।
 */
class StoryReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
        'message',
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
