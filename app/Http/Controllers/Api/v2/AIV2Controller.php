<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Services\AI\AIUsageService;
use App\Services\AI\JugajugAIAssistant;
use App\Services\AI\PromptManager;
use App\Services\Search\VectorSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIV2Controller extends Controller
{
    protected JugajugAIAssistant $assistant;

    protected AIUsageService $usageService;

    protected PromptManager $promptManager;

    protected VectorSearchService $vectorSearch;

    public function __construct(
        JugajugAIAssistant $assistant,
        AIUsageService $usageService,
        PromptManager $promptManager,
        VectorSearchService $vectorSearch
    ) {
        $this->assistant = $assistant;
        $this->usageService = $usageService;
        $this->promptManager = $promptManager;
        $this->vectorSearch = $vectorSearch;
    }

    /**
     * AI Interactive Chat with memory.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:4000']);

        $user = $request->user();
        $response = $this->assistant->chat(
            $request->input('message'),
            $user?->id,
            $request->input('conversation_id'),
            $request->only(['provider', 'model'])
        );

        return response()->json(['status' => 'success', 'data' => $response]);
    }

    /**
     * AI Smart comment suggestions.
     */
    public function suggestComment(Request $request): JsonResponse
    {
        $request->validate(['post_text' => 'required|string|max:2000']);

        $result = $this->assistant->suggestComment($request->input('post_text'), $request->user()?->id);

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * AI Caption generator.
     */
    public function writeCaption(Request $request): JsonResponse
    {
        $request->validate(['topic' => 'required|string|max:500']);

        $result = $this->assistant->writeCaption(
            $request->input('topic'),
            $request->input('tone', 'friendly'),
            $request->user()?->id
        );

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * AI Translation (Bengali <-> English).
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:5000']);

        $result = $this->assistant->translate(
            $request->input('text'),
            $request->input('target', 'bn'),
            $request->user()?->id
        );

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * AI Content moderation filter.
     */
    public function moderate(Request $request): JsonResponse
    {
        $request->validate(['content' => 'required|string|max:5000']);

        $result = $this->assistant->moderate($request->input('content'), $request->user()?->id);

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * Bangladesh Government Citizen e-Services Assistant.
     */
    public function citizenServices(Request $request): JsonResponse
    {
        $request->validate(['inquiry' => 'required|string|max:2000']);

        $result = $this->assistant->citizenServicesAssistant($request->input('inquiry'), $request->user()?->id);

        return response()->json(['status' => 'success', 'data' => $result]);
    }

    /**
     * Semantic vector similarity search.
     */
    public function semanticSearch(Request $request): JsonResponse
    {
        $request->validate(['query' => 'required|string|max:500']);

        $results = $this->vectorSearch->semanticSearch(
            $request->input('query'),
            $request->input('type'),
            min(20, (int) $request->input('limit', 10))
        );

        return response()->json(['status' => 'success', 'results' => $results]);
    }

    /**
     * AI Usage & Token meter overview.
     */
    public function usageSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Authentication required to view AI usage metrics.',
            ], 401);
        }

        // Only administrators can view system-wide aggregate token consumption
        if ($user->hasRole(['admin', 'ADMIN', 'SUPER_ADMIN']) || ($user->is_admin ?? false)) {
            $summary = $this->usageService->getSystemUsageSummary(30);

            return response()->json(['status' => 'success', 'scope' => 'system', 'summary' => $summary]);
        }

        $userUsage = $this->usageService->getUserUsage($user->id);

        return response()->json(['status' => 'success', 'scope' => 'user', 'summary' => $userUsage]);
    }
}
