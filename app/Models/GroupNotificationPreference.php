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
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
