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
        'friend_accepted_alerts',
        'message_alerts',
        'comment_alerts',
        'mention_alerts',
        'like_alerts',
        'share_alerts',
        'follower_alerts',
        'page_alerts',
        'group_alerts',
        'security_alerts',
        'login_alerts',
    ];

    protected function casts(): array
    {
        return [
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'friend_request_alerts' => 'boolean',
            'friend_accepted_alerts' => 'boolean',
            'message_alerts' => 'boolean',
            'comment_alerts' => 'boolean',
            'mention_alerts' => 'boolean',
            'like_alerts' => 'boolean',
            'share_alerts' => 'boolean',
            'follower_alerts' => 'boolean',
            'page_alerts' => 'boolean',
            'group_alerts' => 'boolean',
            'security_alerts' => 'boolean',
            'login_alerts' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
