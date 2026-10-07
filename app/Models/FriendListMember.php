<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendListMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'friend_list_id',
        'friend_id',
    ];

    public function list(): BelongsTo
    {
        return $this->belongsTo(FriendList::class, 'friend_list_id');
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}
