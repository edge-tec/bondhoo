<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneOtpController extends Controller
{
    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * POST /api/v1/auth/send-phone-otp
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'purpose' => ['nullable', 'string', 'in:registration,phone_verification,login_2fa,password_reset'],
        ]);

        $phone = $validated['phone'];
        $purpose = $validated['purpose'] ?? 'phone_verification';
        $user = $request->user('sanctum') ?? User::where('phone', $phone)->first();

        $otpResult = $this->otpService->generateOtp(
            identifier: $phone,
            purpose: $purpose,
            user: $user,
            expiryMinutes: 10,
            ip: $request->ip()
        );

        $sent = $this->otpService->sendSmsOtp($phone, $otpResult['plain_otp'], $purpose);

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => 'AUTH_SMS_OTP_SENT',
            'entity_type' => User::class,
            'entity_id' => $user?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse(
            data: [
                'phone' => $phone,
                'resend_available_in_seconds' => 60,
                'dispatched' => $sent,
            ],
            message: 'মোবাইল নম্বরে ওটিপি কোড সফলভাবে পাঠানো হয়েছে।'
        );
    }

    /**
     * POST /api/v1/auth/verify-phone-otp
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'otp' => ['required', 'string', 'size:6'],
            'purpose' => ['nullable', 'string', 'in:registration,phone_verification,login_2fa,password_reset'],
        ]);

        $phone = $validated['phone'];
        $purpose = $validated['purpose'] ?? 'phone_verification';

        $isValid = $this->otpService->verifyOtp($phone, $purpose, $validated['otp'], $request->ip());

        if (! $isValid) {
            return $this->errorResponse('ভুল ওটিপি কোড প্রদান করা হয়েছে।', 422);
        }

        // If user is authenticated or matches phone, mark phone as verified
        $user = $request->user('sanctum') ?? User::where('phone', $phone)->first();
        if ($user) {
            $user->update(['phone_verified_at' => now()]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'AUTH_PHONE_VERIFIED',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $this->successResponse(
            data: [
                'phone' => $phone,
                'verified' => true,
                'user_id' => $user?->id,
            ],
            message: 'মোবাইল নম্বর সফলভাবে যাচাই সম্পন্ন হয়েছে।'
        );
    }
}
