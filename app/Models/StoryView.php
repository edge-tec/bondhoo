<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * স্টোরি ভিউ মডেল:
 * কোন ইউজার কোন স্টোরি দেখেছেন তা ট্র্যাক করে।
 */
class StoryView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'story_id',
        'user_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
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
