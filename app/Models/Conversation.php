<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * কনভার্সন মডেল:
 * ডিরেক্ট বা গ্রুপ চ্যাট রিপ্রেজেন্ট করে।
 */
class Conversation extends Model
{
    use HasFactory;

    public const TYPE_DIRECT = 'direct';

    public const TYPE_GROUP = 'group';

    public const TYPE_SAVED = 'saved';

    protected $fillable = [
        'type',
        'title',
        'avatar_url',
        'description',
        'settings',
        'creator_id',
        'last_message_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_message_id', 'last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function pinnedMessages(): HasMany
    {
        return $this->hasMany(PinnedMessage::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isSaved(): bool
    {
        return $this->type === self::TYPE_SAVED;
    }

    /**
     * নির্দিষ্ট ইউজার এই কনভার্সনের সদস্য কিনা যাচাই করা।
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * ১-অন-১ চ্যাটে অপর ব্যবহারকারীকে খুঁজে বের করা।
     */
    public function getOtherParticipant(int $currentUserId): ?User
    {
        return $this->users->firstWhere('id', '!==', $currentUserId);
    }

    /**
     * Get the last visible message for a specific user, taking into account
     * clear history (cleared_at) and user-specific deletions (Delete for Me).
     */
    public function getLastVisibleMessageForUser(User $user): ?Message
    {
        $participant = $this->participants->firstWhere('user_id', $user->id);

        $query = $this->messages()
            ->whereDoesntHave('userDeletions', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($participant && $participant->cleared_at) {
            $query->where('created_at', '>', $participant->cleared_at);
        }

        return $query->latest('id')->first();
    }

    /**
     * ইনবক্স তালিকার জন্য সুন্দর ও স্ট্যান্ডার্ড অ্যারে রেসপন্স।
     */
    public function toResponseArray(?User $viewer = null, int $unreadCount = 0): array
    {
        $displayTitle = $this->title;
        $displayAvatar = $this->avatar_url;
        $otherUser = null;
        $viewerParticipant = null;

        if ($viewer) {
            $viewerParticipant = $this->participants->firstWhere('user_id', $viewer->id);
        }

        if ($this->isSaved()) {
            $displayTitle = 'Saved Messages';
        } elseif ($this->isDirect() && $viewer) {
            $otherUser = $this->getOtherParticipant($viewer->id);
            if ($otherUser) {
                $displayTitle = $otherUser->name ?? $otherUser->username;
                $displayAvatar = $otherUser->profile?->avatar_url;
            }
        }

        $isMuted = false;
        if ($viewerParticipant) {
            $isMuted = (bool) $viewerParticipant->is_muted;
            if ($viewerParticipant->muted_until && $viewerParticipant->muted_until->isFuture()) {
                $isMuted = true;
            }
        }

        $effectiveLastMessage = null;
        if ($viewer) {
            $effectiveLastMessage = $this->getLastVisibleMessageForUser($viewer);
        } else {
            $effectiveLastMessage = $this->lastMessage;
        }

        $lastMessageData = null;
        if ($effectiveLastMessage) {
            $lastMessageData = [
                'id' => $effectiveLastMessage->id,
                'body' => $effectiveLastMessage->is_deleted_for_everyone ? 'This message was deleted.' : $effectiveLastMessage->body,
                'sender_id' => $effectiveLastMessage->sender_id,
                'type' => $effectiveLastMessage->type,
                'delivery_status' => $effectiveLastMessage->delivery_status,
                'sent_at' => $effectiveLastMessage->sent_at?->toIso8601String(),
                'is_edited' => (bool) $effectiveLastMessage->is_edited,
                'is_deleted' => (bool) $effectiveLastMessage->is_deleted_for_everyone,
                'version' => $effectiveLastMessage->version ?? 1,
            ];
        }

        $effectiveLastMessageAt = $effectiveLastMessage
            ? $effectiveLastMessage->created_at?->toIso8601String()
            : ($viewer ? null : $this->last_message_at?->toIso8601String());

        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_saved' => $this->isSaved(),
            'title' => $displayTitle,
            'avatar_url' => $displayAvatar,
            'description' => $this->description,
            'settings' => $this->settings ?? [
                'can_send_messages' => 'all',
                'can_add_members' => 'all',
                'can_edit_info' => 'admins',
            ],
            'other_user' => $otherUser ? [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'username' => $otherUser->username,
                'avatar_url' => $otherUser->profile?->avatar_url,
                'bio' => $otherUser->profile?->bio,
            ] : null,
            'last_message' => $lastMessageData,
            'last_message_at' => $effectiveLastMessageAt,
            'unread_count' => $unreadCount,
            'is_pinned' => $viewerParticipant ? (bool) $viewerParticipant->is_pinned : false,
            'is_archived' => $viewerParticipant ? (bool) $viewerParticipant->is_archived : false,
            'is_muted' => $isMuted,
            'muted_until' => $viewerParticipant?->muted_until?->toIso8601String(),
            'is_request' => $viewerParticipant ? (bool) $viewerParticipant->is_request : false,
            'request_status' => $viewerParticipant ? $viewerParticipant->request_status : 'accepted',
            'draft_message' => $viewerParticipant ? $viewerParticipant->draft_message : null,
            'my_role' => $viewerParticipant ? $viewerParticipant->role : 'member',
            'participants_count' => $this->participants->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
