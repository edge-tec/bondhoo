<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MusicTrack;
use App\Models\Report;
use App\Models\Story;
use App\Models\StoryMedia;
use App\Models\StoryReaction;
use App\Models\StoryReply;
use App\Models\StoryView;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * এন্টারপ্রাইজ স্টোরিজ সার্ভিস:
 * মাল্টি-মিডিয়া (ফটো+ভিডিও), ক্যাপশন, মিউজিক, ইমোজি, ইন্টারেক্টিভ পোল, রিঅ্যাকশন, রিপ্লাই
 * এবং ২৪ ঘণ্টা অটো-এক্সপায়ারেশন সাইকেল পরিচালনা করে।
 */
class StoryEnterpriseService
{
    public function __construct(
        protected MusicService $musicService,
        protected NotificationServiceInterface $notificationService,
        protected RealtimeServiceInterface $realtimeService,
        protected ProfilePrivacyService $privacyService
    ) {}

    /**
     * এন্টারপ্রাইজ স্টোরি তৈরি করা
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public function createStory(User $user, array $data): Story
    {
        $type = $data['type'] ?? Story::TYPE_TEXT;
        $content = trim($data['content'] ?? '');
        $mediaIds = (array) ($data['media_ids'] ?? []);

        if ($type === Story::TYPE_TEXT && empty($content)) {
            throw new InvalidArgumentException('Text story must have content.');
        }

        if ($type === Story::TYPE_MEDIA && empty($mediaIds)) {
            throw new InvalidArgumentException('Media story must have at least one media item.');
        }

        return DB::transaction(function () use ($user, $type, $content, $mediaIds, $data) {
            $musicTrack = null;
            if (! empty($data['music_track_id'])) {
                $musicTrack = MusicTrack::query()->available()->find((int) $data['music_track_id']);
            }

            $musicTitle = $data['music_title'] ?? ($musicTrack ? $musicTrack->title : null);

            $publishedAt = now();
            $expiresAt = $publishedAt->copy()->addHours(24);

            $story = Story::create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => Story::STATUS_READY,
                'content' => $content ?: null,
                'background_color' => $data['background_color'] ?? '#1877F2',
                'font_family' => $data['font_family'] ?? 'Hind Siliguri',
                'music_title' => $musicTitle,
                'music_track_id' => $musicTrack?->id,
                'music_start_offset' => (float) ($data['music_start_offset'] ?? 0.0),
                'music_volume' => (int) ($data['music_volume'] ?? 100),
                'emoji' => $data['emoji'] ?? null,
                'interactive_sticker' => $data['interactive_sticker'] ?? null,
                'location' => $data['location'] ?? null,
                'privacy' => $data['privacy'] ?? 'public',
                'allow_replies' => (bool) ($data['allow_replies'] ?? true),
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
                'is_expired' => false,
                'is_archived' => false,
                'is_draft' => (bool) ($data['is_draft'] ?? false),
                'views_count' => 0,
                'reactions_count' => 0,
                'replies_count' => 0,
            ]);

            // মিউজিক ব্যবহার ট্র্যাক করা
            if ($musicTrack) {
                $this->musicService->recordUsage(
                    $musicTrack,
                    $story,
                    $user,
                    (float) ($data['music_start_offset'] ?? 0.0),
                    15.0,
                    (int) ($data['music_volume'] ?? 100)
                );
            }

            // একাধিক মিডিয়া আইটেম ক্রমানুসারে যুক্ত করা
            if (! empty($mediaIds)) {
                $order = 0;
                foreach ($mediaIds as $mediaId) {
                    $media = Media::where('id', $mediaId)
                        ->where('user_id', $user->id)
                        ->first();

                    if ($media) {
                        $media->update([
                            'mediable_type' => Story::class,
                            'mediable_id' => $story->id,
                            'collection' => 'story',
                        ]);

                        // ফটো হলে ডিফল্ট ৫ সেকেন্ড, ভিডিও হলে ডিউরেশন
                        $isVideo = str_starts_with($media->mime_type ?? '', 'video/');
                        $duration = $isVideo ? (int) ($media->metadata['duration'] ?? 15) : 5;

                        StoryMedia::create([
                            'story_id' => $story->id,
                            'media_id' => $media->id,
                            'order' => $order++,
                            'duration' => $duration,
                        ]);
                    }
                }
            }

            $story->load(['user.profile', 'media', 'storyMedia.media', 'musicTrack']);

            if (! $story->is_draft) {
                $this->realtimeService->broadcast('stories', 'story.created', $story->toResponseArray($user));
            }

            return $story;
        });
    }

    /**
     * একক স্টোরি প্রদর্শন (সার্ভার অথরিটেটিভ এক্সপায়ারেশন ও প্রাইভেসি প্রয়োগ)
     *
     * @throws ModelNotFoundException|AuthorizationException|Exception
     */
    public function getStory(int $id, ?User $viewer = null): Story
    {
        $story = Story::with(['user.profile', 'media', 'storyMedia.media', 'musicTrack', 'views', 'reactions'])->findOrFail($id);

        if ($story->isExpired()) {
            throw new Exception('এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।');
        }

        // ইউজার ব্লক চেক
        if ($viewer && $this->privacyService->isBlocked($story->user_id, $viewer)) {
            throw new AuthorizationException('You are not authorized to view this story.');
        }

        // প্রাইভেসি রুলস চেক
        if ($story->privacy === 'only_me' && (! $viewer || $viewer->id !== $story->user_id)) {
            throw new AuthorizationException('This story is private.');
        }

        if ($story->privacy === 'friends' && (! $viewer || ($viewer->id !== $story->user_id && ! $story->user->isFriendWith($viewer)))) {
            throw new AuthorizationException('This story is visible to friends only.');
        }

        return $story;
    }

