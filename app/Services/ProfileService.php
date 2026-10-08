<?php

namespace App\Services;

use App\DTOs\PublicProfileDTO;
use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\Friendship;
use App\Models\Media;
use App\Models\Post;
use App\Models\ProfileEducation;
use App\Models\ProfileExperience;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\ProfileSocialLink;
use App\Models\User;
use App\Models\UsernameHistory;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Get a user's full profile package respecting their privacy and profile lock settings.
     */
    public function getProfileByUsername(string $username, ?User $viewer = null): array
    {
        $cleanUsername = ltrim(trim($username), '@');
        $targetUser = User::with(['profile', 'settings', 'roles'])
            ->where('username', strtolower($cleanUsername))
            ->first();

        $redirectedFrom = null;
        if (! $targetUser) {
            $history = UsernameHistory::where('username', strtolower($cleanUsername))->latest('id')->first();
            if ($history) {
                $targetUser = $history->user()->with(['profile', 'settings', 'roles'])->first();
                $redirectedFrom = strtolower($cleanUsername);
            }
        }

        if (! $targetUser) {
            throw new ModelNotFoundException("User @{$username} not found.");
        }

        $privacyService = app(ProfilePrivacyService::class);

        // Enforce Profile-level visibility and blocking
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $settings = $targetUser->settings;
        $isProfileLocked = (bool) ($settings?->is_profile_locked ?? false);

        // Determine friendship relationship status
        $friendshipStatus = 'none';
        $isFriend = false;

        if ($isOwner) {
            $friendshipStatus = 'self';
            $isFriend = true;
        } elseif ($viewer) {
            $friendship = Friendship::where(function ($q) use ($viewer, $targetUser) {
                $q->where('user_id', $viewer->id)->where('friend_id', $targetUser->id);
            })->orWhere(function ($q) use ($viewer, $targetUser) {
                $q->where('user_id', $targetUser->id)->where('friend_id', $viewer->id);
            })->first();

            if ($friendship) {
                if ($friendship->status === Friendship::STATUS_ACCEPTED) {
                    $friendshipStatus = 'friends';
                    $isFriend = true;
                } elseif ($friendship->user_id === $viewer->id && $friendship->status === Friendship::STATUS_PENDING) {
                    $friendshipStatus = 'request_sent';
                } elseif ($friendship->friend_id === $viewer->id && $friendship->status === Friendship::STATUS_PENDING) {
                    $friendshipStatus = 'request_received';
                }
            }
        }

        // Mutual friends calculation
        $mutualFriendsCount = 0;
        $mutualFriendsPreview = [];
        if ($viewer && ! $isOwner) {
            $targetFriendIds = $targetUser->getFriendIds();
            $viewerFriendIds = $viewer->getFriendIds();
            $mutualIds = array_values(array_intersect($targetFriendIds, $viewerFriendIds));
            $mutualFriendsCount = count($mutualIds);

            if ($mutualFriendsCount > 0) {
                $mutualFriendsPreview = User::whereIn('id', array_slice($mutualIds, 0, 5))
                    ->with('profile')
                    ->get()
                    ->map(fn (User $u) => [
                        'id' => $u->id,
                        'name' => $u->name ?: $u->username,
                        'username' => $u->username,
                        'avatar_url' => $u->profile?->avatar_url,
                    ])->toArray();
            }
        }

        // Followers and Friends count
        $friendsCount = count($targetUser->getFriendIds());
        $followersCount = $targetUser->followers()->count();
        $followingCount = $targetUser->following()->count();

        // Privacy lock enforcement: If profile is locked and viewer is not a friend/owner
        $isLockedView = $isProfileLocked && ! $isFriend;

        $profileData = $targetUser->profile ? $targetUser->profile->toArray() : [];
        if ($isLockedView) {
            // Mask detailed personal information for non-friends on locked profiles
            $profileData['bio'] = $profileData['bio'] ?? null;
            $profileData['work'] = null;
            $profileData['education'] = null;
            $profileData['location'] = null;
            $profileData['hometown'] = null;
            $profileData['website'] = null;
            $profileData['relationship_status'] = null;
        }

        $userData = [
            'id' => $targetUser->id,
            'name' => $targetUser->name ?: $targetUser->username,
            'username' => $targetUser->username,
            'country' => $targetUser->country,
            'status' => $targetUser->status,
            'is_verified' => $targetUser->hasVerifiedProfile(),
            'verification_status' => $targetUser->verification_status,
            'roles' => $targetUser->roles->pluck('name'),
            'created_at' => $targetUser->created_at,
        ];

        if ($isOwner) {
            $userData['email'] = $targetUser->email;
            $userData['phone'] = $targetUser->phone;
            $userData['settings'] = $settings;
            $userData['completion'] = app(ProfileCompletionService::class)->getCompletion($targetUser);
        }

        // Generate extended public profile payload via PublicProfileDTO
        $dto = PublicProfileDTO::fromUser($targetUser, $viewer);

        return [
            'user' => array_merge($userData, $dto['user']),
            'profile' => array_merge($profileData, $dto['profile'], [
                'avatar_url' => $dto['profile']['avatar_url'],
                'cover_url' => $dto['profile']['cover_url'],
                'bio' => $dto['profile']['bio'],
                'slug' => $targetUser->profile?->slug ?: $targetUser->username,
                'about' => $dto['profile']['about'],
                'social_links' => $dto['sections']['social_links'],
            ]),
            'is_owner' => $isOwner,
            'friendship_status' => $friendshipStatus,
            'is_friend' => $isFriend,
            'is_profile_locked' => $isProfileLocked,
            'is_locked_view' => $isLockedView,
            'stats' => array_merge([
                'friends_count' => $friendsCount,
                'mutual_friends_count' => $mutualFriendsCount,
                'mutual_friends' => $mutualFriendsPreview,
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'posts_count' => $targetUser->posts()->count(),
                'completion_percentage' => $targetUser->profile ? $targetUser->profile->calculateCompletionPercentage() : 0,
            ], $dto['stats']),
            'sections' => $dto['sections'],
            'url_migration' => $redirectedFrom ? [
                'redirected' => true,
                'redirected_from' => $redirectedFrom,
                'current_username' => $targetUser->username,
            ] : null,
        ];
    }

    /**
     * Update user's profile details.
     */
    public function updateProfile(User $user, array $data, ?string $ip = null, ?string $userAgent = null): UserProfile
    {
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $oldValues = $profile->toArray();

        if (isset($data['name'])) {
            $user->update(['name' => $data['name']]);
            if (! isset($data['display_name'])) {
                $data['display_name'] = $data['name'];
            }
        }

        $profile->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'profile.updated',
            'entity_type' => UserProfile::class,
            'entity_id' => $profile->id,
            'old_values' => $oldValues,
            'new_values' => $profile->fresh()->toArray(),
            'ip_address' => $ip ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
        ]);

        $this->invalidateProfileCache($user);
        event(new ProfileUpdatedEvent($user, ['basic_info'], $user));

        return $profile;
    }

    /**
     * Complete transactional profile update supporting all 10 sections.
     *
     * @return array<string, mixed>
     */
    public function updateCompleteProfile(User $targetUser, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $profile = $targetUser->profile()->firstOrCreate(['user_id' => $targetUser->id]);
            $oldProfile = $profile->toArray();
            $updatedSections = [];

            // 1. Basic Info & Account Name
            if (isset($data['name']) && $data['name'] !== $targetUser->name) {
                $targetUser->update(['name' => $data['name']]);
                if (! isset($data['display_name'])) {
                    $data['display_name'] = $data['name'];
                }
                $updatedSections[] = 'name';
            }

            // Core Profile Fields (Bio, About, Location, Contact, etc.)
            $profileFillable = [
                'first_name',
                'last_name',
                'display_name',
                'slug',
                'bio',
                'about',
                'location',
                'country',
                'city',
                'address',
                'hometown',
                'website',
                'gender',
                'relationship_status',
                'birth_date',
                'work',
                'education',
                'cover_position_y',
                'middle_name',
                'religion',
                'blood_group',
                'division',
                'district',
                'upazila',
                'pronouns',
                'category',
                'portfolio',
                'whatsapp',
                'telegram',
                'signal',
                'messenger',
                'hobbies',
                'favorite_music',
                'favorite_books',
                'favorite_movies',
                'is_professional_mode',
            ];

            $profileUpdate = [];
            foreach ($profileFillable as $field) {
                if (array_key_exists($field, $data)) {
                    $profileUpdate[$field] = $data[$field];
                }
            }

            if (! empty($profileUpdate)) {
                $profile->update($profileUpdate);
                $updatedSections[] = 'core_profile';
            }

            // Sync User Personal Fields (first_name, last_name, email, phone, birth_date, gender, country)
            $userPersonalUpdates = [];
            foreach (['first_name', 'last_name', 'email', 'phone', 'birth_date', 'gender', 'country'] as $uf) {
                if (array_key_exists($uf, $data)) {
                    $userPersonalUpdates[$uf] = $data[$uf];
                }
            }
            if (! empty($userPersonalUpdates)) {
                $targetUser->update($userPersonalUpdates);
            }

            // Sync Personal Privacy Settings
            if (isset($data['privacy']) && is_array($data['privacy'])) {
                $privacy = $targetUser->privacySettings()->firstOrCreate(['user_id' => $targetUser->id]);
                $privacyMap = [
                    'first_name' => 'first_name_privacy',
                    'last_name' => 'last_name_privacy',
                    'gender' => 'gender_privacy',
                    'birth_date' => 'dob_privacy',
                    'phone' => 'phone_privacy',
                    'email' => 'email_privacy',
                    'country' => 'country_privacy',
                    'city' => 'city_privacy',
                    'address' => 'address_privacy',
                ];
                $pUpdates = [];
                foreach ($privacyMap as $fKey => $colKey) {
                    if (isset($data['privacy'][$fKey])) {
                        $pUpdates[$colKey] = strtolower(trim((string) $data['privacy'][$fKey]));
                    }
                }
                if (isset($data['dob_display_format'])) {
                    $pUpdates['dob_display_format'] = strtolower(trim((string) $data['dob_display_format']));
                }
                if (! empty($pUpdates)) {
                    $privacy->update($pUpdates);
                    $updatedSections[] = 'privacy';
                }
            }

            // 5. Education Section
            if (isset($data['educations']) && is_array($data['educations'])) {
                $targetUser->educations()->delete();
                foreach ($data['educations'] as $edu) {
                    ProfileEducation::create([
                        'user_id' => $targetUser->id,
                        'institution_name' => $edu['institution_name'],
                        'degree' => $edu['degree'] ?? null,
                        'field_of_study' => $edu['field_of_study'] ?? null,
                        'start_date' => $edu['start_date'] ?? null,
                        'end_date' => $edu['end_date'] ?? null,
                        'is_current' => (bool) ($edu['is_current'] ?? false),
                        'grade' => $edu['grade'] ?? null,
                        'description' => $edu['description'] ?? null,
                        'privacy' => $edu['privacy'] ?? 'public',
                    ]);
                }
                $updatedSections[] = 'educations';
            }

            // 6. Work Experience Section
            if (isset($data['experiences']) && is_array($data['experiences'])) {
                $targetUser->experiences()->delete();
                foreach ($data['experiences'] as $exp) {
                    ProfileExperience::create([
                        'user_id' => $targetUser->id,
                        'company_name' => $exp['company_name'],
                        'job_title' => $exp['job_title'],
                        'employment_type' => $exp['employment_type'] ?? null,
                        'location' => $exp['location'] ?? null,
                        'is_remote' => (bool) ($exp['is_remote'] ?? false),
                        'start_date' => $exp['start_date'] ?? null,
                        'end_date' => $exp['end_date'] ?? null,
                        'is_current' => (bool) ($exp['is_current'] ?? false),
                        'description' => $exp['description'] ?? null,
                        'privacy' => $exp['privacy'] ?? 'public',
                    ]);
                }
                $updatedSections[] = 'experiences';
            }

            // 7. Skills Section
            if (isset($data['skills']) && is_array($data['skills'])) {
                $targetUser->skills()->delete();
                foreach ($data['skills'] as $skill) {
                    if (is_array($skill) && ! empty($skill['name'])) {
                        ProfileSkill::create([
                            'user_id' => $targetUser->id,
                            'name' => trim($skill['name']),
                            'level' => $skill['level'] ?? 'beginner',
                        ]);
                    } elseif (is_string($skill) && trim($skill) !== '') {
                        ProfileSkill::create([
                            'user_id' => $targetUser->id,
                            'name' => trim($skill),
                            'level' => 'intermediate',
                        ]);
                    }
                }
                $updatedSections[] = 'skills';
            }

            // 8. Interests Section
            if (isset($data['interests']) && is_array($data['interests'])) {
                $targetUser->interests()->delete();
                $interestsList = [];
                foreach ($data['interests'] as $interest) {
                    if (is_array($interest) && ! empty($interest['name'])) {
                        ProfileInterest::create([
                            'user_id' => $targetUser->id,
                            'name' => trim($interest['name']),
                            'category' => $interest['category'] ?? null,
                        ]);
                        $interestsList[] = trim($interest['name']);
                    } elseif (is_string($interest) && trim($interest) !== '') {
                        ProfileInterest::create([
                            'user_id' => $targetUser->id,
                            'name' => trim($interest),
                            'category' => null,
                        ]);
                        $interestsList[] = trim($interest);
                    }
                }
                $profile->update(['interests' => $interestsList]);
                $updatedSections[] = 'interests';
            }

            // 9. Languages Section
            if (isset($data['languages']) && is_array($data['languages'])) {
                $targetUser->languages()->delete();
                foreach ($data['languages'] as $lang) {
                    if (is_array($lang) && ! empty($lang['language'])) {
                        ProfileLanguage::create([
                            'user_id' => $targetUser->id,
                            'language' => trim($lang['language']),
                            'proficiency' => $lang['proficiency'] ?? 'conversational',
                        ]);
                    } elseif (is_string($lang) && trim($lang) !== '') {
                        ProfileLanguage::create([
                            'user_id' => $targetUser->id,
                            'language' => trim($lang),
                            'proficiency' => 'conversational',
                        ]);
                    }
                }
                $updatedSections[] = 'languages';
            }

            // 10. Social Links Section
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $targetUser->socialLinks()->delete();
                $socialMap = [];
                foreach ($data['social_links'] as $key => $val) {
                    if (is_array($val) && ! empty($val['platform']) && ! empty($val['url'])) {
                        $platform = strtolower(trim($val['platform']));
                        $url = trim($val['url']);
                        ProfileSocialLink::create([
                            'user_id' => $targetUser->id,
                            'platform' => $platform,
                            'url' => $url,
                            'is_visible' => (bool) ($val['is_visible'] ?? true),
                        ]);
                        $socialMap[$platform] = $url;
                    } elseif (is_string($key) && is_string($val) && trim($val) !== '') {
                        $platform = strtolower(trim($key));
                        $url = trim($val);
                        ProfileSocialLink::create([
                            'user_id' => $targetUser->id,
                            'platform' => $platform,
                            'url' => $url,
                            'is_visible' => true,
                        ]);
                        $socialMap[$platform] = $url;
                    }
                }
                $profile->update(['social_links' => $socialMap]);
                $updatedSections[] = 'social_links';
            }

            // Record Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'profile.updated',
                'entity_type' => UserProfile::class,
                'entity_id' => $profile->id,
                'old_values' => $oldProfile,
                'new_values' => [
                    'profile' => $profile->fresh()->toArray(),
                    'updated_sections' => $updatedSections,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // Invalidate Redis Profile Cache
            $this->invalidateProfileCache($targetUser);

            // Reload relationships
            $targetUser->load([
                'profile',
                'educations',
                'experiences',
                'skills',
                'interests',
                'languages',
                'socialLinks',
            ]);

            // Dispatch ProfileUpdatedEvent
            event(new ProfileUpdatedEvent($targetUser, $updatedSections, $actor));

            return PublicProfileDTO::fromUser($targetUser, $actor);
        });
    }

    /**
     * Upload and update user avatar photo, generating a timeline announcement post.
     *
     * @return array{avatar_url: string, post: ?Post}
     */
    public function updateAvatar(User $user, UploadedFile $file, ?string $caption = null, ?array $crop = null): array
    {
        /** @var AvatarService $avatarService */
        $avatarService = app(AvatarService::class);

        return $avatarService->uploadAvatar($user, $file, $crop, $caption);
    }

    /**
     * Upload and update user cover photo, generating a timeline announcement post.
     *
     * @return array{cover_url: string, post: ?Post}
     */
    public function updateCover(User $user, UploadedFile $file, ?string $caption = null, ?array $crop = null): array
    {
        /** @var CoverService $coverService */
        $coverService = app(CoverService::class);

        return $coverService->uploadCover($user, $file, $crop, $caption);
    }

    /**
     * Toggle Profile Lock (Facebook-style profile privacy).
     */
    public function toggleProfileLock(User $user): bool
    {
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $newState = ! $settings->is_profile_locked;

        $settings->update([
            'is_profile_locked' => $newState,
            // When locked, default posts visibility to friends only
            'who_can_see_posts' => $newState ? 'friends' : 'public',
            'who_can_see_friends' => $newState ? 'friends' : 'public',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => $newState ? 'profile.locked' : 'profile.unlocked',
            'entity_type' => UserSetting::class,
            'entity_id' => $settings->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->invalidateProfileCache($user);

        return $newState;
    }

    /**
     * Get user's personal timeline posts respecting privacy.
     */
    public function getUserTimeline(User $targetUser, ?User $viewer = null, int $perPage = 15)
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);

        // If profile is locked and viewer is not friend or owner, return empty collection
        if ($targetUser->settings?->is_profile_locked && ! $isOwner && ! $isFriend) {
            return collect();
        }

        $query = Post::where('user_id', $targetUser->id)
            ->with(['user.profile', 'media'])
            ->orderByDesc('is_pinned')
            ->latest('created_at');

        if (! $isOwner) {
            if ($isFriend) {
                $query->whereIn('audience', ['public', 'friends']);
            } else {
                $query->where('audience', 'public');
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Get photos uploaded by the user.
     */
    public function getUserPhotos(User $targetUser, ?User $viewer = null, int $limit = 12): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);

        if ($targetUser->settings?->is_profile_locked && ! $isOwner && ! $isFriend) {
            return [];
        }

        $photos = [];

        if ($targetUser->profile?->avatar_url) {
            $photos[] = [
                'type' => 'avatar',
                'url' => UserProfile::normalizeStorageUrl($targetUser->profile->avatar_url),
                'created_at' => $targetUser->profile->updated_at,
            ];
        }

        if ($targetUser->profile?->cover_url) {
            $photos[] = [
                'type' => 'cover',
                'url' => UserProfile::normalizeStorageUrl($targetUser->profile->cover_url),
                'created_at' => $targetUser->profile->updated_at,
            ];
        }

        $mediaPhotos = Media::where('user_id', $targetUser->id)
            ->where('mime_type', 'like', 'image/%')
            ->latest()
            ->take($limit)
            ->get();

        foreach ($mediaPhotos as $m) {
            $photos[] = [
                'id' => $m->id,
                'type' => $m->collection,
                'url' => UserProfile::normalizeStorageUrl(Storage::disk($m->disk)->url($m->original_path)),
                'created_at' => $m->created_at,
            ];
        }

        return array_slice($photos, 0, $limit);
    }

    /**
     * Get paginated friends list with mutual friend indicators.
     */
    public function getUserFriends(User $targetUser, ?User $viewer = null, int $perPage = 20): LengthAwarePaginator
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $privacy = $targetUser->settings?->who_can_see_friends ?? 'public';

        // Enforce who_can_see_friends privacy
        if (! $isOwner) {
            if ($privacy === 'only_me' || ($privacy === 'friends' && ! $isFriend)) {
                return User::whereRaw('1 = 0')->paginate($perPage);
            }
        }

        $friendIds = $targetUser->getFriendIds();
        $viewerFriendIds = $viewer ? $viewer->getFriendIds() : [];

        $friendships = $isOwner ? Friendship::where('user_id', $targetUser->id)->whereIn('friend_id', $friendIds)->get()->keyBy('friend_id') : collect();

        $paginator = User::whereIn('id', $friendIds)
            ->with('profile')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (User $friend) use ($viewer, $viewerFriendIds, $friendships) {
            $mutualCount = $viewer ? count(array_intersect($friend->getFriendIds(), $viewerFriendIds)) : 0;
            $fship = $friendships->get($friend->id);

            return [
                'id' => $friend->id,
                'name' => $friend->name ?: $friend->username,
                'username' => $friend->username,
                'avatar_url' => $friend->profile?->avatar_url,
                'bio' => $friend->profile?->bio,
                'mutual_friends_count' => $mutualCount,
                'is_favorite' => (bool) ($fship?->is_favorite ?? false),
                'is_close_friend' => (bool) ($fship?->is_close_friend ?? false),
            ];
        });

        return $paginator;
    }

    /**
     * Get structured about section for a user profile respecting privacy.
     */
    public function getAbout(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $isLocked = (bool) ($targetUser->settings?->is_profile_locked ?? false);
        $isLockedView = $isLocked && ! $isFriend && ! $isOwner;

        $profile = $targetUser->profile;
        $dto = PublicProfileDTO::fromUser($targetUser, $viewer);

        return [
            'overview' => [
                'name' => $targetUser->name ?: $targetUser->username,
                'bio' => $profile?->bio,
                'headline' => $profile?->headline,
                'intro' => $profile?->headline,
                'about' => $isLockedView ? null : $profile?->about,
                'work' => $isLockedView ? null : $profile?->work,
                'education' => $isLockedView ? null : $profile?->education,
                'location' => $isLockedView ? null : $profile?->location,
                'hometown' => $isLockedView ? null : $profile?->hometown,
                'relationship_status' => $isLockedView ? null : $profile?->relationship_status,
            ],
            'work_and_education' => [
                'work' => $isLockedView ? null : $profile?->work,
                'education' => $isLockedView ? null : $profile?->education,
                'educations' => $dto['sections']['educations'],
                'experiences' => $dto['sections']['experiences'],
            ],
            'places_lived' => [
                'current_city' => $dto['profile']['city'] ?? ($isLockedView ? null : $profile?->location),
                'country' => $dto['profile']['country'] ?? null,
                'address' => $dto['profile']['address'] ?? null,
                'hometown' => $isLockedView ? null : $profile?->hometown,
            ],
            'contact_and_basic_info' => [
                'first_name' => $dto['user']['first_name'] ?? null,
                'last_name' => $dto['user']['last_name'] ?? null,
                'email' => $dto['user']['email'] ?? null,
                'phone' => $dto['user']['phone'] ?? null,
                'country' => $dto['profile']['country'] ?? null,
                'city' => $dto['profile']['city'] ?? null,
                'address' => $dto['profile']['address'] ?? null,
                'website' => $isLockedView ? null : $profile?->website,
                'social_links' => $isLockedView ? [] : ($profile?->social_links ?: $dto['sections']['social_links']),
                'gender' => $dto['profile']['gender'] ?? null,
                'birth_date' => $dto['profile']['birth_date'] ?? null,
                'birth_date_meta' => $dto['profile']['birth_date_meta'] ?? null,
                'joined_date' => $targetUser->created_at?->format('F Y'),
            ],
            'skills_and_languages' => [
                'skills' => $dto['sections']['skills'],
                'languages' => $dto['sections']['languages'],
            ],
            'family_and_relationships' => [
                'relationship_status' => $isLockedView ? null : $profile?->relationship_status,
            ],
            'interests' => $dto['sections']['interests'],
        ];
    }

    /**
     * Reposition user cover photo (vertical alignment 0-100%).
     */
    public function updateCoverPosition(User $user, int $positionY): int
    {
        /** @var CoverService $coverService */
        $coverService = app(CoverService::class);

        return $coverService->repositionCover($user, $positionY);
    }

    /**
     * Update user's settings.
     */
    public function updateSettings(User $user, array $data, ?string $ip = null, ?string $userAgent = null): UserSetting
    {
        $settings = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $oldValues = $settings->toArray();
        $settings->update($data);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'settings.updated',
            'entity_type' => UserSetting::class,
            'entity_id' => $settings->id,
            'old_values' => $oldValues,
            'new_values' => $settings->fresh()->toArray(),
            'ip_address' => $ip ?: request()->ip(),
            'user_agent' => $userAgent ?: request()->userAgent(),
        ]);

        $this->invalidateProfileCache($user);

        return $settings;
    }

    /**
     * Invalidate all cached public profile entries for a user.
     */
    public function invalidateProfileCache(User|string $user): void
    {
        $userObj = $user instanceof User ? $user : User::where('username', strtolower($user))->first();
        $username = $user instanceof User ? $user->username : $user;
        $username = strtolower($username);

        Cache::forget("profile:public:{$username}");
        Cache::forget("profile:about:{$username}");
        Cache::forget("profile:v2:{$username}");
        Cache::forget("user_profile:{$username}");

        if ($userObj) {
            $userId = $userObj->id;
            Cache::forget("profile:{$userId}");
            Cache::forget("friends:{$userId}");
            Cache::forget("followers:{$userId}");
            Cache::forget("following:{$userId}");
            Cache::forget("counters:{$userId}");
            Cache::forget("analytics:{$userId}");
            Cache::forget("privacy:{$userId}");

            if (Cache::supportsTags()) {
                try {
                    Cache::tags(["user_{$userId}"])->flush();
                } catch (\Throwable) {
                    // Tag flushing ignored if not supported by active store
                }
            }
        }

        app(ProfileCompletionService::class)->invalidate($user);
    }

    /**
     * Invalidate cached relationship state between two users.
     */
    public function invalidateRelationshipCache(int $userId1, int $userId2): void
    {
        Cache::forget("relationship:{$userId1}:{$userId2}");
        Cache::forget("relationship:{$userId2}:{$userId1}");
    }

    /**
     * Get videos uploaded by the user.
     */
    public function getUserVideos(User $targetUser, ?User $viewer = null, int $limit = 12): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);

        if ($targetUser->settings?->is_profile_locked && ! $isOwner && ! $isFriend) {
            return [];
        }

        $mediaVideos = Media::where('user_id', $targetUser->id)
            ->where('mime_type', 'like', 'video/%')
            ->latest()
            ->take($limit)
            ->get();

        $videos = [];
        foreach ($mediaVideos as $m) {
            $videos[] = [
                'id' => $m->id,
                'type' => $m->collection,
                'url' => UserProfile::normalizeStorageUrl(Storage::disk($m->disk)->url($m->original_path)),
                'thumbnail_url' => $m->thumbnail_path ? UserProfile::normalizeStorageUrl(Storage::disk($m->disk)->url($m->thumbnail_path)) : null,
                'created_at' => $m->created_at,
            ];
        }

        return $videos;
    }

    /**
     * Helper to validate image upload.
     */
    protected function validateImageFile(UploadedFile $file, int $maxMegabytes = 5): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => ['ফাইল আপলোডে ত্রুটি ঘটেছে।']]);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (! in_array($file->getMimeType(), $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => ['শুধুমাত্র JPG, PNG, WEBP অথবা GIF ছবি গ্রহণযোগ্য।'],
            ]);
        }

        $maxBytes = $maxMegabytes * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => ["ছবির সাইজ সর্বোচ্চ {$maxMegabytes}MB হতে পারবে।"],
            ]);
        }
    }

    /**
     * Export complete user profile data package for backup and compliance (Requirement 30).
     *
     * @return array<string, mixed>
     */
    public function exportUserData(User $user): array
    {
        $user->loadMissing([
            'profile',
            'settings',
            'educations',
            'experiences',
            'skills',
            'languages',
            'interests',
            'socialLinks',
            'privacySettings',
            'notificationSettings',
        ]);

        $posts = Post::where('user_id', $user->id)
            ->latest('id')
            ->take(200)
            ->get(['id', 'content', 'audience', 'type', 'location', 'likes_count', 'comments_count', 'shares_count', 'created_at']);

        $friendIds = $user->getFriendIds();
        $friends = User::whereIn('id', $friendIds)
            ->get(['id', 'name', 'username']);

        $followers = $user->followers()
            ->get(['users.id', 'users.name', 'users.username']);

        $following = $user->following()
            ->get(['users.id', 'users.name', 'users.username']);

        $savedItems = app(ProfileEnterpriseService::class)->getSavedItems($user);
        $activityLogs = app(ProfileEnterpriseService::class)->getActivityLogs($user, null, 100);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'profile.data_exported',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => null,
            'new_values' => ['exported_at' => now()->toIso8601String()],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return [
            'export_metadata' => [
                'app_name' => config('app.name', 'Jugajug'),
                'exported_at' => now()->toIso8601String(),
                'user_id' => $user->id,
                'username' => $user->username,
                'version' => '2.0-enterprise',
            ],
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'country' => $user->country,
                'status' => $user->status,
                'joined_at' => $user->created_at?->toIso8601String(),
            ],
            'profile' => $user->profile ? [
                'bio' => $user->profile->bio,
                'headline' => $user->profile->headline,
                'about' => $user->profile->about,
                'website' => $user->profile->website,
                'city' => $user->profile->city,
                'country' => $user->profile->country,
                'hometown' => $user->profile->hometown,
                'relationship_status' => $user->profile->relationship_status,
                'pronouns' => $user->profile->pronouns,
                'category' => $user->profile->category,
                'blood_group' => $user->profile->blood_group,
                'division' => $user->profile->division,
                'district' => $user->profile->district,
                'upazila' => $user->profile->upazila,
                'portfolio' => $user->profile->portfolio,
                'hobbies' => $user->profile->hobbies,
                'favorite_music' => $user->profile->favorite_music,
                'favorite_books' => $user->profile->favorite_books,
                'favorite_movies' => $user->profile->favorite_movies,
            ] : null,
            'education' => $user->educations->toArray(),
            'work_experience' => $user->experiences->toArray(),
            'skills' => $user->skills->toArray(),
            'languages' => $user->languages->toArray(),
            'interests' => $user->interests->toArray(),
            'social_links' => $user->socialLinks->toArray(),
            'privacy_settings' => $user->privacySettings?->toArray(),
            'notification_settings' => $user->notificationSettings?->toArray(),
            'posts' => $posts->toArray(),
            'friends' => $friends->toArray(),
            'followers' => $followers->toArray(),
            'following' => $following->toArray(),
            'saved_items' => $savedItems->toArray(),
            'activity_logs' => $activityLogs ? $activityLogs->toArray() : [],
        ];
    }

    /**
     * Deactivate user account temporarily (Requirement 29).
     */
    public function deactivateUser(User $user, ?string $reason = null): bool
    {
        $oldStatus = $user->status;
        $user->update(['status' => 'deactivated']);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'profile.deactivated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'deactivated', 'reason' => $reason],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->invalidateProfileCache($user);

        return true;
    }

    /**
     * Soft delete user account permanently/grace period (Requirement 29).
     */
    public function deleteUserAccount(User $user, ?string $reason = null): bool
    {
        $oldStatus = $user->status;
        $user->update(['status' => 'deleted']);
        $user->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'profile.deleted',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'deleted', 'reason' => $reason],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->invalidateProfileCache($user);

        return true;
    }
}
