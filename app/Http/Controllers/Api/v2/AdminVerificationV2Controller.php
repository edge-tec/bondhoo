<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RejectVerificationRequest;
use App\Http\Requests\Profile\RequestInfoVerificationRequest;
use App\Models\User;
use App\Services\ProfileVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVerificationV2Controller extends Controller
{
    public function __construct(
        protected ProfileVerificationService $verificationService
    ) {}

    /**
     * GET /api/v2/admin/verifications
     *
     * List verification requests with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'verification_type', 'search']);
        $perPage = (int) $request->input('per_page', 20);

        $verifications = $this->verificationService->getRequests($filters, $perPage);

        return $this->successResponse(
            data: $verifications,
            message: 'ভেরিফিকেশন রিকোয়েস্ট তালিকা সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * GET /api/v2/admin/verifications/{id}
     *
     * Show verification request detail.
     */
    public function show(int $id): JsonResponse
    {
        $verification = $this->verificationService->getVerificationDetails($id);

        return $this->successResponse(
            data: $verification,
            message: 'ভেরিফিকেশন বিস্তারিত তথ্য সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/admin/verifications/{id}/approve
     *
     * Approve verification and award badge.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $verification = $this->verificationService->getVerificationDetails($id);

        $notes = $request->input('admin_notes');

        $approved = $this->verificationService->approve(
            verification: $verification,
            admin: $admin,
            notes: $notes,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $approved,
            message: 'প্রোফাইল ভেরিফিকেশন সফলভাবে অনুমোদন করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/admin/verifications/{id}/reject
     *
     * Reject verification request with reason.
     */
    public function reject(RejectVerificationRequest $request, int $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $verification = $this->verificationService->getVerificationDetails($id);

        $rejected = $this->verificationService->reject(
            verification: $verification,
            admin: $admin,
            reason: $request->input('rejection_reason'),
            notes: $request->input('admin_notes'),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $rejected,
            message: 'প্রোফাইল ভেরিফিকেশন আবেদন বাতিল করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/admin/verifications/{id}/request-info
     *
     * Request additional information from applicant.
     */
    public function requestInfo(RequestInfoVerificationRequest $request, int $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $verification = $this->verificationService->getVerificationDetails($id);

        $updated = $this->verificationService->requestInfo(
            verification: $verification,
            admin: $admin,
            instructions: $request->input('instructions'),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $updated,
            message: 'ব্যবহারকারীর কাছে অতিরিক্ত তথ্যের জন্য অনুরোধ পাঠানো হয়েছে।'
        );
    }

    /**
     * POST /api/v2/admin/verifications/{id}/revoke
     *
     * Revoke an active verified status.
     */
    public function revoke(Request $request, int $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $verification = $this->verificationService->getVerificationDetails($id);

        $request->validate([
            'revocation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $revoked = $this->verificationService->revoke(
            verification: $verification,
            admin: $admin,
            reason: $request->input('revocation_reason'),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $revoked,
            message: 'প্রোফাইল ভেরিফিকেশন স্ট্যাটাস সফলভাবে প্রত্যাহার করা হয়েছে।'
        );
    }
}
