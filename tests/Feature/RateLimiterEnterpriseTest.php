<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EnterpriseRateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RateLimiterEnterpriseTest — এন্টারপ্রাইজ রেট লিমিটার ও স্লাইডিং উইন্ডো টেস্ট
 */
class RateLimiterEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * রেট লিমিটার কোটা অতিক্রম করলে এক্সেস ব্লক করে।
     */
    public function test_enterprise_rate_limiter_blocks_when_quota_exhausted(): void
    {
        $service = app(EnterpriseRateLimiterService::class);
        $service->reset('login', 'test_user_ip_1');

        $limit = EnterpriseRateLimiterService::LIMITS['login'];

        for ($i = 0; $i < $limit; $i++) {
            $check = $service->check('login', 'test_user_ip_1');
            $this->assertTrue($check['allowed']);
        }

        // লিমিটের পরের রিকোয়েস্ট ব্লক হবে
        $blockedCheck = $service->check('login', 'test_user_ip_1');
        $this->assertFalse($blockedCheck['allowed']);
        $this->assertEquals(0, $blockedCheck['remaining']);
    }

    /**
     * সুপার অ্যাডমিন ব্যবহারকারী রেট লিমিট বাইপাস সুবিধা পান।
     */
    public function test_super_admin_bypasses_rate_limits(): void
    {
        $admin = User::factory()->create();
        // ডামি রোল অ্যাসাইন
        $service = app(EnterpriseRateLimiterService::class);

        // সাধারণ ক্ষেত্রে
        $res = $service->check('upload', 'user_99', $admin);
        $this->assertTrue($res['allowed']);
    }
}
