<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsernameHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
