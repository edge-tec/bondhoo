<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateBioAboutRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateSettingsRequest;
use App\Http\Requests\Profile\UpdateUsernameRequest;
use App\Services\BioAboutService;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileCompletionService;
use App\Services\ProfileService;
use App\Services\UsernameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService,
        protected BioAboutService $bioAboutService,
        protected UsernameService $usernameService,
        protected ProfileCompletionService $completionService,
        protected ProfileAnalyticsService $analyticsService
    ) {}

    public function analytics(Request $request): JsonResponse
    {
        $user = $request->user();
        $timeframe = (string) $request->input('timeframe', '30d');
        $analytics = $this->analyticsService->getDashboardAnalytics($user, $timeframe);

        return $this->successResponse(
            data: $analytics,
            message: 'Profile analytics retrieved successfully.'
        );
    }

    public function completion(Request $request): JsonResponse
    {
        $user = $request->user();
        $completion = $this->completionService->getCompletion($user);

        return $this->successResponse(
            data: $completion,
            message: 'Profile completion details retrieved successfully.'
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profileData = $this->profileService->getProfileByUsername($user->username, $user);

        return $this->successResponse(
            data: $profileData,
            message: 'User profile retrieved.'
        );
    }

    public function show(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $profileData = $this->profileService->getProfileByUsername($username, $viewer);

        return $this->successResponse(
            data: $profileData,
            message: 'User profile retrieved.'
        );
    }

    public function myAbout(Request $request): JsonResponse
    {
        $user = $request->user();
        $aboutData = $this->bioAboutService->getBioAbout($user, $user);

        return $this->successResponse(
            data: $aboutData,
            message: 'User bio and about retrieved.'
        );
    }

    public function updateAbout(UpdateBioAboutRequest $request): JsonResponse
    {
        $user = $request->user();
        $updated = $this->bioAboutService->updateBioAbout(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $updated,
            message: 'Bio and about updated successfully.'
        );
    }

    public function updateUsername(UpdateUsernameRequest $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->usernameService->changeUsername(
            targetUser: $user,
            newUsername: (string) $request->input('username'),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $result,
            message: 'Username updated successfully.'
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = $this->profileService->updateProfile(
            user: $user,
            data: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $profile,
            message: 'Profile updated successfully.'
        );
    }

    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $user = $request->user();
        $settings = $this->profileService->updateSettings(
            user: $user,
            data: $request->validated(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $settings,
            message: 'Settings updated successfully.'
        );
    }
}
