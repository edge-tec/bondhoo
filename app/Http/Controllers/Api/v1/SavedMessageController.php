<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Contracts\MessengerServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedMessageController extends Controller
{
    public function __construct(
        protected MessengerServiceInterface $messengerService
    ) {}

    /**
     * List user's saved messages.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $saved = $this->messengerService->getSavedMessages($request->user(), $perPage);

        return $this->successResponse(
            data: $saved->getCollection()->map(fn ($sm) => [
                'id' => $sm->id,
                'saved_at' => $sm->saved_at->toIso8601String(),
                'message' => $sm->message?->toResponseArray($request->user()),
            ])->values()->all(),
            message: 'Saved messages retrieved successfully.',
            meta: [
                'current_page' => $saved->currentPage(),
                'last_page' => $saved->lastPage(),
                'total' => $saved->total(),
                'per_page' => $saved->perPage(),
            ]
        );
    }

    /**
     * Save a message.
     */
    public function store(Request $request, int $messageId): JsonResponse
    {
        $saved = $this->messengerService->saveMessage($request->user(), $messageId);

        return $this->successResponse(
            data: [
                'id' => $saved->id,
                'message_id' => $saved->message_id,
                'saved_at' => $saved->saved_at->toIso8601String(),
            ],
            message: 'Message saved successfully.',
            statusCode: 201
        );
    }

    /**
     * Remove message from saved.
     */
    public function destroy(Request $request, int $messageId): JsonResponse
    {
        $unsaved = $this->messengerService->unsaveMessage($request->user(), $messageId);

        return $this->successResponse(
            data: ['unsaved' => $unsaved],
            message: 'Message removed from saved.'
        );
    }
}
