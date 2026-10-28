<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAnnouncement extends Model
{
    use HasFactory;

    protected $table = 'platform_announcements';

    protected $fillable = [
        'title',
        'message',
        'type',
        'target_audience',
        'is_active',
        'scheduled_at',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
