<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MessengerSyncEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_uuid',
        'user_id',
        'conversation_id',
        'event_type',
        'payload',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MessengerSyncEvent $event) {
            if (empty($event->event_uuid)) {
                $event->event_uuid = (string) Str::uuid();
            }
            if (empty($event->created_at)) {
                $event->created_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
