<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * কনভার্সন পার্টিসিপেন্ট মডেল:
 * চ্যাটে প্রতিটি অংশগ্রহণকারীর রোল এবং রিড রিসিট (সর্বশেষ পড়া মেসেজ) ট্র্যাক করে।
 */
class ConversationParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'last_read_message_id',
        'last_read_at',
        'is_muted',
        'is_pinned',
        'is_archived',
        'muted_until',
        'is_request',
        'request_status',
        'cleared_at',
        'draft_message',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'muted_until' => 'datetime',
        'cleared_at' => 'datetime',
        'is_muted' => 'boolean',
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'is_request' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }
}
