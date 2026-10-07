<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * সার্চ কন্ট্রোলার:
 * ইউজার, পোস্ট, গ্রুপ এবং পেজজুড়ে সার্বজনীন সার্চ পরিচালনা করে।
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
        $user = $request->user();
        $query = (string) $request->query('q', '');
        $type = (string) $request->query('type', 'all');
        $limit = (int) $request->query('limit', 10);

        $results = $this->searchService->search($query, $type, $user, $limit);

        return $this->successResponse(
            data: $results,
            message: 'Search completed successfully.'
        );
    }
}
