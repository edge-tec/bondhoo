<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'description',
        'ip_address',
        'device',
        'metadata',
    ];

    protected $appends = [
        'activity_type',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function getActivityTypeAttribute(): ?string
    {
        return $this->action_type;
    }

    public function setActivityTypeAttribute(?string $value): void
    {
        $this->attributes['action_type'] = $value;
    }

    public function getUserAgentAttribute(): ?string
    {
        return $this->device;
    }

    public function setUserAgentAttribute(?string $value): void
    {
        $this->attributes['device'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
