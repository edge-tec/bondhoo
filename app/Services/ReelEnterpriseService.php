<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MusicTrack;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Models\ReelCommentLike;
use App\Models\ReelMedia;
use App\Models\ReelReaction;
use App\Models\ReelSave;
use App\Models\ReelShare;
use App\Models\ReelView;
use App\Models\Report;
use App\Models\User;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\RealtimeServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * এন্টারপ্রাইজ রিলস সার্ভিস:
 * শর্ট-ফর্ম ভার্টিক্যাল (9:16) ভিডিও ফিড, মিউজিক মিক্সিং, কমেন্ট, শেয়ার, সেভ ও ভিউ পরিচালনা করে।
 * ২৪ ঘণ্টার ক্ষণস্থায়ী (ephemeral) লাইফসাইকেল নিশ্চিত করে।
 */
class ReelEnterpriseService
{
    public function __construct(
        protected MusicService $musicService,
        protected NotificationServiceInterface $notificationService,
        protected RealtimeServiceInterface $realtimeService,
        protected ProfilePrivacyService $privacyService
    ) {}

    /**
     * নতুন রিল তৈরি ও মিডিয়া/মিউজিক অ্যাটাচমেন্ট
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public function createReel(User $user, array $data): Reel
    {
        $mediaId = (int) ($data['media_id'] ?? 0);
        $media = Media::where('id', $mediaId)
            ->where('user_id', $user->id)
            ->first();

        if (! $media) {
            throw new Exception('Uploaded media file not found for reel.');
        }

        return DB::transaction(function () use ($user, $media, $data) {
            $duration = (float) ($media->metadata['duration'] ?? $data['duration'] ?? 15.0);
            $width = (int) ($media->width ?: 720);
            $height = (int) ($media->height ?: 1280);

            $trimStart = max(0.0, (float) ($data['trim_start'] ?? 0.0));
            $trimEnd = isset($data['trim_end']) && $data['trim_end'] > 0 ? (float) $data['trim_end'] : null;
            if ($trimEnd && $trimEnd > $trimStart) {
                $duration = $trimEnd - $trimStart;
            }

            $coverPath = null;
            if (! empty($data['cover_image_path'])) {
                $coverPath = (string) $data['cover_image_path'];
            } elseif (! empty($data['cover_media_id'])) {
                $coverMedia = Media::where('id', (int) $data['cover_media_id'])
                    ->where('user_id', $user->id)
                    ->first();
                $coverPath = $coverMedia?->original_path;
            } else {
                $coverPath = $media->thumbnail_path;
            }

            $musicTrack = null;
            if (! empty($data['music_track_id'])) {
                $musicTrack = MusicTrack::query()->available()->find((int) $data['music_track_id']);
            }

            $audioTitle = $data['audio_title'] ?? ($musicTrack ? $musicTrack->title : 'Original Audio');
            $audioArtist = $data['audio_artist'] ?? ($musicTrack ? $musicTrack->artist : $user->name);

            $isDraft = (bool) ($data['is_draft'] ?? false);
            $isMediaReady = $media->processing_status === 'ready';
            $status = $isMediaReady ? Reel::STATUS_READY : Reel::STATUS_PROCESSING;

            // শুধুমাত্র সফল পাবলিকেশনের পরেই ২৪ ঘণ্টার লাইফসাইকেল শুরু হবে
            $publishedAt = (! $isDraft && $isMediaReady) ? now() : null;
            $expiresAt = $publishedAt ? $publishedAt->copy()->addHours(24) : null;

            $reel = Reel::create([
                'user_id' => $user->id,
                'caption' => $data['caption'] ?? null,
                'audio_title' => $audioTitle,
                'audio_artist' => $audioArtist,
                'cover_image_path' => $coverPath,
                'music_track_id' => $musicTrack?->id,
                'audio_volume' => (int) ($data['audio_volume'] ?? 100),
                'music_volume' => (int) ($data['music_volume'] ?? 100),
                'music_start_offset' => (float) ($data['music_start_offset'] ?? 0.0),
                'trim_start' => $trimStart,
                'trim_end' => $trimEnd,
                'rotation_deg' => (int) ($data['rotation_deg'] ?? 0),
                'crop_aspect' => $data['crop_aspect'] ?? '9:16',
                'duration' => $duration,
                'width' => $width,
                'height' => $height,
                'aspect_ratio' => '9:16',
                'privacy' => $data['privacy'] ?? 'public',
                'status' => $status,
                'published_at' => $publishedAt,
                'expires_at' => $expiresAt,
                'is_expired' => false,
                'is_draft' => $isDraft,
                'location' => $data['location'] ?? null,
                'allow_comments' => (bool) ($data['allow_comments'] ?? true),
                'allow_duet' => (bool) ($data['allow_duet'] ?? true),
                'views_count' => 0,
                'likes_count' => 0,
                'comments_count' => 0,
                'shares_count' => 0,
                'saves_count' => 0,
            ]);

            // মিউজিক ব্যবহার রেকর্ড করা
            if ($musicTrack) {
                $this->musicService->recordUsage(
                    $musicTrack,
                    $reel,
                    $user,
                    (float) ($data['music_start_offset'] ?? 0.0),
                    $duration,
                    (int) ($data['music_volume'] ?? 100)
                );
            }

            // মিডিয়া লিঙ্ক করা
            $media->update([
                'mediable_type' => Reel::class,
                'mediable_id' => $reel->id,
                'collection' => 'reel',
            ]);

            // প্রাইমারি রিল মিডিয়া রেন্ডিশন তৈরি
            ReelMedia::create([
                'reel_id' => $reel->id,
                'media_id' => $media->id,
                'quality' => 'original',
                'video_path' => $media->original_path,
                'thumbnail_path' => $coverPath ?: $media->thumbnail_path,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
            ]);

            $reel->load(['user.profile', 'media', 'musicTrack']);

            if ($reel->published_at && $reel->status === Reel::STATUS_READY && ! $reel->is_draft) {
                $this->realtimeService->broadcast('reels', 'reel.created', $reel->toResponseArray($user));
            }

            return $reel;
        });
    }

    /**
     * ড্রাফট বা প্রসেসিং রিল পাবলিশ করা (২৪ ঘণ্টার লাইফসাইকেল এখানে শুরু হবে)
     *
     * @throws AuthorizationException
     */
    public function publishReel(Reel $reel, User $user): Reel
    {
        if ($reel->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to publish this reel.');
        }

        $publishedAt = now();
        $expiresAt = $publishedAt->copy()->addHours(24);

        $reel->update([
            'is_draft' => false,
            'status' => Reel::STATUS_READY,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'is_expired' => false,
        ]);

        $reel->load(['user.profile', 'media', 'musicTrack']);

        $this->realtimeService->broadcast('reels', 'reel.created', $reel->toResponseArray($user));

        return $reel;
    }

