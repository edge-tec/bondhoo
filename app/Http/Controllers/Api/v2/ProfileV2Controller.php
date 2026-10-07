<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CropAvatarRequest;
use App\Http\Requests\Profile\CropCoverRequest;
use App\Http\Requests\Profile\UpdateBioAboutRequest;
use App\Http\Requests\Profile\UpdateCompleteProfileRequest;
use App\Http\Requests\Profile\UpdatePersonalInfoRequest;
use App\Http\Requests\Profile\UpdateUsernameRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Requests\Profile\UploadCoverRequest;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Report;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\BioAboutService;
use App\Services\CoverService;
use App\Services\FriendshipService;
use App\Services\PersonalInfoService;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileCompletionService;
use App\Services\ProfileEnterpriseService;
use App\Services\ProfileService;
use App\Services\QrCodeService;
use App\Services\UsernameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileV2Controller extends Controller
{
    public function __construct(
        protected ProfileService $profileService,
        protected AvatarService $avatarService,
        protected CoverService $coverService,
        protected PersonalInfoService $personalInfoService,
        protected BioAboutService $bioAboutService,
        protected UsernameService $usernameService,
        protected ProfileCompletionService $completionService,
        protected ProfileAnalyticsService $analyticsService,
        protected QrCodeService $qrCodeService,
        protected FriendshipService $friendshipService
    ) {}

    /**
     * GET /api/v2/profile/analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $timeframe = (string) $request->input('timeframe', '30d');
        $analytics = $this->analyticsService->getDashboardAnalytics($user, $timeframe);

        return $this->successResponse(
            data: $analytics,
            message: 'প্রোফাইল অ্যানালিটিক্স তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/{username}/view
     */
    public function recordView(Request $request, string $username): JsonResponse
    {
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $viewer = $request->user('sanctum');

        $recorded = $this->analyticsService->recordView($targetUser, $viewer, $request);

        return $this->successResponse(
            data: ['recorded' => $recorded],
            message: $recorded ? 'প্রোফাইল ভিউ সফলভাবে ট্র্যাক করা হয়েছে।' : 'ভিউ ইতিপূর্বে রেকর্ড করা হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/completion
     */
    public function completion(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $completion = $this->completionService->getCompletion($user);

        return $this->successResponse(
            data: $completion,
            message: 'প্রোফাইল সম্পূর্ণতার তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}
     */
    public function show(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $profile = $this->profileService->getProfileByUsername($username, $viewer);

        return $this->successResponse(
            data: $profile,
            message: 'প্রোফাইল তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile
     * PUT /api/v2/profile/{user}
     */
    public function update(UpdateCompleteProfileRequest $request, ?User $user = null): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = $user ?? $actor;

        $updated = $this->profileService->updateCompleteProfile(
            targetUser: $targetUser,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $updated,
            message: 'প্রোফাইল সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/avatar
     */
    public function updateAvatar(UploadAvatarRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $crop = null;
        if ($request->filled('crop_width') && $request->filled('crop_height')) {
            $crop = [
                'x' => (int) $request->input('crop_x', 0),
                'y' => (int) $request->input('crop_y', 0),
                'width' => (int) $request->input('crop_width'),
                'height' => (int) $request->input('crop_height'),
            ];
        }

        $result = $this->avatarService->uploadAvatar(
            user: $user,
            file: $request->file('file'),
            crop: $crop,
            caption: $request->input('caption')
        );

        return $this->successResponse(
            data: $result,
            message: 'প্রোফাইল ছবি সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * DELETE /api/v2/profile/avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->avatarService->deleteAvatar($user);

        return $this->successResponse(
            data: $result,
            message: 'প্রোফাইল ছবি সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/avatar/default
     */
    public function restoreDefaultAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->avatarService->restoreDefaultAvatar($user);

        return $this->successResponse(
            data: $result,
            message: 'ডিফল্ট প্রোফাইল ছবি সফলভাবে পুনরুদ্ধার করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/avatar/crop
     */
    public function cropAvatar(CropAvatarRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->avatarService->cropAvatar(
            user: $user,
            x: (int) $request->input('crop_x', 0),
            y: (int) $request->input('crop_y', 0),
            width: (int) $request->input('crop_width'),
            height: (int) $request->input('crop_height')
        );

        return $this->successResponse(
            data: $result,
            message: 'প্রোফাইল ছবি সফলভাবে ক্রপ করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/cover
     */
    public function updateCover(UploadCoverRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $crop = null;
        if ($request->filled('crop_width') && $request->filled('crop_height')) {
            $crop = [
                'x' => (int) $request->input('crop_x', 0),
                'y' => (int) $request->input('crop_y', 0),
                'width' => (int) $request->input('crop_width'),
                'height' => (int) $request->input('crop_height'),
            ];
        }

        $result = $this->coverService->uploadCover(
            user: $user,
            file: $request->file('file'),
            crop: $crop,
            caption: $request->input('caption'),
            coverPositionY: $request->filled('cover_position_y') ? (int) $request->input('cover_position_y') : null
        );

        return $this->successResponse(
            data: $result,
            message: 'কভার ছবি সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * DELETE /api/v2/profile/cover
     */
    public function deleteCover(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->coverService->deleteCover($user);

        return $this->successResponse(
            data: $result,
            message: 'কভার ছবি সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/cover/default
     */
    public function restoreDefaultCover(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->coverService->restoreDefaultCover($user);

        return $this->successResponse(
            data: $result,
            message: 'ডিফল্ট কভার ছবি সফলভাবে পুনরুদ্ধার করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/cover/crop
     */
    public function cropCover(CropCoverRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $this->coverService->cropCover(
            user: $user,
            x: (int) $request->input('crop_x', 0),
            y: (int) $request->input('crop_y', 0),
            width: (int) $request->input('crop_width'),
            height: (int) $request->input('crop_height')
        );

        return $this->successResponse(
            data: $result,
            message: 'কভার ছবি সফলভাবে ক্রপ করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/lock
     */
    public function toggleLock(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $isLocked = $this->profileService->toggleProfileLock($user);

        return $this->successResponse(
            data: ['is_profile_locked' => $isLocked],
            message: $isLocked ? 'প্রোফাইল সফলভাবে লক করা হয়েছে।' : 'প্রোফাইল আনলক করা হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/friends
     */
    public function friends(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $perPage = (int) $request->query('per_page', 20);

        $friends = $this->profileService->getUserFriends($targetUser, $viewer, $perPage);

        return $this->successResponse($friends);
    }

    /**
     * GET /api/v2/profile/{username}/timeline
     */
    public function timeline(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $perPage = (int) $request->query('per_page', 15);

        $timeline = $this->profileService->getUserTimeline($targetUser, $viewer, $perPage);

        return $this->successResponse(
            data: $timeline,
            message: 'টাইমলাইন পোস্ট লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/photos
     */
    public function photos(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $limit = (int) $request->query('limit', 12);

        $photos = $this->profileService->getUserPhotos($targetUser, $viewer, $limit);

        return $this->successResponse(
            data: $photos,
            message: 'ফটো গ্যালারি লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/videos
     */
    public function videos(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $limit = (int) $request->query('limit', 12);

        $videos = $this->profileService->getUserVideos($targetUser, $viewer, $limit);

        return $this->successResponse(
            data: $videos,
            message: 'ভিডিও গ্যালারি লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/about
     */
    public function about(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $aboutData = $this->profileService->getAbout($targetUser, $viewer);

        return $this->successResponse(
            data: $aboutData,
            message: 'অ্যাবাউট তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/cover/position
     */
    public function repositionCover(Request $request): JsonResponse
    {
        $request->validate([
            'position_y' => ['required', 'integer', 'between:0,100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $newPosition = $this->profileService->updateCoverPosition($user, (int) $request->input('position_y'));

        return $this->successResponse(
            data: ['cover_position_y' => $newPosition],
            message: 'কভার ছবির পজিশন সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/personal
     */
    public function getPersonalInfo(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $info = $this->personalInfoService->getPersonalInfo($targetUser, $viewer);

        return $this->successResponse(
            data: $info,
            message: 'ব্যক্তিগত তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile/personal
     * PUT /api/v2/profile/personal/{user}
     */
    public function updatePersonalInfo(UpdatePersonalInfoRequest $request, ?User $user = null): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = $user ?? $actor;

        $updated = $this->personalInfoService->updatePersonalInfo(
            targetUser: $targetUser,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $updated,
            message: 'ব্যক্তিগত তথ্য ও গোপনীয়তা সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/privacy/settings
     */
    public function getPrivacySettings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $privacy = $user->privacySettings()->firstOrCreate(['user_id' => $user->id]);

        return $this->successResponse(
            data: $privacy,
            message: 'গোপনীয়তা সেটিংস লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile/privacy/settings
     */
    public function updatePrivacySettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_visibility' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'avatar_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'cover_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'bio_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'about_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'location_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'education_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'work_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'skills_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'interests_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'languages_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'social_links_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'first_name_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'last_name_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'gender_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'dob_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'dob_display_format' => ['nullable', 'string', 'in:full,month_day,age,hidden'],
            'country_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'city_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'address_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'phone_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'email_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'phone_visibility' => ['nullable', 'string', 'in:everyone,friends,only_me,public,followers'],
            'email_visibility' => ['nullable', 'string', 'in:everyone,friends,only_me,public,followers'],
            'post_default_privacy' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'friends_list_visibility' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'who_can_send_friend_requests' => ['nullable', 'string', 'in:everyone,friends_of_friends,nobody,public,friends'],
            'who_can_follow_me' => ['nullable', 'string', 'in:everyone,friends,nobody,public'],
            'who_can_message_me' => ['nullable', 'string', 'in:everyone,friends,nobody,public'],
            'show_online_status' => ['nullable', 'boolean'],
            'read_receipts_enabled' => ['nullable', 'boolean'],
            'mutual_friends_visibility' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'birthday_visibility' => ['nullable', 'string', 'in:public,followers,friends,only_me'],
            'search_engine_indexing' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $privacy = $user->privacySettings()->firstOrCreate(['user_id' => $user->id]);
        $privacy->update($validated);

        $this->profileService->invalidateProfileCache($user);

        return $this->successResponse(
            data: $privacy->fresh(),
            message: 'গোপনীয়তা সেটিংস সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile
     */
    public function currentProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $this->profileService->getProfileByUsername($user->username, $user);

        return $this->successResponse(
            data: $profile,
            message: 'প্রোফাইল তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/about
     */
    public function currentAbout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $aboutData = $this->bioAboutService->getBioAbout($user, $user);

        return $this->successResponse(
            data: $aboutData,
            message: 'বায়ো ও পরিচিতি তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile/about
     * PUT /api/v2/profile/about/{user}
     */
    public function updateAbout(UpdateBioAboutRequest $request, ?User $user = null): JsonResponse
    {
        $actor = $request->user();
        $targetUser = $user ?? $actor;

        $updated = $this->bioAboutService->updateBioAbout(
            targetUser: $targetUser,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $updated,
            message: 'বায়ো ও পরিচিতি তথ্য সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/username/check
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = (string) $request->query('username', '');
        $currentUser = $request->user('sanctum');

        $result = $this->usernameService->checkAvailability($username, $currentUser);

        return $this->successResponse(
            data: $result,
            message: $result['message']
        );
    }

    /**
     * PUT /api/v2/profile/username
     * PUT /api/v2/profile/username/{user}
     */
    public function updateUsername(UpdateUsernameRequest $request, ?User $user = null): JsonResponse
    {
        $actor = $request->user();
        $targetUser = $user ?? $actor;

        $result = $this->usernameService->changeUsername(
            targetUser: $targetUser,
            newUsername: (string) $request->input('username'),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $result,
            message: 'ইউজারনেম সফলভাবে পরিবর্তন করা হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/export
     *
     * Export all user profile data as JSON archive (Requirement 30).
     */
    public function exportData(Request $request): JsonResponse|Response
    {
        /** @var User $user */
        $user = $request->user();
        $exportData = $this->profileService->exportUserData($user);

        if ($request->query('download') === '1' || $request->header('Accept') === 'application/octet-stream') {
            $filename = sprintf('jugajug_export_%s_%s.json', $user->username, date('Y-m-d_His'));

            return response(json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        return $this->successResponse(
            data: $exportData,
            message: 'প্রোফাইল ডেটা সফলভাবে এক্সপোর্ট করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/deactivate
     *
     * Deactivate user account temporarily (Requirement 29).
     */
    public function deactivateAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->filled('password') && ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['প্রদত্ত পাসওয়ার্ডটি সঠিক নয়।'],
            ]);
        }

        $reason = (string) $request->input('reason', 'User requested deactivation');
        $this->profileService->deactivateUser($user, $reason);

        // Revoke tokens and logout
        $user->tokens()->delete();

        return $this->successResponse(
            data: ['status' => 'deactivated'],
            message: 'আপনার অ্যাকাউন্ট সফলভাবে ডিঅ্যাক্টিভেট করা হয়েছে। পরবর্তীতে লগইন করে এটি সক্রিয় করতে পারবেন।'
        );
    }

    /**
     * POST /api/v2/profile/delete
     *
     * Soft delete user account with confirmation (Requirement 29).
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:DELETE'],
        ], [
            'confirmation.in' => 'নিশ্চিতকরণের জন্য DELETE শব্দটি সঠিকভাবে লিখুন।',
            'password.required' => 'অ্যাকাউন্ট ডিলিট করতে আপনার পাসওয়ার্ড প্রয়োজন।',
        ]);

        if (! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['প্রদত্ত পাসওয়ার্ডটি সঠিক নয়।'],
            ]);
        }

        $reason = (string) $request->input('reason', 'User permanent deletion request');
        $this->profileService->deleteUserAccount($user, $reason);

        // Invalidate tokens and session
        $user->tokens()->delete();

        return $this->successResponse(
            data: ['status' => 'deleted'],
            message: 'আপনার অ্যাকাউন্ট সফলভাবে ডিলিট করা হয়েছে। গ্রেস পিরিয়ডের মধ্যে চাইলে যোগাযোগ করতে পারেন।'
        );
    }

    /**
     * GET /api/v2/profile/notifications/settings
     *
     * Get user profile notification preferences (Requirement 20).
     */
    public function getNotificationSettings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $settings = $user->notificationSettings()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'email_notifications' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
                'friend_request_alerts' => true,
                'comment_alerts' => true,
                'mention_alerts' => true,
                'security_alerts' => true,
            ]
        );

        return $this->successResponse(
            data: $settings,
            message: 'নোটিফিকেশন সেটিংস সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * PUT /api/v2/profile/notifications/settings
     *
     * Update user profile notification preferences (Requirement 20).
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'email_notifications' => ['nullable', 'boolean'],
            'sms_notifications' => ['nullable', 'boolean'],
            'push_notifications' => ['nullable', 'boolean'],
            'friend_request_alerts' => ['nullable', 'boolean'],
            'friend_accepted_alerts' => ['nullable', 'boolean'],
            'comment_alerts' => ['nullable', 'boolean'],
            'mention_alerts' => ['nullable', 'boolean'],
            'message_alerts' => ['nullable', 'boolean'],
            'like_alerts' => ['nullable', 'boolean'],
            'share_alerts' => ['nullable', 'boolean'],
            'follower_alerts' => ['nullable', 'boolean'],
            'page_alerts' => ['nullable', 'boolean'],
            'group_alerts' => ['nullable', 'boolean'],
            'security_alerts' => ['nullable', 'boolean'],
        ]);

        $settings = $user->notificationSettings()->firstOrCreate(['user_id' => $user->id]);
        $settings->update($validated);

        return $this->successResponse(
            data: $settings->fresh(),
            message: 'নোটিফিকেশন পছন্দসমূহ সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/qrcode
     *
     * Generate self-hosted dynamic SVG QR Code for user profile (Requirement 13).
     */
    public function getQrCode(Request $request, string $username): Response
    {
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $profileUrl = route('profile.show', ['username' => $targetUser->username]);

        $size = (int) $request->input('size', 250);
        $size = max(100, min(800, $size));

        $svg = $this->qrCodeService->generateSvg($profileUrl, $size);

        if ($request->query('download') === '1') {
            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml',
                'Content-Disposition' => "attachment; filename=\"qr_{$targetUser->username}.svg\"",
            ]);
        }

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * POST /api/v2/profile/{username}/block
     *
     * Block a user profile (Requirement 19).
     */
    public function blockUser(Request $request, string $username): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        if ($actor->id === $targetUser->id) {
            return $this->errorResponse('আপনি নিজেকে ব্লক করতে পারবেন না।', 422);
        }

        $friendship = $this->friendshipService->blockUser($actor, $targetUser->id);

        $this->profileService->invalidateProfileCache($actor);
        $this->profileService->invalidateProfileCache($targetUser);
        $this->profileService->invalidateRelationshipCache($actor->id, $targetUser->id);

        return $this->successResponse(
            data: ['blocked' => true, 'target_id' => $targetUser->id],
            message: "ব্যবহারকারী @{$targetUser->username} কে সফলভাবে ব্লক করা হয়েছে।"
        );
    }

    /**
     * POST /api/v2/profile/{username}/unblock
     *
     * Unblock a user profile (Requirement 19).
     */
    public function unblockUser(Request $request, string $username): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $unblocked = $this->friendshipService->unblockUser($actor, $targetUser->id);

        $this->profileService->invalidateProfileCache($actor);
        $this->profileService->invalidateProfileCache($targetUser);
        $this->profileService->invalidateRelationshipCache($actor->id, $targetUser->id);

        return $this->successResponse(
            data: ['unblocked' => $unblocked, 'target_id' => $targetUser->id],
            message: "ব্যবহারকারী @{$targetUser->username} কে সফলভাবে আনব্লক করা হয়েছে।"
        );
    }

    /**
     * POST /api/v2/profile/{username}/restrict
     *
     * Toggle restricted status for a friend (Requirement 19).
     */
    public function restrictUser(Request $request, string $username): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $enterpriseService = app(ProfileEnterpriseService::class);
        $isRestricted = $enterpriseService->toggleRestrictedFriend($actor, $targetUser->id);

        return $this->successResponse(
            data: ['is_restricted' => $isRestricted, 'target_id' => $targetUser->id],
            message: $isRestricted ? "ব্যবহারকারী @{$targetUser->username} কে রেস্ট্রিক্ট করা হয়েছে।" : "ব্যবহারকারী @{$targetUser->username} এর রেস্ট্রিকশন তুলে নেওয়া হয়েছে।"
        );
    }

    /**
     * POST /api/v2/profile/{username}/report
     *
     * Report a user profile (Requirement 19).
     */
    public function reportUser(Request $request, string $username): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,harassment,hate_speech,false_information,violence,impersonation,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = Report::create([
            'reporter_id' => $actor->id,
            'reportable_type' => User::class,
            'reportable_id' => $targetUser->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return $this->successResponse(
            data: $report,
            message: 'প্রোফাইল রিপোর্ট সফলভাবে জমা দেওয়া হয়েছে। আমাদের মডারেশন টিম এটি পর্যালোচনা করবে।',
            statusCode: 201
        );
    }

    /**
     * GET /api/v2/profile/blocked-users
     *
     * List all users blocked by the authenticated user.
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
            ->where('type', 'user')
            ->pluck('identifier')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $allBlockedIds = array_values(array_unique(array_filter(array_merge($blockedFriendIds, $blockedUserIds))));

        $blockedUsers = User::whereIn('id', $allBlockedIds)
            ->with('profile')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'avatar' => $u->profile?->avatar_url ?: '/images/default-avatar.svg',
                    'is_verified' => (bool) $u->is_verified,
                ];
            });

        return $this->successResponse(
            data: $blockedUsers,
            message: 'ব্লক তালিকা সফলভাবে লোড হয়েছে।'
        );
    }
}
