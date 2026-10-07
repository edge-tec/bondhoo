<?php

namespace App\Services;

use App\Jobs\FeedUpdateJob;
use App\Models\AuditLog;
use App\Models\Group;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostDraft;
use App\Models\Story;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\QueueServiceInterface;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PostService
{
    public function __construct(
        protected QueueServiceInterface $queueService,
        protected ?NotificationServiceInterface $notificationService = null
    ) {
        if (! $this->notificationService && app()->bound(NotificationServiceInterface::class)) {
            $this->notificationService = app(NotificationServiceInterface::class);
        }
    }

    public function createPost(User $user, array $data, ?string $ip = null, ?string $userAgent = null): Post
    {
        return DB::transaction(function () use ($user, $data, $ip, $userAgent) {
            // Determine status and scheduled time
            $scheduledAt = null;
            $status = $data['status'] ?? 'published';

            if (! empty($data['scheduled_at'])) {
                $parsedDate = Carbon::parse($data['scheduled_at']);
                if ($parsedDate->isFuture()) {
                    $scheduledAt = $parsedDate;
                    $status = 'scheduled';
                }
            }

            // Handle group permissions if group_id is provided
            $groupId = $data['group_id'] ?? null;
            if ($groupId) {
                $group = Group::findOrFail($groupId);
                // Check if user is a member
                if (! $group->isMember($user->id)) {
                    throw new AuthorizationException('You must be a member of this group to post.');
                }
                if ($status === 'published') {
                    $group->increment('posts_count');
                }
            }

            // Clean poll options if present
            $pollData = $data['poll_data'] ?? null;
            if (is_array($pollData) && ! empty($pollData['options'])) {
                $cleanOptions = [];
                $optId = 1;
                foreach ($pollData['options'] as $opt) {
                    $optText = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;
                    if (trim($optText) !== '') {
                        $cleanOptions[] = [
                            'id' => $opt['id'] ?? $optId++,
                            'text' => trim($optText),
                            'votes_count' => (int) ($opt['votes_count'] ?? 0),
                        ];
                    }
                }
                $pollData['options'] = $cleanOptions;
                $pollData['voted_user_ids'] = $pollData['voted_user_ids'] ?? [];
                if (! empty($pollData['duration_hours'])) {
                    $pollData['expires_at'] = now()->addHours((int) $pollData['duration_hours'])->toIso8601String();
                }
            }

            // Clean collaborator
            $collaboratorId = $data['collaborator_id'] ?? null;
            $collaboratorStatus = 'pending';
            if ($collaboratorId && (int) $collaboratorId === (int) $user->id) {
                $collaboratorId = null;
            }

            // Clean tagged users
            $taggedUserIds = array_values(array_unique(array_filter(
                (array) ($data['tagged_user_ids'] ?? []),
                fn ($id) => is_numeric($id) && (int) $id !== (int) $user->id
            )));

            $sharedToStory = ! empty($data['share_to_story']) || ! empty($data['shared_to_story']);

            $post = Post::create([
                'user_id' => $user->id,
                'group_id' => $groupId,
                'page_id' => $data['page_id'] ?? null,
                'collaborator_id' => $collaboratorId,
                'collaborator_status' => $collaboratorId ? $collaboratorStatus : 'pending',
                'tagged_user_ids' => ! empty($taggedUserIds) ? $taggedUserIds : null,
                'content' => $data['content'] ?? null,
                'audience' => $data['audience'] ?? 'public',
                'type' => $data['type'] ?? 'text',
                'background_style' => $data['background_style'] ?? null,
                'is_ai_generated' => (bool) ($data['is_ai_generated'] ?? false),
                'content_warning' => $data['content_warning'] ?? null,
                'media_meta' => $data['media_meta'] ?? null,
                'shared_to_story' => $sharedToStory,
                'location' => $data['location'] ?? null,
                'feeling_activity' => $data['feeling_activity'] ?? null,
                'link_preview' => $data['link_preview'] ?? null,
                'poll_data' => $pollData,
                'is_pinned' => false,
                'comments_disabled' => (bool) ($data['comments_disabled'] ?? false),
                'status' => $status,
                'scheduled_at' => $scheduledAt,
            ]);

            // Attach Media if IDs are passed
            if (! empty($data['media_ids'])) {
                Media::whereIn('id', $data['media_ids'])
                    ->where('user_id', $user->id)
                    ->update([
                        'mediable_type' => Post::class,
                        'mediable_id' => $post->id,
                    ]);
            }

            // Cross-post to story if requested
            if ($sharedToStory) {
                try {
                    $storyText = $post->content ?: 'Shared from post';
                    Story::create([
                        'user_id' => $user->id,
                        'type' => Story::TYPE_TEXT,
                        'content' => mb_substr($storyText, 0, 500),
                        'background_color' => '#1877F2',
                        'privacy' => in_array($post->audience, ['only_me', 'friends'], true) ? $post->audience : 'public',
                        'expires_at' => now()->addHours(24),
                        'views_count' => 0,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Could not auto-cross-post to story: {$e->getMessage()}");
                }
            }

            // Send notification to collaborator if invited
            if ($collaboratorId && $this->notificationService) {
                try {
                    $this->notificationService->send((int) $collaboratorId, 'post_collaboration_invite', [
                        'post_id' => $post->id,
                        'author_id' => $user->id,
                        'author_name' => $user->name,
                        'message' => "{$user->name} আপনাকে একটি পোস্টে সহ-লেখক হিসেবে যুক্ত হওয়ার আমন্ত্রণ জানিয়েছেন।",
                        'action_url' => "/posts/{$post->id}",
                    ]);
                } catch (\Throwable $e) {
                    Log::info("Collaboration notification failed: {$e->getMessage()}");
                }
            }

            // Send notification to tagged users
            if (! empty($taggedUserIds) && $this->notificationService) {
                foreach ($taggedUserIds as $taggedId) {
                    try {
                        $this->notificationService->send((int) $taggedId, 'post_tagged', [
                            'post_id' => $post->id,
                            'author_id' => $user->id,
                            'author_name' => $user->name,
                            'message' => "{$user->name} আপনাকে একটি পোস্টে ট্যাগ করেছেন।",
                            'action_url' => "/posts/{$post->id}",
                        ]);
                    } catch (\Throwable $e) {
                        Log::info("Tag notification failed: {$e->getMessage()}");
                    }
                }
            }

            // Clear any active draft for this user
            try {
                PostDraft::where('user_id', $user->id)->delete();
            } catch (\Throwable $e) {
                // Ignore draft clearing error
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'post.created',
                'entity_type' => Post::class,
                'entity_id' => $post->id,
                'new_values' => ['id' => $post->id, 'type' => $post->type, 'status' => $post->status],
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            // Dispatch background FeedUpdateJob only if published immediately
            if ($status === 'published') {
                $this->queueService->dispatch(new FeedUpdateJob($post->id));
            }

            return $post->load(['user.profile', 'media', 'reactions', 'collaborator.profile']);
        });
    }

    public function updatePost(User $user, Post $post, array $data): Post
    {
        if ($post->user_id !== $user->id && ! $user->hasPermission('posts.moderate')) {
            throw new AuthorizationException('Unauthorized to modify this post.');
        }

        $post->update(array_filter([
            'content' => $data['content'] ?? $post->content,
            'audience' => $data['audience'] ?? $post->audience,
            'location' => $data['location'] ?? $post->location,
            'feeling_activity' => $data['feeling_activity'] ?? $post->feeling_activity,
            'background_style' => $data['background_style'] ?? $post->background_style,
            'content_warning' => $data['content_warning'] ?? $post->content_warning,
            'comments_disabled' => $data['comments_disabled'] ?? $post->comments_disabled,
        ], fn ($val) => $val !== null));

        return $post->fresh(['user.profile', 'media', 'reactions', 'collaborator.profile']);
    }

    public function deletePost(User $user, Post $post): void
    {
        if ($post->user_id !== $user->id && ! $user->hasPermission('posts.delete')) {
            throw new AuthorizationException('Unauthorized to delete this post.');
        }

        $post->delete();
    }

    public function togglePin(User $user, Post $post): bool
    {
        if ($post->user_id !== $user->id) {
            throw new AuthorizationException('Only post author can pin their post.');
        }

        $newPinnedState = ! $post->is_pinned;
        $post->update(['is_pinned' => $newPinnedState]);

        return $newPinnedState;
    }

    public function toggleComments(User $user, Post $post): bool
    {
        if ($post->user_id !== $user->id && ! $user->hasPermission('posts.moderate')) {
            throw new AuthorizationException('Unauthorized to change comment settings on this post.');
        }

        $newState = ! $post->comments_disabled;
        $post->update(['comments_disabled' => $newState]);

        return $newState;
    }

    /**
     * Cast vote on a post's poll.
     */
    public function votePoll(User $user, Post $post, int $optionId): array
    {
        $poll = $post->poll_data;
        if (! is_array($poll) || empty($poll['options'])) {
            throw new InvalidArgumentException('This post does not contain an active poll.');
        }

        if (! empty($poll['expires_at']) && Carbon::parse($poll['expires_at'])->isPast()) {
            throw new InvalidArgumentException('This poll has expired.');
        }

        $votedMap = (array) ($poll['voted_user_ids'] ?? []);
        $previousOptionId = $votedMap[$user->id] ?? null;

        $options = $poll['options'];
        $optionFound = false;

        foreach ($options as &$opt) {
            // Decrement previous vote if changing
            if ($previousOptionId !== null && (int) $opt['id'] === (int) $previousOptionId) {
                $opt['votes_count'] = max(0, ((int) ($opt['votes_count'] ?? 0)) - 1);
            }

            // Increment new vote
            if ((int) $opt['id'] === $optionId) {
                $opt['votes_count'] = ((int) ($opt['votes_count'] ?? 0)) + 1;
                $optionFound = true;
            }
        }
        unset($opt);

        if (! $optionFound) {
            throw new InvalidArgumentException('Selected option does not exist in this poll.');
        }

        $votedMap[$user->id] = $optionId;
        $poll['options'] = $options;
        $poll['voted_user_ids'] = $votedMap;
        $poll['total_votes'] = array_sum(array_column($options, 'votes_count'));

        $post->update(['poll_data' => $poll]);

        return $poll;
    }

    /**
     * Respond to a post collaboration invitation.
     */
    public function respondCollaborator(User $user, Post $post, string $action): Post
    {
        if ((int) $post->collaborator_id !== (int) $user->id) {
            throw new AuthorizationException('You are not invited as a collaborator on this post.');
        }

        if (! in_array($action, ['accept', 'decline'], true)) {
            throw new InvalidArgumentException('Action must be accept or decline.');
        }

        $post->update([
            'collaborator_status' => $action === 'accept' ? 'accepted' : 'declined',
        ]);

        return $post->fresh(['user.profile', 'media', 'reactions', 'collaborator.profile']);
    }

    /**
     * Save or update draft for post composer auto-save.
     */
    public function saveDraft(User $user, array $data): PostDraft
    {
        $draft = PostDraft::where('user_id', $user->id)
            ->where('group_id', $data['group_id'] ?? null)
            ->where('page_id', $data['page_id'] ?? null)
            ->first();

        $attributes = [
            'content' => $data['content'] ?? null,
            'audience' => $data['audience'] ?? 'public',
            'type' => $data['type'] ?? 'text',
            'location' => $data['location'] ?? null,
            'feeling_activity' => $data['feeling_activity'] ?? null,
            'poll_data' => $data['poll_data'] ?? null,
            'link_preview' => $data['link_preview'] ?? null,
            'background_style' => $data['background_style'] ?? null,
            'is_ai_generated' => (bool) ($data['is_ai_generated'] ?? false),
            'content_warning' => $data['content_warning'] ?? null,
            'comments_disabled' => (bool) ($data['comments_disabled'] ?? false),
            'tagged_user_ids' => $data['tagged_user_ids'] ?? null,
            'collaborator_id' => $data['collaborator_id'] ?? null,
            'media_ids' => $data['media_ids'] ?? null,
            'media_meta' => $data['media_meta'] ?? null,
            'scheduled_at' => ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
        ];

        if ($draft) {
            $draft->update($attributes);
        } else {
            $draft = PostDraft::create(array_merge($attributes, [
                'user_id' => $user->id,
                'group_id' => $data['group_id'] ?? null,
                'page_id' => $data['page_id'] ?? null,
            ]));
        }

        return $draft->fresh(['user.profile', 'collaborator.profile']);
    }

    /**
     * Retrieve current user drafts.
     */
    public function getDrafts(User $user, ?int $groupId = null, ?int $pageId = null): Collection
    {
        $query = PostDraft::where('user_id', $user->id)->with(['user.profile', 'collaborator.profile']);
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        if ($pageId) {
            $query->where('page_id', $pageId);
        }

        return $query->latest('updated_at')->get();
    }

    /**
     * Delete a specific draft.
     */
    public function deleteDraft(User $user, int $draftId): bool
    {
        return (bool) PostDraft::where('id', $draftId)
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Clear all drafts for the user.
     */
    public function clearDraft(User $user, ?int $groupId = null, ?int $pageId = null): bool
    {
        $query = PostDraft::where('user_id', $user->id);
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        if ($pageId) {
            $query->where('page_id', $pageId);
        }

        return (bool) $query->delete();
    }

    /**
     * Check if a viewer has authorization to view a specific post.
     */
    public function canViewPost(Post $post, ?User $viewer = null): bool
    {
        // 1. Post author can always view their own post
        if ($viewer && (int) $viewer->id === (int) $post->user_id) {
            return true;
        }

        // 2. Scheduled posts are only visible to the author
        if ($post->status === 'scheduled' || ($post->scheduled_at && $post->scheduled_at->isFuture())) {
            return false;
        }

        // 3. Admin / Moderator bypass
        $privacyService = app(ProfilePrivacyService::class);
        if ($privacyService->isAdmin($viewer)) {
            return true;
        }

        // 4. Author status / deactivation check
        $author = $post->user ?: User::find($post->user_id);
        if (! $author || in_array($author->status, ['deactivated', 'suspended', 'banned'], true)) {
            return false;
        }

        // 5. Block check (bidirectional between author and viewer)
        if ($privacyService->isBlocked($author, $viewer)) {
            return false;
        }

        // 6. Group privacy check if post belongs to a group
        if ($post->group_id) {
            $group = $post->group ?: Group::find($post->group_id);
            if ($group && $group->privacy !== 'public') {
                if (! $viewer || ! $group->isMember($viewer->id)) {
                    return false;
                }
            }
        }

        // 7. Audience evaluation
        $audience = strtolower(trim((string) ($post->audience ?? 'public')));

        return match ($audience) {
            'public' => true,
            'friends' => $viewer ? $privacyService->isFriend($author, $viewer) : false,
            'followers' => $viewer ? ($privacyService->isFollower($author, $viewer) || $privacyService->isFriend($author, $viewer)) : false,
            'only_me' => false,
            default => true,
        };
    }
}
