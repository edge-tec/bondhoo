<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamModerator extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'user_id',
        'appointed_by',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appointed_by');
    }
}
