<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamViewer extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'user_id',
        'session_id',
        'joined_at',
        'last_ping_at',
        'left_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
