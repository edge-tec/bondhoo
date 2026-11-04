<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningDiscussionReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'discussion_id',
        'user_id',
        'content',
        'is_instructor_reply',
        'is_solution',
    ];

    protected function casts(): array
    {
        return [
            'is_instructor_reply' => 'boolean',
            'is_solution' => 'boolean',
        ];
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(LearningDiscussion::class, 'discussion_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
