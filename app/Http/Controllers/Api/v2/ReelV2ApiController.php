<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Models\User;
use App\Services\ReelEnterpriseService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * এন্টারপ্রাইজ রিলস এপিআই কন্ট্রোলার (v2):
 * শর্ট-ফর্ম ভার্টিক্যাল (9:16) ভিডিও আপলোড, মিউজিক মিক্সিং, ফিড, কমেন্ট, শেয়ার, সেভ ও রিঅ্যাকশন হ্যান্ডেল করে।
 * ২৪ ঘণ্টার সার্ভার-অথরিটেটিভ এক্সপায়ারেশন প্রয়োগ করে।
 */
class ReelV2ApiController extends Controller
{
    public function __construct(
        protected ReelEnterpriseService $reelService
    ) {}

    /**
     * পাবলিক রিলস ফিড
     */
    public function feed(Request $request): JsonResponse
    {
        $reels = $this->reelService->getFeed($request->user(), (int) $request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $reels->items(),
            'meta' => [
                'current_page' => $reels->currentPage(),
                'last_page' => $reels->lastPage(),
                'total' => $reels->total(),
            ],
        ]);
    }

    /**
     * নতুন রিল তৈরি ও আপলোড করা মিডিয়া/মিউজিক অ্যাটাচ করা
     */
    public function store(Request $request): JsonResponse
    {
        $raw = $request->all();
        if (! isset($raw['allow_comments']) && isset($raw['comments_enabled'])) {
            $raw['allow_comments'] = filter_var($raw['comments_enabled'], FILTER_VALIDATE_BOOLEAN);
        }
        if (! isset($raw['allow_duet']) && isset($raw['duet_enabled'])) {
            $raw['allow_duet'] = filter_var($raw['duet_enabled'], FILTER_VALIDATE_BOOLEAN);
        }
        $request->merge($raw);

        $validated = $request->validate([
            'media_id' => ['required', 'integer', 'exists:media,id'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'audio_title' => ['nullable', 'string', 'max:100'],
            'audio_artist' => ['nullable', 'string', 'max:100'],
            'cover_image_path' => ['nullable', 'string', 'max:500'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'music_track_id' => ['nullable', 'integer', 'exists:music_tracks,id'],
            'audio_volume' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'music_volume' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'music_start_offset' => ['nullable', 'numeric', 'min:0'],
            'trim_start' => ['nullable', 'numeric', 'min:0'],
            'trim_end' => ['nullable', 'numeric', 'min:0'],
            'rotation_deg' => ['nullable', 'integer', 'in:0,90,180,270'],
            'crop_aspect' => ['nullable', 'string', 'in:9:16,1:1,16:9'],
            'privacy' => ['nullable', 'string', 'in:public,friends,only_me'],
            'location' => ['nullable', 'string', 'max:150'],
            'allow_comments' => ['nullable', 'boolean'],
            'comments_enabled' => ['nullable', 'boolean'],
            'allow_duet' => ['nullable', 'boolean'],
            'duet_enabled' => ['nullable', 'boolean'],
            'is_draft' => ['nullable', 'boolean'],
        ]);

        try {
            $reel = $this->reelService->createReel($request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Reel uploaded successfully.',
                'data' => $reel->toResponseArray($request->user()),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ড্রাফট রিল পাবলিশ করা
     */
    public function publish(Request $request, Reel $reel): JsonResponse
    {
        try {
            $publishedReel = $this->reelService->publishReel($reel, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reel published successfully.',
                'data' => $publishedReel->toResponseArray($request->user()),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * রিল আপডেট / এডিট করা
     * নিয়ম: এডিটের মাধ্যমে কখনো ২৪ ঘণ্টার লাইফটাইম রিসেট বা বৃদ্ধি পাবে না।
     */
    public function update(Request $request, Reel $reel): JsonResponse
    {
        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:150'],
            'privacy' => ['nullable', 'string', 'in:public,friends,only_me'],
            'allow_comments' => ['nullable', 'boolean'],
            'allow_duet' => ['nullable', 'boolean'],
        ]);

        try {
            $updatedReel = $this->reelService->updateReel($reel, $request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Reel updated successfully.',
                'data' => $updatedReel->toResponseArray($request->user()),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => $reel->isExpired(),
                'message' => $e->getMessage(),
            ], $reel->isExpired() ? 410 : 422);
        }
    }

    /**
     * নির্দিষ্ট রিল প্রদর্শন (সার্ভার-অথরিটেটিভ এক্সপায়ারেশন প্রয়োগ)
     */
    public function show(Request $request, Reel $reel): JsonResponse
    {
        try {
            $activeReel = $this->reelService->getReel($reel->id, $request->user());

            return response()->json([
                'success' => true,
                'data' => $activeReel->toResponseArray($request->user()),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => $e->getMessage(),
            ], 410);
        }
    }

    /**
     * নির্দিষ্ট ইউজারের রিলস
     */
    public function userReels(Request $request, User $user): JsonResponse
    {
        $reels = $this->reelService->getUserReels($user, $request->user(), (int) $request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $reels->items(),
            'meta' => [
                'current_page' => $reels->currentPage(),
                'last_page' => $reels->lastPage(),
                'total' => $reels->total(),
            ],
        ]);
    }

    /**
     * রিল ভিউ ও ওয়াচ টাইম রেকর্ড করা
     */
    public function recordView(Request $request, Reel $reel): JsonResponse
    {
        if ($reel->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই রিলটির মেয়াদ শেষ হয়ে গেছে।',
            ], 410);
        }

        $watchTime = (float) $request->input('watch_time_seconds', 0.0);
        $recorded = $this->reelService->recordView($reel, $request->user(), $request->ip(), $watchTime);

        return response()->json([
            'success' => true,
            'data' => [
                'view_recorded' => $recorded,
                'views_count' => $reel->fresh()->views_count,
            ],
        ]);
    }

    /**
     * রিলে লাইক / রিঅ্যাকশন দেওয়া
     */
    public function react(Request $request, Reel $reel): JsonResponse
    {
        if ($reel->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই রিলটির মেয়াদ শেষ হয়ে গেছে।',
            ], 410);
        }

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:like,love,haha,wow,sad,angry'],
        ]);

        try {
            $result = $this->reelService->reactToReel($reel, $request->user(), $validated['type'] ?? 'like');

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => $reel->isExpired(),
                'message' => $e->getMessage(),
            ], 410);
        }
    }

    /**
     * রিলের কমেন্টসমূহ লোড করা
     */
    public function comments(Request $request, Reel $reel): JsonResponse
    {
        try {
            $paginator = $this->reelService->getComments($reel, $request->user(), (int) $request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => $e->getMessage(),
            ], 410);
        }
    }

    /**
     * রিলে নতুন কমেন্ট যোগ করা
     */
    public function storeComment(Request $request, Reel $reel): JsonResponse
    {
        if ($reel->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই রিলটির মেয়াদ শেষ হয়ে গেছে। মন্তব্য করা সম্ভব নয়।',
            ], 410);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:reel_comments,id'],
        ]);

        try {
            $comment = $this->reelService->addComment(
                $reel,
                $request->user(),
                $validated['comment'],
                $validated['parent_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Comment posted successfully.',
                'data' => $comment->toResponseArray($request->user()),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => $reel->isExpired(),
                'message' => $e->getMessage(),
            ], $reel->isExpired() ? 410 : 422);
        }
    }

    /**
     * কমেন্ট ডিলিট করা
     */
    public function deleteComment(Request $request, ReelComment $comment): JsonResponse
    {
        try {
            $this->reelService->deleteComment($comment, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * কমেন্টে লাইক টগল করা
     */
    public function likeComment(Request $request, ReelComment $comment): JsonResponse
    {
        try {
            $result = $this->reelService->likeComment($comment, $request->user());

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => $e->getMessage(),
            ], 410);
        }
    }

    /**
     * রিল সেভ / বুকমার্ক টগল করা
     */
    public function save(Request $request, Reel $reel): JsonResponse
    {
        try {
            $result = $this->reelService->toggleSave($reel, $request->user());

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => $e->getMessage(),
            ], 410);
        }
    }

    /**
     * সংরক্ষিত রিলস তালিকা
     */
    public function savedReels(Request $request): JsonResponse
    {
        $paginator = $this->reelService->getUserSavedReels($request->user(), (int) $request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * ড্রাফট রিলস তালিকা
     */
    public function drafts(Request $request): JsonResponse
    {
        $paginator = $this->reelService->getUserDrafts($request->user(), (int) $request->input('per_page', 12));

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * রিল শেয়ার করা
     */
    public function share(Request $request, Reel $reel): JsonResponse
    {
        try {
            $platform = $request->input('platform', 'internal');
            $result = $this->reelService->recordShare($reel, $request->user(), $platform);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => $e->getMessage(),
            ], 410);
        }
    }

    /**
     * রিল রিপোর্ট করা
     */
    public function report(Request $request, Reel $reel): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $reported = $this->reelService->reportReel($reel, $request->user(), $validated['reason'], $validated['details'] ?? null);

        if (! $reported) {
            return response()->json([
                'success' => true,
                'message' => 'আপনি ইতিমধ্যে এই রিলটির বিরুদ্ধে রিপোর্ট জমা দিয়েছেন। এটি মডারেশন পর্যালোচনায় রয়েছে।',
                'already_reported' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'রিপোর্ট সফলভাবে জমা হয়েছে। আমাদের প্ল্যাটফর্ম নিরাপদ রাখতে সহায়তার জন্য ধন্যবাদ।',
        ]);
    }

    /**
     * রিল ডিলিট করা
     */
    public function destroy(Request $request, Reel $reel): JsonResponse
    {
        try {
            $this->reelService->deleteReel($reel, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Reel deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
