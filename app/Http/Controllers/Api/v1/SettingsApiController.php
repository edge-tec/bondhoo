<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserTwoFactor;
use App\Services\FriendshipService;
use App\Services\ProfilePrivacyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsApiController extends Controller
{
    public function __construct(
        protected FriendshipService $friendshipService,
        protected ProfilePrivacyService $privacyService
    ) {}

    /**
     * GET /api/v1/settings/overview
     *
     * Returns an overview of user settings, privacy, security status, and blocked counts.
     */
    public function overview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $userSetting = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $privacySetting = $user->privacySettings()->firstOrCreate(['user_id' => $user->id]);
        $profile = $user->profile;

        $blockedIds = $this->privacyService->getBlockedUserIds($user->id);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'account' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender ?? $profile?->gender,
                    'birth_date' => $user->birth_date?->format('Y-m-d') ?? ($profile?->birth_date ? date('Y-m-d', strtotime((string) $profile->birth_date)) : null),
                    'avatar' => $profile?->avatar_url ?: '/images/default-avatar.svg',
                    'bio' => $profile?->bio,
                    'created_at' => $user->created_at?->toIso8601String(),
                ],
                'privacy' => [
                    'who_can_see_posts' => $userSetting->who_can_see_posts ?? 'public',
                    'who_can_send_friend_requests' => $userSetting->who_can_send_friend_requests ?? 'everyone',
                    'who_can_follow' => $userSetting->who_can_follow ?? 'everyone',
                    'who_can_message' => $userSetting->who_can_message ?? 'everyone',
                    'who_can_see_friends' => $userSetting->who_can_see_friends ?? 'public',
                    'is_profile_locked' => (bool) $userSetting->is_profile_locked,
                    'has_avatar_guard' => (bool) $userSetting->has_avatar_guard,
                    'show_online_status' => (bool) $privacySetting->show_online_status,
                    'search_engine_indexing' => (bool) $privacySetting->search_engine_indexing,
                    'dark_mode' => (bool) $userSetting->dark_mode,
                    'language' => $userSetting->language ?? 'bn',
                ],
                'security' => [
                    'two_factor_enabled' => (bool) $user->two_factor_enabled,
                    'two_factor_type' => $user->two_factor_type ?? 'totp',
                    'email_verified' => $user->hasVerifiedEmail(),
                    'phone_verified' => (bool) $user->phone_verified_at,
                ],
                'blocking' => [
                    'blocked_count' => count($blockedIds),
                ],
            ],
            'message' => 'সেটিংস বিবরণ সফলভাবে লোড হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/settings/account
     *
     * Returns account details for editing.
     */
    public function getAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->profile;

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender ?? $profile?->gender,
                'birth_date' => $user->birth_date?->format('Y-m-d') ?? ($profile?->birth_date ? date('Y-m-d', strtotime((string) $profile->birth_date)) : null),
                'bio' => $profile?->bio,
                'avatar' => $profile?->avatar_url ?: '/images/default-avatar.svg',
            ],
            'message' => 'অ্যাকাউন্টের তথ্য লোড হয়েছে।',
        ]);
    }

    /**
     * PUT /api/v1/settings/account
     *
     * Updates core account info: name, username, phone, gender, birth_date, bio.
     */
    public function updateAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'username' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:25'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'bio' => ['nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('username', $validated)) {
            $user->username = $validated['username'];
        }

        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }

        if (array_key_exists('gender', $validated)) {
            $user->gender = $validated['gender'];
        }

        if (array_key_exists('birth_date', $validated)) {
            $user->birth_date = $validated['birth_date'];
        }

        $user->save();

        // Sync with UserProfile if present or create one
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $profileUpdates = [];

        if (array_key_exists('name', $validated)) {
            $profileUpdates['display_name'] = $validated['name'];
        }

        if (array_key_exists('gender', $validated)) {
            $profileUpdates['gender'] = $validated['gender'];
        }

        if (array_key_exists('birth_date', $validated)) {
            $profileUpdates['birth_date'] = $validated['birth_date'];
        }

        if (array_key_exists('bio', $validated)) {
            $profileUpdates['bio'] = $validated['bio'];
        }

        if (! empty($profileUpdates)) {
            $profile->update($profileUpdates);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'bio' => $profile->bio,
            ],
            'message' => 'অ্যাকাউন্টের তথ্য সফলভাবে আপডেট হয়েছে।',
        ]);
    }

    /**
     * PUT /api/v1/settings/password
     *
     * Updates user password after verifying the current password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'বর্তমান পাসওয়ার্ডটি সঠিক নয়।',
                'errors' => [
                    'current_password' => ['বর্তমান পাসওয়ার্ডটি সঠিক নয়।'],
                ],
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/settings/privacy
     *
     * Returns all privacy options for the current user.
     */
    public function getPrivacy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $userSetting = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $privacySetting = $user->privacySettings()->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'who_can_see_posts' => $userSetting->who_can_see_posts ?? 'public',
                'who_can_send_friend_requests' => $userSetting->who_can_send_friend_requests ?? 'everyone',
                'who_can_follow' => $userSetting->who_can_follow ?? 'everyone',
                'who_can_message' => $userSetting->who_can_message ?? 'everyone',
                'who_can_see_friends' => $userSetting->who_can_see_friends ?? 'public',
                'story_visibility' => $userSetting->story_visibility ?? 'public',
                'is_profile_locked' => (bool) $userSetting->is_profile_locked,
                'has_avatar_guard' => (bool) $userSetting->has_avatar_guard,
                'dark_mode' => (bool) $userSetting->dark_mode,
                'language' => $userSetting->language ?? 'bn',
                'profile_visibility' => $privacySetting->profile_visibility ?? 'public',
                'search_engine_indexing' => (bool) $privacySetting->search_engine_indexing,
                'show_online_status' => (bool) $privacySetting->show_online_status,
                'read_receipts_enabled' => (bool) $privacySetting->read_receipts_enabled,
                'notification_email' => (bool) $userSetting->notification_email,
                'notification_push' => (bool) $userSetting->notification_push,
                'notification_sms' => (bool) $userSetting->notification_sms,
            ],
            'message' => 'প্রাইভেসি সেটিংস লোড হয়েছে।',
        ]);
    }

    /**
     * PUT /api/v1/settings/privacy
     *
     * Updates privacy and visibility options.
     */
    public function updatePrivacy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'who_can_see_posts' => ['nullable', 'string', 'in:public,friends,only_me'],
            'who_can_send_friend_requests' => ['nullable', 'string', 'in:everyone,friends_of_friends'],
            'who_can_follow' => ['nullable', 'string', 'in:everyone,friends'],
            'who_can_message' => ['nullable', 'string', 'in:everyone,friends'],
            'who_can_see_friends' => ['nullable', 'string', 'in:public,friends,only_me'],
            'story_visibility' => ['nullable', 'string', 'in:public,friends'],
            'is_profile_locked' => ['nullable', 'boolean'],
            'has_avatar_guard' => ['nullable', 'boolean'],
            'dark_mode' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string', 'in:bn,en'],
            'profile_visibility' => ['nullable', 'string', 'in:public,friends,private'],
            'search_engine_indexing' => ['nullable', 'boolean'],
            'show_online_status' => ['nullable', 'boolean'],
            'read_receipts_enabled' => ['nullable', 'boolean'],
            'notification_email' => ['nullable', 'boolean'],
            'notification_push' => ['nullable', 'boolean'],
            'notification_sms' => ['nullable', 'boolean'],
        ]);

        $userSetting = $user->settings()->firstOrCreate(['user_id' => $user->id]);
        $privacySetting = $user->privacySettings()->firstOrCreate(['user_id' => $user->id]);

        $userSettingFields = [
            'who_can_see_posts',
            'who_can_send_friend_requests',
            'who_can_follow',
            'who_can_message',
            'who_can_see_friends',
            'story_visibility',
            'is_profile_locked',
            'has_avatar_guard',
            'dark_mode',
            'language',
            'notification_email',
            'notification_push',
            'notification_sms',
        ];

        $privacySettingFields = [
            'profile_visibility',
            'search_engine_indexing',
            'show_online_status',
            'read_receipts_enabled',
        ];

        $userSettingUpdates = [];
        foreach ($userSettingFields as $field) {
            if (array_key_exists($field, $validated)) {
                $userSettingUpdates[$field] = $validated[$field];
            }
        }

        if (! empty($userSettingUpdates)) {
            $userSetting->update($userSettingUpdates);
        }

        $privacySettingUpdates = [];
        foreach ($privacySettingFields as $field) {
            if (array_key_exists($field, $validated)) {
                $privacySettingUpdates[$field] = $validated[$field];
            }
        }

        if (! empty($privacySettingUpdates)) {
            $privacySetting->update($privacySettingUpdates);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'who_can_see_posts' => $userSetting->who_can_see_posts ?? 'public',
                'who_can_send_friend_requests' => $userSetting->who_can_send_friend_requests ?? 'everyone',
                'who_can_follow' => $userSetting->who_can_follow ?? 'everyone',
                'who_can_message' => $userSetting->who_can_message ?? 'everyone',
                'who_can_see_friends' => $userSetting->who_can_see_friends ?? 'public',
                'story_visibility' => $userSetting->story_visibility ?? 'public',
                'is_profile_locked' => (bool) $userSetting->is_profile_locked,
                'has_avatar_guard' => (bool) $userSetting->has_avatar_guard,
                'dark_mode' => (bool) $userSetting->dark_mode,
                'language' => $userSetting->language ?? 'bn',
                'profile_visibility' => $privacySetting->profile_visibility ?? 'public',
                'search_engine_indexing' => (bool) $privacySetting->search_engine_indexing,
                'show_online_status' => (bool) $privacySetting->show_online_status,
                'read_receipts_enabled' => (bool) $privacySetting->read_receipts_enabled,
                'notification_email' => (bool) $userSetting->notification_email,
                'notification_push' => (bool) $userSetting->notification_push,
                'notification_sms' => (bool) $userSetting->notification_sms,
            ],
            'message' => 'প্রাইভেসি সেটিংস সফলভাবে আপডেট করা হয়েছে।',
        ]);
    }

    /**
     * GET /api/v1/settings/blocking
     *
     * Returns list of blocked users.
     */
    public function getBlockedUsers(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        // 1. Fetch blocked friends from Friendship table
        $blockedFriendIds = Friendship::where('user_id', $actor->id)
            ->where('status', Friendship::STATUS_BLOCKED)
            ->pluck('friend_id')
            ->toArray();

        // 2. Fetch from BlockedUser table
        $blockedUserIds = BlockedUser::where('user_id', $actor->id)
            ->active()
            ->whereNotNull('identifier')
            ->pluck('identifier')
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter()
            ->toArray();

        $allBlockedIds = array_values(array_unique(array_filter(array_merge($blockedFriendIds, $blockedUserIds))));

        $blockedUsers = User::whereIn('id', $allBlockedIds)
            ->with('profile')
            ->get()
            ->map(function (User $u): array {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar' => $u->profile?->avatar_url ?: '/images/default-avatar.svg',
                    'is_verified' => (bool) $u->is_verified,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'blocked_users' => $blockedUsers,
                'total' => count($blockedUsers),
            ],
            'blocked_users' => $blockedUsers,
            'message' => 'ব্লককৃত ব্যবহারকারীদের তালিকা লোড হয়েছে।',
        ]);
    }

    /**
     * POST /api/v1/settings/blocking/{userId}
     *
     * Block a user.
     */
    public function blockUser(Request $request, int $userId): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->id === $userId) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'নিজের অ্যাকাউন্ট ব্লক করা সম্ভব নয়।',
            ], 422);
        }

        $targetUser = User::find($userId);
        if (! $targetUser) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'ব্যবহারকারী পাওয়া যায়নি।',
            ], 404);
        }

        $this->friendshipService->blockUser($actor, $targetUser->id);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'username' => $targetUser->username,
            ],
            'message' => "{$targetUser->name} কে সফলভাবে ব্লক করা হয়েছে।",
        ]);
    }

    /**
     * DELETE /api/v1/settings/blocking/{userId}
     *
     * Unblock a user.
     */
    public function unblockUser(Request $request, int $userId): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $targetUser = User::find($userId);
        $targetName = $targetUser?->name ?? 'ব্যবহারকারী';

        $this->friendshipService->unblockUser($actor, $userId);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => "{$targetName} কে সফলভাবে আনব্লক করা হয়েছে।",
        ]);
    }

    /**
     * POST /api/v1/settings/2fa/toggle
     *
     * Quick toggle for two-factor authentication.
     */
    public function toggleTwoFactor(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['enabled'];

        $user->two_factor_enabled = $enabled;
        if ($enabled && empty($user->two_factor_confirmed_at)) {
            $user->two_factor_confirmed_at = now();
        }
        $user->save();

        UserTwoFactor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'is_enabled' => $enabled,
                'confirmed_at' => $enabled ? now() : null,
                'type' => $user->two_factor_type ?? 'totp',
            ]
        );

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'two_factor_enabled' => $enabled,
            ],
            'message' => $enabled
                ? 'টু-ফ্যাক্টর অথেনটিকেশন সক্রিয় করা হয়েছে।'
                : 'টু-ফ্যাক্টর অথেনটিকেশন নিষ্ক্রিয় করা হয়েছে।',
        ]);
    }
}
