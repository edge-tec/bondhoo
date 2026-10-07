<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Security\EnterpriseSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EnterpriseSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_fingerprint_is_deterministic(): void
    {
        $security = new EnterpriseSecurityService;

        $req1 = Request::create('/login', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Mac OS X',
            'HTTP_ACCEPT_LANGUAGE' => 'bn-BD',
        ]);
        $req2 = Request::create('/login', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Mac OS X',
            'HTTP_ACCEPT_LANGUAGE' => 'bn-BD',
        ]);

        $this->assertSame(
            $security->generateFingerprint($req1),
            $security->generateFingerprint($req2)
        );
    }

    public function test_security_risk_evaluation_detects_unseen_device(): void
    {
        $user = User::factory()->create();
        $security = new EnterpriseSecurityService;

        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '103.25.120.44',
            'HTTP_USER_AGENT' => 'Unknown Browser / 1.0',
            'HTTP_CF_IPCOUNTRY' => 'BD',
        ]);

        $evaluation = $security->evaluateLoginRisk($user, $request);

        $this->assertArrayHasKey('risk_score', $evaluation);
        $this->assertArrayHasKey('risk_level', $evaluation);
        $this->assertContains('new_device_detected', $evaluation['flags']);

        $this->assertDatabaseHas('security_risk_profiles', [
            'user_id' => $user->id,
            'last_ip' => '103.25.120.44',
            'country_code' => 'BD',
        ]);
    }
}
