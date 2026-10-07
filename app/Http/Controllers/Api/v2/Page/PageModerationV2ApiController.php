<?php

namespace App\Http\Controllers\Api\v2\Page;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBlockedUser;
use App\Services\Page\EnterprisePageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * এন্টারপ্রাইজ পেজ মডারেশন সেন্টার কন্ট্রোলার:
 * ইউজার ব্লকিং/আনব্লকিং, কমেন্ট মডারেশন, কিওয়ার্ড ফিল্টার এবং বাল্ক অ্যাকশন।
 */
class PageModerationV2ApiController extends Controller
{
    public function __construct(
        protected EnterprisePageService $enterprisePageService
    ) {}

    /**
     * পেজের ব্লক করা ইউজারদের তালিকা
     */
    public function blockedUsers(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('moderate', $page)) {
            abort(403, 'You do not have permission to view blocked users for this page.');
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $blocked = PageBlockedUser::where('page_id', $page->id)
            ->with(['user.profile', 'blockedBy:id,name,username'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $blocked->items(),
            'meta' => [
                'current_page' => $blocked->currentPage(),
                'last_page' => $blocked->lastPage(),
                'total' => $blocked->total(),
            ],
        ]);
    }

    /**
     * নির্দিষ্ট ইউজারকে পেজ থেকে ব্লক করা
     */
    public function blockUser(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('moderate', $page)) {
            abort(403, 'You do not have permission to block users for this page.');
        }

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $blocked = $this->enterprisePageService->blockUser(
                $page,
                $user,
                (int) $validated['target_user_id'],
                $validated['reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'User blocked from page successfully.',
                'data' => $blocked->load(['user.profile', 'blockedBy:id,name,username']),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ইউজারকে আনব্লক করা
     */
    public function unblockUser(Request $request, Page $page, int $targetUserId): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('moderate', $page)) {
            abort(403, 'You do not have permission to unblock users for this page.');
        }

        $unblocked = $this->enterprisePageService->unblockUser($page, $user, $targetUserId);

        if (! $unblocked) {
            return response()->json([
                'success' => false,
                'message' => 'User was not blocked on this page.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully.',
        ]);
    }

    /**
     * কমেন্ট মডারেশন অ্যাকশন (delete, pin, unpin)
     */
    public function moderateComment(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('moderate', $page)) {
            abort(403, 'You do not have permission to moderate comments on this page.');
        }

        $validated = $request->validate([
            'comment_id' => ['required', 'integer', 'exists:comments,id'],
            'action' => ['required', 'string', 'in:delete,pin,unpin'],
        ]);

        try {
            $this->enterprisePageService->moderateComment(
                $page,
                $user,
                (int) $validated['comment_id'],
                $validated['action']
            );

            return response()->json([
                'success' => true,
                'message' => "Comment moderation '{$validated['action']}' completed successfully.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * বাল্ক কমেন্ট মডারেশন
     */
    public function bulkModerateComments(Request $request, Page $page): JsonResponse
    {
        $user = $request->user();
        if (! Gate::forUser($user)->allows('moderate', $page)) {
            abort(403, 'You do not have permission to moderate comments on this page.');
        }

        $validated = $request->validate([
            'comment_ids' => ['required', 'array', 'min:1', 'max:50'],
            'comment_ids.*' => ['integer', 'exists:comments,id'],
            'action' => ['required', 'string', 'in:delete'],
        ]);

        $successCount = 0;
        $failedCount = 0;

        foreach ($validated['comment_ids'] as $cid) {
            try {
                $this->enterprisePageService->moderateComment($page, $user, (int) $cid, $validated['action']);
                $successCount++;
            } catch (Exception) {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk moderation completed: {$successCount} succeeded, {$failedCount} failed.",
            'meta' => [
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ],
        ]);
    }
}
