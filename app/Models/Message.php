<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * মেসেজ মডেল:
 * চ্যাটে পাঠানো মেসেজের বিষয়বস্তু, টাইপ, ডেলিভারি স্ট্যাটাস ও মিডিয়া সংরক্ষণ করে।
 */
class Message extends Model
{
    use HasFactory;

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_SEEN = 'seen';

    protected $fillable = [
        'conversation_id',
        'reply_to_message_id',
        'sender_id',
        'type',
        'body',
        'delivery_status',
        'sent_at',
        'delivered_at',
        'read_at',
        'metadata',
        'version',
        'is_deleted_for_everyone',
        'deleted_at',
        'deleted_by',
        'is_forwarded',
        'is_edited',
        'edited_at',
        'edit_history',
    ];

    protected $attributes = [
        'version' => 1,
        'type' => 'text',
        'delivery_status' => self::STATUS_SENT,
        'is_edited' => false,
        'is_forwarded' => false,
        'is_deleted_for_everyone' => false,
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
            'version' => 'integer',
            'metadata' => 'array',
            'edit_history' => 'array',
            'is_deleted_for_everyone' => 'boolean',
            'is_forwarded' => 'boolean',
            'is_edited' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function getIsDeletedAttribute(): bool
    {
        return (bool) $this->is_deleted_for_everyone;
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function userDeletions(): HasMany
    {
        return $this->hasMany(MessageUserDeletion::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function pinnedRecord(): HasOne
    {
        return $this->hasOne(PinnedMessage::class);
    }

    public function savedRecords(): HasMany
    {
        return $this->hasMany(SavedMessage::class);
    }

    /**
     * মেসেজের স্ট্যান্ডার্ড রেসপন্স ফরম্যাট।
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $isDeleted = (bool) $this->is_deleted_for_everyone;
        $body = $isDeleted ? 'This message was deleted.' : $this->body;

        // Reply preview
        $replyPreview = null;
        if ($this->replyTo && ! $isDeleted) {
            $replySender = $this->replyTo->sender;
            $replyPreview = [
                'id' => $this->replyTo->id,
                'sender_name' => $replySender?->name ?? $replySender?->username ?? 'User',
                'body' => $this->replyTo->is_deleted_for_everyone ? 'This message was deleted.' : $this->replyTo->body,
                'type' => $this->replyTo->type,
            ];
        }

        // Reactions calculation
        $reactionCounts = [];
        $reactionList = [];
        $myReaction = null;

        if ($this->relationLoaded('reactions')) {
            foreach ($this->reactions as $rx) {
                $reactionCounts[$rx->reaction] = ($reactionCounts[$rx->reaction] ?? 0) + 1;
                $reactionList[] = [
                    'reaction' => $rx->reaction,
                    'user_id' => $rx->user_id,
                    'user_name' => $rx->user?->name ?? $rx->user?->username ?? 'User',
                ];
                if ($viewer && (int) $rx->user_id === (int) $viewer->id) {
                    $myReaction = $rx->reaction;
                }
            }
        }

        $mediaList = $isDeleted ? [] : $this->media->map(fn ($m) => $m->toResponseArray())->toArray();

        $metadata = $this->metadata ?? [];
        if ($this->type === 'voice' || ! empty($metadata['audio_url'])) {
            $metadata['audio_stream_url'] = '/api/v1/messages/'.$this->id.'/voice';
            $metadata['audio_url'] = '/api/v1/messages/'.$this->id.'/voice';
        }

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'reply_to_message_id' => $this->reply_to_message_id,
            'reply_preview' => $replyPreview,
            'reply_to' => $replyPreview,
            'sender' => [
                'id' => $this->sender?->id,
                'name' => $this->sender?->name,
                'username' => $this->sender?->username,
                'avatar_url' => $this->sender?->profile?->avatar_url,
            ],
            'type' => $this->type,
            'body' => $body,
            'delivery_status' => $this->delivery_status,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'is_mine' => $viewer ? (int) $this->sender_id === (int) $viewer->id : false,
            'is_deleted' => $isDeleted,
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'deleted_by' => $this->deleted_by,
            'version' => $this->version ?? 1,
            'is_forwarded' => (bool) $this->is_forwarded,
            'is_edited' => (bool) $this->is_edited,
            'is_pinned' => $this->relationLoaded('pinnedRecord') ? (bool) $this->pinnedRecord : false,
            'is_saved' => $viewer ? ($this->relationLoaded('savedRecords') ? $this->savedRecords->contains('user_id', $viewer->id) : false) : false,
            'edited_at' => $this->edited_at?->toIso8601String(),
            'metadata' => $metadata,
            'media' => $mediaList,
            'attachments' => $mediaList,
            'attachment' => $mediaList[0] ?? null,
            'is_image' => ! $isDeleted && ! empty($mediaList) && collect($mediaList)->contains(fn ($m) => str_starts_with($m['mime_type'] ?? '', 'image/')),
            'reactions' => [
                'counts' => $reactionCounts,
                'total' => array_sum($reactionCounts),
                'my_reaction' => $myReaction,
                'list' => $reactionList,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
