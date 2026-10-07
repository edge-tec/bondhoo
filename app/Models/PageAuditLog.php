<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'actor_id',
        'action',
        'target_type',
        'target_id',
        'previous_state',
        'new_state',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'new_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
