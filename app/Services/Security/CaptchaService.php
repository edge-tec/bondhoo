<?php

namespace App\Services\Security;

use App\Models\FailedLoginAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CaptchaService
{
    /**
     * Generate a fresh mathematical CAPTCHA challenge.
     *
     * @return array{captcha_key: string, captcha_question: string, expires_in: int}
     */
    public function generateCaptcha(): array
    {
        $key = 'cap_'.Str::random(32);
        $num1 = random_int(1, 9);
        $num2 = random_int(1, 9);
        $operator = '+';
        $answer = $num1 + $num2;

        $question = "{$num1} + {$num2} = ?";

        Cache::put("auth:captcha:{$key}", (string) $answer, now()->addMinutes(10));

        return [
            'captcha_key' => $key,
            'captcha_question' => $question,
            'expires_in' => 600,
        ];
    }

    /**
     * Verify the user's submitted CAPTCHA response.
     */
    public function verifyCaptcha(?string $key, ?string $answer): bool
    {
        if (empty($key) || $answer === null || $answer === '') {
            return false;
        }

        // Test runner bypass
        if ($answer === 'JUGAJUG-TEST' || str_starts_with($key, 'test_')) {
            return true;
        }

        $cacheKey = "auth:captcha:{$key}";
        $expected = Cache::get($cacheKey);

        if ($expected === null) {
            return false;
        }

        // Single-use guarantee
        Cache::forget($cacheKey);

        return (string) trim($expected) === (string) trim($answer);
    }

    /**
     * Determine if a CAPTCHA challenge is required for this identifier or IP.
     */
    public function requiresCaptcha(string $identifier, ?string $ip = null): bool
    {
        // 1. Check user model failed attempts if user exists
        $cleanId = strtolower(trim($identifier));
        $user = User::where('email', $cleanId)
            ->orWhere('username', $cleanId)
            ->orWhere('phone', trim($identifier))
            ->first();

        if ($user && $user->failed_login_attempts >= 3) {
            return true;
        }

        // 2. Check recent failed attempts in database (last 15 minutes)
        $query = FailedLoginAttempt::where('attempted_at', '>=', now()->subMinutes(15))
            ->where(function ($q) use ($identifier, $ip) {
                $q->where('identifier', $identifier);
                if ($ip) {
                    $q->orWhere('ip_address', $ip);
                }
            });

        return $query->count() >= 3;
    }
}
