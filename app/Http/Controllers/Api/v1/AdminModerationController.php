<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ModerationAuditLog;
use App\Models\ModerationPolicySetting;
use App\Models\ModerationRequest;
use App\Models\ModerationResult;
use App\Models\Post;
use App\Models\Story;
use App\Models\User;
use App\Models\UserRestriction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminModerationController extends Controller
{
    /**
     * 1. Overview metrics for AI content moderation.
     */
    public function overview(): JsonResponse
    {
        $totalScanned = ModerationRequest::count();
        $autoApproved = ModerationRequest::where('action_taken', 'auto_approved')->count();
        $queuedForReview = ModerationRequest::whereIn('status', ['manual_review', 'pending'])->count();
        $quarantined = ModerationRequest::where('status', 'quarantined')->count();
        $autoRejected = ModerationRequest::where('action_taken', 'auto_rejected')->count();

        // Breakdown by categories flagged
        $allResults = ModerationRequest::where('flagged', true)->latest()->take(200)->get();
        $categoryBreakdown = [
            'sexual' => 0,
            'sexual/minors' => 0,
            'hate' => 0,
            'harassment' => 0,
            'self-harm' => 0,
            'violence' => 0,
            'illicit' => 0,
        ];

        foreach ($allResults as $req) {
            if (is_array($req->categories)) {
                foreach ($req->categories as $cat => $isFlagged) {
                    if ($isFlagged && isset($categoryBreakdown[$cat])) {
                        $categoryBreakdown[$cat]++;
                    }
                }
            }
        }

        $recentActivity = ModerationAuditLog::latest()->take(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => [
                    'total_scanned' => $totalScanned,
                    'auto_approved' => $autoApproved,
                    'queued_for_review' => $queuedForReview,
                    'quarantined' => $quarantined,
                    'auto_rejected' => $autoRejected,
                ],
                'categories_breakdown' => $categoryBreakdown,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    /**
     * 2. Paginated moderation queue.
     */
    public function queue(Request $request): JsonResponse
    {
        $status = $request->query('status', 'manual_review');
        $contentType = $request->query('content_type');
        $perPage = (int) $request->query('per_page', 20);

        $query = ModerationRequest::with(['user:id,name,username,email'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * 3. Show details of a single moderation request.
     */
    public function show(int $id): JsonResponse
    {
        $modRequest = ModerationRequest::with(['user', 'results.reviewer'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $modRequest,
        ]);
    }

    /**
     * 4. Admin Manual Approval.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $modRequest = ModerationRequest::findOrFail($id);
        $admin = $request->user();

        DB::transaction(function () use ($modRequest, $admin, $request) {
            $modRequest->status = ModerationRequest::STATUS_APPROVED;
            $modRequest->action_taken = ModerationRequest::ACTION_ADMIN_APPROVED;
            $modRequest->rejection_reason = null;
            $modRequest->save();

            ModerationResult::create([
                'moderation_request_id' => $modRequest->id,
                'target_type' => $modRequest->moderatable_type,
                'target_id' => $modRequest->moderatable_id,
                'flagged' => false,
                'policy_decision' => ModerationResult::DECISION_APPROVE,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $request->input('notes', 'Admin approved content for publication.'),
            ]);

            // Release item into published state
            if ($modRequest->moderatable_type === Post::class && $modRequest->moderatable_id) {
                Post::where('id', $modRequest->moderatable_id)->update(['status' => 'published']);
            } elseif ($modRequest->moderatable_type === Comment::class && $modRequest->moderatable_id) {
                Comment::where('id', $modRequest->moderatable_id)->update(['status' => 'published']);
            } elseif ($modRequest->moderatable_type === Story::class && $modRequest->moderatable_id) {
                Story::where('id', $modRequest->moderatable_id)->update(['status' => 'published']);
            }

            ModerationAuditLog::log(
                userId: $admin?->id,
                action: 'manual_approve',
                targetType: $modRequest->moderatable_type,
                targetId: $modRequest->moderatable_id,
                reason: $request->input('notes', 'Admin manually approved item')
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Content successfully approved and published.',
            'data' => $modRequest->fresh(),
        ]);
    }

    /**
     * 5. Admin Manual Rejection.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $modRequest = ModerationRequest::findOrFail($id);
        $admin = $request->user();
        $reason = $request->input('reason');

        DB::transaction(function () use ($modRequest, $admin, $reason) {
            $modRequest->status = ModerationRequest::STATUS_REJECTED;
            $modRequest->action_taken = ModerationRequest::ACTION_ADMIN_REJECTED;
            $modRequest->rejection_reason = $reason;
            $modRequest->save();

            ModerationResult::create([
                'moderation_request_id' => $modRequest->id,
                'target_type' => $modRequest->moderatable_type,
                'target_id' => $modRequest->moderatable_id,
                'flagged' => true,
                'policy_decision' => ModerationResult::DECISION_REJECT,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
                'reviewer_notes' => $reason,
            ]);

            // Unpublish or remove entity
            if ($modRequest->moderatable_type === Post::class && $modRequest->moderatable_id) {
                Post::where('id', $modRequest->moderatable_id)->update(['status' => 'rejected']);
            } elseif ($modRequest->moderatable_type === Comment::class && $modRequest->moderatable_id) {
                Comment::where('id', $modRequest->moderatable_id)->update(['status' => 'rejected']);
            }

            ModerationAuditLog::log(
                userId: $admin?->id,
                action: 'manual_reject',
                targetType: $modRequest->moderatable_type,
                targetId: $modRequest->moderatable_id,
                reason: $reason
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Content has been rejected.',
            'data' => $modRequest->fresh(),
        ]);
    }

    /**
     * 6. Admin Content Escalation.
     */
    public function escalate(Request $request, int $id): JsonResponse
    {
        $modRequest = ModerationRequest::findOrFail($id);
        $admin = $request->user();
        $reason = $request->input('reason', 'Escalated for senior trust & safety review.');

        $modRequest->status = ModerationRequest::STATUS_ESCALATED;
        $modRequest->save();

        ModerationAuditLog::log(
            userId: $admin?->id,
            action: 'escalate',
            targetType: $modRequest->moderatable_type,
            targetId: $modRequest->moderatable_id,
            reason: $reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Content escalated to Senior Safety Review.',
            'data' => $modRequest,
        ]);
    }

    /**
     * 7. Suspend violating user's account or posting privilege.
     */
    public function suspendUser(Request $request, int $id): JsonResponse
    {
        $modRequest = ModerationRequest::findOrFail($id);
        $user = $modRequest->user;

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Author user not found.'], 404);
        }

        $admin = $request->user();
        $days = (int) $request->input('days', 7);
        $reason = $request->input('reason', 'Violations of platform safety standards.');

        if (class_exists(UserRestriction::class)) {
            UserRestriction::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'posting'],
                [
                    'reason' => $reason,
                    'restricted_until' => now()->addDays($days),
                    'notes' => "Account suspended by Admin ID {$admin?->id}",
                ]
            );
        }

        ModerationAuditLog::log(
            userId: $admin?->id,
            action: 'suspend_user',
            targetType: User::class,
            targetId: $user->id,
            reason: $reason,
            metadata: ['days' => $days]
        );

        return response()->json([
            'success' => true,
            'message' => "User {$user->name} has been suspended from posting for {$days} days.",
        ]);
    }

    /**
     * 8. Get current moderation policy settings and thresholds.
     */
    public function settings(): JsonResponse
    {
        $settings = [
            'adult_content_policy' => ModerationPolicySetting::get('adult_content_policy', config('moderation.adult_content_policy', 'manual_review')),
            'fail_closed' => ModerationPolicySetting::get('fail_closed', config('moderation.fail_closed', true)),
            'link_scan_enabled' => ModerationPolicySetting::get('link_scan_enabled', config('moderation.link_scan_enabled', true)),
            'threshold_sexual' => ModerationPolicySetting::get('threshold_sexual', config('moderation.thresholds.sexual', 0.70)),
            'threshold_hate' => ModerationPolicySetting::get('threshold_hate', config('moderation.thresholds.hate', 0.60)),
            'threshold_harassment' => ModerationPolicySetting::get('threshold_harassment', config('moderation.thresholds.harassment', 0.60)),
            'threshold_self_harm' => ModerationPolicySetting::get('threshold_self_harm', config('moderation.thresholds.self_harm', 0.50)),
            'threshold_sexual_minors' => ModerationPolicySetting::get('threshold_sexual_minors', config('moderation.thresholds.sexual_minors', 0.05)),
            'threshold_violence' => ModerationPolicySetting::get('threshold_violence', config('moderation.thresholds.violence', 0.70)),
            'threshold_illicit' => ModerationPolicySetting::get('threshold_illicit', config('moderation.thresholds.illicit', 0.70)),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * 9. Update moderation policy settings and thresholds.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'adult_content_policy' => ['nullable', 'in:approve,reject,manual_review'],
            'fail_closed' => ['nullable', 'boolean'],
            'link_scan_enabled' => ['nullable', 'boolean'],
            'threshold_sexual' => ['nullable', 'numeric', 'between:0.0,1.0'],
            'threshold_hate' => ['nullable', 'numeric', 'between:0.0,1.0'],
            'threshold_harassment' => ['nullable', 'numeric', 'between:0.0,1.0'],
            'threshold_self_harm' => ['nullable', 'numeric', 'between:0.0,1.0'],
            'threshold_sexual_minors' => ['nullable', 'numeric', 'between:0.0,1.0'],
            'threshold_violence' => ['nullable', 'numeric', 'between:0.0,1.0'],
            'threshold_illicit' => ['nullable', 'numeric', 'between:0.0,1.0'],
        ]);

        $admin = $request->user();
        $fields = $request->only([
            'adult_content_policy',
            'fail_closed',
            'link_scan_enabled',
            'threshold_sexual',
            'threshold_hate',
            'threshold_harassment',
            'threshold_self_harm',
            'threshold_sexual_minors',
            'threshold_violence',
            'threshold_illicit',
        ]);

        foreach ($fields as $key => $value) {
            if ($value !== null) {
                ModerationPolicySetting::set($key, $value, 'Updated by admin', $admin?->id);
            }
        }

        ModerationAuditLog::log(
            userId: $admin?->id,
            action: 'update_policy',
            reason: 'Admin updated moderation policies and thresholds',
            metadata: $fields
        );

        return response()->json([
            'success' => true,
            'message' => 'Moderation policy settings updated successfully.',
            'data' => $this->settings()->getData(true)['data'],
        ]);
    }
}
