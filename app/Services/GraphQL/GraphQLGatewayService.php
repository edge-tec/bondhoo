<?php

namespace App\Services\GraphQL;

use App\Models\Post;
use App\Models\User;
use App\Services\AI\JugajugAIAssistant;
use App\Services\Feed\AIFeedRankingEngine;

class GraphQLGatewayService
{
    protected AIFeedRankingEngine $rankingEngine;

    protected JugajugAIAssistant $aiAssistant;

    public function __construct(AIFeedRankingEngine $rankingEngine, JugajugAIAssistant $aiAssistant)
    {
        $this->rankingEngine = $rankingEngine;
        $this->aiAssistant = $aiAssistant;
    }

    /**
     * Execute GraphQL query or mutation payload.
     *
     * @param  array<string, mixed>  $variables
     * @return array{data?: array<string, mixed>, errors?: array<int, array{message: string}>}
     */
    public function execute(string $query, array $variables = [], ?User $viewer = null): array
    {
        $normalized = trim($query);

        // Simulated GraphQL query execution engine
        if (str_starts_with($normalized, 'mutation')) {
            return $this->resolveMutation($normalized, $variables, $viewer);
        }

        return $this->resolveQuery($normalized, $variables, $viewer);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function resolveQuery(string $query, array $variables, ?User $viewer): array
    {
        $data = [];

        if (str_contains($query, 'me')) {
            $data['me'] = $viewer ? [
                'id' => $viewer->id,
                'name' => $viewer->name,
                'email' => $viewer->email,
                'username' => $viewer->username,
            ] : null;
        }

        if (str_contains($query, 'feed')) {
            $limit = $variables['limit'] ?? 10;
            $posts = Post::with('user')->where('audience', 'public')->latest('id')->limit($limit)->get();
            $data['feed'] = $this->rankingEngine->rankFeed($posts, $viewer, $limit)->map(function ($p) {
                return [
                    'id' => $p->id,
                    'content' => $p->content,
                    'author' => ['id' => $p->user?->id, 'name' => $p->user?->name],
                    'rankScore' => $p->ai_rank_score ?? 1.0,
                    'createdAt' => $p->created_at->toIso8601String(),
                ];
            })->toArray();
        }

        if (str_contains($query, 'aiUsage')) {
            $data['aiUsage'] = [
                'status' => 'operational',
                'activeModel' => 'gemini-2.5-flash',
                'tier' => 'Enterprise Sovereign',
            ];
        }

        return ['data' => $data];
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function resolveMutation(string $mutation, array $variables, ?User $viewer): array
    {
        $data = [];

        if (str_contains($mutation, 'aiChat')) {
            $msg = $variables['message'] ?? 'Hello';
            $chatResult = $this->aiAssistant->chat($msg, $viewer?->id);
            $data['aiChat'] = [
                'reply' => $chatResult['reply'],
                'model' => $chatResult['model'],
                'tokens' => $chatResult['tokens'],
            ];
        }

        return ['data' => $data];
    }
}
