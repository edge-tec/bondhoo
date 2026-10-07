<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * রিল ভিউ মডেল:
 * ওয়াচ টাইম ও ইউনিক ভিউ ট্র্যাকিং।
 */
class ReelView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'reel_id',
        'user_id',
        'ip_address',
        'watch_time_seconds',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'watch_time_seconds' => 'float',
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
