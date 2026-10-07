<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Page\EnterprisePageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * পেজ টিম ও আরব্যাক এপিআই কন্ট্রোলার:
 * মেম্বার ইনভাইট, অ্যাকসেপ্ট, রোল অ্যাসাইন, কাস্টম পারমিশন ওভাররাইড এবং রিমুভ পরিচালনা করে।
 */
class PageTeamV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * পেজের বর্তমান টিম মেম্বারদের তালিকা
     */
    public function index(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageTeam', $page)) {
            abort(403, 'You do not have permission to view this page team.');
        }

        $members = $page->members()->with(['user.profile', 'inviter'])->get();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * নতুন টিম মেম্বারকে আমন্ত্রণ জানানো
     */
    public function invite(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageTeam', $page)) {
            abort(403, 'You do not have permission to invite team members.');
        }

        $validated = $request->validate([
            'identifier' => ['nullable', 'string'],
            'email_or_username' => ['nullable', 'string'],
            'role' => ['required', 'string', 'in:admin,manager,content_manager,moderator,analyst,editor,viewer'],
            'custom_permissions' => ['nullable', 'array'],
        ]);

        $identifier = $validated['identifier'] ?? $validated['email_or_username'] ?? null;
        if (! $identifier) {
            return response()->json(['success' => false, 'message' => 'The identifier or email field is required.'], 422);
        }

        try {
            $member = $this->enterprisePageService->inviteTeamMember(
                $page,
                $user,
                $identifier,
                $validated['role'],
                $validated['custom_permissions'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Team member invited successfully.',
                'data' => $member->load('user'),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * আমন্ত্রণ গ্রহণ করা (ইনভাইটেড ইউজার কর্তৃক)
     */
    public function acceptInvite(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $member = $this->enterprisePageService->acceptInvitation($validated['token'], $user);

            return response()->json([
                'success' => true,
                'message' => 'You have joined the page team successfully.',
                'data' => $member,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * টিম মেম্বারের রোল বা পারমিশন পরিবর্তন
     */
    public function updateRole(Request $request, Page $page, int $memberId): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageTeam', $page)) {
            abort(403, 'You do not have permission to update team roles.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,manager,content_manager,moderator,analyst,viewer'],
            'custom_permissions' => ['nullable', 'array'],
        ]);

        try {
            $member = $this->enterprisePageService->updateMemberRole(
                $page,
                $user,
                $memberId,
                $validated['role'],
                $validated['custom_permissions'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Member role updated successfully.',
                'data' => $member,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * টিম থেকে মেম্বার বহিষ্কার / অপসারণ
     */
    public function remove(Request $request, Page $page, int $memberId): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('manageTeam', $page)) {
            abort(403, 'You do not have permission to remove team members.');
        }

        try {
            $this->enterprisePageService->removeMember($page, $user, $memberId);

            return response()->json([
                'success' => true,
                'message' => 'Team member removed successfully.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
