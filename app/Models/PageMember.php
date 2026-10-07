<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'role',
        'custom_permissions',
        'status',
        'invited_by',
        'invitation_token',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'custom_permissions' => 'array',
            'joined_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
