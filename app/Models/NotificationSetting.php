<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
        'friend_request_alerts',
        'comment_alerts',
        'mention_alerts',
        'security_alerts',
    ];

    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'friend_request_alerts' => 'boolean',
            'comment_alerts' => 'boolean',
            'mention_alerts' => 'boolean',
            'security_alerts' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
