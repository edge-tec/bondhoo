<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * স্টোরি মিডিয়া মডেল:
 * একটি স্টোরিতে একাধিক ছবি/ভিডিও ক্রমানুসারে প্লে করার ম্যাপিং।
 */
class StoryMedia extends Model
{
    use HasFactory;

    protected $table = 'story_media';

    protected $fillable = [
        'story_id',
        'media_id',
        'order',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
