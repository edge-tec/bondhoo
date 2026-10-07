<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryHighlightItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'highlight_id',
        'story_id',
        'media_path',
        'media_type',
    ];

    public function highlight(): BelongsTo
    {
        return $this->belongsTo(StoryHighlight::class, 'highlight_id');
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class, 'story_id');
    }
}
