<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FriendshipService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function __construct(
        protected FriendshipService $friendshipService
    ) {}

    /**
     * List all accepted friends for authenticated user with search, filter, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $filter = $request->query('filter', 'all');
        $sort = $request->query('sort', 'recently_added');
        $search = $request->query('search');

        $friends = $this->friendshipService->getFriends(
            $request->user(),
            ['filter' => $filter, 'sort' => $sort, 'search' => $search],
            $sort,
            $search,
            $perPage
        );

        return response()->json([
            'success' => true,
            'data' => $friends->items(),
            'meta' => [
                'current_page' => $friends->currentPage(),
                'last_page' => $friends->lastPage(),
                'total' => $friends->total(),
                'per_page' => $friends->perPage(),
            ],
        ]);
    }

    /**
     * List pending received friend requests.
     */
    public function requests(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $requests = $this->friendshipService->getPendingRequests($request->user(), $perPage);

        return response()->json([
            'success' => true,
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * List outgoing sent friend requests awaiting acceptance.
     */
    public function sentRequests(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $sent = $this->friendshipService->getSentRequests($request->user(), $perPage);

        return response()->json([
            'success' => true,
            'data' => $sent->items(),
            'meta' => [
                'current_page' => $sent->currentPage(),
                'last_page' => $sent->lastPage(),
                'total' => $sent->total(),
            ],
        ]);
    }

    /**
     * Send a friend request.
     */
    public function send(Request $request, int $friendId): JsonResponse
    {
        try {
            $friendship = $this->friendshipService->sendRequest($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'message' => 'Friend request sent successfully.',
                'data' => $friendship,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Accept a friend request.
     */
    public function accept(Request $request, int $friendId): JsonResponse
    {
        try {
            $friendship = $this->friendshipService->acceptRequest($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'message' => 'Friend request accepted.',
                'data' => $friendship,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Decline a friend request.
     */
    public function decline(Request $request, int $friendId): JsonResponse
    {
        try {
            $friendship = $this->friendshipService->declineRequest($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'message' => 'Friend request declined.',
                'data' => $friendship,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel an outgoing friend request.
     */
    public function cancel(Request $request, int $friendId): JsonResponse
    {
        try {
            $this->friendshipService->cancelRequest($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'message' => 'Friend request cancelled successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unfriend a user or remove friendship/request.
     */
    public function unfriend(Request $request, int $friendId): JsonResponse
    {
        $this->friendshipService->unfriend($request->user(), $friendId);

        return response()->json([
            'success' => true,
            'message' => 'Friend removed successfully.',
        ]);
    }

    /**
     * Get relationship state between authenticated user and target.
     */
    public function status(Request $request, int $targetId): JsonResponse
    {
        $target = User::findOrFail($targetId);
        $state = $this->friendshipService->getRelationshipState($request->user(), $target);

        return response()->json([
            'success' => true,
            'state' => $state,
            'is_friend' => ($state === 'FRIENDS'),
            'is_pending_sent' => ($state === 'REQUEST_SENT'),
            'is_pending_received' => ($state === 'REQUEST_RECEIVED'),
            'is_blocked' => in_array($state, ['BLOCKED', 'BLOCKED_BY_USER'], true),
            'mutual_count' => $this->friendshipService->getMutualCount($request->user(), $target),
        ]);
    }

    /**
     * Get friend recommendations/suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $suggestions = $this->friendshipService->getSuggestions($request->user(), $limit);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
            'count' => $suggestions->count(),
        ]);
    }

    /**
     * Get mutual friends with target user.
     */
    public function mutual(Request $request, int $userId): JsonResponse
    {
        $target = User::findOrFail($userId);
        $perPage = (int) $request->query('per_page', 20);
        $search = $request->query('search');

        $mutuals = $this->friendshipService->getMutualFriends($request->user(), $target, $search, $perPage);

        return response()->json([
            'success' => true,
            'data' => $mutuals->items(),
            'meta' => [
                'current_page' => $mutuals->currentPage(),
                'last_page' => $mutuals->lastPage(),
                'total' => $mutuals->total(),
            ],
        ]);
    }

    /**
     * Get birthdays summary (today, upcoming, recent).
     */
    public function birthdays(Request $request): JsonResponse
    {
        $birthdays = $this->friendshipService->getBirthdays($request->user());

        return response()->json([
            'success' => true,
            'data' => $birthdays,
        ]);
    }

    /**
     * Get real-time connection counters.
     */
    public function counters(Request $request): JsonResponse
    {
        $counters = $this->friendshipService->getCounters($request->user());

        return response()->json([
            'success' => true,
            'data' => $counters,
        ]);
    }

    /**
     * Bulk accept requests.
     */
    public function bulkAccept(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|max:100', 'ids.*' => 'integer|distinct']);
        $results = $this->friendshipService->bulkAccept($request->user(), $request->input('ids'));

        return response()->json([
            'success' => true,
            'message' => 'Bulk accept operation completed.',
            'data' => $results,
        ]);
    }

    /**
     * Bulk decline requests.
     */
    public function bulkDecline(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|max:100', 'ids.*' => 'integer|distinct']);
        $results = $this->friendshipService->bulkDecline($request->user(), $request->input('ids'));

        return response()->json([
            'success' => true,
            'message' => 'Bulk decline operation completed.',
            'data' => $results,
        ]);
    }

    /**
     * Bulk delete / unfriend / cancel.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|max:100', 'ids.*' => 'integer|distinct']);
        $results = $this->friendshipService->bulkDelete($request->user(), $request->input('ids'));

        return response()->json([
            'success' => true,
            'message' => 'Bulk delete operation completed.',
            'data' => $results,
        ]);
    }

    /**
     * Bulk block users.
     */
    public function bulkBlock(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|max:100', 'ids.*' => 'integer|distinct']);
        $results = $this->friendshipService->bulkBlock($request->user(), $request->input('ids'));

        return response()->json([
            'success' => true,
            'message' => 'Bulk block operation completed.',
            'data' => $results,
        ]);
    }

    /**
     * Follow a user.
     */
    public function follow(Request $request, int $userId): JsonResponse
    {
        try {
            $follow = $this->friendshipService->followUser($request->user(), $userId);

            return response()->json([
                'success' => true,
                'message' => 'User followed successfully.',
                'data' => $follow,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unfollow a user.
     */
    public function unfollow(Request $request, int $userId): JsonResponse
    {
        $this->friendshipService->unfollowUser($request->user(), $userId);

        return response()->json([
            'success' => true,
            'message' => 'User unfollowed successfully.',
        ]);
    }

    /**
     * Block a user.
     */
    public function block(Request $request, int $userId): JsonResponse
    {
        try {
            $this->friendshipService->blockUser($request->user(), $userId);

            return response()->json([
                'success' => true,
                'message' => 'User blocked successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unblock a user.
     */
    public function unblock(Request $request, int $userId): JsonResponse
    {
        $this->friendshipService->unblockUser($request->user(), $userId);

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully.',
        ]);
    }

    /**
     * Toggle Favorite friend.
     */
    public function toggleFavorite(Request $request, int $friendId): JsonResponse
    {
        try {
            $isFav = $this->friendshipService->toggleFavorite($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'is_favorite' => $isFav,
                'message' => $isFav ? 'Added to favorites.' : 'Removed from favorites.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Toggle Close friend.
     */
    public function toggleClose(Request $request, int $friendId): JsonResponse
    {
        try {
            $isClose = $this->friendshipService->toggleCloseFriend($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'is_close_friend' => $isClose,
                'message' => $isClose ? 'Added to close friends.' : 'Removed from close friends.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Toggle Restricted friend.
     */
    public function toggleRestricted(Request $request, int $friendId): JsonResponse
    {
        try {
            $isRestricted = $this->friendshipService->toggleRestricted($request->user(), $friendId);

            return response()->json([
                'success' => true,
                'is_restricted' => $isRestricted,
                'message' => $isRestricted ? 'Friend restricted.' : 'Friend unrestricted.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get user custom and system lists.
     */
    public function lists(Request $request): JsonResponse
    {
        $lists = $this->friendshipService->getLists($request->user());

        return response()->json([
            'success' => true,
            'data' => $lists,
        ]);
    }

    /**
     * Create custom friend list.
     */
    public function createList(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:custom,family,work,school,best_friends',
        ]);

        $list = $this->friendshipService->createList($request->user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom friend list created.',
            'data' => $list,
        ], 201);
    }

    /**
     * Update custom friend list.
     */
    public function updateList(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $list = $this->friendshipService->updateList($request->user(), $id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom friend list updated.',
            'data' => $list,
        ]);
    }

    /**
     * Delete custom friend list.
     */
    public function deleteList(Request $request, int $id): JsonResponse
    {
        try {
            $this->friendshipService->deleteList($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Custom friend list deleted.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get members of custom list.
     */
    public function listMembers(Request $request, int $id): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $members = $this->friendshipService->getListMembers($request->user(), $id, $perPage);

        return response()->json([
            'success' => true,
            'data' => $members->items(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    /**
     * Add friend to custom list.
     */
    public function addListMember(Request $request, int $id, int $friendId): JsonResponse
    {
        try {
            $this->friendshipService->addMemberToList($request->user(), $id, $friendId);

            return response()->json([
                'success' => true,
                'message' => 'Friend added to list.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove friend from custom list.
     */
    public function removeListMember(Request $request, int $id, int $friendId): JsonResponse
    {
        try {
            $this->friendshipService->removeMemberFromList($request->user(), $id, $friendId);

            return response()->json([
                'success' => true,
                'message' => 'Friend removed from list.',
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * List followers of the authenticated user.
     */
    public function followers(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $user = $request->user();

        $followers = $user->followers()
            ->with('profile')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $followers->items(),
            'meta' => [
                'current_page' => $followers->currentPage(),
                'last_page' => $followers->lastPage(),
                'total' => $followers->total(),
                'per_page' => $followers->perPage(),
            ],
        ]);
    }

    /**
     * List users the authenticated user is following.
     */
    public function following(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $user = $request->user();

        $following = $user->following()
            ->with('profile')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $following->items(),
            'meta' => [
                'current_page' => $following->currentPage(),
                'last_page' => $following->lastPage(),
                'total' => $following->total(),
                'per_page' => $following->perPage(),
            ],
        ]);
    }
}
