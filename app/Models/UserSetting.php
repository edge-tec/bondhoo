<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'who_can_see_posts',
        'who_can_send_friend_requests',
        'who_can_follow',
        'who_can_message',
        'who_can_see_friends',
        'find_by_email',
        'find_by_phone',
        'story_visibility',
        'is_profile_locked',
        'has_avatar_guard',
        'notification_email',
        'notification_push',
        'notification_sms',
        'messenger_sounds_enabled',
        'incoming_call_sound_enabled',
        'incoming_call_sound',
        'incoming_call_volume',
        'incoming_message_sound_enabled',
        'incoming_message_sound',
        'incoming_message_volume',
        'outgoing_message_sound_enabled',
        'outgoing_message_sound',
        'outgoing_message_volume',
        'custom_call_sound_path',
        'custom_incoming_msg_sound_path',
        'custom_outgoing_msg_sound_path',
        'dark_mode',
        'language',
    ];

    protected function casts(): array
    {
        return [
            'is_profile_locked' => 'boolean',
            'has_avatar_guard' => 'boolean',
            'notification_email' => 'boolean',
            'notification_push' => 'boolean',
            'notification_sms' => 'boolean',
            'messenger_sounds_enabled' => 'boolean',
            'incoming_call_sound_enabled' => 'boolean',
            'incoming_call_volume' => 'integer',
            'incoming_message_sound_enabled' => 'boolean',
            'incoming_message_volume' => 'integer',
            'outgoing_message_sound_enabled' => 'boolean',
            'outgoing_message_volume' => 'integer',
            'dark_mode' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
