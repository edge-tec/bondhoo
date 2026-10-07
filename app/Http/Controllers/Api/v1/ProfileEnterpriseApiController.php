<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ProfileEnterpriseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileEnterpriseApiController extends Controller
{
    public function __construct(
        protected ProfileEnterpriseService $profileService
    ) {}

    /**
     * Get avatar history.
     */
    public function getAvatarHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $history = $this->profileService->getAvatarHistory($user);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    /**
     * Restore previous avatar.
     */
    public function restoreAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'photo_id' => 'required|integer|exists:profile_photos,id',
        ]);

        $photo = $this->profileService->restoreAvatar($request->user(), (int) $request->input('photo_id'));

        return response()->json([
            'status' => $photo ? 'success' : 'error',
            'message' => $photo ? 'Avatar restored successfully' : 'Avatar not found',
            'data' => $photo,
        ]);
    }

    /**
     * Apply or clear avatar frame.
     */
    public function applyAvatarFrame(Request $request): JsonResponse
    {
        $request->validate([
            'frame_id' => 'nullable|string|max:50',
        ]);

        $photo = $this->profileService->applyAvatarFrame($request->user(), $request->input('frame_id'));

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar frame updated',
            'data' => $photo,
        ]);
    }

    /**
     * Toggle profile picture guard (shield).
     */
    public function toggleAvatarGuard(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $settings->has_avatar_guard = ! $settings->has_avatar_guard;
        $settings->save();

        return response()->json([
            'status' => 'success',
            'message' => $settings->has_avatar_guard ? 'প্রোফাইল পিকচার গার্ড সক্রিয় করা হয়েছে।' : 'প্রোফাইল পিকচার গার্ড নিষ্ক্রিয় করা হয়েছে।',
            'data' => [
                'has_avatar_guard' => $settings->has_avatar_guard,
            ],
        ]);
    }

    /**
     * Get cover photo history.
     */
    public function getCoverHistory(Request $request): JsonResponse
    {
        $history = $this->profileService->getCoverHistory($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    /**
     * Reposition current cover.
     */
    public function repositionCover(Request $request): JsonResponse
    {
        $request->validate([
            'position_y' => 'required|integer|min:0|max:100',
        ]);

        $this->profileService->repositionCover($request->user(), (int) $request->input('position_y'));

        return response()->json([
            'status' => 'success',
            'message' => 'Cover photo repositioned',
        ]);
    }

    /**
     * Update personal section.
     */
    public function updatePersonal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:500',
            'headline' => 'nullable|string|max:200',
            'about' => 'nullable|string|max:2000',
            'religion' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string|max:10',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'relationship_status' => 'nullable|string|max:50',
            'pronouns' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
        ]);

        $profile = $this->profileService->updateAboutPersonal($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Personal details updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Update contact and communication section.
     */
    public function updateContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|url|max:255',
            'portfolio' => 'nullable|url|max:255',
            'whatsapp' => 'nullable|string|max:50',
            'telegram' => 'nullable|string|max:50',
            'signal' => 'nullable|string|max:50',
            'messenger' => 'nullable|string|max:50',
            'social_links' => 'nullable|array',
        ]);

        $profile = $this->profileService->updateAboutContact($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Contact details updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Update location details.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'hometown' => 'nullable|string|max:100',
        ]);

        $profile = $this->profileService->updateAboutLocation($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Location details updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Update interests, hobbies, and favorites.
     */
    public function updateInterests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hobbies' => 'nullable|array',
            'favorite_music' => 'nullable|array',
            'favorite_books' => 'nullable|array',
            'favorite_movies' => 'nullable|array',
            'interests' => 'nullable|array',
        ]);

        $profile = $this->profileService->updateAboutInterestsHobbies($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Interests and favorites updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Get advanced friends list.
     */
    public function getAdvancedFriends(Request $request): JsonResponse
    {
        $filter = $request->query('filter');
        $search = $request->query('search');

        $friends = $this->profileService->getFriends($request->user(), $filter, $search);

        return response()->json([
            'status' => 'success',
            'count' => $friends->count(),
            'data' => $friends,
        ]);
    }

    /**
     * Toggle Favorite friend.
     */
    public function toggleFavoriteFriend(Request $request, int $id): JsonResponse
    {
        $isFavorite = $this->profileService->toggleFavoriteFriend($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'is_favorite' => $isFavorite,
        ]);
    }

    /**
     * Toggle Close friend.
     */
    public function toggleCloseFriend(Request $request, int $id): JsonResponse
    {
        $isClose = $this->profileService->toggleCloseFriend($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'is_close_friend' => $isClose,
        ]);
    }

    /**
     * Toggle Restricted friend.
     */
    public function toggleRestrictedFriend(Request $request, int $id): JsonResponse
    {
        $isRestricted = $this->profileService->toggleRestrictedFriend($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'is_restricted' => $isRestricted,
        ]);
    }

    /**
     * Mute friend.
     */
    public function muteFriend(Request $request, int $id): JsonResponse
    {
        $hours = $request->input('hours') ? (int) $request->input('hours') : null;
        $this->profileService->muteFriend($request->user(), $id, $hours);

        return response()->json([
            'status' => 'success',
            'message' => 'Friend muted successfully',
        ]);
    }

    /**
     * Snooze friend.
     */
    public function snoozeFriend(Request $request, int $id): JsonResponse
    {
        $days = (int) ($request->input('days') ?? 30);
        $this->profileService->snoozeFriend($request->user(), $id, $days);

        return response()->json([
            'status' => 'success',
            'message' => "Friend snoozed for {$days} days",
        ]);
    }

    /**
     * Get user friends list with filter and search.
     */
    public function getFriends(Request $request): JsonResponse
    {
        $filter = $request->query('filter');
        $search = $request->query('search');
        $friends = $this->profileService->getFriends($request->user(), $filter, $search);

        return response()->json([
            'status' => 'success',
            'data' => $friends,
        ]);
    }

    /**
     * Get friend suggestions.
     */
    public function getFriendSuggestions(Request $request): JsonResponse
    {
        $suggestions = $this->profileService->getFriendSuggestions($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $suggestions,
        ]);
    }

    /**
     * Photo Albums list.
     */
    public function getAlbums(Request $request): JsonResponse
    {
        $albums = $this->profileService->getAlbums($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $albums,
        ]);
    }

    /**
     * Create photo album.
     */
    public function createAlbum(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'cover_photo_path' => 'nullable|string|max:255',
            'privacy' => 'nullable|string|in:public,friends,only_me',
        ]);

        $album = $this->profileService->createAlbum($request->user(), $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Album created successfully',
            'data' => $album,
        ], 201);
    }

    /**
     * Add item to album.
     */
    public function addAlbumItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'media_path' => 'required|string|max:255',
            'caption' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:100',
            'tagged_user_ids' => 'nullable|array',
        ]);

        $item = $this->profileService->addAlbumItem($request->user(), $id, $validated);

        return response()->json([
            'status' => $item ? 'success' : 'error',
            'message' => $item ? 'Photo added to album' : 'Album not found',
            'data' => $item,
        ]);
    }

    /**
     * Delete album.
     */
    public function deleteAlbum(Request $request, int $id): JsonResponse
    {
        $deleted = $this->profileService->deleteAlbum($request->user(), $id);

        return response()->json([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Album deleted' : 'Album not found',
        ]);
    }

    /**
     * Update/rename album.
     */
    public function updateAlbum(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:1000',
            'privacy' => 'nullable|string|in:public,friends,only_me',
        ]);

        $album = $this->profileService->updateAlbum($request->user(), $id, $validated);

        return response()->json([
            'status' => $album ? 'success' : 'error',
            'message' => $album ? 'Album updated successfully' : 'Album not found',
            'data' => $album,
        ]);
    }

    /**
     * Remove photo item from album.
     */
    public function removeAlbumItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $deleted = $this->profileService->deleteAlbumItem($request->user(), $itemId);

        return response()->json([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Item removed from album' : 'Item not found',
        ]);
    }

    /**
     * Saved items list.
     */
    public function getSavedItems(Request $request): JsonResponse
    {
        $collection = $request->query('collection');
        $items = $this->profileService->getSavedItems($request->user(), $collection);

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }

    /**
     * Toggle save item.
     */
    public function toggleSaveItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|string|max:100',
            'item_id' => 'required|integer',
            'collection_name' => 'nullable|string|max:100',
        ]);

        $result = $this->profileService->toggleSaveItem(
            $request->user(),
            $validated['item_type'],
            (int) $validated['item_id'],
            $validated['collection_name'] ?? 'All Items'
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Get story highlights.
     */
    public function getHighlights(Request $request): JsonResponse
    {
        $highlights = $this->profileService->getHighlights($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $highlights,
        ]);
    }

    /**
     * Create story highlight.
     */
    public function createHighlight(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'cover_image_path' => 'nullable|string|max:255',
            'story_ids' => 'nullable|array',
        ]);

        $highlight = $this->profileService->createHighlight(
            $request->user(),
            $validated['title'],
            $validated['cover_image_path'] ?? null,
            $validated['story_ids'] ?? []
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Story highlight created',
            'data' => $highlight,
        ], 201);
    }

    /**
     * Delete story highlight.
     */
    public function deleteHighlight(Request $request, int $id): JsonResponse
    {
        $deleted = $this->profileService->deleteHighlight($request->user(), $id);

        return response()->json([
            'status' => $deleted ? 'success' : 'error',
            'message' => $deleted ? 'Story highlight deleted' : 'Highlight not found',
        ]);
    }

    /**
     * Get activity logs.
     */
    public function getActivityLogs(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $logs = $this->profileService->getActivityLogs($request->user(), $type);

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    /**
     * Toggle professional mode.
     */
    public function toggleProfessionalMode(Request $request): JsonResponse
    {
        $isProfessional = $this->profileService->toggleProfessionalMode($request->user());

        return response()->json([
            'status' => 'success',
            'is_professional_mode' => $isProfessional,
            'message' => $isProfessional ? 'Professional Mode activated' : 'Professional Mode deactivated',
        ]);
    }

    /**
     * Professional mode analytics.
     */
    public function getProfessionalAnalytics(Request $request): JsonResponse
    {
        $analytics = $this->profileService->getProfessionalAnalytics($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $analytics,
        ]);
    }

    /**
     * Multi-attribute profile search.
     */
    public function searchProfiles(Request $request): JsonResponse
    {
        $filters = $request->only(['query', 'city', 'category']);
        $results = $this->profileService->searchProfiles($filters);

        return response()->json([
            'status' => 'success',
            'data' => $results,
        ]);
    }
}
