<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * অ্যানালিটিক্স কন্ট্রোলার:
 * পোস্টের ইমপ্রেশন ট্র্যাকিং এবং কনটেন্ট ক্রিয়েটরদের জন্য ইনসাইটস প্রদর্শন করে।
 */
class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    /**
     * পোস্টে ইমপ্রেশন রেকর্ড করা।
     */
    public function impression(Request $request, int $id): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $post = Post::findOrFail($id);

        $this->analyticsService->recordImpression($post, $viewer);

        return $this->successResponse(
            message: 'Impression recorded.'
        );
    }

    /**
     * পোস্টের পারফরম্যান্স ইনসাইটস দেখা (শুধুমাত্র লেখক বা অ্যাডমিন)।
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $post = Post::findOrFail($id);

        $analytics = $this->analyticsService->getPostAnalytics($post, $user);

        return $this->successResponse(
            data: $analytics,
            message: 'Post analytics retrieved successfully.'
        );
    }
}
