<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MemoriesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemoriesApiController extends Controller
{
    public function __construct(
        protected MemoriesService $memoriesService
    ) {}

    /**
     * GET /api/v1/memories
     *
     * Get historical memories for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $memories = $this->memoriesService->getMemoriesForUser($user);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => $memories,
            'message' => 'স্মৃতিচারণ পোস্ট সফলভাবে লোড হয়েছে।',
        ]);
    }
}
