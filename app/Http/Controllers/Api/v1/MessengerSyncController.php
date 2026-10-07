<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Messenger\SyncEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessengerSyncController extends Controller
{
    public function __construct(
        protected SyncEventService $syncEventService
    ) {}

    /**
     * Synchronize events since a specific event ID sequence.
     */
    public function sync(Request $request): JsonResponse
    {
        $sinceId = (int) $request->query('since_id', 0);
        $limit = (int) $request->query('limit', 100);

        $result = $this->syncEventService->getEventsSince(
            user: $request->user(),
            sinceId: $sinceId,
            limit: $limit
        );

        return $this->successResponse(
            data: $result,
            message: 'Events synchronized successfully.'
        );
    }
}
