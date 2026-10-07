<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\Messenger\DeviceSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceSessionController extends Controller
{
    public function __construct(
        protected DeviceSessionService $deviceSessionService
    ) {}

    /**
     * List active device sessions for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = $this->deviceSessionService->getActiveSessions($request->user());

        return $this->successResponse(
            data: $sessions,
            message: 'Active device sessions retrieved successfully.'
        );
    }

    /**
     * Register or update current device session.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:128'],
            'platform' => ['nullable', 'string', 'in:web,android,ios,desktop,windows,macos,linux'],
            'app_version' => ['nullable', 'string', 'max:32'],
            'push_token' => ['nullable', 'string'],
        ]);

        $session = $this->deviceSessionService->registerDevice($request->user(), $validated);

        return $this->successResponse(
            data: $session,
            message: 'Device session registered successfully.'
        );
    }

    /**
     * Revoke a specific device session.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $revoked = $this->deviceSessionService->revokeSession($request->user(), $id);

        if (! $revoked) {
            return $this->errorResponse('Device session not found or already revoked.', 404);
        }

        return $this->successResponse(
            data: ['revoked' => true],
            message: 'Device session revoked successfully.'
        );
    }
}
