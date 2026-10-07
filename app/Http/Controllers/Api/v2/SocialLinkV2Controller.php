<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ReorderSocialLinkRequest;
use App\Http\Requests\Profile\StoreSocialLinkRequest;
use App\Http\Requests\Profile\UpdateSocialLinkRequest;
use App\Models\ProfileSocialLink;
use App\Models\User;
use App\Services\SocialLink\SocialPlatformRegistry;
use App\Services\SocialLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SocialLinkV2Controller extends Controller
{
    public function __construct(
        protected SocialLinkService $socialLinkService
    ) {}

    /**
     * GET /api/v2/profile/social-links
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $links = $this->socialLinkService->getSocialLinks($user, $user);

        return $this->successResponse(
            data: $links,
            message: 'সোশ্যাল প্রোফাইল লিঙ্ক তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/{username}/social-links
     */
    public function userLinks(Request $request, string $username): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $targetUser = User::where('username', strtolower($username))->firstOrFail();

        $links = $this->socialLinkService->getSocialLinks($targetUser, $viewer);

        return $this->successResponse(
            data: $links,
            message: 'ব্যবহারকারীর সোশ্যাল প্রোফাইল লিঙ্ক তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/social-links
     */
    public function store(StoreSocialLinkRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $link = $this->socialLinkService->addSocialLink(
            targetUser: $user,
            data: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->socialLinkService->formatSocialLinkItem($link),
            message: 'সোশ্যাল লিঙ্ক সফলভাবে যুক্ত করা হয়েছে।',
            statusCode: 201
        );
    }

    /**
     * PUT /api/v2/profile/social-links/{id}
     */
    public function update(UpdateSocialLinkRequest $request, int $id): JsonResponse
    {
        /** @var ProfileSocialLink $link */
        $link = ProfileSocialLink::findOrFail($id);
        Gate::authorize('update', $link);

        $updated = $this->socialLinkService->updateSocialLink(
            link: $link,
            data: $request->validated(),
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $this->socialLinkService->formatSocialLinkItem($updated),
            message: 'সোশ্যাল লিঙ্ক সফলভাবে আপডেট করা হয়েছে।'
        );
    }

    /**
     * DELETE /api/v2/profile/social-links/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var ProfileSocialLink $link */
        $link = ProfileSocialLink::findOrFail($id);
        Gate::authorize('delete', $link);

        $this->socialLinkService->deleteSocialLink(
            link: $link,
            actor: $request->user(),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: null,
            message: 'সোশ্যাল লিঙ্ক সফলভাবে মুছে ফেলা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/social-links/reorder
     */
    public function reorder(ReorderSocialLinkRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $reordered = $this->socialLinkService->reorderSocialLinks(
            targetUser: $user,
            orders: $request->validated(),
            actor: $user,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $reordered,
            message: 'সোশ্যাল লিঙ্কের ক্রম সফলভাবে সংরক্ষিত হয়েছে।'
        );
    }

    /**
     * GET /api/v2/profile/social-links/platforms
     */
    public function platforms(): JsonResponse
    {
        return $this->successResponse(
            data: array_values(SocialPlatformRegistry::getSupportedPlatforms()),
            message: 'সমর্থিত সোশ্যাল প্ল্যাটফর্মের তালিকা।'
        );
    }
}
