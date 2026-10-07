<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CoverPhoto;
use App\Models\Friendship;
use App\Models\PhotoAlbum;
use App\Models\PhotoAlbumItem;
use App\Models\ProfilePhoto;
use App\Models\SavedItem;
use App\Models\StoryHighlight;
use App\Models\StoryHighlightItem;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ProfileEnterpriseService
{
    /**
     * Get avatar history for user.
     */
    public function getAvatarHistory(User $user): Collection
    {
        return ProfilePhoto::where('user_id', $user->id)
            ->latest('id')
            ->get();
    }

    /**
     * Upload or set new avatar.
     */
    public function setAvatar(User $user, string $photoPath, ?string $frameId = null, string $privacy = 'public'): ProfilePhoto
    {
        return DB::transaction(function () use ($user, $photoPath, $frameId, $privacy) {
            ProfilePhoto::where('user_id', $user->id)->update(['is_current' => false]);

            $profilePhoto = ProfilePhoto::create([
                'user_id' => $user->id,
                'photo_url' => $photoPath,
                'frame' => $frameId,
                'is_current' => true,
                'privacy' => $privacy,
            ]);

            $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);
            $profile->update([
                'avatar_url' => $photoPath,
            ]);

            $this->logActivity($user, 'avatar_updated', 'Updated profile avatar');

            return $profilePhoto;
        });
    }

    /**
     * Restore previous avatar from history.
     */
    public function restoreAvatar(User $user, int $photoId): ?ProfilePhoto
    {
        $photo = ProfilePhoto::where('user_id', $user->id)->where('id', $photoId)->first();
        if (! $photo) {
            return null;
        }

        ProfilePhoto::where('user_id', $user->id)->update(['is_current' => false]);
        $photo->update(['is_current' => true]);

        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);
        $profile->update([
            'avatar_url' => $photo->photo_url,
        ]);

        $this->logActivity($user, 'avatar_restored', 'Restored previous profile avatar');

        return $photo;
    }

    /**
     * Apply or clear frame on current avatar.
     */
    public function applyAvatarFrame(User $user, ?string $frameId): ?ProfilePhoto
    {
        $current = ProfilePhoto::where('user_id', $user->id)->where('is_current', true)->first();
        if ($current) {
            $current->update(['frame' => $frameId]);
            $this->logActivity($user, 'avatar_frame_updated', 'Updated profile avatar frame');

            return $current->fresh();
        }

        return null;
    }

    /**
     * Get cover photo history for user.
     */
    public function getCoverHistory(User $user): Collection
    {
        return CoverPhoto::where('user_id', $user->id)
            ->latest('id')
            ->get();
    }

    /**
     * Set new cover photo.
     */
    public function setCover(User $user, string $photoPath, int $positionY = 50, string $privacy = 'public'): CoverPhoto
    {
        return DB::transaction(function () use ($user, $photoPath, $positionY, $privacy) {
            CoverPhoto::where('user_id', $user->id)->update(['is_current' => false]);

            $cover = CoverPhoto::create([
                'user_id' => $user->id,
                'cover_url' => $photoPath,
                'position_y' => $positionY,
                'is_current' => true,
                'privacy' => $privacy,
            ]);

            $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);
            $profile->update([
                'cover_url' => $photoPath,
                'cover_position_y' => $positionY,
            ]);

            $this->logActivity($user, 'cover_updated', 'Updated cover photo');

            return $cover;
        });
    }

    /**
     * Reposition current cover photo.
     */
    public function repositionCover(User $user, int $positionY): bool
    {
        $current = CoverPhoto::where('user_id', $user->id)->where('is_current', true)->first();
        if ($current) {
            $current->update(['position_y' => $positionY]);
        }

        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);
        $profile->update(['cover_position_y' => $positionY]);

        $this->logActivity($user, 'cover_repositioned', 'Repositioned cover photo');

        return true;
    }

    /**
     * Update personal section.
     */
    public function updateAboutPersonal(User $user, array $data): UserProfile
    {
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $profile->update(array_filter([
            'first_name' => $data['first_name'] ?? $profile->first_name,
            'middle_name' => $data['middle_name'] ?? $profile->middle_name,
            'last_name' => $data['last_name'] ?? $profile->last_name,
            'bio' => $data['bio'] ?? $profile->bio,
            'headline' => $data['headline'] ?? $profile->headline,
            'about' => $data['about'] ?? $profile->about,
            'religion' => $data['religion'] ?? $profile->religion,
            'blood_group' => $data['blood_group'] ?? $profile->blood_group,
            'birth_date' => $data['birth_date'] ?? $profile->birth_date,
            'gender' => $data['gender'] ?? $profile->gender,
            'relationship_status' => $data['relationship_status'] ?? $profile->relationship_status,
            'pronouns' => $data['pronouns'] ?? $profile->pronouns,
            'category' => $data['category'] ?? $profile->category,
        ], fn ($val) => $val !== null));

        if (! empty($data['first_name']) || ! empty($data['last_name'])) {
            $displayName = trim(($data['first_name'] ?? $profile->first_name).' '.($data['last_name'] ?? $profile->last_name));
            $user->update(['name' => $displayName]);
        }

        $this->logActivity($user, 'profile_updated', 'Updated personal information');

        return $profile;
    }

    /**
     * Update contact and communication section.
     */
    public function updateAboutContact(User $user, array $data): UserProfile
    {
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $profile->update([
            'website' => $data['website'] ?? $profile->website,
            'portfolio' => $data['portfolio'] ?? $profile->portfolio,
            'whatsapp' => $data['whatsapp'] ?? $profile->whatsapp,
            'telegram' => $data['telegram'] ?? $profile->telegram,
            'signal' => $data['signal'] ?? $profile->signal,
            'messenger' => $data['messenger'] ?? $profile->messenger,
            'social_links' => $data['social_links'] ?? $profile->social_links,
        ]);

        if (isset($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }

        $this->logActivity($user, 'contact_updated', 'Updated contact information');

        return $profile;
    }

    /**
     * Update location details.
     */
    public function updateAboutLocation(User $user, array $data): UserProfile
    {
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $profile->update([
            'country' => $data['country'] ?? $profile->country,
            'city' => $data['city'] ?? $profile->city,
            'location' => $data['city'] ?? $profile->location,
            'division' => $data['division'] ?? $profile->division,
            'district' => $data['district'] ?? $profile->district,
            'upazila' => $data['upazila'] ?? $profile->upazila,
            'address' => $data['address'] ?? $profile->address,
            'hometown' => $data['hometown'] ?? $profile->hometown,
        ]);

        $this->logActivity($user, 'location_updated', 'Updated location information');

        return $profile;
    }

    /**
     * Update interests and hobbies.
     */
    public function updateAboutInterestsHobbies(User $user, array $data): UserProfile
    {
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);

        $profile->update([
            'hobbies' => $data['hobbies'] ?? $profile->hobbies,
            'favorite_music' => $data['favorite_music'] ?? $profile->favorite_music,
            'favorite_books' => $data['favorite_books'] ?? $profile->favorite_books,
            'favorite_movies' => $data['favorite_movies'] ?? $profile->favorite_movies,
            'interests' => $data['interests'] ?? $profile->interests,
        ]);

        $this->logActivity($user, 'interests_updated', 'Updated interests, hobbies, and favorites');

        return $profile;
    }

    /**
     * Advanced Friend Management: get list with filter.
     */
    public function getFriends(User $user, ?string $filter = null, ?string $search = null): Collection
    {
        $query = Friendship::where('user_id', $user->id)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['friend.profile']);

        if ($filter === 'favorites') {
            $query->where('is_favorite', true);
        } elseif ($filter === 'close_friends') {
            $query->where('is_close_friend', true);
        } elseif ($filter === 'restricted') {
            $query->where('is_restricted', true);
        } elseif ($filter === 'muted') {
            $query->where('is_muted', true);
        }

        if ($search) {
            $query->whereHas('friend', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return $query->get();
    }

    /**
     * Toggle Favorite friend.
     */
    public function toggleFavoriteFriend(User $user, int $friendId): bool
    {
        $friendship = Friendship::where('user_id', $user->id)->where('friend_id', $friendId)->first();
        if (! $friendship) {
            return false;
        }

        $friendship->update(['is_favorite' => ! $friendship->is_favorite]);

        return (bool) $friendship->is_favorite;
    }

    /**
     * Toggle Close friend.
     */
    public function toggleCloseFriend(User $user, int $friendId): bool
    {
        $friendship = Friendship::where('user_id', $user->id)->where('friend_id', $friendId)->first();
        if (! $friendship) {
            return false;
        }

        $friendship->update(['is_close_friend' => ! $friendship->is_close_friend]);

        return (bool) $friendship->is_close_friend;
    }

    /**
     * Toggle Restricted friend.
     */
    public function toggleRestrictedFriend(User $user, int $friendId): bool
    {
        $friendship = Friendship::where('user_id', $user->id)->where('friend_id', $friendId)->first();

        if (! $friendship) {
            $friendship = Friendship::create([
                'user_id' => $user->id,
                'friend_id' => $friendId,
                'status' => 'accepted',
                'is_restricted' => false,
            ]);
        }

        $friendship->update(['is_restricted' => ! $friendship->is_restricted]);

        return (bool) $friendship->is_restricted;
    }

    /**
     * Mute friend for period or permanently.
     */
    public function muteFriend(User $user, int $friendId, ?int $durationHours = null): bool
    {
        $friendship = Friendship::where('user_id', $user->id)->where('friend_id', $friendId)->first();
        if (! $friendship) {
            return false;
        }

        $friendship->update([
            'is_muted' => true,
            'muted_until' => $durationHours ? Carbon::now()->addHours($durationHours) : null,
        ]);

        return true;
    }

    /**
     * Snooze friend for 30 days.
     */
    public function snoozeFriend(User $user, int $friendId, int $days = 30): bool
    {
        $friendship = Friendship::where('user_id', $user->id)->where('friend_id', $friendId)->first();
        if (! $friendship) {
            return false;
        }

        $friendship->update([
            'snoozed_until' => Carbon::now()->addDays($days),
        ]);

        return true;
    }

    /**
     * Smart friend suggestions.
     */
    public function getFriendSuggestions(User $user, int $limit = 10): Collection
    {
        $existingFriendIds = Friendship::where('user_id', $user->id)->pluck('friend_id')->toArray();
        $existingFriendIds[] = $user->id;

        return User::whereNotIn('id', $existingFriendIds)
            ->with('profile')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Photo Albums Management.
     */
    public function getAlbums(User $user): Collection
    {
        return PhotoAlbum::where('user_id', $user->id)
            ->withCount('items')
            ->with('items')
            ->latest('id')
            ->get();
    }

    public function createAlbum(User $user, array $data): PhotoAlbum
    {
        return DB::transaction(function () use ($user, $data) {
            $album = PhotoAlbum::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => 'custom',
                'privacy' => $data['privacy'] ?? 'public',
            ]);

            if (! empty($data['cover_photo_path'])) {
                PhotoAlbumItem::create([
                    'album_id' => $album->id,
                    'media_url' => $data['cover_photo_path'],
                    'display_order' => 1,
                ]);
            }

            $this->logActivity($user, 'album_created', "Created album: {$album->title}");

            return $album;
        });
    }

    public function updateAlbum(User $user, int $albumId, array $data): ?PhotoAlbum
    {
        $album = PhotoAlbum::where('user_id', $user->id)->where('id', $albumId)->first();
        if (! $album) {
            return null;
        }

        $album->update(array_filter([
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'privacy' => $data['privacy'] ?? null,
        ]));

        return $album;
    }

    public function deleteAlbum(User $user, int $albumId): bool
    {
        $album = PhotoAlbum::where('user_id', $user->id)->where('id', $albumId)->first();
        if (! $album) {
            return false;
        }

        $album->items()->delete();
        $album->delete();

        return true;
    }

    public function addAlbumItem(User $user, int $albumId, array $itemData): ?PhotoAlbumItem
    {
        $album = PhotoAlbum::where('user_id', $user->id)->where('id', $albumId)->first();
        if (! $album) {
            return null;
        }

        $item = PhotoAlbumItem::create([
            'album_id' => $album->id,
            'media_url' => $itemData['media_path'],
            'caption' => $itemData['caption'] ?? null,
            'location' => $itemData['location'] ?? null,
            'tagged_user_ids' => $itemData['tagged_user_ids'] ?? [],
            'display_order' => $album->items()->count() + 1,
        ]);

        return $item;
    }

    public function deleteAlbumItem(User $user, int $itemId): bool
    {
        $item = PhotoAlbumItem::whereHas('album', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->where('id', $itemId)->first();

        if (! $item) {
            return false;
        }

        $item->delete();

        return true;
    }

    /**
     * Saved Items & Collections.
     */
    public function getSavedItems(User $user, ?string $collection = null): Collection
    {
        $query = SavedItem::where('user_id', $user->id);

        if ($collection && $collection !== 'All') {
            $query->where('collection_name', $collection);
        }

        return $query->latest('id')->get();
    }

    public function toggleSaveItem(User $user, string $itemType, int $itemId, ?string $collection = 'All Items'): array
    {
        $existing = SavedItem::where('user_id', $user->id)
            ->where('item_type', $itemType)
            ->where('item_id', $itemId)
            ->first();

        if ($existing) {
            $existing->delete();

            return ['saved' => false];
        }

        $saved = SavedItem::create([
            'user_id' => $user->id,
            'item_type' => $itemType,
            'item_id' => $itemId,
            'collection_name' => $collection ?? 'All Items',
        ]);

        $this->logActivity($user, 'item_saved', 'Saved an item to collection');

        return ['saved' => true, 'item' => $saved];
    }

    /**
     * Story Highlights.
     */
    public function getHighlights(User $user): Collection
    {
        return StoryHighlight::where('user_id', $user->id)
            ->with('items')
            ->latest('id')
            ->get();
    }

    public function createHighlight(User $user, string $title, ?string $coverImagePath, array $storyIds = []): StoryHighlight
    {
        return DB::transaction(function () use ($user, $title, $coverImagePath, $storyIds) {
            $highlight = StoryHighlight::create([
                'user_id' => $user->id,
                'title' => $title,
                'cover_url' => $coverImagePath,
            ]);

            foreach ($storyIds as $storyId) {
                StoryHighlightItem::create([
                    'highlight_id' => $highlight->id,
                    'story_id' => $storyId,
                ]);
            }

            $this->logActivity($user, 'highlight_created', "Created story highlight: {$title}");

            return $highlight->load('items');
        });
    }

    public function deleteHighlight(User $user, int $highlightId): bool
    {
        $highlight = StoryHighlight::where('user_id', $user->id)->where('id', $highlightId)->first();
        if (! $highlight) {
            return false;
        }

        $highlight->items()->delete();
        $highlight->delete();

        return true;
    }

    /**
     * Activity Logs.
     */
    public function logActivity(
        User $user,
        string $activityType,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null
    ): UserActivityLog {
        return UserActivityLog::create([
            'user_id' => $user->id,
            'action_type' => $activityType,
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'device' => Request::userAgent() ?? 'Bondhoo Enterprise Client',
            'metadata' => $metadata,
        ]);
    }

    public function getActivityLogs(User $user, ?string $type = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = UserActivityLog::where('user_id', $user->id);

        if ($type) {
            $query->where('action_type', $type);
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Professional Mode & Analytics.
     */
    public function toggleProfessionalMode(User $user): bool
    {
        $profile = $user->profile ?? UserProfile::create(['user_id' => $user->id]);
        $newState = ! $profile->is_professional_mode;
        $profile->update(['is_professional_mode' => $newState]);

        $this->logActivity($user, 'professional_mode_toggled', 'Toggled Professional Mode to '.($newState ? 'ON' : 'OFF'));

        return $newState;
    }

    public function getProfessionalAnalytics(User $user): array
    {
        $viewsCount = $user->profileViews()->count();
        $postsCount = $user->posts()->count();
        $followersCount = $user->followers()->count();

        // Calculate 30-day reach estimate
        $thirtyDaysViews = $user->profileViews()->where('viewed_at', '>=', Carbon::now()->subDays(30))->count();

        return [
            'is_professional_mode' => (bool) ($user->profile->is_professional_mode ?? false),
            'profile_views_total' => $viewsCount,
            'profile_views_30d' => $thirtyDaysViews,
            'followers_count' => $followersCount,
            'posts_count' => $postsCount,
            'monetization_ready' => $followersCount >= 100 && $postsCount >= 10,
            'engagement_rate' => $followersCount > 0 ? round(($viewsCount / $followersCount) * 10, 2).'%' : '0%',
        ];
    }

    /**
     * Profile Search across multi-attributes.
     */
    public function searchProfiles(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with('profile');

        if (! empty($filters['query'])) {
            $q = $filters['query'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhereHas('profile', function ($pq) use ($q) {
                        $pq->where('bio', 'like', "%{$q}%")
                            ->orWhere('headline', 'like', "%{$q}%")
                            ->orWhere('city', 'like', "%{$q}%");
                    });
            });
        }

        if (! empty($filters['city'])) {
            $city = $filters['city'];
            $query->whereHas('profile', function ($pq) use ($city) {
                $pq->where('city', 'like', "%{$city}%")
                    ->orWhere('location', 'like', "%{$city}%");
            });
        }

        if (! empty($filters['category'])) {
            $cat = $filters['category'];
            $query->whereHas('profile', function ($pq) use ($cat) {
                $pq->where('category', $cat);
            });
        }

        return $query->latest('id')->paginate($perPage);
    }
}
