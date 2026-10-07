<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\SubmitVerificationRequest;
use App\Models\User;
use App\Services\ProfileVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileVerificationV2Controller extends Controller
{
    public function __construct(
        protected ProfileVerificationService $verificationService
    ) {}

    /**
     * GET /api/v2/profile/verification/status
     *
     * Check current verification eligibility and latest request details.
     */
    public function status(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $eligibility = $this->verificationService->checkEligibility($user);

        return $this->successResponse(
            data: [
                'eligible' => $eligibility['eligible'],
                'reasons' => $eligibility['reasons'],
                'current_status' => $eligibility['current_status'],
                'can_resubmit' => $eligibility['can_resubmit'],
                'is_verified' => $user->hasVerifiedProfile(),
                'latest_request' => $eligibility['latest_request'],
            ],
            message: 'ভেরিফিকেশন স্ট্যাটাস সফলভাবে লোড হয়েছে।'
        );
    }

    /**
     * POST /api/v2/profile/verification/submit
     *
     * Submit an identity verification request.
     */
    public function submit(SubmitVerificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $verification = $this->verificationService->submit(
            user: $user,
            data: $request->validated(),
            frontDoc: $request->file('document_front'),
            backDoc: $request->file('document_back'),
            selfie: $request->file('selfie'),
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return $this->successResponse(
            data: $verification,
            message: 'ভেরিফিকেশন আবেদন সফলভাবে জমা দেওয়া হয়েছে। এডমিন পর্যালোচনার পর ফলাফল জানানো হবে।',
            statusCode: 201
        );
    }
}
