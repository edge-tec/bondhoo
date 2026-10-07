<?php

namespace App\Services;

use App\Events\ProfileUpdatedEvent;
use App\Models\AuditLog;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonalInfoService
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Update user's personal information and privacy settings.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updatePersonalInfo(User $targetUser, array $data, User $actor, ?string $ip = null, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($targetUser, $data, $actor, $ip, $userAgent) {
            $profile = $targetUser->profile()->firstOrCreate(['user_id' => $targetUser->id]);
            $privacy = $targetUser->privacySettings()->firstOrCreate(['user_id' => $targetUser->id]);

            $oldProfileData = $profile->toArray();
            $oldUserData = $targetUser->only(['name', 'first_name', 'last_name', 'email', 'phone', 'birth_date', 'gender', 'country']);
            $oldPrivacyData = $privacy->toArray();

            $updatedFields = [];

            // 1. Update Core User Model Fields
            $userUpdates = [];
            if (array_key_exists('first_name', $data) && $data['first_name'] !== $targetUser->first_name) {
                $userUpdates['first_name'] = $data['first_name'];
                $updatedFields[] = 'first_name';
            }
            if (array_key_exists('last_name', $data) && $data['last_name'] !== $targetUser->last_name) {
                $userUpdates['last_name'] = $data['last_name'];
                $updatedFields[] = 'last_name';
            }
            if (array_key_exists('email', $data) && $data['email'] !== $targetUser->email) {
                $userUpdates['email'] = $data['email'];
                $updatedFields[] = 'email';
            }
            if (array_key_exists('phone', $data) && $data['phone'] !== $targetUser->phone) {
                $userUpdates['phone'] = $data['phone'];
                $updatedFields[] = 'phone';
            }
            if (array_key_exists('birth_date', $data) && $data['birth_date'] !== $targetUser->birth_date?->format('Y-m-d')) {
                $userUpdates['birth_date'] = $data['birth_date'];
                $updatedFields[] = 'birth_date';
            }
            if (array_key_exists('gender', $data) && $data['gender'] !== $targetUser->gender) {
                $userUpdates['gender'] = $data['gender'];
                $updatedFields[] = 'gender';
            }
            if (array_key_exists('country', $data) && $data['country'] !== $targetUser->country) {
                $userUpdates['country'] = $data['country'];
                $updatedFields[] = 'country';
            }

            // Sync computed display name or full name if provided
            if (isset($data['display_name']) && ! empty($data['display_name'])) {
                $userUpdates['name'] = $data['display_name'];
            } elseif (isset($data['first_name']) || isset($data['last_name'])) {
                $fn = $data['first_name'] ?? $targetUser->first_name;
                $ln = $data['last_name'] ?? $targetUser->last_name;
                $combined = trim("{$fn} {$ln}");
                if (! empty($combined)) {
                    $userUpdates['name'] = $combined;
                }
            }

            if (! empty($userUpdates)) {
                $targetUser->update($userUpdates);
            }

            // 2. Update UserProfile Fields
            $profileFields = [
                'first_name',
                'last_name',
                'display_name',
                'gender',
                'birth_date',
                'country',
                'city',
                'address',
            ];

            $profileUpdates = [];
            foreach ($profileFields as $f) {
                if (array_key_exists($f, $data)) {
                    $profileUpdates[$f] = $data[$f];
                    if (! in_array($f, $updatedFields, true)) {
                        $updatedFields[] = $f;
                    }
                }
            }

            // If first_name/last_name set and display_name is not specified, auto-sync display_name
            if (! isset($profileUpdates['display_name']) && (! empty($data['first_name']) || ! empty($data['last_name']))) {
                $fn = $data['first_name'] ?? $profile->first_name ?? $targetUser->first_name;
                $ln = $data['last_name'] ?? $profile->last_name ?? $targetUser->last_name;
                $combined = trim("{$fn} {$ln}");
                if (! empty($combined)) {
                    $profileUpdates['display_name'] = $combined;
                }
            }

            if (! empty($profileUpdates)) {
                $profile->update($profileUpdates);
            }

            // 3. Update Granular Privacy Settings
            $privacyUpdates = [];
            if (isset($data['privacy']) && is_array($data['privacy'])) {
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

                foreach ($privacyMap as $fieldKey => $columnKey) {
                    if (isset($data['privacy'][$fieldKey])) {
                        $val = strtolower(trim((string) $data['privacy'][$fieldKey]));
                        $privacyUpdates[$columnKey] = $val;
                        $updatedFields[] = "privacy.{$fieldKey}";

                        // Keep existing phone_visibility & email_visibility in sync
                        if ($fieldKey === 'phone') {
                            $privacyUpdates['phone_visibility'] = $val;
                        } elseif ($fieldKey === 'email') {
                            $privacyUpdates['email_visibility'] = $val;
                        } elseif ($fieldKey === 'birth_date') {
                            $privacyUpdates['birthday_visibility'] = $val;
                        }
                    }
                }
            }

            if (isset($data['dob_display_format'])) {
                $format = strtolower(trim((string) $data['dob_display_format']));
                $privacyUpdates['dob_display_format'] = $format;
                $updatedFields[] = 'dob_display_format';
            }

            if (! empty($privacyUpdates)) {
                $privacy->update($privacyUpdates);
            }

            // 4. Record Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'PERSONAL_INFO_UPDATED',
                'entity_type' => UserProfile::class,
                'entity_id' => $profile->id,
                'old_values' => [
                    'user' => $oldUserData,
                    'profile' => $oldProfileData,
                    'privacy' => $oldPrivacyData,
                ],
                'new_values' => [
                    'user' => $targetUser->fresh()->only(['name', 'first_name', 'last_name', 'email', 'phone', 'birth_date', 'gender', 'country']),
                    'profile' => $profile->fresh()->toArray(),
                    'privacy' => $privacy->fresh()->toArray(),
                    'updated_fields' => $updatedFields,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => $userAgent ?: request()->userAgent(),
            ]);

            // 5. Invalidate Profile Cache
            $this->profileService->invalidateProfileCache($targetUser);

            // 6. Dispatch Event
            event(new ProfileUpdatedEvent($targetUser, $updatedFields, $actor));

            return $this->getPersonalInfo($targetUser, $actor);
        });
    }

    /**
     * Get user's personal information strictly filtered according to privacy configuration.
     *
     * @return array<string, mixed>
     */
    public function getPersonalInfo(User $targetUser, ?User $viewer = null): array
    {
        $privacyService = app(ProfilePrivacyService::class);
        if (! $privacyService->canViewProfile($targetUser, $viewer)) {
            abort(403, 'এই প্রোফাইলটি ব্যক্তিগত অথবা দেখার অনুমতি আপনার নেই।');
        }

        $isAdmin = $privacyService->isAdmin($viewer);
        $isOwner = $viewer && $viewer->id === $targetUser->id;
        $isFriend = $viewer && in_array($viewer->id, $targetUser->getFriendIds(), true);
        $isFollower = $viewer && $targetUser->isFollowedBy($viewer);

        $profile = $targetUser->profile;
        $privacy = $targetUser->privacySettings ?: new PrivacySetting(['user_id' => $targetUser->id]);

        $settings = $targetUser->settings;
        $isProfileLocked = (bool) ($settings?->is_profile_locked ?? false);
        $isLockedView = $isProfileLocked && ! $isFriend && ! $isOwner && ! $isAdmin;

        // Resolve field values
        $firstName = $profile?->first_name ?: $targetUser->first_name;
        $lastName = $profile?->last_name ?: $targetUser->last_name;
        $displayName = $profile?->display_name ?: ($targetUser->name ?: $targetUser->username);
        $gender = $profile?->gender ?: $targetUser->gender;
        $birthDateObj = $profile?->birth_date ?: $targetUser->birth_date;
        $phone = $targetUser->phone;
        $email = $targetUser->email;
        $country = $profile?->country ?: $targetUser->country;
        $city = $profile?->city ?: $profile?->location;
        $address = $profile?->address;

        // Check visibility per field
        $showFirstName = $isAdmin || $isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacy->first_name_privacy ?? 'public', $targetUser, $viewer));
        $showLastName = $isAdmin || $isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacy->last_name_privacy ?? 'public', $targetUser, $viewer));
        $showGender = $isAdmin || $isOwner || (! $isLockedView && $privacyService->isFieldVisible($privacy->gender_privacy ?? 'public', $targetUser, $viewer));
        $showPhone = $isAdmin || $isOwner || (! $isLockedView && $privacyService->canViewPhone($targetUser, $viewer));
        $showEmail = $isAdmin || $isOwner || (! $isLockedView && $privacyService->canViewEmail($targetUser, $viewer));
        $showCountry = $isAdmin || $isOwner || (! $isLockedView && $privacyService->canViewLocation($targetUser, $viewer) && $privacyService->isFieldVisible($privacy->country_privacy ?? 'public', $targetUser, $viewer));
        $showCity = $isAdmin || $isOwner || (! $isLockedView && $privacyService->canViewLocation($targetUser, $viewer) && $privacyService->isFieldVisible($privacy->city_privacy ?? 'public', $targetUser, $viewer));
        $showAddress = $isAdmin || $isOwner || (! $isLockedView && $privacyService->canViewLocation($targetUser, $viewer) && $privacyService->isFieldVisible($privacy->address_privacy ?? 'only_me', $targetUser, $viewer));

        // Date of Birth visibility and formatting
        $formattedDob = $this->formatDateOfBirth(
            dob: $birthDateObj,
            privacySetting: $privacy,
            isOwner: $isOwner,
            isFriend: $isFriend,
            isFollower: $isFollower,
            isLockedView: $isLockedView
        );

        $response = [
            'display_name' => $displayName,
            'first_name' => $showFirstName ? $firstName : null,
            'last_name' => $showLastName ? $lastName : null,
            'gender' => $showGender ? $gender : null,
            'birth_date' => $formattedDob['formatted'],
            'birth_date_meta' => $formattedDob,
            'phone' => $showPhone ? $phone : null,
            'email' => $showEmail ? $email : null,
            'country' => $showCountry ? $country : null,
            'city' => $showCity ? $city : null,
            'address' => $showAddress ? $address : null,
            'privacy' => [
                'first_name' => $privacy->first_name_privacy ?? 'public',
                'last_name' => $privacy->last_name_privacy ?? 'public',
                'gender' => $privacy->gender_privacy ?? 'public',
                'birth_date' => $privacy->dob_privacy ?? 'friends',
                'dob_display_format' => $privacy->dob_display_format ?? 'month_day',
                'phone' => $privacy->phone_privacy ?? ($privacy->phone_visibility ?? 'friends'),
                'email' => $privacy->email_privacy ?? ($privacy->email_visibility ?? 'only_me'),
                'country' => $privacy->country_privacy ?? 'public',
                'city' => $privacy->city_privacy ?? 'public',
                'address' => $privacy->address_privacy ?? 'only_me',
            ],
            'meta' => [
                'is_owner' => $isOwner,
                'is_friend' => $isFriend,
                'is_follower' => $isFollower,
                'is_locked_view' => $isLockedView,
            ],
        ];

        return $response;
    }

    /**
     * Format date of birth based on privacy level and display format.
     *
     * @return array{formatted: ?string, format_used: string, is_visible: bool, age: ?int}
     */
    public function formatDateOfBirth(
        mixed $dob,
        PrivacySetting $privacySetting,
        bool $isOwner,
        bool $isFriend,
        bool $isFollower,
        bool $isLockedView
    ): array {
        if (! $dob || $isLockedView) {
            return [
                'formatted' => null,
                'format_used' => 'hidden',
                'is_visible' => false,
                'age' => null,
            ];
        }

        $dobPrivacy = $privacySetting->dob_privacy ?? 'friends';
        $isVisible = $privacySetting->isFieldVisible($dobPrivacy, $isOwner, $isFriend, $isFollower);

        if (! $isVisible) {
            return [
                'formatted' => null,
                'format_used' => 'hidden',
                'is_visible' => false,
                'age' => null,
            ];
        }

        $carbon = $dob instanceof Carbon ? $dob : Carbon::parse($dob);
        $age = $carbon->age;
        $format = $privacySetting->dob_display_format ?? 'month_day';

        // If viewer is owner, always provide full date access in owner metadata, but format default
        if ($format === 'hidden' && ! $isOwner) {
            return [
                'formatted' => null,
                'format_used' => 'hidden',
                'is_visible' => false,
                'age' => null,
            ];
        }

        $formatted = match ($format) {
            'full' => $carbon->format('Y-m-d'),
            'month_day' => $carbon->format('F j'), // e.g. May 15
            'age' => "{$age} years old",
            'hidden' => $isOwner ? $carbon->format('Y-m-d') : null,
            default => $carbon->format('F j'),
        };

        return [
            'formatted' => $formatted,
            'format_used' => $format,
            'is_visible' => true,
            'age' => $age,
        ];
    }
}
