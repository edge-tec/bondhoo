<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * সার্চ কন্ট্রোলার:
 * ইউজার, পোস্ট, গ্রুপ এবং পেজজুড়ে সার্বজনীন সার্চ ও সার্চ হিস্ট্রি পরিচালনা করে।
 */
class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * সার্বজনীন সার্চ কোয়েরি এক্সিকিউট করে ফলাফল রিটার্ন করে।
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user('web') ?? auth('sanctum')->user() ?? auth('web')->user();
        $query = (string) ($request->query('q') ?? $request->query('query') ?? $request->query('search') ?? $request->input('q') ?? $request->input('query') ?? '');
        $type = (string) $request->query('type', 'all');
        $limit = min((int) ($request->query('limit', 15)), 50);
        $suggest = $request->boolean('suggest');

        $results = $this->searchService->search($query, $type, $user, $limit, $suggest);

        return $this->successResponse(
            data: $results,
            message: 'Search completed successfully.'
        );
    }

    /**
     * ইউজারের সাম্প্রতিক সার্চ হিস্ট্রি তালিকা।
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $history = $this->searchService->getSearchHistory($user);

        return $this->successResponse(
            data: $history,
            message: 'Search history retrieved.'
        );
    }

    /**
     * সার্চ হিস্ট্রি মুছে ফেলা।
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->searchService->clearSearchHistory($user);

        return $this->successResponse(
            data: [],
            message: 'Search history cleared successfully.'
        );
    }
}