    /**
     * সক্রিয় স্টোরি ফিড (ভিউয়ার অনুযায়ী গ্রুপড)
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getActiveFeed(User $viewer): Collection
    {
        // মেয়াদোত্তীর্ণ স্টোরিগুলো চিহ্নিত করা
        $this->expireStaleStories();

        $blockedUserIds = $this->privacyService->getBlockedUserIds($viewer->id);

        $storiesQuery = Story::active()
            ->where(function ($query) use ($viewer) {
                $query->where('privacy', 'public')
                    ->orWhere('user_id', $viewer->id)
                    ->orWhere(function ($q) use ($viewer) {
                        $friendIds = $viewer->getFriendIds();
                        if (! empty($friendIds)) {
                            $q->where('privacy', 'friends')->whereIn('user_id', $friendIds);
                        } else {
                            $q->whereRaw('0 = 1');
                        }
                    });
            });

        if (! empty($blockedUserIds)) {
            $storiesQuery->whereNotIn('user_id', $blockedUserIds);
        }

        $stories = $storiesQuery
            ->with(['user.profile', 'media', 'storyMedia.media', 'views', 'reactions', 'musicTrack'])
            ->latest('id')
            ->get();

        // লেখক অনুযায়ী গ্রুপ করা
        $grouped = $stories->groupBy('user_id')->map(function (Collection $userStories) use ($viewer) {
            $author = $userStories->first()->user;
            $allViewed = $userStories->every(fn (Story $s) => $s->views->contains('user_id', $viewer->id));

            return [
                'user' => [
                    'id' => $author?->id,
                    'name' => $author?->name,
                    'username' => $author?->username,
                    'avatar_url' => $author?->profile?->avatar_url ?? $author?->avatar_url,
                    'is_verified' => (bool) ($author?->profile?->is_verified ?? false),
                ],
                'all_viewed' => $allViewed,
                'latest_story_time' => $userStories->first()->created_at?->toIso8601String(),
                'stories_count' => $userStories->count(),
                'stories' => $userStories->map(fn (Story $s) => $s->toResponseArray($viewer))->values()->toArray(),
            ];
        })->values();

        // যে ব্যবহারকারীর স্টোরি এখনও দেখা হয়নি তারা প্রথমে আসবে, এবং নিজের স্টোরি সবার শুরুতে থাকবে
        return $grouped->sortBy(function ($item) use ($viewer) {
            if ($item['user']['id'] === $viewer->id) {
                return -2;
            }

            return $item['all_viewed'] ? 1 : 0;
        })->values();
    }

    /**
     * স্টোরি ভিউ রেকর্ড করা
     */
    public function recordView(Story $story, User $viewer): bool
    {
        if ($story->isExpired()) {
            return false;
        }

        // নিজের স্টোরি ভিউ কাউন্ট হবে না
        if ($story->user_id === $viewer->id) {
            return false;
        }

        $existing = StoryView::where('story_id', $story->id)
            ->where('user_id', $viewer->id)
            ->first();

        if ($existing) {
            return false;
        }

        StoryView::create([
            'story_id' => $story->id,
            'user_id' => $viewer->id,
            'viewed_at' => now(),
        ]);

        $story->increment('views_count');

        return true;
    }

