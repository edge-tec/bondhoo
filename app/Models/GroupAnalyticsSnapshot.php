<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupAnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'date',
        'total_members',
        'new_members',
        'active_members',
        'posts_count',
        'comments_count',
        'reactions_count',
        'engagement_rate',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_members' => 'integer',
            'new_members' => 'integer',
            'active_members' => 'integer',
            'posts_count' => 'integer',
            'comments_count' => 'integer',
            'reactions_count' => 'integer',
            'engagement_rate' => 'float',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
