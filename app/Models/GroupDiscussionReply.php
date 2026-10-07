<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupDiscussionReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'discussion_id',
        'user_id',
        'parent_id',
        'content',
        'is_accepted_answer',
        'upvotes_count',
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
            'is_accepted_answer' => 'boolean',
            'upvotes_count' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(GroupDiscussion::class, 'discussion_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GroupDiscussionReply::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(GroupDiscussionReply::class, 'parent_id')->oldest();
    }

    public function toResponseArray(): array
    {
        return [
            'id' => $this->id,
            'discussion_id' => $this->discussion_id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            'is_accepted_answer' => $this->is_accepted_answer,
            'upvotes_count' => $this->upvotes_count,
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'username' => $this->author?->username,
                'avatar_url' => $this->author?->profile?->avatar_url,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
