<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\GlobalSearchRequest;
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
    public function search(GlobalSearchRequest $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user('web') ?? auth('sanctum')->user() ?? auth('web')->user();
        $validated = $request->validated();
        $query = (string) ($validated['q'] ?? $validated['query'] ?? $validated['search'] ?? '');
        $type = (string) ($validated['type'] ?? 'all');
        $limit = min((int) ($validated['limit'] ?? 15), 50);
        $suggest = (bool) ($validated['suggest'] ?? false);
        $filters = [
            'location' => $validated['location'] ?? null,
            'country' => $validated['country'] ?? null,
            'industry' => $validated['industry'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'category' => $validated['category'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'job_type' => $validated['job_type'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'relevance',
        ];

        $results = $this->searchService->search($query, $type, $user, $limit, $suggest, $filters);

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
     * নির্দিষ্ট সার্চ হিস্ট্রি আইটেম মুছে ফেলা।
     */
    public function removeHistoryItem(Request $request, string $term): JsonResponse
    {
        $user = $request->user();
        $updated = $this->searchService->removeSearchHistoryItem($user, $term);

        return $this->successResponse(
            data: $updated,
            message: 'Search history item removed successfully.'
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
