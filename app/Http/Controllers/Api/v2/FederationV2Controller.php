<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Federation\ActivityPubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FederationV2Controller extends Controller
{
    protected ActivityPubService $federationService;

    public function __construct(ActivityPubService $federationService)
    {
        $this->federationService = $federationService;
    }

    /**
     * WebFinger discovery endpoint RFC 7033.
     */
    public function webfinger(Request $request): JsonResponse
    {
        $resource = $request->query('resource', '');
        // Example resource: acct:user@jugajug.com
        if (str_starts_with($resource, 'acct:')) {
            $parts = explode('@', substr($resource, 5));
            $username = $parts[0] ?? 'user';
        } else {
            $username = 'user';
        }

        $jrd = $this->federationService->getWebFinger($username);

        return response()->json($jrd, 200, [
            'Content-Type' => 'application/jrd+json',
        ]);
    }

    /**
     * ActivityPub Actor profile endpoint.
     */
    public function actor(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();
        $profile = $this->federationService->getActorProfile($user);

        return response()->json($profile, 200, [
            'Content-Type' => 'application/activity+json',
        ]);
    }

    /**
     * ActivityPub Shared / User Inbox for receiving remote activities.
     */
    public function inbox(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $activity = $this->federationService->handleInboundActivity($payload);

            return response()->json(['status' => 'accepted', 'activity_id' => $activity->activity_id], 202);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'rejected',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ActivityPub Outbox for public activities.
     */
    public function outbox(string $username): JsonResponse
    {
        $user = User::where('username', $username)->firstOrFail();

        return response()->json([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => "https://jugajug.com/users/{$user->username}/outbox",
            'type' => 'OrderedCollection',
            'totalItems' => $user->posts()->count(),
            'first' => "https://jugajug.com/users/{$user->username}/outbox?page=1",
        ], 200, ['Content-Type' => 'application/activity+json']);
    }
}
