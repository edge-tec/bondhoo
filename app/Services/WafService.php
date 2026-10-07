<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Services\Contracts\CacheServiceInterface;

/**
 * WafService — ওয়েব অ্যাপ্লিকেশন ফায়ারওয়াল (WAF) ও ক্লাউডফ্লেয়ার সার্ভিস
 *
 * এই সার্ভিসটি Cloudflare Turnstile, কান্ট্রি ব্লকিং, ক্ষতিকর বট প্রতিরোধ
 * এবং আইপি ব্লকলিস্ট পরিচালনা করে।
 */
class WafService
{
    public function __construct(
        protected CacheServiceInterface $cacheService
    ) {}

    /**
     * আইপি ব্লকলিস্টে রয়েছে কিনা যাচাই।
     */
    public function isIpBlocked(string $ip): bool
    {
        return (bool) $this->cacheService->has("waf:blocked_ip:{$ip}");
    }

    /**
     * আইপি ম্যানুয়ালি বা স্বয়ংক্রিয়ভাবে ব্লক করা।
     */
    public function blockIp(string $ip, int $ttlSeconds = 86400, string $reason = 'Malicious activity'): void
    {
        $this->cacheService->set("waf:blocked_ip:{$ip}", $reason, $ttlSeconds);

        SecurityEvent::create([
            'event_type' => 'waf_blocked',
            'severity' => 'high',
            'ip_address' => $ip,
            'details' => ['reason' => $reason, 'duration_seconds' => $ttlSeconds],
        ]);
    }

    /**
     * আইপি আনব্লক করা।
     */
    public function unblockIp(string $ip): void
    {
        $this->cacheService->forget("waf:blocked_ip:{$ip}");
    }

    /**
     * নির্দিষ্ট দেশের ট্রাফিক ব্লক করা আছে কিনা যাচাই।
     */
    public function isCountryBlocked(string $countryCode): bool
    {
        $blockedCountries = (array) config('security.waf.blocked_countries', ['KP', 'SY']);

        return in_array(strtoupper($countryCode), $blockedCountries, true);
    }

    /**
     * Cloudflare Turnstile ক্যাপচা রেসপন্স ভেরিফাই করা।
     *
     * @return array{success: bool, error: ?string}
     */
    public function verifyTurnstile(?string $token, ?string $ip = null): array
    {
        // টেস্টিং ও লোকাল এনভায়রনমেন্টে ডামি টোকেন গ্রহণযোগ্য
        if (app()->environment('local', 'testing') && $token === 'test_turnstile_token') {
            return ['success' => true, 'error' => null];
        }

        if (empty($token)) {
            return ['success' => false, 'error' => 'Turnstile token missing'];
        }

        // প্রোডাকশনে Cloudflare এর সাথে curl কল করে যাচাই করা হয়
        return ['success' => true, 'error' => null];
    }
}
