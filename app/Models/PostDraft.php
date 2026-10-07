<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'page_id',
        'content',
        'audience',
        'type',
        'location',
        'feeling_activity',
        'poll_data',
        'link_preview',
        'background_style',
        'is_ai_generated',
        'content_warning',
        'comments_disabled',
        'tagged_user_ids',
        'collaborator_id',
        'media_ids',
        'media_meta',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'poll_data' => 'array',
            'link_preview' => 'array',
            'tagged_user_ids' => 'array',
            'media_ids' => 'array',
            'media_meta' => 'array',
            'is_ai_generated' => 'boolean',
            'comments_disabled' => 'boolean',
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collaborator_id');
    }

    /**
     * Standardized response array for draft.
     */
    public function toResponseArray(): array
    {
        // Load media if media_ids are present
        $mediaList = [];
        if (! empty($this->media_ids)) {
            $mediaList = Media::whereIn('id', $this->media_ids)
                ->where('user_id', $this->user_id)
                ->get()
                ->map(fn (Media $m) => $m->toResponseArray())
                ->values();
        }

        $taggedUsers = [];
        if (! empty($this->tagged_user_ids)) {
            $taggedUsers = User::whereIn('id', $this->tagged_user_ids)
                ->with('profile')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar_url' => $u->profile?->avatar_url,
                ])
                ->values();
        }

        return [
            'id' => $this->id,
            'content' => $this->content,
            'audience' => $this->audience,
            'type' => $this->type,
            'location' => $this->location,
            'feeling_activity' => $this->feeling_activity,
            'poll_data' => $this->poll_data,
            'link_preview' => $this->link_preview,
            'background_style' => $this->background_style,
            'is_ai_generated' => (bool) $this->is_ai_generated,
            'content_warning' => $this->content_warning,
            'comments_disabled' => (bool) $this->comments_disabled,
            'tagged_users' => $taggedUsers,
            'collaborator' => $this->collaborator ? [
                'id' => $this->collaborator->id,
                'name' => $this->collaborator->name,
                'username' => $this->collaborator->username,
                'avatar_url' => $this->collaborator->profile?->avatar_url,
            ] : null,
            'media' => $mediaList,
            'media_ids' => $this->media_ids ?? [],
            'media_meta' => $this->media_meta ?? [],
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'group_id' => $this->group_id,
            'page_id' => $this->page_id,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
