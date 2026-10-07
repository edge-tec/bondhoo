<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Page\EnterprisePageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ অ্যানালিটিক্স ও ইনসাইটস কন্ট্রোলার:
 * রিয়েল ডাটাবেজ কোয়েরি এগ্রিগেশন (জিরো ফেক ডেটা, জিরো মক স্ট্যাটিস্টিকস)।
 */
class PageAnalyticsV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * পেজের পারফরম্যান্স ও গ্রোথ অ্যানালিটিক্স ওভারভিউ
     */
    public function overview(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('viewAnalytics', $page)) {
            abort(403, 'You do not have permission to view analytics for this page.');
        }

        $days = min(90, max(1, (int) $request->query('days', 30)));

        $analytics = $this->enterprisePageService->getPageAnalytics($page, $days);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * পেজের অডিয়েন্স ইনসাইটস (ভৌগোলিক ও ডেমোগ্রাফিক রিয়েল ডিস্ট্রিবিউশন)
     */
    public function audience(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('viewAnalytics', $page)) {
            abort(403, 'You do not have permission to view audience insights for this page.');
        }

        $insights = $this->enterprisePageService->getAudienceInsights($page);

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }
}
