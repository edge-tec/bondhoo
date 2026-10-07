<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlockedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'reason',
        'blocked_by',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
