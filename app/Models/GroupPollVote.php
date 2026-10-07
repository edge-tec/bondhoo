<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPollVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'poll_option_id',
        'user_id',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(GroupPoll::class, 'poll_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(GroupPollOption::class, 'poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
