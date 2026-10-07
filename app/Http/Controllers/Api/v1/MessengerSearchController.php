<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessengerSearchController extends Controller
{
    public function __construct(
        protected MessengerServiceInterface $messengerService
    ) {}

    /**
     * Search messages globally or within a specific conversation.
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $conversationId = $request->has('conversation_id') ? (int) $request->query('conversation_id') : null;
        $type = (string) $request->query('type', 'all'); // all, media, file, voice
        $perPage = (int) $request->query('per_page', 20);

        $results = $this->messengerService->searchMessenger(
            user: $request->user(),
            query: $query,
            conversationId: $conversationId,
            type: $type,
            perPage: $perPage
        );

        return $this->successResponse(
            data: $results->getCollection()->map(fn ($msg) => $msg->toResponseArray($request->user()))->values()->all(),
            message: 'Search completed successfully.',
            meta: [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
                'per_page' => $results->perPage(),
            ]
        );
    }
}
