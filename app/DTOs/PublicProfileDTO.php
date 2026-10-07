<?php

namespace App\DTOs;

use App\Models\PrivacySetting;
use App\Models\User;
use App\Services\ProfilePrivacyService;
use Carbon\Carbon;

class PublicProfileDTO
{
    /**
     * Build filtered public profile package respecting all privacy rules and profile lock.
     *
     * @return array<string, mixed>
     */
    public static function fromUser(User $targetUser, ?User $viewer = null): array
    {
        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $targetFriendIds = $targetUser->getFriendIds();
        $isFriend = $viewer && in_array($viewer->id, $targetFriendIds, true);
        $isFollower = $viewer && $targetUser->isFollowedBy($viewer);
        $settings = $targetUser->settings;
        $isProfileLocked = (bool) ($settings?->is_profile_locked ?? false);
        $isLockedView = $isProfileLocked && ! $isFriend && ! $isOwner;

        $profile = $targetUser->profile;

        // Privacy filters from UserSettings or PrivacySettings
        $friendsPrivacy = $settings?->who_can_see_friends ?? 'public';
        $canSeeFriends = $isOwner || ($friendsPrivacy === 'public') || ($friendsPrivacy === 'friends' && $isFriend);

        $privacyService = app(ProfilePrivacyService::class);
        $isAdmin = $privacyService->isAdmin($viewer);

        // 1. Educations (Respect section privacy, each item's individual privacy or locked view)
        $canViewEducationSection = $isAdmin || ($isOwner || $privacyService->canViewEducation($targetUser, $viewer));
        $educations = (! $canViewEducationSection || $isLockedView) ? [] : $targetUser->educations
            ->filter(function ($item) use ($isOwner, $isFriend, $isFollower, $isAdmin) {
                if ($isOwner || $isAdmin) {
                    return true;
                }
                if ($item->privacy === 'only_me') {
                    return false;
                }
                if ($item->privacy === 'friends') {
                    return $isFriend;
                }
                if ($item->privacy === 'followers') {
                    return $isFollower || $isFriend;
                }

                return true;
            })
            ->map(fn ($item) => [
                'id' => $item->id,
                'institution' => $item->institution_name,
                'institution_name' => $item->institution_name,
                'degree' => $item->degree,
                'field_of_study' => $item->field_of_study,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'is_current' => (bool) $item->is_current,
                'currently_studying' => (bool) $item->is_current,
                'grade' => $item->grade,
                'description' => $item->description,
                'location' => $item->location,
                'display_order' => (int) $item->display_order,
                'privacy' => $item->privacy,
            ])
            ->values()
            ->toArray();

        // 2. Experiences (Respect section privacy, each item's individual privacy or locked view)
        $canViewWorkSection = $isAdmin || ($isOwner || $privacyService->canViewWork($targetUser, $viewer));
        $experiences = (! $canViewWorkSection || $isLockedView) ? [] : $targetUser->experiences
            ->filter(function ($item) use ($isOwner, $isFriend, $isFollower, $isAdmin) {
                if ($isOwner || $isAdmin) {
                    return true;
                }
                if ($item->privacy === 'only_me') {
                    return false;
                }
                if ($item->privacy === 'friends') {
                    return $isFriend;
                }
                if ($item->privacy === 'followers') {
                    return $isFollower || $isFriend;
                }

                return true;
            })
            ->map(fn ($item) => [
                'id' => $item->id,
                'company' => $item->company_name,
                'company_name' => $item->company_name,
                'position' => $item->job_title,
                'job_title' => $item->job_title,
                'employment_type' => $item->employment_type,
                'location' => $item->location,
                'is_remote' => (bool) $item->is_remote,
                'start_date' => $item->start_date?->format('Y-m-d'),
                'end_date' => $item->end_date?->format('Y-m-d'),
                'is_current' => (bool) $item->is_current,
                'currently_working' => (bool) $item->is_current,
                'description' => $item->description,
                'display_order' => (int) $item->display_order,
                'privacy' => $item->privacy,
            ])
            ->values()
            ->toArray();

        // 3. Skills (Respect skills_privacy and locked view)
        $canViewSkillsSection = $isAdmin || ($isOwner || $privacyService->canViewSkills($targetUser, $viewer));
        $skills = (! $canViewSkillsSection || $isLockedView) ? [] : $targetUser->skills
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'level' => $item->level,
                'endorsements_count' => $item->endorsements_count,
            ])
            ->values()
            ->toArray();

        // 4. Interests (Respect interests_privacy and locked view)
        $canViewInterestsSection = $isAdmin || ($isOwner || $privacyService->canViewInterests($targetUser, $viewer));
        $interests = (! $canViewInterestsSection || $isLockedView) ? [] : $targetUser->interests
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
            ])
            ->values()
            ->toArray();

        // 5. Languages (Respect languages_privacy and locked view)
        $canViewLanguagesSection = $isAdmin || ($isOwner || $privacyService->canViewLanguages($targetUser, $viewer));
        $languages = (! $canViewLanguagesSection || $isLockedView) ? [] : $targetUser->languages
            ->map(fn ($item) => [
                'id' => $item->id,
                'language' => $item->language,
                'proficiency' => $item->proficiency,
            ])
            ->values()
            ->toArray();

        // 6. Social Links (Respect social_links_privacy and locked view)
        $canViewSocialLinksSection = $isAdmin || ($isOwner || $privacyService->canViewSocialLinks($targetUser, $viewer));
        $socialLinks = (! $canViewSocialLinksSection || $isLockedView) ? [] : $targetUser->socialLinks
            ->filter(function ($item) use ($isOwner, $isFriend, $isFollower, $isAdmin) {
                if ($isOwner || $isAdmin) {
                    return true;
                }
                if (! $item->is_visible) {
                    return false;
                }
                if ($item->privacy === 'only_me') {
                    return false;
                }
                if ($item->privacy === 'friends') {
                    return $isFriend;
                }
                if ($item->privacy === 'followers') {
                    return $isFollower || $isFriend;
                }

                return true;
            })
            ->map(fn ($item) => [
                'id' => $item->id,
                'platform' => $item->platform,
                'platform_name' => $item->platform_name,
                'url' => $item->url,
                'display_order' => (int) $item->display_order,
                'is_visible' => (bool) $item->is_visible,
                'privacy' => $item->privacy ?? 'public',
            ])
            ->values()
            ->toArray();

        // 7. Mutual Friends calculation
        $mutualFriendsCount = 0;
        $mutualFriendsPreview = [];
        if ($viewer && ! $isOwner) {
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
                    ])
                    ->toArray();
            }
        }

        // 8. Friends count & followers count
        $friendsCount = count($targetFriendIds);
        $followersCount = $targetUser->followers()->count();
        $followingCount = $targetUser->following()->count();
        $postsCount = $targetUser->posts()->count();

        // 9. Personal Info & Profile Fields Privacy Resolution
        $privacySetting = $targetUser->privacySettings ?: new PrivacySetting(['user_id' => $targetUser->id]);

        $rawFirstName = $profile?->first_name ?: $targetUser->first_name;
        $rawLastName = $profile?->last_name ?: $targetUser->last_name;
        $rawGender = $profile?->gender ?: $targetUser->gender;
        $rawPhone = $targetUser->phone;
        $rawEmail = $targetUser->email;
        $rawCountry = $profile?->country ?: $targetUser->country;
        $rawCity = $profile?->city ?: $profile?->location;
        $rawAddress = $profile?->address;
        $rawDob = $profile?->birth_date ?: $targetUser->birth_date;

        $showFirstName = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacySetting->first_name_privacy ?? 'public', $targetUser, $viewer)));
        $showLastName = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacySetting->last_name_privacy ?? 'public', $targetUser, $viewer)));
        $showGender = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacySetting->gender_privacy ?? 'public', $targetUser, $viewer)));
        $showPhone = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->canViewPhone($targetUser, $viewer)));
        $showEmail = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->canViewEmail($targetUser, $viewer)));
        $showCountry = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacySetting->country_privacy ?? 'public', $targetUser, $viewer)));
        $showCity = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacySetting->city_privacy ?? 'public', $targetUser, $viewer)));
        $showAddress = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacySetting->address_privacy ?? 'only_me', $targetUser, $viewer)));
        $showLocation = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->canViewLocation($targetUser, $viewer)));

        // Date of Birth Formatting (full, month_day, age, hidden)
        $formattedBirthDate = null;
        $dobAge = null;
        $dobFormatUsed = 'hidden';
        $canSeeDob = $isAdmin || ($isOwner || (! $isLockedView && $privacyService->canViewDob($targetUser, $viewer)));
        if ($rawDob && $canSeeDob) {
            $carbonDob = $rawDob instanceof Carbon ? $rawDob : Carbon::parse($rawDob);
            $dobAge = $carbonDob->age;
            $format = $privacySetting->dob_display_format ?? 'month_day';
            $dobFormatUsed = $format;

            if ($format !== 'hidden' || $isOwner || $isAdmin) {
                $formattedBirthDate = match ($format) {
                    'full' => $carbonDob->format('Y-m-d'),
                    'month_day' => $carbonDob->format('F j'),
                    'age' => "{$dobAge} years old",
                    'hidden' => ($isOwner || $isAdmin) ? $carbonDob->format('Y-m-d') : null,
                    default => $carbonDob->format('F j'),
                };
            }
        }

        // Basic Profile fields with section privacy
        $avatarUrl = ($isAdmin || $isOwner || $privacyService->canViewAvatar($targetUser, $viewer)) ? $profile?->avatar_url : null;
        $coverUrl = ($isAdmin || $isOwner || $privacyService->canViewCover($targetUser, $viewer)) ? $profile?->cover_url : null;
        $coverPositionY = $profile?->cover_position_y ?? 50;
        $bio = ($isAdmin || $isOwner || $privacyService->canViewBio($targetUser, $viewer)) ? $profile?->bio : null;
        $about = ($isAdmin || $isOwner || (! $isLockedView && $privacyService->canViewAbout($targetUser, $viewer))) ? $profile?->about : null;
        $location = $showLocation ? $profile?->location : null;
        $city = ($showCity && $showLocation) ? $rawCity : null;
        $country = ($showCountry && $showLocation) ? $rawCountry : null;
        $address = ($showAddress && $showLocation) ? $rawAddress : null;
        $hometown = ($isLockedView && ! $isOwner && ! $isAdmin) ? null : $profile?->hometown;
        $website = ($isLockedView && ! $isOwner && ! $isAdmin) ? null : $profile?->website;
        $gender = $showGender ? $rawGender : null;
        $relationshipStatus = ($isLockedView && ! $isOwner && ! $isAdmin) ? null : $profile?->relationship_status;
        $work = ($canViewWorkSection && ! $isLockedView) ? $profile?->work : null;
        $education = ($canViewEducationSection && ! $isLockedView) ? $profile?->education : null;

        // Joined date
        $joinedDate = $targetUser->created_at?->format('F Y');

        $userPayload = [
            'id' => $targetUser->id,
            'name' => $targetUser->name ?: $targetUser->username,
            'username' => $targetUser->username,
            'country' => $country,
            'status' => $targetUser->status,
            'is_verified' => $targetUser->hasVerifiedProfile(),
            'verification_status' => $targetUser->verification_status,
            'joined_date' => $joinedDate,
            'member_since' => $joinedDate,
            'roles' => $targetUser->roles->pluck('name'),
            'created_at' => $targetUser->created_at?->toIso8601String(),
        ];

        if ($showFirstName) {
            $userPayload['first_name'] = $rawFirstName;
        }
        if ($showLastName) {
            $userPayload['last_name'] = $rawLastName;
        }
        if ($showEmail) {
            $userPayload['email'] = $rawEmail;
        }
        if ($showPhone) {
            $userPayload['phone'] = $rawPhone;
        }

        return [
            'user' => $userPayload,
            'profile' => [
                'first_name' => $showFirstName ? $rawFirstName : null,
                'last_name' => $showLastName ? $rawLastName : null,
                'display_name' => $profile?->display_name ?: $targetUser->name,
                'slug' => $profile?->slug ?: $targetUser->username,
                'avatar_url' => $avatarUrl,
                'cover_url' => $coverUrl,
                'cover_position_y' => $coverPositionY,
                'bio' => $bio,
                'headline' => $profile?->headline,
                'intro' => $profile?->headline,
                'about' => $about,
                'location' => $location,
                'country' => $country,
                'city' => $city,
                'address' => $address,
                'hometown' => $hometown,
                'website' => $website,
                'gender' => $gender,
                'relationship_status' => $relationshipStatus,
                'work' => $work,
                'education' => $education,
                'birth_date' => $formattedBirthDate,
                'birth_date_meta' => [
                    'formatted' => $formattedBirthDate,
                    'format_used' => $dobFormatUsed,
                    'is_visible' => $formattedBirthDate !== null,
                    'age' => $dobAge,
                ],
                'social_links' => $isLockedView ? [] : ($profile?->social_links ?? []),
            ],
            'stats' => [
                'followers_count' => $followersCount,
                'following_count' => $followingCount,
                'posts_count' => $postsCount,
                'friends_count' => $canSeeFriends ? $friendsCount : 0,
                'mutual_friends_count' => $mutualFriendsCount,
                'mutual_friends' => $mutualFriendsPreview,
                'completion_percentage' => $profile ? $profile->calculateCompletionPercentage() : 0,
            ],
            'sections' => [
                'educations' => $educations,
                'experiences' => $experiences,
                'skills' => $skills,
                'interests' => $interests,
                'languages' => $languages,
                'social_links' => $socialLinks,
            ],
            'privacy' => [
                'is_owner' => $isOwner,
                'is_friend' => $isFriend,
                'is_profile_locked' => $isProfileLocked,
                'is_locked_view' => $isLockedView,
                'can_see_friends' => $canSeeFriends,
            ],
        ];
    }
}