    /**
     * স্টোরিতে রিঅ্যাকশন দেওয়া
     *
     * @return array{reacted: bool, type: string, count: int}
     *
     * @throws Exception
     */
    public function reactToStory(Story $story, User $user, string $type = 'like'): array
    {
        if ($story->isExpired()) {
            throw new Exception('এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।');
        }

        $validTypes = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];
        if (! in_array($type, $validTypes)) {
            $type = 'like';
        }

        $reaction = StoryReaction::where('story_id', $story->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            if ($reaction->type === $type) {
                $reaction->delete();
                $story->decrement('reactions_count');

                return ['reacted' => false, 'type' => $type, 'count' => $story->fresh()->reactions_count];
            }

            $reaction->update(['type' => $type]);

            return ['reacted' => true, 'type' => $type, 'count' => $story->fresh()->reactions_count];
        }

        StoryReaction::create([
            'story_id' => $story->id,
            'user_id' => $user->id,
            'type' => $type,
        ]);

        $story->increment('reactions_count');

        if ($story->user_id !== $user->id) {
            try {
                $this->notificationService->send(
                    $story->user_id,
                    'story.reaction',
                    [
                        'story_id' => $story->id,
                        'actor_id' => $user->id,
                        'actor_name' => $user->name,
                        'message' => "{$user->name} আপনার স্টোরিতে রিঅ্যাক্ট করেছেন।",
                        'type' => $type,
                    ],
                    ['database', 'broadcast']
                );
            } catch (Exception) {
            }
        }

