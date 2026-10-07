<?php

namespace App\Http\Controllers\Api\v2\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordV2Request;
use App\Http\Requests\Auth\LoginV2Request;
use App\Http\Requests\Auth\RegisterV2Request;
use App\Http\Requests\Auth\ResetPasswordV2Request;
use App\Http\Requests\Auth\TwoFactorV2Request;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\AuthServiceV2;
use App\Services\DeviceSessionService;
use App\Services\OtpService;
use App\Services\Security\CaptchaService;
use App\Services\Sms\SmsGatewayManager;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceV2 $authService,
        protected OtpService $otpService,
        protected DeviceSessionService $deviceSessionService,
        protected TwoFactorService $twoFactorService,
        protected SmsGatewayManager $smsManager
    ) {}

    /**
     * POST /api/v2/auth/register
     */
    public function register(RegisterV2Request $request): JsonResponse
    {
        $result = $this->authService->register($request->validated(), $request);

        return $this->successResponse(
            data: $result,
            message: 'নিবন্ধন সফল হয়েছে। আপনার ইমেইল বা মোবাইল যাচাই করতে ওটিপি কোড চেক করুন।',
            statusCode: 201
        );
    }

    /**
     * POST /api/v2/auth/login
     */
    public function login(LoginV2Request $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->login(
            identifier: $validated['identifier'],
            password: $validated['password'],
            request: $request,
            rememberMe: (bool) ($validated['remember'] ?? false),
            deviceName: $validated['device_name'] ?? null
        );

        if (! empty($result['requires_2fa'])) {
            return $this->successResponse(
                data: $result,
                message: 'টু-ফ্যাক্টর অথেন্টিকেশন কোড প্রয়োজন।',
                statusCode: 200
            );
        }

        return $this->successResponse(
            data: $result,
            message: 'লগইন সফল হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/2fa/challenge
     */
    public function verifyChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $result = $this->authService->complete2faLogin(
            $request->input('challenge_token'),
            $request->input('code'),
            $request
        );

        return $this->successResponse(
            data: $result,
            message: 'টু-ফ্যাক্টর যাচাই সফল হয়েছে। লগইন সম্পন্ন।'
        );
    }

    /**
     * POST /api/v2/auth/verify-email
     */
    public function verifyEmail(VerifyOtpRequest $request): JsonResponse
    {
        $this->authService->verifyEmail(
            $request->input('identifier'),
            $request->input('otp'),
            $request,
            $request->input('token')
        );

        return $this->successResponse(
            data: ['verified' => true],
            message: 'আপনার ইমেইল ঠিকানা সফলভাবে ভেরিফাই হয়েছে।'
        );
    }

    /**
     * GET /email/verify/{id}/{hash}
     */
    public function verifySignedEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'ভেরিফিকেশন লিঙ্কটির মেয়াদ শেষ অথবা লিঙ্কটি পরিবর্তিত হয়েছে।');
        }

        $this->authService->verifySignedUrl($request, $id, $hash);

        return redirect()->route('login')->with('success', 'আপনার ইমেইল সফলভাবে নিশ্চিত হয়েছে! এখন লগইন করতে পারেন।');
    }

    /**
     * GET /api/v2/auth/verification-status
     */
    public function verificationStatus(Request $request): JsonResponse
    {
        $email = $request->input('email') ?: $request->user()?->email;
        if (! $email) {
            return $this->errorResponse('ইমেইল অ্যাড্রেস প্রদান করুন।', 422);
        }

        $status = $this->authService->getVerificationStatus($email);

        return $this->successResponse(
            data: $status,
            message: 'ইমেইল ভেরিফিকেশন স্থিতি।'
        );
    }

    /**
     * POST /api/v2/auth/verify-mobile
     */
    public function verifyMobile(VerifyOtpRequest $request): JsonResponse
    {
        $this->authService->verifyMobile(
            $request->input('identifier'),
            $request->input('otp'),
            $request
        );

        return $this->successResponse(
            data: ['verified' => true],
            message: 'আপনার মোবাইল নম্বর সফলভাবে ভেরিফাই হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/resend-email
     */
    public function resendEmail(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $result = $this->authService->resendEmailVerification($request->input('email'), $request);

        return $this->successResponse(
            data: $result,
            message: $result['message'] ?? 'নতুন ভেরিফিকেশন ওটিপি কোড আপনার ইমেইলে পাঠানো হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/resend-otp
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string']]);
        $phone = $request->input('phone');
        $result = $this->authService->sendPhoneOtp($phone, $request);

        return $this->successResponse(
            data: $result,
            message: $result['message'] ?? 'নতুন ভেরিফিকেশন ওটিপি আপনার মোবাইলে পাঠানো হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordV2Request $request): JsonResponse
    {
        $result = $this->authService->sendPasswordResetOtp($request->input('identifier'), $request);

        return response()->json(array_merge([
            'success' => true,
            'status' => 'success',
            'message' => $result['message'],
            'data' => $result,
        ], $result));
    }

    /**
     * POST /api/v2/auth/reset-password
     */
    public function resetPassword(ResetPasswordV2Request $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->input('identifier'),
            $request->input('otp'),
            $request->input('password'),
            $request,
            $request->input('token')
        );

        return $this->successResponse(
            data: ['reset' => true],
            message: 'আপনার পাসওয়ার্ড সফলভাবে রিসেট হয়েছে। নতুন পাসওয়ার্ড দিয়ে লগইন করুন।'
        );
    }

    /**
     * GET /api/v2/auth/captcha
     */
    public function captcha(CaptchaService $captchaService): JsonResponse
    {
        $challenge = $captchaService->generateCaptcha();

        return $this->successResponse(
            data: $challenge,
            message: 'ক্যাপচা চ্যালেঞ্জ সফলভাবে তৈরি করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/2fa/setup
     */
    public function setup2fa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $setup = $this->twoFactorService->setupTotp($user);

        return $this->successResponse(
            data: $setup,
            message: 'টু-ফ্যাক্টর সেটআপ তথ্য তৈরি হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/enable-2fa
     */
    public function enable2fa(TwoFactorV2Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->twoFactorService->enable(
            $user,
            $request->input('code'),
            $request->input('type', 'app')
        );

        return $this->successResponse(
            data: ['two_factor_enabled' => true],
            message: 'টু-ফ্যাক্টর অথেন্টিকেশন সফলভাবে চালু করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/disable-2fa
     */
    public function disable2fa(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        /** @var User $user */
        $user = $request->user();
        $this->twoFactorService->disable($user, $request->input('password'));

        return $this->successResponse(
            data: ['two_factor_enabled' => false],
            message: 'টু-ফ্যাক্টর অথেন্টিকেশন নিষ্ক্রিয় করা হয়েছে।'
        );
    }

    /**
     * GET /api/v2/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse(
            data: [
                'user' => $user->load(['profile', 'settings', 'roles']),
                'sessions_count' => $user->sessions()->count(),
                'is_verified' => $user->isVerified(),
            ]
        );
    }

    /**
     * GET /api/v2/auth/sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $sessions = $this->deviceSessionService->getUserSessions($user);

        return $this->successResponse(
            data: $sessions,
            message: 'সক্রিয় সেশন তালিকা।'
        );
    }

    /**
     * DELETE /api/v2/auth/sessions/{id}
     */
    public function revokeSession(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->deviceSessionService->revokeSession($user, $id);

        return $this->successResponse(
            data: ['revoked' => true],
            message: 'সেশনটি সফলভাবে বন্ধ করা হয়েছে।'
        );
    }

    /**
     * POST /api/v2/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        if ($bearer) {
            PersonalAccessToken::findToken($bearer)?->delete();
        }

        foreach (['bondhoo_token', 'jugajug_token'] as $cookieName) {
            if ($request->hasCookie($cookieName)) {
                PersonalAccessToken::findToken((string) $request->cookie($cookieName))?->delete();
            }
        }

        /** @var User|null $user */
        $user = $request->user() ?? Auth::guard('web')->user();

        if ($user) {
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
            }
            $this->deviceSessionService->logoutCurrentDevice($user, $request);
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        try {
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $res = $this->successResponse(
            data: ['logged_out' => true],
            message: 'সফলভাবে লগআউট করা হয়েছে।'
        );

        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }

    /**
     * POST /api/v2/auth/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user() ?? Auth::guard('web')->user();

        if ($user) {
            $user->tokens()->delete();
            $user->sessions()->delete();
            $this->deviceSessionService->logoutAllDevices($user, $request);
        }

        Auth::guard('web')->logout();
        Auth::guard('admin')->logout();
        try {
            Auth::guard('sanctum')->forgetUser();
        } catch (\Throwable) {
        }
        Auth::forgetGuards();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $res = $this->successResponse(
            data: ['all_logged_out' => true],
            message: 'আপনার সমস্ত ডিভাইস থেকে সফলভাবে লগআউট করা হয়েছে।'
        );

        foreach (DeviceSessionService::getLogoutCookies() as $cookie) {
            $res->withCookie($cookie);
        }
        $res->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $res->headers->set('Pragma', 'no-cache');

        return $res;
    }
}
