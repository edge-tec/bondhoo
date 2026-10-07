<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ReorderSkillRequest;
use App\Http\Requests\Profile\StoreInterestRequest;
use App\Http\Requests\Profile\StoreLanguageRequest;
use App\Http\Requests\Profile\StoreSkillRequest;
use App\Http\Requests\Profile\UpdateLanguageRequest;
use App\Models\ProfileInterest;
use App\Models\ProfileLanguage;
use App\Models\ProfileSkill;
use App\Models\User;
use App\Services\ProfileTaxonomyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileTaxonomyV2Controller extends Controller
{
    public function __construct(
        protected ProfileTaxonomyService $taxonomyService
    ) {}

    // ==========================================
    // SKILLS ENDPOINTS
    // ==========================================

    public function indexSkills(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $skills = $this->taxonomyService->getSkills($user);

        return $this->successResponse(
            data: $skills,
            message: 'দক্ষতার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    public function userSkills(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $skills = $this->taxonomyService->getSkills($targetUser, $viewer);

        return $this->successResponse(
            data: $skills,
            message: 'ব্যবহারকারীর দক্ষতার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    public function storeSkill(StoreSkillRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $skill = $this->taxonomyService->addSkill(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->taxonomyService->formatSkillItem($skill),
            message: 'নতুন দক্ষতা সফলভাবে যুক্ত করা হয়েছে।',
            statusCode: 201
        );
    }

    public function destroySkill(Request $request, int $id): JsonResponse
    {
        /** @var ProfileSkill $skill */
        $skill = ProfileSkill::findOrFail($id);
        Gate::authorize('delete', $skill);

        $this->taxonomyService->removeSkill(
            skill: $skill,
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: null,
            message: 'দক্ষতা সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    public function reorderSkills(ReorderSkillRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reordered = $this->taxonomyService->reorderSkills(
            targetUser: $user,
            orders: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $reordered,
            message: 'দক্ষতার ক্রম সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }

    public function searchSkills(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $limit = min(50, max(1, (int) $request->input('limit', 20)));

        $results = $this->taxonomyService->searchSkills($query, $limit);

        return $this->successResponse(
            data: $results,
            message: 'দক্ষতা অনুসন্ধানের ফলাফল সফলভাবে লোড হয়েছে।'
        );
    }

    // ==========================================
    // INTERESTS ENDPOINTS
    // ==========================================

    public function indexInterests(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $interests = $this->taxonomyService->getInterests($user);

        return $this->successResponse(
            data: $interests,
            message: 'আগ্রহের তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    public function userInterests(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $interests = $this->taxonomyService->getInterests($targetUser, $viewer);

        return $this->successResponse(
            data: $interests,
            message: 'ব্যবহারকারীর আগ্রহের তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    public function storeInterest(StoreInterestRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $interest = $this->taxonomyService->addInterest(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->taxonomyService->formatInterestItem($interest),
            message: 'নতুন আগ্রহ সফলভাবে যুক্ত করা হয়েছে।',
            statusCode: 201
        );
    }

    public function destroyInterest(Request $request, int $id): JsonResponse
    {
        /** @var ProfileInterest $interest */
        $interest = ProfileInterest::findOrFail($id);
        Gate::authorize('delete', $interest);

        $this->taxonomyService->removeInterest(
            interest: $interest,
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: null,
            message: 'আগ্রহ সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    public function searchInterests(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $limit = min(50, max(1, (int) $request->input('limit', 20)));

        $results = $this->taxonomyService->searchInterests($query, $limit);

        return $this->successResponse(
            data: $results,
            message: 'আগ্রহ অনুসন্ধানের ফলাফল সফলভাবে লোড হয়েছে।'
        );
    }

    // ==========================================
    // LANGUAGES ENDPOINTS
    // ==========================================

    public function indexLanguages(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $languages = $this->taxonomyService->getLanguages($user);

        return $this->successResponse(
            data: $languages,
            message: 'ভাষার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    public function userLanguages(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();
        $languages = $this->taxonomyService->getLanguages($targetUser, $viewer);

        return $this->successResponse(
            data: $languages,
            message: 'ব্যবহারকারীর ভাষার তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    public function storeLanguage(StoreLanguageRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $language = $this->taxonomyService->addLanguage(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->taxonomyService->formatLanguageItem($language),
            message: 'নতুন ভাষা সফলভাবে যুক্ত করা হয়েছে।',
            statusCode: 201
        );
    }

    public function updateLanguage(UpdateLanguageRequest $request, int $id): JsonResponse
    {
        /** @var ProfileLanguage $language */
        $language = ProfileLanguage::findOrFail($id);
        Gate::authorize('update', $language);

        $updated = $this->taxonomyService->updateLanguage(
            language: $language,
            data: $request->validated(),
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->taxonomyService->formatLanguageItem($updated),
            message: 'ভাষার পারদর্শিতা সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    public function destroyLanguage(Request $request, int $id): JsonResponse
    {
        /** @var ProfileLanguage $language */
        $language = ProfileLanguage::findOrFail($id);
        Gate::authorize('delete', $language);

        $this->taxonomyService->removeLanguage(
            language: $language,
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: null,
            message: 'ভাষা সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    public function searchLanguages(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        $limit = min(50, max(1, (int) $request->input('limit', 20)));

        $results = $this->taxonomyService->searchLanguages($query, $limit);

        return $this->successResponse(
            data: $results,
            message: 'ভাষা অনুসন্ধানের ফলাফল সফলভাবে লোড হয়েছে।'
        );
    }
}
