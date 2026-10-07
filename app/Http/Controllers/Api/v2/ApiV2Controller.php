<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Feed\AIFeedRankingEngine;
use App\Services\Search\VectorSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiV2Controller extends Controller
{
    protected AIFeedRankingEngine $rankingEngine;

    protected VectorSearchService $vectorSearch;

    public function __construct(AIFeedRankingEngine $rankingEngine, VectorSearchService $vectorSearch)
    {
        $this->rankingEngine = $rankingEngine;
        $this->vectorSearch = $vectorSearch;
    }

    /**
     * API v2 root discovery and gateway information.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'version' => '2.0.0-enterprise',
            'platform' => 'Jugajug (যোগাযোগ)',
            'grade' => 'Bangladesh Government Grade + Facebook Scale',
            'modules' => [
                'ai' => '/api/v2/ai',
                'feed' => '/api/v2/feed/ranked',
                'search' => '/api/v2/search/hybrid',
                'live' => '/api/v2/live',
                'marketplace' => '/api/v2/marketplace',
                'federation' => '/api/v2/federation',
                'graphql' => '/api/v2/graphql',
                'analytics' => '/api/v2/analytics',
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * AI-ranked personalized feed with cursor pagination.
     */
    public function rankedFeed(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min(50, (int) $request->get('limit', 20));

        $posts = Post::with(['user', 'media', 'reactions', 'comments'])
            ->where('audience', 'public')
            ->latest('id')
            ->limit(100)
            ->get();

        $ranked = $this->rankingEngine->rankFeed($posts, $user, $limit);

        return response()->json([
            'status' => 'success',
            'count' => $ranked->count(),
            'data' => $ranked,
        ]);
    }

    /**
     * Hybrid keyword + vector semantic search.
     */
    public function hybridSearch(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $limit = min(30, (int) $request->get('limit', 10));

        if (empty($query)) {
            return response()->json(['status' => 'error', 'message' => 'Query is required'], 422);
        }

        $results = $this->vectorSearch->hybridSearch($query, $limit);

        return response()->json([
            'query' => $query,
            'count' => count($results),
            'results' => $results,
        ]);
    }
}
