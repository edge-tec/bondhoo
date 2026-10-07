<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FriendList extends Model
{
    use HasFactory;

    public const TYPE_CUSTOM = 'custom';

    public const TYPE_FAMILY = 'family';

    public const TYPE_WORK = 'work';

    public const TYPE_SCHOOL = 'school';

    public const TYPE_CLOSE_FRIENDS = 'close_friends';

    public const TYPE_FAVORITES = 'favorites';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'type',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FriendList $list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->name).'-'.Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friend_list_members', 'friend_list_id', 'friend_id')
            ->withTimestamps();
    }

    public function memberRecords(): HasMany
    {
        return $this->hasMany(FriendListMember::class);
    }
}
