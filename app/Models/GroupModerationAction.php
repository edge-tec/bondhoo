<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupModerationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'moderator_id',
        'target_user_id',
        'action',
        'target_type',
        'target_id',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'moderator' => [
                'id' => $this->moderator?->id,
                'name' => $this->moderator?->name,
                'username' => $this->moderator?->username,
            ],
            'target_user' => $this->targetUser ? [
                'id' => $this->targetUser->id,
                'name' => $this->targetUser->name,
                'username' => $this->targetUser->username,
            ] : null,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'reason' => $this->reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
