<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveStreamReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_stream_id',
        'reporter_id',
        'reason',
        'details',
        'status',
        'action_taken_by',
    ];

    public function liveStream(): BelongsTo
    {
        return $this->belongsTo(LiveStream::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function actionTakenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_taken_by');
    }
}
