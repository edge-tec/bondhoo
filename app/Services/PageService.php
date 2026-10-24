<?php

namespace App\Services;

use App\Events\PageFollowerUpdatedEvent;
use App\Events\PageNotificationEvent;
use App\Events\PagePostPublishedEvent;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageFollower;
use App\Models\PageMember;
use App\Models\Post;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * পেজ সার্ভিস:
 * পাবলিক পেজ তৈরি, ফলো/আনফলো এবং পেজ পোস্ট পরিচালনা করে।
 */
class PageService
{
    /**
     * নতুন পেজ তৈরি করা।
     */
    public function createPage(User $owner, array $data): Page
    {
        $name = trim($data['name']);
        $slug = Str::slug($name);

        $originalSlug = $slug;
        $counter = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        return DB::transaction(function () use ($owner, $data, $name, $slug) {
            $username = $data['username'] ?? $slug;
            $counter = 1;
            $origUser = $username;
            while (Page::where('username', $username)->exists()) {
                $username = "{$origUser}-{$counter}";
                $counter++;
            }

            $page = Page::create([
                'name' => $name,
                'slug' => $slug,
                'username' => $username,
                'category' => $data['category'] ?? 'General',
                'bio' => $data['bio'] ?? ($data['short_description'] ?? null),
                'short_description' => $data['short_description'] ?? ($data['bio'] ?? null),
                'description' => $data['description'] ?? ($data['bio'] ?? null),
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'visibility' => $data['visibility'] ?? 'public',
                'owner_id' => $owner->id,
                'followers_count' => 0,
                'posts_count' => 0,
                'is_verified' => false,
            ]);

            PageMember::firstOrCreate([
                'page_id' => $page->id,
                'user_id' => $owner->id,
            ], [
                'role' => Page::ROLE_OWNER,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $page;
        });
    }

    /**
     * পেজ ফলো বা আনফলো টগল করা।
     */
    public function toggleFollow(Page $page, User $user): array
    {
        return DB::transaction(function () use ($page, $user) {
            $lockedPage = Page::where('id', $page->id)->lockForUpdate()->firstOrFail();

            $existing = PageFollower::where('page_id', $lockedPage->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $lockedPage->decrement('followers_count');
                $newCount = (int) $lockedPage->fresh()->followers_count;

                DB::afterCommit(function () use ($lockedPage, $newCount, $user) {
                    try {
                        broadcast(new PageFollowerUpdatedEvent((int) $lockedPage->id, $newCount, 'unfollowed', (int) $user->id));
                    } catch (\Throwable $e) {
                        // Ignore broadcast failure
                    }
                });

                return [
                    'is_following' => false,
                    'followers_count' => $newCount,
                    'message' => 'Unfollowed page successfully.',
                ];
            }

            try {
                PageFollower::create([
                    'page_id' => $lockedPage->id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                ]);

                $lockedPage->increment('followers_count');
            } catch (UniqueConstraintViolationException $e) {
                // Concurrently followed, retain consistency
            }

            $newCount = (int) $lockedPage->fresh()->followers_count;

            DB::afterCommit(function () use ($lockedPage, $newCount, $user) {
                try {
                    broadcast(new PageFollowerUpdatedEvent((int) $lockedPage->id, $newCount, 'followed', (int) $user->id));
                } catch (\Throwable $e) {
                    // Ignore broadcast failure
                }

                if ((int) $lockedPage->owner_id !== (int) $user->id) {
                    try {
                        broadcast(new PageNotificationEvent(
                            recipientUserId: (int) $lockedPage->owner_id,
                            pageId: (int) $lockedPage->id,
                            notificationData: [
                                'type' => 'page.followed',
                                'title' => 'নতুন পেজ ফলোয়ার!',
                                'message' => ($user->name ?? $user->username)." আপনার পেজ '{$lockedPage->name}' ফলো করেছেন।",
                                'action_url' => "/pages/{$lockedPage->slug}",
                                'actor_id' => $user->id,
                                'actor_name' => $user->name ?? $user->username,
                            ],
                            broadcastToAdminChannel: true
                        ));
                    } catch (\Throwable $e) {
                        // Ignore broadcast failure
                    }
                }
            });

            if (app()->bound(NotificationServiceInterface::class) && (int) $lockedPage->owner_id !== (int) $user->id) {
                try {
                    app(NotificationServiceInterface::class)->send(
                        recipientId: $lockedPage->owner_id,
                        type: 'page.followed',
                        data: [
                            'actor_id' => $user->id,
                            'actor_name' => $user->name ?? $user->username,
                            'page_id' => $lockedPage->id,
                            'page_name' => $lockedPage->name,
                            'title' => 'নতুন পেজ ফলোয়ার!',
                            'message' => ($user->name ?? $user->username)." আপনার পেজ '{$lockedPage->name}' ফলো করেছেন।",
                            'action_url' => "/pages/{$lockedPage->slug}",
                        ],
                        channels: ['database']
                    );
                } catch (\Throwable $e) {
                    // Ignore notification failure
                }
            }

            return [
                'is_following' => true,
                'followers_count' => $newCount,
                'message' => 'Followed page successfully.',
            ];
        });
    }

    /**
     * পেজের পক্ষ থেকে পোস্ট তৈরি করা (পেজ ওনার বা অনুমোদিত টিম মেম্বার করতে পারবেন)।
     */
    public function createPagePost(Page $page, User $user, array $data): Post
    {
        if (! $page->hasPermission($user->id, 'posts.create') && (int) $page->owner_id !== (int) $user->id) {
            throw new AuthorizationException('Only authorized page managers can publish posts as this page.');
        }

        return DB::transaction(function () use ($page, $user, $data) {
            $status = $data['status'] ?? 'published';
            $scheduledAt = ! empty($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

            if ($scheduledAt && $scheduledAt->isFuture() && $status !== 'draft') {
                $status = 'scheduled';
            }

            $post = Post::create([
                'user_id' => $user->id,
                'page_id' => $page->id,
                'content' => $data['content'],
                'audience' => 'public',
                'type' => $data['type'] ?? 'text',
                'status' => $status,
                'scheduled_at' => $scheduledAt,
            ]);

            // Attach Media if provided - strictly enforce user ownership and prevent hijacking attached media
            if (! empty($data['media_ids'])) {
                Media::whereIn('id', $data['media_ids'])
                    ->where('user_id', $user->id)
                    ->where(function ($q) use ($post) {
                        $q->whereNull('mediable_id')->orWhere('mediable_id', $post->id);
                    })
                    ->update([
                        'mediable_type' => Post::class,
                        'mediable_id' => $post->id,
                    ]);
            }

            if ($status === 'published') {
                $page->increment('posts_count');

                try {
                    broadcast(new PagePostPublishedEvent((int) $page->id, [
                        'id' => $post->id,
                        'content' => $post->content,
                        'created_at' => $post->created_at?->toIso8601String(),
                        'author_name' => $page->name,
                        'avatar_url' => $page->avatar_url,
                    ]));
                } catch (\Throwable $e) {
                    // Ignore broadcast error
                }
            }

            $post->load(['user.profile', 'media']);

            return $post;
        });
    }

    /**
     * পেজের পোস্ট ফিড পেজিনেশনসহ নিয়ে আসা।
     */
    public function getPageFeed(Page $page, ?User $viewer = null, int $perPage = 15): LengthAwarePaginator
    {
        $paginator = Post::where('page_id', $page->id)
            ->with(['user.profile', 'media', 'reactions'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (Post $p) => $p->toResponseArray($viewer));

        return $paginator;
    }

    /**
     * Delete unused page media from storage disk safely without deleting files referenced by other records.
     */
    public function cleanupUnusedMedia(?string $url, ?int $exceptPageId = null): bool
    {
        if (empty($url) || ! is_string($url)) {
            return false;
        }

        // Only clean up local public storage paths
        if (! str_contains($url, 'storage/') && ! str_starts_with($url, 'pages/')) {
            return false;
        }

        $relativePath = Str::after($url, 'storage/');
        $relativePath = ltrim($relativePath, '/');

        if (empty($relativePath)) {
            return false;
        }

        $normalizedUrl = '/storage/'.$relativePath;

        // Check if any other Page references this file
        $otherPageExists = Page::where('id', '!=', $exceptPageId ?? 0)
            ->where(function ($q) use ($url, $normalizedUrl, $relativePath) {
                $q->where('avatar_url', $url)
                    ->orWhere('avatar_url', $normalizedUrl)
                    ->orWhere('avatar_url', $relativePath)
                    ->orWhere('cover_image_url', $url)
                    ->orWhere('cover_image_url', $normalizedUrl)
                    ->orWhere('cover_image_url', $relativePath)
                    ->orWhere('logo_url', $url)
                    ->orWhere('logo_url', $normalizedUrl)
                    ->orWhere('logo_url', $relativePath);
            })
            ->exists();

        if ($otherPageExists) {
            return false;
        }

        // Check if UserProfile references this file
        $userProfileExists = DB::table('user_profiles')
            ->where('avatar_url', $url)
            ->orWhere('avatar_url', $normalizedUrl)
            ->orWhere('avatar_url', $relativePath)
            ->orWhere('cover_url', $url)
            ->orWhere('cover_url', $normalizedUrl)
            ->orWhere('cover_url', $relativePath)
            ->exists();

        if ($userProfileExists) {
            return false;
        }

        // Check if Media table references this file
        $mediaExists = DB::table('media')
            ->where('url', $url)
            ->orWhere('url', $normalizedUrl)
            ->orWhere('original_path', $relativePath)
            ->orWhere('path', $relativePath)
            ->exists();

        if ($mediaExists) {
            return false;
        }

        if (Storage::disk('public')->exists($relativePath)) {
            return Storage::disk('public')->delete($relativePath);
        }

        return false;
    }
}
