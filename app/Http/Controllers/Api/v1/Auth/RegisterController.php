<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\VerifyEmailMail;
use App\Models\EmailVerification;
use App\Notifications\VerifyEmailNotification;
use App\Services\AuthService;
use App\Services\Email\EmailService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected OtpService $otpService,
        protected EmailService $emailService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        $user = $result['user'];
        $token = $result['token'];
        $requiresEmailVerification = false;
        $emailSent = false;
        $emailError = null;

        if (! empty($user->email)) {
            $requiresEmailVerification = true;

            try {
                // Invalidate any older unused verification tokens
                EmailVerification::where('email', strtolower($user->email))
                    ->whereNull('verified_at')
                    ->update(['expires_at' => now()]);

                $emailOtp = $this->otpService->generateOtp($user->email, 'verify_email', $user, 60, $request->ip());
                $tokenString = Str::random(64);

                EmailVerification::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'otp_hash' => $emailOtp['otp_model']->code_hash,
                    'token' => $tokenString,
                    'expires_at' => now()->addMinutes(60),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $verificationUrl = URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes(60),
                    [
                        'id' => $user->id,
                        'hash' => sha1($user->getEmailForVerification()),
                        'token' => $tokenString,
                    ]
                );

                $mailable = new VerifyEmailMail(
                    user: $user,
                    otp: $emailOtp['plain_otp'],
                    verificationUrl: $verificationUrl,
                    expiresMinutes: 60
                );

                $emailResult = $this->emailService->sendWithResult(
                    to: $user->email,
                    mailable: $mailable,
                    emailType: 'verify_email',
                    user: $user,
                    idempotencyKey: "verify_email:{$user->id}:{$tokenString}",
                    metadata: [
                        'user_id' => $user->id,
                        'action' => 'register_verification',
                        'ip' => $request->ip(),
                    ],
                    forceSync: true
                );

                $emailSent = (bool) ($emailResult['success'] ?? false);
                $emailError = $emailResult['error'] ?? null;

                try {
                    $user->notify(new VerifyEmailNotification(
                        $emailOtp['plain_otp'],
                        $tokenString,
                        $request->ip(),
                        $request->userAgent()
                    ));
                } catch (\Throwable) {
                }
            } catch (\Throwable $e) {
                Log::error('Registration OTP email dispatch failed: '.$e->getMessage());
                $emailError = $e->getMessage();
            }
        }

        $plainOtp = isset($emailOtp['plain_otp']) ? $emailOtp['plain_otp'] : null;
        $fallbackOtp = (! $emailSent || config('app.debug')) ? $plainOtp : null;

        $message = $emailSent
            ? 'Registration successful. We have sent a 6-digit verification code to your email.'
            : 'Registration successful. Email delivery could not be completed via current SMTP ('.($emailError ?? 'Check SMTP in Admin').').';

        return $this->successResponse(
            data: [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'requires_email_verification' => $requiresEmailVerification,
                'email_sent' => $emailSent,
                'email_error' => $emailError,
                'otp' => $fallbackOtp,
                'dev_otp' => $fallbackOtp,
            ],
            message: $message,
            statusCode: 201
        );
    }
}
