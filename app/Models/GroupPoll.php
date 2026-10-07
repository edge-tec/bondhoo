<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupPoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'post_id',
        'creator_id',
        'question',
        'is_multiple_choice',
        'is_anonymous',
        'can_change_vote',
        'ends_at',
        'is_closed',
        'total_votes_count',
    ];

    protected function casts(): array
    {
        return [
            'is_multiple_choice' => 'boolean',
            'is_anonymous' => 'boolean',
            'can_change_vote' => 'boolean',
            'is_closed' => 'boolean',
            'total_votes_count' => 'integer',
            'ends_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(GroupPollOption::class, 'poll_id')->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(GroupPollVote::class, 'poll_id');
    }

    public function isExpired(): bool
    {
        return $this->is_closed || ($this->ends_at && $this->ends_at->isPast());
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $viewerVotedOptionIds = [];
        if ($viewer) {
            $viewerVotedOptionIds = $this->votes()
                ->where('user_id', $viewer->id)
                ->pluck('poll_option_id')
                ->all();
        }

        return [
            'id' => $this->id,
            'group_id' => $this->group_id,
            'question' => $this->question,
            'is_multiple_choice' => $this->is_multiple_choice,
            'is_anonymous' => $this->is_anonymous,
            'can_change_vote' => $this->can_change_vote,
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_closed' => $this->isExpired(),
            'total_votes_count' => $this->total_votes_count,
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
                'username' => $this->creator?->username,
            ],
            'options' => $this->options->map(fn (GroupPollOption $opt) => [
                'id' => $opt->id,
                'option_text' => $opt->option_text,
                'votes_count' => $opt->votes_count,
                'percentage' => $this->total_votes_count > 0 ? round(($opt->votes_count / $this->total_votes_count) * 100, 1) : 0,
                'has_voted' => in_array($opt->id, $viewerVotedOptionIds, true),
            ])->values(),
            'viewer_has_voted' => ! empty($viewerVotedOptionIds),
            'viewer_voted_option_ids' => $viewerVotedOptionIds,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
