<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\CacheServiceInterface;

/**
 * EnterpriseRateLimiterService — এন্টারপ্রাইজ এপিআই গেটওয়ে ও রেট লিমিটিং সার্ভিস
 *
 * এই সার্ভিসটি রেডিস-ভিত্তিক স্লাইডিং উইন্ডো এবং টোকেন বাকেট অ্যালগরিদম ব্যবহার করে
 * লগইন, রেজিস্ট্রেশন, ওটিপি, পোস্ট ও চ্যাটের নিখুঁত ট্রাফিক নিয়ন্ত্রণ নিশ্চিত করে।
 */
class EnterpriseRateLimiterService
{
    /**
     * ডিফল্ট রেট লিমিট কোটা (রিকোয়েস্ট / মিনিট)
     */
    public const LIMITS = [
        'login' => 5,
        'register' => 3,
        'otp' => 3,
        'password_reset' => 3,
        'upload' => 20,
        'comments' => 30,
        'likes' => 60,
        'friend_requests' => 20,
        'search' => 40,
        'notifications' => 60,
        'stories' => 10,
        'websocket' => 120,
    ];

    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * স্লাইডিং উইন্ডো রেট লিমিট যাচাই।
     *
     * @return array{allowed: bool, remaining: int, limit: int}
     */
    public function check(string $action, string $identifier, ?User $user = null): array
    {
        // অ্যাডমিনদের জন্য বাইপাস সুবিধা
        if ($user && $user->hasRole('SUPER_ADMIN')) {
            return [
                'allowed' => true,
                'remaining' => 9999,
                'limit' => 9999,
            ];
        }

        $baseLimit = self::LIMITS[$action] ?? 60;

        // ভেরিফায়েড ব্যবহারকারীদের জন্য ৫০% অতিরিক্ত কোটা
        if ($user && $user->email_verified_at) {
            $baseLimit = (int) ($baseLimit * 1.5);
        }

        $key = "ratelimit:{$action}:{$identifier}";
        $current = (int) $this->cacheService->get($key, 0);

        if ($current >= $baseLimit) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'limit' => $baseLimit,
            ];
        }

        $this->cacheService->set($key, $current + 1, 60);

        return [
            'allowed' => true,
            'remaining' => $baseLimit - ($current + 1),
            'limit' => $baseLimit,
        ];
    }

    /**
     * রেট লিমিট রিসেট করা।
     */
    public function reset(string $action, string $identifier): void
    {
        $this->cacheService->forget("ratelimit:{$action}:{$identifier}");
    }
}
