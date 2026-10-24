<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupNotificationPreference extends Model
{
    use HasFactory;

    public const PREF_ALL = 'all';

    public const PREF_HIGHLIGHTS = 'highlights';

    public const PREF_FRIENDS = 'friends';

    public const PREF_ADMIN_ONLY = 'admin_only';

    public const PREF_OFF = 'off';

    protected $fillable = [
        'group_id',
        'user_id',
        'preference',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a specific notification type is allowed for the user in this group.
     */
    public static function allowsNotification(int $userId, int $groupId, string $type): bool
    {
        $pref = static::where('user_id', $userId)->where('group_id', $groupId)->first();
        if (! $pref) {
            return true;
        }

        if ($pref->preference === self::PREF_OFF) {
            return false;
        }

        if ($pref->settings && isset($pref->settings[$type])) {
            return (bool) $pref->settings[$type];
        }

        return true;
    }
}
