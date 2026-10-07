<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ReelCommentLike Model
 */
class ReelCommentLike extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'reel_comment_id',
        'user_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(ReelComment::class, 'reel_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
