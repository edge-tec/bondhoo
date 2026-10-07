<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'assigned_to',
        'status',
        'priority',
        'last_message_at',
        'unread_page_count',
        'unread_user_count',
        'labels',
    ];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'last_message_at' => 'datetime',
            'unread_page_count' => 'integer',
            'unread_user_count' => 'integer',
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

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PageMessage::class, 'conversation_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PageConversationNote::class, 'conversation_id');
    }
}
