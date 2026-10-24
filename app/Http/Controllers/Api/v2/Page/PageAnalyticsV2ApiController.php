<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Page\EnterprisePageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7d,30d,90d,custom'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'timezone' => ['nullable', 'string'],
        ]);

        $timezone = $validated['timezone'] ?? config('app.timezone', 'UTC');
        if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            $timezone = 'UTC';
        }

        $startDate = null;
        $endDate = null;
        $days = 30;

        $period = $validated['period'] ?? null;
        if ($period === '7d' || ($validated['days'] ?? null) === 7) {
            $days = 7;
        } elseif ($period === '90d' || ($validated['days'] ?? null) === 90) {
            $days = 90;
        } elseif ($period === 'custom' || (! empty($validated['start_date']) && ! empty($validated['end_date']))) {
            $startDate = Carbon::parse($validated['start_date'], $timezone)->startOfDay();
            $endDate = Carbon::parse($validated['end_date'], $timezone)->endOfDay();
            $days = max(1, (int) $startDate->diffInDays($endDate) + 1);
        } else {
            $days = min(365, max(1, (int) ($validated['days'] ?? $request->query('days', 30))));
        }

        $analytics = $this->enterprisePageService->getPageAnalytics(
            $page,
            $days,
            $startDate,
            $endDate,
            $timezone
        );

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
