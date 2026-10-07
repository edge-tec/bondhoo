<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OtpCode;
use App\Models\OtpLog;
use App\Models\User;
use App\Services\Sms\SmsGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(
        protected SmsGatewayManager $smsManager
    ) {}

    /**
     * Generate and store a new secure OTP code.
     *
     * @return array{plain_otp: string, otp_model: OtpCode}
     */
    public function generateOtp(string $identifier, string $purpose, ?User $user = null, int $expiryMinutes = 10, ?string $ip = null): array
    {
        // 1. Check if an active OTP was recently generated (cooldown check: 60s)
        $recent = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->first();

        if ($recent) {
            $secondsRemaining = 60 - now()->diffInSeconds($recent->created_at);
            throw ValidationException::withMessages([
                'otp' => ["অনুগ্রহ করে অপেক্ষা করুন। আপনি {$secondsRemaining} সেকেন্ড পর আবার নতুন কোড চাইতে পারবেন।"],
            ]);
        }

        // Invalidate older unused codes for the same identifier & purpose
        OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Generate cryptographically secure 6-digit OTP
        $plainOtp = (string) random_int(100000, 999999);
        $codeHash = $this->hashOtp($plainOtp);

        $otpModel = OtpCode::create([
            'user_id' => $user?->id,
            'identifier' => $identifier,
            'purpose' => $purpose,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'resend_available_at' => now()->addSeconds(60),
            'retry_count' => 0,
            'max_retries' => 3,
            'is_used' => false,
            'ip_address' => $ip ?: request()->ip(),
        ]);

        try {
            OtpLog::create([
                'user_id' => $user?->id,
                'identifier' => $identifier,
                'otp_code' => substr($plainOtp, 0, 2).'****',
                'type' => $purpose,
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => request()->userAgent(),
                'expires_at' => now()->addMinutes($expiryMinutes),
                'is_used' => false,
            ]);
        } catch (\Throwable $e) {
            // Ignore failure in log insertion
        }

        return [
            'plain_otp' => $plainOtp,
            'otp_model' => $otpModel,
        ];
    }

    /**
     * Dispatch SMS OTP via SmsGatewayManager.
     */
    public function sendSmsOtp(string $phone, string $plainOtp, string $purpose): bool
    {
        $message = "আপনার যুগাজুগ ওটিপি (OTP) কোড: {$plainOtp}। মেয়াদ ১০ মিনিট। কাউকে শেয়ার করবেন না।";
        $result = $this->smsManager->send($phone, $message);

        return $result['success'] ?? true;
    }

    /**
     * Verify an incoming OTP code against identifier and purpose.
     */
    public function verifyOtp(string $identifier, string $purpose, string $plainOtp, ?string $ip = null): bool
    {
        return DB::transaction(function () use ($identifier, $purpose, $plainOtp, $ip) {
            $otp = OtpCode::where('identifier', $identifier)
                ->where('purpose', $purpose)
                ->where('is_used', false)
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                throw ValidationException::withMessages([
                    'otp' => ['কোনো সক্রিয় ওটিপি (OTP) পাওয়া যায়নি। দয়া করে নতুন ওটিপির জন্য অনুরোধ করুন।'],
                ]);
            }

            if ($otp->isExpired()) {
                $otp->update(['is_used' => true]);
                throw ValidationException::withMessages([
                    'otp' => ['ওটিপি কোডের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে আবার চেষ্টা করুন।'],
                ]);
            }

            if ($otp->retry_count >= $otp->max_retries) {
                $otp->update(['is_used' => true]);
                throw ValidationException::withMessages([
                    'otp' => ['সর্বোচ্চ সংখ্যক ভুল কোড প্রবেশ করানো হয়েছে। ওটিপি বাতিল করা হলো।'],
                ]);
            }

            $submittedHash = $this->hashOtp($plainOtp);
            if (! hash_equals($otp->code_hash, $submittedHash)) {
                $otp->increment('retry_count');
                $remaining = $otp->max_retries - $otp->retry_count;

                throw ValidationException::withMessages([
                    'otp' => ["ভুল ওটিপি কোড। আপনার আর {$remaining} বার চেষ্টা করার সুযোগ আছে।"],
                ]);
            }

            // Valid code
            $otp->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

            try {
                OtpLog::where('identifier', $identifier)
                    ->where('type', $purpose)
                    ->where('is_used', false)
                    ->latest()
                    ->first()
                    ?->update([
                        'is_used' => true,
                        'verified_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                // Ignore log update failure
            }

            // Audit log
            AuditLog::create([
                'user_id' => $otp->user_id,
                'action' => 'otp.verified',
                'entity_type' => OtpCode::class,
                'entity_id' => $otp->id,
                'new_values' => [
                    'identifier' => $identifier,
                    'purpose' => $purpose,
                ],
                'ip_address' => $ip ?: request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return true;
        });
    }

    /**
     * Compute peppered SHA-256 hash of OTP.
     */
    public function hashOtp(string $otp): string
    {
        $pepper = config('app.key', 'jugajug-secret-pepper');

        return hash_hmac('sha256', trim($otp), $pepper);
    }
}