        return ['reacted' => true, 'type' => $type, 'count' => $story->fresh()->reactions_count];
    }

    /**
     * স্টোরিতে রিপ্লাই মেসেজ পাঠানো
     *
     * @throws Exception
     */
    public function replyToStory(Story $story, User $user, string $message): StoryReply
    {
        if ($story->isExpired()) {
            throw new Exception('এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে। রিপ্লাই পাঠানো সম্ভব নয়।');
        }

        if (! $story->allow_replies) {
            throw new Exception('Replies are disabled for this story.');
        }

        $reply = StoryReply::create([
            'story_id' => $story->id,
            'user_id' => $user->id,
            'message' => trim($message),
        ]);

        $story->increment('replies_count');

        if ($story->user_id !== $user->id) {
            try {
                $this->notificationService->send(
                    $story->user_id,
                    'story.reply',
                    [
                        'story_id' => $story->id,
                        'actor_id' => $user->id,
                        'actor_name' => $user->name,
                        'message' => "{$user->name} আপনার স্টোরিতে রিপ্লাই দিয়েছেন: \"".Str::limit($message, 40).'"',
                    ],
                    ['database', 'broadcast']
                );
            } catch (Exception) {
            }
        }

        return $reply;
    }

    /**
     * ভিউয়ার তালিকা
     *
     * @throws AuthorizationException
     */
    public function getStoryViewers(Story $story, User $user, int $perPage = 20): LengthAwarePaginator
    {
        if ($story->user_id !== $user->id) {
            throw new AuthorizationException('Only the story creator can view the viewer list.');
        }

        $paginator = StoryView::where('story_id', $story->id)
            ->with('user.profile')
            ->latest('viewed_at')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (StoryView $view) {
            return [
                'user' => [
                    'id' => $view->user?->id,
                    'name' => $view->user?->name,
                    'username' => $view->user?->username,
                    'avatar_url' => $view->user?->profile?->avatar_url ?? $view->user?->avatar_url,
                ],
                'viewed_at' => $view->viewed_at?->toIso8601String(),
                'formatted_time' => $view->viewed_at?->diffForHumans() ?? 'just now',
            ];
        });

        return $paginator;
    }

    /**
     * স্টোরি ডিলিট
     *
     * @throws AuthorizationException
     */
    public function deleteStory(Story $story, User $user): bool
    {
        if ($story->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this story.');
        }

        $storyId = $story->id;
        $deleted = $story->delete();

        if ($deleted) {
            $this->realtimeService->broadcast('stories', 'story.deleted', ['story_id' => $storyId]);
        }

        return (bool) $deleted;
    }

    /**
     * স্টোরি আর্কাইভ করা
     *
     * @throws AuthorizationException
     */
    public function archiveStory(Story $story, User $user): bool
    {
        if ($story->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to archive this story.');
        }

        return $story->update([
            'is_archived' => true,
        ]);
    }

    /**
     * ইউজারের আর্কাইভড স্টোরিজ লিস্ট
     */
    public function getArchivedStories(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Story::where('user_id', $user->id)
            ->archived()
            ->with(['media', 'storyMedia.media', 'musicTrack'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * স্টোরি রিপোর্ট করা (ডুপ্লিকেট সুরক্ষা সহ)
     */
    public function reportStory(Story $story, User $user, string $reason, ?string $details = null): bool
    {
        $existing = Report::where('reporter_id', $user->id)
            ->where('reportable_type', Story::class)
            ->where('reportable_id', $story->id)
            ->first();

        if ($existing) {
            return false;
        }

        Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => Story::class,
            'reportable_id' => $story->id,
            'reason' => $reason,
            'details' => $details,
            'status' => Report::STATUS_PENDING,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * স্টোরি শেয়ার রেকর্ড করা
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function recordShare(Story $story, User $user, string $platform = 'internal'): array
    {
        if ($story->isExpired()) {
            throw new Exception('এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে। শেয়ার করা সম্ভব নয়।');
        }

        $story->increment('shares_count');

        if ($story->user_id !== $user->id) {
            try {
                $this->notificationService->send(
                    $story->user_id,
                    'story.share',
                    [
                        'story_id' => $story->id,
                        'actor_id' => $user->id,
                        'actor_name' => $user->name,
                        'message' => "{$user->name} আপনার স্টোরি শেয়ার করেছেন।",
                    ],
                    ['database', 'broadcast']
                );
            } catch (Exception) {
            }
        }

        return [
            'shared' => true,
            'platform' => $platform,
            'shares_count' => $story->fresh()->shares_count,
        ];
    }

    /**
     * মেয়াদোত্তীর্ণ স্টোরিগুলো চিহ্নিত করে এক্সপায়ার করা ও রিয়েল-টাইমে ব্রডকাস্ট করা
     */
    public function expireStaleStories(): int
    {
        $staleStories = Story::where('is_expired', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($staleStories as $stale) {
            $stale->update(['is_expired' => true]);
            $count++;

            $this->realtimeService->broadcast('stories', 'story.expired', [
                'story_id' => $stale->id,
                'user_id' => $stale->user_id,
            ]);
        }

        return $count;
    }
}
