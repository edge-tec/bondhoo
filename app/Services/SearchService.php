<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Group;
use App\Models\JobPosting;
use App\Models\LearningCourse;
use App\Models\Page;
use App\Models\Post;
use App\Models\Reel;
use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;

/**
 * ইউনিভার্সাল সার্চ সার্ভিস:
 * ইউজার, পোস্ট, গ্রুপ এবং পেজজুড়ে সার্বজনীন সার্চ সম্পাদন করে।
 * ব্লকড ইউজার ফিল্টারিং ও সার্চ হিস্ট্রি হ্যান্ডেল করে।
 */
class SearchService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * সার্চ কোয়েরি ও ফিল্টার অনুযায়ী ফলাফল রিটার্ন করে।
     */
    public function search(string $query, string $type = 'all', ?User $viewer = null, int $limit = 10, bool $suggest = false, array $filters = []): array
    {
        $term = trim($query);

        if ($viewer && ! empty($term) && ! $suggest) {
            $this->recordSearchHistory($viewer, $term);
        }

        $blockedUserIds = [];
        if ($viewer) {
            $blockedUserIds = app(ProfilePrivacyService::class)->getBlockedUserIds($viewer->id);
        }

        if (empty($term)) {
            if ($suggest) {
                $suggestUsersQuery = User::when($viewer, fn ($q) => $q->where('id', '!=', $viewer->id))
                    ->with('profile');

                if (! empty($blockedUserIds)) {
                    $suggestUsersQuery->whereNotIn('id', $blockedUserIds);
                }

                return [
                    'query' => '',
                    'type' => $type,
                    'is_suggestion' => true,
                    'results' => [
                        'users' => $suggestUsersQuery
                            ->latest('id')
                            ->limit(min($limit, 6))
                            ->get()
                            ->map(fn (User $u) => [
                                'id' => $u->id,
                                'name' => $u->name,
                                'username' => $u->username,
                                'avatar_url' => $u->profile?->avatar_url,
                                'bio' => $u->profile?->bio,
                            ])
                            ->values(),
                        'posts' => [],
                        'groups' => Group::where('privacy', Group::PRIVACY_PUBLIC)
                            ->latest('id')
                            ->limit(min($limit, 4))
                            ->get()
                            ->map(fn (Group $g) => $g->toResponseArray($viewer))
                            ->values(),
                        'pages' => Page::latest('id')
                            ->limit(min($limit, 4))
                            ->get()
                            ->map(fn (Page $p) => $p->toResponseArray($viewer))
                            ->values(),
                        'events' => Event::latest('start_time')
                            ->limit(min($limit, 4))
                            ->get()
                            ->map(fn (Event $e) => [
                                'id' => $e->id,
                                'title' => $e->title,
                                'location' => $e->address ?: $e->location_type,
                            ])
                            ->values(),
                        'jobs' => JobPosting::where('status', JobPosting::STATUS_ACTIVE)
                            ->latest('id')
                            ->limit(min($limit, 4))
                            ->get()
                            ->map(fn (JobPosting $j) => [
                                'id' => $j->id,
                                'title' => $j->title,
                                'company_name' => $j->company_name,
                                'company_logo' => $j->company_logo,
                                'location' => $j->location,
                                'employment_type' => $j->employment_type,
                            ])
                            ->values(),
                        'trending' => ['#Connectly', '#Dhaka', '#Bangladesh', '#TechSummit2026'],
                    ],
                ];
            }

            return [
                'query' => $term,
                'results' => [
                    'users' => [],
                    'posts' => [],
                    'groups' => [],
                    'pages' => [],
                    'events' => [],
                    'jobs' => [],
                    'reels' => [],
                    'hashtags' => [],
                ],
            ];
        }

        $results = [];

        // ১. ইউজার সার্চ (ব্লকড ইউজার ও সেলফ ফিল্টার সহ)
        if ($type === 'all' || $type === 'users') {
            $userQuery = User::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('username', 'like', "%{$term}%");
            });

            if ($viewer) {
                $userQuery->where('id', '!=', $viewer->id);
                if (! empty($blockedUserIds)) {
                    $userQuery->whereNotIn('id', $blockedUserIds);
                }
            }

            if (! empty($filters['location'])) {
                $userQuery->whereHas('profile', fn ($pq) => $pq->where('location', 'like', "%{$filters['location']}%")->orWhere('city', 'like', "%{$filters['location']}%"));
            }

            if (! empty($filters['country'])) {
                $userQuery->whereHas('profile', fn ($pq) => $pq->where('country', 'like', "%{$filters['country']}%"));
            }

            if (! empty($filters['industry'])) {
                $userQuery->whereHas('profile', fn ($pq) => $pq->where('category', 'like', "%{$filters['industry']}%")->orWhere('headline', 'like', "%{$filters['industry']}%"));
            }

            if (! empty($filters['skills'])) {
                $userQuery->whereHas('skills', fn ($sq) => $sq->where('name', 'like', "%{$filters['skills']}%"));
            }

            if (! empty($filters['sort_by']) && $filters['sort_by'] === 'newest') {
                $userQuery->latest('id');
            }

            $results['users'] = $userQuery
                ->with('profile')
                ->limit($limit)
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar_url' => $u->profile?->avatar_url,
                    'bio' => $u->profile?->bio,
                    'location' => $u->profile?->location,
                    'headline' => $u->profile?->headline,
                ])
                ->values();
        }

        // ২. পোস্ট সার্চ (শুধুমাত্র পাবলিক পোস্ট ও ব্লকড ইউজার ফিল্টার)
        if ($type === 'all' || $type === 'posts') {
            $postQuery = Post::where('audience', 'public')
                ->where('content', 'like', "%{$term}%");

            if (! empty($blockedUserIds)) {
                $postQuery->whereNotIn('user_id', $blockedUserIds);
            }

            if (! empty($filters['category'])) {
                $postQuery->where('category', 'like', "%{$filters['category']}%");
            }

            if (! empty($filters['date_from'])) {
                $postQuery->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $postQuery->whereDate('created_at', '<=', $filters['date_to']);
            }

            // Group privacy check: Only show group posts if group is public or viewer is active member
            $postQuery->where(function ($q) use ($viewer) {
                $q->whereNull('group_id')
                    ->orWhereHas('group', function ($gq) use ($viewer) {
                        $gq->where('privacy', Group::PRIVACY_PUBLIC);
                        if ($viewer) {
                            $gq->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
                        }
                    });
            });

            if (! empty($filters['sort_by']) && $filters['sort_by'] === 'popularity') {
                $postQuery->orderByDesc('reactions_count')->orderByDesc('comments_count');
            } else {
                $postQuery->latest('id');
            }

            $results['posts'] = $postQuery
                ->with(['user.profile', 'media'])
                ->limit($limit)
                ->get()
                ->map(fn (Post $p) => $p->toResponseArray($viewer))
                ->values();
        }

        // ৩. গ্রুপ সার্চ (পাবলিক বা অনুমোদিত গ্রুপ)
        if ($type === 'all' || $type === 'groups') {
            $groupQuery = Group::where('status', Group::STATUS_ACTIVE)
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('category', 'like', "%{$term}%");
                });

            if ($viewer) {
                $groupQuery->where(function ($q) use ($viewer) {
                    $q->where('privacy', '!=', Group::PRIVACY_HIDDEN)
                        ->orWhereHas('members', fn ($mq) => $mq->where('user_id', $viewer->id)->where('status', 'active'));
                });
            } else {
                $groupQuery->where('privacy', Group::PRIVACY_PUBLIC);
            }

            if (! empty($filters['category'])) {
                $groupQuery->where('category', 'like', "%{$filters['category']}%");
            }

            if (! empty($filters['sort_by']) && $filters['sort_by'] === 'popularity') {
                $groupQuery->orderByDesc('members_count');
            }

            $results['groups'] = $groupQuery
                ->limit($limit)
                ->get()
                ->map(fn (Group $g) => $g->toResponseArray($viewer))
                ->values();
        }

        // ৪. পেজ সার্চ
        if ($type === 'all' || $type === 'pages') {
            $pageQuery = Page::where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%")
                    ->orWhere('bio', 'like', "%{$term}%");
            });

            if (! empty($filters['category']) || ! empty($filters['industry'])) {
                $cat = $filters['category'] ?? $filters['industry'];
                $pageQuery->where('category', 'like', "%{$cat}%");
            }

            if (! empty($filters['sort_by']) && $filters['sort_by'] === 'popularity') {
                $pageQuery->orderByDesc('followers_count');
            }

            $results['pages'] = $pageQuery
                ->limit($limit)
                ->get()
                ->map(fn (Page $p) => $p->toResponseArray($viewer))
                ->values();
        }

        // ৫. রিলস সার্চ
        if ($type === 'all' || $type === 'reels') {
            $reelQuery = Reel::where('status', 'active')
                ->where(function ($q) use ($term) {
                    $q->where('caption', 'like', "%{$term}%")
                        ->orWhere('audio_title', 'like', "%{$term}%")
                        ->orWhere('audio_artist', 'like', "%{$term}%");
                });

            if (! empty($blockedUserIds)) {
                $reelQuery->whereNotIn('user_id', $blockedUserIds);
            }

            $results['reels'] = $reelQuery
                ->limit($limit)
                ->get()
                ->map(fn (Reel $r) => $r->toResponseArray($viewer))
                ->values();
        }

        // ৬. ইভেন্ট সার্চ
        if ($type === 'all' || $type === 'events') {
            $eventQuery = Event::where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });

            if (! empty($filters['location'])) {
                $eventQuery->where('address', 'like', "%{$filters['location']}%");
            }

            if (! empty($filters['date_from'])) {
                $eventQuery->whereDate('start_time', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $eventQuery->whereDate('start_time', '<=', $filters['date_to']);
            }

            $results['events'] = $eventQuery
                ->limit($limit)
                ->get()
                ->map(fn (Event $e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'start_time' => $e->start_time?->toIso8601String(),
                    'location' => $e->address ?: $e->location_type,
                    'banner' => $e->cover_image,
                    'going_count' => (int) ($e->going_count ?? 0),
                    'interested_count' => (int) ($e->interested_count ?? 0),
                ])
                ->values();
        }

        // ৭. জবস সার্চ
        if ($type === 'all' || $type === 'jobs') {
            $jobQuery = JobPosting::where('status', JobPosting::STATUS_ACTIVE)
                ->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhere('company_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%");
                });

            if (! empty($filters['job_type'])) {
                $jobQuery->where(function ($q) use ($filters) {
                    $q->where('employment_type', $filters['job_type'])
                        ->orWhere('location_type', $filters['job_type']);
                });
            }

            if (! empty($filters['location'])) {
                $jobQuery->where('location', 'like', "%{$filters['location']}%");
            }

            if (! empty($filters['skills'])) {
                $jobQuery->where(function ($q) use ($filters) {
                    $q->where('title', 'like', "%{$filters['skills']}%")
                        ->orWhere('requirements', 'like', "%{$filters['skills']}%");
                });
            }

            if (! empty($filters['sort_by']) && $filters['sort_by'] === 'newest') {
                $jobQuery->latest('id');
            } elseif (! empty($filters['sort_by']) && $filters['sort_by'] === 'popularity') {
                $jobQuery->orderByDesc('views_count');
            }

            $results['jobs'] = $jobQuery
                ->limit($limit)
                ->get()
                ->map(fn (JobPosting $j) => [
                    'id' => $j->id,
                    'title' => $j->title,
                    'company_name' => $j->company_name,
                    'company_logo' => $j->company_logo,
                    'location' => $j->location,
                    'location_type' => $j->location_type,
                    'employment_type' => $j->employment_type,
                    'salary' => $j->salaryFormatted(),
                    'created_at' => $j->created_at?->toIso8601String(),
                ])
                ->values();
        }

        // ৮. হ্যাশট্যাগ সার্চ
        if ($type === 'all' || $type === 'hashtags') {
            $cleanTerm = ltrim($term, '#');
            $postsWithTags = Post::where('audience', 'public')
                ->where('content', 'like', "%#{$cleanTerm}%")
                ->limit(20)
                ->pluck('content');

            $matchedTags = [];
            foreach ($postsWithTags as $text) {
                preg_match_all('/#[a-zA-Z0-9_\x{0980}-\x{09FF}]+/u', (string) $text, $matches);
                foreach ($matches[0] ?? [] as $tag) {
                    if (stripos($tag, $cleanTerm) !== false) {
                        $matchedTags[$tag] = ($matchedTags[$tag] ?? 0) + 1;
                    }
                }
            }
            $results['hashtags'] = collect($matchedTags)->map(fn ($count, $tag) => [
                'tag' => $tag,
                'count' => $count,
            ])->values();
        }

        // ৯. কোর্স সার্চ (Phase 13 LMS)
        if ($type === 'all' || $type === 'courses') {
            $courseQuery = LearningCourse::with('instructor')
                ->where('status', LearningCourse::STATUS_PUBLISHED)
                ->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhere('subtitle', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('skills', function ($sq) use ($term) {
                            $sq->where('skill_name', 'like', "%{$term}%");
                        });
                });

            $results['courses'] = $courseQuery
                ->limit($limit)
                ->get()
                ->map(fn (LearningCourse $c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'slug' => $c->slug,
                    'subtitle' => $c->subtitle,
                    'level' => $c->level,
                    'price' => (float) $c->price,
                    'rating_avg' => (float) $c->rating_avg,
                    'thumbnail_url' => $c->thumbnail_url,
                    'instructor_name' => $c->instructor?->name,
                ])
                ->values();
        }

        return [
            'query' => $term,
            'type' => $type,
            'results' => $results,
        ];
    }

    /**
     * সার্চ হিস্ট্রি সংরক্ষণ (Redis ব্যাকড)।
     */
    public function recordSearchHistory(User $user, string $query): void
    {
        $term = trim($query);
        if ($term === '') {
            return;
        }

        $key = "user:{$user->id}:recent_searches";
        $history = $this->cacheService->get($key);
        if (! is_array($history)) {
            $history = [];
        }

        $history = array_values(array_unique(array_merge([$term], $history)));
        $history = array_slice($history, 0, 10);

        $this->cacheService->set($key, $history, 86400 * 30);
    }

    /**
     * ইউজারের সাম্প্রতিক সার্চ হিস্ট্রি রিট্রিভ করা।
     */
    public function getSearchHistory(User $user): array
    {
        $key = "user:{$user->id}:recent_searches";
        $history = $this->cacheService->get($key);

        return is_array($history) ? $history : [];
    }

    /**
     * ইউজারের সার্চ হিস্ট্রি থেকে নির্দিষ্ট একটি টার্ম মুছে ফেলা।
     */
    public function removeSearchHistoryItem(User $user, string $term): array
    {
        $term = trim($term);
        $key = "user:{$user->id}:recent_searches";
        $history = $this->getSearchHistory($user);

        $updated = array_values(array_filter($history, fn ($item) => strcasecmp($item, $term) !== 0));

        $this->cacheService->set($key, $updated, 86400 * 30);

        return $updated;
    }

    /**
     * ইউজারের সার্চ হিস্ট্রি মুছে ফেলা।
     */
    public function clearSearchHistory(User $user): void
    {
        $key = "user:{$user->id}:recent_searches";
        $this->cacheService->forget($key);
    }
}
