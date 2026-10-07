<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupDiscussion extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_DISCUSSION = 'discussion';

    public const TYPE_QUESTION = 'question';

    public const TYPE_FEEDBACK = 'feedback';

    protected $fillable = [
        'group_id',
        'user_id',
        'title',
        'content',
        'type',
        'is_solved',
        'accepted_answer_id',
        'views_count',
        'replies_count',
        'is_pinned',
        'is_locked',
        'body',
        'author_id',
    ];

    protected $appends = ['body', 'author_id'];

    public function getBodyAttribute(): ?string
    {
        return $this->attributes['content'] ?? null;
    }

    public function setBodyAttribute(?string $value): void
    {
        $this->attributes['content'] = $value;
    }

    public function getAuthorIdAttribute(): ?int
    {
        return $this->attributes['user_id'] ?? null;
    }

    public function setAuthorIdAttribute(?int $value): void
    {
        $this->attributes['user_id'] = $value;
    }

    protected function casts(): array
    {
        return [
            'is_solved' => 'boolean',
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'views_count' => 'integer',
            'replies_count' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(GroupDiscussionReply::class, 'discussion_id')->whereNull('parent_id')->latest();
    }

    public function allReplies(): HasMany
    {
        return $this->hasMany(GroupDiscussionReply::class, 'discussion_id');
    }

    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(GroupDiscussionReply::class, 'accepted_answer_id');
    }

    public function toResponseArray(?User $viewer = null): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'is_solved' => $this->is_solved,
            'accepted_answer_id' => $this->accepted_answer_id,
            'views_count' => $this->views_count,
            'replies_count' => $this->replies_count,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'username' => $this->author?->username,
                'avatar_url' => $this->author?->profile?->avatar_url,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
