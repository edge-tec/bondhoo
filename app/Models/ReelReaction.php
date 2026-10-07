<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * রিল রিঅ্যাকশন মডেল:
 * Like, Love, Haha, Wow, Sad, Angry
 */
class ReelReaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'reel_id',
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

    public function reel(): BelongsTo
    {
        return $this->belongsTo(Reel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