    /**
     * রিল এডিট করা (ক্যাপশন, প্রাইভেসী ইত্যাদি)
     * নিয়ম: কোনো অবস্থাতেই এডিটের মাধ্যমে ২৪ ঘণ্টার এক্সপায়ারেশন টাইম বাড়ানো বা রিসেট করা যাবে না।
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException|Exception
     */
    public function updateReel(Reel $reel, User $user, array $data): Reel
    {
        if ($reel->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to edit this reel.');
        }

        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে। মেয়াদোত্তীর্ণ রিল সম্পাদনা করা সম্ভব নয়।');
        }

        $allowed = array_intersect_key($data, array_flip([
            'caption',
            'location',
            'privacy',
            'allow_comments',
            'allow_duet',
        ]));

        $reel->update($allowed);

        return $reel->fresh(['user.profile', 'media', 'musicTrack']);
    }

    /**
     * একক রিল প্রাপ্তি (সার্ভার অথরিটেটিভ এক্সপায়ারেশন ও প্রাইভেসি প্রয়োগ)
     *
     * @throws ModelNotFoundException|AuthorizationException|Exception
     */
    public function getReel(int $id, ?User $viewer = null): Reel
    {
        $reel = Reel::with(['user.profile', 'media', 'reactions', 'musicTrack', 'saves'])->findOrFail($id);

        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে।');
        }

        // ইউজার ব্লক চেক
        if ($viewer && $this->privacyService->isBlocked($reel->user_id, $viewer)) {
            throw new AuthorizationException('You are not authorized to view this reel.');
        }

        // প্রাইভেসি রুলস চেক
        if ($reel->privacy === 'only_me' && (! $viewer || $viewer->id !== $reel->user_id)) {
            throw new AuthorizationException('This reel is private.');
        }

        if ($reel->privacy === 'friends' && (! $viewer || ($viewer->id !== $reel->user_id && ! $reel->user->isFriendWith($viewer)))) {
            throw new AuthorizationException('This reel is visible to friends only.');
        }

        return $reel;
    }

    /**
     * পাবলিক রিলস ফিড (ইনফিনিট স্ক্রল পেজিনেশন)
     */
    public function getFeed(?User $viewer = null, int $perPage = 10): LengthAwarePaginator
    {
        // মেয়াদোত্তীর্ণ রিলগুলো ডাটাবেসে ফ্ল্যাগ আপডেট করা
        Reel::where('is_expired', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_expired' => true]);

        $query = Reel::query()
            ->publicFeed()
            ->with(['user.profile', 'media', 'reactions', 'musicTrack', 'saves']);

        if ($viewer) {
            $blockedUserIds = $this->privacyService->getBlockedUserIds($viewer->id);
            if (! empty($blockedUserIds)) {
                $query->whereNotIn('user_id', $blockedUserIds);
            }
        }

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (Reel $reel) use ($viewer) {
            return $reel->toResponseArray($viewer);
        });

        return $paginator;
    }

    /**
     * নির্দিষ্ট ইউজারের রিলস তালিকা
     */
    public function getUserReels(User $targetUser, ?User $viewer = null, int $perPage = 12): LengthAwarePaginator
    {
        if ($viewer && $this->privacyService->isBlocked($viewer->id, $targetUser->id)) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        Reel::where('user_id', $targetUser->id)
            ->where('is_expired', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['is_expired' => true]);

        $query = Reel::where('user_id', $targetUser->id)
            ->active()
            ->with(['user.profile', 'media', 'musicTrack', 'reactions', 'saves']);

        // প্রাইভেসি ফিল্টার
        if (! $viewer || $viewer->id !== $targetUser->id) {
            $isFriend = $viewer && $targetUser->isFriendWith($viewer);
            if ($isFriend) {
                $query->whereIn('privacy', ['public', 'friends']);
            } else {
                $query->where('privacy', 'public');
            }
        }

        $paginator = $query->latest('id')->paginate($perPage);

        $paginator->getCollection()->transform(function (Reel $reel) use ($viewer) {
            return $reel->toResponseArray($viewer);
        });

        return $paginator;
    }

    /**
     * রিল ভিউ ও ওয়াচ টাইম রেকর্ড করা
     */
    public function recordView(Reel $reel, ?User $viewer = null, ?string $ip = null, float $watchTime = 0.0): bool
    {
        if ($reel->isExpired()) {
            return false;
        }

        ReelView::create([
            'reel_id' => $reel->id,
            'user_id' => $viewer?->id,
            'ip_address' => $ip,
            'watch_time_seconds' => $watchTime,
            'created_at' => now(),
        ]);

        $reel->increment('views_count');

        return true;
    }

    /**
     * রিল রিঅ্যাকশন / লাইক দেওয়া বা টগল করা
     *
     * @return array{reacted: bool, type: ?string, count: int}
     *
     * @throws Exception
     */
    public function reactToReel(Reel $reel, User $user, string $type = 'like'): array
    {
        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে।');
        }

        $validTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
        if (! in_array($type, $validTypes)) {
            $type = 'like';
        }

        $reaction = ReelReaction::where('reel_id', $reel->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            if ($reaction->type === $type) {
                $reaction->delete();
                $reel->decrement('likes_count');

                $res = ['reacted' => false, 'type' => null, 'count' => $reel->fresh()->likes_count];
            } else {
                $reaction->update(['type' => $type]);

                $res = ['reacted' => true, 'type' => $type, 'count' => $reel->fresh()->likes_count];
            }
        } else {
            ReelReaction::create([
                'reel_id' => $reel->id,
                'user_id' => $user->id,
                'type' => $type,
            ]);

            $reel->increment('likes_count');

            if ($reel->user_id !== $user->id) {
                try {
                    $this->notificationService->send(
                        $reel->user_id,
                        'reel.reaction',
                        [
                            'reel_id' => $reel->id,
                            'actor_id' => $user->id,
                            'actor_name' => $user->name,
                            'message' => "{$user->name} আপনার রিলে প্রতিক্রিয়া জানিয়েছেন।",
                            'type' => $type,
                        ],
                        ['database', 'broadcast']
                    );
                } catch (Exception) {
                }
            }

            $res = ['reacted' => true, 'type' => $type, 'count' => $reel->fresh()->likes_count];
        }

        // Realtime reaction broadcast
        $this->realtimeService->broadcast("reel.{$reel->id}", 'reel.reaction.updated', [
            'reel_id' => $reel->id,
            'likes_count' => $res['count'],
            'actor_id' => $user->id,
            'type' => $res['type'],
        ]);

        return $res;
    }

    /**
     * কমেন্ট যোগ করা
     *
     * @throws Exception
     */
    public function addComment(Reel $reel, User $user, string $commentText, ?int $parentId = null): ReelComment
    {
        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে। নতুন মন্তব্য যোগ করা যাবে না।');
        }

        if (! $reel->allow_comments) {
            throw new Exception('Comments are disabled for this reel.');
        }

        $commentText = trim($commentText);
        if (empty($commentText)) {
            throw new Exception('Comment cannot be empty.');
        }

        return DB::transaction(function () use ($reel, $user, $commentText, $parentId) {
            $comment = ReelComment::create([
                'reel_id' => $reel->id,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'comment' => $commentText,
                'likes_count' => 0,
                'replies_count' => 0,
            ]);

            $reel->increment('comments_count');

            if ($parentId) {
                ReelComment::where('id', $parentId)->increment('replies_count');
            }

            $comment->load(['user.profile']);

            if ($reel->user_id !== $user->id) {
                try {
                    $this->notificationService->send(
                        $reel->user_id,
                        'reel.comment',
                        [
                            'reel_id' => $reel->id,
                            'actor_id' => $user->id,
                            'actor_name' => $user->name,
                            'message' => "{$user->name} আপনার রিলে মন্তব্য করেছেন: \"".Str::limit($commentText, 40).'"',
                            'comment_id' => $comment->id,
                        ],
                        ['database', 'broadcast']
                    );
                } catch (Exception) {
                }
            }

            // Realtime comment broadcast to all connected viewers of this reel
            $this->realtimeService->broadcast("reel.{$reel->id}", 'reel.comment.created', [
                'comment' => $comment->toResponseArray($user),
                'reel_id' => $reel->id,
                'comments_count' => $reel->fresh()->comments_count,
            ]);

            return $comment;
        });
    }

    /**
     * রিলের কমেন্টসমূহ লোড করা
     *
     * @throws Exception
     */
    public function getComments(Reel $reel, ?User $viewer = null, int $perPage = 20): LengthAwarePaginator
    {
        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে।');
        }

        $paginator = ReelComment::where('reel_id', $reel->id)
            ->whereNull('parent_id')
            ->with(['user.profile', 'replies.user.profile'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (ReelComment $comment) use ($viewer) {
            $data = $comment->toResponseArray($viewer);
            $data['replies'] = $comment->replies->map(fn ($r) => $r->toResponseArray($viewer))->toArray();

            return $data;
        });

        return $paginator;
    }

    /**
     * কমেন্ট ডিলিট করা
     *
     * @throws AuthorizationException
     */
    public function deleteComment(ReelComment $comment, User $user): bool
    {
        if ($comment->user_id !== $user->id && $comment->reel->user_id !== $user->id) {
            throw new AuthorizationException('Not authorized to delete this comment.');
        }

        return DB::transaction(function () use ($comment) {
            $reel = $comment->reel;
            $deleted = $comment->delete();
            $reel->decrement('comments_count');

            $this->realtimeService->broadcast("reel.{$reel->id}", 'reel.comment.deleted', [
                'comment_id' => $comment->id,
                'reel_id' => $reel->id,
                'comments_count' => $reel->fresh()->comments_count,
            ]);

            return (bool) $deleted;
        });
    }

    /**
     * কমেন্টে লাইক টগল করা
     *
     * @return array{liked: bool, count: int}
     *
     * @throws Exception
     */
    public function likeComment(ReelComment $comment, User $user): array
    {
        if ($comment->reel?->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে।');
        }

        $existing = ReelCommentLike::where('reel_comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $comment->decrement('likes_count');

            return ['liked' => false, 'count' => $comment->fresh()->likes_count];
        }

        ReelCommentLike::create([
            'reel_comment_id' => $comment->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $comment->increment('likes_count');

        $cnt = $comment->fresh()->likes_count;

        return ['liked' => true, 'count' => $cnt, 'likes_count' => $cnt];
    }

    /**
     * রিল সেভ / বুকমার্ক টগল করা
     *
     * @return array{saved: bool, count: int, saves_count: int}
     *
     * @throws Exception
     */
    public function toggleSave(Reel $reel, User $user): array
    {
        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে।');
        }

        $save = ReelSave::where('reel_id', $reel->id)
            ->where('user_id', $user->id)
            ->first();

        if ($save) {
            $save->delete();
            $reel->decrement('saves_count');

            $cnt = $reel->fresh()->saves_count;

            return ['saved' => false, 'count' => $cnt, 'saves_count' => $cnt];
        }

        ReelSave::create([
            'reel_id' => $reel->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $reel->increment('saves_count');

        $cnt = $reel->fresh()->saves_count;

        return ['saved' => true, 'count' => $cnt, 'saves_count' => $cnt];
    }

    /**
     * ব্যবহারকারীর সংরক্ষিত রিলস
     */
    public function getUserSavedReels(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = Reel::whereHas('saves', fn ($q) => $q->where('user_id', $user->id))
            ->active()
            ->with(['user.profile', 'media', 'musicTrack'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (Reel $reel) use ($user) {
            return $reel->toResponseArray($user);
        });

        return $paginator;
    }

    /**
     * ব্যবহারকারীর ড্রাফট রিলস
     */
    public function getUserDrafts(User $user, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = Reel::where('user_id', $user->id)
            ->where('is_draft', true)
            ->with(['user.profile', 'media', 'musicTrack'])
            ->latest('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (Reel $reel) use ($user) {
            return $reel->toResponseArray($user);
        });

        return $paginator;
    }

    /**
     * রিল শেয়ার রেকর্ড করা
     *
     * @return array{share_url: string, count: int, shares_count: int}
     *
     * @throws Exception
     */
    public function recordShare(Reel $reel, ?User $user = null, string $platform = 'internal'): array
    {
        if ($reel->isExpired()) {
            throw new Exception('এই রিলটির মেয়াদ শেষ হয়ে গেছে। শেয়ার করা সম্ভব নয়।');
        }

        ReelShare::create([
            'reel_id' => $reel->id,
            'user_id' => $user?->id,
            'platform' => $platform,
            'created_at' => now(),
        ]);

        $reel->increment('shares_count');

        if ($user && $reel->user_id !== $user->id) {
            try {
                $this->notificationService->send(
                    $reel->user_id,
                    'reel.share',
                    [
                        'reel_id' => $reel->id,
                        'actor_id' => $user->id,
                        'actor_name' => $user->name,
                        'message' => "{$user->name} আপনার রিল শেয়ার করেছেন।",
                    ],
                    ['database', 'broadcast']
                );
            } catch (Exception) {
            }
        }

        $cnt = $reel->fresh()->shares_count;

        return [
            'share_url' => url("/reels/{$reel->id}"),
            'count' => $cnt,
            'shares_count' => $cnt,
        ];
    }

    /**
     * রিল রিপোর্ট করা
     */
    public function reportReel(Reel $reel, User $user, string $reason, ?string $details = null): bool
    {
        $existing = Report::where('reporter_id', $user->id)
            ->where('reportable_type', Reel::class)
            ->where('reportable_id', $reel->id)
            ->first();

        if ($existing) {
            return false;
        }

        Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => Reel::class,
            'reportable_id' => $reel->id,
            'reason' => $reason,
            'details' => $details,
            'status' => Report::STATUS_PENDING,
            'created_at' => now(),
        ]);

        return true;
    }

    /**
     * রিল ডিলিট করা
     *
     * @throws AuthorizationException
     */
    public function deleteReel(Reel $reel, User $user): bool
    {
        if ($reel->user_id !== $user->id) {
            throw new AuthorizationException('You are not authorized to delete this reel.');
        }

        $reelId = $reel->id;
        $deleted = $reel->delete();

        if ($deleted) {
            $this->realtimeService->broadcast('reels', 'reel.deleted', ['reel_id' => $reelId]);
            $this->realtimeService->broadcast("reel.{$reelId}", 'reel.deleted', ['reel_id' => $reelId]);
        }

        return (bool) $deleted;
    }

    /**
     * মেয়াদোত্তীর্ণ রিলগুলো এক্সপায়ার করা ও রিয়েল-টাইমে ব্রডকাস্ট করা
     */
    public function expireStaleReels(): int
    {
        $staleReels = Reel::where('is_expired', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($staleReels as $stale) {
            $stale->update(['is_expired' => true]);
            $count++;

            $this->realtimeService->broadcast('reels', 'reel.expired', [
                'reel_id' => $stale->id,
                'user_id' => $stale->user_id,
            ]);
            $this->realtimeService->broadcast("reel.{$stale->id}", 'reel.expired', [
                'reel_id' => $stale->id,
            ]);
        }

        return $count;
    }
}
