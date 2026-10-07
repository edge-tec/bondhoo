<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupPollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'option_text',
        'votes_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'votes_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function poll(): BelongsTo
    {
        return $this->belongsTo(GroupPoll::class, 'poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(GroupPollVote::class, 'poll_option_id');
    }
}
