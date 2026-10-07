<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Services\StoryEnterpriseService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * এন্টারপ্রাইজ স্টোরিজ এপিআই কন্ট্রোলার (v2):
 * মাল্টি-মিডিয়া স্টোরি, মিউজিক, ইন্টারেক্টিভ পোল, রিঅ্যাকশন, রিপ্লাই এবং এন্টারপ্রাইজ ফিড হ্যান্ডেল করে।
 * ২৪ ঘণ্টার সার্ভার-অথরিটেটিভ এক্সপায়ারেশন প্রয়োগ করে।
 */
class StoryV2ApiController extends Controller
{
    public function __construct(
        protected StoryEnterpriseService $storyService
    ) {}

    /**
     * সক্রিয় স্টোরিজ ফিড
     */
    public function feed(Request $request): JsonResponse
    {
        $feed = $this->storyService->getActiveFeed($request->user());

        return response()->json([
            'success' => true,
            'data' => $feed,
        ]);
    }

    /**
     * নতুন এন্টারপ্রাইজ স্টোরি তৈরি
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:text,media'],
            'content' => ['nullable', 'string', 'max:2000'],
            'media_ids' => ['nullable', 'array'],
            'media_ids.*' => ['integer', 'exists:media,id'],
            'background_color' => ['nullable', 'string', 'max:255'],
            'font_family' => ['nullable', 'string', 'max:50'],
            'music_title' => ['nullable', 'string', 'max:100'],
            'music_track_id' => ['nullable', 'integer', 'exists:music_tracks,id'],
            'music_start_offset' => ['nullable', 'numeric', 'min:0'],
            'music_volume' => ['nullable', 'integer', 'min:0', 'max:100'],
            'emoji' => ['nullable', 'string', 'max:20'],
            'interactive_sticker' => ['nullable', 'array'],
            'location' => ['nullable', 'string', 'max:150'],
            'privacy' => ['nullable', 'string', 'in:public,friends,only_me'],
            'allow_replies' => ['nullable', 'boolean'],
            'is_draft' => ['nullable', 'boolean'],
        ]);

        try {
            $story = $this->storyService->createStory($request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Story published successfully.',
                'data' => $story->toResponseArray($request->user()),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * স্টোরি বিস্তারিত প্রদর্শন (সার্ভার অথরিটেটিভ এক্সপায়ারেশন প্রয়োগ)
     */
    public function show(Request $request, Story $story): JsonResponse
    {
        try {
            $activeStory = $this->storyService->getStory($story->id, $request->user());

            return response()->json([
                'success' => true,
                'data' => $activeStory->toResponseArray($request->user()),
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
     * স্টোরি ভিউ রেকর্ড করা
     */
    public function recordView(Request $request, Story $story): JsonResponse
    {
        if ($story->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।',
            ], 410);
        }

        $recorded = $this->storyService->recordView($story, $request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'view_recorded' => $recorded,
                'views_count' => $story->fresh()->views_count,
            ],
        ]);
    }

    /**
     * স্টোরিতে রিঅ্যাকশন দেওয়া
     */
    public function react(Request $request, Story $story): JsonResponse
    {
        if ($story->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।',
            ], 410);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:like,love,care,haha,wow,sad,angry'],
        ]);

        try {
            $result = $this->storyService->reactToStory($story, $request->user(), $validated['type']);

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
     * স্টোরিতে রিপ্লাই পাঠানো
     */
    public function reply(Request $request, Story $story): JsonResponse
    {
        if ($story->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে। রিপ্লাই পাঠানো সম্ভব নয়।',
            ], 410);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $reply = $this->storyService->replyToStory($story, $request->user(), $validated['message']);

            return response()->json([
                'success' => true,
                'message' => 'Reply sent successfully.',
                'data' => [
                    'id' => $reply->id,
                    'message' => $reply->message,
                    'created_at' => $reply->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'is_expired' => $story->isExpired(),
                'message' => $e->getMessage(),
            ], $story->isExpired() ? 410 : 422);
        }
    }

    /**
     * স্টোরির ভিউয়ারদের তালিকা
     */
    public function viewers(Request $request, Story $story): JsonResponse
    {
        if ($story->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।',
            ], 410);
        }

        try {
            $viewers = $this->storyService->getStoryViewers($story, $request->user());

            return response()->json([
                'success' => true,
                'data' => $viewers->items(),
                'meta' => [
                    'total' => $viewers->total(),
                    'current_page' => $viewers->currentPage(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * স্টোরি আর্কাইভ করা
     */
    public function archive(Request $request, Story $story): JsonResponse
    {
        try {
            $this->storyService->archiveStory($story, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Story archived successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * আর্কাইভড স্টোরিজ তালিকা
     */
    public function archived(Request $request): JsonResponse
    {
        $archived = $this->storyService->getArchivedStories($request->user());

        return response()->json([
            'success' => true,
            'data' => $archived->items(),
            'meta' => [
                'total' => $archived->total(),
            ],
        ]);
    }

    /**
     * স্টোরি ডিলিট করা
     */
    public function destroy(Request $request, Story $story): JsonResponse
    {
        try {
            $this->storyService->deleteStory($story, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Story deleted successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * স্টোরি রিপোর্ট করা
     */
    public function report(Request $request, Story $story): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $reported = $this->storyService->reportStory($story, $request->user(), $validated['reason'], $validated['details'] ?? null);

        if (! $reported) {
            return response()->json([
                'success' => true,
                'message' => 'আপনি ইতিমধ্যে এই স্টোরিটির বিরুদ্ধে রিপোর্ট জমা দিয়েছেন। এটি পর্যালোচনায় রয়েছে।',
                'already_reported' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'রিপোর্ট সফলভাবে জমা হয়েছে। আমাদের প্ল্যাটফর্ম নিরাপদ রাখতে সহায়তার জন্য ধন্যবাদ।',
        ]);
    }

    /**
     * স্টোরি শেয়ার রেকর্ড করা
     */
    public function share(Request $request, Story $story): JsonResponse
    {
        if ($story->isExpired()) {
            return response()->json([
                'success' => false,
                'is_expired' => true,
                'message' => 'এই স্টোরিটির মেয়াদ শেষ হয়ে গেছে।',
            ], 410);
        }

        try {
            $platform = $request->input('platform', 'internal');
            $result = $this->storyService->recordShare($story, $request->user(), $platform);

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
}
