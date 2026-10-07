<?php

namespace Tests\Feature;

use App\Services\WafService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * WAFConfigurationTest — ওয়েব অ্যাপ্লিকেশন ফায়ারওয়াল ও সিকিউরিটি টেস্ট
 */
class WAFConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ক্ষতিকর আইপি ব্লক ও আনব্লক যাচাই।
     */
    public function test_waf_service_blocks_and_unblocks_ip(): void
    {
        $service = app(WafService::class);
        $testIp = '198.51.100.45';

        $this->assertFalse($service->isIpBlocked($testIp));

        $service->blockIp($testIp, 3600, 'Test malicious attack');
        $this->assertTrue($service->isIpBlocked($testIp));

        $service->unblockIp($testIp);
        $this->assertFalse($service->isIpBlocked($testIp));
    }

    /**
     * নিষিদ্ধ দেশ থেকে ট্রাফিক ব্লকিং যাচাই।
     */
    public function test_waf_service_identifies_blocked_countries(): void
    {
        $service = app(WafService::class);

        $this->assertTrue($service->isCountryBlocked('KP'));
        $this->assertFalse($service->isCountryBlocked('BD'));
        $this->assertFalse($service->isCountryBlocked('US'));
    }

    /**
     * Cloudflare Turnstile ভ্যালিডেশন টেস্ট।
     */
    public function test_waf_turnstile_verification(): void
    {
        $service = app(WafService::class);

        $valid = $service->verifyTurnstile('test_turnstile_token');
        $this->assertTrue($valid['success']);

        $invalid = $service->verifyTurnstile('');
        $this->assertFalse($invalid['success']);
    }
}
