<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * পেজ ফলোয়ার মডেল:
 * কোন ইউজার কোন পেজ ফলো করছেন তা ধারণ করে।
 */
class PageFollower extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
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
}
