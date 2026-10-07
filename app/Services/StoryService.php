<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * স্টোরি সার্ভিস:
 * ২৪ ঘণ্টার ক্ষণস্থায়ী স্টোরি তৈরি, সক্রিয় স্টোরি ফিড এবং ভিউ ট্র্যাকিং হ্যান্ডেল করে।
 */
class StoryService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * নতুন স্টোরি তৈরি করা (২৪ ঘণ্টা মেয়াদের সাথে)।
     */
    public function createStory(User $user, array $data): Story
    {
        $type = $data['type'] ?? Story::TYPE_TEXT;
        $content = trim($data['content'] ?? '');
        $mediaIds = $data['media_ids'] ?? [];

        if ($type === Story::TYPE_TEXT && empty($content)) {
            throw new InvalidArgumentException('Text story must have content.');
        }

        if ($type === Story::TYPE_MEDIA && empty($mediaIds)) {
            throw new InvalidArgumentException('Media story must have at least one media attachment.');
        }

        return DB::transaction(function () use ($user, $type, $content, $mediaIds, $data) {
            $story = Story::create([
                'user_id' => $user->id,
                'type' => $type,
                'status' => Story::STATUS_READY,
                'content' => $content ?: null,
                'background_color' => $data['background_color'] ?? '#1877F2',
                'privacy' => $data['privacy'] ?? 'public',
                'published_at' => now(),
                'expires_at' => now()->addHours(24),
                'is_expired' => false,
                'views_count' => 0,
            ]);

            if (! empty($mediaIds)) {
                Media::whereIn('id', $mediaIds)
                    ->where('user_id', $user->id)
                    ->update([
                        'mediable_type' => Story::class,
                        'mediable_id' => $story->id,
                        'collection' => 'story',
                    ]);
            }

            $story->load(['user.profile', 'media']);

            return $story;
        });
    }

    /**
     * ফিডের জন্য সক্রিয় স্টোরিগুলো সংগ্রহ করে ইউজার অনুযায়ী গ্রুপ করা।
     */
    public function getActiveFeedStories(User $viewer): Collection
    {
        // ২৪ ঘণ্টার মধ্যে থাকা এবং ভিউয়ারের জন্য প্রযোজ্য প্রাইভেসি সম্পন্ন স্টোরি আনা
        $stories = Story::active()
            ->where(function ($query) use ($viewer) {
                $query->where('privacy', 'public')
                    ->orWhere('user_id', $viewer->id);
            })
            ->with(['user.profile', 'media', 'views'])
            ->latest('id')
            ->get();

        // ইউজার ভিত্তিক গ্রুপ করা (ইউজারের নিজস্ব স্টোরি সবার আগে থাকবে)
        $grouped = $stories->groupBy('user_id')->map(function (Collection $userStories) use ($viewer) {
            $author = $userStories->first()->user;
            $allViewed = $userStories->every(fn (Story $s) => $s->views->contains('user_id', $viewer->id));

            return [
                'user' => [
                    'id' => $author?->id,
                    'name' => $author?->name,
                    'username' => $author?->username,
                    'avatar_url' => $author?->profile?->avatar_url,
                ],
                'all_viewed' => $allViewed,
                'stories_count' => $userStories->count(),
                'latest_story_at' => $userStories->first()->created_at?->toIso8601String(),
                'stories' => $userStories->map(fn (Story $s) => $s->toResponseArray($viewer))->values(),
            ];
        })->values();

        // ভিউয়ারের নিজের স্টোরিগুলো শুরুতে সর্ট করা
        return $grouped->sortByDesc(fn ($item) => $item['user']['id'] === $viewer->id)->values();
    }

    /**
     * স্টোরি ভিউ রেকর্ড করা (একই ইউজার থেকে ডুপ্লিকেট বাদ দিয়ে ভিউ কাউন্ট বাড়ানো)।
     */
    public function recordView(Story $story, User $viewer): bool
    {
        // নিজের স্টোরি নিজে দেখলে ভিউয়ার লিস্টে যোগ হয় না
        if ($story->user_id === $viewer->id) {
            return false;
        }

        // ইতিমধ্যে দেখে থাকলে নতুন করে রেকর্ড হবে না
        $alreadyViewed = StoryView::where('story_id', $story->id)
            ->where('user_id', $viewer->id)
            ->exists();

        if ($alreadyViewed) {
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
     * স্টোরির ভিউয়ার তালিকা পাওয়া (শুধুমাত্র স্টোরি লেখক দেখতে পারবেন)।
     */
    public function getStoryViews(Story $story, User $user, int $perPage = 20): LengthAwarePaginator
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
                    'avatar_url' => $view->user?->profile?->avatar_url,
                ],
                'viewed_at' => $view->viewed_at?->toIso8601String(),
            ];
        });

        return $paginator;
    }

    /**
     * স্টোরি মুছে ফেলা।
     */
    public function deleteStory(Story $story, User $user): bool
    {
        if ($story->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this story.');
        }

        return $story->delete();
    }
}
