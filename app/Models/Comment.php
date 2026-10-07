<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'body',
        'likes_count',
        'replies_count',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'replies_count' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->oldest();
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $viewerReaction = null;
        if ($viewer) {
            $userReact = $this->reactions->firstWhere('user_id', $viewer->id);
            $viewerReaction = $userReact ? $userReact->type : null;
        }

        $authorData = [
            'id' => $this->user->id,
            'username' => $this->user->username,
            'name' => $this->user->name,
            'avatar_url' => $this->user->profile?->avatar_url,
        ];

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'content' => $this->body,
            'author' => $authorData,
            'user' => $authorData,
            'counters' => [
                'likes' => $this->likes_count,
                'replies' => $this->replies_count,
            ],
            'viewer_reaction' => $viewerReaction,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
