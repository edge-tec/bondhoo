<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    use HasFactory;

    protected $table = 'profile_views';

    protected $fillable = [
        'user_id',
        'viewer_id',
        'is_anonymous',
        'ip_address',
        'ip_hash',
        'user_agent',
        'device_type',
        'source',
        'referer',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'is_anonymous' => 'boolean',
        ];
    }

    public function scopeOwner($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSince($query, $date)
    {
        return $query->where('viewed_at', '>=', $date);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
