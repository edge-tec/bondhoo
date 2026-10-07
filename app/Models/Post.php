<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'group_id',
        'page_id',
        'collaborator_id',
        'collaborator_status',
        'tagged_user_ids',
        'content',
        'audience',
        'type',
        'background_style',
        'is_ai_generated',
        'content_warning',
        'media_meta',
        'shared_to_story',
        'location',
        'feeling_activity',
        'link_preview',
        'poll_data',
        'is_pinned',
        'comments_disabled',
        'likes_count',
        'comments_count',
        'shares_count',
        'status',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'link_preview' => 'array',
            'poll_data' => 'array',
            'tagged_user_ids' => 'array',
            'media_meta' => 'array',
            'is_ai_generated' => 'boolean',
            'shared_to_story' => 'boolean',
            'is_pinned' => 'boolean',
            'comments_disabled' => 'boolean',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'shares_count' => 'integer',
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->latest();
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(PostAnalytics::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collaborator_id');
    }

    /**
     * Standardized serialized representation of Post.
     */
    public function toResponseArray(?User $viewer = null): array
    {
        $viewerReaction = null;
        if ($viewer) {
            $userReact = $this->reactions->firstWhere('user_id', $viewer->id);
            $viewerReaction = $userReact ? $userReact->type : null;
        }

        $collaboratorData = null;
        if ($this->collaborator_id) {
            $collab = $this->relationLoaded('collaborator') ? $this->collaborator : User::find($this->collaborator_id);
            if ($collab) {
                $collaboratorData = [
                    'id' => $collab->id,
                    'name' => $collab->name,
                    'username' => $collab->username,
                    'avatar_url' => $collab->profile?->avatar_url,
                    'status' => $this->collaborator_status ?? 'pending',
                ];
            }
        }

        $taggedUsersData = [];
        if (! empty($this->tagged_user_ids)) {
            $taggedUsersData = User::whereIn('id', $this->tagged_user_ids)
                ->with('profile')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar_url' => $u->profile?->avatar_url,
                ])
                ->values()
                ->toArray();
        }

        return [
            'id' => $this->id,
            'author' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'name' => $this->user->name,
                'avatar_url' => $this->user->profile?->avatar_url,
            ],
            'content' => $this->content,
            'audience' => $this->audience,
            'type' => $this->type,
            'background_style' => $this->background_style,
            'is_ai_generated' => (bool) $this->is_ai_generated,
            'content_warning' => $this->content_warning,
            'media_meta' => $this->media_meta,
            'shared_to_story' => (bool) $this->shared_to_story,
            'collaborator' => $collaboratorData,
            'tagged_users' => $taggedUsersData,
            'location' => $this->location,
            'feeling_activity' => $this->feeling_activity,
            'link_preview' => $this->link_preview,
            'poll_data' => $this->poll_data,
            'is_pinned' => $this->is_pinned,
            'comments_disabled' => $this->comments_disabled,
            'status' => $this->status ?? 'published',
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'counters' => [
                'likes' => $this->likes_count,
                'comments' => $this->comments_count,
                'shares' => $this->shares_count,
            ],
            'media' => $this->media->map(fn (Media $m) => $m->toResponseArray())->values(),
            'group_id' => $this->group_id,
            'page_id' => $this->page_id,
            'viewer_reaction' => $viewerReaction,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
