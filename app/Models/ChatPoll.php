<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatPoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'message_id',
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

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ChatPollOption::class, 'poll_id')->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ChatPollVote::class, 'poll_id');
    }

    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    public function canVote(): bool
    {
        return ! $this->is_closed && ! $this->isExpired();
    }

    public function toResponseArray(?User $viewer = null): array
    {
        $myVotedOptionIds = [];
        if ($viewer) {
            $myVotedOptionIds = $this->votes()
                ->where('user_id', $viewer->id)
                ->pluck('poll_option_id')
                ->toArray();
        }

        $totalVotes = (int) $this->total_votes_count;

        $optionsList = $this->options->map(function (ChatPollOption $option) use ($totalVotes, $myVotedOptionIds) {
            $votesCount = (int) $option->votes_count;
            $percentage = $totalVotes > 0 ? round(($votesCount / $totalVotes) * 100, 1) : 0;

            $voters = [];
            if (! $this->is_anonymous && $option->relationLoaded('votes')) {
                foreach ($option->votes as $vote) {
                    if ($vote->relationLoaded('user') && $vote->user) {
                        $voters[] = [
                            'id' => $vote->user->id,
                            'name' => $vote->user->name,
                            'avatar_url' => $vote->user->profile?->avatar_url,
                        ];
                    }
                }
            }

            return [
                'id' => $option->id,
                'poll_id' => $option->poll_id,
                'option_text' => $option->option_text,
                'votes_count' => $votesCount,
                'percentage' => $percentage,
                'is_voted_by_me' => in_array($option->id, $myVotedOptionIds, true),
                'voters' => $voters,
            ];
        })->toArray();

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'message_id' => $this->message_id,
            'creator_id' => $this->creator_id,
            'creator_name' => $this->creator?->name ?? 'User',
            'question' => $this->question,
            'is_multiple_choice' => (bool) $this->is_multiple_choice,
            'is_anonymous' => (bool) $this->is_anonymous,
            'can_change_vote' => (bool) $this->can_change_vote,
            'is_closed' => (bool) ($this->is_closed || $this->isExpired()),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'total_votes_count' => $totalVotes,
            'has_voted' => ! empty($myVotedOptionIds),
            'my_voted_option_ids' => $myVotedOptionIds,
            'options' => $optionsList,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
